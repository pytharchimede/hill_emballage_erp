<?php

/**
 * Modèle Vente pour HILL EMBALLAGE
 */

class Sale
{
    private $conn;
    private $table = 'sales';

    public $id;
    public $numero_vente;
    public $client_id;
    public $depot_id;
    public $vendeur_id;
    public $assignment_id; // nouvelle référence vers une assignation vendeur
    public $type_vente;
    public $montant_total;
    public $montant_paye;
    public $montant_restant;
    public $statut;
    public $date_echeance;
    public $notes;
    public $created_at;
    public $updated_at;

    // Détails de vente
    public $details = [];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Générer un numéro de vente unique
     */
    private function generateNumeroVente()
    {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) + 1 as next_id FROM " . $this->table . " WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return 'V' . $date . str_pad($row['next_id'], 4, '0', STR_PAD_LEFT);
    }

    /**
     * Créer une nouvelle vente
     */
    public function create()
    {
        try {
            $this->conn->beginTransaction();

            // Génération du numéro de vente
            if (empty($this->numero_vente)) {
                $this->numero_vente = $this->generateNumeroVente();
            }

            // Insertion de la vente
            $sql = "INSERT INTO " . $this->table . " 
                    SET numero_vente = :numero_vente,
                        client_id = :client_id,
                        depot_id = :depot_id,
                        vendeur_id = :vendeur_id,
                        assignment_id = :assignment_id,
                        type_vente = :type_vente,
                        montant_total = :montant_total,
                        montant_paye = :montant_paye,
                        montant_restant = :montant_restant,
                        statut = :statut,
                        date_echeance = :date_echeance,
                        notes = :notes";

            $stmt = $this->conn->prepare($sql);

            // Sanitize
            $this->notes = htmlspecialchars(strip_tags($this->notes));

            // Bind des paramètres
            $stmt->bindParam(':numero_vente', $this->numero_vente);
            $stmt->bindParam(':client_id', $this->client_id);
            $stmt->bindParam(':depot_id', $this->depot_id);
            $stmt->bindParam(':vendeur_id', $this->vendeur_id);
            $stmt->bindParam(':assignment_id', $this->assignment_id);
            $stmt->bindParam(':type_vente', $this->type_vente);
            $stmt->bindParam(':montant_total', $this->montant_total);
            $stmt->bindParam(':montant_paye', $this->montant_paye);
            $stmt->bindParam(':montant_restant', $this->montant_restant);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':date_echeance', $this->date_echeance);
            $stmt->bindParam(':notes', $this->notes);

            $stmt->execute();
            $sale_id = $this->conn->lastInsertId();

            // Insertion des détails de vente
            if (!empty($this->details)) {
                $this->insertSaleDetails($sale_id);
            }

            // Mise à jour du stock
            $this->updateStock();

            // Si la vente est liée à une assignation vendeur, mettre à jour le suivi d'assignation
            $this->updateAssignmentTracking();

            // Mise à jour du solde client si vente à crédit
            if ($this->type_vente == 'credit' && $this->montant_restant > 0) {
                $this->updateClientCredit();
            }

            // Attribution des points de fidélité
            $this->addFidelityPoints($sale_id);

            $this->conn->commit();
            return $sale_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Insérer les détails de vente
     */
    private function insertSaleDetails($sale_id)
    {
        $sql = "INSERT INTO sale_details (sale_id, product_id, quantite, prix_unitaire, sous_total) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        foreach ($this->details as $detail) {
            $stmt->execute([
                $sale_id,
                $detail['product_id'],
                $detail['quantite'],
                $detail['prix_unitaire'],
                $detail['sous_total']
            ]);
        }
    }

    /**
     * Mettre à jour le stock après vente
     */
    private function updateStock()
    {
        // Si la vente provient d'une assignation vendeur, on consomme d'abord la réserve
        if (!empty($this->assignment_id)) {
            $sql = "UPDATE stock SET quantite_reservee = quantite_reservee - ? 
                    WHERE depot_id = ? AND product_id = ? AND quantite_reservee >= ?";
            $stmt = $this->conn->prepare($sql);
            foreach ($this->details as $detail) {
                $stmt->execute([
                    $detail['quantite'],
                    $this->depot_id,
                    $detail['product_id'],
                    $detail['quantite']
                ]);
            }
        } else {
            // Vente standard: on décrémente le stock disponible
            $sql = "UPDATE stock SET quantite_disponible = quantite_disponible - ? 
                    WHERE depot_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            foreach ($this->details as $detail) {
                $stmt->execute([
                    $detail['quantite'],
                    $this->depot_id,
                    $detail['product_id']
                ]);
            }
        }
    }

    /**
     * Mettre à jour l'assignation (quantités vendues et montants agrégés)
     */
    private function updateAssignmentTracking()
    {
        if (empty($this->assignment_id)) {
            return;
        }

        // Mettre à jour les quantités vendues par produit dans vendor_assignment_details
        $upd = $this->conn->prepare("UPDATE vendor_assignment_details SET qty_sold = qty_sold + ? WHERE assignment_id = ? AND product_id = ?");
        $qtySoldTotal = 0;
        foreach ($this->details as $detail) {
            $q = (float)$detail['quantite'];
            $pid = (int)$detail['product_id'];
            $upd->execute([$q, (int)$this->assignment_id, $pid]);
            $qtySoldTotal += $q;
        }

        // Mettre à jour les montants cumulés
        $agg = $this->conn->prepare("UPDATE vendor_assignments SET qty_sold = qty_sold + ?, amount_cash_collected = amount_cash_collected + ?, amount_credit_outstanding = amount_credit_outstanding + ? WHERE id = ?");
        $agg->execute([
            $qtySoldTotal,
            (float)$this->montant_paye,
            (float)$this->montant_restant,
            (int)$this->assignment_id
        ]);
    }

    /**
     * Mettre à jour le crédit client
     */
    private function updateClientCredit()
    {
        $sql = "UPDATE clients SET solde_credit = solde_credit + ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->montant_restant, $this->client_id]);
    }

    /**
     * Attribuer des points de fidélité
     */
    private function addFidelityPoints($sale_id)
    {
        // Calculer les points basés sur les produits
        $total_points = 0;
        foreach ($this->details as $detail) {
            $sql = "SELECT points_fidelite FROM products WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$detail['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_points += $product['points_fidelite'] * $detail['quantite'];
        }

        if ($total_points > 0) {
            // Mettre à jour les points du client
            $sql = "UPDATE clients SET points_fidelite = points_fidelite + ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$total_points, $this->client_id]);

            // Historique des points
            $sql = "INSERT INTO fidelity_history (client_id, points, type_operation, description, sale_id) 
                    VALUES (?, ?, 'gain', 'Points gagnés pour vente', ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->client_id, $total_points, $sale_id]);
        }
    }

    /**
     * Lire toutes les ventes
     */
    public function read()
    {
        $sql = "SELECT s.*, 
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client,
                       d.nom as depot_nom, d.zone as depot_zone,
                       u.full_name as vendeur_nom
                FROM " . $this->table . " s
                LEFT JOIN clients c ON s.client_id = c.id
                LEFT JOIN depots d ON s.depot_id = d.id  
                LEFT JOIN users u ON s.vendeur_id = u.id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Lire une vente par ID
     */
    public function readOne()
    {
        $sql = "SELECT s.*, 
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client, c.telephone as client_telephone,
                       d.nom as depot_nom, d.zone as depot_zone,
                       u.full_name as vendeur_nom
                FROM " . $this->table . " s
                LEFT JOIN clients c ON s.client_id = c.id
                LEFT JOIN depots d ON s.depot_id = d.id
                LEFT JOIN users u ON s.vendeur_id = u.id
                WHERE s.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Récupérer les détails de la vente
            $details_sql = "SELECT sd.*, p.nom as product_nom, p.code_produit 
                           FROM sale_details sd
                           LEFT JOIN products p ON sd.product_id = p.id
                           WHERE sd.sale_id = ?";

            $details_stmt = $this->conn->prepare($details_sql);
            $details_stmt->execute([$this->id]);

            $row['details'] = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $row;
    }

    /**
     * Mettre à jour le statut d'une vente
     */
    public function updateStatus($nouveau_statut)
    {
        $sql = "UPDATE " . $this->table . " SET statut = ? WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $nouveau_statut);
        $stmt->bindParam(2, $this->id);

        return $stmt->execute();
    }

    /**
     * Obtenir les ventes d'un client
     */
    public function getByClient($client_id)
    {
        $sql = "SELECT s.*, d.nom as depot_nom, u.full_name as vendeur_nom
                FROM " . $this->table . " s
                LEFT JOIN depots d ON s.depot_id = d.id
                LEFT JOIN users u ON s.vendeur_id = u.id
                WHERE s.client_id = ?
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$client_id]);

        return $stmt;
    }

    /**
     * Obtenir les ventes d'un dépôt
     */
    public function getByDepot($depot_id)
    {
        $sql = "SELECT s.*, 
                       c.nom as client_nom, c.prenoms as client_prenoms, c.code_client,
                       u.full_name as vendeur_nom
                FROM " . $this->table . " s
                LEFT JOIN clients c ON s.client_id = c.id
                LEFT JOIN users u ON s.vendeur_id = u.id
                WHERE s.depot_id = ?
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$depot_id]);

        return $stmt;
    }

    /**
     * Obtenir les statistiques des ventes
     */
    public function getStats($depot_id = null, $date_debut = null, $date_fin = null)
    {
        $where_conditions = ['1=1'];
        $params = [];

        if ($depot_id) {
            $where_conditions[] = 's.depot_id = ?';
            $params[] = $depot_id;
        }

        if ($date_debut && $date_fin) {
            $where_conditions[] = 's.created_at BETWEEN ? AND ?';
            $params[] = $date_debut . ' 00:00:00';
            $params[] = $date_fin . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "SELECT 
                    COUNT(*) as total_ventes,
                    SUM(montant_total) as chiffre_affaires,
                    SUM(montant_paye) as montant_encaisse,
                    SUM(montant_restant) as montant_credit,
                    COUNT(CASE WHEN type_vente = 'comptant' THEN 1 END) as ventes_comptant,
                    COUNT(CASE WHEN type_vente = 'credit' THEN 1 END) as ventes_credit,
                    COUNT(CASE WHEN statut = 'solde' THEN 1 END) as ventes_soldees
                FROM " . $this->table . " s
                WHERE " . $where_clause;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir le top des produits vendus
     */
    public function getTopProducts($limit = 10, $depot_id = null)
    {
        $where_condition = '';
        $params = [];

        if ($depot_id) {
            $where_condition = 'WHERE s.depot_id = ?';
            $params[] = $depot_id;
        }

        $sql = "SELECT p.nom, p.code_produit, 
                       SUM(sd.quantite) as total_vendu,
                       SUM(sd.sous_total) as chiffre_affaires
                FROM sale_details sd
                INNER JOIN " . $this->table . " s ON sd.sale_id = s.id
                INNER JOIN products p ON sd.product_id = p.id
                " . $where_condition . "
                GROUP BY p.id
                ORDER BY total_vendu DESC
                LIMIT ?";

        $params[] = $limit;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }
}
