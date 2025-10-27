<?php
require_once __DIR__ . '/../backend/config/database.php';

echo "<h2>Migration: étendre l'ENUM users.role</h2>\n";

echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier existence de la colonne role
    $col = $db->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $col->execute();
    if (!$col->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Colonne users.role introuvable");
    }

    // Etendre l'ENUM pour inclure les nouveaux rôles tout en gardant les anciens
    $sql = "ALTER TABLE users MODIFY role ENUM('admin','gerant','commercial','comptable','vendeur','livreur') DEFAULT 'commercial'";
    $db->exec($sql);
    echo "<p class='success'>✓ users.role étendu (admin, gerant, commercial, comptable, vendeur, livreur)</p>\n";

    // Optionnel: corriger les lignes NULL en 'commercial'
    $fix = $db->prepare("UPDATE users SET role='commercial' WHERE role IS NULL");
    $fix->execute();
    echo "<p class='success'>✓ Rôles NULL mis à 'commercial' (si présents)</p>\n";

    echo "<p class='success'>Terminé.</p>\n";
} catch (Throwable $e) {
    echo "<p class='error'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
