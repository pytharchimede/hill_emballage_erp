<?php

/**
 * Modèle Client pour HILL EMBALLAGE
 */

class Client
{
    private $conn;
    private $table = 'clients';

    public $id;
    public $code_client;
    public $nom;
    public $prenoms;
    public $telephone;
    public $email;
    public $adresse;
    public $zone;
    public $type_client;
    public $credit_limite;
    public $solde_credit;
    public $points_fidelite;
    public $qr_code;
    public $photo_url;
    public $is_active;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Générer un code client unique
     */
    private function generateCodeClient()
    {
        $sql = "SELECT COUNT(*) + 1 as next_id FROM " . $this->table;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return 'CLI' . str_pad($row['next_id'], 3, '0', STR_PAD_LEFT);
    }

    /**
     * Créer un nouveau client
     */
    public function create()
    {
        $sql = "INSERT INTO " . $this->table . " 
                SET nom = :nom, 
                    prenoms = :prenoms,
                    telephone = :telephone,
                    email = :email,
                    adresse = :adresse,
                    zone = :zone,
                    type_client = :type_client,
                    credit_limite = :credit_limite,
                    code_client = :code_client,
                    created_by = :created_by";

        $stmt = $this->conn->prepare($sql);

        // Générer le code client si pas fourni
        if (empty($this->code_client)) {
            $this->code_client = $this->generateCodeClient();
        }

        // Sanitize
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenoms = htmlspecialchars(strip_tags($this->prenoms));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind des paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenoms', $this->prenoms);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':adresse', $this->adresse);
        $stmt->bindParam(':zone', $this->zone);
        $stmt->bindParam(':type_client', $this->type_client);
        $stmt->bindParam(':credit_limite', $this->credit_limite);
        $stmt->bindParam(':code_client', $this->code_client);
        $stmt->bindParam(':created_by', $this->created_by);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Lire tous les clients
     */
    public function read()
    {
        $sql = "SELECT c.*, u.full_name as created_by_name 
                FROM " . $this->table . " c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.is_active = 1
                ORDER BY c.nom ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Lire un client par ID
     */
    public function readOne()
    {
        $sql = "SELECT c.*, u.full_name as created_by_name 
                FROM " . $this->table . " c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ? AND c.is_active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->nom = $row['nom'];
            $this->prenoms = $row['prenoms'];
            $this->telephone = $row['telephone'];
            $this->email = $row['email'];
            $this->adresse = $row['adresse'];
            $this->zone = $row['zone'];
            $this->type_client = $row['type_client'];
            $this->credit_limite = $row['credit_limite'];
            $this->solde_credit = $row['solde_credit'];
            $this->points_fidelite = $row['points_fidelite'];
            $this->code_client = $row['code_client'];
        }

        return $row ? true : false;
    }

    /**
     * Mettre à jour un client
     */
    public function update()
    {
        $sql = "UPDATE " . $this->table . " 
                SET nom = :nom,
                    prenoms = :prenoms,
                    telephone = :telephone,
                    email = :email,
                    adresse = :adresse,
                    zone = :zone,
                    type_client = :type_client,
                    credit_limite = :credit_limite
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        // Sanitize
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenoms = htmlspecialchars(strip_tags($this->prenoms));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind des paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenoms', $this->prenoms);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':adresse', $this->adresse);
        $stmt->bindParam(':zone', $this->zone);
        $stmt->bindParam(':type_client', $this->type_client);
        $stmt->bindParam(':credit_limite', $this->credit_limite);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Supprimer un client (soft delete)
     */
    public function delete()
    {
        $sql = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    /**
     * Rechercher des clients
     */
    public function search($keyword)
    {
        $sql = "SELECT * FROM " . $this->table . " 
                WHERE is_active = 1 AND (
                    nom LIKE ? OR 
                    prenoms LIKE ? OR 
                    telephone LIKE ? OR 
                    code_client LIKE ?
                )
                ORDER BY nom ASC";

        $keyword = "%{$keyword}%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $keyword);
        $stmt->bindParam(2, $keyword);
        $stmt->bindParam(3, $keyword);
        $stmt->bindParam(4, $keyword);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Mettre à jour le solde crédit du client
     */
    public function updateSoldeCredit($montant, $operation = 'add')
    {
        $sql = "UPDATE " . $this->table . " 
                SET solde_credit = solde_credit " . ($operation == 'add' ? '+' : '-') . " ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $montant);
        $stmt->bindParam(2, $this->id);

        return $stmt->execute();
    }

    /**
     * Mettre à jour les points de fidélité
     */
    public function updatePointsFidelite($points, $operation = 'add')
    {
        $sql = "UPDATE " . $this->table . " 
                SET points_fidelite = points_fidelite " . ($operation == 'add' ? '+' : '-') . " ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $points);
        $stmt->bindParam(2, $this->id);

        return $stmt->execute();
    }

    /**
     * Obtenir les statistiques des clients
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN type_client = 'particulier' THEN 1 END) as particuliers,
                    COUNT(CASE WHEN type_client = 'entreprise' THEN 1 END) as entreprises,
                    SUM(solde_credit) as total_credits,
                    SUM(points_fidelite) as total_points
                FROM " . $this->table . " 
                WHERE is_active = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
