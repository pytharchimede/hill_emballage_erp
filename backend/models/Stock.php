<?php

/**
 * Modèle Stock pour HILL EMBALLAGE
 */

class Stock
{
    private $conn;
    private $table = 'stock';

    public $id;
    public $depot_id;
    public $product_id;
    public $quantite_disponible;
    public $quantite_reservee;
    public $seuil_alerte;
    public $last_inventory;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Créer ou mettre à jour un stock
     */
    public function upsert()
    {
        $sql = "INSERT INTO " . $this->table . " 
                (depot_id, product_id, quantite_disponible, quantite_reservee, seuil_alerte)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                quantite_disponible = VALUES(quantite_disponible),
                quantite_reservee = VALUES(quantite_reservee),
                seuil_alerte = VALUES(seuil_alerte)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $this->depot_id,
            $this->product_id,
            $this->quantite_disponible,
            $this->quantite_reservee,
            $this->seuil_alerte
        ]);
    }

    /**
     * Lire le stock d'un dépôt
     */
    public function readByDepot($depot_id)
    {
        $sql = "SELECT s.*, 
                       p.nom as product_nom, p.code_produit, p.unite, p.prix_unitaire,
                       d.nom as depot_nom, d.code_depot,
                       CASE 
                           WHEN s.quantite_disponible <= s.seuil_alerte THEN 'critique'
                           WHEN s.quantite_disponible <= (s.seuil_alerte * 2) THEN 'bas'
                           ELSE 'normal'
                       END as niveau_stock
                FROM " . $this->table . " s
                LEFT JOIN products p ON s.product_id = p.id
                LEFT JOIN depots d ON s.depot_id = d.id
                WHERE s.depot_id = ?
                ORDER BY s.quantite_disponible ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$depot_id]);

        return $stmt;
    }

    /**
     * Lire tout le stock avec filtres
     */
    public function read($depot_id = null, $product_id = null, $alert_only = false)
    {
        $where_conditions = ['1=1'];
        $params = [];

        if ($depot_id) {
            $where_conditions[] = 's.depot_id = ?';
            $params[] = $depot_id;
        }

        if ($product_id) {
            $where_conditions[] = 's.product_id = ?';
            $params[] = $product_id;
        }

        if ($alert_only) {
            $where_conditions[] = 's.quantite_disponible <= s.seuil_alerte';
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "SELECT s.*, 
                       p.nom as product_nom, p.code_produit, p.unite, p.prix_unitaire,
                       d.nom as depot_nom, d.code_depot, d.zone,
                       CASE 
                           WHEN s.quantite_disponible <= s.seuil_alerte THEN 'critique'
                           WHEN s.quantite_disponible <= (s.seuil_alerte * 2) THEN 'bas'
                           ELSE 'normal'
                       END as niveau_stock
                FROM " . $this->table . " s
                LEFT JOIN products p ON s.product_id = p.id
                LEFT JOIN depots d ON s.depot_id = d.id
                WHERE " . $where_clause . "
                ORDER BY d.nom, s.quantite_disponible ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Mettre à jour la quantité disponible
     */
    public function updateQuantite($depot_id, $product_id, $quantite, $operation = 'add')
    {
        $operator = ($operation == 'add') ? '+' : '-';

        $sql = "UPDATE " . $this->table . " 
                SET quantite_disponible = quantite_disponible " . $operator . " ?
                WHERE depot_id = ? AND product_id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$quantite, $depot_id, $product_id]);
    }

    /**
     * Réserver du stock
     */
    public function reserverStock($depot_id, $product_id, $quantite)
    {
        try {
            $this->conn->beginTransaction();

            // Vérifier disponibilité
            $sql = "SELECT quantite_disponible FROM " . $this->table . " 
                    WHERE depot_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$depot_id, $product_id]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stock || $stock['quantite_disponible'] < $quantite) {
                throw new Exception("Stock insuffisant");
            }

            // Réserver
            $sql = "UPDATE " . $this->table . " 
                    SET quantite_disponible = quantite_disponible - ?,
                        quantite_reservee = quantite_reservee + ?
                    WHERE depot_id = ? AND product_id = ?";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$quantite, $quantite, $depot_id, $product_id]);

            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Libérer du stock réservé
     */
    public function libererStock($depot_id, $product_id, $quantite)
    {
        $sql = "UPDATE " . $this->table . " 
                SET quantite_disponible = quantite_disponible + ?,
                    quantite_reservee = quantite_reservee - ?
                WHERE depot_id = ? AND product_id = ? AND quantite_reservee >= ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$quantite, $quantite, $depot_id, $product_id, $quantite]);
    }

    /**
     * Consommer uniquement la réserve (lors d'une vente issue d'une assignation)
     */
    public function consommerReserve($depot_id, $product_id, $quantite)
    {
        $sql = "UPDATE " . $this->table . " 
                SET quantite_reservee = quantite_reservee - ?
                WHERE depot_id = ? AND product_id = ? AND quantite_reservee >= ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$quantite, $depot_id, $product_id, $quantite]);
    }

    /**
     * Effectuer un inventaire
     */
    public function inventaire($depot_id, $product_id, $nouvelle_quantite, $user_id, $notes = '')
    {
        try {
            $this->conn->beginTransaction();

            // Récupérer l'ancienne quantité
            $sql = "SELECT quantite_disponible FROM " . $this->table . " 
                    WHERE depot_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$depot_id, $product_id]);
            $ancien_stock = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ancien_stock) {
                throw new Exception("Stock inexistant");
            }

            $difference = $nouvelle_quantite - $ancien_stock['quantite_disponible'];

            // Mettre à jour le stock
            $sql = "UPDATE " . $this->table . " 
                    SET quantite_disponible = ?, last_inventory = NOW()
                    WHERE depot_id = ? AND product_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nouvelle_quantite, $depot_id, $product_id]);

            // Enregistrer l'historique d'inventaire
            $sql = "INSERT INTO stock_history 
                    (depot_id, product_id, ancienne_quantite, nouvelle_quantite, difference, type_mouvement, user_id, notes)
                    VALUES (?, ?, ?, ?, ?, 'inventaire', ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $depot_id,
                $product_id,
                $ancien_stock['quantite_disponible'],
                $nouvelle_quantite,
                $difference,
                $user_id,
                $notes
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Obtenir les alertes de stock bas
     */
    public function getAlertes($depot_id = null)
    {
        $where_condition = 's.quantite_disponible <= s.seuil_alerte';
        $params = [];

        if ($depot_id) {
            $where_condition .= ' AND s.depot_id = ?';
            $params[] = $depot_id;
        }

        $sql = "SELECT s.*, 
                       p.nom as product_nom, p.code_produit,
                       d.nom as depot_nom, d.code_depot, d.zone,
                       u.full_name as responsable_nom, u.telephone as responsable_tel
                FROM " . $this->table . " s
                LEFT JOIN products p ON s.product_id = p.id
                LEFT JOIN depots d ON s.depot_id = d.id
                LEFT JOIN users u ON d.responsable_id = u.id
                WHERE " . $where_condition . "
                ORDER BY s.quantite_disponible ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Obtenir les statistiques de stock
     */
    public function getStats($depot_id = null)
    {
        $where_condition = '1=1';
        $params = [];

        if ($depot_id) {
            $where_condition = 's.depot_id = ?';
            $params[] = $depot_id;
        }

        $sql = "SELECT 
                    COUNT(*) as total_references,
                    SUM(s.quantite_disponible) as total_quantite,
                    SUM(s.quantite_disponible * p.prix_unitaire) as valeur_stock,
                    COUNT(CASE WHEN s.quantite_disponible <= s.seuil_alerte THEN 1 END) as alertes_critiques,
                    COUNT(CASE WHEN s.quantite_disponible <= (s.seuil_alerte * 2) AND s.quantite_disponible > s.seuil_alerte THEN 1 END) as alertes_basses,
                    AVG(s.quantite_disponible) as moyenne_stock
                FROM " . $this->table . " s
                LEFT JOIN products p ON s.product_id = p.id
                WHERE " . $where_condition;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir l'historique des mouvements de stock
     */
    public function getHistorique($depot_id, $product_id = null, $limit = 50)
    {
        $where_conditions = ['depot_id = ?'];
        $params = [$depot_id];

        if ($product_id) {
            $where_conditions[] = 'product_id = ?';
            $params[] = $product_id;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "SELECT sh.*, 
                       p.nom as product_nom, p.code_produit,
                       u.full_name as user_nom
                FROM stock_history sh
                LEFT JOIN products p ON sh.product_id = p.id
                LEFT JOIN users u ON sh.user_id = u.id
                WHERE " . $where_clause . "
                ORDER BY sh.created_at DESC
                LIMIT ?";

        $params[] = $limit;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }
}
