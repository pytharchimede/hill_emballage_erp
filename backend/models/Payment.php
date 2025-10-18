<?php

/**
 * Modèle Payment pour HILL EMBALLAGE
 */

class Payment
{
    private $conn;
    private $table = 'payments';

    public $id;
    public $numero_paiement;
    public $sale_id;
    public $client_id;
    public $montant;
    public $mode_paiement;
    public $reference_externe;
    public $notes;
    public $photo_preuve;
    public $receveur_id;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Générer un numéro de paiement unique
     */
    private function generateNumeroPaiement()
    {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) + 1 as next_id FROM " . $this->table . " WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return 'P' . $date . str_pad($row['next_id'], 4, '0', STR_PAD_LEFT);
    }

    /**
     * Créer un nouveau paiement
     */
    public function create()
    {
        try {
            $this->conn->beginTransaction();

            // Génération du numéro de paiement
            if (empty($this->numero_paiement)) {
                $this->numero_paiement = $this->generateNumeroPaiement();
            }

            // Vérifier que le montant ne dépasse pas le montant restant
            $sql = "SELECT montant_restant FROM sales WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->sale_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Vente introuvable");
            }

            if ($this->montant > $sale['montant_restant']) {
                throw new Exception("Le montant du paiement dépasse le montant restant dû");
            }

            // Insertion du paiement
            $sql = "INSERT INTO " . $this->table . " 
                    SET numero_paiement = :numero_paiement,
                        sale_id = :sale_id,
                        client_id = :client_id,
                        montant = :montant,
                        mode_paiement = :mode_paiement,
                        reference_externe = :reference_externe,
                        notes = :notes,
                        photo_preuve = :photo_preuve,
                        receveur_id = :receveur_id";

            $stmt = $this->conn->prepare($sql);

            // Sanitize
            $this->notes = htmlspecialchars(strip_tags($this->notes));
            $this->reference_externe = htmlspecialchars(strip_tags($this->reference_externe));

            // Bind des paramètres
            $stmt->bindParam(':numero_paiement', $this->numero_paiement);
            $stmt->bindParam(':sale_id', $this->sale_id);
            $stmt->bindParam(':client_id', $this->client_id);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':mode_paiement', $this->mode_paiement);
            $stmt->bindParam(':reference_externe', $this->reference_externe);
            $stmt->bindParam(':notes', $this->notes);
            $stmt->bindParam(':photo_preuve', $this->photo_preuve);
            $stmt->bindParam(':receveur_id', $this->receveur_id);

            $stmt->execute();
            $payment_id = $this->conn->lastInsertId();

            // Mettre à jour la vente
            $this->updateSaleAfterPayment();

            $this->conn->commit();
            return $payment_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour la vente après paiement
     */
    private function updateSaleAfterPayment()
    {
        // Calculer le nouveau montant payé et restant
        $sql = "UPDATE sales 
                SET montant_paye = montant_paye + ?,
                    montant_restant = montant_restant - ?,
                    statut = CASE 
                        WHEN (montant_restant - ?) = 0 THEN 'solde'
                        WHEN (montant_restant - ?) < montant_total THEN 'partiel'
                        ELSE statut
                    END
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $this->montant,
            $this->montant,
            $this->montant,
            $this->montant,
            $this->sale_id
        ]);

        // Mettre à jour le solde crédit du client
        $sql = "UPDATE clients SET solde_credit = solde_credit - ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->montant, $this->client_id]);
    }

    /**
     * Lire tous les paiements
     */
    public function read()
    {
        $sql = "SELECT p.*, 
                       s.numero_vente, s.montant_total as vente_montant_total,
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client,
                       u.full_name as receveur_nom
                FROM " . $this->table . " p
                LEFT JOIN sales s ON p.sale_id = s.id
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.receveur_id = u.id
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Lire un paiement par ID
     */
    public function readOne()
    {
        $sql = "SELECT p.*, 
                       s.numero_vente, s.montant_total as vente_montant_total, s.montant_restant,
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client, c.telephone as client_telephone,
                       u.full_name as receveur_nom
                FROM " . $this->table . " p
                LEFT JOIN sales s ON p.sale_id = s.id
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.receveur_id = u.id
                WHERE p.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir les paiements d'une vente
     */
    public function getBySale($sale_id)
    {
        $sql = "SELECT p.*, u.full_name as receveur_nom
                FROM " . $this->table . " p
                LEFT JOIN users u ON p.receveur_id = u.id
                WHERE p.sale_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$sale_id]);

        return $stmt;
    }

    /**
     * Obtenir les paiements d'un client
     */
    public function getByClient($client_id)
    {
        $sql = "SELECT p.*, 
                       s.numero_vente, s.montant_total as vente_montant_total,
                       u.full_name as receveur_nom
                FROM " . $this->table . " p
                LEFT JOIN sales s ON p.sale_id = s.id
                LEFT JOIN users u ON p.receveur_id = u.id
                WHERE p.client_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$client_id]);

        return $stmt;
    }

    /**
     * Obtenir les paiements par période
     */
    public function getByPeriod($date_debut, $date_fin, $receveur_id = null)
    {
        $where_conditions = ['p.created_at BETWEEN ? AND ?'];
        $params = [$date_debut . ' 00:00:00', $date_fin . ' 23:59:59'];

        if ($receveur_id) {
            $where_conditions[] = 'p.receveur_id = ?';
            $params[] = $receveur_id;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "SELECT p.*, 
                       s.numero_vente,
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client,
                       u.full_name as receveur_nom
                FROM " . $this->table . " p
                LEFT JOIN sales s ON p.sale_id = s.id
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.receveur_id = u.id
                WHERE " . $where_clause . "
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Obtenir les statistiques des paiements
     */
    public function getStats($date_debut = null, $date_fin = null, $receveur_id = null)
    {
        $where_conditions = ['1=1'];
        $params = [];

        if ($date_debut && $date_fin) {
            $where_conditions[] = 'p.created_at BETWEEN ? AND ?';
            $params[] = $date_debut . ' 00:00:00';
            $params[] = $date_fin . ' 23:59:59';
        }

        if ($receveur_id) {
            $where_conditions[] = 'p.receveur_id = ?';
            $params[] = $receveur_id;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "SELECT 
                    COUNT(*) as total_paiements,
                    SUM(p.montant) as montant_total_encaisse,
                    COUNT(CASE WHEN p.mode_paiement = 'especes' THEN 1 END) as paiements_especes,
                    COUNT(CASE WHEN p.mode_paiement = 'mobile_money' THEN 1 END) as paiements_mobile,
                    COUNT(CASE WHEN p.mode_paiement = 'cheque' THEN 1 END) as paiements_cheque,
                    COUNT(CASE WHEN p.mode_paiement = 'virement' THEN 1 END) as paiements_virement,
                    AVG(p.montant) as montant_moyen,
                    COUNT(DISTINCT p.client_id) as clients_distincts
                FROM " . $this->table . " p
                WHERE " . $where_clause;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir les rappels de paiement (clients en retard)
     */
    public function getRappelsPaiement()
    {
        $sql = "SELECT 
                    c.id as client_id, c.nom, c.prenoms, c.telephone, c.code_client,
                    SUM(s.montant_restant) as total_du,
                    COUNT(s.id) as nombre_factures,
                    MIN(s.date_echeance) as plus_ancienne_echeance,
                    MAX(s.date_echeance) as derniere_echeance
                FROM clients c
                INNER JOIN sales s ON c.id = s.client_id
                WHERE s.statut IN ('en_cours', 'partiel') 
                AND s.date_echeance < CURDATE()
                AND s.montant_restant > 0
                GROUP BY c.id
                ORDER BY total_du DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Supprimer un paiement (avec annulation des effets sur la vente)
     */
    public function delete()
    {
        try {
            $this->conn->beginTransaction();

            // Récupérer les détails du paiement à supprimer
            $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception("Paiement introuvable");
            }

            // Annuler les effets sur la vente
            $sql = "UPDATE sales 
                    SET montant_paye = montant_paye - ?,
                        montant_restant = montant_restant + ?,
                        statut = CASE 
                            WHEN (montant_restant + ?) = montant_total THEN 'en_cours'
                            WHEN (montant_restant + ?) > 0 THEN 'partiel'
                            ELSE 'solde'
                        END
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $payment['montant'],
                $payment['montant'],
                $payment['montant'],
                $payment['montant'],
                $payment['sale_id']
            ]);

            // Rétablir le solde crédit du client
            $sql = "UPDATE clients SET solde_credit = solde_credit + ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$payment['montant'], $payment['client_id']]);

            // Supprimer le paiement
            $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
