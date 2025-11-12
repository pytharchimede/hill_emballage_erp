<?php
require_once 'includes/config.php';
requireLogin();

$role = $_SESSION['user_role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$pageTitle = 'Distributions';

include __DIR__ . '/includes/header.php';
?>

<!-- Select2 for recherche -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .select2-container .select2-selection--single {
        height: 38px;
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
        right: .5rem
    }
</style>
<div class="page-header">
    <h1>Distributions</h1>
    <div class="breadcrumb">Gestion des sorties itinérantes</div>
    <div class="mt-2">
        <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
            <span class="badge bg-primary">Gestion: gérant</span>
        <?php else: ?>
            <span class="badge bg-secondary">Consultation: commercial</span>
        <?php endif; ?>
    </div>

    <div class="mt-3 small text-muted">
        - Gérant: créer une distribution, enregistrer retours et clôturer.<br>
        - Commercial: consulter vos distributions ouvertes.
    </div>

</div>

<div class="card">
    <h3 class="mb-3">Distributions ouvertes</h3>
    <div id="assignmentsList" class="table-responsive"></div>
</div>

<?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
    <div class="card">
        <h3 class="mb-3">Créer une distribution</h3>
        <form id="createForm" class="row g-3">
            <?php
            // Charger dépôts et utilisateurs (assignee) pour le formulaire
            // Si gérant rattaché à un dépôt non principal, restreindre au dépôt de la gérante
            $depots = [];
            $current = getCurrentUser();
            $userDepotId = (int)($current['depot_id'] ?? 0);
            $restrictDepot = (in_array($role, ['gerant'], true) && $userDepotId > 0 && !isMainDepot($userDepotId));
            if ($restrictDepot) {
                $stDep = $db->prepare("SELECT id, nom FROM depots WHERE is_active=1 AND id=? ORDER BY nom");
                $stDep->execute([$userDepotId]);
                $depots = $stDep->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $depots = $db->query("SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
            }

            // Détecter colonnes existantes sur users (compat schémas différents)
            $colExists = function ($col) use ($db) {
                $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME=?");
                $q->execute([$col]);
                return ((int)$q->fetchColumn()) > 0;
            };
            $roleCol = $colExists('user_role') ? 'user_role' : 'role';
            $hasNom = $colExists('nom');
            $hasPrenoms = $colExists('prenoms');
            $hasUserDepot = $colExists('depot_id');
            // Construire l'expression du nom complet selon le schéma
            if ($hasNom) {
                // Alias en full_name pour homogénéiser l'affichage
                $nameExpr = "CONCAT(COALESCE(nom,''),' ',COALESCE(" . ($hasPrenoms ? "prenoms" : "''") . ",'') ) AS full_name";
                $orderBy = "ORDER BY nom" . ($hasPrenoms ? ", prenoms" : "");
            } else {
                $nameExpr = "full_name"; // colonne native
                $orderBy = "ORDER BY full_name";
            }
            // Rôles cibles: commerciaux uniquement
            $targetRoles = "('commercial')";
            $sqlUsers = "SELECT id, $nameExpr, $roleCol AS user_role FROM users WHERE is_active=1 AND $roleCol IN $targetRoles $orderBy";
            $vendeurs = $db->query($sqlUsers)->fetchAll(PDO::FETCH_ASSOC);
            $assigneeLabel = 'Commercial';
            ?>
            <div class="col-md-4">
                <label class="form-label">Dépôt</label>
                <select class="form-select" id="depotSelect" name="depot_id" required>
                    <option value="">—</option>
                    <?php foreach ($depots as $d): ?>
                        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label"><?= htmlspecialchars($assigneeLabel) ?></label>
                <select class="form-select" id="vendeurSelect" name="vendeur_id" required style="width:100%">
                    <option value="">—</option>
                    <?php foreach ($vendeurs as $v): $label = trim($v['full_name'] ?? (($v['nom'] ?? '') . ' ' . ($v['prenoms'] ?? ''))); ?>
                        <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($label) ?> (<?= htmlspecialchars($v['user_role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Articles</label>
                <div id="lines"></div>
                <button type="button" class="btn btn-outline-secondary mt-2" id="addLineBtn"><i class="fas fa-plus"></i> Ajouter</button>
                <div class="form-text">Ajoutez un ou plusieurs produits du dépôt sélectionné.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <input type="text" class="form-control" name="notes" placeholder="optionnel">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Créer la distribution
                </button>
                <span id="createMsg" class="ms-2"></span>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Modal: Retours -->
<div class="modal fade" id="returnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer des retours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="returnsForm">
                    <input type="hidden" name="assignment_id" id="ret_assignment_id" />
                    <input type="hidden" name="depot_id" id="ret_depot_id" />
                    <div id="retLines"></div>
                    <div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="btnRetAll">Tout retourner</button></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSubmitReturns">Valider</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Clôture -->
<div class="modal fade" id="settleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clôturer la distribution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="settleForm">
                    <input type="hidden" name="assignment_id" id="set_assignment_id" />
                    <input type="hidden" name="vendeur_id" id="set_vendeur_id" />
                    <div class="mb-2"><label class="form-label">Montant cash versé</label><input type="number" step="0.01" min="0" class="form-control" name="cash_paid" id="set_cash_paid" required /></div>
                    <div class="mb-2"><label class="form-label">Notes (optionnel)</label><input type="text" class="form-control" name="notes" id="set_notes" /></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnSubmitSettle">Clôturer</button>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';

    async function loadAssignments() {
        const url = baseUrl + '/app/api/assignments_list.php';
        const res = await fetch(url);
        const data = await res.json();
        const wrap = document.getElementById('assignmentsList');
        if (!data.ok) {
            wrap.innerHTML = '<div class="alert alert-warning">Impossible de charger les distributions</div>';
            return;
        }
        if (!data.items || data.items.length === 0) {
            wrap.innerHTML = '<div class="text-muted">Aucune distribution ouverte.</div>';
            return;
        }
        let html = '<table class="table table-striped"><thead><tr>' +
            '<th>#</th><th>Numéro</th><th>Dépôt</th><th>Commercial</th><th>Qte totale</th><th>Vendue</th><th>Retournée</th><th>Cash</th><th>Crédit</th><?php if (in_array($role, ['admin', 'gerant'], true)) echo '<th>Actions</th>'; ?>' +
            '</tr></thead><tbody>';
        for (const r of data.items) {
            html += `<tr>
      <td>${r.id}</td>
      <td>${r.numero || ''}</td>
      <td>${r.depot_nom || r.depot_id}</td>
      <td>${r.vendeur_nom || r.vendeur_id}</td>
      <td>${r.qty_total}</td>
      <td>${r.qty_sold}</td>
      <td>${r.qty_returned}</td>
      <td>${r.amount_cash_collected}</td>
      <td>${r.amount_credit_outstanding}</td>
      <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
      <td>
        <button class="btn btn-sm btn-outline-primary" onclick="openReturns(${r.id}, ${r.depot_id})">Retours</button>
        <button class="btn btn-sm btn-outline-success" onclick="openSettle(${r.id}, ${r.vendeur_id})">Clôturer</button>
      </td>
      <?php endif; ?>
    </tr>`;
        }
        html += '</tbody></table>';
        wrap.innerHTML = html;
    }

    // Modales
    let returnsModal, settleModal;
    document.addEventListener('DOMContentLoaded', () => {
        returnsModal = new bootstrap.Modal(document.getElementById('returnsModal'));
        settleModal = new bootstrap.Modal(document.getElementById('settleModal'));
        const btnAll = document.getElementById('btnRetAll');
        if (btnAll) btnAll.addEventListener('click', () => {
            document.querySelectorAll('#retLines input.ret-qty').forEach(inp => {
                const max = parseFloat(inp.getAttribute('max') || '0');
                if (max > 0) inp.value = max;
            });
        });
        const btnRet = document.getElementById('btnSubmitReturns');
        if (btnRet) btnRet.addEventListener('click', submitReturns);
        const btnSettle = document.getElementById('btnSubmitSettle');
        if (btnSettle) btnSettle.addEventListener('click', submitSettle);
    });

    function openReturns(assignmentId, depotId) {
        document.getElementById('ret_assignment_id').value = assignmentId;
        document.getElementById('ret_depot_id').value = depotId;
        const cont = document.getElementById('retLines');
        cont.innerHTML = '<div class="text-muted">Chargement…</div>';
        fetch(baseUrl + '/app/api/assignments_details.php?assignment_id=' + assignmentId)
            .then(r => r.json()).then(data => {
                if (!data.ok) {
                    cont.innerHTML = '<div class="text-danger">Erreur de chargement</div>';
                    return;
                }
                const items = (data.items || []).filter(it => (parseFloat(it.remaining || 0) > 0));
                if (items.length === 0) {
                    cont.innerHTML = '<div class="text-muted">Rien à retourner.</div>';
                } else {
                    cont.innerHTML = items.map(it => {
                        const rem = parseFloat(it.remaining || 0);
                        const label = `${it.code ? it.code + ' - ' : ''}${it.nom || ''} (reste: ${rem})`;
                        return `<div class=\"row g-2 align-items-center mb-2\">\n                            <div class=\"col-md-8\"><div class=\"form-control-plaintext\">${label}</div></div>\n                            <div class=\"col-md-4\"><input type=\"number\" step=\"0.01\" min=\"0\" max=\"${rem}\" class=\"form-control ret-qty\" data-pid=\"${it.product_id}\" placeholder=\"Qté à retourner\" /></div>\n                        </div>`;
                    }).join('');
                }
                returnsModal.show();
            });
    }

    function submitReturns() {
        const assignmentId = parseInt(document.getElementById('ret_assignment_id').value || '0', 10);
        const depotId = parseInt(document.getElementById('ret_depot_id').value || '0', 10);
        const returns = Array.from(document.querySelectorAll('#retLines input.ret-qty'))
            .map(inp => ({
                product_id: parseInt(inp.getAttribute('data-pid'), 10),
                quantite: parseFloat(inp.value || '0')
            }))
            .filter(it => it.product_id > 0 && it.quantite > 0);
        if (returns.length === 0) {
            returnsModal.hide();
            return;
        }
        fetch(baseUrl + '/app/api/assignments_return.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                assignment_id: assignmentId,
                depot_id: depotId,
                returns
            })
        }).then(r => r.json()).then(resp => {
            if (resp.ok) {
                returnsModal.hide();
                loadAssignments();
            } else {
                alert('Erreur retour');
            }
        });
    }

    function openSettle(assignmentId, vendeurId) {
        document.getElementById('set_assignment_id').value = assignmentId;
        document.getElementById('set_vendeur_id').value = vendeurId;
        document.getElementById('set_cash_paid').value = '';
        document.getElementById('set_notes').value = '';
        settleModal.show();
    }

    function submitSettle() {
        const assignmentId = parseInt(document.getElementById('set_assignment_id').value || '0', 10);
        const vendeurId = parseInt(document.getElementById('set_vendeur_id').value || '0', 10);
        const cash = parseFloat(document.getElementById('set_cash_paid').value || '0');
        const notes = document.getElementById('set_notes').value || null;
        if (!(assignmentId > 0 && vendeurId > 0)) return;
        fetch(baseUrl + '/app/api/assignments_settle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                assignment_id: assignmentId,
                vendeur_id: vendeurId,
                cash_paid: cash,
                notes
            })
        }).then(r => r.json()).then(resp => {
            if (resp.ok) {
                settleModal.hide();
                loadAssignments();
            } else {
                alert('Erreur clôture');
            }
        });
    }


    <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
        document.getElementById('createForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const f = e.target;
            const depot_id = parseInt(f.depot_id.value || '0', 10);
            const vendeur_id = parseInt(f.vendeur_id.value || '0', 10);
            // Construire details depuis lignes
            const details = Array.from(document.querySelectorAll('#lines .line')).map(row => ({
                product_id: parseInt($(row).find('.product-select').val() || '0', 10),
                quantite: parseFloat(row.querySelector('.qty').value || '0'),
                unit_price: row.querySelector('.pu').value ? parseFloat(row.querySelector('.pu').value) : null
            })).filter(d => d.product_id > 0 && d.quantite > 0);
            const msg = document.getElementById('createMsg');
            if (details.length === 0) {
                msg.textContent = 'Ajoutez au moins un article';
                msg.className = 'text-danger';
                return;
            }
            const notes = f.notes.value || null;
            const res = await fetch(baseUrl + '/app/api/assignments_create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    depot_id,
                    vendeur_id,
                    details,
                    notes
                })
            });
            const data = await res.json();
            if (data.ok) {
                msg.textContent = 'Créé: #' + data.assignment_id;
                msg.className = 'text-success';
                f.reset();
                loadAssignments();
            } else {
                msg.textContent = data.error || 'Erreur';
                msg.className = 'text-danger';
            }
        });
    <?php endif; ?>

    loadAssignments();

    <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
        // Init Select2 pour vendeur
        $(function() {
            $('#vendeurSelect').select2({
                width: '100%'
            });
            // Dépôt par défaut: si restreint, présélectionner
            const depotSel = document.getElementById('depotSelect');
            if (depotSel && depotSel.options.length === 1 && depotSel.options[0].value) {
                depotSel.selectedIndex = 0; // unique option (hors placeholder)
            }
        });
        // Template ligne produits (Select2 + qty + PU)
        function initProductSelect($el) {
            $el.select2({
                placeholder: 'Produit du dépôt…',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: baseUrl + '/app/api/products_suggest.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || '',
                        limit: 10,
                        depot_id: document.getElementById('depotSelect').value || ''
                    }),
                    processResults: data => ({
                        results: (Array.isArray(data) ? data : []).map(it => ({
                            id: it.id,
                            text: (it.code ? it.code + ' - ' : '') + (it.nom || '') + (typeof it.remaining === 'number' ? ` (stock: ${it.remaining})` : ''),
                            pu: it.pu ?? null
                        }))
                    })
                }
            });
            $el.on('select2:select', function(e) {
                const d = e.params.data || {};
                const row = $el.closest('.line');
                if (d.pu != null) row.find('.pu').val(d.pu);
            });
        }

        function addLine() {
            const wrap = document.getElementById('lines');
            const div = document.createElement('div');
            div.className = 'row g-2 align-items-end mb-2 line';
            div.innerHTML = `
            <div class="col-md-6"><select class="form-select product-select" style="width:100%"></select></div>
            <div class="col-md-3"><input type="number" class="form-control qty" placeholder="Qté" min="1" value="1" required></div>
            <div class="col-md-3"><input type="number" class="form-control pu" placeholder="PU (optionnel)" min="0" step="0.01"></div>`;
            wrap.appendChild(div);
            initProductSelect($(div.querySelector('.product-select')));
        }
        document.getElementById('addLineBtn').addEventListener('click', addLine);
        addLine();
        // Quand dépôt change, on nettoie les sélecteurs produits pour forcer le refiltrage
        document.getElementById('depotSelect').addEventListener('change', () => {
            document.querySelectorAll('#lines .product-select').forEach(sel => {
                const $s = $(sel);
                $s.val(null).trigger('change');
            });
        });
    <?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>