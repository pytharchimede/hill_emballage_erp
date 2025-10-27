<?php

/**
 * Modèle Assignation Vendeur (sorties de vente itinérante)
 */

class VendorAssignment
{
    private $conn;
    private $assignmentsTable = 'vendor_assignments';
    private $detailsTable = 'vendor_assignment_details';
    private $settlementsTable = 'vendor_settlements';
    private $balancesTable = 'vendor_balances';
    private $balanceHistoryTable = 'vendor_balance_history';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function generateNumber()
    {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) + 1 as next_id FROM {$this->assignmentsTable} WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return 'A' . $date . str_pad($row['next_id'], 4, '0', STR_PAD_LEFT);
    }

    /**
     * Créer une assignation et réserver le stock
     * @param int $depot_id
     * @param int $vendeur_id
     * @param int $assigner_id
     * @param array $details [ [product_id, quantite, unit_price?], ... ]
     * @param string|null $notes
     * @return int assignment id
     */
    public function create($depot_id, $vendeur_id, $assigner_id, array $details, $notes = null)
    {
        try {
            $this->conn->beginTransaction();

            $numero = $this->generateNumber();
            $sql = "INSERT INTO {$this->assignmentsTable} (numero, depot_id, vendeur_id, assigner_id, notes) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$numero, $depot_id, $vendeur_id, $assigner_id, $notes]);
            $assignment_id = (int)$this->conn->lastInsertId();

            // Réserver le stock et créer les détails
            $totalQty = 0;
            $ins = $this->conn->prepare("INSERT INTO {$this->detailsTable} (assignment_id, product_id, qty_assigned, unit_price) VALUES (?, ?, ?, ?)");
            $reserve = $this->conn->prepare("UPDATE stock SET quantite_disponible = quantite_disponible - ?, quantite_reservee = quantite_reservee + ? WHERE depot_id = ? AND product_id = ? AND quantite_disponible >= ?");
            foreach ($details as $d) {
                $pid = (int)$d['product_id'];
                $q = (float)$d['quantite'];
                $pu = isset($d['unit_price']) ? (float)$d['unit_price'] : null;
                if ($q <= 0) continue;
                // Réserver (contrôle quantité)
                $ok = $reserve->execute([$q, $q, $depot_id, $pid, $q]);
                if (!$ok || $reserve->rowCount() === 0) {
                    throw new Exception("Stock insuffisant pour le produit #$pid");
                }
                $ins->execute([$assignment_id, $pid, $q, $pu]);
                $totalQty += $q;
            }

            // Mettre à jour l'agrégat
            $up = $this->conn->prepare("UPDATE {$this->assignmentsTable} SET qty_total = ? WHERE id = ?");
            $up->execute([$totalQty, $assignment_id]);

            $this->conn->commit();
            return $assignment_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Retour de produits (libérer la réserve vers le stock dispo)
     * @param int $assignment_id
     * @param int $depot_id
     * @param array $returns [ [product_id, quantite], ... ]
     */
    public function registerReturns($assignment_id, $depot_id, array $returns)
    {
        try {
            $this->conn->beginTransaction();
            $updStock = $this->conn->prepare("UPDATE stock SET quantite_reservee = quantite_reservee - ?, quantite_disponible = quantite_disponible + ? WHERE depot_id = ? AND product_id = ? AND quantite_reservee >= ?");
            $updDetail = $this->conn->prepare("UPDATE {$this->detailsTable} SET qty_returned = qty_returned + ? WHERE assignment_id = ? AND product_id = ?");
            $totalReturned = 0;
            foreach ($returns as $r) {
                $pid = (int)$r['product_id'];
                $q = (float)$r['quantite'];
                if ($q <= 0) continue;
                $ok = $updStock->execute([$q, $q, $depot_id, $pid, $q]);
                if (!$ok || $updStock->rowCount() === 0) {
                    throw new Exception("Réserve insuffisante à libérer pour le produit #$pid");
                }
                $updDetail->execute([$q, $assignment_id, $pid]);
                $totalReturned += $q;
            }
            if ($totalReturned > 0) {
                $agg = $this->conn->prepare("UPDATE {$this->assignmentsTable} SET qty_returned = qty_returned + ? WHERE id = ?");
                $agg->execute([$totalReturned, $assignment_id]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Recalcule les agrégats (quantités vendues et montants) à partir des ventes liées
     */
    public function recomputeSummary($assignment_id)
    {
        // Ventes liées (table sales)
        $sql = "SELECT COALESCE(SUM(montant_paye),0) as cash, COALESCE(SUM(montant_restant),0) as credit, COALESCE(SUM(montant_total),0) as total FROM sales WHERE assignment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$assignment_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cash' => 0, 'credit' => 0, 'total' => 0];

        // Quantités vendues par détails de vente
        $sqlQ = "SELECT sd.product_id, COALESCE(SUM(sd.quantite),0) as qte FROM sale_details sd INNER JOIN sales s ON sd.sale_id = s.id WHERE s.assignment_id = ? GROUP BY sd.product_id";
        $stmtQ = $this->conn->prepare($sqlQ);
        $stmtQ->execute([$assignment_id]);
        $qtySoldTotal = 0;
        $updDetail = $this->conn->prepare("UPDATE {$this->detailsTable} SET qty_sold = ? WHERE assignment_id = ? AND product_id = ?");
        while ($r = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
            $updDetail->execute([(float)$r['qte'], $assignment_id, (int)$r['product_id']]);
            $qtySoldTotal += (float)$r['qte'];
        }

        $up = $this->conn->prepare("UPDATE {$this->assignmentsTable} SET qty_sold = ?, amount_cash_collected = ?, amount_credit_outstanding = ? WHERE id = ?");
        $up->execute([$qtySoldTotal, $row['cash'], $row['credit'], $assignment_id]);

        return [
            'qty_sold' => $qtySoldTotal,
            'cash' => (float)$row['cash'],
            'credit' => (float)$row['credit']
        ];
    }

    /**
     * Clôturer l'assignation: enregistrer un règlement (cash versé) et reporter le reste en solde vendeur
     */
    public function settleAndClose($assignment_id, $vendeur_id, $receiver_id, $cashPaid, $notes = null)
    {
        try {
            $this->conn->beginTransaction();

            // Recalcul
            $sum = $this->recomputeSummary($assignment_id);
            $cashExpected = (float)$sum['cash'];
            $creditOutstanding = (float)$sum['credit'];

            // Enregistrer le règlement (cash versé)
            $ins = $this->conn->prepare("INSERT INTO {$this->settlementsTable} (assignment_id, vendeur_id, receiver_id, amount_cash, amount_credit, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$assignment_id, $vendeur_id, $receiver_id, (float)$cashPaid, $creditOutstanding, $notes]);

            // Mettre à jour l'assignation
            $this->conn->prepare("UPDATE {$this->assignmentsTable} SET status = 'closed', closed_at = NOW(), amount_cash_collected = ?, amount_credit_outstanding = ? WHERE id = ?")
                ->execute([(float)$cashPaid, $creditOutstanding, $assignment_id]);

            // Solde vendeur: on considère que le crédit non encaissé est dû par le vendeur (à régulariser plus tard)
            // balance += creditOutstanding - (cashPaid - cashExpected) ; mais on suppose cashPaid == cashExpected attendu pour éviter écart
            $this->upsertVendorBalance($vendeur_id, $creditOutstanding, 'assignment_close');

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function upsertVendorBalance($vendeur_id, $delta, $reason)
    {
        // Upsert
        $stmt = $this->conn->prepare("INSERT INTO {$this->balancesTable} (vendeur_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)");
        $stmt->execute([(int)$vendeur_id, (float)$delta]);
        // Historique
        $hist = $this->conn->prepare("INSERT INTO {$this->balanceHistoryTable} (vendeur_id, assignment_id, change_amount, reason) VALUES (?, NULL, ?, ?)");
        $hist->execute([(int)$vendeur_id, (float)$delta, substr($reason, 0, 100)]);
    }

    public function getOpenByVendeur($vendeur_id)
    {
        $sql = "SELECT * FROM {$this->assignmentsTable} WHERE vendeur_id = ? AND status = 'open' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([(int)$vendeur_id]);
        return $stmt;
    }
}
