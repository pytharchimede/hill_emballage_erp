<?php
require_once 'includes/config.php';
require_once 'includes/migrations.php';
requireLogin();
if (!hasPermission('view_reports') && !in_array($_SESSION['user_role'] ?? '', ['livreur', 'commercial', 'vendeur'], true)) {
    setFlashMessage('warning', "Accès refusé.");
    header('Location: dashboard.php');
    exit();
}

ensureLivreurFlowTables($db);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$livreurId = isset($_GET['livreur_id']) ? (int)$_GET['livreur_id'] : (in_array($_SESSION['user_role'] ?? '', ['livreur', 'commercial'], true) ? (int)$_SESSION['user_id'] : 0);

// Liste des livreurs (pour filtre si pas livreur)
$livreurs = [];
if (!in_array($_SESSION['user_role'] ?? '', ['livreur', 'commercial'], true)) {
    // Détection dynamique de la colonne rôle dans users (user_role/role/profil/type)
    $roleCol = null;
    try {
        $cand = ['user_role', 'role', 'profil', 'type'];
        $sqlCols = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME IN ('" . implode("','", $cand) . "')";
        $rs = $db->query($sqlCols)->fetchAll(PDO::FETCH_COLUMN);
        if ($rs) {
            $roleCol = $rs[0];
        }
    } catch (Exception $e) {
    }
    // Restreindre aux livreurs du dépôt courant pour vendeur/comptable non-principal
    $role = $_SESSION['user_role'] ?? '';
    $currentDepotId = (int)($_SESSION['depot_id'] ?? 0);
    $restrictDepot = in_array($role, ['vendeur', 'comptable'], true) && $currentDepotId > 0 && !isMainDepot($currentDepotId);
    if ($roleCol) {
        if ($restrictDepot) {
            $st = $db->prepare("SELECT id, full_name FROM users WHERE $roleCol IN ('livreur','commercial') AND depot_id=? ORDER BY full_name");
            $st->execute([$currentDepotId]);
        } else {
            $st = $db->prepare("SELECT id, full_name FROM users WHERE $roleCol IN ('livreur','commercial') ORDER BY full_name");
            $st->execute();
        }
    } else {
        // Fallback: pas de colonne rôle → filtrer éventuellement par dépôt
        if ($restrictDepot) {
            $st = $db->prepare("SELECT id, full_name FROM users WHERE depot_id=? ORDER BY full_name");
            $st->execute([$currentDepotId]);
        } else {
            $st = $db->query("SELECT id, full_name FROM users ORDER BY full_name");
        }
    }
    $livreurs = $st->fetchAll(PDO::FETCH_ASSOC);
}

function scalar(PDO $db, $sql, $p = [])
{
    $s = $db->prepare($sql);
    $s->execute($p);
    $r = $s->fetch(PDO::FETCH_NUM);
    return $r ? (float)$r[0] : 0;
}

