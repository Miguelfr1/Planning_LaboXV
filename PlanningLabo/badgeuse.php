<?php


session_start();

date_default_timezone_set('Europe/Paris'); // Pour l'heure française

require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Rediriger vers la page de connexion (et non dashboard.php)
    exit();
}



// Vérifier si l'utilisateur est connecté
$user_id = $_SESSION['user_id'] ?? null;
$isAdmin = false;

if ($user_id) {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $isAdmin = $userData['is_admin'] ?? false;
    $stmt->close();
}



// Récupérer la date sélectionnée ou utiliser la date du jour
$date_selected = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Vérifier que la date est valide
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_selected)) {
    $date_selected = date('Y-m-d'); // Sécurisation
}

// Récupérer le début de la semaine de la date sélectionnée (Lundi)
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($date_selected)));

// Déterminer l'index du jour de la semaine (1 = Lundi, 7 = Dimanche)
$day_index = date('N', strtotime($date_selected)); // 1 à 7

// Liste des jours en texte (pour la base de données)
$jours_fr = [
    1 => "Lundi",
    2 => "Mardi",
    3 => "Mercredi",
    4 => "Jeudi",
    5 => "Vendredi",
    6 => "Samedi",
    7 => "Dimanche"
];

$day_in_french = $jours_fr[$day_index];

$schedule = null;
if ($user_id && !$isAdmin) {
    $stmt = $conn->prepare("SELECT start_time, end_time FROM schedules WHERE user_id = ? AND week_start = ? AND day_of_week = ?");
    $stmt->bind_param("iss", $user_id, $week_start, $day_in_french);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();
}

