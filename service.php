<?php
session_start();
include 'config.php';

// Générer numéro de service
function genererNumeroService($conn) {
    $result = $conn->query("SELECT MAX(numServ) AS dernier FROM SERVICE");
    $row = $result->fetch_assoc();
    $dernier = $row['dernier'];

    if ($dernier) {
        $num = intval(substr($dernier, 1)) + 1;
        return 'S' . str_pad($num, 4, '0', STR_PAD_LEFT);
    } else {
        return 'S0001';
    }
}

// Ajouter service
if (isset($_POST['ajouterService'])) {
    $numServ = genererNumeroService($conn);
    $service = $_POST['service'];
    $prix = $_POST['prix'];

    $sql = "INSERT INTO SERVICE (numServ, service, prix) VALUES ('$numServ', '$service', $prix)";
    
    if ($conn->query($sql)) {
        $_SESSION['message'] = 'Service ajouté !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur : ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    header("Location: service.php");
    exit();
}

// Modifier service
if (isset($_POST['modifierService'])) {
    $numServ = $_POST['editNunServ'];
    $service = $_POST['editService'];
    $prix = $_POST['editPrix'];

    $sql = "UPDATE SERVICE SET service='$service', prix=$prix WHERE numServ='$numServ'";
    
    if ($conn->query($sql)) {
        $_SESSION['message'] = 'Service modifié !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur : ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    header("Location: service.php");
    exit();
}

// Supprimer service
if (isset($_GET['supprimer'])) {
    $numServ = $_GET['supprimer'];
    $conn->query("DELETE FROM SERVICE WHERE numServ='$numServ'");
    header("Location: service.php");
    exit();
}

// Récupérer services
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM SERVICE" . (!empty($search) ? " WHERE service LIKE '%$search%'" : "");
$services = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Services</title>
    <style>
        /* Styles identiques à achat.php */
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
            margin-bottom: 30px;
            font-size: 24px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 12px;
            margin: 5px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar ul li:hover {
            background: rgba(255,255,255,0.1);
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
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
        }

        .search-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
        }

        .add-btn {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white; 
            padding: 10px 20px;
            border: none; 
            cursor: pointer;
            border-radius: 5px; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
        }

        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: center; 
        }

        th { 
            background: black; 
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
            background: white;
            padding: 25px; 
            border-radius: 10px; 
            width: 400px; 
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Station Essence</h2>
        <ul>
            <li><a href="index.php">Tableau de bord</a></li>
            <li><a href="produits.php">Produits</a></li>
            <li><a href="entree.php">Entrées</a></li>
            <li><a href="achat.php">Achats</a></li>
            <li><a href="service.php">Services</a></li>
            <li><a href="entretien.php">Entretien</a></li>
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

        <h1>Gestion des Services</h1>
        
        <div class="search-bar">
            <button class="add-btn" onclick="openModal('add')">+ Ajouter</button>
            <form method="GET" style="margin-left: auto;">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="Rechercher..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="add-btn">Rechercher</button>
                <button type="button" onclick="window.location.href='service.php'" class="add-btn">Réinitialiser</button>
            </form>
        </div>

        <table>
            <tr>
                <th>N° Service</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $services->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row["numServ"]) ?></td>
                    <td><?= htmlspecialchars($row["service"]) ?></td>
                    <td><?= htmlspecialchars($row["prix"]) ?> Ar</td>
                    <td>
                        <button onclick="openEditModal(
                            '<?= $row['numServ'] ?>',
                            '<?= $row['service'] ?>',
                            '<?= $row['prix'] ?>'
                        )" style="background: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                            Modifier
                        </button>
                        <button onclick="confirmDelete('<?= $row['numServ'] ?>')" 
                                style="background: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                            Supprimer
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Modale Ajout -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>Nouveau Service</h2>
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label>Nom du service :</label>
                    <input type="text" name="service" required style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Prix :</label>
                    <input type="number" name="prix" required style="width: 100%; padding: 8px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="ajouterService" class="add-btn">Enregistrer</button>
                    <button type="button" onclick="closeModal('add')" class="add-btn">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Édition -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Modifier Service</h2>
            <form method="POST">
                <input type="hidden" name="editNunServ" id="editNunServ">
                
                <div style="margin-bottom: 15px;">
                    <label>Nom du service :</label>
                    <input type="text" name="editService" id="editService" required style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Prix :</label>
                    <input type="number" name="editPrix" id="editPrix" required style="width: 100%; padding: 8px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="modifierService" class="add-btn">Enregistrer</button>
                    <button type="button" onclick="closeModal('edit')" class="add-btn">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fonctions identiques à achat.php
        function openModal(type) {
            document.getElementById(type + 'Modal').style.display = 'flex';
        }

        function closeModal(type) {
            document.getElementById(type + 'Modal').style.display = 'none';
        }

        function confirmDelete(numServ) {
            if (confirm("Supprimer ce service ?")) {
                window.location.href = 'service.php?supprimer=' + numServ;
            }
        }

        function openEditModal(numServ, service, prix) {
            document.getElementById('editNunServ').value = numServ;
            document.getElementById('editService').value = service;
            document.getElementById('editPrix').value = prix;
            openModal('edit');
        }

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>
</html>