<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$orderNumber = trim((string)($_GET['order'] ?? ''));
$status = null;

try {
    if ($orderNumber !== '') {
        global $db;
        $stmt = $db->prepare('SELECT order_number, payment_status, order_status FROM storefront_orders WHERE order_number = ? LIMIT 1');
        $stmt->execute([$orderNumber]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    $status = null;
}

$paid = $status && $status['payment_status'] === 'paid';
$title = $paid ? 'Paiement confirmé' : 'Paiement en cours de vérification';
$message = $paid
    ? 'Votre paiement a bien été confirmé. Vous pouvez retourner dans l’application Hill Emballage.'
    : 'Votre paiement est en cours de vérification. Retournez dans l’application puis utilisez « Vérifier le paiement ».';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Hill Emballage</title>
    <style>
        :root { --yellow:#FFD400; --ink:#171717; --muted:#6f6f6f; --line:#ececec; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter,Arial,sans-serif; background:#f7f7f7; color:var(--ink); min-height:100vh; display:grid; place-items:center; padding:24px; }
        .card { width:min(100%,460px); background:#fff; border:1px solid var(--line); border-radius:28px; padding:28px; box-shadow:0 18px 50px rgba(0,0,0,.08); }
        .brand { display:flex; align-items:center; gap:12px; font-weight:900; margin-bottom:28px; }
        .logo { width:48px; height:48px; border-radius:16px; background:var(--yellow); display:grid; place-items:center; font-size:24px; }
        .state { width:64px; height:64px; border-radius:50%; display:grid; place-items:center; margin-bottom:18px; background:<?= $paid ? '#eaf8ef' : '#fff7cf' ?>; font-size:30px; }
        h1 { font-size:26px; line-height:1.1; margin:0 0 12px; }
        p { color:var(--muted); line-height:1.55; margin:0; }
        .order { margin-top:20px; padding:14px 16px; border-radius:16px; background:#f6f6f6; font-weight:800; }
        .hint { margin-top:18px; font-size:13px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand"><div class="logo">H</div><div>HILL EMBALLAGE</div></div>
        <div class="state"><?= $paid ? '✓' : '…' ?></div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
        <?php if ($orderNumber !== ''): ?>
            <div class="order">Commande <?= htmlspecialchars($orderNumber) ?></div>
        <?php endif; ?>
        <p class="hint">Vous pouvez fermer cette page après être revenu dans l’application.</p>
    </main>
</body>
</html>
