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


?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- My CSS -->
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

	<script>
		function toggleLaboratory(button) {
			button.classList.toggle('active');
			const container = document.getElementById('laboratories-container');
			const existingInput = document.querySelector(`input[name='laboratories[]'][value='${button.value}']`);

			if (button.classList.contains('active')) {
				if (!existingInput) {
					const input = document.createElement('input');
					input.type = 'hidden';
					input.name = 'laboratories[]';
					input.value = button.value;
					container.appendChild(input);
				}
			} else {
				if (existingInput) {
					container.removeChild(existingInput);
				}
			}
		}
	</script>
</head>
<body>


	<!-- SIDEBAR -->
    <section id="sidebar" class ='hide' >
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

        <li class="active">
		<a href="employers.php">
        <i class="bx bxs-group"></i>
        <span class="text">
            <?php echo ($userData['is_admin'] ? 'Collaborateurs' : 'Mon Profil'); ?>
        </span>
    </a>
          </li>

        </li>

		<li>
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
        <a href="#" class="notification"> </a>
      
      </nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		<main>
			<div class="head-title">
				<div class="left">
				<?php echo ($userData['is_admin'] ? '<h1>Collaborateurs</h1>' : '<h1>Profil</h1>'); ?>
				</div>
			</div>

			<?php if ($userData['is_admin']) { ?>

			<!-- Formulaire de création -->
			<div class="create-user">
    <h3 class = "text" onclick="toggleForm()">Créer un nouveau Collaborateur <i class='bx bx-chevron-down'></i></h3>
    <div class="form-container">
        <form method="POST" action="create_user.php">
            <label for="name">Nom :</label>
            <input type="text" id="name" name="name" required>
			<label for="email">Email :</label>
<input type="email" id="email" name="email" >

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>

            <label for="role">Rôle :</label>
			<select id="role" name="role[]" multiple required>
			<option value="Boss">Boss</option>
				<option value="Docteur">Docteur</option>
				<option value="Qualité">Qualité</option>
				<option value="Préléveur">Préléveur</option>
				<option value="Bactério">Bactério</option>
				<option value="Immuno">Immuno</option>
				<option value="Secrétaire">Secrétaire</option>
				<option value="Apprenti">Apprenti </option>
				<option value="Apprenti">Apprenti Bacterio </option>
				<option value="Apprenti">Apprenti Immuno </option>
				<option value="Apprenti">Apprenti Secretaire </option>

				<option value="Collaborateur">Collaborateur</option>
				<option value="Stagiaire">Stagiaire</option>

            </select>

            <label>Laboratoires :</label>
            <div class="laboratory-buttons">
                <button type="button" value="Vaugirard" onclick="toggleLaboratory(this)">Vaugirard</button>
                <button type="button" value="Grignon" onclick="toggleLaboratory(this)">Grignon</button>
                <button type="button" value="Mozart" onclick="toggleLaboratory(this)">Mozart</button>
            </div>
            <div id="laboratories-container"></div>

			<label for="is_admin">Admin :</label>
			<input type="checkbox" id="is_admin" name="is_admin" value="1">


            <button type="submit">Créer</button>
        </form>
    </div>
</div>

<?php } ?>

			<div class="table-data">
				<div class="order">
				<div class="head">


				<?php echo ($userData['is_admin'] ? '<h3>Liste des Collaborateurs</h3>' : '<h3>Mon Profil</h3>'); ?>
				<?php if ($userData['is_admin']) : ?>
    <input type="text" id="searchInput" placeholder="Rechercher un Collaborateur..." onkeyup="filterEmployees()">
<?php endif; ?>
				</div>

					<table>
						<thead>
						<tr>
    <th>Nom</th>
    <th>Rôle</th>
    <th>Laboratoires</th>
    <th>Modifier</th>
    <?php if ($userData['is_admin']) { ?>
        <th>Supprimer</th>
    <?php } ?>
</tr>

						</thead>
						<tbody>
							<?php
							if ($userData['is_admin']) {
								// Admin : affiche tous les utilisateurs
								$query = "SELECT u.id, u.name, u.role, GROUP_CONCAT(l.laboratory SEPARATOR ', ') AS laboratories
								FROM users u
								LEFT JOIN user_laboratories l ON u.id = l.user_id
								GROUP BY u.id
								ORDER BY SUBSTRING_INDEX(u.name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC";
							} else {
								// Non admin : affiche uniquement l'utilisateur connecté
								$query = "SELECT u.id, u.name, u.role, GROUP_CONCAT(l.laboratory SEPARATOR ', ') AS laboratories
								FROM users u
								LEFT JOIN user_laboratories l ON u.id = l.user_id
								WHERE u.id = '$user_id'
								GROUP BY u.id";
							}
							

							$result = $conn->query($query);

							if ($result->num_rows > 0) {
								while ($row = $result->fetch_assoc()) {
									echo "<tr>";
echo "<td>" . htmlspecialchars($row['name']) . "</td>";
echo "<td>" . htmlspecialchars(str_replace(',', ', ', $row['role'])) . "</td>";
echo "<td>" . htmlspecialchars($row['laboratories'] ?? 'Aucun') . "</td>";
echo "<td><a href='edit_user.php?id=" . $row['id'] . "'><i class='bx bxs-edit'></i></a></td>";
if ($userData['is_admin']) {
    echo "<td><a href='delete_user.php?id=" . $row['id'] . "'><i class='bx bx-message-square-x bx-rotate-270'></i></a></td>";
}
echo "</tr>";

								}
							} else {
								echo "<tr><td colspan='5'>Aucun Collaborateur trouvé.</td></tr>";
							}
							
							?>
						</tbody>
					</table>
				</div>
			</div>
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	
	<script src="script2.js"></script>

</body>

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
