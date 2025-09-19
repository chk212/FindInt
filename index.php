<?php
/**
 * Page d'entrée - FINDint
 * Redirige vers la page d'accueil applicative.
 */

// Charger la configuration
require_once 'config.php';

// Protection des droits d'auteur
require_once 'protection-droits-auteur.php';
protectCopyright();

// Redirection directe vers l'application
header('Location: generer-lettre.php');
exit;
?>
