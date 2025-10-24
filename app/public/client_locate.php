<?php
require_once __DIR__ . '/../includes/config.php';
// Cette page est publique; ne nécessite pas de session mais vérifie une signature HMAC
$idParam = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['vente']) ? $_GET['vente'] : null);
$id = $idParam !== null ? (int)$idParam : 0;
$sig = $_GET['sig'] ?? '';
$valid = $id > 0 && $sig && hash_equals(hash_hmac('sha256', (string)$id, LINK_SIGN_SECRET), $sig);
$baseUrl = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Partager ma position</title>
    <meta name="base-url" content="<?= $baseUrl ?>" />
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
    <link href="<?= $baseUrl ?>app/assets/css/client_locate.css" rel="stylesheet">
</head>

<body data-id="<?= (int)$id ?>" data-sig="<?= htmlspecialchars($sig, ENT_QUOTES, 'UTF-8') ?>" data-valid="<?= $valid ? '1' : '0' ?>">
    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Partager ma position</h1>
                <?php if (!$valid): ?>
                    <div class="alert alert-danger">Lien invalide ou expiré.</div>
                <?php else: ?>
                    <p class="text-muted">Autorisez l'accès à votre position pour aider le livreur à vous localiser. Vous pourrez ajuster le point sur la carte avant d'envoyer.</p>
                    <div id="status" class="mb-3 small text-secondary">Prêt.</div>
                    <div class="mb-3 d-grid">
                        <button id="btnShare" class="btn btn-primary btn-lg">Utiliser ma position</button>
                    </div>
                    <div id="mapBlock" class="d-none">
                        <div id="cl_map" style="height:320px;border-radius:10px;overflow:hidden;"></div>
                        <div class="form-text">Déplacez le point si nécessaire pour ajuster votre position exacte.</div>
                        <div class="mt-3">
                            <label for="addr" class="form-label">Complément d'adresse (facultatif)</label>
                            <input type="text" id="addr" class="form-control" placeholder="Bâtiment, étage, portail, repères…" />
                        </div>
                        <div class="d-grid mt-3">
                            <button id="btnConfirm" class="btn btn-success btn-lg">Confirmer et envoyer</button>
                        </div>
                    </div>
                    <div id="ok" class="alert alert-success mt-3 d-none">Merci, votre position a été partagée. Vous pouvez fermer cette page.</div>
                    <div id="err" class="alert alert-danger mt-3 d-none"></div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted small">Protection des données: votre position est utilisée uniquement pour cette livraison.</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
    <script src="<?= $baseUrl ?>app/assets/js/client_locate.js"></script>
</body>

</html>