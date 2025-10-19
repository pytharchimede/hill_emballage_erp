<?php

/**
 * Migration structure-only pour unifier les tables produits/products sans modifier les données.
 *
 * Principe:
 * - Détecte l'existence et le volume (COUNT) de 'produits' et 'products'.
 * - Choisit comme table canonique celle qui contient le plus de lignes (ou la seule existante).
 * - S'assure que la table canonique possède la colonne image_path.
 * - Met à jour les clés étrangères qui pointent vers l'autre table pour qu'elles pointent vers la table canonique.
 * - Renomme l'ancienne table non canonique en backup (products_backup_YYYYMMDD_HHMMSS ou produits_backup_...),
 *   puis crée une VUE portant l'ancien nom qui pointe vers la table canonique (mapping 1:1 des colonnes).
 *
 * Aucune insertion/suppression/mise à jour de lignes de données n'est effectuée.
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Unification structurelle des produits</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00} pre{background:#f7f7f7;padding:10px;border:1px solid #ddd}</style>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception('Connexion DB impossible');
    }

    $esc = fn($s) => str_replace('`', '``', $s);

    $hasTable = function ($name) use ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn() > 0;
    };
    $countRows = function ($name) use ($conn) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM `" . $name . "`");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    };
    $hasColumn = function ($table, $col) use ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $col]);
        return $stmt->fetchColumn() > 0;
    };

    $existsProduits = $hasTable('produits');
    $existsProducts = $hasTable('products');
    echo "<p>Tables: produits=" . ($existsProduits ? 'oui' : 'non') . ", products=" . ($existsProducts ? 'oui' : 'non') . "</p>";

    if (!$existsProduits && !$existsProducts) {
        echo "<p class='warn'>Aucune table produits/products trouvée. Rien à faire.</p>";
        exit;
    }

    // Choix de la table canonique
    $canonical = null;
    $alias = null;
    if ($existsProduits && $existsProducts) {
        $c1 = $countRows('produits');
        $c2 = $countRows('products');
        if ($c1 >= $c2) {
            $canonical = 'produits';
            $alias = 'products';
        } else {
            $canonical = 'products';
            $alias = 'produits';
        }
        echo "<p>Comptages: produits=$c1, products=$c2. Table canonique: <strong>$canonical</strong></p>";
    } else if ($existsProduits) {
        $canonical = 'produits';
        $alias = 'products';
    } else {
        $canonical = 'products';
        $alias = 'produits';
    }

    // Colonne image_path si manquante
    if (!$hasColumn($canonical, 'image_path')) {
        $conn->exec("ALTER TABLE `{$canonical}` ADD COLUMN image_path VARCHAR(255) NULL AFTER points_fidelite");
        echo "<p class='ok'>✓ Colonne image_path ajoutée à {$canonical}</p>";
    }

    // Mettre à jour les FKs pointant sur la table alias (non canonique) pour référencer la canonique
    if (($canonical === 'produits' && $existsProducts) || ($canonical === 'products' && $existsProduits)) {
        $stmt = $conn->prepare("SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
                                 FROM information_schema.KEY_COLUMN_USAGE kcu
                                 JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                                   ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                                WHERE kcu.TABLE_SCHEMA = DATABASE()
                                  AND kcu.REFERENCED_TABLE_NAME = ?");
        $stmt->execute([$alias]);
        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($fks) {
            echo "<p>Réécriture des FKs depuis <code>$alias</code> vers <code>$canonical</code>…</p>";
            foreach ($fks as $fk) {
                $constraint = $fk['CONSTRAINT_NAME'];
                $table = $fk['TABLE_NAME'];
                $col = $fk['COLUMN_NAME'];
                $refCol = $fk['REFERENCED_COLUMN_NAME'] ?: 'id';
                $upd = $fk['UPDATE_RULE'] ?: 'RESTRICT';
                $del = $fk['DELETE_RULE'] ?: 'RESTRICT';
                try {
                    $conn->exec("ALTER TABLE `{$esc($table)}` DROP FOREIGN KEY `{$esc($constraint)}`");
                    $newName = $constraint . "_to_" . $canonical;
                    $sqlAdd = sprintf(
                        "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON UPDATE %s ON DELETE %s",
                        $esc($table),
                        $esc($newName),
                        $esc($col),
                        $esc($canonical),
                        $esc($refCol),
                        $upd,
                        $del
                    );
                    $conn->exec($sqlAdd);
                    echo "<div class='ok'>✓ FK %s.%s réécrite</div>";
                } catch (Exception $e) {
                    echo "<div class='warn'>⚠ Impossible de réécrire FK {$constraint} sur {$table}. Détail: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        } else {
            echo "<p>Aucune FK ne référence $alias</p>";
        }

        // Renommer l'ancienne table alias en backup, puis créer la vue de compatibilité
        if ($hasTable($alias)) {
            $backup = $alias . '_backup_' . date('Ymd_His');
            try {
                $conn->exec("RENAME TABLE `{$alias}` TO `{$backup}`");
                echo "<p class='ok'>✓ Table {$alias} renommée en {$backup}</p>";
            } catch (Exception $e) {
                echo "<p class='warn'>⚠ Impossible de renommer {$alias}: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        // Créer/Remplacer la vue alias -> canonical
        try {
            $conn->exec("DROP VIEW IF EXISTS `{$alias}`");
        } catch (Exception $e) {
        }
        $cols = "id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at";
        try {
            $conn->exec("CREATE VIEW `{$alias}` AS SELECT {$cols} FROM `{$canonical}`");
            echo "<p class='ok'>✓ Vue {$alias} -> {$canonical} créée</p>";
        } catch (Exception $e) {
            echo "<p class='warn'>⚠ Impossible de créer la vue {$alias}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        // Une seule table existe; créer une vue pour l'autre nom
        $existing = $canonical;
        $viewName = $alias;
        if ($existing) {
            try {
                $conn->exec("DROP VIEW IF EXISTS `{$viewName}`");
            } catch (Exception $e) {
            }
            if (!$hasTable($viewName)) {
                $cols = "id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at";
                try {
                    $conn->exec("CREATE VIEW `{$viewName}` AS SELECT {$cols} FROM `{$existing}`");
                    echo "<p class='ok'>✓ Vue {$viewName} -> {$existing} créée</p>";
                } catch (Exception $e) {
                    echo "<p class='warn'>⚠ Impossible de créer la vue {$viewName}: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }

    echo "<h3 class='ok'>Unification structurelle terminée</h3>";
    echo "<ul><li>Une seule table physique est conservée: <strong>{$canonical}</strong></li><li>Compatibilité assurée via une vue pour l'autre nom: <strong>{$alias}</strong></li></ul>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
