<?php

/**
 * API Ventes pour HILL EMBALLAGE
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Sale.php';

// Création de la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Création de l'objet Sale
$sale = new Sale($db);

// Obtenir la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Router selon la méthode HTTP
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Lire une vente spécifique
            $sale->id = $_GET['id'];
            $sale_data = $sale->readOne();

            if ($sale_data) {
                http_response_code(200);
                echo json_encode($sale_data);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Vente non trouvée."));
            }
        } elseif (isset($_GET['client_id'])) {
            // Obtenir les ventes d'un client
            $stmt = $sale->getByClient($_GET['client_id']);
            $ventes = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ventes[] = $row;
            }

            http_response_code(200);
            echo json_encode($ventes);
        } elseif (isset($_GET['depot_id'])) {
            // Obtenir les ventes d'un dépôt
            $stmt = $sale->getByDepot($_GET['depot_id']);
            $ventes = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ventes[] = $row;
            }

            http_response_code(200);
            echo json_encode($ventes);
        } elseif (isset($_GET['stats'])) {
            // Obtenir les statistiques
            $depot_id = $_GET['depot_id'] ?? null;
            $date_debut = $_GET['date_debut'] ?? null;
            $date_fin = $_GET['date_fin'] ?? null;

            $stats = $sale->getStats($depot_id, $date_debut, $date_fin);

            http_response_code(200);
            echo json_encode($stats);
        } elseif (isset($_GET['top_products'])) {
            // Obtenir le top des produits
            $limit = $_GET['limit'] ?? 10;
            $depot_id = $_GET['depot_id'] ?? null;

            $stmt = $sale->getTopProducts($limit, $depot_id);
            $top_products = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $top_products[] = $row;
            }

            http_response_code(200);
            echo json_encode($top_products);
        } else {
            // Lire toutes les ventes
            $stmt = $sale->read();
            $ventes = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ventes[] = $row;
            }

            http_response_code(200);
            echo json_encode($ventes);
        }
        break;

    case 'POST':
        // Créer une nouvelle vente
        if (
            !empty($input['client_id']) && !empty($input['depot_id']) &&
            !empty($input['vendeur_id']) && !empty($input['details'])
        ) {

            try {
                $sale->client_id = $input['client_id'];
                $sale->depot_id = $input['depot_id'];
                $sale->vendeur_id = $input['vendeur_id'];
                $sale->type_vente = $input['type_vente'] ?? 'comptant';
                $sale->montant_total = $input['montant_total'];
                $sale->montant_paye = $input['montant_paye'] ?? $input['montant_total'];
                $sale->montant_restant = $input['montant_total'] - ($input['montant_paye'] ?? $input['montant_total']);
                $sale->date_echeance = $input['date_echeance'] ?? null;
                $sale->notes = $input['notes'] ?? '';
                $sale->details = $input['details'];

                // Définir le statut selon le paiement
                if ($sale->montant_restant == 0) {
                    $sale->statut = 'solde';
                } elseif ($sale->montant_paye > 0) {
                    $sale->statut = 'partiel';
                } else {
                    $sale->statut = 'en_cours';
                }

                $sale_id = $sale->create();

                if ($sale_id) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Vente créée avec succès.",
                        "id" => $sale_id,
                        "numero_vente" => $sale->numero_vente
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Impossible de créer la vente."));
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

    case 'PUT':
        // Mettre à jour le statut d'une vente
        if (isset($_GET['id']) && isset($input['statut'])) {
            $sale->id = $_GET['id'];

            if ($sale->updateStatus($input['statut'])) {
                http_response_code(200);
                echo json_encode(array("message" => "Statut de la vente mis à jour."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de mettre à jour la vente."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données manquantes."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée."));
        break;
}
