<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('manage_users')) {
    setFlashMessage('warning', "Accès refusé: Utilisateurs");
    header('Location: dashboard.php');
    exit();
}

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'vendeur';
            $depot = (int)($_POST['depot_id'] ?? 0);
            $pass = password_hash($_POST['password'] ?? 'changeme', PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO users (username,email,password,full_name,role,depot_id) VALUES (?,?,?,?,?,?)");
            $st->execute([$username, $email, $pass, $full, $role, $depot ?: null]);
            $msg = 'Utilisateur créé';
            $msgType = 'success';
        }
        if ($action === 'update') {
            $id = (int)$_POST['id'];
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'vendeur';
            $depot = (int)($_POST['depot_id'] ?? 0);
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $st = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, depot_id=?, password=?, updated_at=NOW() WHERE id=?");
                $st->execute([$username, $email, $full, $role, $depot ?: null, $pass, $id]);
            } else {
                $st = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, depot_id=?, updated_at=NOW() WHERE id=?");
                $st->execute([$username, $email, $full, $role, $depot ?: null, $id]);
            }
            $msg = 'Utilisateur mis à jour';
            $msgType = 'success';
        }
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$id]);
            $msg = 'Utilisateur désactivé';
            $msgType = 'success';
        }
    } catch (Exception $e) {
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

$search = $_GET['search'] ?? '';
$roleF = $_GET['role'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$where = 'WHERE is_active=1';
$params = [];
if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q, $q);
}
if ($roleF !== 'all') {
    $where .= ' AND role=?';
    $params[] = $roleF;
}
$ct = $db->prepare("SELECT COUNT(*) t FROM users $where");
$ct->execute($params);
$total = (int)($ct->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
$st = $db->prepare("SELECT u.*, d.nom as depot_nom FROM users u LEFT JOIN depots d ON u.depot_id=d.id $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));
$depots = $db->query("SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Utilisateurs';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-user-cog"></i> Utilisateurs</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus"></i> Nouvel utilisateur</button>
    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" style="margin-top:1rem;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
</div>

<div class="card">
    <form class="row g-2" method="get">
        <div class="col-md-4"><input class="form-control" name="search" placeholder="Rechercher (nom/email)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-3">
            <select class="form-select" name="role">
                <option value="all" <?= $roleF === 'all' ? 'selected' : '' ?>>Tous rôles</option>
                <?php foreach (['admin', 'vendeur', 'livreur', 'comptable'] as $r): ?><option value="<?= $r ?>" <?= $roleF === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Dépôt</th>
                <th>Dernière connexion</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                    <td><?= htmlspecialchars($u['depot_nom'] ?? '-') ?></td>
                    <td><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '—' ?></td>
                    <td>
                        <button class="btn" onclick='fillUser(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="fas fa-edit"></i></button>
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Désactiver cet utilisateur ?');">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                            <button class="btn btn-danger" type="submit"><i class="fas fa-user-slash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="6">Aucun utilisateur</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleF) ?>"><?= $i ?></a><?php endfor; ?>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouvel utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create" />
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Nom complet</label><input class="form-control" name="full_name" required /></div>
                    <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required /></div>
                    <div class="mb-2"><label class="form-label">Identifiant</label><input class="form-control" name="username" required /></div>
                    <div class="mb-2"><label class="form-label">Mot de passe</label><input class="form-control" type="password" name="password" required /></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Rôle</label><select class="form-select" name="role"><?php foreach (['admin', 'vendeur', 'livreur', 'comptable'] as $r): ?><option value="<?= $r ?>"><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id">
                                <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="id" id="edit_id" />
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Nom complet</label><input class="form-control" name="full_name" id="edit_full" required /></div>
                    <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" id="edit_email" required /></div>
                    <div class="mb-2"><label class="form-label">Identifiant</label><input class="form-control" name="username" id="edit_username" required /></div>
                    <div class="mb-2"><label class="form-label">Nouveau mot de passe (optionnel)</label><input class="form-control" type="password" name="password" /></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Rôle</label><select class="form-select" name="role" id="edit_role"><?php foreach (['admin', 'vendeur', 'livreur', 'comptable'] as $r): ?><option value="<?= $r ?>"><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id" id="edit_depot">
                                <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Mettre à jour</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function fillUser(u) {
        document.getElementById('edit_id').value = u.id;
        document.getElementById('edit_full').value = u.full_name || '';
        document.getElementById('edit_email').value = u.email || '';
        document.getElementById('edit_username').value = u.username || '';
        document.getElementById('edit_role').value = u.role || 'vendeur';
        document.getElementById('edit_depot').value = u.depot_id || '';
        const el = document.getElementById('editUserModal');
        if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(el).show();
        else if (window.__fallbackShowModal) window.__fallbackShowModal('editUserModal');
        else {
            el.classList.add('show');
            el.style.display = 'block';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>