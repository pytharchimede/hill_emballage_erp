<?php
// Login dynamique pour web_admin, réutilise la logique de app/login.php
require_once __DIR__ . '/../app/includes/config.php';

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
                // 1) Vérif standard (bcrypt)
                if (password_verify($password, $stored)) {
                    $isValid = true;
                    // Rehash si l'algo par défaut a changé ou coût différent
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $reh = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $reh->execute([$newHash, $user['id']]);
                    }
                } else {
                    // 2) Compat héritée: mot de passe stocké en clair ou MD5
                    $looksBcrypt = preg_match('/^\$2y\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', (string)$stored) === 1;
                    if (!$looksBcrypt) {
                        $isPlain = hash_equals((string)$stored, (string)$password);
                        $isMd5 = hash_equals((string)$stored, md5($password));
                        if ($isPlain || $isMd5) {
                            $isValid = true;
                            // Migrer vers bcrypt immédiatement
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $reh = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $reh->execute([$newHash, $user['id']]);
                        }
                    }
                }
            }

            if ($user && $isValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['depot_id'] = $user['depot_id'];
                $_SESSION['depot_name'] = $user['depot_nom'];

                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([$user['id']]);

                // Log audit LOGIN
                log_action('LOGIN', null, null, ['email' => $email, 'status' => 'success']);

                // Redirection selon le rôle
                $destinations = [
                    'admin' => 'dashboard.php',
                    'vendeur' => 'vendeur.php',
                    'livreur' => 'livreur.php',
                    'comptable' => 'comptable.php',
                ];
                $dest = $destinations[$_SESSION['user_role']] ?? 'dashboard.php';
                header('Location: ' . $dest);
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
                // Log audit échec LOGIN
                log_action('LOGIN', null, null, ['email' => $email, 'status' => 'failed']);
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
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <style>
        body {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: #fff;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            font-size: 3rem;
            color: #FFD700;
            margin-bottom: 1rem;
        }

        .app-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .app-subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .form-group {
            text-align: left;
            margin-bottom: 1.25rem;
        }

        .form-group .input-icon {
            position: relative;
        }

        .form-group .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.4rem;
        }

        .form-group .input-icon input {
            width: 100%;
            height: 48px;
            padding-left: 3rem;
            padding-right: 3rem;
            border: 1px solid #e2e2e2;
            border-radius: 12px;
            background: #f9fafb;
            color: #222;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .02);
        }

        .form-group .input-icon input::placeholder {
            color: #a3a3a3;
        }

        .form-group .input-icon input:focus {
            background: #fff;
            border-color: #FFA500;
            box-shadow: 0 0 0 3px rgba(255, 165, 0, .2);
        }

        .form-group .input-icon:focus-within i {
            color: #FFA500;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #333;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #333;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }

        .demo-accounts {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
            text-align: left;
        }

        .demo-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .demo-account:last-child {
            border-bottom: none;
        }

        .quick-login {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
            font-size: 0.8rem;
        }
    </style>
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

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="email">Email ou Nom d'utilisateur</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="email" name="email" required autocomplete="username" spellcheck="false" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Entrez votre email ou nom d'utilisateur">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Entrez votre mot de passe">
                    <button type="button" class="toggle-password" aria-label="Afficher ou masquer le mot de passe" title="Afficher/Masquer">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

    </div>

    <script src="<?= ASSETS_URL ?>/js/login.js"></script>
</body>

</html>