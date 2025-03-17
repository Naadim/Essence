<?php
session_start();
include 'config.php';

// Générer un numéro d'achat automatique
function genererNumeroAchat($conn) {
    $result = $conn->query("SELECT MAX(numAchat) AS dernier FROM ACHAT");
    $row = $result->fetch_assoc();
    $dernier = $row['dernier'];

    if ($dernier) {
        $num = intval(substr($dernier, 1)) + 1;
        return 'A' . str_pad($num, 4, '0', STR_PAD_LEFT);
    } else {
        return 'A0001';
    }
}

// Ajouter un achat
if (isset($_POST['ajouterAchat'])) {
    $numAchat = genererNumeroAchat($conn);
    $numProd = $_POST['numProd'];
    $nomClient = $_POST['nomClient'];
    $nbrLitre = $_POST['nbrLitre'];
    $dateAchat = $_POST['dateAchat'];

    $sql = "INSERT INTO ACHAT (numAchat, numProd, nomClient, nbrLitre, dateAchat) 
            VALUES ('$numAchat', '$numProd', '$nomClient', $nbrLitre, '$dateAchat')";
    
    if ($conn->query($sql)) {
        $_SESSION['message'] = 'Achat ajouté avec succès !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur lors de l\'ajout de l\'achat !';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: achat.php");
    exit();
}

// Modifier un achat
if (isset($_POST['modifierAchat'])) {
    $numAchat = $_POST['editNumAchat'];
    $numProd = $_POST['editNumProd'];
    $nomClient = $_POST['editNomClient'];
    $nbrLitre = $_POST['editNbrLitre'];
    $dateAchat = $_POST['editDateAchat'];

    $sql = "UPDATE ACHAT SET numProd='$numProd', nomClient='$nomClient', nbrLitre=$nbrLitre, dateAchat='$dateAchat' WHERE numAchat='$numAchat'";
    
    if ($conn->query($sql)) {
        $_SESSION['message'] = 'Achat modifié avec succès !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur lors de la modification de l\'achat !';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: achat.php");
    exit();
}

// Supprimer un achat
if (isset($_GET['supprimer'])) {
    $numAchat = $_GET['supprimer'];
    if ($conn->query("DELETE FROM ACHAT WHERE numAchat='$numAchat'")) {
        $_SESSION['message'] = 'Achat supprimé avec succès !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur lors de la suppression !';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: achat.php");
    exit();
}

// Récupérer la liste des achats avec recherche
$searchClient = isset($_GET['searchClient']) ? trim($_GET['searchClient']) : '';
$sql = "SELECT * FROM ACHAT";
if (!empty($searchClient)) {
    $searchClient = $conn->real_escape_string($searchClient);
    $sql .= " WHERE nomClient LIKE '%$searchClient%'";
}
$achats = $conn->query($sql);

$produits = $conn->query("SELECT numProd FROM PRODUIT");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Achats</title>
    <style>
        * { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            display: flex; 
            background: whitesmoke; 
            height: 100vh; 
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(30deg, #ff001d, #ff2a4e);
            padding: 20px;
            color: white;
            position: fixed;
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

        .main-content { 
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .search-bar { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px;
            align-items: center;
        }

        .search-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
            font-size: 16px;
        }

        .add-btn {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white; 
            padding: 10px 20px;
            border: none; 
            cursor: pointer;
            border-radius: 5px; 
            font-size: 16px;
            transition: opacity 0.3s;
        }

        .add-btn:hover {
            opacity: 0.9;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: center; 
        }

        th { 
            background: black; 
            color: white; 
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: 0.3s;
        }

        .edit-btn:hover {
            background: #3498db;
            color: white;
        }

        .delete-btn:hover {
            background: #e74c3c;
            color: white;
        }

        .modal {
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            background: rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center;
        }

        .modal-content {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 25px; 
            border-radius: 10px; 
            color: white; 
            width: 400px; 
            text-align: center;
        }

        .modal-content input, .modal-content select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Station Essence</h2>
        <ul>
            <li><a href="index.php">Tableau de bord</a></li>
            <li><a href="produits.php">PRODUIT</a></li>
            <li><a href="entree.php">ENTREE</a></li>
            <li><a href="achat.php">ACHAT</a></li>
            <li><a href="service.php">SERVICE</a></li>
            <li><a href="entretien.php">ENTRETIEN</a></li>
            <li><a href="statistiques.php">Statistiques</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <h1>Gestion des Achats</h1>
        
        <div class="search-bar">
            <button class="add-btn" onclick="openModal('add')">+ Ajouter Achat</button>
            <form method="GET" style="margin-left: auto;">
                <input type="text" 
                       name="searchClient" 
                       class="search-input"
                       placeholder="Rechercher par client..."
                       value="<?= htmlspecialchars($searchClient ?? '') ?>">
                <button type="submit" class="add-btn">🔍 Rechercher</button>
                <button type="button" onclick="window.location.href='achat.php'" class="add-btn">🔄 Réinitialiser</button>
            </form>
        </div>

        <table>
            <tr>
                <th>Numéro</th>
                <th>Produit</th>
                <th>Client</th>
                <th>Litres</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $achats->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row["numAchat"]) ?></td>
                    <td><?= htmlspecialchars($row["numProd"]) ?></td>
                    <td><?= htmlspecialchars($row["nomClient"]) ?></td>
                    <td><?= htmlspecialchars($row["nbrLitre"]) ?></td>
                    <td><?= htmlspecialchars($row["dateAchat"]) ?></td>
                    <td>
                        <button class="edit-btn" onclick="openEditModal(
                            '<?= $row['numAchat'] ?>',
                            '<?= $row['numProd'] ?>',
                            '<?= $row['nomClient'] ?>',
                            '<?= $row['nbrLitre'] ?>',
                            '<?= $row['dateAchat'] ?>'
                        )">✏️</button>
                        <button class="delete-btn" onclick="confirmDelete('<?= $row['numAchat'] ?>')">🗑</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Modale d'ajout -->
    <!-- <div class="modal" id="modal-add"> -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>Ajouter un Achat</h2>
            <form method="POST">
                <label>Produit :</label>
                <select name="numProd" required>
                    <option value="">-- Sélectionner un produit --</option>
                    <?php while ($prod = $produits->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($prod['numProd']) ?>">
                            <?= htmlspecialchars($prod['numProd']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Nom du client :</label>
                <input type="text" name="nomClient" required>

                <label>Nombre de litres :</label>
                <input type="number" name="nbrLitre" required>

                <label>Date d'achat :</label>
                <input type="date" name="dateAchat" required>

                <button type="submit" name="ajouterAchat" class="add-btn">Ajouter</button>
                <button type="button" onclick="closeModal('add')" class="add-btn">Annuler</button>
            </form>
        </div>
    </div>

    <!-- Modale de modification -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Modifier un Achat</h2>
            <form method="POST">
                <input type="hidden" name="editNumAchat" id="editNumAchat">
                <label>Produit :</label>
                <select name="editNumProd" id="editNumProd" required>
                    <option value="">-- Sélectionner un produit --</option>
                    <?php 
                    $produits = $conn->query("SELECT numProd FROM PRODUIT");
                    while ($prod = $produits->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($prod['numProd']) ?>">
                            <?= htmlspecialchars($prod['numProd']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Nom du client :</label>
                <input type="text" name="editNomClient" id="editNomClient" required>

                <label>Nombre de litres :</label>
                <input type="number" name="editNbrLitre" id="editNbrLitre" required>

                <label>Date d'achat :</label>
                <input type="date" name="editDateAchat" id="editDateAchat" required>

                <button type="submit" name="modifierAchat" class="add-btn">Modifier</button>
                <button type="button" onclick="closeModal('edit')" class="add-btn">Annuler</button>
            </form>
        </div>
    </div>

    <script>
        // Gestion des modales
        function openModal(id) {
            // document.getElementById('modal-' + id).style.display = "flex";
            document.getElementById(id  + 'Modal').style.display = "flex";
        }

        function closeModal(id) {
            document.getElementById(id + 'Modal').style.display = "none";
        }

        // Confirmation suppression
        function confirmDelete(numAchat) {
            if (confirm("Voulez-vous vraiment supprimer cet achat ?")) {
                window.location.href = "achat.php?supprimer=" + numAchat;
            }
        }

        // Remplissage modale d'édition
        function openEditModal(numAchat, numProd, nomClient, nbrLitre, dateAchat) {
            document.getElementById("editModal").style.display = "flex";
            document.getElementById("editNumAchat").value = numAchat;
            document.getElementById("editNumProd").value = numProd;
            document.getElementById("editNomClient").value = nomClient;
            document.getElementById("editNbrLitre").value = nbrLitre;
            document.getElementById("editDateAchat").value = dateAchat;
        }

        // Disparition automatique des messages
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>
</html>