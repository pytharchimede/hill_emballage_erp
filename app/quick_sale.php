<?php
require_once 'includes/config.php';
requireLogin();

if (!hasPermission('sales_create')) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = 'Vente rapide';
include __DIR__ . '/includes/header.php';
?>

<!-- Select2 for recherche clients/produits -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    /* Harmoniser la hauteur de Select2 avec Bootstrap */
    .select2-container .select2-selection--single {
        height: 38px;
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
        right: 0.5rem;
    }

    .select2-container .select2-selection--multiple {
        min-height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        align-items: center;
        min-height: 38px;
    }
</style>

<div class="page-header">
    <h1>Vente rapide</h1>
    <div class="breadcrumb">Créer un client et conclure une vente sur un seul écran</div>
    <div class="small text-muted">Optimisé mobile (livreur/commercial)</div>
    <div class="mt-2">Dépôt: <span class="badge bg-light text-dark"><?php echo htmlspecialchars($_SESSION['depot_name'] ?? ''); ?></span></div>
</div>

<div class="card">
    <form id="qsForm" class="row g-3">
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="newClientSwitch" checked>
                <label class="form-check-label" for="newClientSwitch">Nouveau client</label>
            </div>
        </div>
        <div id="existingClientWrap" class="col-12" style="display:none">
            <label class="form-label">Client</label>
            <select class="form-select" id="clientSelect" style="width:100%"></select>
        </div>
        <div id="newClientWrap" class="col-12">
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nc_nom" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Prénoms</label>
                    <input type="text" class="form-control" id="nc_prenoms">
                </div>
                <div class="col-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" class="form-control" id="nc_tel">
                </div>
                <div class="col-6">
                    <label class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="nc_adresse">
                </div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Affecter à une assignation (optionnel)</label>
            <select class="form-select" id="assignmentSelect">
                <option value="">Sans assignation</option>
            </select>
            <div class="form-text">Si vous avez une assignation ouverte, sélectionnez-la pour consommer la réserve.</div>
        </div>

        <div class="col-12">
            <label class="form-label">Articles</label>
            <div id="lines"></div>
            <button type="button" class="btn btn-outline-secondary mt-2" id="addLineBtn"><i class="fas fa-plus"></i> Ajouter</button>
        </div>

        <div class="col-md-4">
            <label class="form-label">Montant payé</label>
            <input type="number" class="form-control" id="amountPaid" value="0" min="0" step="0.01">
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <input type="text" class="form-control" id="notes" placeholder="optionnel">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-check"></i> Valider la vente</button>
            <div id="qsMsg" class="mt-2"></div>
        </div>
    </form>
</div>

<template id="lineTpl">
    <div class="row g-2 align-items-end mb-2 line position-relative">
        <div class="col-6 position-relative">
            <select class="form-select product-select" style="width:100%"></select>
        </div>
        <div class="col-3">
            <input type="number" class="form-control qty" placeholder="Qté" min="1" value="1" required>
            <div class="form-text remaining-info"></div>
            <div class="text-danger small remaining-warn" style="display:none;"></div>
        </div>
        <div class="col-3">
            <input type="number" class="form-control pu" placeholder="PU" min="0" step="0.01" required>
        </div>
    </div>
</template>

