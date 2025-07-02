<?php
require_once "db.php";


$user_id = $_GET['user_id'];
$day_of_week = $_GET['day_of_week'];
$week_start = $_GET['week_start'];
$laboratory = $_GET['laboratory'];
$apply_all = $_GET['apply_all'];

if ($apply_all == 1) {
    $deleteQuery = "DELETE FROM schedules WHERE user_id = ? AND day_of_week = ? AND week_start >= ? AND laboratory = ?";
} else {
    $deleteQuery = "DELETE FROM schedules WHERE user_id = ? AND day_of_week = ? AND week_start = ? AND laboratory = ?";
}

$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("isss", $user_id, $day_of_week, $week_start, $laboratory);
$deleteStmt->execute();
$deleteStmt->close();

echo "<script>window.location.href = 'dashboard.php?week_start=" . $week_start . "&laboratory=" . $laboratory . "';</script>";

$conn->close();
?>
