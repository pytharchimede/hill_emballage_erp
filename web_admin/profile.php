<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit();
}

$msg = '';
$type = 'success';

// Handle POST updates: password and profile photo only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['change_password'])) {
            $pwd = $_POST['new_password'] ?? '';
            $pwd2 = $_POST['confirm_password'] ?? '';
            if ($pwd === '' || strlen($pwd) < 6) throw new Exception('Mot de passe trop court.');
            if ($pwd !== $pwd2) throw new Exception('Les mots de passe ne correspondent pas.');
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $st = $db->prepare('UPDATE users SET password=?, updated_at=NOW() WHERE id=?');
            $st->execute([$hash, $_SESSION['user_id']]);
            $msg = 'Mot de passe mis à jour.';
            log_action('UPDATE', 'users', (int)$_SESSION['user_id'], ['field' => 'password']);
        }
        if (isset($_POST['change_photo'])) {
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) throw new Exception('Fichier invalide.');
            $f = $_FILES['photo'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $mime = mime_content_type($f['tmp_name']);
            if (!isset($allowed[$mime])) throw new Exception('Type d\'image non supporté.');
            $ext = $allowed[$mime];
            $dir = realpath(__DIR__ . '/../uploads');
            if (!$dir) {
                $dirPath = __DIR__ . '/../uploads';
                if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);
                $dir = realpath($dirPath);
            }
            $sub = $dir . DIRECTORY_SEPARATOR . 'profiles';
            if (!is_dir($sub)) mkdir($sub, 0777, true);
            $fileName = 'u' . (int)$_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $dest = $sub . DIRECTORY_SEPARATOR . $fileName;
            if (!move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('Échec upload.');
            // URL publique correcte sous la racine du projet (et non sous /web_admin)
            $relPath = BASE_URL . '/uploads/profiles/' . $fileName;
            // Stocker chemin relatif (web)
            $st = $db->prepare('UPDATE users SET profile_photo=?, updated_at=NOW() WHERE id=?');
            $st->execute([$relPath, $_SESSION['user_id']]);
            $msg = 'Photo de profil mise à jour.';
            log_action('UPDATE', 'users', (int)$_SESSION['user_id'], ['field' => 'profile_photo']);
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $type = 'error';
    }
    // rafraîchir données utilisateur
    $user = getCurrentUser();
}

$pageTitle = 'Mon profil';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-user-circle"></i> Mon profil</h1>
    <p class="breadcrumb">Vos informations et droits d\'accès</p>
    <?php if ($msg): ?><div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
</div>

<div class="card">
    <h2>Informations</h2>
    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <?php
            // Construire une URL d'image fiable en corrigeant d'anciens chemins éventuels
            $photoUrl = trim((string)($user['profile_photo'] ?? ''));
            if ($photoUrl !== '') {
                if (strpos($photoUrl, '/web_admin/uploads/') !== false) {
                    $corrected = str_replace('/web_admin/uploads/', '/uploads/', $photoUrl);
                    // Vérifier existence du fichier pour décider d'une mise à jour silencieuse
                    $projectRoot = realpath(dirname(__DIR__)); // c:\wamp\www\hill
                    $fsPath = $projectRoot . str_replace(['\\'], [''], str_replace(BASE_URL, '', $corrected));
                    if (is_file($fsPath)) {
                        $photoUrl = $corrected;
                        // Mise à jour du chemin stocké (une fois) pour l'utilisateur courant
                        try {
                            $u = $db->prepare('UPDATE users SET profile_photo=? WHERE id=?');
                            $u->execute([$photoUrl, (int)$_SESSION['user_id']]);
                        } catch (Exception $e) {
                        }
                    }
                }
            }
            ?>
            <?php if ($photoUrl !== ''): ?>
                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo de profil" title="Photo de profil" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #FFD700;" />
            <?php else: ?>
                <div style="width:80px;height:80px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border:2px solid #FFD700;">
                    <i class="fas fa-user" style="font-size:32px;color:#999;" aria-hidden="true"></i>
                    <span class="visually-hidden">Pas de photo</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="col">
            <div><strong>Nom:</strong> <?= htmlspecialchars($user['full_name']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
            <div><strong>Rôle:</strong> <?= htmlspecialchars($user['role']) ?></div>
            <?php if (!empty($user['depot_nom'])): ?><div><strong>Dépôt:</strong> <?= htmlspecialchars($user['depot_nom']) ?></div><?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <h2>Mes droits (lecture seule)</h2>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
        <?php
        $rolePerms = getRolePermissions($_SESSION['user_role']);
        // collect effective perms including overrides
        $effective = [];
        foreach ($rolePerms as $p) $effective[$p] = true;
        try {
            $s = $db->prepare("SELECT permission, allowed FROM user_permissions WHERE user_id=?");
            $s->execute([$_SESSION['user_id']]);
            while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
                $effective[$r['permission']] = ((int)$r['allowed'] === 1);
            }
        } catch (Exception $e) {
        }
        ksort($effective);
        foreach ($effective as $perm => $ok):
            $cls = $ok ? 'badge-success' : 'badge-danger';
        ?>
            <span class="badge <?= $cls ?>" title="<?= $ok ? 'autorisé' : 'refusé' ?>" style="user-select:none;cursor:not-allowed;"><?= htmlspecialchars($perm) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Changer mon mot de passe</h2>
    <form method="post" class="row g-3">
        <input type="hidden" name="change_password" value="1" />
        <div class="col-md-6">
            <label class="form-label">Nouveau mot de passe</label>
            <input type="password" name="new_password" class="form-control" minlength="6" required />
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirmer</label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" required />
        </div>
        <div class="col-12">
            <button class="btn">Mettre à jour</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Changer ma photo</h2>
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="change_photo" value="1" />
        <div class="col-md-8">
            <input type="file" name="photo" class="form-control" accept="image/*" required />
        </div>
        <div class="col-md-4">
            <button class="btn w-100">Téléverser</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>