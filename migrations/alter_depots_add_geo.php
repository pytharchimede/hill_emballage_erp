<?php

/**
 * Migration pour ajouter email, horaires, latitude, longitude à la table depots (si absents)
 */
require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Migration: Ajout de colonnes géolocalisation aux dépôts</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) throw new Exception('Connexion DB échouée');

    $cols = [
        'email' => 'ADD COLUMN email VARCHAR(100) NULL AFTER telephone',
        'horaires' => 'ADD COLUMN horaires VARCHAR(255) NULL AFTER email',
        'latitude' => 'ADD COLUMN latitude DECIMAL(10,7) NULL AFTER horaires',
        'longitude' => 'ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude'
    ];

    foreach ($cols as $name => $ddl) {
        $check = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='depots' AND COLUMN_NAME=?");
        $check->execute([$name]);
        if ((int)$check->fetchColumn() === 0) {
            try {
                $db->exec("ALTER TABLE depots $ddl");
                echo "<p class='success'>✓ Colonne $name ajoutée</p>\n";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Échec ajout $name: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        } else {
            echo "<p class='info'>• Colonne $name déjà présente</p>\n";
        }
    }

    echo "<hr><p class='success'><strong>Terminé.</strong> Vous pouvez retourner à <a href='../web_admin/depots.php'>la gestion des dépôts</a>.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}
