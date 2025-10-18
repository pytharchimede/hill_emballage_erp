<?php

/**
 * Migration pour ajouter la table d'historique des stocks
 */

require_once '../config/database.php';

class StockHistoryMigration
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createStockHistoryTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS stock_history (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            depot_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            ancienne_quantite INT(11) NOT NULL,
            nouvelle_quantite INT(11) NOT NULL,
            difference INT(11) NOT NULL,
            type_mouvement ENUM('entree', 'sortie', 'transfert', 'inventaire', 'ajustement') NOT NULL,
            reference_externe VARCHAR(100),
            user_id INT(11) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (depot_id) REFERENCES depots(id),
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->db->exec($sql);
        echo "✅ Table stock_history créée\n";
    }

    public function run()
    {
        echo "🚀 Ajout de la table d'historique des stocks...\n\n";
        $this->createStockHistoryTable();
        echo "\n✅ Migration terminée!\n";
    }
}

// Exécution de la migration
if (php_sapi_name() == 'cli' || isset($_GET['migrate_stock_history'])) {
    $migration = new StockHistoryMigration();
    $migration->run();
}
