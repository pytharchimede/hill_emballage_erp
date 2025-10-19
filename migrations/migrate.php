<?php

/**
 * Migration directe pour HILL EMBALLAGE
 */

require_once __DIR__ . '/../backend/config/database.php';

// Vérifier le paramètre migrate ou forcer l'exécution
if (!isset($_GET['migrate']) && !isset($_GET['force'])) {
    echo "<h2>Migration HILL EMBALLAGE</h2>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .btn{background:#FFD700;color:black;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px;}</style>";
    echo "<p>Cliquez sur le bouton ci-dessous pour exécuter la migration :</p>";
    echo "<a href='?migrate=1' class='btn'>🚀 Créer les tables et données</a>";
    echo "<p><small>Ou accédez à : <code>create_tables.php?migrate=1</code></small></p>";
    exit;
}

echo "<h2>Exécution de la migration HILL EMBALLAGE</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    $database = new Database();

    // Créer la base de données si elle n'existe pas
    if ($database->createDatabase()) {
        echo "<p class='success'>✓ Base de données créée/vérifiée</p>\n";
    }

    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    echo "<p class='success'>✓ Connexion établie</p>\n";

    // Supprimer les tables existantes dans l'ordre inverse des dépendances
    $dropTables = [
        "DROP TABLE IF EXISTS fidelity_points",
        "DROP TABLE IF EXISTS stock_transfers",
        "DROP TABLE IF EXISTS payments",
        "DROP TABLE IF EXISTS vente_items",
        "DROP TABLE IF EXISTS ventes",
        "DROP TABLE IF EXISTS stock",
        "DROP TABLE IF EXISTS produits",
        "DROP TABLE IF EXISTS clients",
        "DROP TABLE IF EXISTS users",
        "DROP TABLE IF EXISTS depots"
    ];

    echo "<p class='info'>Suppression des tables existantes...</p>\n";
    foreach ($dropTables as $sql) {
        $conn->exec($sql);
    }
    echo "<p class='success'>✓ Tables existantes supprimées</p>\n";

    // Création des tables
    echo "<p class='info'>Création des nouvelles tables...</p>\n";

    // Table depots
    $sql = "CREATE TABLE depots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        adresse TEXT,
        responsable VARCHAR(100),
        telephone VARCHAR(20),
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table depots créée</p>\n";

    // Table users  
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'vendeur', 'livreur', 'comptable') DEFAULT 'vendeur',
        phone VARCHAR(20),
        depot_id INT,
        is_active BOOLEAN DEFAULT 1,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (depot_id) REFERENCES depots(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table users créée</p>\n";

    // Table clients
    $sql = "CREATE TABLE clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100),
        entreprise VARCHAR(100),
        telephone VARCHAR(20),
        email VARCHAR(100),
        adresse TEXT,
        type_client ENUM('particulier', 'entreprise') DEFAULT 'particulier',
        limite_credit DECIMAL(15,2) DEFAULT 0.00,
        solde_credit DECIMAL(15,2) DEFAULT 0.00,
        points_fidelite INT DEFAULT 0,
        date_derniere_visite DATE,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table clients créée</p>\n";

    // Table produits
    $sql = "CREATE TABLE produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        code_produit VARCHAR(50) UNIQUE NOT NULL,
        description TEXT,
        unite VARCHAR(20) DEFAULT 'pièce',
        prix_unitaire DECIMAL(10,2) NOT NULL,
        prix_credit DECIMAL(10,2),
        points_fidelite INT DEFAULT 1,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table produits créée</p>\n";

    // Table stock
    $sql = "CREATE TABLE stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produit_id INT NOT NULL,
        depot_id INT NOT NULL,
        quantite DECIMAL(10,2) DEFAULT 0.00,
        quantite_reservee DECIMAL(10,2) DEFAULT 0.00,
        seuill_alerte DECIMAL(10,2) DEFAULT 0.00,
        last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (produit_id) REFERENCES produits(id),
        FOREIGN KEY (depot_id) REFERENCES depots(id),
        UNIQUE KEY unique_produit_depot (produit_id, depot_id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table stock créée</p>\n";

    // Table ventes
    $sql = "CREATE TABLE ventes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        user_id INT NOT NULL,
        numero_vente VARCHAR(50) UNIQUE NOT NULL,
        date_vente DATE NOT NULL,
        type_vente ENUM('comptant', 'credit') DEFAULT 'comptant',
        montant_total DECIMAL(15,2) NOT NULL,
        montant_paye DECIMAL(15,2) DEFAULT 0.00,
        statut ENUM('en_attente', 'validee', 'livree', 'annulee') DEFAULT 'en_attente',
        date_echeance DATE NULL,
        commentaire TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table ventes créée</p>\n";

    // Table vente_items
    $sql = "CREATE TABLE vente_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vente_id INT NOT NULL,
        produit_id INT NOT NULL,
        nom_produit VARCHAR(100) NOT NULL,
        quantite DECIMAL(10,2) NOT NULL,
        prix_unitaire DECIMAL(10,2) NOT NULL,
        montant DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
        FOREIGN KEY (produit_id) REFERENCES produits(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table vente_items créée</p>\n";

    // Table payments
    $sql = "CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vente_id INT NOT NULL,
        numero_recu VARCHAR(50) UNIQUE NOT NULL,
        date_payment DATE NOT NULL,
        montant DECIMAL(15,2) NOT NULL,
        mode_payment ENUM('espece', 'cheque', 'virement', 'mobile') DEFAULT 'espece',
        statut ENUM('valide', 'attente', 'rejete') DEFAULT 'valide',
        reference VARCHAR(100),
        commentaire TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vente_id) REFERENCES ventes(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table payments créée</p>\n";

    // Table stock_transfers
    $sql = "CREATE TABLE stock_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produit_id INT NOT NULL,
        depot_source INT NOT NULL,
        depot_destination INT NOT NULL,
        quantite DECIMAL(10,2) NOT NULL,
        motif VARCHAR(255),
        date_transfer TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT NOT NULL,
        FOREIGN KEY (produit_id) REFERENCES produits(id),
        FOREIGN KEY (depot_source) REFERENCES depots(id),
        FOREIGN KEY (depot_destination) REFERENCES depots(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table stock_transfers créée</p>\n";

    // Table fidelity_points
    $sql = "CREATE TABLE fidelity_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        points INT NOT NULL,
        type_operation ENUM('earned', 'spent') NOT NULL,
        vente_id INT,
        description VARCHAR(255),
        date_operation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id),
        FOREIGN KEY (vente_id) REFERENCES ventes(id)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table fidelity_points créée</p>\n";

    // Table user_permissions (overrides de droits)
    $sql = "CREATE TABLE user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission VARCHAR(100) NOT NULL,
        allowed TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uniq_user_perm (user_id, permission),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ Table user_permissions créée</p>\n";

    echo "<hr><h3>Insertion des données de test</h3>\n";

    // Insérer les dépôts
    $sql = "INSERT INTO depots (nom, adresse, responsable, telephone) VALUES 
            ('Dépôt Principal', 'Zone Industrielle Abidjan', 'Kouassi Jean', '+225 07 12 34 56'),
            ('Dépôt Secondaire', 'Plateau Abidjan', 'Adjoua Marie', '+225 05 98 76 54')";
    $conn->exec($sql);
    echo "<p class='success'>✓ Dépôts insérés</p>\n";

    // Insérer l'utilisateur admin avec mot de passe hashé
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, full_name, role, depot_id) VALUES 
            ('admin', 'admin@hill.com', ?, 'Administrateur Hill', 'admin', 1)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$hashedPassword]);
    echo "<p class='success'>✓ Utilisateur admin créé (admin@hill.com / admin123)</p>\n";

    // Insérer des utilisateurs supplémentaires
    $vendeurPassword = password_hash('vendeur123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, full_name, role, depot_id) VALUES 
            ('vendeur1', 'vendeur@hill.com', ?, 'Jean Vendeur', 'vendeur', 1)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$vendeurPassword]);
    echo "<p class='success'>✓ Utilisateur vendeur créé (vendeur@hill.com / vendeur123)</p>\n";

    // Insérer des produits
    $sql = "INSERT INTO produits (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite) VALUES 
            ('Sac plastique 25x35', 'SAC_25_35', 'Sac plastique transparent 25x35cm', 'pièce', 50.00, 55.00, 1),
            ('Sac plastique 30x40', 'SAC_30_40', 'Sac plastique transparent 30x40cm', 'pièce', 75.00, 80.00, 1),
            ('Carton 30x30x30', 'CAR_30_30_30', 'Carton d\\'emballage 30x30x30cm', 'pièce', 500.00, 550.00, 2),
            ('Papier bulle 1m', 'BULLE_1M', 'Papier bulle largeur 1 mètre', 'mètre', 200.00, 220.00, 1),
            ('Adhésif transparent', 'ADH_TRANS', 'Rouleau adhésif transparent', 'pièce', 300.00, 330.00, 1)";
    $conn->exec($sql);
    echo "<p class='success'>✓ Produits insérés</p>\n";

    // Insérer du stock
    $sql = "INSERT INTO stock (produit_id, depot_id, quantite, seuill_alerte) VALUES 
            (1, 1, 1000.00, 100.00),
            (2, 1, 800.00, 80.00),
            (3, 1, 200.00, 50.00),
            (4, 1, 500.00, 100.00),
            (5, 1, 300.00, 50.00),
            (1, 2, 500.00, 50.00),
            (2, 2, 400.00, 40.00)";
    $conn->exec($sql);
    echo "<p class='success'>✓ Stock initial inséré</p>\n";

    // Insérer des clients
    $sql = "INSERT INTO clients (nom, prenom, entreprise, telephone, email, type_client, limite_credit) VALUES 
            ('Kouame', 'Akissi', null, '+225 07 11 22 33', 'akissi@gmail.com', 'particulier', 100000.00),
            ('Traore', 'Mamadou', 'SARL TRAORE', '+225 05 44 55 66', 'traore@sarl.com', 'entreprise', 500000.00),
            ('Diabate', 'Fatou', null, '+225 01 77 88 99', 'fatou@yahoo.fr', 'particulier', 50000.00)";
    $conn->exec($sql);
    echo "<p class='success'>✓ Clients insérés</p>\n";

    echo "<hr><h3 class='success'>🎉 Migration terminée avec succès !</h3>\n";
    echo "<p><strong>Vous pouvez maintenant :</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Vous connecter sur l'interface web : <a href='../web_admin/login.php' target='_blank'>Interface Web Admin</a></li>\n";
    echo "<li>✅ Tester l'API d'authentification</li>\n";
    echo "<li>✅ Utiliser l'application mobile Flutter</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Identifiants de test :</strong></p>\n";
    echo "<table border='1' style='border-collapse:collapse; padding:10px;'>\n";
    echo "<tr><th style='padding:8px; background:#FFD700;'>Rôle</th><th style='padding:8px; background:#FFD700;'>Email</th><th style='padding:8px; background:#FFD700;'>Mot de passe</th></tr>\n";
    echo "<tr><td style='padding:8px;'>Admin</td><td style='padding:8px;'>admin@hill.com</td><td style='padding:8px;'>admin123</td></tr>\n";
    echo "<tr><td style='padding:8px;'>Vendeur</td><td style='padding:8px;'>vendeur@hill.com</td><td style='padding:8px;'>vendeur123</td></tr>\n";
    echo "</table>\n";

    echo "<p><strong>Test rapide de l'authentification :</strong></p>\n";
    echo "<form method='post' action='../backend/requests/auth.php' style='background:#f5f5f5; padding:20px; border-radius:5px;'>\n";
    echo "<input type='hidden' name='action' value='login'>\n";
    echo "<input type='text' name='username' value='admin@hill.com' placeholder='Email' style='margin:5px; padding:8px;'><br>\n";
    echo "<input type='password' name='password' value='admin123' placeholder='Mot de passe' style='margin:5px; padding:8px;'><br>\n";
    echo "<button type='submit' style='background:#FFD700; color:black; padding:10px 20px; border:none; border-radius:5px; margin:5px;'>Tester la connexion</button>\n";
    echo "</form>\n";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la migration : " . $e->getMessage() . "</p>\n";
    echo "<p>Détails de l'erreur :</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<p><a href='../backend/config/test_connection.php'>← Retour au test de connexion</a></p>\n";
