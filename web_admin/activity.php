<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('manage_users')) {
    setFlashMessage('warning', "Accès refusé à l'historique global.");
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}

// Filtres
$u = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$action = $_GET['action'] ?? '';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];
if ($u > 0) {
    $where .= ' AND a.user_id=?';
    $params[] = $u;
}
if ($action !== '') {
    $where .= ' AND a.action=?';
    $params[] = $action;
}
if ($d1) {
    $where .= ' AND a.created_at>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND a.created_at<=?';
    $params[] = $d2;
}

// count
$cs = $db->prepare("SELECT COUNT(*) t FROM audit_logs a $where");
$cs->execute($params);
$total = (int)($cs->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

$sql = "SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id
         $where ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

// users list for filter
$users = $db->query("SELECT id, full_name FROM users WHERE is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Historique d'activité";
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-history"></i> Historique global</h1>
</div>

<div class="card">
    <form class="row g-2" method="get">
        <div class="col-md-3">
            <select name="user" class="form-select">
                <option value="0">Tous utilisateurs</option>
                <?php foreach ($users as $uu): ?>
                    <option value="<?= (int)$uu['id'] ?>" <?= $u === $uu['id'] ? 'selected' : '' ?>><?= htmlspecialchars($uu['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input class="form-control" name="action" placeholder="Action (CREATE, VIEW...)" value="<?= htmlspecialchars($action) ?>" />
        </div>
        <div class="col-md-2"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-2"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/activity_xls.php?user=<?= $u ?>&action=<?= urlencode($action) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>">Export XLS</a>
        </div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/activity_pdf.php?user=<?= $u ?>&action=<?= urlencode($action) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>">Export PDF</a>
        </div>
    </form>

    <table class="table" data-type="activity">
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Entité</th>
                <th>ID</th>
                <th>Détails</th>
                <th>IP</th>
                <th>UA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td><?= htmlspecialchars($r['full_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['action']) ?></td>
                    <td><?= htmlspecialchars($r['entity'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['entity_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['details'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['user_agent'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="8">Aucune activité</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&user=<?= $u ?>&action=<?= urlencode($action) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>