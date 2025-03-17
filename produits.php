<?php
include("config.php");

// Générer un numéro de produit automatique
// function generateProductNumber($conn) {
//     $result = $conn->query("SELECT COUNT(*) AS total FROM PRODUIT");
//     $row = $result->fetch_assoc();
//     $count = $row["total"] + 1;
//     return "P" . str_pad($count, 3, "0", STR_PAD_LEFT);
// }
function generateProductNumber($conn) {
    $result = $conn->query("SELECT MAX(numProd) AS maxNum FROM PRODUIT");
    $row = $result->fetch_assoc();
    $maxNum = $row["maxNum"];

    if($maxNum){
        // Extrait le nombre du format P001
        $number=intval(substr($maxNum, 1)) + 1;
    }else{
        $number=1;
    }
    return "P" . str_pad($number, 3, "0", STR_PAD_LEFT);
}







// Ajouter un produit
if (isset($_POST["ajouter"])) {
    $numProd = generateProductNumber($conn);
    // $design = $_POST["design"];
    $design = $_POST["editDesign"];
    $stock = 0;

    $sql = "INSERT INTO PRODUIT (numProd, Design, stock) VALUES ('$numProd', '$design', '$stock')";
    $conn->query($sql);
    header("Location: produits.php");
    exit();
}

// Modifier un produit
if (isset($_POST["modifier"])) {
    $numProd = $_POST["editNumProd"];
    $design = $_POST["editDesign"];
    $conn->query("UPDATE PRODUIT SET Design='$design' WHERE numProd='$numProd'");
    header("Location: produits.php");
    exit();
}

// Supprimer un produit
if (isset($_GET["delete"])) {
    $numProd = $_GET["delete"];
    $conn->query("DELETE FROM PRODUIT WHERE numProd='$numProd'");
    header("Location: produits.php");
    exit();
}

// Récupérer tous les produits
$result = $conn->query("SELECT * FROM PRODUIT");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Produits</title>
    <style>
        * { font-family: Arial, sans-serif; }
        body { display: flex; background: whitesmoke; height: 100vh; }

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
        .sidebar h2 { text-align: center; margin-bottom: 60px; font-size: 27px; }
        .sidebar ul { list-style: none; padding: 0; text-align: center; cursor: pointer; }
        .sidebar ul li { padding: 15px; border-bottom: 2px solid rgba(255, 255, 255, 0.2); font-weight: bold; transition: 0.4s; }
        .sidebar ul li a { color: white; text-decoration: none; display: block; }
        .sidebar ul li:hover { background: rgba(255, 255, 255, 0.4); transform: scale(1.1); }

        .main-content { margin-left: 350px; margin-right: 75px;  padding: 20px; width: 100%; }
        .header { text-align: center; font-size: 34px; font-weight: bold; margin-bottom: 20px; }
        .add-btn {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white; padding: 10px 20px;
            border: none; cursor: pointer;
            border-radius: 5px; font-size: 16px;
            transition: 0.4s;
        }
        .add-btn:hover { transform: scale(1.1); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; text-align: center; padding: 10px; }
        th { background: black; color: white; padding: 20px;}
        tr:nth-child(even) { background: white; }
        tr:nth-child(odd) { background: lightgreen; }

        .edit-btn, .delete-btn {
            cursor: pointer; padding: 8px;
            border: none; color: white;
            border-radius: 5px; margin: 8px;
        }
        .edit-btn { background: linear-gradient(to right, #f1c40f, #f39c12); }
        .delete-btn { background: linear-gradient(to right, #e74c3c, #c0392b); }
        .edit-btn:hover, .delete-btn:hover { transform: scale(1.2); }

        .modal {
            display: none; position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 20px; border-radius: 10px;
            color: white; width: 400px;
            text-align: center;
        }
        .modal-content h2 { font-size: 24px; margin-bottom: 20px; }
        .modal-content input {
            padding: 10px; margin-top: 10px;
            width: 100%; border-radius: 5px;
            border: 1px solid #ddd; text-align: center;
        }
        .div_boutton {
            margin-top: 20px; display: flex;
            justify-content: space-evenly;
        }
    </style>
</head>
<body>

    <!-- Barre de navigation -->
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

    <!-- Contenu principal -->
    <div class="main-content">
        <div class="header">Gestion des Produits</div>
        <button class="add-btn" onclick="openModal('add')">+ Ajouter Produit</button>


        <?php
$lowStock = $conn->query("SELECT * FROM PRODUIT WHERE stock < 10");
if ($lowStock->num_rows > 0) {
    echo "<div style='background: red; color: white; padding: 10px; text-align: center; font-weight: bold;'>
            ⚠️ Attention ! Certains produits ont un stock inférieur à 10 litres.
          </div>";
}
?>


        <table>
            <tr>
                <th>Numéro Produit</th>
                <th>Désignation</th>
                <th>Stock</th>
                <th>Actions</th>
                <th>Stock Critique</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row["numProd"] ?></td>
                    <td><?= $row["Design"] ?></td>
                    <td><?= $row["stock"] ?></td>
                    <td>
                        <button class="edit-btn" onclick="openModal('edit', '<?= $row['numProd'] ?>', '<?= $row['Design'] ?>')">✏️</button>
                        <button class="delete-btn" onclick="confirmDelete('<?= $row['numProd'] ?>')">🗑</button>
                    </td>
                    <td>
                        <?= ($row["stock"] < 10) ? "<span style='color: red; font-weight: bold;'>⚠️ Stock bas</span>" : "✔️ OK"; ?>
                    </td>


                </tr>
            <?php } ?>
        </table>
    </div>

    <!-- Modale -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h2 id="modal-title">Ajouter un Produit</h2>
            <form method="POST">
                <input type="hidden" name="editNumProd" id="editNumProd">
                <input type="text" name="editDesign" id="editDesign" placeholder="Désignation" required>
                <div class="div_boutton">
                    <button type="submit" name="modifier" id="modal-submit" class="add-btn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, numProd = '', design = '') {
            document.getElementById("modal").style.display = "flex";
            document.getElementById("modal-title").innerText = mode === "edit" ? "Modifier Produit" : "Ajouter Produit";
            document.getElementById("modal-submit").name = mode === "edit" ? "modifier" : "ajouter";
            document.getElementById("editNumProd").value = numProd;
            document.getElementById("editDesign").value = design;
        }
        function confirmDelete(numProd){
            if(confirm("Vous-vous vraiment supprimer ce produit ? ")){
                window.location.href="produits.php?delete=" + numProd;
            }
        }
    </script>

</body>
</html>