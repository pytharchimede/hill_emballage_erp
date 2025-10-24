<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!in_array($_SESSION['user_role'], ['livreur', 'admin'])) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Livraisons';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header" style="background:linear-gradient(135deg,#FFD700 0%, #FFA500 100%);padding:1rem 1.25rem;border-radius:12px;color:#333;">
    <h1 style="margin:0;display:flex;align-items:center;gap:.5rem;"><i class="fas fa-truck"></i> Mes Livraisons</h1>
    <div class="small">Parcours du jour et suivi des preuves de livraison</div>
    <div class="mt-2 d-flex gap-2">
        <button class="btn btn-light" data-scope="pending"><i class="fas fa-clock"></i> En attente</button>
        <button class="btn btn-light" data-scope="today"><i class="fas fa-calendar-day"></i> Aujourd'hui</button>
        <button class="btn btn-light" data-scope="all"><i class="fas fa-list"></i> Toutes</button>
    </div>
    <div class="mt-2 small text-muted">Astuce: cliquez un point sur la carte ou une ligne pour ouvrir la fiche.</div>
</div>

<div class="row g-3" style="margin-top:1rem;">
    <div class="col-lg-7">
        <div class="card" style="border-radius:12px;overflow:hidden;">
            <div id="deliveries_map" style="height:480px;width:100%;"></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card" style="border-radius:12px;">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong><i class="fas fa-route"></i> Parcours</strong>
                <span class="small text-muted" id="deliveries_count">0</span>
            </div>
            <div class="list-group list-group-flush" id="deliveries_list" style="max-height:480px;overflow:auto;"></div>
        </div>
    </div>
</div>

<!-- Modal fiche de livraison -->
<div class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature"></i> Fiche de livraison</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-2"><strong>Commande</strong>: <span id="dl_numero">-</span></div>
                        <div class="mb-2"><strong>Client</strong>: <span id="dl_client">-</span></div>
                        <div class="mb-2"><strong>Adresse</strong>: <span id="dl_adresse">-</span></div>
                        <div class="mb-2"><strong>Montant</strong>: <span id="dl_montant">-</span> FCFA</div>
                        <div id="dl_map" style="height:240px;border-radius:10px;"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-camera"></i> Photo du colis livré</div>
                            <div class="card-body">
                                <form id="dl_photo_form" method="post" action="<?= BASE_URL ?>/app/api/upload_attachment.php" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="entity" value="ventes" />
                                    <input type="hidden" name="entity_id" id="dl_entity_id" value="" />
                                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/web_admin/deliveries.php" />
                                    <input class="form-control" type="file" name="file" accept="image/*" capture="environment" />
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Joindre</button>
                                </form>
                                <div class="small text-muted mt-1">Formats acceptés: JPG, PNG, GIF.</div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header"><i class="fas fa-pen-nib"></i> Signature du client</div>
                            <div class="card-body">
                                <div style="border:1px dashed #ccc;border-radius:8px;position:relative;">
                                    <canvas id="dl_signature" style="width:100%;height:220px;display:block;"></canvas>
                                    <div class="small text-center text-muted" style="position:absolute;top:8px;left:0;right:0;">Signez ci-dessus</div>
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-secondary" id="dl_sig_clear"><i class="fas fa-eraser"></i> Effacer</button>
                                    <button class="btn btn-primary" id="dl_sig_save"><i class="fas fa-save"></i> Enregistrer la signature</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button class="btn btn-outline-secondary" id="dl_print_btn"><i class="fas fa-print"></i> Imprimer la fiche</button>
                <button class="btn btn-success" id="dl_confirm_btn"><i class="fas fa-check"></i> Confirmer la livraison</button>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet + SignaturePad + JS page -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js" crossorigin="anonymous"></script>
<script src="<?= ASSETS_URL ?>/js/deliveries.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>