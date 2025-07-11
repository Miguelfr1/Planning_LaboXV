<?php
session_start();
require_once "db.php";

// Sécurité : utilisateur connecté ?
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userQuery = "SELECT is_admin, name FROM users WHERE id = '$user_id'";
$userResult = $conn->query($userQuery);
$userData = $userResult->fetch_assoc();
$isAdmin = $userData['is_admin'] ?? false;
$userName = $userData['name'] ?? '';

$selectedLab = $_GET['laboratory'] ?? 'Plateau Technique';
$allLabs = isset($_GET['all_labs']) && $_GET['all_labs'] === '1';
$startDateFilter = $_GET['start_date'] ?? date('Y-m-01');
$endDateFilter   = $_GET['end_date']   ?? date('Y-m-t');


?>




<?php
$typeLabels = [
    'conge'           => 'Congé Payé',
    'arret_maladie'   => 'Arrêt Maladie',
    'conge_maternite' => 'Congé Maternité',
    'enfant_malade'   => 'Enfant Malade',
    'Revision'        => 'Jour(s) de révision',
    'Exam'            => 'Examen',
    'Formation'       => 'Formation'
];

?>

<?php
function formatDateFr($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('d/m/Y') : $date;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style2.css">
    <title>Labo XV</title>
    <style>
        .laboratory-buttons { display: flex; justify-content: space-between; }
        .laboratory-buttons button { flex: 1; margin: 5px; padding: 10px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .laboratory-buttons button.active { background-color: #0056b3; }
        .form-container { margin-bottom: 40px; }
        .create-user { margin-bottom: 30px; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar" class="hide">
        <a href="#" class="brand"><span class="text">Labo XV</span></a>
        <ul class="side-menu top">
    <li><a href="dashboard.php"><i class="bx bxs-dashboard"></i><span class="text">Planning</span></a></li>
        <li><a href="heures.php"><i class="bx bxs-doughnut-chart"></i><span class="text">Décompte d'heures</span></a></li>

        <li>
    <a href="employers.php">
        <i class="bx bxs-group"></i>
        <span class="text">
            <?php echo ($userData['is_admin'] ? 'Collaborateurs' : 'Mon Profil'); ?>
        </span>
    </a>
</li>    <li class="active"><a href="conges.php"><i class='bx bx-calendar-check'></i><span class="text">Congés</span></a></li>
    <li><a href="badgeuse.php"><i class='bx bxs-user-check'></i><span class="text">Badgeuse</span></a></li>
</ul>

        <ul class="side-menu">
            <li><a href="logout.php" class="logout"><i class="bx bxs-log-out-circle"></i><span class="text">Déconnexion</span></a></li>
        </ul>
    </section>

    <!-- CONTENT -->
    <section id="content">
        <nav>
            <i class="bx bx-menu"></i>
            <form action="#"><div class="form-input"></div></form>
            <input type="checkbox" id="switch-mode" hidden />
            <a href="#" class="notification"></a>
        </nav>
        <main>
            <div class="head-title">
                <div class="left"><h1>Congés</h1></div>
            </div>

            <!-- Bloc 1 : Créer/Demander un congé -->
            <?php if ($isAdmin): ?>
            <div class="create-user">
                <h3 class="text" onclick="toggleForm('form-conge')">Créer un congé <i class='bx bx-chevron-down'></i></h3>
                <div class="form-container" id="form-conge">
                    <form method="POST" action="create_conges.php">
                        <label for="employee">Collaborateur :</label>
                        <select id="employee" name="employee" required>
                        <?php
                            $employeeQuery = "SELECT id, name FROM users ORDER BY SUBSTRING_INDEX(name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC";
                            $employeeResult = $conn->query($employeeQuery);
                            while ($emp = $employeeResult->fetch_assoc()) {
                                echo "<option value=\"" . htmlspecialchars($emp['id']) . "\">" . htmlspecialchars($emp['name']) . "</option>";
                            }
                        ?>
                        </select>
                        <label for="absence-type">Type d'absence :</label>
                        <select id="absence-type" name="absence_type" required>
                            <option value="conge">Congé Payé</option>
                            <option value="arret_maladie">Arrêt Maladie</option>
                            <option value="conge_maternite">Congé Maternité</option>
                            <option value="enfant_malade">Enfant Malade</option>
                            <option value="Revision">Jour(s) de révision</option>
                            <option value="Exam">Examen</option>
                            <option value="Formation">Formation</option>
                        </select>
                        <label for="start-date">Date de début :</label>
                        <input type="date" id="start-date" name="start_date" required>
                        <label for="end-date">Date de fin :</label>
                        <input type="date" id="end-date" name="end_date" required>
                        <button type="submit">Enregistrer</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="create-user">
                <h3 class="text" onclick="toggleForm('form-conge')">Demander un congé <i class='bx bx-chevron-down'></i></h3>
                <div class="form-container" id="form-conge">
                    <form method="POST" action="create_demande.php">
                        <input type="hidden" name="employee" value="<?= $user_id ?>">
                        <p><strong>Collaborateur :</strong> <?= htmlspecialchars($userName) ?></p>
                        <label for="absence-type">Type d'absence :</label>
                        <select id="absence-type" name="absence_type" required>
                            <option value="conge">Congé Payé</option>
                            <option value="arret_maladie">Arrêt Maladie</option>
                            <option value="conge_maternite">Congé Maternité</option>
                            <option value="enfant_malade">Enfant Malade</option>
                            <option value="Revision">Semaine de révision</option>
                            <option value="Exam">Examen</option>
                            <option value="Formation">Formation</option>
                        </select>
                        <label for="start-date">Date de début :</label>
                        <input type="date" id="start-date" name="start_date" required>
                        <label for="end-date">Date de fin :</label>
                        <input type="date" id="end-date" name="end_date" required>
                        <button type="submit">Demander</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bloc 2 : Demandes de congés -->
            <?php if ($isAdmin): ?>
            <div class="create-user">
                <h3 class="text" onclick="toggleForm('bloc-demandes')">Demandes de congés <i class='bx bx-chevron-down'></i></h3>
                <div class="form-container" id="bloc-demandes" style="display:none;">
                    <table>
                        <thead>
                            <tr>
                                <th>Collaborateur</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>

    
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT cp.*, u.name FROM conges_pending cp JOIN users u ON cp.user_id = u.id ORDER BY cp.start_date DESC");
                            while ($row = $res->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($typeLabels[$row['absence_type']] ?? $row['absence_type']) ?></td>
                                <td><?= formatDateFr($row['start_date']) ?> au <?= formatDateFr($row['end_date']) ?></td>
                                <td>
                                    <?php
                                    if ($row['etat'] == 'attente') echo "<span style='color:orange'>En attente</span>";
                                    if ($row['etat'] == 'accepte') echo "<span style='color:green'>Accepté</span>";
                                    if ($row['etat'] == 'refuse') echo "<span style='color:red'>Refusé</span>";
                                    ?>
                                </td>
                                <td>
    <?php if ($row['etat'] == 'attente'): ?>
    <div class="demande-actions">
        <form method="post" action="gerer_demande.php" style="display:inline;">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <button type="submit" name="action" value="accepter" class="btn-accept">Accepter</button>
        </form>
        <form method="post" action="gerer_demande.php" style="display:flex;">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="text" name="motif_refus" placeholder="Motif refus" required>
            <button type="submit" name="action" value="refuser" class="btn-refuse">Refuser</button>
        </form>
    </div>
    <?php elseif ($row['etat'] == 'refuse'): ?>
        <span title="<?= htmlspecialchars($row['motif_refus']) ?>">Refusé</span>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
<div class="create-user">
    <h3 class="text" onclick="toggleForm('bloc-demandes')">Mes demandes <i class='bx bx-chevron-down'></i></h3>
    <div class="form-container" id="bloc-demandes" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th>Motif du refus</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM conges_pending WHERE user_id='$user_id' ORDER BY start_date DESC");
                while ($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($typeLabels[$row['absence_type']] ?? $row['absence_type']) ?></td>
                    <td><?= formatDateFr($row['start_date']) ?> au <?= formatDateFr($row['end_date']) ?></td>
                    <td>
                        <?php
                        if ($row['etat'] == 'attente') echo "<span style='color:orange'>En attente</span>";
                        if ($row['etat'] == 'accepte') echo "<span style='color:green'>Accepté</span>";
                        if ($row['etat'] == 'refuse') echo "<span style='color:red'>Refusé</span>";
                        ?>
                    </td>
                    <td><?= $row['etat']=='refuse' ? htmlspecialchars($row['motif_refus']) : '-' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


            <!-- Filtre période -->
             <div class="header">


            <div class="date-selection">
                <label for="start-date-filter">Date de début :</label>
                <input type="date" id="start-date-filter" value="<?= htmlspecialchars($startDateFilter) ?>">
                <label for="end-date-filter">Date de fin :</label>
                <input type="date" id="end-date-filter" value="<?= htmlspecialchars($endDateFilter) ?>">
                <button onclick="updateFilters()">Afficher</button>
                </div>

                <div class="right">
                <select id="lab-selector" class="lab-selector" onchange="updateFilters()" style="margin-left:10px;">
    <option value="Plateau Technique" <?= $selectedLab == 'Plateau Technique' ? 'selected' : '' ?>>PLATEAU TECHNIQUE</option>
    <option value="351_vaugirard" <?= $selectedLab == '351_vaugirard' ? 'selected' : '' ?>>351, VAUGIRARD</option>
    <option value="mozart" <?= $selectedLab == 'mozart' ? 'selected' : '' ?>>MOZART</option>
    <option value="grignon" <?= $selectedLab == 'grignon' ? 'selected' : '' ?>>GRIGNON</option>
</select>

    <label style="margin-left:10px;">
        <input type="checkbox" id="all-labs-toggle" <?= $allLabs ? 'checked' : '' ?> onchange="updateFilters()"> Tous les laboratoires
    </label>
</div>

                </div>


<div class="table-data">
    <div class="order">
        <div class="head">
            <h3>Planning mensuel des congés</h3>
            <input type="text" id="searchInput" placeholder="Rechercher un Collaborateur..." onkeyup="filterEmployees()">
        </div>
        <div id="grille-conges"></div>
    </div>
</div>


           
        </main>
    </section>
    <script>

</script>



    <script src="script3.js"></script>

</body>
<script>

// Sélectionne la table la plus proche de l'input
function filterEmployees() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    // Va chercher la table juste à côté du input (dans la même .order)
    const table = input.closest('.order').querySelector("table tbody");
    const rows = table.getElementsByTagName("tr");
    for (let i = 0; i < rows.length; i++) {
        const td = rows[i].getElementsByTagName("td")[0];
        if (td) {
            const txtValue = td.textContent || td.innerText;
            rows[i].style.display = txtValue.toLowerCase().includes(filter) ? "" : "none";
        }
    }
}


function toggleForm(id) {
    const el = document.getElementById(id);
    el.style.display = (el.style.display == "none" || el.style.display == "") ? "block" : "none";
}

</script>

<style>
    
    
    
    .demande-actions {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;       /* espace entre chaque élément */
    margin-left: 24px;  /* éloigne du texte "En attente" */
}

.demande-actions input[type="text"] {
    min-width: 170px;
    max-width: 250px;
    padding: 7px 12px;
    border: 1px solid #bbb;
    border-radius: 5px;
    font-size: 1rem;
    margin: 0 8px 0 0;
}
.demande-actions {
    display: flex;
    flex-direction: column;  /* ← Ici ! */
    align-items: flex-start; /* Optionnel : aligne à gauche, sinon center */
    gap: 12px;               /* Espace vertical entre les lignes */
    margin-left: 24px;
}
.demande-actions form {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
}


.table-presence td.absent {
    background: #e74c3c;
}
.table-presence td.present {
    background: #27ae60;
}
.table-presence th, .table-presence td {
    width: 26px; height: 26px; padding: 0; text-align: center;
    border: 1px solid #ddd;
}
.table-presence th:first-child, .table-presence td:first-child {
    width: 200px; text-align: left; font-weight: bold; background: #f8f8f8;
}


.demande-actions .btn-accept,
.demande-actions .btn-refuse {
    min-width: 110px;
    padding: 8px 0;
    border-radius: 7px;
    font-weight: bold;
    font-size: 1.05rem;
    border: none;
    background: #3498db;
    color: #fff;
    margin: 0 3px;
    transition: background 0.2s;
    cursor: pointer;
    box-shadow: 0 2px 8px #0001;
}
.demande-actions .btn-accept:hover {
    background: #16a085;
}
.demande-actions .btn-refuse {
    background: #e74c3c;
}
.demande-actions .btn-refuse:hover {
    background: #c0392b;
}

.demande-status {
    color: #fa9900;
    font-weight: 500;
    margin-right: 16px;
}

#bloc-demandes table th, 
#bloc-demandes table td {
    padding: 16px 28px;  /* Ajoute de l'espace à gauche et droite */
    text-align: center;
}

#bloc-demandes table th {
    font-size: 1.07rem;
    letter-spacing: 0.02em;
}

#bloc-demandes table td {
    font-size: 1.04rem;
}

/* Optionnel : largeur minimum pour chaque colonne (adapter si besoin) */
#bloc-demandes table th:nth-child(1),
#bloc-demandes table td:nth-child(1) { min-width: 120px; }
#bloc-demandes table th:nth-child(2),
#bloc-demandes table td:nth-child(2) { min-width: 100px; }
#bloc-demandes table th:nth-child(3),
#bloc-demandes table td:nth-child(3) { min-width: 210px; }
#bloc-demandes table th:nth-child(4),
#bloc-demandes table td:nth-child(4) { min-width: 110px; }
#bloc-demandes table th:nth-child(5),
#bloc-demandes table td:nth-child(5) { min-width: 200px; }
.select-disabled {
    background: #f4f4f4 !important;
    color: #888 !important;
    border-color: #bbb !important;
    cursor: not-allowed !important;
    pointer-events: none;
    opacity: 1;
}

select {
	padding: 10px;
	font-size: 16px;
	border-radius: 8px;
	border: 2px solid #007BFF;
	background: var(--white);
	color: var(--dark);

	box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
	transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }



  select:focus {
	border-color: #0056b3;
	box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
	background: var(--white);
	
  }

  .lab-selector{
	color: var(--dark);
  }

  option {
	padding: 10px;
	background: var(--light);
	color: var(--dark);


  }


  .table-presence th.ferie,
.table-presence td.ferie,
.table-presence th.sunday,
.table-presence td.sunday {
    background: #222 !important;
    color: #fff !important;
}
.table-presence {
    border-collapse: collapse;
    width: 100%;
    background: white;
    table-layout: fixed; /* Important pour les carrés */
}

.table-presence th,
.table-presence td {
    border: 2px solid #111 !important;
    padding: 0 !important;
    text-align: center;
    vertical-align: middle;
    width: 1.0vw;   /* ou 2.5vw selon le nb de jours, adapte si tu veux */
    height: 1.0vw;  /* identique à width pour carré */
    min-width: 30px;
    min-height: 30px;
    max-width: 50px;
    max-height: 50px;
    font-size: 1em;
    box-sizing: border-box;
}

.table-presence th:first-child,
.table-presence td:first-child {
    width: 60px !important;
    min-width: 130px !important;
    max-width: 220px !important;
    text-align: left;
    background: #fff;
    font-weight: bold;
}

.table-presence td.absent { background: #e74c3c !important; }
.table-presence td.present { background: #27ae60 !important; }
.table-presence td.ferie, .table-presence td.sunday {
    background: #222 !important;
    color: #fff !important;
}

.table-presence tr td[colspan] {
    background: #f7f7fa !important;
    color: #222 !important;
    font-size: 1.13em;
    font-weight: bold;
    text-align: left;
    border: 2px solid #111 !important;
}



.header {
    display: flex;
    gap : 500px;
}

</style>
</html>
