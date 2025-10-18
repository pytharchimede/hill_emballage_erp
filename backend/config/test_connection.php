<?php

/**
 * Script de test de connexion à la base de données
 */

require_once 'database.php';

echo "<h2>Test de connexion à la base de données HILL EMBALLAGE</h2>\n";

try {
    $database = new Database();

    echo "<p>Tentative de création de la base de données...</p>\n";
    if ($database->createDatabase()) {
        echo "<p style='color: green;'>✓ Base de données créée/vérifiée avec succès</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Erreur lors de la création de la base de données</p>\n";
    }

    echo "<p>Tentative de connexion à la base de données...</p>\n";
    $conn = $database->getConnection();

    if ($conn && $conn instanceof PDO) {
        echo "<p style='color: green;'>✓ Connexion réussie à la base de données !</p>\n";

        // Test de requête simple
        echo "<p>Test d'une requête simple...</p>\n";
        $sql = "SELECT DATABASE() as current_db, VERSION() as mysql_version";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p style='color: green;'>✓ Base de données active: " . $result['current_db'] . "</p>\n";
        echo "<p style='color: green;'>✓ Version MySQL: " . $result['mysql_version'] . "</p>\n";

        // Vérifier si les tables existent
        echo "<p>Vérification des tables...</p>\n";
        $sql = "SHOW TABLES";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            echo "<p style='color: green;'>✓ Tables trouvées: " . implode(', ', $tables) . "</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ Aucune table trouvée. Vous devez exécuter les migrations.</p>\n";
            echo "<p><a href='../migrations/create_tables.php' style='background: #FFD700; color: black; padding: 10px; text-decoration: none; border-radius: 5px;'>Créer les tables maintenant</a></p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ Échec de la connexion à la base de données</p>\n";
        echo "<p>Vérifiez vos paramètres de connexion dans database.php</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Configuration actuelle:</strong></p>\n";
echo "<ul>\n";
echo "<li>Host: localhost</li>\n";
echo "<li>Base de données: fidestci_hill_emballage_db</li>\n";
echo "<li>Utilisateur: fidestci_ulrich</li>\n";
echo "<li>Mot de passe: [masqué]</li>\n";
echo "</ul>\n";

echo "<p><a href='../../migrations/create_tables.php'>← Lancer la création des tables</a></p>\n";
