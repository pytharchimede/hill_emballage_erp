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

// S'assurer que la table des overrides de permissions existe
try {
    $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission VARCHAR(100) NOT NULL,
        allowed TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uniq_user_perm (user_id, permission),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (Exception $e) { /* ignore si déjà créée */
}

// Liste des permissions disponibles (clé => libellé)
$ALL_PERMS = [
    'manage_users' => "Gérer les utilisateurs",
    'view_reports' => "Voir les rapports",
    'clients_read' => "Clients: lecture",
    'clients_create' => "Clients: création",
    'clients_update' => "Clients: modification",
    'clients_delete' => "Clients: suppression",
    'sales_read' => "Ventes: lecture",
    'sales_create' => "Ventes: création",
    'sales_update' => "Ventes: modification",
    'sales_delete' => "Ventes: suppression",
    'stock_read' => "Stock: lecture",
    'stock_update' => "Stock: mouvement/transfert",
    'payments_read' => "Paiements: lecture",
    'payments_create' => "Paiements: enregistrement",
    'payments_update' => "Paiements: modification",
    'products_read' => "Produits: lecture",
    'products_create' => "Produits: création",
    'products_update' => "Produits: modification",
    'products_delete' => "Produits: suppression",
    'deliveries_read' => "Livraisons: lecture",
    'deliveries_update' => "Livraisons: mise à jour"
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        // Adapter les rôles UI aux valeurs de l'ENUM BDD si nécessaire (évite NULL si schéma non migré)
        $mapRoleForStorage = function (string $wantedRole) use ($db): string {
            $wantedRole = trim(strtolower($wantedRole));
            $allowed = [];
            try {
                $col = $db->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
                if ($col && preg_match("/enum\((.*)\)/i", (string)($col['Type'] ?? ''), $m)) {
                    $raw = $m[1];
                    $allowed = array_map(fn($v) => trim(strtolower(trim($v, "'\""))), explode(',', $raw));
                }
            } catch (Exception $e) { /* ignore */
            }
            if (!$allowed) $allowed = ['admin', 'vendeur', 'livreur', 'comptable'];
            if (in_array($wantedRole, $allowed, true)) return $wantedRole;
            if ($wantedRole === 'commercial') return in_array('livreur', $allowed, true) ? 'livreur' : (in_array('vendeur', $allowed, true) ? 'vendeur' : $allowed[0]);
            if ($wantedRole === 'gerant') return in_array('vendeur', $allowed, true) ? 'vendeur' : (in_array('admin', $allowed, true) ? 'admin' : $allowed[0]);
            return in_array('vendeur', $allowed, true) ? 'vendeur' : $allowed[0];
        };
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'commercial';
            // normaliser les anciens rôles éventuellement envoyés
            if (function_exists('normalizeRole')) {
                $role = normalizeRole($role);
            }
            $storeRole = $mapRoleForStorage($role);
            $depot = (int)($_POST['depot_id'] ?? 0);
            $pass = password_hash($_POST['password'] ?? 'changeme', PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO users (username,email,password,full_name,role,depot_id) VALUES (?,?,?,?,?,?)");
            $st->execute([$username, $email, $pass, $full, $storeRole, $depot ?: null]);
            $newUserId = (int)$db->lastInsertId();
            // Persister les overrides de permissions si fournis
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($ALL_PERMS as $permKey => $label) {
                    $allowed = isset($_POST['permissions'][$permKey]) ? 1 : 0;
                    $ins = $db->prepare("INSERT INTO user_permissions (user_id, permission, allowed) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)");
                    $ins->execute([$newUserId, $permKey, $allowed]);
                }
            }
            $msg = 'Utilisateur créé';
            $msgType = 'success';
        }
        if ($action === 'update') {
            $id = (int)$_POST['id'];
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'commercial';
            if (function_exists('normalizeRole')) {
                $role = normalizeRole($role);
            }
            $storeRole = $mapRoleForStorage($role);
            $depot = (int)($_POST['depot_id'] ?? 0);
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $st = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, depot_id=?, password=?, updated_at=NOW() WHERE id=?");
                $st->execute([$username, $email, $full, $storeRole, $depot ?: null, $pass, $id]);
            } else {
                $st = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, depot_id=?, updated_at=NOW() WHERE id=?");
                $st->execute([$username, $email, $full, $storeRole, $depot ?: null, $id]);
            }
            // Mettre à jour les overrides si fournis
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($ALL_PERMS as $permKey => $label) {
                    $allowed = isset($_POST['permissions'][$permKey]) ? 1 : 0;
                    $ins = $db->prepare("INSERT INTO user_permissions (user_id, permission, allowed) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)");
                    $ins->execute([$id, $permKey, $allowed]);
                }
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
$where = 'WHERE u.is_active=1';
$params = [];
if ($search) {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q, $q);
}
if ($roleF !== 'all') {
    $where .= ' AND u.role=?';
    $params[] = $roleF;
}
$ct = $db->prepare("SELECT COUNT(*) t FROM users u $where");
$ct->execute($params);
$total = (int)($ct->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
$st = $db->prepare("SELECT u.*, d.nom as depot_nom FROM users u LEFT JOIN depots d ON u.depot_id=d.id $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));
$depots = $db->query("SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les overrides de permissions pour les utilisateurs affichés
$ids = array_map(fn($r) => (int)$r['id'], $rows);
$permMap = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $ps = $db->prepare("SELECT user_id, permission, allowed FROM user_permissions WHERE user_id IN ($in)");
    $ps->execute($ids);
    while ($pr = $ps->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int)$pr['user_id'];
        if (!isset($permMap[$uid])) $permMap[$uid] = [];
        $permMap[$uid][$pr['permission']] = (int)$pr['allowed'];
    }
}

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
                <?php foreach (['admin', 'gerant', 'commercial', 'comptable'] as $r): ?><option value="<?= $r ?>" <?= $roleF === $r ? 'selected' : '' ?>><?= htmlspecialchars(roleLabel($r)) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
        <div class="col-md-3">
            <a class="btn" href="<?= BASE_URL ?>/app/export/users_xls.php">Export XLS</a>
            <a class="btn" href="<?= BASE_URL ?>/app/export/users_pdf.php">Export PDF</a>
        </div>
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
                    <td><?= htmlspecialchars(roleLabel($u['role'])) ?></td>
                    <td><?= htmlspecialchars($u['depot_nom'] ?? '-') ?></td>
                    <td><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '—' ?></td>
                    <td>
                        <button class="btn btn-edit" type="button"
                            data-id="<?= (int)$u['id'] ?>"
                            data-full="<?= htmlspecialchars($u['full_name']) ?>"
                            data-email="<?= htmlspecialchars($u['email']) ?>"
                            data-username="<?= htmlspecialchars($u['username']) ?>"
                            data-role="<?= htmlspecialchars($u['role']) ?>"
                            data-depot-id="<?= (int)($u['depot_id'] ?? 0) ?>"><i class="fas fa-edit"></i></button>
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

<div class="modal fade modal-modern" id="addUserModal" tabindex="-1" aria-hidden="true" role="dialog" aria-labelledby="addUserModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus"></i> Nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:65vh; overflow:auto;">
                    <input type="hidden" name="action" value="create" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-2"><label class="form-label">Nom complet</label><input class="form-control" name="full_name" required /></div>
                            <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" /></div>
                            <div class="mb-2"><label class="form-label">Identifiant</label><input class="form-control" name="username" required /></div>
                            <div class="mb-2"><label class="form-label">Mot de passe</label><input class="form-control" type="password" name="password" required /></div>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="form-label">Rôle</label><select class="form-select" name="role"><?php foreach (['admin', 'gerant', 'commercial', 'comptable'] as $r): ?><option value="<?= $r ?>"><?= htmlspecialchars(roleLabel($r)) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-6"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id">
                                        <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                                    </select></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Droits (overrides)</strong>
                            <div class="row" id="createPermGrid" style="max-height:45vh; overflow:auto;">
                                <?php foreach ($ALL_PERMS as $k => $label): ?>
                                    <div class="col-md-6">
                                        <label class="form-check-label"><input class="form-check-input" type="checkbox" name="permissions[<?= htmlspecialchars($k) ?>]" /> <?= htmlspecialchars($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Si non coché, le droit suit le rôle. Cocher = forcer l'autorisation (override autorisé), décocher en édition pour refuser.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-modern" id="editUserModal" tabindex="-1" aria-hidden="true" role="dialog" aria-labelledby="editUserModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-edit"></i> Modifier utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:65vh; overflow:auto;">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" id="edit_id" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-2"><label class="form-label">Nom complet</label><input class="form-control" name="full_name" id="edit_full" required /></div>
                            <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" id="edit_email" required /></div>
                            <div class="mb-2"><label class="form-label">Identifiant</label><input class="form-control" name="username" id="edit_username" required /></div>
                            <div class="mb-2"><label class="form-label">Nouveau mot de passe (optionnel)</label><input class="form-control" type="password" name="password" /></div>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="form-label">Rôle</label><select class="form-select" name="role" id="edit_role"><?php foreach (['admin', 'gerant', 'commercial', 'comptable'] as $r): ?><option value="<?= $r ?>"><?= htmlspecialchars(roleLabel($r)) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-6"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id" id="edit_depot">
                                        <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                                    </select></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Droits (overrides)</strong>
                            <div class="row" id="editPermGrid" style="max-height:45vh; overflow:auto;">
                                <?php foreach ($ALL_PERMS as $k => $label): ?>
                                    <div class="col-md-6">
                                        <label class="form-check-label"><input class="form-check-input perm-item" type="checkbox" name="permissions[<?= htmlspecialchars($k) ?>]" data-perm="<?= htmlspecialchars($k) ?>" /> <?= htmlspecialchars($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Cocher pour forcer l'autorisation, décocher pour forcer le refus. Si non précisé, le rôle s'applique.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary" type="submit">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="users-config"
    data-role-defaults='<?= json_encode([
                            'admin' => getRolePermissions('admin'),
                            'gerant' => getRolePermissions('gerant'),
                            'commercial' => getRolePermissions('commercial'),
                            'comptable' => getRolePermissions('comptable'),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
    data-user-overrides='<?= json_encode($permMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
</div>
<script src="<?= ASSETS_URL ?>/js/users.js"></script>

<?php include 'includes/footer.php'; ?>