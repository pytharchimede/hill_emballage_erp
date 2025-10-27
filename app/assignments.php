<?php
require_once 'includes/config.php';
requireLogin();

$role = $_SESSION['user_role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$pageTitle = 'Assignations vendeurs';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Assignations vendeurs</h1>
    <div class="breadcrumb">Gestion des sorties itinérantes</div>
    <div class="mt-2">
        <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
            <span class="badge bg-primary">Gestion: gérant</span>
        <?php else: ?>
            <span class="badge bg-secondary">Consultation: vendeur/livreur</span>
        <?php endif; ?>
    </div>

    <div class="mt-3 small text-muted">
        - Gérant: créer une assignation, enregistrer retours et clôturer.<br>
        - Vendeur/Livreur: consulter vos assignations ouvertes.
    </div>

</div>

<div class="card">
    <h3 class="mb-3">Assignations ouvertes</h3>
    <div id="assignmentsList" class="table-responsive"></div>
</div>

<?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
    <div class="card">
        <h3 class="mb-3">Créer une assignation</h3>
        <form id="createForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Dépôt ID</label>
                <input type="number" class="form-control" name="depot_id" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vendeur ID</label>
                <input type="number" class="form-control" name="vendeur_id" required>
            </div>
            <div class="col-12">
                <label class="form-label">Détails (JSON)</label>
                <textarea class="form-control" name="details" rows="4" placeholder='[
  {"product_id":1, "quantite":10, "unit_price":500}
]'></textarea>
                <div class="form-text">Saisir un tableau JSON d'articles.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <input type="text" class="form-control" name="notes" placeholder="optionnel">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Créer l'assignation
                </button>
                <span id="createMsg" class="ms-2"></span>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';

    async function loadAssignments() {
        const url = baseUrl + '/app/api/assignments_list.php';
        const res = await fetch(url);
        const data = await res.json();
        const wrap = document.getElementById('assignmentsList');
        if (!data.ok) {
            wrap.innerHTML = '<div class="alert alert-warning">Impossible de charger les assignations</div>';
            return;
        }
        if (!data.items || data.items.length === 0) {
            wrap.innerHTML = '<div class="text-muted">Aucune assignation ouverte.</div>';
            return;
        }
        let html = '<table class="table table-striped"><thead><tr>' +
            '<th>#</th><th>Numéro</th><th>Dépôt</th><th>Vendeur</th><th>Qte totale</th><th>Vendue</th><th>Retournée</th><th>Cash</th><th>Crédit</th><?php if (in_array($role, ['admin', 'gerant'], true)) echo '<th>Actions</th>'; ?>' +
            '</tr></thead><tbody>';
        for (const r of data.items) {
            html += `<tr>
      <td>${r.id}</td>
      <td>${r.numero || ''}</td>
      <td>${r.depot_id}</td>
      <td>${r.vendeur_id}</td>
      <td>${r.qty_total}</td>
      <td>${r.qty_sold}</td>
      <td>${r.qty_returned}</td>
      <td>${r.amount_cash_collected}</td>
      <td>${r.amount_credit_outstanding}</td>
      <?php if (in_array($role, ['admin', 'gerant'], true)) : ?>
      <td>
        <button class="btn btn-sm btn-outline-primary" onclick="promptReturns(${r.id}, ${r.depot_id})">Retours</button>
        <button class="btn btn-sm btn-outline-success" onclick="promptSettle(${r.id}, ${r.vendeur_id})">Clôturer</button>
      </td>
      <?php endif; ?>
    </tr>`;
        }
        html += '</tbody></table>';
        wrap.innerHTML = html;
    }

    function promptReturns(assignmentId, depotId) {
        const json = prompt('Retour (JSON) ex: [{"product_id":1,"quantite":2}]');
        if (!json) return;
        let payload;
        try {
            payload = JSON.parse(json);
        } catch (e) {
            alert('JSON invalide');
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
                returns: payload
            })
        }).then(r => r.json()).then(resp => {
            if (resp.ok) {
                loadAssignments();
            } else {
                alert('Erreur retour');
            }
        });
    }

    function promptSettle(assignmentId, vendeurId) {
        const cash = prompt('Montant cash versé');
        if (cash === null) return;
        fetch(baseUrl + '/app/api/assignments_settle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                assignment_id: assignmentId,
                vendeur_id: vendeurId,
                cash_paid: parseFloat(cash) || 0
            })
        }).then(r => r.json()).then(resp => {
            if (resp.ok) {
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
            let details = [];
            const msg = document.getElementById('createMsg');
            try {
                details = JSON.parse(f.details.value || '[]');
            } catch (e) {
                msg.textContent = 'JSON invalide';
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>