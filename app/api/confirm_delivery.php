<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}
// Autorisations: livreur (deliveries_update) ou rôles ayant modification ventes (sales_update)
$allowedDeliveries = hasPermission('deliveries_update');
$allowedSales = hasPermission('sales_update');
// Autoriser aussi si utilisateur est explicitement le livreur assigné même sans deliveries_update (grâce à sales_read)
$rawUserId = (int)($_SESSION['user_id'] ?? 0);
$allowed = $allowedDeliveries || $allowedSales;
if (!$allowed) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'forbidden',
        'debug' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'deliveries_update' => $allowedDeliveries,
            'sales_update' => $allowedSales,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ]
    ]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

try {
    // Vérifier état actuel et propriété si livreur
    $hasLivreurCol = false;
    try {
        $chk = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='livreur_id'");
        $chk->execute();
        $hasLivreurCol = ((int)$chk->fetchColumn()) > 0;
    } catch (Exception $ie) {
    }
    if ($hasLivreurCol) {
        $qcol = $db->prepare("SELECT statut, livreur_id AS livreur_id_col FROM ventes WHERE id=?");
    } else {
        $qcol = $db->prepare("SELECT statut, NULL AS livreur_id_col FROM ventes WHERE id=?");
    }
    $qcol->execute([$id]);
    $row = $qcol->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('vente_introuvable');
    }
    $currentStatut = $row['statut'];
    $currentLivreurId = isset($row['livreur_id_col']) ? (int)$row['livreur_id_col'] : null;

    // Si livreur: valider propriété
    $isLivreur = hasPermission('deliveries_update') && !hasPermission('sales_update');
    if ($isLivreur) {
        if ($currentLivreurId && $currentLivreurId !== (int)$_SESSION['user_id']) {
            throw new Exception('non_attribue_au_livreur');
        }
    }

    // Statuts autorisés à passer en "livree"
    $allowedFrom = ['en_attente', 'validee', 'en_livraison'];
    if (!in_array($currentStatut, $allowedFrom, true)) {
        if ($currentStatut === 'livree') {
            echo json_encode(['ok' => true, 'already' => true]);
            exit; // idempotent
        }
        throw new Exception('statut_interdit:' . $currentStatut);
    }

    $st = $db->prepare("UPDATE ventes SET statut='livree', updated_at=NOW() WHERE id=?");
    $st->execute([$id]);
    if ($st->rowCount() === 0) {
        throw new Exception('maj_inattendue');
    }
    log_action('DELIVERY_CONFIRM', 'ventes', $id);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
