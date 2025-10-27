<?php

/**
 * Migration: tables d'assignation vendeur et clôture, et colonne sales.assignment_id
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Migration - Assignations Vendeur</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Connexion BD échouée');
    }

    // Détecter noms de tables produits/ventes selon schéma actuel
    $tableExists = function ($table) use ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    };

    $productsTable = $tableExists('products') ? 'products' : ($tableExists('produits') ? 'produits' : null);
    $salesTable    = $tableExists('sales') ? 'sales' : ($tableExists('ventes') ? 'ventes' : null);
    $saleDetailsTable = $tableExists('sale_details') ? 'sale_details' : ($tableExists('vente_items') ? 'vente_items' : null);

    if ($productsTable === null) {
        throw new Exception("Table produits introuvable (products/produits)");
    }
    if ($salesTable === null) {
        throw new Exception("Table ventes introuvable (sales/ventes)");
    }

    echo "<p class='info'>Schéma détecté: produits=$productsTable, ventes=$salesTable</p>\n";

    // Créer vendor_assignments
    $sql = "CREATE TABLE IF NOT EXISTS vendor_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero VARCHAR(50) UNIQUE NOT NULL,
        depot_id INT NOT NULL,
        vendeur_id INT NOT NULL,
        assigner_id INT NOT NULL,
        status ENUM('open','closed') DEFAULT 'open',
        notes TEXT NULL,
        qty_total DECIMAL(15,2) DEFAULT 0,
        qty_sold DECIMAL(15,2) DEFAULT 0,
        qty_returned DECIMAL(15,2) DEFAULT 0,
        amount_cash_collected DECIMAL(15,2) DEFAULT 0,
        amount_credit_outstanding DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL,
        INDEX idx_vendeur_status (vendeur_id, status),
        INDEX idx_depot (depot_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<p class='success'>✓ Table vendor_assignments OK</p>\n";

    // Détails d'assignation
    $sql = "CREATE TABLE IF NOT EXISTS vendor_assignment_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        product_id INT NOT NULL,
        qty_assigned DECIMAL(15,2) NOT NULL,
        unit_price DECIMAL(15,2) NULL,
        qty_sold DECIMAL(15,2) DEFAULT 0,
        qty_returned DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_assignment (assignment_id),
        INDEX idx_product (product_id),
        CONSTRAINT fk_vad_assignment FOREIGN KEY (assignment_id) REFERENCES vendor_assignments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<p class='success'>✓ Table vendor_assignment_details OK</p>\n";

    // Règlements de fin d'assignation
    $sql = "CREATE TABLE IF NOT EXISTS vendor_settlements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        vendeur_id INT NOT NULL,
        receiver_id INT NOT NULL,
        amount_cash DECIMAL(15,2) NOT NULL DEFAULT 0,
        amount_credit DECIMAL(15,2) NOT NULL DEFAULT 0,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_assignment (assignment_id),
        CONSTRAINT fk_vs_assignment FOREIGN KEY (assignment_id) REFERENCES vendor_assignments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<p class='success'>✓ Table vendor_settlements OK</p>\n";

    // Solde vendeur courant
    $sql = "CREATE TABLE IF NOT EXISTS vendor_balances (
        vendeur_id INT PRIMARY KEY,
        balance DECIMAL(15,2) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<p class='success'>✓ Table vendor_balances OK</p>\n";

    // Historique de mouvements du solde vendeur
    $sql = "CREATE TABLE IF NOT EXISTS vendor_balance_history (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        vendeur_id INT NOT NULL,
        assignment_id INT NULL,
        change_amount DECIMAL(15,2) NOT NULL,
        reason VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vendeur (vendeur_id),
        INDEX idx_assignment (assignment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<p class='success'>✓ Table vendor_balance_history OK</p>\n";

    // Ajouter colonne assignment_id dans la table des ventes détectée
    $hasAssignmentCol = function () use ($db, $salesTable) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'assignment_id'");
        $stmt->execute([$salesTable]);
        return (int)$stmt->fetchColumn() > 0;
    };

    if (!$hasAssignmentCol()) {
        $db->exec("ALTER TABLE $salesTable ADD COLUMN assignment_id INT NULL AFTER vendeur_id");
        echo "<p class='success'>✓ Colonne assignment_id ajoutée à $salesTable</p>\n";
    } else {
        echo "<p class='info'>Colonne assignment_id déjà présente dans $salesTable</p>\n";
    }

    echo "<p class='success'><strong>Migration assignations: OK</strong></p>\n";
} catch (Exception $e) {
    echo "<p class='error'>Erreur migration: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
