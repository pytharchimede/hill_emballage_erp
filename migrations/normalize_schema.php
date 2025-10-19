<?php

/**
 * Normalisation du schéma (structure-only):
 * - Unifie la table Produits: conserve une seule table physique (produits ou products) selon présence/volume
 *   + ajoute image_path si manquant
 *   + crée une vue pour l'autre nom afin de garder la compatibilité
 *   + réécrit les FKs pointant vers l'ancien nom vers le canonique
 * - Crée les tables d'inventaire si manquantes: stock_entries (entrées / réceptions / achats), stock_adjustments (inventaires / corrections)
 * - S'assure des colonnes manquantes sur tables clés: ventes/vente_items, stock, depots, clients, users
 * - Ajoute index utiles
 *
 * IMPORTANT: aucune modification de lignes de données (INSERT/UPDATE/DELETE) — structure only.
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Normalisation du schéma (structure only)</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00} pre{background:#f7f7f7;padding:10px;border:1px solid #ddd}</style>";

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) throw new Exception('Connexion DB impossible');

    // Helpers schéma
    $hasTable = function ($name) use ($db) {
        $s = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $s->execute([$name]);
        return $s->fetchColumn() > 0;
    };
    $getTableType = function ($name) use ($db) {
        $s = $db->prepare("SELECT TABLE_TYPE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $s->execute([$name]);
        $t = $s->fetchColumn();
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
    $hasColumn = function ($table, $col) use ($db) {
        $s = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $s->execute([$table, $col]);
        return $s->fetchColumn() > 0;
    };
    $countRows = function ($name) use ($db) {
        try {
            return (int)$db->query("SELECT COUNT(*) FROM `{$name}`")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    };

    // 1) Unifier produits/products structurellement
    $existsProduits = $hasTable('produits');
    $existsProducts = $hasTable('products');
    $typeProduits = $existsProduits ? $getTableType('produits') : null;
    $typeProducts = $existsProducts ? $getTableType('products') : null;

    $canonicalPhysical = null; // table physique cible pour ALTER/FK
    $logicalAlias = null;      // le second nom à présenter en vue si manquant

    if ($existsProduits && $existsProducts) {
        $pBase = $isBaseTable('produits');
        $qBase = $isBaseTable('products');
        if ($pBase && !$qBase) {
            $canonicalPhysical = 'produits';
            $logicalAlias = 'products';
            echo "<p>Deux objets trouvés. Canonique (physique): <b>produits</b> (products est une VUE)</p>";
        } elseif ($qBase && !$pBase) {
            $canonicalPhysical = 'products';
            $logicalAlias = 'produits';
            echo "<p>Deux objets trouvés. Canonique (physique): <b>products</b> (produits est une VUE)</p>";
        } elseif ($pBase && $qBase) {
            // 2 tables physiques: choisir par volume
            $c1 = $countRows('produits');
            $c2 = $countRows('products');
            $canonicalPhysical = ($c1 >= $c2) ? 'produits' : 'products';
            $logicalAlias = ($canonicalPhysical === 'produits') ? 'products' : 'produits';
            echo "<p>Deux tables physiques détectées. Canonique: <b>{$canonicalPhysical}</b> (produits=$c1, products=$c2)</p>";
        } else {
            // Les deux sont des vues: pas de table physique
            $canonicalPhysical = null;
            $logicalAlias = 'products'; // valeur par défaut pour messages
            echo "<p class='warn'>⚠ produits et products sont des VUES. Impossible d'altérer une VUE. FKs et ALTER seront ignorés. Exécutez plutôt la migration de consolidation des produits.</p>";
        }
    } elseif ($existsProduits) {
        if ($isBaseTable('produits')) {
            $canonicalPhysical = 'produits';
            $logicalAlias = 'products';
            echo "<p>Objet produits trouvé (type: {$typeProduits}). Canonique physique: <b>produits</b>.</p>";
        } else {
            // produits est une VUE
            $canonicalPhysical = null;
            $logicalAlias = 'products';
            echo "<p class='warn'>⚠ 'produits' est une VUE. Aucune table physique de produits détectée. Les opérations ALTER/FK seront ignorées.</p>";
        }
    } elseif ($existsProducts) {
        if ($isBaseTable('products')) {
            $canonicalPhysical = 'products';
            $logicalAlias = 'produits';
            echo "<p>Objet products trouvé (type: {$typeProducts}). Canonique physique: <b>products</b>.</p>";
        } else {
            $canonicalPhysical = null;
            $logicalAlias = 'produits';
            echo "<p class='warn'>⚠ 'products' est une VUE. Aucune table physique de produits détectée. Les opérations ALTER/FK seront ignorées.</p>";
        }
    } else {
        // Créer une table physique 'produits' si aucune n'existe
        $db->exec("CREATE TABLE produits (
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
        )");
        $canonicalPhysical = 'produits';
        $logicalAlias = 'products';
        echo "<p class='ok'>✓ Table produits créée (aucune table/ vue produits existante trouvée)</p>";
    }

    // S'assurer image_path sur la table physique canonique
    if ($canonicalPhysical && !$hasColumn($canonicalPhysical, 'image_path')) {
        $db->exec("ALTER TABLE `{$canonicalPhysical}` ADD COLUMN image_path VARCHAR(255) NULL AFTER points_fidelite");
        echo "<p class='ok'>✓ Colonne image_path ajoutée à {$canonicalPhysical}</p>";
    } elseif (!$canonicalPhysical) {
        echo "<p class='warn'>⚠ Aucune table physique de produits disponible. image_path non vérifié (les VUES ne peuvent pas être altérées).</p>";
        // Appel à l'action: proposer de lancer la consolidation
        echo "<div style='border:1px solid #f0ad4e;padding:12px;border-radius:6px;background:#fff8e1;margin:12px 0'>";
        echo "<b>Action recommandée:</b> exécutez la <a href='./consolidate_products.php'>consolidation des produits</a> pour créer une table physique, puis revenez sur cette page pour finaliser les FKs.";
        echo "</div>";
    }

    // Vue de compatibilité (seulement si l'alias n'existe pas du tout)
    if ($logicalAlias && !$hasTable($logicalAlias) && $canonicalPhysical) {
        try {
            $db->exec("DROP VIEW IF EXISTS `{$logicalAlias}`");
        } catch (Exception $e) {
        }
        $db->exec("CREATE VIEW `{$logicalAlias}` AS SELECT id, nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active, created_at, updated_at FROM `{$canonicalPhysical}`");
        echo "<p class='ok'>✓ Vue {$logicalAlias} → {$canonicalPhysical} créée</p>";
    }

    // Réécriture des FKs vers la table non canonique
    $stmt = $db->prepare("SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
                           FROM information_schema.KEY_COLUMN_USAGE kcu
                           JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                             ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                          WHERE kcu.TABLE_SCHEMA = DATABASE()
                            AND kcu.REFERENCED_TABLE_NAME = ?");
    // Réécriture des FKs depuis l'alias seulement si alias est une table physique et qu'une cible physique existe
    if ($logicalAlias && $canonicalPhysical && $isBaseTable($logicalAlias)) {
        $stmt->execute([$logicalAlias]);
    } else {
        $stmt->execute(['__no_such_table__']); // évite de renvoyer des FKs
    }
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
            $newName = $constraint . "_to_" . $canonical;
            $sqlAdd = sprintf(
                "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON UPDATE %s ON DELETE %s",
                $table,
                $newName,
                $col,
                $canonicalPhysical,
                $refCol,
                $upd,
                $del
            );
            $db->exec($sqlAdd);
            echo "<div class='ok'>✓ FK {$constraint} (table {$table}) → {$canonicalPhysical}</div>";
        } catch (Exception $e) {
            echo "<div class='warn'>⚠ Réécriture FK échouée {$constraint}: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // 2) Tables d’inventaire si manquantes
    // stock_entries: enregistrements d'entrée en stock (achat, réception, retour client…)
    if (!$hasTable('stock_entries')) {
        $db->exec("CREATE TABLE stock_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produit_id INT NOT NULL,
            depot_id INT NOT NULL,
            quantite DECIMAL(10,2) NOT NULL,
            cout_unitaire DECIMAL(10,2) NULL,
            reference VARCHAR(100) NULL,
            type_entry ENUM('achat','reception','retour_client','autre') DEFAULT 'achat',
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (depot_id) REFERENCES depots(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )");
        if ($canonicalPhysical) {
            try {
                $db->exec("ALTER TABLE stock_entries ADD CONSTRAINT fk_se_produit FOREIGN KEY (produit_id) REFERENCES {$canonicalPhysical}(id)");
            } catch (Exception $e) {
                echo "<p class='warn'>⚠ FK stock_entries.produit_id → {$canonicalPhysical}(id) ignorée: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='warn'>⚠ Aucune table physique de produits. stock_entries créé SANS contrainte FK produit_id.</p>";
        }
        $db->exec("CREATE INDEX idx_stock_entries_prod_depot ON stock_entries(produit_id, depot_id)");
        echo "<p class='ok'>✓ Table stock_entries créée</p>";
    }

    // stock_adjustments: ajustements d’inventaire (inventaire physique, correction, casse)
    if (!$hasTable('stock_adjustments')) {
        $db->exec("CREATE TABLE stock_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produit_id INT NOT NULL,
            depot_id INT NOT NULL,
            delta DECIMAL(10,2) NOT NULL,
            motif ENUM('inventaire','correction','casse','perte','autre') DEFAULT 'inventaire',
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (depot_id) REFERENCES depots(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )");
        if ($canonicalPhysical) {
            try {
                $db->exec("ALTER TABLE stock_adjustments ADD CONSTRAINT fk_sa_produit FOREIGN KEY (produit_id) REFERENCES {$canonicalPhysical}(id)");
            } catch (Exception $e) {
                echo "<p class='warn'>⚠ FK stock_adjustments.produit_id → {$canonicalPhysical}(id) ignorée: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='warn'>⚠ Aucune table physique de produits. stock_adjustments créé SANS contrainte FK produit_id.</p>";
        }
        $db->exec("CREATE INDEX idx_stock_adjust_prod_depot ON stock_adjustments(produit_id, depot_id)");
        echo "<p class='ok'>✓ Table stock_adjustments créée</p>";
    }

    // 3) Colonnes et contraintes minimales sur tables existantes
    // depots: is_active si absent
    if ($hasTable('depots') && !$hasColumn('depots', 'is_active')) {
        $db->exec("ALTER TABLE depots ADD COLUMN is_active BOOLEAN DEFAULT 1");
        echo "<p class='ok'>✓ depots.is_active ajouté</p>";
    }

    // stock: assurer (produit_id, depot_id, quantite et clé unique)
    if ($hasTable('stock')) {
        if (!$hasColumn('stock', 'produit_id') && $hasColumn('stock', 'product_id')) {
            // cas schéma backend: on garde product_id mais on veut FK cohérente
            // Structure-only: on retente la FK vers la table canonique
            try {
                $db->exec("ALTER TABLE stock DROP FOREIGN KEY stock_ibfk_product");
            } catch (Exception $e) {
            }
            if ($canonicalPhysical) {
                try {
                    $db->exec("ALTER TABLE stock ADD CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES {$canonicalPhysical}(id)");
                    echo "<p class='ok'>✓ FK stock.product_id → {$canonicalPhysical}(id)</p>";
                } catch (Exception $e) {
                    echo "<p class='warn'>⚠ FK stock.product_id: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            } else {
                echo "<p class='warn'>⚠ Aucune table physique de produits. FK stock.product_id non créée.</p>";
            }
        } else {
            // schéma app: produit_id
            if ($canonicalPhysical) {
                try {
                    $db->exec("ALTER TABLE stock ADD CONSTRAINT fk_stock_produit FOREIGN KEY (produit_id) REFERENCES {$canonicalPhysical}(id)");
                    echo "<p class='ok'>✓ FK stock.produit_id → {$canonicalPhysical}(id)</p>";
                } catch (Exception $e) {
                    // FK peut exister déjà
                }
            } else {
                echo "<p class='warn'>⚠ Aucune table physique de produits. FK stock.produit_id non créée.</p>";
            }
        }
        // Unique produit/depot si manquant
        try {
            $db->exec("ALTER TABLE stock ADD UNIQUE KEY unique_produit_depot (produit_id, depot_id)");
            echo "<p class='ok'>✓ Index unique stock(produit_id,depot_id)</p>";
        } catch (Exception $e) {
        }
    }

    // ventes/vente_items cohérence FK produits
    if ($hasTable('vente_items')) {
        if ($canonicalPhysical) {
            try {
                $db->exec("ALTER TABLE vente_items ADD CONSTRAINT fk_vi_produit FOREIGN KEY (produit_id) REFERENCES {$canonicalPhysical}(id)");
                echo "<p class='ok'>✓ FK vente_items.produit_id → {$canonicalPhysical}(id)</p>";
            } catch (Exception $e) {
            }
        } else {
            echo "<p class='warn'>⚠ Aucune table physique de produits. FK vente_items.produit_id non créée.</p>";
        }
    }

    // payments: déjà en place via ventes; rien à changer ici structure-only

    echo "<h3 class='ok'>Normalisation terminée (structure only)</h3>";
    echo "<ul>" .
        "<li>Une seule table logique pour les produits (table physique canonique si disponible + vue de compatibilité)</li>" .
        "<li>Tables d'inventaire créées: stock_entries, stock_adjustments</li>" .
        "<li>FK et index principaux vérifiés/ajoutés</li>" .
        "</ul>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
