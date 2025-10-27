<?php
require_once __DIR__ . '/../includes/config.php';
require_once dirname(__DIR__, 2) . '/backend/models/Sale.php';
require_once dirname(__DIR__, 2) . '/backend/models/Client.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if (!hasPermission('sales_create')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $input = $_POST;
    if ($raw) {
        $dec = json_decode($raw, true);
        if (is_array($dec)) $input = $dec;
    }

    // Champs attendus (minimaux)
    $client_id = isset($input['client_id']) ? (int)$input['client_id'] : 0;
    $new_client = isset($input['new_client']) && is_array($input['new_client']) ? $input['new_client'] : null;
    $depot_id = isset($input['depot_id']) ? (int)$input['depot_id'] : (int)($_SESSION['depot_id'] ?? 0);
    $assignment_id = isset($input['assignment_id']) && $input['assignment_id'] !== '' ? (int)$input['assignment_id'] : null;
    $lines = isset($input['lines']) && is_array($input['lines']) ? $input['lines'] : [];
    $amount_paid = isset($input['amount_paid']) ? (float)$input['amount_paid'] : 0;
    $notes = isset($input['notes']) ? trim((string)$input['notes']) : null;

    if ($depot_id <= 0 || empty($lines)) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters', 'hint' => 'depot_id, lines required']);
        exit;
    }

    // Si nouveau client
    if ($client_id <= 0 && $new_client) {
        if (!hasPermission('clients_create')) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden_clients_create']);
            exit;
        }
        // Création client minimale via modèle
        $cli = new Client($db);
        $cli->nom = trim((string)($new_client['nom'] ?? 'Client'));
        $cli->prenoms = trim((string)($new_client['prenoms'] ?? ''));
        $cli->telephone = trim((string)($new_client['telephone'] ?? ''));
        $cli->email = trim((string)($new_client['email'] ?? ''));
        $cli->adresse = trim((string)($new_client['adresse'] ?? ''));
        $cli->zone = trim((string)($new_client['zone'] ?? ''));
        $cli->type_client = (string)($new_client['type_client'] ?? 'particulier');
        $cli->credit_limite = (float)($new_client['credit_limite'] ?? 0);
        $cli->created_by = (int)($_SESSION['user_id'] ?? 0);
        $client_id = (int)$cli->create();
        if ($client_id <= 0) {
            throw new Exception('client_create_failed');
        }
    }

    if ($client_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'client_missing']);
        exit;
    }

    // Construire la vente
    $sale = new Sale($db);
    $sale->client_id = $client_id;
    $sale->depot_id = $depot_id;
    $sale->vendeur_id = (int)($_SESSION['user_id'] ?? 0);
    $sale->assignment_id = $assignment_id;
    $sale->notes = $notes;

    $total = 0.0;
    $details = [];
    foreach ($lines as $ln) {
        if (!isset($ln['product_id'], $ln['quantite'])) continue;
        $pid = (int)$ln['product_id'];
        $qty = (float)$ln['quantite'];
        $pu  = isset($ln['prix_unitaire']) ? (float)$ln['prix_unitaire'] : 0;
        $st  = $pu * $qty;
        $total += $st;
        $details[] = [
            'product_id' => $pid,
            'quantite' => $qty,
            'prix_unitaire' => $pu,
            'sous_total' => $st
        ];
    }
    if (empty($details)) {
        http_response_code(400);
        echo json_encode(['error' => 'no_lines']);
        exit;
    }

    $sale->details = $details;
    $sale->montant_total = $total;
    $sale->montant_paye = max(0, min($amount_paid, $total));
    $sale->montant_restant = max(0, $total - $sale->montant_paye);
    $sale->type_vente = $sale->montant_restant > 0 ? 'credit' : 'comptant';
    $sale->statut = 'validee';
    $sale->date_echeance = $sale->type_vente === 'credit' ? date('Y-m-d', strtotime('+14 days')) : null;

    $sale_id = $sale->create();
    echo json_encode(['ok' => true, 'sale_id' => (int)$sale_id, 'client_id' => (int)$client_id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
