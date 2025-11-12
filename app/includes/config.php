<?php

/**
 * Configuration et fonctions globales de l'application
 */

// Démarrage de la session (éviter l'avertissement Intelephense P1008 sur $_SESSION)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
/**
 * Astuce pour certains analyseurs statiques (Intelephense) qui signalent parfois
 * P1008 "Undefined variable $_SESSION" avant interception des superglobales.
 * On documente le type attendu sans réaffecter la superglobale.
 * @var array<string,mixed> $_SESSION
 */

// Définir des chemins/URLs de base pour réutilisation entre app/ et web_admin/
define('ROOT_PATH', realpath(__DIR__ . '/..')); // c:\wamp\www\hill\app
// Détecter la base URL (ex: /hill). On veut la racine du projet web (parent de /app et /web_admin)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptName = str_replace('\\', '/', $scriptName);
$scriptDir  = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if (preg_match('#^(.*?)/(web_admin|app)(?:/|$)#', $scriptName, $m)) {
    $baseUrl = rtrim($m[1], '/'); // ex: '', '/hill'
} else {
    $baseUrl = $scriptDir === '/' ? '' : $scriptDir;
}
define('BASE_URL', $baseUrl);
define('ASSETS_URL', BASE_URL . '/app/assets');

// URL absolue (schéma + host) pour les liens partageables (WhatsApp/SMS, emails, etc.)
// Détecte aussi les en-têtes de reverse proxy courants.
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || $forwardedProto === 'https';
$scheme = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
// Construire l'URL absolue du site (ex: https://app.hillemballage.ci[/base])
define('ORIGIN_URL', $scheme . '://' . $host);
define('SITE_URL', rtrim(ORIGIN_URL . BASE_URL, '/'));

// Secret léger pour signer des liens de localisation (changez en PROD)
if (!defined('LINK_SIGN_SECRET')) {
    define('LINK_SIGN_SECRET', 'change-me-please');
}

// Inclusion robuste de la configuration base de données
$dbConfigPath = realpath(ROOT_PATH . '/../backend/config/database.php');
if ($dbConfigPath && file_exists($dbConfigPath)) {
    require_once $dbConfigPath;
} else {
    // Fallback absolu basé sur le chemin du fichier courant
    require_once dirname(__DIR__, 2) . '/backend/config/database.php';
}

// Configuration de l'application
define('APP_NAME', 'HILL EMBALLAGE');
define('APP_VERSION', '1.0.0');

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Fonctions utilitaires
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($allowedRoles)
{
    requireLogin();
    /** @var array<string,mixed> $_SESSION */
    if (!in_array($GLOBALS['_SESSION']['user_role'] ?? null, $allowedRoles)) {
        // Message et redirection propre si rôle non autorisé
        setFlashMessage('warning', "Accès refusé pour votre rôle.");
        header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
        exit();
    }
}

function getCurrentUser()
{
    global $db;
    if (!isLoggedIn()) return null;

    $sql = "SELECT u.*, d.nom as depot_nom FROM users u 
            LEFT JOIN depots d ON u.depot_id = d.id 
            WHERE u.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$GLOBALS['_SESSION']['user_id'] ?? null]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Détermine si un dépôt est le dépôt principal (accès global).
 * Priorité:
 * - Si la colonne depots.is_principal existe: utiliser sa valeur (1 = principal)
 * - Sinon, si depots.is_main existe: utiliser sa valeur
 * - Sinon, fallback convention: id = 1 est considéré comme principal
 */
function isMainDepot($depotId)
{
    global $db;
    try {
        $depotId = (int)$depotId;
        if ($depotId <= 0) return false;
        // Détecter colonne marqueur
        $col = null;
        foreach (['is_principal', 'is_main', 'principal'] as $candidate) {
            $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='depots' AND COLUMN_NAME=?");
            $q->execute([$candidate]);
            if ((int)$q->fetchColumn() > 0) {
                $col = $candidate;
                break;
            }
        }
        if ($col) {
            $s = $db->prepare("SELECT $col FROM depots WHERE id=?");
            $s->execute([$depotId]);
            $val = $s->fetch(PDO::FETCH_NUM);
            if ($val !== false) return ((int)$val[0]) === 1;
        }
        // Fallback: considérer id=1 comme principal
        return $depotId === 1;
    } catch (Exception $e) {
        return false;
    }
}

