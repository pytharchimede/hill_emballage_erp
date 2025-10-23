<?php

/**
 * Canoniser la table des produits vers 'products' (table physique) et supprimer la table 'produits'.
 * - Réécrit toutes les FKs référant 'produits' pour cibler 'products'
 * - Si 'produits' est une TABLE et non référencée, la renomme en backup puis crée une VUE 'produits' => 'products'
 *   Sinon, crée directement la VUE et supprime/renomme la TABLE selon possibilités.
 * - Si 'produits' est une VUE, la remplace par une VUE pointant vers 'products'
 */
require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Canoniser vers 'products'</h2>\n";

echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00} pre{background:#f7f7f7;padding:10px;border:1px solid #ddd}</style>";

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) throw new Exception('Connexion DB impossible');

    $hasTable = function ($name) use ($db) {
        $s = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $s->execute([$name]);
        return $s->fetchColumn() > 0;
    };
    $getTableType = function ($name) use ($db) {
        $s = $db->prepare("SELECT TABLE_TYPE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $s->execute([$name]);
        return $s->fetchColumn() ?: null; // BASE TABLE | VIEW
    };

    $existsProducts = $hasTable('products');
    $existsProduits = $hasTable('produits');
    // Si 'products' n'existe pas mais 'produits' est une TABLE: la renommer en 'products' pour canoniser
    if (!$existsProducts && $existsProduits && strtoupper((string)$getTableType('produits')) === 'BASE TABLE') {
        $db->exec("RENAME TABLE produits TO products");
        echo "<p class='ok'>✓ Table 'produits' renommée en 'products' (canonisation)</p>";
        $existsProducts = true;
        $existsProduits = false; // l'ancien nom n'existe plus
        // Créer une vue 'produits' de compat
        try {
            $db->exec("CREATE VIEW produits AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at FROM products");
        } catch (Exception $e) {
        }
    }
    if (!$existsProducts) throw new Exception("La table 'products' n'existe pas — impossible de canoniser.");

    // 1) Réécrire les FKs qui pointent vers 'produits' -> 'products'
    $stmt = $db->prepare("SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
                           FROM information_schema.KEY_COLUMN_USAGE kcu
                           JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                             ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                          WHERE kcu.TABLE_SCHEMA = DATABASE()
                            AND kcu.REFERENCED_TABLE_NAME = 'produits'");
    $stmt->execute();
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fks as $fk) {
        $constraint = $fk['CONSTRAINT_NAME'];
        $table = $fk['TABLE_NAME'];
        $col = $fk['COLUMN_NAME'];
        $refCol = $fk['REFERENCED_COLUMN_NAME'] ?: 'id';
        $upd = $fk['UPDATE_RULE'] ?: 'RESTRICT';
        $del = $fk['DELETE_RULE'] ?: 'RESTRICT';
        try {
            $db->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            $newName = $constraint . "_to_products";
            $sqlAdd = sprintf(
                "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES products(`%s`) ON UPDATE %s ON DELETE %s",
                $table,
                $newName,
                $col,
                $refCol,
                $upd,
                $del
            );
            $db->exec($sqlAdd);
            echo "<div class='ok'>✓ FK {$constraint} (table {$table}) → products</div>";
        } catch (Exception $e) {
            echo "<div class='warn'>⚠ Réécriture FK échouée {$constraint}: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // 2) Traiter 'produits':
    if ($existsProduits) {
        $type = $getTableType('produits');
        if (strtoupper((string)$type) === 'VIEW') {
            // Remplacer la VUE par une VUE propre pointant vers products
            try {
                $db->exec("DROP VIEW produits");
            } catch (Exception $e) {
            }
            $db->exec("CREATE VIEW produits AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at FROM products");
            echo "<p class='ok'>✓ Vue 'produits' recréée vers 'products'</p>";
        } else {
            // Table physique: tenter de renommer en backup et créer une vue
            // Vérifier s'il reste des FKs vers cette table
            $fkCheck = $db->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME='produits'")->fetchColumn();
            if ((int)$fkCheck === 0) {
                $backup = 'produits_backup_' . date('Ymd_His');
                $db->exec("RENAME TABLE produits TO `$backup`");
                echo "<p class='ok'>✓ Table 'produits' renommée en $backup</p>";
                // Créer la vue de compat
                $db->exec("CREATE VIEW produits AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at FROM products");
                echo "<p class='ok'>✓ Vue 'produits' créée vers 'products'</p>";
            } else {
                echo "<p class='warn'>⚠ Des FKs pointent encore vers 'produits'. Elles ont été réécrites ci-dessus. Relancez ce script si besoin pour finaliser le renommage.</p>";
            }
        }
    } else {
        // Pas d'objet 'produits' — rien à supprimer; éventuellement créer une vue pour compat
        try {
            $db->exec("CREATE VIEW produits AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at FROM products");
            echo "<p class='ok'>✓ Vue 'produits' (compat) créée</p>";
        } catch (Exception $e) {
            echo "<p class='warn'>⚠ Vue 'produits' non créée: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "<h3 class='ok'>Canonisation terminée</h3>";
    echo "<ul><li>'products' = table canonique</li><li>'produits' = vue de compat (si présente)</li><li>FKs réécrites vers products</li></ul>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
