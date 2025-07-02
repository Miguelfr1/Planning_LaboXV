<?php
require_once "db.php";

// Récupération des paramètres
$user_id       = isset($_GET['user_id'])       ? (int) $_GET['user_id']       : null;
$day_of_week   = $_GET['day_of_week']          ?? null;
$start_time    = $_GET['start_time']           ?? null;
$end_time      = $_GET['end_time']             ?? null;
$week_start    = $_GET['week_start']           ?? null;
$laboratory    = $_GET['laboratory']           ?? null;
$apply_all     = isset($_GET['apply_all'])     ? (int) $_GET['apply_all']     : 0;
$role          = $_GET['role']                 ?? null;
$break_duration = isset($_GET['break_duration']) ? (int) $_GET['break_duration'] : 0;

// Choix de la requête selon propagation multi‐semaines ou non
if ($apply_all === 1) {
    $sql = "
      UPDATE schedules
      SET start_time    = ?,
          end_time      = ?,
          role          = ?,
          break_duration= ?
      WHERE user_id     = ?
        AND day_of_week = ?
        AND week_start >= ?
        AND laboratory  = ?
    ";
} else {
    $sql = "
      UPDATE schedules
      SET start_time    = ?,
          end_time      = ?,
          role          = ?,
          break_duration= ?
      WHERE user_id     = ?
        AND day_of_week = ?
        AND week_start  = ?
        AND laboratory  = ?
    ";
}

$stmt = $conn->prepare($sql);
// Types : s=string, i=integer
// paramètres : start_time, end_time, role, break_duration, user_id, day_of_week, week_start, laboratory
$stmt->bind_param(
    "sssissss",
    $start_time,
    $end_time,
    $role,
    $break_duration,
    $user_id,
    $day_of_week,
    $week_start,
    $laboratory
);

$stmt->execute();
$stmt->close();

header("Location: dashboard.php?week_start=" . urlencode($week_start) . "&laboratory=" . urlencode($laboratory));
exit();
