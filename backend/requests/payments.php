<?php

/**
 * API Paiements pour HILL EMBALLAGE
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Payment.php';

// Création de la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Création de l'objet Payment
$payment = new Payment($db);

// Obtenir la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Router selon la méthode HTTP
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Lire un paiement spécifique
            $payment->id = $_GET['id'];
            $payment_data = $payment->readOne();

            if ($payment_data) {
                http_response_code(200);
                echo json_encode($payment_data);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Paiement non trouvé."));
            }
        } elseif (isset($_GET['sale_id'])) {
            // Obtenir les paiements d'une vente
            $stmt = $payment->getBySale($_GET['sale_id']);
            $paiements = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $paiements[] = $row;
            }

            http_response_code(200);
            echo json_encode($paiements);
        } elseif (isset($_GET['client_id'])) {
            // Obtenir les paiements d'un client
            $stmt = $payment->getByClient($_GET['client_id']);
            $paiements = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $paiements[] = $row;
            }

            http_response_code(200);
            echo json_encode($paiements);
        } elseif (isset($_GET['periode'])) {
            // Obtenir les paiements par période
            $date_debut = $_GET['date_debut'];
            $date_fin = $_GET['date_fin'];
            $receveur_id = $_GET['receveur_id'] ?? null;

            $stmt = $payment->getByPeriod($date_debut, $date_fin, $receveur_id);
            $paiements = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $paiements[] = $row;
            }

            http_response_code(200);
            echo json_encode($paiements);
        } elseif (isset($_GET['stats'])) {
            // Obtenir les statistiques des paiements
            $date_debut = $_GET['date_debut'] ?? null;
            $date_fin = $_GET['date_fin'] ?? null;
            $receveur_id = $_GET['receveur_id'] ?? null;

            $stats = $payment->getStats($date_debut, $date_fin, $receveur_id);

            http_response_code(200);
            echo json_encode($stats);
        } elseif (isset($_GET['rappels'])) {
            // Obtenir les rappels de paiement
            $stmt = $payment->getRappelsPaiement();
            $rappels = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rappels[] = $row;
            }

            http_response_code(200);
            echo json_encode($rappels);
        } else {
            // Lire tous les paiements
            $stmt = $payment->read();
            $paiements = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $paiements[] = $row;
            }

            http_response_code(200);
            echo json_encode($paiements);
        }
        break;

    case 'POST':
        // Créer un nouveau paiement
        if (
            !empty($input['sale_id']) && !empty($input['client_id']) &&
            !empty($input['montant']) && !empty($input['mode_paiement']) &&
            !empty($input['receveur_id'])
        ) {

            try {
                $payment->sale_id = $input['sale_id'];
                $payment->client_id = $input['client_id'];
                $payment->montant = $input['montant'];
                $payment->mode_paiement = $input['mode_paiement'];
                $payment->reference_externe = $input['reference_externe'] ?? '';
                $payment->notes = $input['notes'] ?? '';
                $payment->photo_preuve = $input['photo_preuve'] ?? '';
                $payment->receveur_id = $input['receveur_id'];

                $payment_id = $payment->create();

                if ($payment_id) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Paiement enregistré avec succès.",
                        "id" => $payment_id,
                        "numero_paiement" => $payment->numero_paiement
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Impossible d'enregistrer le paiement."));
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(array("message" => "Erreur: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes."));
        }
        break;

    case 'DELETE':
        // Supprimer un paiement
        if (isset($_GET['id'])) {
            try {
                $payment->id = $_GET['id'];

                if ($payment->delete()) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Paiement supprimé avec succès."));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Impossible de supprimer le paiement."));
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(array("message" => "Erreur: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID manquant."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée."));
        break;
}
