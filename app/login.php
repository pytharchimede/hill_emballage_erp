<?php
require_once 'includes/config.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $sql = "SELECT u.*, d.nom as depot_nom 
                    FROM users u
                    LEFT JOIN depots d ON u.depot_id = d.id 
                    WHERE (u.email = ? OR u.username = ?) AND u.is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $isValid = false;
            if ($user) {
                $stored = $user['password'];
                if (password_verify($password, $stored)) {
                    $isValid = true;
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $reh = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $reh->execute([$newHash, $user['id']]);
                    }
                } else {
                    $looksBcrypt = preg_match('/^\$2y\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', (string)$stored) === 1;
                    if (!$looksBcrypt) {
                        $isPlain = hash_equals((string)$stored, (string)$password);
                        $isMd5 = hash_equals((string)$stored, md5($password));
                        if ($isPlain || $isMd5) {
                            $isValid = true;
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $reh = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $reh->execute([$newHash, $user['id']]);
                        }
                    }
                }
            }

            if ($user && $isValid) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['depot_id'] = $user['depot_id'];
                $_SESSION['depot_name'] = $user['depot_nom'];

                // Mettre à jour la dernière connexion
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([$user['id']]);

                // Rediriger vers le dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
        }
    }
}

$pageTitle = 'Connexion - ' . APP_NAME;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-boxes"></i>
        </div>
        <h1 class="app-title">HILL EMBALLAGE</h1>
        <p class="app-subtitle">Système de Gestion Intégré</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email ou Nom d'utilisateur</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="Entrez votre email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required
                        placeholder="Entrez votre mot de passe">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

        <div class="demo-accounts">
            <h4><i class="fas fa-users"></i> Comptes de Démonstration</h4>

            <div class="demo-account">
                <div>
                    <span class="role">Administrateur</span><br>
                    <span class="credentials">admin@hillemballage.ci / admin123</span>
                </div>
                <button class="quick-login" data-email="admin@hillemballage.ci" data-password="admin123">
                    Connexion rapide
                </button>
            </div>

            <div class="demo-account">
                <div>
                    <span class="role">Vendeur</span><br>
                    <span class="credentials">vendeur@hillemballage.ci / vendeur123</span>
                </div>
                <button class="quick-login" data-email="vendeur@hillemballage.ci" data-password="vendeur123">
                    Connexion rapide
                </button>
            </div>

            <div class="demo-account">
                <div>
                    <span class="role">Livreur</span><br>
                    <span class="credentials">livreur@hillemballage.ci / livreur123</span>
                </div>
                <button class="quick-login" data-email="livreur@hillemballage.ci" data-password="livreur123">
                    Connexion rapide
                </button>
            </div>

            <div class="demo-account">
                <div>
                    <span class="role">Comptable</span><br>
                    <span class="credentials">comptable@hillemballage.ci / comptable123</span>
                </div>
                <button class="quick-login" data-email="comptable@hillemballage.ci" data-password="comptable123">
                    Connexion rapide
                </button>
            </div>
        </div>

        <div class="footer-info">
            <i class="fas fa-info-circle"></i>
            Application de gestion des dépôts-ventes et stock d'emballages
        </div>
    </div>

    <script src="<?= ASSETS_URL ?>/js/login.js"></script>
</body>

</html>