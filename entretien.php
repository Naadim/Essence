<?php
session_start();
include 'config.php';
require 'fpdf/fpdf.php';

// Générer NumEntr
function genererNumEntr($conn) {
    $result = $conn->query("SELECT MAX(NumEntr) AS dernier FROM ENTRETIEN");
    if (!$result) {
        die("Erreur SQL : {$conn->error}");
    }
    $row = $result->fetch_assoc();
    $dernier = $row['dernier'];

    if ($dernier) {
        $num = intval(substr($dernier, 2)) + 1;
        return 'EN' . str_pad($num, 4, '0', STR_PAD_LEFT);
    } else {
        return 'EN0001';
    }
}

// Générer PDF
function genererPDF($conn, $numEntr) {
    $result = $conn->query("SELECT e.*, s.service, s.prix 
                           FROM ENTRETIEN e
                           JOIN SERVICE s ON e.numServ = s.numServ
                           WHERE e.NumEntr = '$numEntr'");
    if (!$result) {
        die("Erreur SQL : {$conn->error}");
    }
    $data = $result->fetch_assoc();

    if (!$data) {
        die("Aucun entretien trouvé avec ce numéro.");
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Entête
    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,'ATTESTATION D\'ENTRETIEN',0,1,'C');
    $pdf->Ln(15);

    // Contenu
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(50,10,'Numero : ' . htmlspecialchars($data['NumEntr']),0,1);
    $pdf->Cell(50,10,'Date : ' . date('d/m/Y', strtotime($data['dateEntretien'])),0,1);
    $pdf->Cell(50,10,'Client : ' . htmlspecialchars($data['nomClient']),0,1);
    $pdf->Cell(50,10,'Vehicule : ' . htmlspecialchars($data['Immatriculation_voiture']),0,1);
    $pdf->Cell(50,10,'Service : ' . htmlspecialchars($data['service']),0,1);
    $pdf->Cell(50,10,'Cout : ' . htmlspecialchars($data['prix']) . ' Ar',0,1);
    
    $pdf->Output('D',"Entretien_{$numEntr}.pdf");
}

// Ajouter
if (isset($_POST['ajouter'])) {
    $NumEntr = genererNumEntr($conn);
    $stmt = $conn->prepare("INSERT INTO ENTRETIEN VALUES (?,?,?,?,?)");
    if (!$stmt) {
        die("Erreur SQL : {$conn->error}");
    }
    $stmt->bind_param("sssss", 
        $NumEntr,
        $_POST['numServ'],
        $_POST['immat'],
        $_POST['client'],
        $_POST['date']
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Entretien ajouté !';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Erreur : {$stmt->error}";
        $_SESSION['message_type'] = 'error';
    }
    header("Location: entretien.php");
    exit();
}

// Modifier
if (isset($_POST['modifier'])) {
    $stmt = $conn->prepare("UPDATE ENTRETIEN SET 
        numServ = ?,
        Immatriculation_voiture = ?,
        nomClient = ?,
        dateEntretien = ?
        WHERE NumEntr = ?");
    if (!$stmt) {
        die("Erreur SQL : {$conn->error}");
    }
    $stmt->bind_param("sssss", 
        $_POST['numServ'],
        $_POST['immat'],
        $_POST['client'],
        $_POST['date'],
        $_POST['id']
    );
    $stmt->execute();
    header("Location: entretien.php");
    exit();
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $conn->query("DELETE FROM ENTRETIEN WHERE NumEntr='" . $conn->real_escape_string($_GET['supprimer']) . "'");
    $_SESSION['message'] = 'Entretien supprimé !';
    $_SESSION['message_type'] = 'success';
    header("Location: entretien.php");
    exit();
}

// Récupérer données
$search = $conn->real_escape_string($_GET['search'] ?? '');
$sql = "SELECT e.*, s.service 
        FROM ENTRETIEN e 
        LEFT JOIN SERVICE s ON e.numServ = s.numServ
        WHERE e.NumEntr LIKE '%$search%' 
        OR e.nomClient LIKE '%$search%' 
        OR e.Immatriculation_voiture LIKE '%$search%'";

$result = $conn->query($sql);
if (!$result) {
    die("Erreur SQL : {$conn->error}");
}

$services = $conn->query("SELECT * FROM SERVICE");
if (!$services) {
    die("Erreur SQL : {$conn->error}");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Entretiens</title>
    <style>
        /* Styles identiques à service.php */
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
            <li><a href="entretien.php">Entretiens</a></li>
            <li><a href="statistiques.php">Statistiques</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['message_type']) ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <h1>Gestion des Entretiens</h1>
        
        <div class="search-bar">
            <button class="add-btn" onclick="openModal('add')">+ Ajouter</button>
            <form method="GET" style="margin-left: auto;">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="Rechercher..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="add-btn">Rechercher</button>
                <button type="button" onclick="window.location.href='entretien.php'" class="add-btn">Réinitialiser</button>
            </form>
        </div>

        <table>
            <tr>
                <th>N° Entretien</th>
                <th>Service</th>
                <th>Immatriculation</th>
                <th>Client</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['NumEntr']) ?></td>
                    <td><?= htmlspecialchars($row['service']) ?></td>
                    <td><?= htmlspecialchars($row['Immatriculation_voiture']) ?></td>
                    <td><?= htmlspecialchars($row['nomClient']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['dateEntretien'])) ?></td>
                    <td>
                        <button onclick="openEditModal(
                            '<?= htmlspecialchars($row['NumEntr']) ?>',
                            '<?= htmlspecialchars($row['numServ']) ?>',
                            '<?= htmlspecialchars($row['Immatriculation_voiture']) ?>',
                            '<?= addslashes(htmlspecialchars($row['nomClient'])) ?>',
                            '<?= htmlspecialchars($row['dateEntretien']) ?>'
                        )" style="background: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                            Modifier
                        </button>
                        <button onclick="window.location='entretien.php?pdf=<?= htmlspecialchars($row['NumEntr']) ?>'" 
                                style="background: #17a2b8; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                            PDF
                        </button>
                        <button onclick="confirmDelete('<?= htmlspecialchars($row['NumEntr']) ?>')" 
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
            <h2>Nouvel Entretien</h2>
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label>Service :</label>
                    <select name="numServ" required style="width: 100%; padding: 8px;">
                        <?php while ($s = $services->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['numServ']) ?>"><?= htmlspecialchars($s['service']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Immatriculation :</label>
                    <input type="text" name="immat" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Client :</label>
                    <input type="text" name="client" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Date :</label>
                    <input type="date" name="date" required style="width: 100%; padding: 8px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="ajouter" class="add-btn">Enregistrer</button>
                    <button type="button" onclick="closeModal('add')" class="add-btn">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Édition -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Modifier Entretien</h2>
            <form method="POST">
                <input type="hidden" name="id" id="editId">
                
                <div style="margin-bottom: 15px;">
                    <label>Service :</label>
                    <select name="numServ" id="editServ" required style="width: 100%; padding: 8px;">
                        <?php $services->data_seek(0); ?>
                        <?php while ($s = $services->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['numServ']) ?>"><?= htmlspecialchars($s['service']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Immatriculation :</label>
                    <input type="text" name="immat" id="editImmat" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Client :</label>
                    <input type="text" name="client" id="editClient" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Date :</label>
                    <input type="date" name="date" id="editDate" required style="width: 100%; padding: 8px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="modifier" class="add-btn">Enregistrer</button>
                    <button type="button" onclick="closeModal('edit')" class="add-btn">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion des modales
        function openModal(type) {
            document.getElementById(type + 'Modal').style.display = 'flex';
        }

        function closeModal(type) {
            document.getElementById(type + 'Modal').style.display = 'none';
        }

        function openEditModal(id, serv, immat, client, date) {
            document.getElementById('editId').value = id;
            document.getElementById('editServ').value = serv;
            document.getElementById('editImmat').value = immat;
            document.getElementById('editClient').value = client;
            document.getElementById('editDate').value = date;
            openModal('edit');
        }

        function confirmDelete(id) {
            if (confirm('Voulez-vous vraiment supprimer cet entretien ?')) {
                window.location.href = 'entretien.php?supprimer=' + id;
            }
        }

        // Fermer alerte après 5s
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => a.style.display = 'none');
        }, 5000);
    </script>
</body>
</html>