<script>
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';

    function initProductSelect($el) {
        $el.select2({
            placeholder: 'Rechercher produit…',
            allowClear: true,
            ajax: {
                url: baseUrl + '/app/api/products_suggest.php',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    limit: 10,
                    assignment_id: document.getElementById('assignmentSelect').value || ''
                }),
                processResults: data => ({
                    results: (Array.isArray(data) ? data : []).map(it => ({
                        id: it.id,
                        text: (it.code ? it.code + ' - ' : '') + (it.nom || '') + (typeof it.remaining === 'number' ? ` (reste: ${it.remaining})` : ''),
                        pu: typeof it.pu === 'number' ? it.pu : null,
                        remaining: typeof it.remaining === 'number' ? it.remaining : null
                    }))
                })
            },
            width: '100%'
        });

        // au select, préremplir le PU si disponible
        $el.on('select2:select', function(e) {
            const data = e.params.data || {};
            const row = $el.closest('.line');
            if (data.pu != null) {
                row.find('.pu').val(data.pu);
            }
        });
    }

    function addLine() {
        const tpl = document.getElementById('lineTpl').content.cloneNode(true);
        document.getElementById('lines').appendChild(tpl);
        const lineEl = document.querySelector('#lines .line:last-child');
        const $sel = $(lineEl.querySelector('.product-select'));
        const qtyInput = lineEl.querySelector('.qty');
        const remainInfo = lineEl.querySelector('.remaining-info');
        const warn = lineEl.querySelector('.remaining-warn');

        // stocker le restant sur le data-attribute du container de ligne
        lineEl.dataset.remaining = '';

        initProductSelect($sel);

        function clampQty() {
            const rem = parseFloat(lineEl.dataset.remaining || '');
            const hasRem = !Number.isNaN(rem);
            let q = parseFloat(qtyInput.value || '0');
            if (hasRem && rem >= 0 && q > rem) {
                qtyInput.value = rem > 0 ? rem : 0;
                warn.style.display = '';
                warn.textContent = 'Quantité ajustée au reste disponible';
            } else {
                warn.style.display = 'none';
                warn.textContent = '';
            }
        }
        qtyInput.addEventListener('input', clampQty);
    }

    async function loadAssignments() {
        try {
            const res = await fetch(baseUrl + '/app/api/assignments_list.php');
            const data = await res.json();
            const sel = document.getElementById('assignmentSelect');
            if (data.ok && Array.isArray(data.items)) {
                for (const it of data.items) {
                    const opt = document.createElement('option');
                    opt.value = it.id;
                    opt.textContent = (it.numero || ('#' + it.id)) + ' | Q: ' + it.qty_total + ' / Vendue: ' + it.qty_sold;
                    sel.appendChild(opt);
                }
            }
        } catch (e) {}
    }

    document.getElementById('newClientSwitch').addEventListener('change', (e) => {
        const on = e.target.checked;
        document.getElementById('newClientWrap').style.display = on ? '' : 'none';
        document.getElementById('existingClientWrap').style.display = on ? 'none' : '';
        if (!on) {
            const $c = $('#clientSelect');
            if (!$c.hasClass('select2-hidden-accessible')) {
                $c.select2({
                    placeholder: 'Rechercher client…',
                    allowClear: true,
                    ajax: {
                        url: baseUrl + '/app/api/clients_suggest.php',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({
                            q: params.term || '',
                            limit: 10
                        }),
                        processResults: data => ({
                            results: (Array.isArray(data) ? data : []).map(it => ({
                                id: it.id,
                                text: it.label + (it.telephone ? (' | ' + it.telephone) : '')
                            }))
                        })
                    },
                    width: '100%'
                });
            }
        }
    });

    // Préinit client select si nécessaire
    if (!document.getElementById('newClientSwitch').checked) {
        const $c = $('#clientSelect');
        $c.select2({
            placeholder: 'Rechercher client…',
            allowClear: true,
            ajax: {
                url: baseUrl + '/app/api/clients_suggest.php',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    limit: 10
                }),
                processResults: data => ({
                    results: (Array.isArray(data) ? data : []).map(it => ({
                        id: it.id,
                        text: it.label + (it.telephone ? (' | ' + it.telephone) : '')
                    }))
                })
            },
            width: '100%'
        });
    }

    document.getElementById('addLineBtn').addEventListener('click', addLine);
    // Nettoyer les sélections produits si l'assignation change, pour resynchroniser la recherche
    document.getElementById('assignmentSelect').addEventListener('change', () => {
        document.querySelectorAll('#lines .line').forEach(line => {
            const $s = $(line.querySelector('.product-select'));
            $s.val(null).trigger('change');
            line.dataset.remaining = '';
            const info = line.querySelector('.remaining-info');
            if (info) info.textContent = '';
            const warn = line.querySelector('.remaining-warn');
            if (warn) {
                warn.style.display = 'none';
                warn.textContent = '';
            }
            const pu = line.querySelector('.pu');
            if (pu) pu.value = '';
        });
    });
    addLine();
    loadAssignments();

    document.getElementById('qsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('qsMsg');
        msg.textContent = '';
        msg.className = '';

        const lines = Array.from(document.querySelectorAll('#lines .line')).map(row => ({
            product_id: parseInt($(row).find('.product-select').val() || '0', 10),
            quantite: parseFloat(row.querySelector('.qty').value || '0'),
            prix_unitaire: parseFloat(row.querySelector('.pu').value || '0')
        })).filter(l => l.product_id > 0 && l.quantite > 0);

        const payload = {
            depot_id: <?php echo (int)($_SESSION['depot_id'] ?? 0); ?>,
            assignment_id: document.getElementById('assignmentSelect').value || null,
            lines,
            amount_paid: parseFloat(document.getElementById('amountPaid').value || '0'),
            notes: document.getElementById('notes').value || null
        };

        if (document.getElementById('newClientSwitch').checked) {
            payload.new_client = {
                nom: document.getElementById('nc_nom').value || 'Client',
                prenoms: document.getElementById('nc_prenoms').value || '',
                telephone: document.getElementById('nc_tel').value || '',
                adresse: document.getElementById('nc_adresse').value || ''
            };
        } else {
            payload.client_id = parseInt($('#clientSelect').val() || '0', 10);
        }

        try {
            const res = await fetch(baseUrl + '/app/api/quick_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.ok) {
                msg.className = 'text-success';
                msg.textContent = 'Vente validée #' + data.sale_id + ' (Client #' + data.client_id + ')';
                // reset simple
                document.getElementById('lines').innerHTML = '';
                addLine();
                document.getElementById('amountPaid').value = '0';
            } else {
                msg.className = 'text-danger';
                msg.textContent = data.error || 'Erreur';
            }
        } catch (e) {
            msg.className = 'text-danger';
            msg.textContent = 'Erreur réseau';
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>