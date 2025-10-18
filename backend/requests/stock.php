<?php

/**
 * API Stock pour HILL EMBALLAGE
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Stock.php';

// Création de la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Création de l'objet Stock
$stock = new Stock($db);

// Obtenir la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Router selon la méthode HTTP
switch ($method) {
    case 'GET':
        if (isset($_GET['depot_id']) && isset($_GET['action']) && $_GET['action'] === 'by_depot') {
            // Lire le stock d'un dépôt spécifique
            $stmt = $stock->readByDepot($_GET['depot_id']);
            $stocks = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stocks[] = $row;
            }

            http_response_code(200);
            echo json_encode($stocks);
        } elseif (isset($_GET['alertes'])) {
            // Obtenir les alertes de stock bas
            $depot_id = $_GET['depot_id'] ?? null;
            $stmt = $stock->getAlertes($depot_id);
            $alertes = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $alertes[] = $row;
            }

            http_response_code(200);
            echo json_encode($alertes);
        } elseif (isset($_GET['stats'])) {
            // Obtenir les statistiques de stock
            $depot_id = $_GET['depot_id'] ?? null;
            $stats = $stock->getStats($depot_id);

            http_response_code(200);
            echo json_encode($stats);
        } elseif (isset($_GET['historique'])) {
            // Obtenir l'historique des mouvements
            $depot_id = $_GET['depot_id'];
            $product_id = $_GET['product_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;

            $stmt = $stock->getHistorique($depot_id, $product_id, $limit);
            $historique = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $historique[] = $row;
            }

            http_response_code(200);
            echo json_encode($historique);
        } else {
            // Lire tout le stock avec filtres optionnels
            $depot_id = $_GET['depot_id'] ?? null;
            $product_id = $_GET['product_id'] ?? null;
            $alert_only = isset($_GET['alert_only']) ? filter_var($_GET['alert_only'], FILTER_VALIDATE_BOOLEAN) : false;

            $stmt = $stock->read($depot_id, $product_id, $alert_only);
            $stocks = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stocks[] = $row;
            }

            http_response_code(200);
            echo json_encode($stocks);
        }
        break;

    case 'POST':
        if (isset($input['action'])) {
            try {
                switch ($input['action']) {
                    case 'upsert':
                        // Créer ou mettre à jour un stock
                        if (!empty($input['depot_id']) && !empty($input['product_id'])) {
                            $stock->depot_id = $input['depot_id'];
                            $stock->product_id = $input['product_id'];
                            $stock->quantite_disponible = $input['quantite_disponible'] ?? 0;
                            $stock->quantite_reservee = $input['quantite_reservee'] ?? 0;
                            $stock->seuil_alerte = $input['seuil_alerte'] ?? 10;

                            if ($stock->upsert()) {
                                http_response_code(201);
                                echo json_encode(array("message" => "Stock mis à jour avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible de mettre à jour le stock."));
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(array("message" => "Depot ID et Product ID requis."));
                        }
                        break;

                    case 'update_quantite':
                        // Mettre à jour une quantité
                        if (
                            !empty($input['depot_id']) && !empty($input['product_id']) &&
                            isset($input['quantite']) && isset($input['operation'])
                        ) {

                            if ($stock->updateQuantite(
                                $input['depot_id'],
                                $input['product_id'],
                                $input['quantite'],
                                $input['operation']
                            )) {
                                http_response_code(200);
                                echo json_encode(array("message" => "Quantité mise à jour avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible de mettre à jour la quantité."));
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(array("message" => "Données manquantes pour la mise à jour."));
                        }
                        break;

                    case 'reserver':
                        // Réserver du stock
                        if (!empty($input['depot_id']) && !empty($input['product_id']) && isset($input['quantite'])) {
                            if ($stock->reserverStock($input['depot_id'], $input['product_id'], $input['quantite'])) {
                                http_response_code(200);
                                echo json_encode(array("message" => "Stock réservé avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible de réserver le stock."));
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(array("message" => "Données manquantes pour la réservation."));
                        }
                        break;

                    case 'liberer':
                        // Libérer du stock réservé
                        if (!empty($input['depot_id']) && !empty($input['product_id']) && isset($input['quantite'])) {
                            if ($stock->libererStock($input['depot_id'], $input['product_id'], $input['quantite'])) {
                                http_response_code(200);
                                echo json_encode(array("message" => "Stock libéré avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible de libérer le stock."));
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(array("message" => "Données manquantes pour la libération."));
                        }
                        break;

                    case 'inventaire':
                        // Effectuer un inventaire
                        if (
                            !empty($input['depot_id']) && !empty($input['product_id']) &&
                            isset($input['nouvelle_quantite']) && !empty($input['user_id'])
                        ) {

                            $notes = $input['notes'] ?? '';

                            if ($stock->inventaire(
                                $input['depot_id'],
                                $input['product_id'],
                                $input['nouvelle_quantite'],
                                $input['user_id'],
                                $notes
                            )) {
                                http_response_code(200);
                                echo json_encode(array("message" => "Inventaire effectué avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible d'effectuer l'inventaire."));
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(array("message" => "Données manquantes pour l'inventaire."));
                        }
                        break;

                    default:
                        http_response_code(400);
                        echo json_encode(array("message" => "Action non reconnue."));
                        break;
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(array("message" => "Erreur: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Action manquante."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée."));
        break;
}