function formatMoney($amount)
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($date)
{
    return date('d/m/Y H:i', strtotime($date));
}

// --- Traçabilité: utilitaires ---
function clientIp()
{
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    // si X_FORWARDED_FOR contient plusieurs IP, prendre la première
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return $ip;
}

function clientPort()
{
    return isset($_SERVER['REMOTE_PORT']) ? (int)$_SERVER['REMOTE_PORT'] : null;
}

function clientUserAgent()
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Journaliser une action utilisateur.
 * @param string $action ex: VIEW, CREATE, UPDATE, DELETE, VALIDATE, LOGIN, LOGOUT
 * @param string|null $entity ex: produits, clients, ventes, payments
 * @param int|null $entityId identifiant de l'entité
 * @param array|string|null $details données additionnelles (stockées en texte)
 */
function log_action($action, $entity = null, $entityId = null, $details = null)
{
    global $db;
    try {
        // Vérifier si la table existe (au cas où migration pas encore faite)
        $check = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs'");
        $check->execute();
        if ((int)$check->fetchColumn() === 0) return; // silencieux

        $userId = $_SESSION['user_id'] ?? null;
        if (is_array($details)) {
            // Sans JSON_FORCE_OBJECT ici; on veut conserver tableaux
            $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip, user_agent, port, created_at)
            VALUES (?,?,?,?,?,?,?,?, NOW())");
        $stmt->execute([
            $userId,
            substr((string)$action, 0, 50),
            $entity ? substr((string)$entity, 0, 50) : null,
            $entityId !== null ? (int)$entityId : null,
            $details,
            substr(clientIp(), 0, 45),
            substr(clientUserAgent(), 0, 255),
            clientPort()
        ]);
    } catch (Exception $e) {
        // ne pas interrompre le flux applicatif
    }
}

function normalizeRole($role)
{
    // Unifier uniquement l'ancien profil 'vendeur' vers 'commercial'.
    // On garde désormais 'livreur' distinct afin de lui attribuer des droits spécifiques (deliveries_update).
    if ($role === 'vendeur') return 'commercial';
    return $role;
}

function roleLabel($role)
{
    $norm = normalizeRole($role);
    $labels = [
        'admin' => 'Administrateur',
        'gerant' => 'Gérant',
        'commercial' => 'Commercial',
        'livreur' => 'Livreur',
        'comptable' => 'Comptable',
    ];
    // Afficher le label original si distinct (ex: livreur) sinon label normalisé
    return $labels[$role] ?? ($labels[$norm] ?? ucfirst($norm));
}

