<?php

/**
 * Modèle User pour HILL EMBALLAGE
 */

class User
{
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $role;
    public $phone;
    public $depot_id;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function create()
    {
        $sql = "INSERT INTO " . $this->table . " 
                SET username = :username,
                    email = :email,
                    password = :password,
                    full_name = :full_name,
                    role = :role,
                    phone = :phone,
                    depot_id = :depot_id";

        $stmt = $this->conn->prepare($sql);

        // Hash du mot de passe
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));

        // Bind des paramètres
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':depot_id', $this->depot_id);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Authentifier un utilisateur
     */
    public function authenticate($username, $password)
    {
        // Vérifier que la connexion est établie
        if (!$this->conn) {
            throw new Exception("Connexion à la base de données non établie");
        }

        $sql = "SELECT u.*, d.nom as depot_nom 
                FROM " . $this->table . " u
                LEFT JOIN depots d ON u.depot_id = d.id
                WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Mettre à jour la dernière connexion
            $this->updateLastLogin($user['id']);

            // Retourner les données utilisateur (sans le mot de passe)
            unset($user['password']);
            return $user;
        }

        return false;
    }

    /**
     * Mettre à jour la dernière connexion
     */
    private function updateLastLogin($user_id)
    {
        $sql = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
    }

    /**
     * Lire tous les utilisateurs
     */
    public function read()
    {
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.role, u.phone, u.is_active, 
                       u.last_login, u.created_at, d.nom as depot_nom
                FROM " . $this->table . " u
                LEFT JOIN depots d ON u.depot_id = d.id
                ORDER BY u.full_name ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Lire un utilisateur par ID
     */
    public function readOne()
    {
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.role, u.phone, 
                       u.depot_id, u.is_active, u.last_login, u.created_at, d.nom as depot_nom
                FROM " . $this->table . " u
                LEFT JOIN depots d ON u.depot_id = d.id
                WHERE u.id = ? AND u.is_active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->role = $row['role'];
            $this->phone = $row['phone'];
            $this->depot_id = $row['depot_id'];
        }

        return $row ? true : false;
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update()
    {
        $sql = "UPDATE " . $this->table . " 
                SET username = :username,
                    email = :email,
                    full_name = :full_name,
                    role = :role,
                    phone = :phone,
                    depot_id = :depot_id
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));

        // Bind des paramètres
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':depot_id', $this->depot_id);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword($new_password)
    {
        $sql = "UPDATE " . $this->table . " SET password = ? WHERE id = ?";

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$hashed_password, $this->id]);
    }

    /**
     * Désactiver un utilisateur
     */
    public function deactivate()
    {
        $sql = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    /**
     * Activer un utilisateur
     */
    public function activate()
    {
        $sql = "UPDATE " . $this->table . " SET is_active = 1 WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    /**
     * Vérifier si un username ou email existe déjà
     */
    public function exists($username, $email, $exclude_id = null)
    {
        $sql = "SELECT COUNT(*) FROM " . $this->table . " 
                WHERE (username = ? OR email = ?)";

        if ($exclude_id) {
            $sql .= " AND id != ?";
        }

        $stmt = $this->conn->prepare($sql);

        if ($exclude_id) {
            $stmt->execute([$username, $email, $exclude_id]);
        } else {
            $stmt->execute([$username, $email]);
        }

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
                    COUNT(CASE WHEN role = 'livreur' THEN 1 END) as livreurs,
                    COUNT(CASE WHEN role = 'vendeur' THEN 1 END) as vendeurs,
                    COUNT(CASE WHEN role = 'comptable' THEN 1 END) as comptables,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as actifs,
                    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as actifs_30j
                FROM " . $this->table;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
