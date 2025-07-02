<?php
// Connexion à la base de données
require_once "db.php";

// 🔥 DEBUG GLOBAL 🔥
/*
echo "<pre>";
echo "Données reçues via POST :\n";
var_dump($_POST);
echo "Données reçues via GET :\n";
var_dump($_GET);
echo "</pre>";
//exit(); 
*/

$jours_feries = [
    '2025-01-01', '2025-04-21', '2025-05-01', '2025-05-08',
    '2025-05-29', '2025-07-14', '2025-08-15',
    '2025-11-01', '2025-11-11', '2025-12-25'
];

$jours_semaine = [
    "Lundi" => 0, "Mardi" => 1, "Mercredi" => 2,
    "Jeudi" => 3, "Vendredi" => 4, "Samedi" => 5, "Dimanche" => 6
];

// Récupération sécurisée des données
$role = $_POST['role'] ?? $_POST['role_selected'] ?? $_GET['role'] ?? $_GET['role_selected'] ?? null;
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
$day_of_week = $_POST['day_of_week'] ?? $_GET['day_of_week'] ?? null;
$start_time = $_POST['start_time'] ?? $_GET['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? $_GET['end_time'] ?? null;
$laboratory = $_POST['laboratory'] ?? $_GET['laboratory'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if (isset($_POST['break_duration'])) {
    $break_duration = (int) $_POST['break_duration'];
} elseif (isset($_GET['break_duration'])) {
    $break_duration = (int) $_GET['break_duration'];
} else {
    $break_duration = 0;
}

$week_start = null;
if (!empty($_POST['week_start'])) {
    $week_start = date('Y-m-d', strtotime($_POST['week_start']));
} elseif (!empty($_GET['week_start'])) {
    $week_start = date('Y-m-d', strtotime($_GET['week_start']));
}

// Vérifications basiques
if (!$user_id || !$day_of_week || !$week_start || !isset($jours_semaine[$day_of_week])) {
    die("Erreur : données invalides !");
}

$schedule_date = date('Y-m-d', strtotime($week_start . " + " . $jours_semaine[$day_of_week] . " days"));

// Vérification congés
$checkLeaveStmt = $conn->prepare("SELECT start_date, end_date FROM conges WHERE user_id = ? AND ? BETWEEN start_date AND end_date");
$checkLeaveStmt->bind_param("is", $user_id, $schedule_date);
$checkLeaveStmt->execute();
$leaveResult = $checkLeaveStmt->get_result();
$checkLeaveStmt->close();

if ($leaveResult->num_rows > 0) {
    $leave = $leaveResult->fetch_assoc();
    $start_date = date('d/m/Y', strtotime($leave['start_date']));
    $end_date = date('d/m/Y', strtotime($leave['end_date']));
    header("Location: dashboard.php?week_start=$week_start&laboratory=$laboratory&alert=" . urlencode("Impossible d'ajouter cet horaire : en congé du $start_date au $end_date.") . "&role=$role");
    exit();
}

// Supprimer un horaire
if ($action === 'delete') {
    header("Location: dashboard.php?confirm=delete_schedule&user_id=$user_id&day_of_week=$day_of_week&week_start=$week_start&laboratory=$laboratory&role=$role&break_duration=$break_duration");
    exit();
}

// Si c'est un jour férié semaine actuelle et pas confirmé : demander confirmation
if (
    in_array($schedule_date, $jours_feries)
    && !isset($_GET['confirmed_holiday'])
    && !isset($_GET['confirmed'])
    && !isset($_GET['confirmed_conflict'])
    && !isset($_GET['confirmed_multi_weeks'])
    && !isset($_GET['confirmed_single_week'])
) {
    header("Location: dashboard.php?confirm=holiday_schedule&user_id=$user_id&day_of_week=$day_of_week&start_time=$start_time&end_time=$end_time&week_start=$week_start&laboratory=$laboratory&holiday_date=$schedule_date&role=$role&break_duration=$break_duration");
    exit();
}

// Mise à jour horaire
if ($action === 'update_schedule') {
    header("Location: dashboard.php?confirm=update_schedule&user_id=$user_id&day_of_week=$day_of_week&start_time=$start_time&end_time=$end_time&week_start=$week_start&laboratory=$laboratory&role=$role&break_duration=$break_duration");
    exit();
}

// Vérification conflit laboratoire (avec prise en compte du flag confirmed)
$conflictStmt = $conn->prepare(
    "SELECT laboratory, start_time, end_time
       FROM schedules 
      WHERE user_id = ?
        AND day_of_week = ?
        AND week_start = ?
        AND laboratory != ?"
);
$conflictStmt->bind_param("isss", $user_id, $day_of_week, $week_start, $laboratory);
$conflictStmt->execute();
$conflictResult = $conflictStmt->get_result();
$conflictStmt->close();

if ($conflictResult->num_rows > 0
    && !isset($_GET['confirmed_conflict'])
    && !isset($_GET['confirmed'])
    && !isset($_GET['confirmed_multi_weeks'])
    && !isset($_GET['confirmed_single_week'])
){
    $conflict = $conflictResult->fetch_assoc();
    $conflictLab   = $conflict['laboratory'];
    $conflictStart = $conflict['start_time'];
    $conflictEnd   = $conflict['end_time'];
    header(
      "Location: dashboard.php?"
     . "confirm=conflict_lab"
     . "&user_id={$user_id}"
     . "&day_of_week={$day_of_week}"
     . "&start_time={$start_time}"
     . "&end_time={$end_time}"
     . "&week_start={$week_start}"
     . "&laboratory={$laboratory}"
     . "&conflict_lab="   . urlencode($conflictLab)
     . "&conflict_start=" . urlencode($conflictStart)
     . "&conflict_end="   . urlencode($conflictEnd)
     . "&role="           . urlencode($role)
     . "&break_duration=" . urlencode($break_duration)
    );
    exit();
}

// Vérification dépassement 35h (avec prise en compte du flag confirmed_conflict)
$totalHoursStmt = $conn->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60 AS total_hours FROM schedules WHERE user_id = ? AND week_start = ?");
$totalHoursStmt->bind_param("is", $user_id, $week_start);
$totalHoursStmt->execute();
$totalHoursResult = $totalHoursStmt->get_result();
$totalHoursStmt->close();

$totalHours   = $totalHoursResult->fetch_assoc()['total_hours'] ?? 0;
$addedHours   = (strtotime($end_time) - strtotime($start_time)) / 3600;
$totalHours  += $addedHours;

if ($totalHours > 35
    && !isset($_GET['confirmed'])
    && !isset($_GET['confirmed_multi_weeks'])
    && !isset($_GET['confirmed_single_week'])
) {
    if (isset($_GET['cancel_over_hours'])) {
        header("Location: dashboard.php?week_start=" . urlencode($week_start) . "&laboratory=" . urlencode($laboratory) . "&role=" . urlencode($role));
        exit();
    }
    header("Location: dashboard.php?confirm=over_hours&user_id=$user_id&day_of_week=$day_of_week&start_time=$start_time&end_time=$end_time&week_start=" . urlencode($week_start) . "&laboratory=" . urlencode($laboratory) . "&role=" . urlencode($role) . "&break_duration=$break_duration");
    exit();
}

// Vérification existence horaire
$checkExistStmt = $conn->prepare("SELECT id FROM schedules WHERE user_id = ? AND day_of_week = ? AND week_start = ? AND laboratory = ?");
$checkExistStmt->bind_param("isss", $user_id, $day_of_week, $week_start, $laboratory);
$checkExistStmt->execute();
$is_update = ($checkExistStmt->get_result()->num_rows > 0);
$checkExistStmt->close();

if ($is_update && !isset($_GET['confirmed_update'])) {
    header("Location: dashboard.php?confirm=update_schedule&user_id=$user_id&day_of_week=$day_of_week&start_time=$start_time&end_time=$end_time&week_start=$week_start&laboratory=$laboratory&role=$role&break_duration=$break_duration");
    exit();
}

// Confirmation propagation multi-semaines
if (
    !$is_update
    && $action !== 'update_schedule'
    && !isset($_GET['confirmed_multi_weeks'])
    && !isset($_GET['confirmed_single_week'])
) {
    header("Location: dashboard.php?confirm=multi_weeks&user_id=$user_id&day_of_week=$day_of_week&start_time=$start_time&end_time=$end_time&week_start=$week_start&laboratory=$laboratory&role=$role&break_duration=$break_duration");
    exit();
}


// ⛔️ Sauter les jours fériés automatiquement sur les semaines futures
if (isset($_GET['confirmed_multi_weeks']) && $_GET['confirmed_multi_weeks'] == 1) {
    $weeksToAdd = 52; 
    $insertStmt = $conn->prepare("INSERT INTO schedules (user_id, day_of_week, start_time, end_time, week_start, laboratory, role, break_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < $weeksToAdd; $i++) {
        $future_week_start    = date('Y-m-d', strtotime("+$i weeks", strtotime($week_start)));
        $future_schedule_date = date('Y-m-d', strtotime($future_week_start . " + " . $jours_semaine[$day_of_week] . " days"));

        if ($i > 0 && in_array($future_schedule_date, $jours_feries)) {
            continue;
        }

        $checkFutureStmt = $conn->prepare("SELECT id FROM schedules WHERE user_id = ? AND day_of_week = ? AND week_start = ? AND laboratory = ?");
        $checkFutureStmt->bind_param("isss", $user_id, $day_of_week, $future_week_start, $laboratory);
        $checkFutureStmt->execute();
        $resultFuture = $checkFutureStmt->get_result();

        if ($resultFuture->num_rows == 0) {
            $insertStmt->bind_param("issssssi", $user_id, $day_of_week, $start_time, $end_time, $future_week_start, $laboratory, $role, $break_duration);
            $insertStmt->execute();
        }
        $checkFutureStmt->close();
    }
    $insertStmt->close();

    header("Location: dashboard.php?week_start=" . urlencode($week_start) . "&laboratory=" . urlencode($laboratory));
    exit();
}

// Ajout simple si pas multi-semaine
$insertStmt = $conn->prepare("INSERT INTO schedules (user_id, day_of_week, start_time, end_time, week_start, laboratory, role, break_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$insertStmt->bind_param("issssssi", $user_id, $day_of_week, $start_time, $end_time, $week_start, $laboratory, $role, $break_duration);

$insertStmt->execute();
$insertStmt->close();


file_put_contents('debug_post.txt', print_r($_POST, true));

// ==== AJOUT DU DEUXIEME CRENEAU SI REMPLI ====
if (!empty($_POST['start_time_2']) && !empty($_POST['end_time_2'])) {
    $start_time_2 = $_POST['start_time_2'];
    $end_time_2 = $_POST['end_time_2'];

    $insertStmt2 = $conn->prepare("INSERT INTO schedules (user_id, day_of_week, start_time, end_time, week_start, laboratory, role, break_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt2->bind_param("issssssi", $user_id, $day_of_week, $start_time_2, $end_time_2, $week_start, $laboratory, $role, 0);
    $insertStmt2->execute();
    $insertStmt2->close();
}

header("Location: dashboard.php?week_start=" . urlencode($week_start) . "&laboratory=" . urlencode($laboratory));
exit();


?>
