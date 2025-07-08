
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

        <li class="active">
        <a href="heures.php">
            <i class="bx bxs-doughnut-chart"></i>
            <span class="text">Décompte d'heures</span>
          </a>
        </li>

        <li >
          <a href="employers.php">
          
            <i class="bx bxs-group"></i>
            <?php echo ($userData['is_admin'] ? 'Collaborateurs' : 'Mon Profil'); ?>
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
        <h1>Décompte d'heures</h1>
        <?php if ($userData['is_admin']): ?>
<div style="margin:15px 0;">
    <button onclick="printSection()" style="margin-right: 10px;">Imprimer</button>
    <button onclick="exportTableToJPG()">Exporter en JPG</button>
</div>
<?php endif; ?>

</div>


			<!-- Sélection de la plage de dates -->
			<div class="date-selection">
				<label for="start-date">Date de début :</label>
				<input type="date" id="start-date">
				<label for="end-date">Date de fin :</label>
				<input type="date" id="end-date">
				<button onclick="fetchHours()">Afficher</button>
			</div>

            <div class="table-data">
				<div class="order">
				<div class="head">
    <h3>Liste des Collaborateurs</h3>
    <?php if ($userData['is_admin']): ?>
        <input type="text" id="searchInput" placeholder="Rechercher un Collaborateur..." onkeyup="filterEmployees()">
    <?php endif; ?>
</div>

					<table>
						<thead>
							<tr>
								<th>Nom</th>
								<th>Total heures régulières / mois	</th>
								<th>+ Heures supps semaine 25%	</th>
								<th>+ Heures supps semaine 50%	</th>
								<th>+ Heures dimanche 50%	</th>
								<th>+ Heures jours fériés 50%</th>
								<th class="col-diff">+ Différence</th>
								
							</tr>
						</thead>
						<tbody id="employee-hours">
							<!-- Contenu généré dynamiquement -->
						</tbody>
					</table>
				</div>
			</div>

		</main>

		<script src="script2.js">


	</script>

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
<script>

function toFrenchDate(isoDate) {
    if (!isoDate) return '';
    const [year, month, day] = isoDate.split('-');
    return `${day}/${month}/${year}`;
}





function printSection() {
    const printContents = document.querySelector('.table-data').outerHTML;

    // Récupère les valeurs des dates sélectionnées
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;



	const startFr = toFrenchDate(startDate);
    const endFr = toFrenchDate(endDate);

	
    // Construit le bloc affichant la plage horaire (à droite, en haut)
	const dateRange = `
    <div class="date-print-range">
        Période : <strong>${startFr}</strong> au <strong>${endFr}</strong>
    </div>
`;

    const printWindow = window.open('', '', 'height=900,width=1200');

    printWindow.document.write('<html><head><title>Décompte d\'heures</title>');
    printWindow.document.write('<link rel="stylesheet" href="style2.css" type="text/css" />');
    printWindow.document.write(`
        <style>
        @media print {
            
 .col-diff, #searchInput { display:none!important; }
             {
                overflow: hidden !important;
            }

            .date-print-range {
                position: absolute;
                top: -13px;
                right: 150;
                margin: 20px 40px 0 0;
                font-size: 20px;
                font-weight: bold;
                color: #333;
            }
        }
        </style>
    `);
    printWindow.document.write('</head><body>');
    // Ajoute la date avant la table !
    printWindow.document.write(dateRange);
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}


// Exporter le tableau en JPG avec html2canvas
function exportTableToJPG() {
    const tableDiv = document.querySelector('.table-data');
    html2canvas(tableDiv).then(function(canvas) {
        // Crée un lien pour télécharger l'image
        let link = document.createElement('a');
        link.href = canvas.toDataURL("image/jpeg");
        link.download = 'tableau_heures.jpg';
        link.click();
    });
}
</script>
<style>
@media print {
    .col-diff,
    #searchInput {
        display: none !important;
    }
	
}

</style>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

</body>


</html>