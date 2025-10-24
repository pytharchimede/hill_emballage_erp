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

        // Evolutions de schéma: ajouter colonnes pour workflow (statut, validation, mise à jour)
        // Ajouter colonne status si absente
        try {
            $hasStatus = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='status'")->fetchColumn();
            if ((int)$hasStatus === 0) {
                $db->exec("ALTER TABLE livreur_loads ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER date_load");
            }
        } catch (Exception $e) {
        }
        // Ajouter colonne validated_by si absente
        try {
            $hasValidatedBy = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='validated_by'")->fetchColumn();
            if ((int)$hasValidatedBy === 0) {
                $db->exec("ALTER TABLE livreur_loads ADD COLUMN validated_by INT NULL AFTER status");
            }
        } catch (Exception $e) {
        }
        // Ajouter colonne validated_at si absente
        try {
            $hasValidatedAt = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='validated_at'")->fetchColumn();
            if ((int)$hasValidatedAt === 0) {
                $db->exec("ALTER TABLE livreur_loads ADD COLUMN validated_at DATETIME NULL AFTER validated_by");
            }
        } catch (Exception $e) {
        }
        // Ajouter colonne updated_at si absente
        try {
            $hasUpdatedAt = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='updated_at'")->fetchColumn();
            if ((int)$hasUpdatedAt === 0) {
                $db->exec("ALTER TABLE livreur_loads ADD COLUMN updated_at DATETIME NULL AFTER created_at");
            }
        } catch (Exception $e) {
        }
    } catch (Exception $e) {
        // silencieux: ne pas interrompre l'appli si pas de droit CREATE
    }
    // Evolutions de schéma: rattacher un client et une localisation par ligne d'article du panier
    try {
        $hasClientId = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_load_items' AND COLUMN_NAME='client_id'")->fetchColumn();
        if ((int)$hasClientId === 0) {
            $db->exec("ALTER TABLE livreur_load_items ADD COLUMN client_id INT NULL AFTER produit_id, ADD INDEX idx_cli (client_id)");
        }
    } catch (Exception $e) {
    }
    try {
        $hasLat = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_load_items' AND COLUMN_NAME='client_lat'")->fetchColumn();
        if ((int)$hasLat === 0) {
            $db->exec("ALTER TABLE livreur_load_items ADD COLUMN client_lat DECIMAL(10,6) NULL AFTER client_id");
        }
    } catch (Exception $e) {
    }
    try {
        $hasLng = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_load_items' AND COLUMN_NAME='client_lng'")->fetchColumn();
        if ((int)$hasLng === 0) {
            $db->exec("ALTER TABLE livreur_load_items ADD COLUMN client_lng DECIMAL(10,6) NULL AFTER client_lat");
        }
    } catch (Exception $e) {
    }

    // Table d'audit des paniers (snapshots)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS livreur_load_audits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                load_id INT NOT NULL,
                action VARCHAR(20) NOT NULL,
                items_json LONGTEXT NULL,
                notes TEXT NULL,
                user_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_load (load_id),
                INDEX idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
    }
}
