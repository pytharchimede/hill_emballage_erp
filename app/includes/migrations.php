<?php

/**
 * Création des tables nécessaires au flux livreur (panier reçu, versements)
 */

function ensureLivreurFlowTables(PDO $db)
{
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS livreur_loads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            livreur_id INT NOT NULL,
            depot_id INT NOT NULL,
            date_load DATE NOT NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_livreur_date (livreur_id, date_load),
            INDEX idx_depot_date (depot_id, date_load)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS livreur_load_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            load_id INT NOT NULL,
            produit_id INT NOT NULL,
            quantite DECIMAL(12,2) NOT NULL,
            CONSTRAINT fk_load FOREIGN KEY (load_id) REFERENCES livreur_loads(id) ON DELETE CASCADE,
            INDEX idx_load (load_id),
            INDEX idx_prod (produit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS livreur_remittances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            livreur_id INT NOT NULL,
            depot_id INT NOT NULL,
            date_remit DATE NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_livreur_date (livreur_id, date_remit),
            INDEX idx_depot_date (depot_id, date_remit)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        // silencieux: ne pas interrompre l'appli si pas de droit CREATE
    }
}
