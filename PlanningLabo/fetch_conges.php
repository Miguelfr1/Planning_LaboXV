<?php
session_start();
require_once "db.php";

// --- Sécurité, session, filtre labo ---
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $userRes = $conn->query("SELECT is_admin FROM users WHERE id = '$user_id'");
    if ($userRes && $u = $userRes->fetch_assoc()) $isAdmin = $u['is_admin'];
}

$startDateFilter = $_GET['start_date'] ?? date('Y-m-01');
$endDateFilter   = $_GET['end_date']   ?? date('Y-m-t');
$selectedLab = $_GET['laboratory'] ?? 'vaugirard';
$allLabs     = isset($_GET['all_labs']) && $_GET['all_labs'] == '1';

// --- Calcul jours du mois ---
$firstDay = date('Y-m-01', strtotime($startDateFilter));
$daysInMonth = (int)date('t', strtotime($startDateFilter));
$month = date('m', strtotime($startDateFilter));
$year = date('Y', strtotime($startDateFilter));

// --- Fonction jours fériés France ---
function joursFeries($annee) {
    $easter = date('Y-m-d', easter_date($annee));
    $easter_ts = strtotime($easter);
    return [
        "$annee-01-01", // Jour de l'an
        date('Y-m-d', strtotime("+1 day", $easter_ts)),      // Lundi de Pâques
        "$annee-05-01", // Fête du Travail
        "$annee-05-08", // Victoire 1945
        date('Y-m-d', strtotime("+39 days", $easter_ts)),    // Ascension
        date('Y-m-d', strtotime("+50 days", $easter_ts)),    // Pentecôte
        "$annee-07-14", // Fête nationale
        "$annee-08-15", // Assomption
        "$annee-11-01", // Toussaint
        "$annee-11-11", // Armistice
        "$annee-12-25"  // Noël
    ];
}
$feries = joursFeries($year);

// Types d'absences
$absence_types = [
    'conge'           => 'Congé Payé',
    'arret_maladie'   => 'Arrêt Maladie',
    'conge_maternite' => 'Congé Maternité',
    'enfant_malade'   => 'Enfant Malade',
    'Revision'        => 'Jour(s) de révision',
    'Exam'            => 'Examen',
    'Formation'       => 'Formation'
];

// --- Récupération des utilisateurs selon labo ---
$usersSql = "SELECT DISTINCT u.id, u.name, u.role FROM users u";
if (!$allLabs) {
    $usersSql .= " JOIN user_laboratories ul ON u.id = ul.user_id WHERE ul.laboratory = '" . $conn->real_escape_string($selectedLab) . "'";
}
$usersSql .= " ORDER BY u.name ASC"; // le tri final est fait en PHP ci-dessous
$usersRes = $conn->query($usersSql);

// ------------------------
// GROUPEMENT & TRI EN PHP
// ------------------------

$users = [];
if ($usersRes && $usersRes->num_rows > 0) {
    while ($row = $usersRes->fetch_assoc()) {
        $users[] = $row;
    }
}

$groupe_roles = [
    'Biologiste' => 'Biologistes',
    'Qualité'    => 'Administratif - Qualité',
    'Secrétaire' => 'Secrétaires',
    'Immuno'     => 'Préleveurs',
    'Bactério'   => 'Préleveurs',
    'Préleveur'  => 'Préleveurs',
    "Coursier" => "Coursiers",
    "Agent d'entretien" => "Agents d'entretien"
];

function premier_role($rolestr) {
    $tmp = explode(',', $rolestr);
    return trim($tmp[0]);
}
function groupe_affichage($role) {
    global $groupe_roles;
    $role = ucfirst(strtolower($role));
    if (stripos($role, "apprenti") === 0) return "Apprentis";
    if (isset($groupe_roles[$role])) return $groupe_roles[$role];
    return "Autres";
}
$ordre_groupes = [
    "Biologistes",
    "Préleveurs",
    "Secrétaires",
    "Apprentis",
    "Administratif - Qualité", 
    "Coursiers",
    "Agents d'entretien",
    "Autres"
];
// Tri
usort($users, function($a, $b) use ($ordre_groupes) {
    $roleA = premier_role($a['role'] ?? '');
    $roleB = premier_role($b['role'] ?? '');
    $grpA = array_search(groupe_affichage($roleA), $ordre_groupes);
    $grpB = array_search(groupe_affichage($roleB), $ordre_groupes);
    if ($grpA === $grpB) return strcmp($a['name'], $b['name']);
    return $grpA - $grpB;
});

// --------------------------------------
// ----------- AFFICHAGE TABLEAU --------
// --------------------------------------

echo "<table class='table-presence'>";
echo "<thead>";
echo "<tr><th>Collaborateur</th>";
for ($d = 1; $d <= $daysInMonth; $d++) {
    echo "<th>" . sprintf('%02d', $d) . "/" . sprintf('%02d', $month) . "</th>";
}
if ($isAdmin) {
    echo "<th>Total</th><th>Actions</th>";
} else {
    echo "<th>Total</th>";
}
echo "</tr></thead>";

