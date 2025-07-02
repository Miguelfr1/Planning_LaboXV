<?php
session_start();
require_once "db.php";

date_default_timezone_set('Europe/Paris'); // Pour l'heure française


header("Content-Type: application/json"); // Réponse JSON pour l'AJAX

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Utilisateur non connecté."]);
    header("Location: badgeuse.php?error=non_connecte");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? null;
$date = date('Y-m-d');
$heure = date('H:i:s');

if (!$action || !in_array($action, ['entrée', 'sortie'])) {
    echo json_encode(["success" => false, "message" => "Action invalide."]);
    header("Location: badgeuse.php?error=action_invalide");
    exit();
}

// Vérifier si l'utilisateur a déjà un enregistrement pour aujourd'hui
$stmt = $conn->prepare("SELECT id, entree_time, sortie_time FROM badgeages WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$badgeage = $result->fetch_assoc();
$stmt->close();

if ($action === "entrée") {
    if ($badgeage && !empty($badgeage['entree_time'])) {
        header("Location: badgeuse.php?error=deja_badge_entree");
        exit();
    }

    if (!$badgeage) {
        $stmt = $conn->prepare("INSERT INTO badgeages (user_id, date, entree_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $date, $heure);
    } else {
        $stmt = $conn->prepare("UPDATE badgeages SET entree_time = ? WHERE id = ?");
        $stmt->bind_param("si", $heure, $badgeage['id']);
    }
} elseif ($action === "sortie") {
    if (!$badgeage || empty($badgeage['entree_time'])) {
        header("Location: badgeuse.php?error=pas_badge_entree");
        exit();
    }

    if (!empty($badgeage['sortie_time'])) {
        header("Location: badgeuse.php?error=deja_badge_sortie");
        exit();
    }

    $stmt = $conn->prepare("UPDATE badgeages SET sortie_time = ? WHERE id = ?");
    $stmt->bind_param("si", $heure, $badgeage['id']);
}

// Exécuter la requête
if ($stmt->execute()) {
    header("Location: badgeuse.php?success=badge_enregistre");
    exit();
} else {
    header("Location: badgeuse.php?error=badge_echec");
    exit();
}

$stmt->close();
$conn->close();
exit();
?>