// Panier reçu (loads)
$loadHeader = $db->prepare("SELECT l.*, d.nom as depot_nom, u.full_name as livreur_nom
    FROM livreur_loads l JOIN depots d ON l.depot_id=d.id JOIN users u ON l.livreur_id=u.id
    WHERE l.livreur_id=? AND l.date_load=? ORDER BY l.id DESC LIMIT 1");
$loadHeader->execute([$livreurId, $selectedDate]);
$load = $loadHeader->fetch(PDO::FETCH_ASSOC);
$loadItems = [];
if ($load) {
    $it = $db->prepare("SELECT i.*, p.nom as produit_nom, c.nom as client_nom FROM livreur_load_items i JOIN products p ON i.produit_id=p.id LEFT JOIN clients c ON i.client_id=c.id WHERE i.load_id=? ORDER BY p.nom");
    $it->execute([$load['id']]);
    $loadItems = $it->fetchAll(PDO::FETCH_ASSOC);
}

// Total livré (ventes livrées ce jour)
// on privilégie delivery_date si dispo, sinon DATE(updated_at) ou DATE(created_at)
$hasDeliveryDate = scalar($db, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='delivery_date'") > 0;
$livreDateExpr = $hasDeliveryDate ? 'DATE(v.delivery_date)' : 'DATE(v.updated_at)';
$sumLivres = $db->prepare("SELECT c.nom as client, v.numero_vente, v.montant_total
    FROM ventes v JOIN clients c ON v.client_id=c.id
    WHERE v.livreur_id=? AND $livreDateExpr = ?");
$sumLivres->execute([$livreurId, $selectedDate]);
$livraisons = $sumLivres->fetchAll(PDO::FETCH_ASSOC);
$totLivraisons = array_sum(array_map(fn($r) => (float)$r['montant_total'], $livraisons));

// Total vendu (soldé) et partiel
$venteDateExpr = 'DATE(v.created_at)';
$venduStmt = $db->prepare("SELECT c.nom as client, v.numero_vente, v.montant_total, v.montant_paye
    FROM ventes v JOIN clients c ON v.client_id=c.id WHERE v.livreur_id=? AND $venteDateExpr=?");
$venduStmt->execute([$livreurId, $selectedDate]);
$ventesJour = $venduStmt->fetchAll(PDO::FETCH_ASSOC);
$venduTotal = 0;
$venduPartiel = 0;
$venduList = [];
$partielList = [];
foreach ($ventesJour as $v) {
    if ((float)$v['montant_paye'] >= (float)$v['montant_total']) {
        $venduTotal += (float)$v['montant_total'];
        $venduList[] = $v;
    } elseif ((float)$v['montant_paye'] > 0) {
        $venduPartiel += (float)$v['montant_paye'];
        $partielList[] = $v;
    }
}

// Crédit restant (toutes ventes du livreur non soldées, à date ou global?) -> journalier demandé
$creditStmt = $db->prepare("SELECT c.nom as client, v.numero_vente, (v.montant_total - v.montant_paye) as restant
    FROM ventes v JOIN clients c ON v.client_id=c.id WHERE v.livreur_id=? AND $venteDateExpr=? AND v.montant_paye < v.montant_total");
$creditStmt->execute([$livreurId, $selectedDate]);
$credits = $creditStmt->fetchAll(PDO::FETCH_ASSOC);
$creditRestant = array_sum(array_map(fn($r) => max(0, (float)$r['restant']), $credits));

// Versements du livreur (remittances) du jour
$remitStmt = $db->prepare("SELECT * FROM livreur_remittances WHERE livreur_id=? AND date_remit=? ORDER BY id DESC");
$remitStmt->execute([$livreurId, $selectedDate]);
$remittances = $remitStmt->fetchAll(PDO::FETCH_ASSOC);
$totalRemis = array_sum(array_map(fn($r) => (float)$r['amount'], $remittances));

// Paiements reçus par le livreur (via ses ventes) à la date (statut valide)
$payStmt = $db->prepare("SELECT COALESCE(SUM(p.montant),0) as s FROM payments p JOIN ventes v ON p.vente_id=v.id WHERE v.livreur_id=? AND p.statut='valide' AND p.date_payment=?");
$payStmt->execute([$livreurId, $selectedDate]);
$paymentsByLivreur = (float)$payStmt->fetch(PDO::FETCH_ASSOC)['s'];
$ecartPV = $paymentsByLivreur - $totalRemis;

// Colonne statut des paniers disponible ?
$hasLoadStatus = (int)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='status'")->fetchColumn() > 0;

// Enregistrement form (load/remit)
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['action'] ?? '') === 'create_remit' && hasPermission('payments_create')) {
            $amount = (float)($_POST['amount'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            if ($amount <= 0) throw new Exception('Montant invalide');
            $st = $db->prepare("INSERT INTO livreur_remittances (livreur_id,depot_id,date_remit,amount,notes,created_by) VALUES (?,?,?,?,?,?)");
            $st->execute([$livreurId, $_SESSION['depot_id'] ?? 0, $selectedDate, $amount, $notes, $_SESSION['user_id'] ?? null]);
            setFlashMessage('success', 'Versement enregistré');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
        if (($_POST['action'] ?? '') === 'close_tour') {
            // Clôturer la tournée: calculer le restant (panier validé - ventes du jour), ré-incrémenter le stock, auditer et marquer fermé
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['vendeur', 'gerant', 'admin', 'comptable'], true)) throw new Exception('Non autorisé à clôturer');
            // Identifier le load à clôturer
            $loadId = isset($_POST['load_id']) ? (int)$_POST['load_id'] : 0;
            if (!$loadId && $livreurId) {
                $q = $db->prepare("SELECT id FROM livreur_loads WHERE livreur_id=? AND date_load=? ORDER BY id DESC LIMIT 1");
                $q->execute([$livreurId, $selectedDate]);
                $row = $q->fetch(PDO::FETCH_ASSOC);
                if ($row) $loadId = (int)$row['id'];
            }
            if (!$loadId) throw new Exception('Aucun panier trouvé pour clôture');

            $db->beginTransaction();
            // Verrouiller le load et vérifier statut
            $stLoad = $db->prepare("SELECT id, depot_id, status FROM livreur_loads WHERE id=? FOR UPDATE");
            $stLoad->execute([$loadId]);
            $lr = $stLoad->fetch(PDO::FETCH_ASSOC);
            if (!$lr) throw new Exception('Panier introuvable');
            $depot_id = (int)$lr['depot_id'];
            $status = $lr['status'] ?? 'draft';
            if ($status !== 'validated') throw new Exception('Le panier doit être validé avant clôture');

            // Quantités chargées par produit
            $loadItemsQ = $db->prepare("SELECT produit_id, SUM(quantite) q FROM livreur_load_items WHERE load_id=? GROUP BY produit_id");
            $loadItemsQ->execute([$loadId]);
            $loadMap = [];
            foreach ($loadItemsQ->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $loadMap[(int)$it['produit_id']] = (float)$it['q'];
            }

            // Quantités vendues par produit pour ce livreur et ce jour
            $soldQ = $db->prepare("SELECT i.produit_id, COALESCE(SUM(i.quantite),0) q
                                   FROM vente_items i JOIN ventes v ON i.vente_id=v.id
                                   WHERE v.livreur_id=? AND DATE(v.created_at)=?
                                   GROUP BY i.produit_id");
            $soldQ->execute([$livreurId, $selectedDate]);
            $soldMap = [];
            foreach ($soldQ->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $soldMap[(int)$s['produit_id']] = (float)$s['q'];
            }

            // Calcul du restant par produit (>=0)
            $returns = [];
            foreach ($loadMap as $pid => $qLoad) {
                $qSold = $soldMap[$pid] ?? 0.0;
                $qRet = $qLoad - $qSold;
                if ($qRet > 0) {
                    $returns[$pid] = $qRet;
                }
            }

            // Ré-incrémenter le stock pour chaque retour
            if ($returns) {
                $selStock = $db->prepare('SELECT id, quantite FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
                $updStock = $db->prepare('UPDATE stock SET quantite=quantite+? WHERE id=?');
                $insStock = $db->prepare('INSERT INTO stock (produit_id,depot_id,quantite) VALUES (?,?,?)');
                foreach ($returns as $pid => $qRet) {
                    $selStock->execute([(int)$pid, $depot_id]);
                    $sr = $selStock->fetch(PDO::FETCH_ASSOC);
                    if ($sr) {
                        $updStock->execute([$qRet, (int)$sr['id']]);
                    } else {
                        $insStock->execute([(int)$pid, $depot_id, $qRet]);
                    }
                }
            }

            // Marquer le load comme ‘closed’
            $db->prepare("UPDATE livreur_loads SET status='closed', closed_by=?, closed_at=NOW(), updated_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'] ?? null, $loadId]);

            // Audit snapshot des retours
            try {
                $db->prepare("INSERT INTO livreur_load_audits (load_id, action, items_json, notes, user_id) VALUES (?,?,?,?,?)")
                    ->execute([$loadId, 'close', json_encode($returns, JSON_UNESCAPED_UNICODE), trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
            } catch (Exception $e) {
                // silencieux
            }

            $db->commit();
            setFlashMessage('success', 'Tournée clôturée, retours réintégrés en stock');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
        if (($_POST['action'] ?? '') === 'save_load') {
            // Enregistrer/mettre à jour un panier en brouillon (sans impacter le stock)
            $depot_id = (int)($_POST['depot_id'] ?? ($_SESSION['depot_id'] ?? 0));
            $items = json_decode($_POST['items_json'] ?? '[]', true) ?: [];
            if ($depot_id <= 0 || !$items) throw new Exception('Données incomplètes');
            // Autorisations: livreur (pour lui-même) ou vendeur/gerant/admin
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['livreur', 'commercial', 'vendeur', 'gerant', 'admin'], true)) throw new Exception('Non autorisé');
            if (in_array($role, ['livreur', 'commercial'], true) && ($livreurId !== (int)($_SESSION['user_id'] ?? 0))) throw new Exception('Non autorisé');

            $db->beginTransaction();
            // Chercher un brouillon existant pour ce jour
            $loadId = null;
            if ($hasLoadStatus) {
                $q = $db->prepare("SELECT id FROM livreur_loads WHERE livreur_id=? AND date_load=? AND status='draft' ORDER BY id DESC LIMIT 1");
            } else {
                $q = $db->prepare("SELECT id FROM livreur_loads WHERE livreur_id=? AND date_load=? ORDER BY id DESC LIMIT 1");
            }
            $q->execute([$livreurId, $selectedDate]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $loadId = (int)$row['id'];
                // Mettre à jour depot/notes
                if ($hasLoadStatus) {
                    $db->prepare("UPDATE livreur_loads SET depot_id=?, notes=?, updated_at=NOW() WHERE id=? AND (status='draft' OR status IS NULL)")
                        ->execute([$depot_id, trim($_POST['notes'] ?? ''), $loadId]);
                } else {
                    $db->prepare("UPDATE livreur_loads SET depot_id=?, notes=? WHERE id=?")
                        ->execute([$depot_id, trim($_POST['notes'] ?? ''), $loadId]);
                }
                $db->prepare("DELETE FROM livreur_load_items WHERE load_id=?")->execute([$loadId]);
            } else {
                if ($hasLoadStatus) {
                    $db->prepare("INSERT INTO livreur_loads (livreur_id,depot_id,date_load,status,notes,created_by) VALUES (?,?,?,?,?,?)")
                        ->execute([$livreurId, $depot_id, $selectedDate, 'draft', trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
                } else {
                    $db->prepare("INSERT INTO livreur_loads (livreur_id,depot_id,date_load,notes,created_by) VALUES (?,?,?,?,?)")
                        ->execute([$livreurId, $depot_id, $selectedDate, trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
                }
                $loadId = (int)$db->lastInsertId();
            }
            $ins = $db->prepare("INSERT INTO livreur_load_items (load_id,produit_id,quantite,client_id,client_lat,client_lng) VALUES (?,?,?,?,?,?)");
            foreach ($items as $it) {
                $pid = (int)($it['produit_id'] ?? 0);
                $q = (float)($it['quantite'] ?? 0);
                if ($pid && $q > 0) {
                    $cid = isset($it['client_id']) ? (int)$it['client_id'] : null;
                    $clat = isset($it['client_lat']) ? (float)$it['client_lat'] : null;
                    $clng = isset($it['client_lng']) ? (float)$it['client_lng'] : null;
                    $ins->execute([$loadId, $pid, $q, $cid, $clat, $clng]);
                }
            }
            // Audit snapshot du brouillon
            try {
                $aud = $db->prepare("INSERT INTO livreur_load_audits (load_id, action, items_json, notes, user_id) VALUES (?,?,?,?,?)");
                $aud->execute([$loadId, 'save', json_encode($items, JSON_UNESCAPED_UNICODE), trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
            } catch (Exception $e) {
                // ne pas bloquer si audit indisponible
            }
            $db->commit();
            setFlashMessage('success', 'Panier enregistré en brouillon');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }

        if (($_POST['action'] ?? '') === 'validate_load') {
            // Valider le panier: décrémenter le stock et marquer validé
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['vendeur', 'gerant', 'admin', 'comptable'], true)) throw new Exception('Non autorisé à valider');
            // Recharger le dernier load du jour (ou via load_id)
            $loadId = isset($_POST['load_id']) ? (int)$_POST['load_id'] : 0;
            if (!$loadId) {
                $q = $db->prepare("SELECT id FROM livreur_loads WHERE livreur_id=? AND date_load=? ORDER BY id DESC LIMIT 1");
                $q->execute([$livreurId, $selectedDate]);
                $row = $q->fetch(PDO::FETCH_ASSOC);
                if ($row) $loadId = (int)$row['id'];
            }
            if (!$loadId) throw new Exception('Aucun panier à valider');
            // Si status existe, ne valider que les brouillons
            if ($hasLoadStatus) {
                $stt = $db->prepare("SELECT status,depot_id FROM livreur_loads WHERE id=?");
                $stt->execute([$loadId]);
                $lr = $stt->fetch(PDO::FETCH_ASSOC);
                if (!$lr) throw new Exception('Panier introuvable');
                if (($lr['status'] ?? 'draft') !== 'draft') throw new Exception('Panier déjà validé');
                $depot_id = (int)$lr['depot_id'];
            } else {
                $depot_id = (int)($_POST['depot_id'] ?? ($_SESSION['depot_id'] ?? 0));
            }
            // Décrémenter stock pour les items du load
            $db->beginTransaction();
            // Récupérer items pour décrémenter le stock et auditer
            $itemsStmt = $db->prepare("SELECT produit_id, quantite, client_id, client_lat, client_lng FROM livreur_load_items WHERE load_id=?");
            $itemsStmt->execute([$loadId]);
            $itemsData = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            $selStock = $db->prepare('SELECT id, quantite FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
            $updStock = $db->prepare('UPDATE stock SET quantite = quantite - ? WHERE id=?');
            foreach ($itemsData as $it) {
                $pid = (int)$it['produit_id'];
                $q = (float)$it['quantite'];
                if ($pid && $q > 0) {
                    $selStock->execute([$pid, $depot_id]);
                    $sr = $selStock->fetch(PDO::FETCH_ASSOC);
                    if (!$sr) throw new Exception('Stock indisponible pour le produit ID ' . $pid . ' au dépôt');
                    if (((float)$sr['quantite']) < $q) throw new Exception('Stock insuffisant pour le produit ID ' . $pid . ' (disponible: ' . ((float)$sr['quantite']) . ')');
                    $updStock->execute([$q, (int)$sr['id']]);
                }
            }
            if ($hasLoadStatus) {
                $db->prepare("UPDATE livreur_loads SET status='validated', validated_by=?, validated_at=NOW(), updated_at=NOW() WHERE id=?")
                    ->execute([$_SESSION['user_id'] ?? null, $loadId]);
            }
            // Audit snapshot de la validation
            try {
                // Récupérer notes courantes du load
                $noteRow = $db->prepare("SELECT notes FROM livreur_loads WHERE id=?");
                $noteRow->execute([$loadId]);
                $notes = ($noteRow->fetch(PDO::FETCH_ASSOC)['notes'] ?? null);
                $aud = $db->prepare("INSERT INTO livreur_load_audits (load_id, action, items_json, notes, user_id) VALUES (?,?,?,?,?)");
                $aud->execute([$loadId, 'validate', json_encode($itemsData, JSON_UNESCAPED_UNICODE), $notes, $_SESSION['user_id'] ?? null]);
            } catch (Exception $e) {
                // ne pas bloquer si audit indisponible
            }
            $db->commit();
            setFlashMessage('success', 'Panier validé');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
    } catch (Exception $e) {
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
        if ($db->inTransaction()) $db->rollBack();
    }
}

$pageTitle = 'Etat journalier Livreur';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-user-check"></i> Etat journalier Livreur</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" /></div>
        <?php if (!in_array($_SESSION['user_role'] ?? '', ['livreur', 'commercial'], true)): ?>
            <div class="col-md-4"><select class="form-select" name="livreur_id" required>
                    <option value="">— Sélectionner livreur —</option>
                    <?php foreach ($livreurs as $l): ?><option value="<?= (int)$l['id'] ?>" <?= $livreurId === (int)$l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?>
                </select></div>
        <?php endif; ?>
        <div class="col-md-2"><button class="btn w-100">Voir</button></div>
    </form>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-shopping-basket"></i></div>
        <div class="value"><?= number_format(array_sum(array_map(fn($i) => (float)$i['quantite'], $loadItems)), 0, ',', ' ') ?></div>
        <div class="label">Panier reçu (Qté)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-truck"></i></div>
        <div class="value"><?= number_format($totLivraisons, 0, ',', ' ') ?></div>
        <div class="label">Total livré (FCFA)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <div class="value"><?= number_format($venduTotal, 0, ',', ' ') ?></div>
        <div class="label">Total vendu (soldé)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <div class="value"><?= number_format($creditRestant, 0, ',', ' ') ?></div>
        <div class="label">Crédit restant</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="value"><?= number_format($totalRemis, 0, ',', ' ') ?></div>
        <div class="label">Versements du jour</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-coins"></i></div>
        <div class="value"><?= number_format($paymentsByLivreur ?? 0, 0, ',', ' ') ?></div>
        <div class="label">Paiements reçus (jour)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-balance-scale-right"></i></div>
        <div class="value"><?= number_format(($ecartPV ?? 0), 0, ',', ' ') ?></div>
        <div class="label">Ecart reçus - versés</div>
    </div>
</div>

<div class="card">
    <h2>Panier reçu</h2>
    <?php if ($load): ?>
        <p><strong>Dépôt:</strong> <?= htmlspecialchars($load['depot_nom']) ?> — <strong>Livreur:</strong> <?= htmlspecialchars($load['livreur_nom']) ?></p>
        <?php if ($hasLoadStatus && (($load['status'] ?? 'draft') === 'validated')): ?>
            <form method="post" class="mt-2 mb-2">
                <input type="hidden" name="action" value="close_tour" />
                <input type="hidden" name="load_id" value="<?= (int)$load['id'] ?>" />
                <button class="btn btn-warning" type="submit"><i class="fas fa-undo"></i> Clôturer la tournée (retour stock)</button>
            </form>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-end">Qté</th>
                    <th>Client</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loadItems as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['produit_nom']) ?></td>
                        <td class="text-end"><?= number_format($it['quantite'], 0, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($it['client_nom'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$loadItems): ?><tr>
                        <td colspan="3">—</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>— Aucun panier enregistré pour ce jour.</p>
    <?php endif; ?>

    <?php
    $role = $_SESSION['user_role'] ?? '';
    $canEditDraft = in_array($role, ['livreur', 'commercial', 'vendeur', 'gerant', 'admin'], true) && $livreurId;
    $canValidate = in_array($role, ['vendeur', 'gerant', 'admin', 'comptable'], true) && $livreurId;
    if ($canEditDraft): ?>
        <hr />
        <h3>Enregistrer un panier</h3>
        <?php
        // Préparer les listes de sélection (produits, clients)
        try {
            $productsList = $db->query("SELECT id, nom FROM products ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $productsList = [];
        }
        try {
            $clientsList = $db->query("SELECT id, nom FROM clients ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $clientsList = [];
        }
        ?>
        <div id="options_cache" class="d-none">
            <select id="product_options">
                <option value="">— Sélectionner produit —</option>
                <?php foreach ($productsList as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="client_options">
                <option value="">— Sélectionner client —</option>
                <?php foreach ($clientsList as $cl): ?>
                    <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <form method="post" class="mb-3" id="load-form">
            <input type="hidden" name="action" value="save_load" />
            <input type="hidden" name="items_json" id="items_json" value="[]" />
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label">Dépôt</label><input class="form-control" name="depot_id" value="<?= (int)($_SESSION['depot_id'] ?? 0) ?>" required /></div>
                <div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" name="notes" /></div>
            </div>
            <div class="mt-2">
                <div id="items_area" class="row g-2"></div>
                <button class="btn btn-secondary mt-2" type="button" data-add-item>+ Ajouter un produit</button>
            </div>
            <div class="mt-3"><button class="btn btn-success" type="submit">Enregistrer le panier</button></div>
        </form>
        <?php if ($canValidate && $load): ?>
            <form method="post" class="mt-2">
                <input type="hidden" name="action" value="validate_load" />
                <input type="hidden" name="load_id" value="<?= (int)$load['id'] ?>" />
                <button class="btn">Valider le panier</button>
            </form>
        <?php endif; ?>
        <script src="<?= ASSETS_URL ?>/js/livreur_load.js" defer></script>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Total livré (clients)</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Facture</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($livraisons as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td><?= htmlspecialchars($r['numero_vente']) ?></td>
                    <td class="text-end"><?= number_format($r['montant_total'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$livraisons): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Ventes du jour</h2>
    <h3>Soldées</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Facture</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($venduList as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td><?= htmlspecialchars($r['numero_vente']) ?></td>
                    <td class="text-end"><?= number_format($r['montant_total'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$venduList): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
    <h3>Partiellement payées</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Facture</th>
                <th class="text-end">Payé</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partielList as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td><?= htmlspecialchars($r['numero_vente']) ?></td>
                    <td class="text-end"><?= number_format($r['montant_paye'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$partielList): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Crédit restant (clients)</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Facture</th>
                <th class="text-end">Reste</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($credits as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td><?= htmlspecialchars($r['numero_vente']) ?></td>
                    <td class="text-end"><?= number_format($r['restant'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$credits): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Versements du jour</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Heure</th>
                <th class="text-end">Montant</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($remittances as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td class="text-end"><?= number_format($r['amount'], 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($r['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$remittances): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
    <?php if (hasPermission('payments_create') && $livreurId): ?>
        <form method="post" class="row g-2 mt-2">
            <input type="hidden" name="action" value="create_remit" />
            <div class="col-md-3"><label class="form-label">Montant</label><input class="form-control" type="number" step="0.01" name="amount" required /></div>
            <div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" name="notes" /></div>
            <div class="col-md-3"><label class="form-label">&nbsp;</label><button class="btn w-100">Enregistrer</button></div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php';
