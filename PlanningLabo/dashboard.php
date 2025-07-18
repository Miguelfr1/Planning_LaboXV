
    <?php
    session_start();
    require_once "db.php";

    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr', 'French_France.1252');
    $mois = [
        1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avri',
        5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
        9 => 'sept', 10 => 'octo', 11 => 'nove', 12 => 'déc.'
    ];
    

    $allLabs = isset($_GET['all_labs']) && $_GET['all_labs'] == '1';

    $labs_hours = [
        'plateau technique' => [
        'Lundi'    => ['open' => '07:30', 'close' => '20:00'],
        'Mardi'    => ['open' => '07:30', 'close' => '20:00'],
        'Mercredi' => ['open' => '07:30', 'close' => '20:00'],
        'Jeudi'    => ['open' => '07:30', 'close' => '20:00'],
        'Vendredi' => ['open' => '07:30', 'close' => '20:00'],
        'Samedi'   => ['open' => '07:30', 'close' => '16:00'],
        'Dimanche' => ['open' => '09:00', 'close' => '14:00'],
        ],
        '351_vaugirard' => [ // <-- AJOUTER les horaires pour 351 VAUGIRARD
        // Mets les horaires voulus ici
        'Lundi'    => ['open' => '07:30', 'close' => '20:00'],
        'Mardi'    => ['open' => '07:30', 'close' => '20:00'],
        'Mercredi' => ['open' => '07:30', 'close' => '20:00'],
        'Jeudi'    => ['open' => '07:30', 'close' => '20:00'],
        'Vendredi' => ['open' => '07:30', 'close' => '20:00'],
        'Samedi'   => ['open' => '07:30', 'close' => '16:00'],
        'Dimanche' => ['open' => '09:00', 'close' => '14:00'],
    ],
        'mozart' => [
        'Lundi'    => ['open' => '07:30', 'close' => '18:00'],
        'Mardi'    => ['open' => '07:30', 'close' => '18:00'],
        'Mercredi' => ['open' => '07:30', 'close' => '18:00'],
        'Jeudi'    => ['open' => '07:30', 'close' => '18:00'],
        'Vendredi' => ['open' => '07:30', 'close' => '18:00'],
        'Samedi'   => ['open' => '07:30', 'close' => '16:00'],
        'Dimanche' => null, // Fermé
        ],
        'grignon' => [
        'Lundi'    => ['open' => '07:30', 'close' => '14:30'],
        'Mardi'    => ['open' => '07:30', 'close' => '14:30'],
        'Mercredi' => ['open' => '07:30', 'close' => '14:30'],
        'Jeudi'    => ['open' => '07:30', 'close' => '14:30'],
        'Vendredi' => ['open' => '07:30', 'close' => '14:30'],
        'Samedi'   => ['open' => '07:30', 'close' => '14:30'],
        'Dimanche' => ['open' => '09:00', 'close' => '13:00'],
        ],
    ];
    
    // Vérifier si l'utilisateur est connecté (mais ne pas rediriger)
    $user_id = $_SESSION['user_id'] ?? null;
    $isAdmin = false;

    if ($user_id) {
        $userQuery = "SELECT is_admin FROM users WHERE id = '$user_id'";
        $userResult = $conn->query($userQuery);
        $userData = $userResult->fetch_assoc();
        $isAdmin = $userData['is_admin'] ?? false;
    }
    ?>

    <?php
    if (isset($_GET['alert'])) {
        $alertMessage = urldecode($_GET['alert']); // Décodage correct
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert(\"" . addslashes($alertMessage) . "\");
            });
        </script>";
    }
    ?>





    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <link
        href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css"
        rel="stylesheet"
        />
        <link rel="stylesheet" href="style.css" />

        <title>Labo XV</title>

        <script>
        var isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    </script>

    <!-- éditeur riche -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
        selector: '#editor',
        menubar: false,
        plugins: 'lists link textcolor colorpicker',
        toolbar: 'undo redo | bold italic underline | forecolor backcolor | bullist numlist | link',
        height: 300
        });
    </script>

    </head>

    <body>

    <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
    <div class="alert alert-danger" id="error-alert" style="color:#a00; background:#fee; padding:16px; border:2px solid #a00; font-size:18px; font-weight:bold; text-align:center; margin-bottom:24px; z-index:10000;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <script>
        setTimeout(function() {
            var alert = document.getElementById('error-alert');
            if(alert) alert.style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>



        <!-- SIDEBAR -->
        <section id="sidebar" class="hide">
        <a href="#" class="brand">
            <span class="text">Labo XV</span>
        </a>
        <ul class="side-menu top">
        <li class="active">
            <a href="dashboard.php"> <!-- Planning accessible à tous -->
                <i class="bx bxs-dashboard"></i>
                <span class="text">Planning</span>
            </a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>  <!-- Seuls les connectés voient le bouton Déconnexion -->

        <li>
            <a href="heures.php">
                <i class="bx bxs-doughnut-chart"></i>
                <span class="text">Décompte d'heures</span>
            </a>
        </li>

        <li>
        <a href="employers.php">
            <i class="bx bxs-group"></i>
            <span class="text">
                <?php echo ($userData['is_admin'] ? 'Collaborateurs' : 'Mon Profil'); ?>
            </span>
        </a>
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
        
        <?php endif; ?>

    </ul>

    <ul class="side-menu">
        <?php if (isset($_SESSION['user_id'])): ?>  <!-- Seuls les connectés voient le bouton Déconnexion -->
        <li>
            <a href="logout.php" class="logout">
                <i class="bx bxs-log-out-circle"></i>
                <span class="text">Déconnexion</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>


        </section>
        
        <section id="content">
        <nav>

            <i class="bx bx-menu"></i>
            <form action="#">
                <div class="form-input"></div>
            </form>
            <input type="checkbox" id="switch-mode" hidden />
            <a href="#" class="notification"> </a>
            <div class="user-welcome">
        <?php if (isset($_SESSION['user_id'])): ?> 
            <p>Bonjour, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 👋</p>
        <?php else: ?>
            <a href="index.php" class="login-button">Se connecter</a>
        <?php endif; ?>
    </div>

        </nav>
        
        <main>
        <div class="sticky-header">


            <div class="head-title">
            <div class="left">
                <h1>Planning</h1>

            </div>

            <?php
        // Pour les slots (doux)
        $slotColorsByRole = [
            'Boss' => '#c7b5e4',
            'Biologiste' => '#c7b5e4',
            'Bactério' => '#FF8A80',
            'Bacterio' => '#FF8A80',
            'Qualité' => '#e0e0e0',
            'Immuno' => '#FF8A80',
            'Préleveur' => '#FF8A80',
            'Secrétaire' => '#b3d1fc',           // bleu (même que l'ancien Apprenti Secrétaire)
            'Apprenti Immuno' => '#FFA500',      // orange
            'Apprenti Bacterio' => '#FFF59D',    // jaune pastel
            'Apprenti Secretaire' => '#b3e6ff',  // bleu clair
            'Stagiaire' => '#dddddd'
        ];
        
        // Pour le nom (flashy)
        $nameColorsByRole = [
            'Boss' => 'violet',
            'Biologiste' => 'violet',
            'Bactério' => 'red',
            'Bacterio' => 'red',
            'Qualité' => 'black',
            'Immuno' => 'red',
            'Préleveur' => 'red',
            'Secrétaire' => '#00aaff',           // bleu
            'Apprenti Immuno' => 'orange',       // orange bien visible
            'Apprenti Bacterio' => '#FFD600',    // jaune flashy
            'Apprenti Secretaire' => '#4fc3f7',  // bleu plus clair (bleu "sky", visible)
            'Stagiaire' => 'black'
        ];
        
            
            
            

            $slotColors = ['#d1ecf1', '#f1d1ec', '#ecf1d1', '#d1f1ec', '#ecd1f1', '#f1ecd1', '#e8f8f5', '#e3f2fd', '#fef9e7'];
            $colorCount = count($slotColors);

            // Vérifier la date sélectionnée ou utiliser la date actuelle
            $selectedDate = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d');
            $searchName = isset($_GET['search']) ? trim($_GET['search']) : '';
            $selectedLab = isset($_GET['laboratory']) ? $_GET['laboratory'] : 'Plateau Technique';
            $allLabs = isset($_GET['all_labs']) && $_GET['all_labs']=='1';
                                // Calculer le début de la semaine (lundi)
            $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));

            $existingComment = '';
    $commentQuery = $conn->prepare("SELECT content FROM weekly_comments WHERE week_start = ? AND laboratory = ?");
    $commentQuery->bind_param("ss", $weekStart, $selectedLab);
    $commentQuery->execute();
    $commentResult = $commentQuery->get_result();
    if ($row = $commentResult->fetch_assoc()) {
        $existingComment = $row['content'];
    }
    $commentQuery->close();
            ?>
