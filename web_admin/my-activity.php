<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$uid = (int)$_SESSION['user_id'];
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$where = 'WHERE a.user_id=?';
$params = [$uid];
if ($d1) {
    $where .= ' AND a.created_at>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND a.created_at<=?';
    $params[] = $d2;
}

$cs = $db->prepare("SELECT COUNT(*) t FROM audit_logs a $where");
$cs->execute($params);
$total = (int)($cs->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
$st = $db->prepare("SELECT a.* FROM audit_logs a $where ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

$pageTitle = 'Mon activité';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-user-clock"></i> Mon activité</h1>
</div>

<div class="card">
    <form class="row g-2" method="get">
        <div class="col-md-3"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-3"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
    </form>
    <table class="table" data-type="activity">
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Entité</th>
                <th>ID</th>
                <th>Détails</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td><?= htmlspecialchars($r['action']) ?></td>
                    <td><?= htmlspecialchars($r['entity'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['entity_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['details'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="6">Aucune activité</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>