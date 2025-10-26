<?php
require_once 'includes/config.php';
require_once 'includes/migrations.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Vérifier les permissions
if (!hasPermission('clients_read')) {
    header('Location: dashboard.php');
    exit();
}

$userRole = $_SESSION['user_role'];
$depotId = $_SESSION['depot_id'];

// S'assurer que la colonne d'affectation existe
ensureClientsLivreurColumn($db);

// Helpers de compatibilité de schéma
function columnExists(PDO $db, $table, $column)
{
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

// Détection robuste du nom de colonne rôle dans users (role, user_role, profil, type, fonction)
function detectUserRoleColumn(PDO $db)
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $candidates = ['role', 'user_role', 'profil', 'type', 'fonction'];
    foreach ($candidates as $col) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
            $stmt->execute([$col]);
            if ((int)$stmt->fetchColumn() > 0) {
                $cache = $col;
                return $cache;
            }
        } catch (Exception $e) {
        }
    }
    $cache = null;
    return $cache;
}

// Traitement des actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && hasPermission('clients_create')) {
        // Création d'un nouveau client (schéma adaptatif)
        try {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $company = trim($_POST['company'] ?? '');
            $client_type = $_POST['client_type'] ?? 'particulier';

            // Affectation au livreur: par défaut, si le créateur est un livreur, lier au créateur
            $livreurAssign = null;
            $creatorRole = $_SESSION['user_role'] ?? '';
            if (in_array($creatorRole, ['livreur', 'commercial'], true)) {
                $livreurAssign = (int)($_SESSION['user_id'] ?? 0) ?: null;
            } else {
                // Autoriser l'admin ou vendeur à choisir un livreur depuis le formulaire
                if (isset($_POST['livreur_id']) && $_POST['livreur_id'] !== '') {
                    $livreurAssign = (int)$_POST['livreur_id'];
                }
            }

            if (columnExists($db, 'clients', 'code_client')) {
                // Schéma backend/migrations (clients avancés)
                $code = 'CLT-' . strtoupper(dechex(time()));
                $cols = "code_client, nom, prenom, telephone, email, adresse, zone, type_client, credit_limite, solde_credit, points_fidelite, qr_code, photo_url, is_active, created_by";
                $vals = "?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, NULL, NULL, 1, ?";
                if (columnExists($db, 'clients', 'livreur_id')) {
                    $cols .= ", livreur_id";
                    $vals .= ", ?";
                }
                $sql = "INSERT INTO clients ($cols) VALUES ($vals)";
                $stmt = $db->prepare($sql);
                $params = [$code, ($name ?: $company ?: 'Client'), '', $phone, $email, $address, $city, $client_type, $_SESSION['user_id']];
                if (columnExists($db, 'clients', 'livreur_id')) {
                    $params[] = $livreurAssign;
                }
                $stmt->execute($params);
            } else {
                // Schéma legacy (/migrations)
                $cols = "nom, prenom, entreprise, telephone, email, adresse, type_client, limite_credit, solde_credit, points_fidelite, is_active";
                $vals = "?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 1";
                if (columnExists($db, 'clients', 'livreur_id')) {
                    $cols .= ", livreur_id";
                    $vals .= ", ?";
                }
                $sql = "INSERT INTO clients ($cols) VALUES ($vals)";
                $stmt = $db->prepare($sql);
                $prenom = '';
                $params = [($name ?: $company ?: 'Client'), $prenom, $company, $phone, $email, $address, $client_type];
                if (columnExists($db, 'clients', 'livreur_id')) {
                    $params[] = $livreurAssign;
                }
                $stmt->execute($params);
            }

            $message = 'Client créé avec succès !';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Erreur lors de la création du client : ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update' && hasPermission('clients_update')) {
        // Mise à jour d'un client (schéma adaptatif)
        try {
            $id = $_POST['id'];
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $company = trim($_POST['company'] ?? '');
            $client_type = $_POST['client_type'] ?? 'particulier';

            // Gestion d'affectation livreur (droits):
            // - admin: peut affecter à n'importe quel livreur
            // - vendeur: peut affecter uniquement aux livreurs de son dépôt
            // - livreur: ne peut pas changer l'affectation
            $livreurAssign = null;
            $role = $_SESSION['user_role'] ?? '';
            if (isset($_POST['livreur_id']) && $_POST['livreur_id'] !== '' && columnExists($db, 'clients', 'livreur_id')) {
                $candidate = (int)$_POST['livreur_id'];
                $allow = false;
                if ($role === 'admin') {
                    $allow = true;
                } elseif ($role === 'vendeur') {
                    // Vérifier le dépôt du livreur candidat = dépôt du vendeur
                    try {
                        $stc = $db->prepare("SELECT depot_id FROM users WHERE id=?");
                        $stc->execute([$candidate]);
                        $row = $stc->fetch(PDO::FETCH_ASSOC);
                        if ($row && (int)$row['depot_id'] === (int)($_SESSION['depot_id'] ?? 0)) $allow = true;
                    } catch (Exception $e) {
                    }
                }
                if ($allow) {
                    $livreurAssign = $candidate;
                }
            }

            if (columnExists($db, 'clients', 'name')) {
                $sql = "UPDATE clients SET name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, 
                        company = ?, client_type = ?, updated_at = NOW()";
                $params = [$name, $email, $phone, $address, $city, $postal_code, $company, $client_type];
                if (columnExists($db, 'clients', 'livreur_id') && $livreurAssign !== null) {
                    $sql .= ", livreur_id = ?";
                    $params[] = $livreurAssign;
                }
                $sql .= " WHERE id = ?";
                $params[] = $id;
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            } else {
                // Schéma legacy
                $sql = "UPDATE clients SET nom = ?, prenom = ?, entreprise = ?, telephone = ?, email = ?, adresse = ?, type_client = ?, updated_at = NOW()";
                $prenom = '';
                $params = [($name ?: $company ?: 'Client'), $prenom, $company, $phone, $email, $address, $client_type];
                if (columnExists($db, 'clients', 'livreur_id') && $livreurAssign !== null) {
                    $sql .= ", livreur_id = ?";
                    $params[] = $livreurAssign;
                }
                $sql .= " WHERE id = ?";
                $params[] = $id;
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }

            $message = 'Client mis à jour avec succès !';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Erreur lors de la mise à jour : ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'delete' && hasPermission('clients_delete')) {
        // Suppression d'un client
        try {
            $id = $_POST['id'];
            if (columnExists($db, 'clients', 'is_active')) {
                $sql = "UPDATE clients SET is_active = 0 WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id]);
            } else {
                $sql = "DELETE FROM clients WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id]);
            }

            $message = 'Client supprimé avec succès !';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Erreur lors de la suppression : ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    $bulkAction = $_POST['bulk_action'] ?? '';
    if ((($action === 'bulk_assign') || ($bulkAction === 'bulk_assign')) && hasPermission('clients_update')) {
        try {
            if (!columnExists($db, 'clients', 'livreur_id')) {
                throw new Exception("Fonctionnalité non disponible: colonne 'livreur_id' absente");
            }
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['admin', 'vendeur'], true)) {
                throw new Exception('Non autorisé');
            }
            $livreurId = (int)($_POST['livreur_id'] ?? 0);
            $ids = array_filter(array_map('intval', $_POST['client_ids'] ?? []));
            if ($livreurId <= 0 || empty($ids)) {
                throw new Exception('Sélection ou livreur invalide');
            }
            // Si vendeur, vérifier que le livreur appartient à son dépôt
            if ($role === 'vendeur') {
                $stc = $db->prepare("SELECT depot_id FROM users WHERE id=?");
                $stc->execute([$livreurId]);
                $row = $stc->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['depot_id'] !== (int)($_SESSION['depot_id'] ?? 0)) {
                    throw new Exception("Livreur hors de votre dépôt");
                }
            }
            // Construire l'update
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE clients SET livreur_id = ? WHERE id IN ($in)";
            $params = array_merge([$livreurId], $ids);
            // Si vendeur et colonne depot présente, ne mettre à jour que les clients de son dépôt
            if (columnExists($db, 'clients', 'depot_id') && $role === 'vendeur') {
                $sql .= " AND depot_id = ?";
                $params[] = (int)($_SESSION['depot_id'] ?? 0);
            }
            $st = $db->prepare($sql);
            $st->execute($params);
            $count = $st->rowCount();
            $message = $count . ' client(s) affecté(s) avec succès';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Affectation en masse échouée: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Récupérer les clients
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$affect = $_GET['affect'] ?? 'all';
$livreurFilter = isset($_GET['livreur']) ? (int)$_GET['livreur'] : 0;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = columnExists($db, 'clients', 'is_active') ? "WHERE c.is_active = 1" : "WHERE 1=1";
$params = [];

// Filtrage par rôle et visibilité portefeuille
$hasClientDepot = columnExists($db, 'clients', 'depot_id');
if ($userRole === 'livreur' || $userRole === 'commercial') {
    if (columnExists($db, 'clients', 'livreur_id')) {
        $whereClause .= " AND c.livreur_id = ?";
        $params[] = (int)($_SESSION['user_id'] ?? 0);
    } else if (columnExists($db, 'ventes', 'livreur_id')) {
        // fallback: clients qui ont des ventes par ce livreur
        $whereClause .= " AND c.id IN (SELECT DISTINCT v.client_id FROM ventes v WHERE v.livreur_id = ?)";
        $params[] = (int)($_SESSION['user_id'] ?? 0);
    }
} elseif ($userRole === 'vendeur') {
    // Vendeur: clients des livreurs de son dépôt (si livreur_id dispo) sinon dépôt
    if (columnExists($db, 'clients', 'livreur_id')) {
        if ($hasClientDepot) {
            $whereClause .= " AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR (c.livreur_id IS NULL AND c.depot_id = ?))";
            $params[] = $depotId;
            $params[] = $depotId;
        } else {
            $whereClause .= " AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR c.livreur_id IS NULL)";
            $params[] = $depotId;
        }
    } elseif (columnExists($db, 'clients', 'depot_id')) {
        $whereClause .= " AND c.depot_id = ?";
        $params[] = $depotId;
    }
} elseif ($userRole === 'comptable') {
    // Comptable: même règle que vendeur, sauf si dépôt principal (accès global)
    if ($depotId > 0 && !isMainDepot((int)$depotId)) {
        if (columnExists($db, 'clients', 'livreur_id')) {
            if ($hasClientDepot) {
                $whereClause .= " AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR (c.livreur_id IS NULL AND c.depot_id = ?))";
                $params[] = $depotId;
                $params[] = $depotId;
            } else {
                $whereClause .= " AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR c.livreur_id IS NULL)";
                $params[] = $depotId;
            }
        } elseif (columnExists($db, 'clients', 'depot_id')) {
            $whereClause .= " AND c.depot_id = ?";
            $params[] = $depotId;
        }
    }
} else {
    // Admin: aucune restriction supplémentaire
}

// Filtre "Sans livreur"
if ($affect === 'none' && columnExists($db, 'clients', 'livreur_id')) {
    $whereClause .= " AND c.livreur_id IS NULL";
}

// Filtre par livreur précis (admin/vendeur)
if ($livreurFilter > 0 && in_array($userRole, ['admin', 'vendeur'], true) && columnExists($db, 'clients', 'livreur_id')) {
    $whereClause .= " AND c.livreur_id = ?";
    $params[] = $livreurFilter;
}

// Recherche
if (!empty($search)) {
    if (columnExists($db, 'clients', 'name')) {
        $whereClause .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    } else {
        // Legacy
        $whereClause .= " AND (c.nom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ? OR c.entreprise LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
}

// Filtre par type
if ($filter !== 'all') {
    if (columnExists($db, 'clients', 'client_type')) {
        $whereClause .= " AND c.client_type = ?";
        $params[] = $filter;
    } else {
        $whereClause .= " AND c.type_client = ?";
        $params[] = $filter;
    }
}

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM clients c $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalClients = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les clients (joins optionnels selon le schéma)
$joins = '';
if (columnExists($db, 'clients', 'depot_id')) {
    $joins .= ' LEFT JOIN depots d ON c.depot_id = d.id ';
}
if (columnExists($db, 'clients', 'created_by')) {
    $joins .= ' LEFT JOIN users u ON c.created_by = u.id ';
}

if (columnExists($db, 'clients', 'livreur_id')) {
    $joins .= ' LEFT JOIN users lvr ON c.livreur_id = lvr.id ';
}

$sql = "SELECT c.*"
    . (strpos($joins, 'depots') !== false ? ", d.nom as depot_nom" : '')
    . (strpos($joins, 'users u') !== false ? ", u.full_name as created_by_name" : '')
    . (strpos($joins, 'users lvr') !== false ? ", lvr.full_name as livreur_nom" : '')
    .
    " FROM clients c " . $joins .
    " $whereClause ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Détecter les colonnes optionnelles pour l'affichage (type et dépôt)
$hasClientType = columnExists($db, 'clients', 'client_type') || columnExists($db, 'clients', 'type_client');
$hasDepotJoin = strpos($joins, 'depots') !== false;

$totalPages = ceil($totalClients / $limit);

$pageTitle = 'Gestion des Clients';
include 'includes/header.php';
?>

<div class="clients-page">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Gestion des Clients</h1>
        <?php if (hasPermission('clients_create')): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="fas fa-plus"></i> Nouveau Client
            </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group" style="min-width:280px;flex:2">
                <label for="filter" class="form-label">Saisissez votre requête</label>
                <input type="text" name="search" placeholder="Rechercher un client (nom, email, téléphone, entreprise)"
                    value="<?= htmlspecialchars($search) ?>" class="form-control search-input">
            </div>

            <?php if (columnExists($db, 'clients', 'livreur_id') && in_array($userRole, ['admin', 'vendeur'], true)): ?>
                <div class="filter-group" style="min-width:220px">
                    <label for="affect" class="form-label">Affectation</label>
                    <select name="affect" id="affect" class="form-select filter-select">
                        <option value="all" <?= $affect === 'all' ? 'selected' : '' ?>>Tous (affectés + sans)</option>
                        <option value="none" <?= $affect === 'none' ? 'selected' : '' ?>>Sans livreur</option>
                    </select>
                </div>
                <div class="filter-group" style="min-width:260px">
                    <label for="livreur" class="form-label">Par livreur</label>
                    <select name="livreur" id="livreur" class="form-select filter-select">
                        <option value="0">Tous les livreurs</option>
                        <?php
                        // Charger la liste des livreurs (filtrés pour vendeur par dépôt)
                        $role = $_SESSION['user_role'] ?? '';
                        $sqlU = "SELECT id, full_name, depot_id FROM users WHERE 1=1";
                        $paramsU = [];
                        $roleCol = detectUserRoleColumn($db);
                        if ($roleCol) {
                            $sqlU .= " AND $roleCol = ?";
                            $paramsU[] = 'livreur';
                        }
                        if ($role === 'vendeur') {
                            $sqlU .= " AND depot_id = ?";
                            $paramsU[] = (int)($_SESSION['depot_id'] ?? 0);
                        }
                        $sqlU .= " ORDER BY full_name";
                        try {
                            $lus = $db->prepare($sqlU);
                            $lus->execute($paramsU);
                            $uls = $lus->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $uls = [];
                        }
                        foreach ($uls as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= $livreurFilter === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-group" style="min-width:220px">
                <label for="filter" class="form-label">Type</label>
                <select id="filter" name="filter" class="form-select filter-select">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tous les types</option>
                    <option value="particulier" <?= $filter === 'particulier' ? 'selected' : '' ?>>Particuliers</option>
                    <option value="entreprise" <?= $filter === 'entreprise' ? 'selected' : '' ?>>Entreprise</option>
                    <option value="revendeur" <?= $filter === 'revendeur' ? 'selected' : '' ?>>Revendeurs</option>
                </select>
            </div>

            <div class="filters-actions" style="display:flex; gap:.5rem; margin-left:auto; align-items:center; flex-wrap:wrap">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <a href="clients.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Reset
                </a>
                <a class="btn" href="<?= BASE_URL ?>/app/export/clients_xls.php?search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>&affect=<?= urlencode($affect) ?>&livreur=<?= urlencode((string)$livreurFilter) ?>">
                    <i class="fas fa-file-excel"></i> Export XLS
                </a>
                <a class="btn" href="<?= BASE_URL ?>/app/export/clients_pdf.php?search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>&affect=<?= urlencode($affect) ?>&livreur=<?= urlencode((string)$livreurFilter) ?>">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </form>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-number"><?= number_format($totalClients) ?></span>
            <span class="stat-label">Clients Total</span>
        </div>
    </div>

    <!-- Action en masse + Tableau des clients -->
    <?php $canBulk = hasPermission('clients_update') && columnExists($db, 'clients', 'livreur_id') && in_array($userRole, ['admin', 'vendeur'], true); ?>
    <?php if ($canBulk): ?>
        <form method="POST" class="clients-table-container" id="bulkAssignForm">
            <input type="hidden" name="bulk_action" value="bulk_assign" />
            <div class="d-flex align-items-center justify-content-end gap-2 p-2">
                <label for="bulk_livreur" class="me-2">Affecter la sélection à :</label>
                <select id="bulk_livreur" name="livreur_id" class="form-select" style="max-width: 320px">
                    <option value="">— Sélectionner livreur —</option>
                    <?php
                    $role = $_SESSION['user_role'] ?? '';
                    $sqlU = "SELECT id, full_name, depot_id FROM users WHERE 1=1";
                    $paramsU = [];
                    $roleCol = detectUserRoleColumn($db);
                    if ($roleCol) {
                        $sqlU .= " AND $roleCol = ?";
                        $paramsU[] = 'livreur';
                    }
                    if ($role === 'vendeur') {
                        $sqlU .= " AND depot_id = ?";
                        $paramsU[] = (int)($_SESSION['depot_id'] ?? 0);
                    }
                    $sqlU .= " ORDER BY full_name";
                    try {
                        $lus = $db->prepare($sqlU);
                        $lus->execute($paramsU);
                        $uls = $lus->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $uls = [];
                    }
                    foreach ($uls as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit" name="bulk_submit" value="1"><i class="fas fa-user-check"></i> Affecter</button>
            </div>
        <?php else: ?>
            <div class="clients-table-container">
            <?php endif; ?>
            <table class="clients-table">
                <thead>
                    <tr>
                        <?php if ($canBulk): ?>
                            <th><input type="checkbox" id="select_all_clients" /></th>
                        <?php endif; ?>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Adresse</th>
                        <?php if ($hasClientType): ?>
                            <th>Type</th>
                        <?php endif; ?>
                        <?php if ($userRole === 'admin' && $hasDepotJoin): ?>
                            <th>Dépôt</th>
                        <?php endif; ?>
                        <?php if (columnExists($db, 'clients', 'livreur_id')): ?><th>Livreur</th><?php endif; ?>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <?php
                            // Colonnes toujours présentes: Client, Contact, Adresse, Créé le, Actions = 5
                            $cols = 5;
                            if ($hasClientType) $cols++; // Type
                            if ($userRole === 'admin' && $hasDepotJoin) $cols++; // Dépôt
                            if (columnExists($db, 'clients', 'livreur_id')) $cols++; // Livreur
                            ?>
                            <td colspan="<?= $cols ?>" class="no-data">
                                <i class="fas fa-users"></i>
                                Aucun client trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <?php if ($canBulk): ?>
                                    <td><input type="checkbox" name="client_ids[]" value="<?= (int)$client['id'] ?>" class="client-checkbox" /></td>
                                <?php endif; ?>
                                <td>
                                    <div class="client-info">
                                        <?php
                                        $displayName = $client['name'] ?? trim(($client['nom'] ?? '') . ' ' . ($client['prenom'] ?? ($client['prenom'] ?? '')));
                                        if (!$displayName) {
                                            $displayName = 'Client';
                                        }
                                        ?>
                                        <strong><?= htmlspecialchars($displayName) ?></strong>
                                        <?php $companyVal = $client['company'] ?? ($client['entreprise'] ?? '');
                                        if ($companyVal): ?>
                                            <br><small><?= htmlspecialchars($companyVal) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <?php $emailVal = $client['email'] ?? '';
                                        if ($emailVal): ?>
                                            <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($emailVal) ?></div>
                                        <?php endif; ?>
                                        <?php $phoneVal = $client['phone'] ?? ($client['telephone'] ?? '');
                                        if ($phoneVal): ?>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($phoneVal) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="address-info">
                                        <?php
                                        $addr = $client['address'] ?? ($client['adresse'] ?? '');
                                        $city = $client['city'] ?? ($client['zone'] ?? '');
                                        $pc = $client['postal_code'] ?? '';
                                        ?>
                                        <?= htmlspecialchars($addr) ?><br>
                                        <?= htmlspecialchars(trim($pc . ' ' . $city)) ?>
                                    </div>
                                </td>
                                <?php if ($hasClientType): ?>
                                    <?php
                                    $ctype = $client['client_type'] ?? ($client['type_client'] ?? null);
                                    $ctypeSafe = $ctype ? preg_replace('/[^a-z_\-]/i', '', strtolower($ctype)) : 'inconnu';
                                    ?>
                                    <td>
                                        <span class="type-badge type-<?= htmlspecialchars($ctypeSafe) ?>">
                                            <?= htmlspecialchars($ctype ? ucfirst($ctype) : '—') ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <?php if ($userRole === 'admin' && $hasDepotJoin): ?>
                                    <td><?= htmlspecialchars($client['depot_nom'] ?? '') ?></td>
                                <?php endif; ?>
                                <?php if (columnExists($db, 'clients', 'livreur_id')): ?>
                                    <td><?= htmlspecialchars($client['livreur_nom'] ?? '—') ?></td>
                                <?php endif; ?>
                                <td><?= date('d/m/Y', strtotime($client['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if (hasPermission('clients_update')): ?>
                                            <?php
                                            // Normaliser les valeurs pour le bouton d'édition (sans JS inline)
                                            $nId = (int)$client['id'];
                                            $nName = $displayName;
                                            $nEmail = $client['email'] ?? '';
                                            $nPhone = $client['phone'] ?? ($client['telephone'] ?? '');
                                            $nAddress = $client['address'] ?? ($client['adresse'] ?? '');
                                            $nCity = $client['city'] ?? ($client['zone'] ?? '');
                                            $nPostal = $client['postal_code'] ?? '';
                                            $nCompany = $client['company'] ?? ($client['entreprise'] ?? '');
                                            $nCtype = $client['client_type'] ?? ($client['type_client'] ?? '');
                                            $nLivreurId = isset($client['livreur_id']) ? (int)$client['livreur_id'] : '';
                                            $nLivreurNom = $client['livreur_nom'] ?? '';
                                            ?>
                                            <button type="button" class="btn-icon btn-primary btn-edit-client"
                                                data-id="<?= $nId ?>"
                                                data-name="<?= htmlspecialchars($nName) ?>"
                                                data-email="<?= htmlspecialchars($nEmail) ?>"
                                                data-phone="<?= htmlspecialchars($nPhone) ?>"
                                                data-address="<?= htmlspecialchars($nAddress) ?>"
                                                data-city="<?= htmlspecialchars($nCity) ?>"
                                                data-postal-code="<?= htmlspecialchars($nPostal) ?>"
                                                data-company="<?= htmlspecialchars($nCompany) ?>"
                                                data-client-type="<?= htmlspecialchars($nCtype) ?>"
                                                data-livreur-id="<?= $nLivreurId ?>"
                                                data-livreur-nom="<?= htmlspecialchars($nLivreurNom) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (hasPermission('clients_delete')): ?>
                                            <button type="button" class="btn-icon btn-danger btn-delete-client"
                                                data-id="<?= (int)$client['id'] ?>"
                                                data-name="<?= htmlspecialchars($displayName) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($canBulk): ?>
        </form>
    <?php else: ?>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>&affect=<?= urlencode($affect) ?>&livreur=<?= urlencode((string)$livreurFilter) ?>"
                class="page-link <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
</div>

<!-- Modal Nouveau Client (Bootstrap) -->
<?php if (hasPermission('clients_create')): ?>
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouveau Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" class="client-form">
                    <input type="hidden" name="action" value="create">
                    <?php if (columnExists($db, 'clients', 'livreur_id')): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="create_livreur">Affecter à un livreur</label>
                                <select id="create_livreur" name="livreur_id">
                                    <option value="">— Aucun —</option>
                                    <?php
                                    // Liste des livreurs, filtrée par rôle
                                    $role = $_SESSION['user_role'] ?? '';
                                    $sqlU = "SELECT id, full_name, depot_id FROM users WHERE 1=1";
                                    $paramsU = [];
                                    $roleCol = detectUserRoleColumn($db);
                                    if ($roleCol) {
                                        $sqlU .= " AND $roleCol = ?";
                                        $paramsU[] = 'livreur';
                                    }
                                    if ($role === 'vendeur') {
                                        $sqlU .= " AND depot_id = ?";
                                        $paramsU[] = (int)($_SESSION['depot_id'] ?? 0);
                                    }
                                    $sqlU .= " ORDER BY full_name";
                                    try {
                                        $lus = $db->prepare($sqlU);
                                        $lus->execute($paramsU);
                                        $uls = $lus->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (Exception $e) {
                                        $uls = [];
                                    }
                                    foreach ($uls as $u): ?>
                                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nom *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="client_type">Type *</label>
                            <select id="client_type" name="client_type" required>
                                <option value="particulier">Particulier</option>
                                <option value="entreprise">Entreprise</option>
                                <option value="revendeur">Revendeur</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company">Entreprise</label>
                        <input type="text" id="company" name="company">
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse *</label>
                        <textarea id="address" name="address" rows="2" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Code Postal *</label>
                            <input type="text" id="postal_code" name="postal_code" required>
                        </div>

                        <div class="form-group">
                            <label for="city">Ville *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                    </div>

                    <div class="form-actions d-flex justify-content-end gap-2 p-3 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer le Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Édition Client (Bootstrap) -->
<?php if (hasPermission('clients_update')): ?>
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" class="client-form" id="editClientForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <?php if (columnExists($db, 'clients', 'livreur_id')): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_livreur">Affecter à un livreur</label>
                                <?php $disableLivSelect = in_array($userRole, ['livreur', 'commercial'], true); ?>
                                <select id="edit_livreur" name="livreur_id" <?= $disableLivSelect ? 'disabled' : '' ?>>
                                    <option value="">— Aucun —</option>
                                    <?php
                                    $role = $_SESSION['user_role'] ?? '';
                                    $sqlU = "SELECT id, full_name, depot_id FROM users WHERE 1=1";
                                    $paramsU = [];
                                    $roleCol = detectUserRoleColumn($db);
                                    if ($roleCol) {
                                        $sqlU .= " AND $roleCol = ?";
                                        $paramsU[] = 'livreur';
                                    }
                                    if ($role === 'vendeur') {
                                        $sqlU .= " AND depot_id = ?";
                                        $paramsU[] = (int)($_SESSION['depot_id'] ?? 0);
                                    }
                                    $sqlU .= " ORDER BY full_name";
                                    try {
                                        $lus = $db->prepare($sqlU);
                                        $lus->execute($paramsU);
                                        $uls = $lus->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (Exception $e) {
                                        $uls = [];
                                    }
                                    foreach ($uls as $u): ?>
                                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="edit_livreur_current">&nbsp;</div>
                                <small class="text-muted">Admin: tous les livreurs. Vendeur: livreurs de son dépôt. Livreur: non modifiable.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_name">Nom *</label>
                            <input type="text" id="edit_name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_client_type">Type *</label>
                            <select id="edit_client_type" name="client_type" required>
                                <option value="particulier">Particulier</option>
                                <option value="entreprise">Entreprise</option>
                                <option value="revendeur">Revendeur</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email">
                        </div>

                        <div class="form-group">
                            <label for="edit_phone">Téléphone</label>
                            <input type="tel" id="edit_phone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_company">Entreprise</label>
                        <input type="text" id="edit_company" name="company">
                    </div>

                    <div class="form-group">
                        <label for="edit_address">Adresse *</label>
                        <textarea id="edit_address" name="address" rows="2" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_postal_code">Code Postal *</label>
                            <input type="text" id="edit_postal_code" name="postal_code" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_city">Ville *</label>
                            <input type="text" id="edit_city" name="city" required>
                        </div>
                    </div>

                    <div class="form-actions d-flex justify-content-end gap-2 p-3 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .clients-page {
        padding: 2rem;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-header h1 {
        color: #333;
        margin: 0;
    }

    .filters-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .filters-form {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .search-input,
    .filter-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .search-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: #FFD700;
    }

    .stats-row {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .stat-item {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        color: #FFD700;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .clients-table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .clients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .clients-table th,
    .clients-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .clients-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .clients-table tr:hover {
        background: #f8f9fa;
    }

    .client-info strong {
        color: #333;
    }

    .contact-info div {
        margin-bottom: 0.25rem;
    }

    .contact-info i {
        width: 15px;
        color: #666;
    }

    .address-info {
        font-size: 0.9rem;
        color: #666;
    }

    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .type-particulier {
        background: #e3f2fd;
        color: #1976d2;
    }

    .type-entreprise {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .type-revendeur {
        background: #e8f5e8;
        color: #388e3c;
    }

    .actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .btn-icon.btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-icon.btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-icon:hover {
        transform: scale(1.1);
    }

    .no-data {
        text-align: center;
        padding: 3rem;
        color: #999;
    }

    .no-data i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .page-link {
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
    }

    .page-link.active {
        background: #FFD700;
        color: #333;
        font-weight: bold;
    }

    .page-link:hover {
        background: #f8f9fa;
    }

    .client-form {
        padding: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
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
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #FFD700;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .filters-form {
            flex-direction: column;
        }

        .filter-group {
            min-width: auto;
        }

        .stats-row {
            flex-direction: column;
            gap: 1rem;
        }

        .clients-table-container {
            overflow-x: auto;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<script src="<?= BASE_URL ?>/app/assets/js/clients.js"></script>

<?php include 'includes/footer.php'; ?>