<?php

/**
 * API Authentification pour HILL EMBALLAGE
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/User.php';

// Création de la connexion à la base de données
$database = new Database();

// Créer la base de données si elle n'existe pas
$database->createDatabase();

$db = $database->getConnection();

// Vérifier que la connexion est établie
if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Erreur de connexion à la base de données"
    ));
    exit();
}

// Création de l'objet User
$user = new User($db);

// Obtenir la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Router selon la méthode HTTP
switch ($method) {
    case 'POST':
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'login':
                    // Connexion utilisateur
                    if (!empty($input['username']) && !empty($input['password'])) {
                        $userData = $user->authenticate($input['username'], $input['password']);

                        if ($userData) {
                            // Génération d'un token simple (à améliorer avec JWT en production)
                            $token = base64_encode($userData['id'] . ':' . time() . ':' . $userData['role']);

                            http_response_code(200);
                            echo json_encode(array(
                                "message" => "Connexion réussie.",
                                "user" => $userData,
                                "token" => $token
                            ));
                        } else {
                            http_response_code(401);
                            echo json_encode(array("message" => "Identifiants incorrects."));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array("message" => "Username et mot de passe requis."));
                    }
                    break;

                case 'register':
                    // Inscription d'un nouvel utilisateur (admin seulement)
                    if (
                        !empty($input['username']) && !empty($input['email']) &&
                        !empty($input['password']) && !empty($input['full_name'])
                    ) {

                        // Vérifier si l'utilisateur existe déjà
                        if ($user->exists($input['username'], $input['email'])) {
                            http_response_code(409);
                            echo json_encode(array("message" => "Username ou email déjà utilisé."));
                            break;
                        }

                        $user->username = $input['username'];
                        $user->email = $input['email'];
                        $user->password = $input['password'];
                        $user->full_name = $input['full_name'];
                        $user->role = $input['role'] ?? 'vendeur';
                        $user->phone = $input['phone'] ?? '';
                        $user->depot_id = $input['depot_id'] ?? null;

                        $user_id = $user->create();

                        if ($user_id) {
                            http_response_code(201);
                            echo json_encode(array(
                                "message" => "Utilisateur créé avec succès.",
                                "user_id" => $user_id
                            ));
                        } else {
                            http_response_code(503);
                            echo json_encode(array("message" => "Impossible de créer l'utilisateur."));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array("message" => "Données incomplètes."));
                    }
                    break;

                case 'change_password':
                    // Changer le mot de passe
                    if (
                        !empty($input['user_id']) && !empty($input['current_password']) &&
                        !empty($input['new_password'])
                    ) {

                        // Vérifier l'ancien mot de passe
                        $userData = $user->authenticate($input['username'] ?? '', $input['current_password']);

                        if ($userData && $userData['id'] == $input['user_id']) {
                            $user->id = $input['user_id'];

                            if ($user->changePassword($input['new_password'])) {
                                http_response_code(200);
                                echo json_encode(array("message" => "Mot de passe changé avec succès."));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Impossible de changer le mot de passe."));
                            }
                        } else {
                            http_response_code(401);
                            echo json_encode(array("message" => "Mot de passe actuel incorrect."));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array("message" => "Données manquantes."));
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(array("message" => "Action non reconnue."));
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Action manquante."));
        }
        break;

    case 'GET':
        // Vérification de token et récupération du profil utilisateur
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);

            if (count($parts) >= 3) {
                $user_id = $parts[0];
                $timestamp = $parts[1];

                // Vérifier si le token n'est pas trop ancien (24h)
                if ((time() - $timestamp) < 86400) {
                    $user->id = $user_id;

                    if ($user->readOne()) {
                        $user_data = array(
                            "id" => $user->id,
                            "username" => $user->username,
                            "email" => $user->email,
                            "full_name" => $user->full_name,
                            "role" => $user->role,
                            "phone" => $user->phone,
                            "depot_id" => $user->depot_id
                        );

                        http_response_code(200);
                        echo json_encode($user_data);
                    } else {
                        http_response_code(404);
                        echo json_encode(array("message" => "Utilisateur non trouvé."));
                    }
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Token expiré."));
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Token invalide."));
            }
        } elseif (isset($_GET['users']) && isset($_GET['token'])) {
            // Lister tous les utilisateurs (admin seulement)
            $stmt = $user->read();
            $users = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $row;
            }

            http_response_code(200);
            echo json_encode($users);
        } elseif (isset($_GET['stats'])) {
            // Statistiques des utilisateurs
            $stats = $user->getStats();

            http_response_code(200);
            echo json_encode($stats);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Paramètres manquants."));
        }
        break;

    case 'PUT':
        // Mettre à jour un utilisateur
        if (isset($_GET['id']) && !empty($input['username']) && !empty($input['email'])) {
            $user->id = $_GET['id'];

            // Vérifier si username/email existe pour un autre utilisateur
            if ($user->exists($input['username'], $input['email'], $user->id)) {
                http_response_code(409);
                echo json_encode(array("message" => "Username ou email déjà utilisé par un autre utilisateur."));
                break;
            }

            $user->username = $input['username'];
            $user->email = $input['email'];
            $user->full_name = $input['full_name'];
            $user->role = $input['role'];
            $user->phone = $input['phone'] ?? '';
            $user->depot_id = $input['depot_id'] ?? null;

            if ($user->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Utilisateur mis à jour avec succès."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de mettre à jour l'utilisateur."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes."));
        }
        break;

    case 'DELETE':
        // Désactiver un utilisateur
        if (isset($_GET['id'])) {
            $user->id = $_GET['id'];

            if ($user->deactivate()) {
                http_response_code(200);
                echo json_encode(array("message" => "Utilisateur désactivé avec succès."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de désactiver l'utilisateur."));
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