<div class="week-selector" style="display: flex; align-items: center; gap: 10px;">
    <p style="margin:0;">Semaine du :</p>
    <button type="button" id="prev-week-btn" style=" padding: 4px 10px;">&#8592;</button>
    <input type="date" id="week-start-selector" value="<?= $selectedDate ?>" onchange="updateFilters()" style="min-width:120px;"/>
    <button type="button" id="next-week-btn" style= "padding: 4px 10px;">&#8594;</button>
    <button id="export-jpg-btn" style="margin-left: 10px;">Exporter en JPG</button>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("week-start-selector");
    const prev = document.getElementById("prev-week-btn");
    const next = document.getElementById("next-week-btn");

    function getMonday(dateStr) {
        const d = new Date(dateStr);
        // force au lundi (0=dimanche, 1=lundi)
        const day = d.getDay();
        const diff = d.getDate() - ((day + 6) % 7); // lundi = 1
        d.setDate(diff);
        return d;
    }

    function setNewDate(days) {
        let d = getMonday(input.value || (new Date()).toISOString().slice(0,10));
        d.setDate(d.getDate() + days);
        // format AAAA-MM-JJ
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const iso = `${yyyy}-${mm}-${dd}`;
        input.value = iso;
        updateFilters();
    }

    prev.addEventListener("click", function() {
        setNewDate(-7);
    });
    next.addEventListener("click", function() {
        setNewDate(7);
    });
});
</script>
     
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const exportBtn = document.getElementById("export-jpg-btn");
        const labSelector = document.getElementById("lab-selector");

        exportBtn.onclick = function () {
            const lab = labSelector.value;
            if (lab === "mozart" || lab === "grignon") {
                exportToJPG2(); // appelle ta fonction JPG1
            } else {
                exportToJPG1(); // appelle ta fonction JPG2
            }
        };
    });



    
    </script>
    <div class="right">
    <select id="lab-selector" class="lab-selector" onchange="updateFilters()">
    <option value="Plateau Technique" <?= $selectedLab == 'Plateau Technique' ? 'selected' : '' ?>>PLATEAU TECHNIQUE</option>
    <option value="351_vaugirard" <?= $selectedLab == '351_vaugirard' ? 'selected' : '' ?>>351, VAUGIRARD</option>
    <option value="mozart" <?= $selectedLab == 'mozart' ? 'selected' : '' ?>>MOZART</option>
    <option value="grignon" <?= $selectedLab == 'grignon' ? 'selected' : '' ?>>GRIGNON</option>
</select>

    <label style="margin-left:10px;">
        <input type="checkbox" id="all-labs-toggle" <?= isset($_GET['all_labs']) && $_GET['all_labs']=='1' ? 'checked' : '' ?> onchange="updateFilters()" />
        Tous les laboratoires
    </label>
    </div>


            </div>
            <div class="search-user-container">
    <input type="text" id="search-user" class="search-user" placeholder="Rechercher un nom..." oninput="filterRows(); toggleClearBtn();" value="<?= htmlspecialchars($searchName) ?>" />
    <button type="button" id="clear-search-btn" class="clear-search-btn" onclick="clearSearch()" style="display:none;" tabindex="-1">&times;</button>
</div>