function getMenuForRole($role)
{
    $role = normalizeRole($role);
    $menus = [
        'admin' => [
            'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'title' => 'Tableau de bord', 'url' => BASE_URL . '/web_admin/dashboard.php'],
            'activity' => ['icon' => 'fas fa-history', 'title' => 'Activité', 'url' => BASE_URL . '/web_admin/activity.php'],
            'depots' => ['icon' => 'fas fa-warehouse', 'title' => 'Dépôts', 'url' => BASE_URL . '/web_admin/depots.php'],
            'depots_map' => ['icon' => 'fas fa-map-location-dot', 'title' => 'Carte des dépôts', 'url' => BASE_URL . '/web_admin/depots_map.php'],
            'clients' => ['icon' => 'fas fa-users', 'title' => 'Clients', 'url' => BASE_URL . '/web_admin/clients.php'],
            'products' => ['icon' => 'fas fa-box', 'title' => 'Produits', 'url' => BASE_URL . '/web_admin/products.php'],
            'sales' => ['icon' => 'fas fa-shopping-cart', 'title' => 'Ventes', 'url' => BASE_URL . '/app/sales.php'],
            'stock' => ['icon' => 'fas fa-warehouse', 'title' => 'Stock', 'url' => BASE_URL . '/web_admin/stock.php'],
            'stock_entries' => ['icon' => 'fas fa-plus-square', 'title' => 'Entrées de stock', 'url' => BASE_URL . '/web_admin/stock_entries.php'],
            'stock_adjustments' => ['icon' => 'fas fa-clipboard-check', 'title' => "Ajustements d'inventaire", 'url' => BASE_URL . '/web_admin/stock_adjustments.php'],
            'payments' => ['icon' => 'fas fa-credit-card', 'title' => 'Paiements', 'url' => BASE_URL . '/web_admin/payments.php'],
            'reports' => ['icon' => 'fas fa-chart-bar', 'title' => 'Rapports', 'url' => BASE_URL . '/web_admin/reports.php'],
            'livreur_state' => ['icon' => 'fas fa-user-check', 'title' => 'Etat Livreur (J)', 'url' => BASE_URL . '/app/livreur_state.php'],
            'depot_state' => ['icon' => 'fas fa-warehouse', 'title' => 'Etat Dépôt (J)', 'url' => BASE_URL . '/app/depot_state.php'],
            'system_state' => ['icon' => 'fas fa-sitemap', 'title' => 'Etat Général', 'url' => BASE_URL . '/app/system_state.php'],
            'assignments' => ['icon' => 'fas fa-route', 'title' => 'Distributions', 'url' => BASE_URL . '/app/assignments.php'],
            'vendor_balance' => ['icon' => 'fas fa-scale-balanced', 'title' => 'Soldes Commerciaux', 'url' => BASE_URL . '/app/vendor_balance.php'],
            'users' => ['icon' => 'fas fa-user-cog', 'title' => 'Utilisateurs', 'url' => BASE_URL . '/web_admin/users.php'],
            'profile' => ['icon' => 'fas fa-user-circle', 'title' => 'Mon profil', 'url' => BASE_URL . '/web_admin/profile.php'],
        ],
        'gerant' => [
            'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'title' => 'Tableau de bord', 'url' => BASE_URL . '/web_admin/dashboard.php'],
            'depots' => ['icon' => 'fas fa-warehouse', 'title' => 'Dépôts', 'url' => BASE_URL . '/web_admin/depots.php'],
            'stock' => ['icon' => 'fas fa-warehouse', 'title' => 'Stock', 'url' => BASE_URL . '/web_admin/stock.php'],
            'stock_entries' => ['icon' => 'fas fa-plus-square', 'title' => 'Entrées de stock', 'url' => BASE_URL . '/web_admin/stock_entries.php'],
            'stock_adjustments' => ['icon' => 'fas fa-clipboard-check', 'title' => "Ajustements d'inventaire", 'url' => BASE_URL . '/web_admin/stock_adjustments.php'],
            'livreur_state' => ['icon' => 'fas fa-user-check', 'title' => 'Etat Livreur (J)', 'url' => BASE_URL . '/app/livreur_state.php'],
            'depot_state' => ['icon' => 'fas fa-warehouse', 'title' => 'Etat Dépôt (J)', 'url' => BASE_URL . '/app/depot_state.php'],
            'system_state' => ['icon' => 'fas fa-sitemap', 'title' => 'Etat Général', 'url' => BASE_URL . '/app/system_state.php'],
            'assignments' => ['icon' => 'fas fa-route', 'title' => 'Distributions', 'url' => BASE_URL . '/app/assignments.php'],
            'vendor_balance' => ['icon' => 'fas fa-scale-balanced', 'title' => 'Soldes Commerciaux', 'url' => BASE_URL . '/app/vendor_balance.php'],
            'profile' => ['icon' => 'fas fa-user-circle', 'title' => 'Mon profil', 'url' => BASE_URL . '/web_admin/profile.php'],
        ],
        'commercial' => [
            'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'title' => 'Tableau de bord', 'url' => BASE_URL . '/web_admin/vendeur.php'],
            'clients' => ['icon' => 'fas fa-users', 'title' => 'Clients', 'url' => BASE_URL . '/web_admin/clients.php'],
            'sales' => ['icon' => 'fas fa-shopping-cart', 'title' => 'Ventes', 'url' => BASE_URL . '/app/sales.php'],
            'quick_sale' => ['icon' => 'fas fa-bolt', 'title' => 'Vente rapide', 'url' => BASE_URL . '/app/quick_sale.php'],
            'stock_view' => ['icon' => 'fas fa-eye', 'title' => 'Consulter Stock', 'url' => BASE_URL . '/web_admin/stock.php'],
            'livreur_state' => ['icon' => 'fas fa-user-check', 'title' => 'Mon état journalier', 'url' => BASE_URL . '/app/livreur_state.php'],
            'assignments' => ['icon' => 'fas fa-route', 'title' => 'Mes distributions', 'url' => BASE_URL . '/app/assignments.php'],
            'vendor_balance' => ['icon' => 'fas fa-scale-balanced', 'title' => 'Mon solde', 'url' => BASE_URL . '/app/vendor_balance.php'],
            'profile' => ['icon' => 'fas fa-user-circle', 'title' => 'Mon profil', 'url' => BASE_URL . '/web_admin/profile.php'],
        ],
        'livreur' => [
            'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'title' => 'Tableau de bord', 'url' => BASE_URL . '/web_admin/livreur.php'],
            'clients' => ['icon' => 'fas fa-users', 'title' => 'Clients', 'url' => BASE_URL . '/web_admin/clients.php'],
            'sales' => ['icon' => 'fas fa-shopping-cart', 'title' => 'Ventes', 'url' => BASE_URL . '/app/sales.php'],
            'quick_sale' => ['icon' => 'fas fa-bolt', 'title' => 'Vente rapide', 'url' => BASE_URL . '/app/quick_sale.php'],
            'assignments' => ['icon' => 'fas fa-route', 'title' => 'Distributions', 'url' => BASE_URL . '/app/assignments.php'],
            'stock_view' => ['icon' => 'fas fa-eye', 'title' => 'Consulter Stock', 'url' => BASE_URL . '/web_admin/stock.php'],
            'deliveries' => ['icon' => 'fas fa-truck', 'title' => 'Livraisons', 'url' => BASE_URL . '/web_admin/deliveries.php'],
            'livreur_state' => ['icon' => 'fas fa-user-check', 'title' => 'Mon état journalier', 'url' => BASE_URL . '/app/livreur_state.php'],
            'profile' => ['icon' => 'fas fa-user-circle', 'title' => 'Mon profil', 'url' => BASE_URL . '/web_admin/profile.php'],
        ],
        'comptable' => [
            'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'title' => 'Tableau de bord', 'url' => BASE_URL . '/web_admin/comptable.php'],
            'payments' => ['icon' => 'fas fa-credit-card', 'title' => 'Paiements', 'url' => BASE_URL . '/app/payments.php'],
            'reports' => ['icon' => 'fas fa-chart-bar', 'title' => 'Rapports', 'url' => BASE_URL . '/app/reports.php'],
            'credits' => ['icon' => 'fas fa-clock', 'title' => 'Crédits', 'url' => BASE_URL . '/app/credits.php'],
            'invoices' => ['icon' => 'fas fa-file-invoice', 'title' => 'Factures', 'url' => BASE_URL . '/app/invoices.php'],
            'livreur_state' => ['icon' => 'fas fa-user-check', 'title' => 'Etat Livreur (J)', 'url' => BASE_URL . '/app/livreur_state.php'],
            'depot_state' => ['icon' => 'fas fa-warehouse', 'title' => 'Etat Dépôt (J)', 'url' => BASE_URL . '/app/depot_state.php'],
            'system_state' => ['icon' => 'fas fa-sitemap', 'title' => 'Etat Général', 'url' => BASE_URL . '/app/system_state.php'],
            'clients_view' => ['icon' => 'fas fa-users', 'title' => 'Clients', 'url' => BASE_URL . '/web_admin/clients.php'],
            'my_activity' => ['icon' => 'fas fa-user-clock', 'title' => 'Mon activité', 'url' => BASE_URL . '/web_admin/my-activity.php'],
            'profile' => ['icon' => 'fas fa-user-circle', 'title' => 'Mon profil', 'url' => BASE_URL . '/web_admin/profile.php'],
        ]
    ];

    return $menus[$role] ?? [];
}

