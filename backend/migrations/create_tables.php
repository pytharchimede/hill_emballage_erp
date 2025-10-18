<?php

/**
 * Migration principale pour créer toutes les tables de HILL EMBALLAGE
 */

require_once '../config/database.php';

class Migration
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $database->createDatabase();
        $this->db = $database->getConnection();
    }

    public function runAll()
    {
        echo "🚀 Début des migrations pour HILL EMBALLAGE...\n\n";

        $this->createUsersTable();
        $this->createClientsTable();
        $this->createDepotsTable();
        $this->createProductsTable();
        $this->createStockTable();
        $this->createSalesTable();
        $this->createPaymentsTable();
        $this->createTransfersTable();
        $this->createFidelityTable();
        $this->createNotificationsTable();

        echo "\n✅ Toutes les migrations ont été exécutées avec succès!\n";
    }

    private function createUsersTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'livreur', 'vendeur', 'comptable') NOT NULL DEFAULT 'vendeur',
            phone VARCHAR(20),
            depot_id INT(11) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table users créée\n";
    }

    private function createClientsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS clients (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            code_client VARCHAR(20) UNIQUE NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenoms VARCHAR(100),
            telephone VARCHAR(20),
            email VARCHAR(100),
            adresse TEXT,
            zone VARCHAR(50),
            type_client ENUM('particulier', 'entreprise') DEFAULT 'particulier',
            credit_limite DECIMAL(15,2) DEFAULT 0.00,
            solde_credit DECIMAL(15,2) DEFAULT 0.00,
            points_fidelite INT(11) DEFAULT 0,
            qr_code VARCHAR(255),
            photo_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT(11),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table clients créée\n";
    }

    private function createDepotsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS depots (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            code_depot VARCHAR(20) UNIQUE NOT NULL,
            adresse TEXT,
            zone VARCHAR(50),
            type_depot ENUM('principal', 'sous_depot') NOT NULL DEFAULT 'sous_depot',
            responsable_id INT(11),
            telephone VARCHAR(20),
            stock_alert_min INT(11) DEFAULT 10,
            qr_code VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (responsable_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table depots créée\n";
    }

    private function createProductsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            code_produit VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            unite VARCHAR(20) DEFAULT 'pièce',
            prix_unitaire DECIMAL(10,2) NOT NULL,
            prix_credit DECIMAL(10,2),
            points_fidelite INT(11) DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table products créée\n";
    }

    private function createStockTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS stock (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            depot_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            quantite_disponible INT(11) DEFAULT 0,
            quantite_reservee INT(11) DEFAULT 0,
            seuil_alerte INT(11) DEFAULT 10,
            last_inventory TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_depot_product (depot_id, product_id),
            FOREIGN KEY (depot_id) REFERENCES depots(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table stock créée\n";
    }

    private function createSalesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS sales (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            numero_vente VARCHAR(30) UNIQUE NOT NULL,
            client_id INT(11) NOT NULL,
            depot_id INT(11) NOT NULL,
            vendeur_id INT(11) NOT NULL,
            type_vente ENUM('comptant', 'credit', 'depot_vente') NOT NULL,
            montant_total DECIMAL(15,2) NOT NULL,
            montant_paye DECIMAL(15,2) DEFAULT 0.00,
            montant_restant DECIMAL(15,2) NOT NULL,
            statut ENUM('en_cours', 'partiel', 'solde', 'annule') DEFAULT 'en_cours',
            date_echeance DATE NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id),
            FOREIGN KEY (depot_id) REFERENCES depots(id),
            FOREIGN KEY (vendeur_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);

        // Table des détails de vente
        $sql2 = "CREATE TABLE IF NOT EXISTS sale_details (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            sale_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            quantite INT(11) NOT NULL,
            prix_unitaire DECIMAL(10,2) NOT NULL,
            sous_total DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql2);
        echo "✅ Tables sales et sale_details créées\n";
    }

    private function createPaymentsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS payments (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            numero_paiement VARCHAR(30) UNIQUE NOT NULL,
            sale_id INT(11) NOT NULL,
            client_id INT(11) NOT NULL,
            montant DECIMAL(15,2) NOT NULL,
            mode_paiement ENUM('especes', 'mobile_money', 'cheque', 'virement') NOT NULL,
            reference_externe VARCHAR(100),
            notes TEXT,
            photo_preuve VARCHAR(255),
            receveur_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sale_id) REFERENCES sales(id),
            FOREIGN KEY (client_id) REFERENCES clients(id),
            FOREIGN KEY (receveur_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table payments créée\n";
    }

    private function createTransfersTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS transfers (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            numero_transfert VARCHAR(30) UNIQUE NOT NULL,
            depot_source_id INT(11) NOT NULL,
            depot_destination_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            quantite INT(11) NOT NULL,
            statut ENUM('en_attente', 'expedie', 'recu', 'annule') DEFAULT 'en_attente',
            expedie_par INT(11),
            recu_par INT(11),
            date_expedition TIMESTAMP NULL,
            date_reception TIMESTAMP NULL,
            notes TEXT,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (depot_source_id) REFERENCES depots(id),
            FOREIGN KEY (depot_destination_id) REFERENCES depots(id),
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (expedie_par) REFERENCES users(id),
            FOREIGN KEY (recu_par) REFERENCES users(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table transfers créée\n";
    }

    private function createFidelityTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS fidelity_history (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            client_id INT(11) NOT NULL,
            points INT(11) NOT NULL,
            type_operation ENUM('gain', 'utilisation', 'expiration') NOT NULL,
            description TEXT,
            sale_id INT(11) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id),
            FOREIGN KEY (sale_id) REFERENCES sales(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);

        // Table des récompenses fidélité
        $sql2 = "CREATE TABLE IF NOT EXISTS fidelity_rewards (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            points_requis INT(11) NOT NULL,
            reduction_pourcentage DECIMAL(5,2) DEFAULT 0.00,
            reduction_montant DECIMAL(10,2) DEFAULT 0.00,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql2);
        echo "✅ Tables fidelity_history et fidelity_rewards créées\n";
    }

    private function createNotificationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            titre VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table notifications créée\n";
    }
}

// Exécution des migrations
if (php_sapi_name() == 'cli' || isset($_GET['migrate'])) {
    $migration = new Migration();
    $migration->runAll();
} else {
    echo "Pour exécuter les migrations, ajoutez ?migrate=1 à l'URL ou exécutez en ligne de commande.";
}
