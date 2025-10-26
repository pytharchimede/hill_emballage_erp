<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <meta name="base-url" content="<?= BASE_URL ?>">

    <!-- FontAwesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Bootstrap CSS (pour modals/popups et composants) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/toast.css">

    <script src="<?= ASSETS_URL ?>/js/toast.js" defer></script>



    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .menu-toggle {
            background: transparent;
            border: 0;
            color: #333;
            font-size: 1.5rem;
            display: none;
            /* visible en mobile via media query */
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar .user-role {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: #333;
            font-weight: 500;
        }

        .badge-main-depot {
            background: #fff4cc;
            border: 1px solid #ffcc66;
            color: #7a5200;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .badge-depot {
            background: #eef7ff;
            border: 1px solid #99c9ff;
            color: #0b4d88;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .app-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 2rem 0;
        }

        .sidebar .menu-item {
            display: block;
            padding: 1rem 2rem;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar .menu-item:hover,
        .sidebar .menu-item.active {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-left-color: #FF8C00;
            color: #333;
        }

        .sidebar .menu-item i {
            width: 20px;
            margin-right: 1rem;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FFD700;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #2ed573 0%, #1e90ff 100%);
            color: white;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .role-admin .stat-card .icon {
            color: #ff6b6b;
        }

        .role-vendeur .stat-card .icon {
            color: #4834d4;
        }

        .role-livreur .stat-card .icon {
            color: #ff9ff3;
        }

        .role-comptable .stat-card .icon {
            color: #2ed573;
        }

        .menu-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
        }

        @media (max-width: 992px) {
            .navbar .menu-toggle {
                display: inline-flex;
            }

            .app-container {
                position: relative;
            }

            .sidebar {
                position: fixed;
                top: 64px;
                /* approx hauteur navbar */
                left: 0;
                height: calc(100vh - 64px);
                transform: translateX(-100%);
                transition: transform .25s ease;
                z-index: 1050;
                /* sous les modals (1070) */
                padding-top: 1rem;
            }

            body.menu-open .sidebar {
                transform: translateX(0%);
            }

            body.menu-open .menu-backdrop {
                display: block;
            }

            .main-content {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }

            .navbar {
                padding: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="role-<?= $_SESSION['user_role'] ?? 'guest' ?>">
    <!-- jQuery (certaines pages peuvent l'utiliser) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS (popups/modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <style>
        /* Fallback minimal si Bootstrap CSS n'est pas chargé: scoped pour éviter les conflits */
        body.no-bs-css .modal {
            display: none;
            position: fixed;
            z-index: 1070;
            /* au-dessus du backdrop fallback (1060) */
            inset: 0;
            width: 100%;
            height: 100%;
        }

        body.no-bs-css .modal.show {
            display: block;
        }

        body.no-bs-css .modal .modal-dialog {
            position: relative;
            margin: 1.75rem auto;
            max-width: 1000px;
        }

        body.no-bs-css .modal .modal-content {
            background: #fff;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
        }

        body.no-bs-css .btn-close {
            border: 0;
            background: transparent;
            width: 1em;
            height: 1em;
            opacity: .5;
        }
    </style>

    <script src="<?= ASSETS_URL ?>/js/fallback.js"></script>
    <script src="<?= ASSETS_URL ?>/js/menu.js"></script>
    <script src="<?= ASSETS_URL ?>/js/table2cards.js"></script>
    <script src="<?= ASSETS_URL ?>/js/app-base.js"></script>

    <?php if (isLoggedIn()): ?>
        <nav class="navbar">
            <button class="menu-toggle" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <i class="fas fa-boxes"></i> <?= APP_NAME ?>
            </div>
            <div class="user-info">
                <span class="user-role">
                    <i class="fas fa-user"></i> <?= ucfirst($_SESSION['user_role']) ?>
                </span>
                <?php
                $__depId = (int)($_SESSION['depot_id'] ?? 0);
                $__current = isLoggedIn() ? (getCurrentUser() ?: null) : null;
                $__depotNom = $__current['depot_nom'] ?? null;
                $__role = $_SESSION['user_role'] ?? '';
                $__isMain = ($__depId > 0) && isMainDepot($__depId);
                $__isRestricted = (!$__isMain) && in_array($__role, ['vendeur', 'comptable', 'livreur'], true);
                ?>
                <?php if ($__isMain): ?>
                    <span class="badge-main-depot" title="Accès global (dépôt principal)"><i class="fas fa-star"></i> Dépôt principal</span>
                <?php elseif ($__isRestricted && $__depotNom): ?>
                    <span class="badge-depot" title="Accès limité à ce dépôt"><i class="fas fa-location-dot"></i> Dépôt: <?= htmlspecialchars($__depotNom) ?></span>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/web_admin/profile.php" style="text-decoration:none;color:#333;">
                    <span><?= $_SESSION['user_name'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/web_admin/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </nav>

        <div class="app-container">
            <aside class="sidebar" id="sidebar" aria-label="Menu principal">
                <?php
                $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                $currentPage = basename($currentPath ?: ($_SERVER['PHP_SELF'] ?? ''));
                $currQueryStr = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
                $currQuery = [];
                if ($currQueryStr) parse_str($currQueryStr, $currQuery);
                $menu = getMenuForRole($_SESSION['user_role']);
                foreach ($menu as $key => $item):
                    $itemPath = parse_url($item['url'], PHP_URL_PATH);
                    $itemPage = basename($itemPath);
                    $itemQueryStr = parse_url($item['url'], PHP_URL_QUERY);
                    $itemQuery = [];
                    if ($itemQueryStr) parse_str($itemQueryStr, $itemQuery);
                    $isPathMatch = ($currentPage === $itemPage);
                    $isActive = '';
                    if ($isPathMatch) {
                        if (!empty($itemQuery)) {
                            // actif si toutes les paires de l'item sont présentes et égales dans la requête courante
                            $match = true;
                            foreach ($itemQuery as $k => $v) {
                                if (!isset($currQuery[$k]) || (string)$currQuery[$k] !== (string)$v) {
                                    $match = false;
                                    break;
                                }
                            }
                            if ($match) $isActive = 'active';
                        } else {
                            // sans query, actif seulement si aucune version plus spécifique n'est demandée (ex: mine=1)
                            if (!isset($currQuery['mine']) || (string)$currQuery['mine'] !== '1') {
                                $isActive = 'active';
                            }
                        }
                    }
                ?>
                    <a href="<?= $item['url'] ?>" class="menu-item <?= $isActive ?>">
                        <i class="<?= $item['icon'] ?>"></i>
                        <?= $item['title'] ?>
                    </a>
                <?php endforeach; ?>
            </aside>
            <div class="menu-backdrop" data-menu-backdrop></div>

            <main class="main-content">
                <?php
                $flashMessage = getFlashMessage();
                if ($flashMessage):
                ?>
                    <div class="alert alert-<?= $flashMessage['type'] ?>">
                        <?= htmlspecialchars($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>