function getRolePermissions($role)
{
    $role = normalizeRole($role);
    $permissions = [
        'admin' => [
            'read',
            'write',
            'delete',
            'manage_users',
            'view_reports',
            // Dépôts
            'depots_read',
            'depots_create',
            'depots_update',
            'depots_delete',
            'clients_read',
            'clients_create',
            'clients_update',
            'clients_delete',
            'sales_read',
            'sales_create',
            'sales_update',
            'sales_delete',
            'stock_read',
            'stock_update',
            'payments_read',
            'payments_create',
            'payments_update',
            'products_read',
            'products_create',
            'products_update',
            'products_delete',
            'assignments_manage',
            'assignments_view'
        ],
        'gerant' => [
            'read',
            'view_reports',
            'depots_read',
            'depots_create',
            'depots_update',
            'depots_delete',
            'clients_read',
            'clients_create',
            'clients_update',
            'sales_read',
            'sales_create',
            'sales_update',
            'stock_read',
            'stock_update',
            'products_read',
            'products_update',
            'assignments_manage',
            'assignments_view'
        ],
        'commercial' => [
            'read',
            'clients_read',
            'clients_create',
            'clients_update',
            'sales_read',
            'sales_create',
            'deliveries_update',
            'stock_read',
            'products_read',
            'assignments_view'
        ],
        // Nouveau: rôle livreur séparé (ne doit PAS avoir les droits vendeur étendus)
        'livreur' => [
            'read',
            'clients_read',        // Voir fiches clients nécessaires aux livraisons
            'sales_read',          // Voir ventes à livrer
            'sales_create',        // Créer une vente directe (si logique terrain)
            'stock_read',          // Consulter stock affecté
            'assignments_view',    // Voir ses distributions éventuelles
            'deliveries_update',   // Confirmer livraisons / joindre pièces
            'payments_read',       // Voir paiements liés aux ventes livrées
        ],
        'comptable' => [
            'read',
            'write_payments',
            'view_reports',
            'read_credits',
            'payments_read',
            'payments_create',
            'payments_update',
            'clients_read',
            'sales_read',
            'products_read'
        ]
    ];

    return $permissions[$role] ?? [];
}

