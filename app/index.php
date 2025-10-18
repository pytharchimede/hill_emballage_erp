<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    // Rediriger vers le dashboard
    header('Location: dashboard.php');
} else {
    // Rediriger vers la page de connexion
    header('Location: login.php');
}
exit();
