<?php

/**
 * Consolidation des tables produits/products vers une table unique "produits"
 * - Synchronise les données de products -> produits (par code_produit)
 * - Assure la présence de la colonne image_path dans produits
 * - Si aucune contrainte étrangère ne référence "products", renomme la table en backup
 *   et crée une vue "products" basée sur "produits" pour compatibilité.
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Consolidation des produits</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00} pre{background:#f7f7f7;padding:10px;border:1px solid #ddd}</style>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception('Connexion DB impossible');
    }

    // Utilitaires
    $hasTable = function ($name) use ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn() > 0;
    };
    $getTableType = function ($name) use ($conn) {
        $stmt = $conn->prepare("SELECT TABLE_TYPE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$name]);
        $t = $stmt->fetchColumn();
        return $t ?: null; // 'BASE TABLE' ou 'VIEW'
    };
    $isBaseTable = function ($name) use ($getTableType) {
        $t = $getTableType($name);
        return strtoupper((string)$t) === 'BASE TABLE';
    };
    $isView = function ($name) use ($getTableType) {
        $t = $getTableType($name);
        return strtoupper((string)$t) === 'VIEW';
    };
    $hasColumn = function ($table, $col) use ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $col]);
        return $stmt->fetchColumn() > 0;
    };

    $tProduits = $hasTable('produits');
    $tProducts = $hasTable('products');
    echo "<p>Tables détectées: produits=" . ($tProduits ? 'oui' : 'non') . ", products=" . ($tProducts ? 'oui' : 'non') . "</p>";

    // 1) Créer produits si absent, ou convertir la VUE en TABLE si nécessaire
    if (!$tProduits) {
        $sql = "CREATE TABLE produits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            code_produit VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            unite VARCHAR(20) DEFAULT 'pièce',
            prix_unitaire DECIMAL(10,2) NOT NULL,
            prix_credit DECIMAL(10,2) NULL,
            points_fidelite INT DEFAULT 1,
            image_path VARCHAR(255) NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        echo "<p class='ok'>✓ Table produits créée</p>";
        $tProduits = true;
    } else {
        // produits existe — vérifier si c'est une VUE
        if ($isView('produits')) {
            echo "<p class='warn'>⚠ 'produits' est une VUE. Conversion en table physique…</p>";
            // Créer une table physique temporaire avec le schéma cible
            // Nettoyage si une tentative précédente a laissé la table temporaire
            try {
                $conn->exec("DROP TABLE IF EXISTS produits_physical");
            } catch (Exception $e) {
            }
            $conn->exec("CREATE TABLE produits_physical (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                code_produit VARCHAR(50) UNIQUE NOT NULL,
                description TEXT,
                unite VARCHAR(20) DEFAULT 'pièce',
                prix_unitaire DECIMAL(10,2) NOT NULL,
                prix_credit DECIMAL(10,2) NULL,
                points_fidelite INT DEFAULT 1,
                image_path VARCHAR(255) NULL,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )");
            // Insérer les données depuis la VUE 'produits' (sans image_path)
            $insertSql = "INSERT INTO produits_physical (id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, is_active, created_at, updated_at)
                          SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, IFNULL(is_active,1), created_at, updated_at FROM produits";
            $moved = $conn->exec($insertSql);
            echo "<p class='ok'>✓ $moved lignes copiées depuis la VUE 'produits' vers la table physique</p>";
            // Remplacer la VUE par la TABLE
            try {
                $conn->exec("DROP VIEW produits");
            } catch (Exception $e) {
                throw new Exception("Impossible de supprimer la VUE 'produits'. Vérifiez les privilèges DROP VIEW. Détail: " . $e->getMessage());
            }
            $conn->exec("RENAME TABLE produits_physical TO produits");
            echo "<p class='ok'>✓ La VUE 'produits' a été remplacée par une TABLE physique 'produits'</p>";
        }
    }

    // 2) S'assurer de la colonne image_path (maintenant produits est une TABLE)
    if ($isBaseTable('produits') && !$hasColumn('produits', 'image_path')) {
        $conn->exec("ALTER TABLE produits ADD COLUMN image_path VARCHAR(255) NULL AFTER points_fidelite");
        echo "<p class='ok'>✓ Colonne image_path ajoutée à produits</p>";
    }

    // 3) Synchroniser les données depuis products -> produits
    if ($tProducts && $isBaseTable('products')) {
        echo "<p>Synchronisation des enregistrements de products vers produits...</p>";
        $conn->exec("CREATE TEMPORARY TABLE _tmp_products AS SELECT * FROM products");
        // Insertion des nouveaux codes
        $sqlInsert = "INSERT INTO produits (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, is_active, created_at, updated_at)
                      SELECT p.nom, p.code_produit, p.description, p.unite, p.prix_unitaire, p.prix_credit, p.points_fidelite, IFNULL(p.is_active,1), p.created_at, p.updated_at
                      FROM _tmp_products p
                      LEFT JOIN produits pr ON pr.code_produit = p.code_produit
                      WHERE pr.id IS NULL";
        $ins = $conn->exec($sqlInsert);
        echo "<p class='ok'>✓ $ins produits insérés</p>";

        // Mise à jour des existants par code_produit
        $sqlUpdate = "UPDATE produits pr
                      JOIN _tmp_products p ON p.code_produit = pr.code_produit
                      SET pr.nom = COALESCE(p.nom, pr.nom),
                          pr.description = COALESCE(p.description, pr.description),
                          pr.unite = COALESCE(p.unite, pr.unite),
                          pr.prix_unitaire = COALESCE(p.prix_unitaire, pr.prix_unitaire),
                          pr.prix_credit = COALESCE(p.prix_credit, pr.prix_credit),
                          pr.points_fidelite = COALESCE(p.points_fidelite, pr.points_fidelite),
                          pr.is_active = COALESCE(p.is_active, pr.is_active)";
        $upd = $conn->exec($sqlUpdate);
        echo "<p class='ok'>✓ $upd produits mis à jour</p>";
        $conn->exec("DROP TEMPORARY TABLE _tmp_products");
    }

    // 4) Si possible: basculer vers table unique et vue de compatibilité
    if ($tProducts && $isBaseTable('products')) {
        // Vérifier FK vers products
        $sqlFk = "SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME = 'products'";
        $fk = $conn->query($sqlFk)->fetchAll(PDO::FETCH_ASSOC);
        if (!$fk) {
            $backup = 'products_backup_' . date('Ymd_His');
            $conn->exec("RENAME TABLE products TO `$backup`");
            echo "<p class='ok'>✓ Table products renommée en $backup</p>";
            // Créer vue de compat
            $conn->exec("CREATE VIEW products AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, is_active, created_at, updated_at FROM produits");
            echo "<p class='ok'>✓ Vue products créée pour compatibilité</p>";
        } else {
            echo "<p class='warn'>⚠ Des clés étrangères référencent products; conservation de la table telle quelle. Aucune suppression/renommage effectué.</p>";
            echo "<details><summary>Détails FK</summary><pre>" . htmlspecialchars(print_r($fk, true)) . "</pre></details>";
        }
    }

    echo "<h3 class='ok'>Consolidation terminée</h3>";
    echo "<ul>";
    echo "<li>Toutes les applications peuvent continuer d'utiliser 'produits'.</li>";
    echo "<li>Si la vue 'products' a été créée, le code existant pointant vers 'products' reste compatible.</li>";
    echo "</ul>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