function hasPermission($permission)
{
    if (!isLoggedIn()) return false;
    static $overrideCache = null;
    global $db;
    // Charger les overrides de l'utilisateur une seule fois
    if ($overrideCache === null) {
        $overrideCache = [];
        try {
            $stmt = $db->prepare("SELECT permission, allowed FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$GLOBALS['_SESSION']['user_id'] ?? 0]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $overrideCache[$row['permission']] = (int)$row['allowed']; // 1 autorise, 0 refuse
            }
        } catch (Exception $e) {
            // si la table n'existe pas encore, ignorer et retomber sur les droits du rôle
        }
    }
    // Si override explicite existe, il prime (1 autorise, 0 refuse)
    if (array_key_exists($permission, $overrideCache)) {
        return $overrideCache[$permission] === 1;
    }
    // Sinon, retomber sur les permissions du rôle
    $rawRole = $GLOBALS['_SESSION']['user_role'] ?? '';
    $rolePerms = getRolePermissions(strtolower($rawRole));
    return in_array($permission, $rolePerms, true);
}

// Messages flash
function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Statistiques rapides pour le dashboard
function getDashboardStats($role, $userId = null)
{
    global $db;

    $stats = [];

    try {
        switch ($role) {
            case 'admin':
                // Ventes du jour
                $sql = "SELECT COALESCE(SUM(montant_total), 0) as total FROM ventes WHERE DATE(created_at) = CURDATE()";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['sales_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Nombre de clients
                $sql = "SELECT COUNT(*) as total FROM clients WHERE is_active = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['clients_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Stock critique
                $sql = "SELECT COUNT(*) as total FROM stock s JOIN produits p ON s.produit_id = p.id 
                        WHERE s.quantite <= s.seuill_alerte AND p.is_active = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Paiements en attente
                $sql = "SELECT COUNT(*) as total FROM payments WHERE statut = 'attente'";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                break;

            case 'vendeur':
                // Mes ventes du jour
                $sql = "SELECT COALESCE(SUM(montant_total), 0) as total FROM ventes 
                        WHERE user_id = ? AND DATE(created_at) = CURDATE()";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $stats['my_sales_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Mes clients
                $sql = "SELECT COUNT(DISTINCT client_id) as total FROM ventes WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $stats['my_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                break;

            case 'livreur':
                // Livraisons en attente
                $sql = "SELECT COUNT(*) as total FROM ventes WHERE statut = 'validee'";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['pending_deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Livraisons du jour
                $sql = "SELECT COUNT(*) as total FROM ventes WHERE statut = 'livree' AND DATE(updated_at) = CURDATE()";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['deliveries_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                break;

            case 'comptable':
                // Montant à encaisser
                $sql = "SELECT COALESCE(SUM(montant_total - montant_paye), 0) as total FROM ventes 
                        WHERE type_vente = 'credit' AND montant_paye < montant_total";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['amount_to_collect'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Paiements du jour
                $sql = "SELECT COALESCE(SUM(montant), 0) as total FROM payments WHERE DATE(created_at) = CURDATE()";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $stats['payments_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                break;
        }
    } catch (Exception $e) {
        // En cas d'erreur, retourner des valeurs par défaut
    }

    return $stats;
}