echo "<tbody>";

$current_group = null;
foreach ($users as $user) {
    $uid = (int)$user['id'];
    $name = htmlspecialchars($user['name']);
    $firstRole = premier_role($user['role']);
    $group = groupe_affichage($firstRole);

    if ($group !== $current_group) {
        $current_group = $group;
        echo "<tr><td colspan='" . ($daysInMonth + ($isAdmin ? 2 : 1)) . "' style='background:#f7f7fa;font-weight:bold;font-size:1.00em;color:#333;border-top:2px solid #aaa; text-align:center;'>$group</td></tr>";
    }

    // Récupère TOUS les congés du user sur la période du mois
    $congesSql = "SELECT id, start_date, end_date, absence_type FROM conges
        WHERE user_id = $uid
        AND (start_date <= '$year-$month-{$daysInMonth}')
        AND (end_date >= '$firstDay')";
    $congesRes = $conn->query($congesSql);

    $absences = [];
    if ($congesRes) {
        while ($row = $congesRes->fetch_assoc()) {
            $absences[] = [
                'start'    => $row['start_date'],
                'end'      => $row['end_date'],
                'type'     => $absence_types[$row['absence_type']] ?? $row['absence_type'], // label
                'raw_type' => $row['absence_type'], // ← garde la clé
                'id'       => $row['id']
            ];
            
        }
    }

    // Calcul du total des jours de congé sur la période filtrée
    $total_days_off = 0;
foreach ($absences as $abs) {
    // Calcule la portion du congé dans la période affichée
    $start = max($abs['start'], $firstDay);
    $end = min($abs['end'], date('Y-m-t', strtotime($firstDay)));

    $startDateObj = new DateTime($start);
    $endDateObj = new DateTime($end);

    while ($startDateObj <= $endDateObj) {
        $currentDateStr = $startDateObj->format('Y-m-d');
        $dayOfWeek = $startDateObj->format('N'); // 1 = lundi, ..., 7 = dimanche

        // Compte seulement si pas dimanche (7) et pas jour férié
        if ($dayOfWeek != 7 && !in_array($currentDateStr, $feries)) {
            $total_days_off++;
        }
        $startDateObj->modify('+1 day');
    }
}


    echo "<tr>";
    echo "<td>$name</td>";

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $isAbsent = false;
        $absenceTypeKey = '';
        $absenceTypeLabel = '';
        foreach ($absences as $abs) {
            if ($currentDate >= $abs['start'] && $currentDate <= $abs['end']) {
                $isAbsent = true;
                $absenceTypeKey = $abs['raw_type'] ?? '';
                $absenceTypeLabel = $abs['type'];
                break;
            }
        }
        
        $isSunday = (date('w', strtotime($currentDate)) == 0);
        $isFerie = in_array($currentDate, $feries);
    
        if ($isFerie) {
            $class = 'ferie';
        } elseif ($isSunday) {
            $class = 'sunday';
        } elseif ($isAbsent && $absenceTypeKey) {
            $class = $absenceTypeKey; // ex: "conge", "Revision", etc.
        } else {
            $class = 'present';
        }
        echo "<td class='$class'" . ($isAbsent ? " title='$absenceTypeLabel'" : "") . "></td>";
    }
    
    // Affiche le total des jours de congé
    echo "<td style='font-weight:bold;'>$total_days_off</td>";

    // Affiche les boutons Actions si admin
    $absences_valides = array_filter($absences, function($a) {
        return !empty($a['id']);
    });

    if ($isAdmin) {
        echo "<td>";
        if (count($absences_valides) > 0) {
            $absences_data = htmlspecialchars(json_encode(array_map(function($a) {
                return [
                    'id' => $a['id'],
                    'start' => $a['start'],
                    'end' => $a['end']
                ];
            }, $absences_valides)));

            echo "
                <button class='btn-edit' data-absences='$absences_data' title='Modifier'>
                    <i class='bx bxs-edit'></i>
                </button>
            ";
        }
        echo "</td>";
    }

    echo "</tr>";
}

if (empty($users)) {
    echo "<tr><td colspan='" . ($daysInMonth + ($isAdmin ? 2 : 1)) . "'>Aucun collaborateur trouvé</td></tr>";
}
echo "</tbody></table>";

$conn->close();
?>

<style>
    .btn-edit, .btn-delete {
    background: none;
    border: none;
    outline: none;
    padding: 0 1px;
    margin : 0;
    cursor: pointer;
    font-size: 1.3em; /* ou ajuste selon la taille voulue */
}

.btn-edit i, .btn-delete i {
    pointer-events: none; /* Permet de cliquer sur le bouton même si on clique sur l'icône */
}

</style>
