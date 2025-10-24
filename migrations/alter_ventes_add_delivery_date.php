<?php
// Migration: add delivery_date column to ventes if missing
require_once __DIR__ . '/../app/includes/config.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    if (!isLoggedIn()) {
        http_response_code(403);
        echo '<h3>Accès refusé</h3>';
        exit;
    }
    if (!hasPermission('sales_update')) {
        http_response_code(403);
        echo '<h3>Accès refusé</h3>';
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
    if (!colExists($db, 'ventes', 'delivery_date')) {
        $db->exec("ALTER TABLE ventes ADD COLUMN delivery_date DATE NULL AFTER delivery_details");
        $results[] = "+ Ajout de la colonne delivery_date (DATE NULL)";
        try {
            $db->exec("CREATE INDEX idx_ventes_delivery_date ON ventes(delivery_date)");
        } catch (Exception $e) {
        }
    } else {
        $results[] = "= Colonne delivery_date déjà présente";
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
    <title>Migration delivery_date</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" />
</head>

<body class="p-4">
    <div class="container">
        <h3>Migration: ajout de delivery_date à ventes</h3>
        <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) {
                                                                    echo '<div>' . htmlspecialchars($e) . '</div>';
                                                                } ?></div><?php else: ?><div class="alert alert-success">OK</div><?php endif; ?>
        <ul class="list-group mb-3"><?php foreach ($results as $r) {
                                        echo '<li class="list-group-item">' . htmlspecialchars($r) . '</li>';
                                    } ?></ul>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/app/sales.php">Retour aux ventes</a>
    </div>
</body>

</html>