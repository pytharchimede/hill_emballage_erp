<?php
require_once 'includes/config.php';
requireLogin();

$role = $_SESSION['user_role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$pageTitle = 'Solde vendeur';

include __DIR__ . '/includes/header.php';

function fetchAll($sql, $params = [])
{
    global $db;
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function fetchOne($sql, $params = [])
{
    global $db;
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="page-header">
    <h1>Solde vendeur</h1>
    <div class="breadcrumb">Crédits à régulariser</div>
</div>

<?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
    <?php $balances = fetchAll("SELECT vb.vendeur_id, vb.balance, u.full_name, u.role FROM vendor_balances vb LEFT JOIN users u ON vb.vendeur_id=u.id ORDER BY vb.balance DESC"); ?>
    <div class="card">
        <h3>Soldes des vendeurs</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Vendeur</th>
                        <th>Rôle</th>
                        <th>Solde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($balances as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['full_name'] ?? ('#' . $b['vendeur_id'])) ?></td>
                            <td><?= htmlspecialchars($b['role'] ?? '') ?></td>
                            <td><?= formatMoney((float)$b['balance']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
// Vue personnelle pour vendeur/livreur/commercial
if (in_array($role, ['vendeur', 'livreur', 'commercial'], true)) {
    $my = fetchOne("SELECT balance FROM vendor_balances WHERE vendeur_id=?", [$userId]);
    $hist = fetchAll("SELECT * FROM vendor_balance_history WHERE vendeur_id=? ORDER BY created_at DESC LIMIT 50", [$userId]);
?>
    <div class="card">
        <h3>Mon solde</h3>
        <p class="lead">Solde actuel: <strong><?= formatMoney((float)($my['balance'] ?? 0)) ?></strong></p>
        <h5>Historique (50 derniers)</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Motif</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hist as $h): ?>
                        <tr>
                            <td><?= formatDateTime($h['created_at']) ?></td>
                            <td><?= htmlspecialchars($h['reason']) ?></td>
                            <td><?= formatMoney((float)$h['change_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php include __DIR__ . '/includes/footer.php'; ?>