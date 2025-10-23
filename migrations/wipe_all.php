<?php

/**
 * Supprimer TOUTES les vues et tables de la base courante (hard reset)
 * - Désactive les contraintes FK
 * - Supprime toutes les VUES
 * - Supprime toutes les TABLES (quel que soit l'ordre)
 * - Réactive les contraintes
 */
require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Wipe total du schéma</h2>\n";

echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green} .warn{color:#b58900} .err{color:#c00} pre{background:#f7f7f7;padding:10px;border:1px solid #ddd}</style>";

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) throw new Exception('Connexion DB impossible');

    $db->exec("SET FOREIGN_KEY_CHECKS=0");

    // Drop ALL views
    $views = $db->query("SELECT TABLE_NAME FROM information_schema.views WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($views as $v) {
        try {
            $db->exec("DROP VIEW IF EXISTS `{$v}`");
            echo "<div class='ok'>✓ Vue supprimée: {$v}</div>";
        } catch (Exception $e) {
            echo "<div class='warn'>⚠ Échec DROP VIEW {$v}: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // Drop ALL tables
    $tables = $db->query("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        try {
            $db->exec("DROP TABLE IF EXISTS `{$t}`");
            echo "<div class='ok'>✓ Table supprimée: {$t}</div>";
        } catch (Exception $e) {
            echo "<div class='warn'>⚠ Échec DROP TABLE {$t}: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    $db->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "<h3 class='ok'>Wipe terminé</h3>";
    echo "<p>Vous pouvez maintenant recréer le schéma.</p>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<p class='err'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='./create_tables.php'>&larr; Retour migrations</a></p>";
