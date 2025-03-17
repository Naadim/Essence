<?php
// Paramètres de connexion à la base de données
$host = "localhost"; // Serveur MySQL (généralement "localhost" en local)
$user = "root"; // Nom d'utilisateur MySQL (par défaut "root" sous XAMPP)
$password = ""; // Mot de passe MySQL (par défaut vide sous XAMPP)
$database = "station_essence"; // Nom de la base de données

// Connexion à la base de données
$conn = new mysqli($host, $user, $password, $database);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Définir l'encodage des caractères pour éviter les erreurs d'affichage
$conn->set_charset("utf8");

// Message de confirmation (à supprimer en production)
//echo "Connexion réussie à la base de données.";
?>