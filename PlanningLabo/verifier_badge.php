<?php
session_start();
require_once "db.php";


$user_id = $_SESSION['user_id'] ?? null;
$date = date('Y-m-d');

$badge_entree = null;
$badge_sortie = null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT entree_time, sortie_time FROM badgeages WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $badge = $result->fetch_assoc();
    $stmt->close();

    if ($badge) {
        $_SESSION['badge_entree'] = $badge['entree_time'];
        $_SESSION['badge_sortie'] = $badge['sortie_time'];
    }
}

// Rediriger automatiquement vers badgeuse.php
header("Location: badgeuse.php");
exit();
?>
