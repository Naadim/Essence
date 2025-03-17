<?php
// Inclure la connexion à la base de données
include("config.php");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Station Essence</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            display: flex;
        }

        /* Barre de navigation */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(30deg, #ff001d, #ff2a4e);      
            padding: 20px;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 60px;
            font-size: 27px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            text-align: center;
            cursor: pointer;
        }

        .sidebar ul li {
            padding: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            font-weight: bold;
            transition: 0.4s;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar ul li:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        /* Contenu principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
    </style>
</head>
<body>

    <!-- Barre de navigation -->
    <div class="sidebar">
        <h2>Station Essence</h2>
        <ul>
            <li><a href="#">Tableau de bord</a></li>
            <li><a href="produits.php">PRODUIT</a></li>
            <li><a href="entree.php">ENTREE</a></li>
            <li><a href="#">ACHAT</a></li>
            <li><a href="#">SERVICE</a></li>
            <li><a href="#">ENTRETIEN</a></li>
            <li><a href="#">Statistiques</a></li>
        </ul>
    </div>

    <!-- Contenu principal -->
    <div class="main-content">
        <h1>Tableau de bord</h1>
        <p>Hello</p>
    </div>

</body>
</html>