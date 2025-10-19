<?php

/**
 * Ajout d'utilisateurs pour tous les rôles
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Ajout d'utilisateurs multi-rôles</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Connexion impossible");
    }

    // Ajouter des utilisateurs pour chaque rôle
    $users = [
        ['vendeur1', 'vendeur@hill.com', 'vendeur123', 'Jean Vendeur', 'vendeur'],
        ['livreur1', 'livreur@hill.com', 'livreur123', 'Pierre Livreur', 'livreur'],
        ['comptable1', 'comptable@hill.com', 'comptable123', 'Marie Comptable', 'comptable'],
        ['vendeur2', 'vendeur2@hill.com', 'vendeur123', 'Fatou Vendeuse', 'vendeur'],
        ['livreur2', 'livreur2@hill.com', 'livreur123', 'Mamadou Livreur', 'livreur']
    ];

    foreach ($users as $user) {
        $hashedPassword = password_hash($user[2], PASSWORD_DEFAULT);

        // Vérifier si l'utilisateur existe déjà
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$user[1]]);

        if ($checkStmt->rowCount() == 0) {
            $sql = "INSERT INTO users (username, email, password, full_name, role, depot_id) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user[0], $user[1], $hashedPassword, $user[3], $user[4]]);
            echo "<p class='success'>✓ Utilisateur {$user[4]} créé : {$user[1]} / {$user[2]}</p>\n";
        } else {
            echo "<p>⚠ Utilisateur {$user[1]} existe déjà</p>\n";
        }
    }

    echo "<hr><h3 class='success'>Comptes créés avec succès !</h3>\n";
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>\n";
    echo "<tr style='background:#FFD700;'><th style='padding:10px;'>Rôle</th><th style='padding:10px;'>Email</th><th style='padding:10px;'>Mot de passe</th><th style='padding:10px;'>Actions</th></tr>\n";
    echo "<tr><td style='padding:8px;'>Admin</td><td style='padding:8px;'>admin@hill.com</td><td style='padding:8px;'>admin123</td><td style='padding:8px;'>Gestion complète</td></tr>\n";
    echo "<tr><td style='padding:8px;'>Vendeur</td><td style='padding:8px;'>vendeur@hill.com</td><td style='padding:8px;'>vendeur123</td><td style='padding:8px;'>Ventes, Clients</td></tr>\n";
    echo "<tr><td style='padding:8px;'>Livreur</td><td style='padding:8px;'>livreur@hill.com</td><td style='padding:8px;'>livreur123</td><td style='padding:8px;'>Livraisons, Stock</td></tr>\n";
    echo "<tr><td style='padding:8px;'>Comptable</td><td style='padding:8px;'>comptable@hill.com</td><td style='padding:8px;'>comptable123</td><td style='padding:8px;'>Paiements, Rapports</td></tr>\n";
    echo "</table>\n";
} catch (Exception $e) {
    echo "<p class='error'>Erreur : " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='../app/login.php'>→ Accéder à l'application dynamique</a></p>\n";
