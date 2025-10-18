<?php

/**
 * Configuration de la base de données pour HILL EMBALLAGE
 */

class Database
{
    private $host = "localhost";
    private $db_name = "fidestci_hill_emballage_db";
    private $username = "fidestci_ulrich";
    private $password = "@Succes2019";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Erreur de connexion: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function createDatabase()
    {
        try {
            $conn = new PDO("mysql:host=" . $this->host . ";charset=utf8", $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "CREATE DATABASE IF NOT EXISTS " . $this->db_name . " CHARACTER SET utf8 COLLATE utf8_unicode_ci";
            $conn->exec($sql);

            return true;
        } catch (PDOException $exception) {
            echo "Erreur création BDD: " . $exception->getMessage();
            return false;
        }
    }
}

// Configuration CORS pour l'API (ne s'applique qu'aux endpoints API)
if (!headers_sent()) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/api/') !== false) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            exit(0);
        }
    }
}
