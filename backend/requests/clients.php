<?php

/**
 * API Clients pour HILL EMBALLAGE
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Client.php';

// Création de la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Création de l'objet Client
$client = new Client($db);

// Obtenir la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Router selon la méthode HTTP
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Lire un client spécifique
            $client->id = $_GET['id'];
            if ($client->readOne()) {
                $client_array = array(
                    "id" => $client->id,
                    "code_client" => $client->code_client,
                    "nom" => $client->nom,
                    "prenoms" => $client->prenoms,
                    "telephone" => $client->telephone,
                    "email" => $client->email,
                    "adresse" => $client->adresse,
                    "zone" => $client->zone,
                    "type_client" => $client->type_client,
                    "credit_limite" => $client->credit_limite,
                    "solde_credit" => $client->solde_credit,
                    "points_fidelite" => $client->points_fidelite
                );

                http_response_code(200);
                echo json_encode($client_array);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Client non trouvé."));
            }
        } elseif (isset($_GET['search'])) {
            // Rechercher des clients
            $keyword = $_GET['search'];
            $stmt = $client->search($keyword);
            $clients = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $clients[] = $row;
            }

            http_response_code(200);
            echo json_encode($clients);
        } elseif (isset($_GET['stats'])) {
            // Obtenir les statistiques
            $stats = $client->getStats();

            http_response_code(200);
            echo json_encode($stats);
        } else {
            // Lire tous les clients
            $stmt = $client->read();
            $clients = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $clients[] = $row;
            }

            http_response_code(200);
            echo json_encode($clients);
        }
        break;

    case 'POST':
        // Créer un nouveau client
        if (!empty($input['nom'])) {
            $client->nom = $input['nom'];
            $client->prenoms = $input['prenoms'] ?? '';
            $client->telephone = $input['telephone'] ?? '';
            $client->email = $input['email'] ?? '';
            $client->adresse = $input['adresse'] ?? '';
            $client->zone = $input['zone'] ?? '';
            $client->type_client = $input['type_client'] ?? 'particulier';
            $client->credit_limite = $input['credit_limite'] ?? 0;
            $client->created_by = $input['created_by'] ?? 1;

            $client_id = $client->create();

            if ($client_id) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Client créé avec succès.",
                    "id" => $client_id,
                    "code_client" => $client->code_client
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de créer le client."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes. Le nom est requis."));
        }
        break;

    case 'PUT':
        // Mettre à jour un client
        if (isset($_GET['id']) && !empty($input['nom'])) {
            $client->id = $_GET['id'];
            $client->nom = $input['nom'];
            $client->prenoms = $input['prenoms'] ?? '';
            $client->telephone = $input['telephone'] ?? '';
            $client->email = $input['email'] ?? '';
            $client->adresse = $input['adresse'] ?? '';
            $client->zone = $input['zone'] ?? '';
            $client->type_client = $input['type_client'] ?? 'particulier';
            $client->credit_limite = $input['credit_limite'] ?? 0;

            if ($client->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Client mis à jour avec succès."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de mettre à jour le client."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes."));
        }
        break;

    case 'DELETE':
        // Supprimer un client
        if (isset($_GET['id'])) {
            $client->id = $_GET['id'];

            if ($client->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Client supprimé avec succès."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de supprimer le client."));
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
