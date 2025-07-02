<?php
session_start();
require_once "db.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Rediriger vers la page de connexion (et non dashboard.php)
    exit();
}

// Vérifier si l'utilisateur est admin
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT is_admin FROM users WHERE id = '$user_id'";
$userResult = $conn->query($userQuery);
$userData = $userResult->fetch_assoc();

if (!$userData['is_admin']) {
    header("Location: dashboard.php"); // Redirection des non-admins vers Planning
    exit();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- CSS principal -->
    <link rel="stylesheet" href="style2.css">

    <title>Labo XV</title>

    <style>
        .laboratory-buttons {
            display: flex;
            justify-content: space-between;
        }

        .laboratory-buttons button {
            flex: 1;
            margin: 5px;
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .laboratory-buttons button.active {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
        <section id="sidebar" class="hide">

        <a href="#" class="brand">
            <span class="text">Labo XV</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="dashboard.php">
                    <i class="bx bxs-dashboard"></i>
                    <span class="text">Planning</span>
                </a>
            </li>
            <li>
                <a href="heures.php">
                    <i class="bx bxs-doughnut-chart"></i>
                    <span class="text">Décompte d'heures</span>
                </a>
            </li>
            <li>
                <a href="employers.php">
                    <i class="bx bxs-group"></i>
                    <span class="text">Employés</span>
                </a>
            </li>
            <li class="active">
                <a href="conges.php">
                    <i class='bx bx-calendar-check'></i>
                    <span class="text">Congés</span>
                </a>
            </li>

            <li>
        <a href="badgeuse.php">
        <i class='bx bxs-user-check'></i>
            <span class="text">Badgeuse</span>
        </a>
    </li>
        </ul>
        <ul class="side-menu">
        <li>
    <a href="logout.php" class="logout">
        <i class="bx bxs-log-out-circle"></i>
        <span class="text">Déconnexion</span>
    </a>
</li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class="bx bx-menu"></i>
            <form action="#">
                <div class="form-input"></div>
            </form>
            <input type="checkbox" id="switch-mode" hidden />
            <a href="#" class="notification"></a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Congés</h1>
                </div>
            </div>

            <!-- Formulaire de création -->
            <div class="create-user">
                <h3 class="text" onclick="toggleForm()">Créer un congé <i class='bx bx-chevron-down'></i></h3>
                <div class="form-container">
                    <form method="POST" action="create_conges.php">
                    <label for="employee">Employé :</label>
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
    <option value="enfant_malade">Examen</option>
    <option value="enfant_malade">Enfant Malade</option>
    <option value="Revision">Semaine de révision</option>
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

            <!-- Sélection de la période pour affichage -->
            <div class="date-selection">
                <label for="start-date-filter">Date de début :</label>
                <input type="date" id="start-date-filter">
                <label for="end-date-filter">Date de fin :</label>
                <input type="date" id="end-date-filter">
                <button onclick="fetchConges()">Afficher</button>
            </div>

            <!-- Tableau des congés -->
            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Liste des congés</h3>
                        <input type="text" id="searchInput" placeholder="Rechercher un employé..." onkeyup="filterEmployees()">
                        </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type d'absence</th>
                                <th>Jours d'absences</th>
                                <th>Période</th>
                                <th>Modifier</th>
                                <th>Supprimer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Données dynamiques ici -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <script src="script3.js"></script>

    

</body>
<script src="sql.js"></script>
<script>
function filterEmployees() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.querySelector("table tbody");
    const rows = table.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        const td = rows[i].getElementsByTagName("td")[0]; // On filtre sur le nom (première colonne)
        if (td) {
            const txtValue = td.textContent || td.innerText;
            rows[i].style.display = txtValue.toLowerCase().includes(filter) ? "" : "none";
        }
    }
}
</script>

</html>
