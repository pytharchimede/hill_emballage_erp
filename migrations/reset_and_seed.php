<?php

/**
 * Réinitialiser entièrement le schéma puis créer et peupler la base.
 * - Appelle wipe_all.php pour supprimer toutes les vues et tables
 * - Appelle create_tables.php pour recréer le schéma + données de base
 */
require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Reset & Seed (tout-en-un)</h2>\n";

echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00}</style>";

try {
    // 1) Wipe total
    include __DIR__ . '/wipe_all.php';

    echo "<hr>";

    // 2) Recréer les tables + données de base
    include __DIR__ . '/create_tables.php';

    echo "<h3 class='ok'>Reset & Seed terminés</h3>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