</div>


            <div class="table-data">
            <div class="order">
                <div class="row header">
                <div class="cell name">Nom</div>
    <div class="cell total-hours-header">Heures Semaine</div>

                <?php
                    $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    // Liste des jours fériés français (à compléter selon ton besoin)
                $jours_feries = [
                        '2025-01-01', // Jour de l'an
                        '2025-04-21', // Lundi de Pâques
                        '2025-05-01', // Fête du Travail
                        '2025-05-08', // Victoire 1945
                        '2025-05-29', // Ascension
                        '2025-07-14', // Fête nationale
                        '2025-08-15', // Assomption
                        '2025-11-01', // Toussaint
                        '2025-11-11', // Armistice
                        '2025-12-25', // Noël
                    ];
                    $previousMonth = date('m', strtotime("$weekStart")); // le mois du lundi
                    foreach ($days as $index => $day) {
                        $currentDay = date('Y-m-d', strtotime("$weekStart +$index days"));
                        $currentDayDate = date('d', strtotime($currentDay));
                        $currentMonth = date('m', strtotime($currentDay));
                        $currentMonthNum = (int)date('m', strtotime($currentDay));
                        $moisLettres4 = $mois[$currentMonthNum];
                        
                    
                        $isFerie = in_array($currentDay, $jours_feries);
                        $isDimanche = ($day === 'Dimanche');
                        $dayClass = ($isFerie || $isDimanche) ? 'day-special' : '';
                        $separatorClass = '';
                        if ($index > 0 && $currentMonth != $previousMonth) {
                            $separatorClass = 'month-separator';
                        }
                        $previousMonth = $currentMonth;
                    
                        echo "<div class='cell day {$dayClass} {$separatorClass}'>"
                        . "{$day} {$currentDayDate} {$moisLettres4}"
                        . "</div>";
                    
                    }
                    
                    
                ?>

      
                </div>

                <?php

                
    require_once "db.php";

    if ($searchName !== '' || $allLabs) {
        $usersQuery = "
            SELECT u.id, u.name, u.role, s.laboratory,
            COALESCE(
            SUM(
                TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
                - COALESCE(s.break_duration, 0)
            ),
            0
            ) AS total_minutes
            FROM users u
            LEFT JOIN schedules s 
                ON u.id = s.user_id AND s.week_start = '$weekStart'
            WHERE u.name LIKE '%" . $conn->real_escape_string($searchName) . "%'
            GROUP BY u.id, u.name, u.role, s.laboratory
            HAVING s.laboratory IS NOT NULL
            
            ORDER BY 

                CASE
                    WHEN FIELD(
                        COALESCE(
                            (SELECT s2.role
                                FROM schedules s2
                                WHERE s2.user_id = u.id
                                    AND s2.week_start = '$weekStart'
                                    AND s2.laboratory = s.laboratory
                                    AND s2.role IS NOT NULL
                                LIMIT 1
                            ),
                            u.role
                        ),
                        'Boss',
                        'Biologiste',
                        'Bacterio',
                        'Immuno',
                        'Préleveur',
                        'Qualité',
                        'Secrétaire',
                        'Apprenti Secretaire',
                        'Apprenti Bacterio',
                        'Apprenti Immuno',
                        'Apprenti',
                        'Stagiaire'
                    ) = 0 THEN 999
                    ELSE FIELD(
                        COALESCE(
                            (SELECT s2.role
                                FROM schedules s2
                                WHERE s2.user_id = u.id
                                    AND s2.week_start = '$weekStart'
                                    AND s2.laboratory = s.laboratory
                                    AND s2.role IS NOT NULL
                                LIMIT 1
                            ),
                            u.role
                        ),
                        'Boss',
                        'Biologiste',
                        'Bacterio',
                        'Immuno',
                        'Préleveur',
                        'Qualité',
                        'Secrétaire',
                        'Apprenti Secretaire',
                        'Apprenti Bacterio',
                        'Apprenti Immuno',
                        'Apprenti',
                        'Stagiaire'
                    )
                END,
                CASE
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Biologiste'
                    THEN FIELD(u.name,
                        'Dr Natalio AWAIDA',
                        'Dr Antoine KHOURY',
                        'Dr Firas CHOUKRI',
                        'Dr Karin Fredman'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Bacterio'
                    THEN FIELD(u.name,
                        
                        'Mustapha ARAFAT',
                        'Andrei BURDENIUC',
                        'Naomasa KAWASHIMA'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Immuno'
                    THEN FIELD(u.name,
                        'Nacera AMIRI',
                        'Lisa ALLOUT'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Secrétaire'
                    THEN FIELD(u.name,
                        'Samar HUSSEIN',
                        'Joelle ABI ABBOUD',
                        'Jagoda FEDYSZYN',
                        'Nessrine BEN AMMAR',
                        'Derya TULEK',
                        'Nadedja CHIRIAC',
                       ' Mémouna SISSOKO',
                       'Lena AMAOUCHE'


                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Apprenti Bacterio'
                    THEN FIELD(u.name,
                        'Jonathan SIGNE',
                        'Wassim MENDACI'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Apprenti Immuno'
                    THEN FIELD(u.name,
                      'Camille MONCERET',

                    'Farahna CHARIFFOU',

                        'Jediah SOUHNIN',
                        'Djalleycha FRANCIS',
                        'Lena AMAOUCHE',
                        'Kamil BELBOUAB'
                        
                    )
                    WHEN COALESCE(
        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = s.laboratory AND s2.role IS NOT NULL LIMIT 1),
        u.role
    ) = 'Préleveur'
    THEN FIELD(u.name,
        'Dr Antoine KHOURY',
        'Dr Firas CHOUKRI',
        'Naomasa KAWASHIMA',
        'Andrei BURDENIUC',
        'Lisa ALLOUT',
        'Nacera AMIRI',
       ' Caroline CIRETTE',
       'Fadila DJELLEL',
       'Farahna CHARIFFOU',
       'Sophie AMEDIEN'



    )

                        
                    ELSE 0
                END,
                SUBSTRING_INDEX(u.name, ' ', -1) ASC
        ";
    }

    else {
        // Version filtrée labo + tri complexe
        $usersQuery = "
            SELECT u.id, u.name, u.role, 
            COALESCE(
            SUM(
                TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
                - COALESCE(s.break_duration, 0)
            ),
            0
            ) AS total_minutes
            FROM users u
            LEFT JOIN schedules s ON u.id = s.user_id AND s.week_start = '$weekStart'
            LEFT JOIN user_laboratories ul ON u.id = ul.user_id
            WHERE ul.laboratory = '$selectedLab'
            GROUP BY u.id, u.name, u.role
            ORDER BY 
                CASE
                    WHEN FIELD(
                        COALESCE(
                            (SELECT s2.role
                                FROM schedules s2
                                WHERE s2.user_id = u.id
                                    AND s2.week_start = '$weekStart'
                                    AND s2.laboratory = '$selectedLab'
                                    AND s2.role IS NOT NULL
                                LIMIT 1
                            ),
                            u.role
                        ),
                        'Boss',
                        'Biologiste',
                        'Bacterio',
                        'Immuno',
                        'Préleveur',
                        'Qualité',
                        'Secrétaire',
                        'Apprenti Secretaire',
                        'Apprenti Bacterio',
                        'Apprenti Immuno',
                        'Apprenti',
                        'Stagiaire'
                    ) = 0 THEN 999
                    ELSE FIELD(
                        COALESCE(
                            (SELECT s2.role
                                FROM schedules s2
                                WHERE s2.user_id = u.id
                                    AND s2.week_start = '$weekStart'
                                    AND s2.laboratory = '$selectedLab'
                                    AND s2.role IS NOT NULL
                                LIMIT 1
                            ),
                            u.role
                        ),
                        'Boss',
                        'Biologiste',
                        'Bacterio',
                        'Immuno',
                        'Préleveur',
                        'Qualité',
                        'Secrétaire',
                        'Apprenti Secretaire',
                        'Apprenti Bacterio',
                        'Apprenti Immuno',
                        'Apprenti',
                        'Stagiaire'
                    )
                END,
                CASE
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Biologiste'
                    THEN FIELD(u.name,
                        'Dr Natalio AWAIDA',
                        'Dr Antoine KHOURY',
                        'Dr Firas CHOUKRI',
                        'Dr Karin Fredman'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Bacterio'
                    THEN FIELD(u.name,
                        'Mustapha ARAFAT',
                        'Andrei BURDENIUC',
                        'Naomasa KAWASHIMA'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Immuno'
                    THEN FIELD(u.name,
                        'Nacera AMIRI',
                        'Lisa ALLOUT'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Secrétaire'
                    THEN FIELD(u.name,
                        'Samar HUSSEIN',
                        'Joelle ABI ABBOUD'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Apprenti Bacterio'
                    THEN FIELD(u.name,
                        'Jonathan SIGNE',
                        'Wassim MENDACI'
                    )
                    WHEN COALESCE(
                        (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
                        u.role
                    ) = 'Apprenti Immuno'
                    THEN FIELD(u.name,
                        'Farahna CHARIFFOU',
                        'Jediah SOUHNIN',
                        'Camille MONCERET',
                        'Lena AMAOUCHE',
                        'Kamil BELBOUAB',
                        'Djalleycha FRANCIS'
                    )

                           WHEN COALESCE(
    (SELECT s2.role FROM schedules s2 WHERE s2.user_id = u.id AND s2.week_start = '$weekStart' AND s2.laboratory = '$selectedLab' AND s2.role IS NOT NULL LIMIT 1),
    u.role
) = 'Préleveur'

    THEN FIELD(u.name,
        'Dr Antoine KHOURY',
        'Dr Firas CHOUKRI',
        'Naomasa KAWASHIMA',
        'Andrei BURDENIUC',
        'Lisa ALLOUT',
        'Nacera AMIRI',
       ' Caroline CIRETTE',
       'Fadila DJELLEL',
       'Farahna CHARIFFOU',
       'Sophie AMEDIEN'



    )
                    ELSE 0
                END,
                SUBSTRING_INDEX(u.name, ' ', -1) ASC
        ";
    }


    $usersResult = $conn->query($usersQuery);   

    while ($user = $usersResult->fetch_assoc()) {
        // 🔥 Rôle réel de la semaine (depuis schedules)
        $laboratory = $user['laboratory'] ?? $selectedLab;
        $rolesQuery = $conn->prepare(
            "SELECT DISTINCT role FROM schedules WHERE user_id = ? AND week_start = ? AND laboratory = ? LIMIT 1"
        );
        $rolesQuery->bind_param("iss", $user['id'], $weekStart, $laboratory);
        
        $rolesQuery->execute();
        $rolesResult = $rolesQuery->get_result();
        $scheduleRoleRow = $rolesResult->fetch_assoc();
        $rolesQuery->close();

        // 🔥 Récupérer le rôle à utiliser
        $scheduleRole = $scheduleRoleRow['role'] ?? null;
        if (!$scheduleRole) {
            $scheduleRole = $user['role'] ?? null;
        }

        // Couleur flashy pour le nom
        $nameColor = $nameColorsByRole[$scheduleRole] ?? 'black';
        // Couleur douce/pastel pour le slot
        $slotColor = $slotColorsByRole[$scheduleRole] ?? '#eeeeee';

        $totalHours = round($user['total_minutes'] / 60, 2);
        echo "<div class='row'>";
    // Colonne nom
    echo "<div class='cell name' style='color: {$nameColor}; font-weight:normal;'>" . htmlspecialchars($user['name']) . "</div>";
    // Nouvelle colonne total heures
    $totalHoursComma = number_format($totalHours, 2, ',', '');
    echo "<div class='cell total-hours'>" . $totalHoursComma . " h</div>";
    $previousMonth = date('m', strtotime("$weekStart")); // le mois du lundi
    foreach ($days as $index => $day) {
    $currentDay = date('Y-m-d', strtotime("$weekStart +$index days"));
    $currentMonth = date('m', strtotime($currentDay));
    $separatorClass = '';
    if ($index > 0 && $currentMonth != $previousMonth) {
        $separatorClass = 'month-separator';
    }
    $previousMonth = $currentMonth;

    $isFerie = in_array($currentDay, $jours_feries);
    $isDimanche = ($day === 'Dimanche');
    $specialClass = ($isDimanche || $isFerie) ? 'jour-special' : '';

    $realDayForHours = ($isFerie ? 'Dimanche' : $day);

    

    if ($searchName !== '' || $allLabs) {
        // Mode tous labos : on veut une ligne par employé/par labo
        $scheduleQuery = "SELECT start_time, end_time, role, laboratory, break_duration,
            TIME_FORMAT(start_time, '%H:%i') as formatted_start,
            TIME_FORMAT(end_time, '%H:%i') as formatted_end,
            TIMESTAMPDIFF(MINUTE, start_time, end_time) as minutes_worked
        FROM schedules
        WHERE user_id = {$user['id']}
            AND day_of_week = '$day'
            AND week_start = '$weekStart'
            AND laboratory = '" . $user['laboratory'] . "'
        ORDER BY start_time ASC
        LIMIT 1"; // <-- Prend uniquement le premier créneau du jour/labo (tu peux choisir le critère)
    } else {
        // Filtré labo : un seul créneau max
        $scheduleQuery = "SELECT start_time, end_time, role, break_duration,
            TIME_FORMAT(start_time, '%H:%i') as formatted_start,
            TIME_FORMAT(end_time, '%H:%i') as formatted_end,
            TIMESTAMPDIFF(MINUTE, start_time, end_time) as minutes_worked
        FROM schedules
        WHERE user_id = {$user['id']}
            AND day_of_week = '$day'
            AND week_start = '$weekStart'
            AND laboratory = '$selectedLab'
        LIMIT 1";
    }
    
    $scheduleResult = $conn->query($scheduleQuery);


    if ($scheduleResult->num_rows > 0) {
    while ($schedule = $scheduleResult->fetch_assoc()) {

                $start = $schedule['formatted_start'];
                $end = $schedule['formatted_end'];
                $pause = $schedule['break_duration'] ?? 0;
                $workedMinutes = max($schedule['minutes_worked'] - $pause, 0);
                $hoursWorked = round($workedMinutes / 60, 2);
                $startHour = intval(explode(':', $start)[0]);
                $userRoles = explode(',', $user['role'] ?? '');
                $userRoles = array_filter(array_map('trim', $userRoles));
                $userRolesJson = json_encode(array_values($userRoles), JSON_UNESCAPED_UNICODE);
                $breakDurationJS = (int) $pause;
            
                // Calcul time-bar adaptative
// Utilise le vrai laboratoire du créneau (sinon fallback sur le selectedLab)
$labFromDb = isset($schedule['laboratory']) && $schedule['laboratory'] ? $schedule['laboratory'] : $selectedLab;
$currentLab = strtolower($labFromDb); // tout en minuscule
$labDayHours = $labs_hours[$currentLab][$realDayForHours] ?? null;


                $hasBar = false;
                if ($labDayHours && isset($schedule['start_time']) && isset($schedule['end_time'])) {
                    $labOpen = strtotime($currentDay . ' ' . $labDayHours['open']);
                    $labClose = strtotime($currentDay . ' ' . $labDayHours['close']);
                    $slotStart = strtotime($currentDay . ' ' . $schedule['start_time']);
                    $slotEnd = strtotime($currentDay . ' ' . $schedule['end_time']);
            
                    $totalMinutes = max(1, ($labClose - $labOpen) / 60); // durée d'ouverture du labo
                    $startOffset = max(0, min(($slotStart - $labOpen) / 60, $totalMinutes));
                    $slotLength = max(0, min(($slotEnd - $slotStart) / 60, $totalMinutes - $startOffset));
                    $startPercent = 100 * $startOffset / $totalMinutes;
                    $widthPercent = 100 * $slotLength / $totalMinutes;
                    $hasBar = true;
                }
            
                // Affichage du slot (rectangle)
                echo "<div class='slot {$specialClass} {$separatorClass}' "
                . ($isAdmin
                    ? "onclick='openModal(\"{$user['id']}\", \"{$day}\", \"{$start}\", \"{$end}\", {$userRolesJson}, {$breakDurationJS})' style=\"--slotColor: {$slotColor}; cursor:pointer;\""
                    : "style=\"--slotColor: {$slotColor}; cursor:not-allowed; opacity:0.85;\" title=\"Seuls les admins peuvent modifier\"")
                . ">";
            
                // Affichage de la time-bar adaptative
                if ($hasBar) {
                    echo "<div class='time-bar' style='left:{$startPercent}%;width:{$widthPercent}%;background:{$slotColor};'></div>";
                }
            
                // Mapping pour labels jolis
                $labLabels = [
                    'plateau technique' => 'PLATEAU TECHNIQUE',
                    '351_vaugirard' => '351, VAUGIRARD',
                    'mozart' => 'MOZART',
                    'grignon' => 'GRIGNON'
                ];
                
                if (isset($schedule['laboratory']) && $schedule['laboratory']) {
                    $labKey = strtolower($schedule['laboratory']);
                    $laboAffiche = $labLabels[$labKey] ?? strtoupper($labKey);
                } else {
                    $labKey = strtolower($selectedLab);
                    $laboAffiche = $labLabels[$labKey] ?? strtoupper($labKey);
                }
                
                $hoursWorkedComma = number_format($hoursWorked, 2, ',', '');
                $timePosClass = ($startHour >= 12) ? 'time-top-right' : 'time-top-left';
                // Nom du labo en badge graphique
                $laboClass = '';
                switch (strtolower($laboAffiche)) {
                    case '351, vaugirard':
                    case '351_vaugirard':
                        $laboClass = 'vaugirard';
                        break;
                    case 'mozart':
                        $laboClass = 'mozart';
                        break;
                    case 'grignon':
                        $laboClass = 'grignon';
                        break;
                    case 'plateau technique':
                        $laboClass = 'plateau technique';
                        break;
                    default:
                        $laboClass = strtolower(preg_replace('/[^a-z0-9]+/', '-', $laboAffiche));
                }                echo "<div class='labo-badge $laboClass'>{$laboAffiche}</div>";
                echo "<div class='time {$timePosClass}'>{$start} - {$end}</div>";
                echo "<div class='hours-worked'>{$hoursWorkedComma} h</div>";
                echo "<div class='employer' style='font-style:normal;'>{$user['name']}</div>";
                echo "</div>"; // fin slot
            }
            } else {
                $userRoles = array_map('trim', explode(',', $user['role']));
                $userRolesJson = htmlspecialchars(json_encode($userRoles), ENT_QUOTES, 'UTF-8');
                echo "<div class='slot {$specialClass} {$separatorClass}' "
            . ($isAdmin ? "onclick=\"openModal('{$user['id']}', '{$day}', '', '', {$userRolesJson})\" style=\"cursor:pointer;\"" 
                    : "style=\"cursor:not-allowed;opacity:0.85;\" title=\"Seuls les admins peuvent modifier\"")
            . "></div>";
            }
            
        }
        echo "</div>";
    }
                

                $conn->close();
                ?>
            <div  class="wsh">

        <form action="save_comment.php" method="POST">
        <input type="hidden" name="week_start" value="<?= $weekStart ?>">
        <input type="hidden" name="laboratory" value="<?= $selectedLab ?>">
        <input type="hidden" name="comment_content" id="comment_content">

        <div class="comment-row-full">
        <label for="commentaire"><strong>NB :</strong></label>

        <!-- PALETTE DE COULEURS -->
        <div class="color-palette">
            <button type="button" class="color-btn" style="background-color: black;" onclick="setCommentColor('black', this)"></button>
            <button type="button" class="color-btn" style="background-color: red;" onclick="setCommentColor('red', this)"></button>
            <button type="button" class="color-btn" style="background-color: blue;" onclick="setCommentColor('blue', this)"></button>
            <button type="button" class="color-btn" style="background-color: green;" onclick="setCommentColor('green', this)"></button>
            <button type="button" class="color-btn" style="background-color: orange;" onclick="setCommentColor('orange', this)"></button>
            <button type="button" class="color-btn" style="background-color: purple;" onclick="setCommentColor('purple', this)"></button>
        </div>

        <!-- ÉDITEUR DE COMMENTAIRE -->
        <div 
    id="commentaire" 
    class="comment-editor"
    contenteditable="<?= $isAdmin ? 'true' : 'false' ?>"
    style="<?= $isAdmin ? '' : 'background: #eee; color: #888; cursor: not-allowed;' ?>"
    >
    <?= $existingComment ?>
    </div>
    <button type="submit" style="margin-top: 10px;" <?= $isAdmin ? '' : 'disabled' ?>>Enregistrer</button>
    <?php if (!$isAdmin): ?>
    <p style="color: #c00; margin-top: 5px;">Seuls les admins peuvent modifier ce commentaire.</p>
    <?php endif; ?>

        </div>
    </form>
    </div>


            </div>
            </div>

        



            <!-- Modal -->
            <div class="modal-overlay" id="modal-overlay"></div>
            <div class="modal" id="modal">
            <h3>Ajouter/Modifier/Supprimer Horaire</h3>
            <form action="add_schedule.php" method="POST">
        <input type="hidden" name="user_id" id="modal-user-id" />
        <input type="hidden" name="day_of_week" id="modal-day" />
        <input type="hidden" name="week_start" id="modal-week-start" value="<?= $weekStart ?>" />
        <input type="hidden" name="laboratory" value="<?= $selectedLab ?>" />
        <input type="hidden" name="confirmed_multi_weeks" id="confirmed_multi_weeks" value="">

        <label for="modal-role">Rôle :</label>
    <select id="modal-role" name="role" required>
        <!-- options ajoutées dynamiquement -->
    </select>




        <label for="modal-start-time">Heure de début :</label>
        <input type="time" name="start_time" id="modal-start-time" required />
        <label for="modal-end-time">Heure de fin :</label>
        <input type="time" name="end_time" id="modal-end-time" required />
        
        <label for="modal-break-duration">Pause (en minutes) :</label>
    <input type="number" name="break_duration" id="modal-break-duration" min="0" max="480" step="5" value="0" />

        <button type="submit" name="action" value="save">Enregistrer</button>
        <button type="button" onclick="closeModal()">Annuler</button>
        <button type="submit" name="action" value="delete" style="background-color: red; color: white;">Supprimer</button>
    </form>

            </div>
        </main>
        <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    <script src="script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="export.js"></script>

    <div id="alert-box" class="alert hidden">
        <span id="alert-message"></span>
        <button onclick="closeAlert()">X</button>
    </div>

    <style>
        .comment-row-full {
        grid-column: 1 / -1;

        margin-top: 30px;
        padding: 10px;
    }


    .wsh {
        grid-column: 1 / -1;


        border-top: 2px solid #ccc;
    }

    .labo-name {
    position: absolute;
    bottom: 1px;
    left: 1px;
    
    font-size: 12px;
    font-weight: 700;
    border-radius: 4px;
    z-index: 10;
    pointer-events: none; /* Ne bloque pas les clics */
    letter-spacing: 0.5px;
    }


    .comment-editor {
        min-height: 150px;
        border: 1px solid #aaa;
        padding: 10px;
        background-color: #fff;
        color: #000;
        outline: none;
        font-size: 16px;
        grid-column: 1 / -1;

    }

    .time-bar {
        position: absolute;
        top: 0px;       /* pour que la barre ne touche pas le haut */
        bottom: 0px;    /* pour qu'elle ne touche pas le bas */
        z-index: 1;
        /* Pas besoin de background ici, il sera donné dynamiquement en ligne */
        opacity: 0.88;
    }
    .slot > *:not(.time-bar) {
        z-index: 2;
    }

    <style>



.slot.month-separator::after {
    content: '';
    position: absolute;
    top: 0px;         /* Dépasse au-dessus */
    bottom: -5px;      /* Dépasse en-dessous */
    left: -4px;
    width: 4px;
    background: #111;
    z-index: 20;
    pointer-events: none; /* Ne bloque pas les clics */
}




            

    .slot {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Centre verticalement */
    align-items: center;     /* Centre horizontalement */
    height: 60px;     /* Hauteur fixe */
    }


    .time-top-left {
    position: absolute;
    top: 1px;
    left: 1px;
    font-size: 12px;
    font-weight: bold;
    }

    .time-top-right {
    position: absolute;
    top: 1px;
    right: 1px;
    font-size: 12px;
    font-weight: bold;
    }



    .cell.total-hours-header,
    .cell.total-hours {
        min-width: 80px;
        max-width: 90px;
        width: 85px;
        font-size: 14px;
        font-weight: 400;
        text-align: center;
        box-sizing: border-box;   /* IMPORTANT */
        padding-left: 0;
        padding-right: 0;
        margin-left: 0;
        margin-right: 0;
    }
    .cell.total-hours {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #342E37;
        height: 100%;
        font-style : italic;

    }

    .cell.total-hours-header{
    font-weight: bold;

    }

        .alert {
            position: fixed;
            top: 50px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 80%;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .hidden {
            display: none;
        }
        .alert button {
            background: none;
            border: none;
            color: #721c24;
            font-weight: bold;
            cursor: pointer;
            margin-left: 10px;
        }
        .slot.jour-special {
        background-color: #fceaea !important;
    }

    .day-special {
        background-color: #fceaea;
        font-weight: bold;
        color: #b10000;
    }

    .comment-row {
        display: flex;
        margin-top: 15px;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }
    .comment-row .cell {
        padding: 5px;
    }
    .comment-row-full {
        grid-column: 1 / -1;
        margin-top: 20px;
    }

    .color-palette {
        margin-bottom: 10px;
    }

    .color-btn {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        border: 2px solid #ccc;
        margin-right: 5px;
        cursor: pointer;
    }
    .color-btn:hover {
        border: 2px solid #000;
    }

    .comment-editor {
        min-height: 120px;
        border: 1px solid #aaa;
        padding: 10px;
        background-color: white;
        outline: none;
    }

    .color-btn.selected {
        border: 2px solid black;
        box-shadow: 0 0 3px rgba(0, 0, 0, 0.6);
    }


    .search-user {
        padding: 8px 12px;
        border-radius: 8px;
        border: 2px solid #007BFF;
        font-size: 16px;
        margin-left: 10px;
        width: 210px;
        transition: border-color 0.3s;
    }
    .search-user:focus {
        border-color: #0056b3;
        outline: none;
    }

    .slot-before-midi {
        /* Partie DROITE colorée */
        background: linear-gradient(
            -90deg,
            transparent 49.5%,
            var(--slotColor, #eee) 50%
        ) !important;
    }

    .slot-apres-midi {
        /* Partie GAUCHE colorée */
        background: linear-gradient(
        -90deg,
            var(--slotColor, #eee) 50%,
            transparent 50.5%
        ) !important;
    }
    .labo-badge {
    position: absolute;
    left: 1px;
    bottom: 1px;
    display: inline-block;
    padding: 2px 10px;
    border-radius: 11px;
    font-size: 10px;
    font-weight: bold;
    color: white;
    box-shadow: 0 1px 3px #0001;
    letter-spacing: 0.5px;
    margin-bottom: 0;
    border: 1.5px solid #fff;
    z-index: 12;
    pointer-events: none;
}

.labo-badge.plateau.technique {
    font-size: 7px !important;
    padding: 1px 4px !important;
    min-width: unset !important;
    max-width: 78px !important;  /* adapte si besoin */
    line-height: 1.2 !important;
    white-space: normal !important;
    background : #808080;
}

.labo-badge.vaugirard    { 
      font-size: 9px !important;
    padding: 1px 4px !important;
    min-width: unset !important;
    line-height: 1.2 !important;
    white-space: normal !important;
    background : #00cbcc; }  /* Violet foncé */

.labo-badge.mozart    { background: #8e24aa; }  /* Violet foncé */
.labo-badge.grignon   { background: #43a047; }  /* Vert */


.search-user-container {
    display: inline-block;
    position: relative;
}
.search-user {
    padding-right: 32px; /* Pour laisser la place au bouton x */
}
.clear-search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    font-size: 22px;
    color: #999;
    cursor: pointer;
    padding: 0 2px;
    z-index: 5;
    display: none;
}
.clear-search-btn:focus {
    outline: none;
}

    </style>

    <script>
        function showAlert(message) {
            let alertBox = document.getElementById("alert-box");
            document.getElementById("alert-message").innerText = message;
            alertBox.classList.remove("hidden");

            setTimeout(() => {
                alertBox.classList.add("hidden");
            }, 5000); // L'alerte disparaît après 5 secondes
        }

        function closeAlert() {
            document.getElementById("alert-box").classList.add("hidden");
        }
    </script>


    <div id="confirm-box" class="confirm hidden">
        <span id="confirm-message">Voulez-vous apporter cette modification pour toutes les prochaines semaines ?</span>
        <div class="confirm-buttons">
            <button id="confirm-yes">Oui</button>
            <button id="confirm-no">Non</button>
        </div>
    </div>

    <style>
        .confirm {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            color: #333;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            min-width: 300px;
            max-width: 80%;
            z-index: 2000;
        }
        .hidden {
            display: none;
        }
        .confirm-buttons {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            
        }
        .confirm button {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }
        #confirm-yes {
            background-color: #d9534f;
            color: white;
        }
        #confirm-no {
            background-color: #ccc;
            color: black;
        }

        
        .sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 36px ;    padding-bottom: 10px;
    background : #eee;
    /* Optionnel : border-bottom pour bien séparer quand c'est sticky */
}






    </style>



    <script>

        
        function showConfirm(yesUrl, noUrl, message) {
        let confirmBox = document.getElementById("confirm-box");
        let confirmMessage = document.getElementById("confirm-message");
        let yesButton = document.getElementById("confirm-yes");
        let noButton = document.getElementById("confirm-no");

        confirmMessage.innerText = message;
        confirmBox.classList.remove("hidden");

        yesButton.onclick = function() {
            window.location.href = yesUrl;
        };

        noButton.onclick = function() {
            window.location.href = noUrl;
        };
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        console.log("Action détectée :", urlParams.get("confirm"));

        if (urlParams.has("confirm")) {
            let action = urlParams.get("confirm");
            let user_id = urlParams.get("user_id");
            let day_of_week = urlParams.get("day_of_week");
            let start_time = urlParams.get("start_time");
            let end_time = urlParams.get("end_time");
            let week_start = urlParams.get("week_start");
            let laboratory = urlParams.get("laboratory");
            let role = urlParams.get("role"); // 🛠 Ajout récup role aussi
            let break_duration = urlParams.get("break_duration");

            let alertAlreadyShown = false;

            if (action === "delete_schedule" && !alertAlreadyShown) {
                alertAlreadyShown = true;
                let yesUrl = `delete_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&week_start=${week_start}&laboratory=${laboratory}&apply_all=1&role=${encodeURIComponent(role)}&break_duration=${break_duration}`;
                let noUrl = `delete_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&week_start=${week_start}&laboratory=${laboratory}&apply_all=0&role=${encodeURIComponent(role)}&break_duration=${break_duration}`;
                showConfirm(yesUrl, noUrl, "Voulez-vous appliquer cette modification à toutes les prochaines semaines ?");
            }

            if (action === "update_schedule" && !alertAlreadyShown) {
                alertAlreadyShown = true;
                let yesUrl = `update_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&apply_all=1&role=${encodeURIComponent(role)}&break_duration=${break_duration}`;
                let noUrl = `update_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&apply_all=0&role=${encodeURIComponent(role)}&break_duration=${break_duration}`;
                showConfirm(yesUrl, noUrl, "Voulez-vous appliquer cette modification à toutes les prochaines semaines ?");
            }

            // === Nouveau bloc ===
            if (action === "over_hours" && !urlParams.has("confirmed")) {
        // Construis l'URL en une seule ligne (pas de retours à la ligne !)
        let yesUrl = `add_schedule.php?` +
            `user_id=${encodeURIComponent(user_id)}` +
            `&day_of_week=${encodeURIComponent(day_of_week)}` +
            `&start_time=${encodeURIComponent(start_time)}` +
            `&end_time=${encodeURIComponent(end_time)}` +
            `&week_start=${encodeURIComponent(week_start)}` +
            `&laboratory=${encodeURIComponent(laboratory)}` +
            `&role=${encodeURIComponent(role)}` +
            `&break_duration=${encodeURIComponent(break_duration)}` +
            `&confirmed_conflict=1` +   // on garde la confirmation de conflit
            `&confirmed=1`;            // et on ajoute la confirmation 35 h
        let noUrl = `dashboard.php?week_start=${encodeURIComponent(week_start)}&laboratory=${encodeURIComponent(laboratory)}&cancel_over_hours=1`;
        showConfirm(yesUrl, noUrl,
            "Attention : Cet Collaborateur dépassera les 35 heures hebdomadaires avec ce créneau. Voulez-vous continuer ?");
    }



            if (action === "conflict_lab" && !alertAlreadyShown) {
                alertAlreadyShown = true;
                let conflictLab = urlParams.get("conflict_lab");
                let conflictStart = urlParams.get("conflict_start");
                let conflictEnd = urlParams.get("conflict_end");

                let yesUrl = `add_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&role=${encodeURIComponent(role)}&break_duration=${break_duration}&confirmed_conflict=1`;
                let noUrl = `dashboard.php?week_start=${week_start}&laboratory=${laboratory}`;
                showConfirm(yesUrl, noUrl, `⚠️ Conflit détecté ! Cet Collaborateur est déjà prévu dans le laboratoire "${conflictLab}" de ${conflictStart} à ${conflictEnd} pour ce jour. Voulez-vous continuer ?`);
            }

            if (action === "multi_weeks" && !urlParams.has("confirmed_update")) {
                let yesUrl = `add_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&role=${encodeURIComponent(role)}&break_duration=${break_duration}&confirmed_multi_weeks=1`;
                let noUrl = `add_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&role=${encodeURIComponent(role)}&break_duration=${break_duration}&confirmed_single_week=1`;
                console.log("showConfirm is called with:", yesUrl, noUrl);
                showConfirm(yesUrl, noUrl, "Voulez-vous appliquer cet horaire à toutes les prochaines semaines ?");
            }

            if (action === "holiday_schedule" && !alertAlreadyShown) {
                let holiday_date = urlParams.get("holiday_date");
                let yesUrl = `add_schedule.php?user_id=${user_id}&day_of_week=${day_of_week}&start_time=${start_time}&end_time=${end_time}&week_start=${week_start}&laboratory=${laboratory}&role=${encodeURIComponent(role)}&break_duration=${break_duration}&confirmed_holiday=1`;
                let noUrl = `dashboard.php?week_start=${week_start}&laboratory=${laboratory}`;
                showConfirm(yesUrl, noUrl, `⚠️ Attention : Vous ajoutez un horaire un jour férié (${holiday_date}). Voulez-vous continuer ?`);
            }
        }
    });




    function filterRows() {
        const input = document.getElementById("search-user").value.trim().toLowerCase();
        const rows = document.querySelectorAll('.order > .row:not(.header)');

        rows.forEach(row => {
            // Le nom est toujours la première cell (".cell.name")
            const nameCell = row.querySelector('.cell.name');
            if (!nameCell) return; // Sécurité

            const nom = nameCell.textContent.trim().toLowerCase();

            if (input === "" || nom.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    </script>


    <script>
        let currentColor = "black";

        function setCommentColor(color, btn) {
        currentColor = color;
        // Reset all
        document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        }

        document.addEventListener("DOMContentLoaded", function () {
        const editor = document.getElementById("commentaire");

        editor.addEventListener("keydown", function () {
            document.execCommand("styleWithCSS", false, true);
            document.execCommand("foreColor", false, currentColor);
        });

        editor.addEventListener("mouseup", function () {
            document.execCommand("styleWithCSS", false, true);
            document.execCommand("foreColor", false, currentColor);
        });

        // Capture du contenu au submit
        const form = editor.closest("form");
        form.addEventListener("submit", function () {
            document.getElementById("comment_content").value = editor.innerHTML;
        });
        });



        document.addEventListener('DOMContentLoaded', function () {
        const labSelector = document.getElementById('lab-selector');
        const allLabsToggle = document.getElementById('all-labs-toggle');

        function updateLabSelectState() {
            if (allLabsToggle.checked) {
                labSelector.disabled = true;
                labSelector.classList.add('select-disabled');
            } else {
                labSelector.disabled = false;
                labSelector.classList.remove('select-disabled');
            }
        }

        // Initialisation au chargement
        updateLabSelectState();

        // Sur changement
        allLabsToggle.addEventListener('change', updateLabSelectState);
    });

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


    function toggleClearBtn() {
    var input = document.getElementById("search-user");
    var btn = document.getElementById("clear-search-btn");
    if (input.value.trim() !== "") {
        btn.style.display = "block";
    } else {
        btn.style.display = "none";
    }
}

function clearSearch() {
    var input = document.getElementById("search-user");
    input.value = "";
    toggleClearBtn();
    filterRows();
    input.focus();

    // Supprimer 'search' des paramètres d'URL et recharger la page proprement
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    window.location.href = url.toString();
}


// Affiche ou non le bouton au chargement si pré-rempli
document.addEventListener("DOMContentLoaded", function () {
    toggleClearBtn();
});

    </script>

    <style>
        #lab-selector:disabled,
    .select-disabled {
        background: #f4f4f4 !important;
        color: #888 !important;
        border-color: #bbb !important;
        cursor: not-allowed !important;
        pointer-events: none;
        opacity: 1;
    }

    </style>
    </body>
    </html>