// Vérifier si l'utilisateur a déjà badgé aujourd'hui
$badgeage = null;
if ($user_id && !$isAdmin) {
    $stmt = $conn->prepare("SELECT entree_time, sortie_time FROM badgeages WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date_selected);
    $stmt->execute();
    $result = $stmt->get_result();
    $badgeage = $result->fetch_assoc();
    $stmt->close();
}

// Vérifier si l'utilisateur a déjà badgé aujourd'hui
$badge_entree = null;
$badge_sortie = null;

if ($user_id && !$isAdmin) {
    $stmt = $conn->prepare("SELECT entree_time, sortie_time FROM badgeages WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date_selected);
    $stmt->execute();
    $result = $stmt->get_result();
    $badge = $result->fetch_assoc();
    $stmt->close();

    if ($badge) {
        $badge_entree = $badge['entree_time'];
        $badge_sortie = $badge['sortie_time'];
    }
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style2.css" />
    <title>Badgeuse</title>
</head>
<body>

<?php if (isset($_GET['success'])): ?>
    <div class="alert success"><?php echo htmlspecialchars($_GET['success']); ?></div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>


    <!-- SIDEBAR -->
        <section id="sidebar" class="hide">

        <a href="#" class="brand">
            <span class="text">Labo XV</span>
        </a>
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
</li>
            <li><a href="conges.php"><i class='bx bx-calendar-check'></i><span class="text">Congés</span></a></li>

            <li class="active"><a href="badgeuse.php">        <i class='bx bxs-user-check'></i>
            <span class="text">Badgeuse</span></a></li>
        </ul>
        <ul class="side-menu">
            <?php if ($user_id): ?>
                <li><a href="logout.php" class="logout"><i class="bx bxs-log-out-circle"></i><span class="text">Déconnexion</span></a></li>
            <?php endif; ?>
        </ul>
    </section>

    <section id="content">
        <nav>
            <i class="bx bx-menu"></i>
        </nav>

        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Badgeuse</h1>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <div class="date-selection">
                    <label for="date-selector">Sélectionner une date :</label>
                    <input type="date" id="date-selector" value="<?php echo htmlspecialchars($date_selected); ?>" onchange="updateBadgeuse()">
                </div>

                <div class="table-data">
                    <div class="order">
                        <div class="head">
                            <h3>Liste des Collaborateurs</h3>
                        </div>
                        <table>
                            <thead>
    <tr>
        <th>Nom</th>
        <th>Heure Début</th>
        <th>Heure Fin</th>
        <th>Badge Entrée</th>
        <th>Badge Sortie</th>
        <th>Différence</th> 
    </tr>
</thead>

                            <tbody>
<?php
$query = "
SELECT 
    u.name, 
    s.start_time, 
    s.end_time, 
    b.entree_time, 
    b.sortie_time 
FROM schedules s 
JOIN users u ON s.user_id = u.id 
LEFT JOIN badgeages b ON u.id = b.user_id AND b.date = ?
WHERE s.week_start = ? AND s.day_of_week = ?
ORDER BY SUBSTRING_INDEX(u.name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC, u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $date_selected, $week_start, $day_in_french); // ✅ Correct : 3 paramètres pour 3 `?`
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $heure_prevue_debut = $row['start_time'];
        $heure_prevue_fin = $row['end_time'];
        $heure_badgee_entree = $row['entree_time'] ?? null;
        $heure_badgee_sortie = $row['sortie_time'] ?? null;
    
        $difference_total = "Non terminé";
        $couleur = "orange"; 
    
        if (!empty($heure_badgee_entree) && !empty($heure_badgee_sortie)) {
            $time_prevu_total = strtotime($heure_prevue_fin) - strtotime($heure_prevue_debut);
            $time_travail_total = strtotime($heure_badgee_sortie) - strtotime($heure_badgee_entree);
            $diff_seconds = $time_travail_total - $time_prevu_total;
    
            if ($diff_seconds > 0) {
                $difference_total = "+" . gmdate("H:i:s", $diff_seconds);
                $couleur = "green";
            } elseif ($diff_seconds < 0) {
                $difference_total = "-" . gmdate("H:i:s", abs($diff_seconds)); 
                $couleur = "red";
            } else {
                $difference_total = "00:00:00"; // ✅ Pile à l'heure
                $couleur = "black";
            }
        }
    
        echo "<tr>
                <td>{$row['name']}</td>
                <td>{$row['start_time']}</td>
                <td>{$row['end_time']}</td>
                <td>" . (!empty($row['entree_time']) ? $row['entree_time'] : "Non badgé") . "</td>
                <td>" . (!empty($row['sortie_time']) ? $row['sortie_time'] : "Non badgé") . "</td>
                <td style='color: $couleur; font-weight: bold;'>$difference_total</td>
              </tr>";
    }
    
} else {
echo "<tr><td colspan='5'>Aucune donnée pour cette date</td></tr>";
}

$stmt->close();

                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="badge-section">
    <h3>Badger votre présence</h3>
    
    <?php if ($schedule): ?>
        <p><strong>Horaire prévu :</strong> <?php echo $schedule['start_time']; ?> - <?php echo $schedule['end_time']; ?></p>

        <form method="POST" action="badger.php" id="badgeForm">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

            <button type="submit" name="action" value="entrée" id="btn-entree"
    <?php echo !empty($badge_entree) ? 'disabled' : ''; ?>>
    <?php echo !empty($badge_entree) ? "Badgé à " . $badge_entree : "Badger Entrée"; ?>
</button>

<button type="submit" name="action" value="sortie" id="btn-sortie"
    <?php echo !empty($badge_sortie) ? 'disabled' : ''; ?>>
    <?php echo !empty($badge_sortie) ? "Badgé à " . $badge_sortie : "Badger Sortie"; ?>
</button>

        </form>

    <?php else: ?>
        <p><strong>Horaire prévu :</strong> Non défini</p>
        <p><em>Vous n'avez pas d'horaire prévu aujourd'hui.</em></p>
    <?php endif; ?>
</div>

    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
   
</form>



    <p id="badge-time"></p>
</div>

            <?php endif; ?>
        </main>
    </section>


    <script>
        function updateBadgeuse() {
            let selectedDate = document.getElementById("date-selector").value;
            window.location.href = `badgeuse.php?date=${selectedDate}`;
        }

      
    </script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var menuBtn = document.querySelector('.bx-menu');

    // À l'ouverture de la page, on lit l'état stocké
    var sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'open') {
        sidebar.classList.remove('hide');
    } else {
        sidebar.classList.add('hide');
    }

    // Quand on clique sur le menu
    if (menuBtn) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('hide');
            // Sauve l'état actuel
            if (sidebar.classList.contains('hide')) {
                localStorage.setItem('sidebarState', 'closed');
            } else {
                localStorage.setItem('sidebarState', 'open');
            }
        });
    }
});

</script>

<script src="script.js"></script>


</body>

<script src="sql.js"></script>

</html>

