<?php
// Migration: add delivery-related columns to ventes table if missing
// Access: require login + sales_update permission

require_once __DIR__ . '/../app/includes/config.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    if (!isLoggedIn()) {
        http_response_code(403);
        echo "<h3>Accès refusé: utilisateur non authentifié.</h3>";
        exit;
    }
    if (!function_exists('hasPermission') || !hasPermission('sales_update')) {
        http_response_code(403);
        echo "<h3>Accès refusé: permissions insuffisantes.</h3>";
        exit;
    }
}

function colExists(PDO $db, string $table, string $col): bool
{
    $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->execute([$table, $col]);
    return (bool)$q->fetchColumn();
}

$results = [];
$errors = [];

try {
    $db->beginTransaction();

    // 1) delivery_mode (VARCHAR(20) NOT NULL DEFAULT 'sur_place')
    if (!colExists($db, 'ventes', 'delivery_mode')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_mode VARCHAR(20) NOT NULL DEFAULT 'sur_place' AFTER commentaire");
        $results[] = "+ Ajout de la colonne delivery_mode (VARCHAR(20), DEFAULT 'sur_place')";
    } else {
        $results[] = "= Colonne delivery_mode déjà présente";
    }

    // 2) livreur_id (INT NULL)
    if (!colExists($db, 'ventes', 'livreur_id')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN livreur_id INT NULL AFTER delivery_mode");
        $results[] = "+ Ajout de la colonne livreur_id (INT NULL)";
        // index (optionnel mais recommandé)
        try {
            $db->exec("CREATE INDEX idx_ventes_livreur_id ON ventes(livreur_id)");
        } catch (Exception $e) { /* index existant ou erreur mineure */
        }
    } else {
        $results[] = "= Colonne livreur_id déjà présente";
    }

    // 3) delivery_address (VARCHAR(255) NULL)
    if (!colExists($db, 'ventes', 'delivery_address')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_address VARCHAR(255) NULL AFTER livreur_id");
        $results[] = "+ Ajout de la colonne delivery_address (VARCHAR(255) NULL)";
    } else {
        $results[] = "= Colonne delivery_address déjà présente";
    }

    // 4) delivery_latitude (DECIMAL(10,7) NULL)
    if (!colExists($db, 'ventes', 'delivery_latitude')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_latitude DECIMAL(10,7) NULL AFTER delivery_address");
        $results[] = "+ Ajout de la colonne delivery_latitude (DECIMAL(10,7) NULL)";
    } else {
        $results[] = "= Colonne delivery_latitude déjà présente";
    }

    // 5) delivery_longitude (DECIMAL(10,7) NULL)
    if (!colExists($db, 'ventes', 'delivery_longitude')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_longitude DECIMAL(10,7) NULL AFTER delivery_latitude");
        $results[] = "+ Ajout de la colonne delivery_longitude (DECIMAL(10,7) NULL)";
    } else {
        $results[] = "= Colonne delivery_longitude déjà présente";
    }

    // 6) delivery_details (TEXT NULL)
    if (!colExists($db, 'ventes', 'delivery_details')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_details TEXT NULL AFTER delivery_longitude");
        $results[] = "+ Ajout de la colonne delivery_details (TEXT NULL)";
    } else {
        $results[] = "= Colonne delivery_details déjà présente";
    }

    $db->commit();
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    $errors[] = $e->getMessage();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Migration livraisons - ventes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" />
</head>

<body class="p-4">
    <div class="container">
        <h3>Migration: colonnes de livraison pour la table ventes</h3>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <div><strong>Erreur(s) durant la migration:</strong></div>
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-success">Migration exécutée.</div>
        <?php endif; ?>

        <ul class="list-group mb-3">
            <?php foreach ($results as $r): ?>
                <li class="list-group-item"><?= htmlspecialchars($r) ?></li>
            <?php endforeach; ?>
        </ul>

        <a class="btn btn-primary" href="<?= BASE_URL ?>/app/sales.php">Retour aux ventes</a>
    </div>
</body>

</html>