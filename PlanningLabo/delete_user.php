<?php
require_once 'db.php';

if (!isset($_GET['id'])) {
    die('ID utilisateur non spécifié.');
}

$id = intval($_GET['id']);

// Supprimer l'utilisateur
$query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo "<script> window.location.href = 'employers.php';</script>";
} else {
    echo "<script> window.location.href = 'index.php';</script>";
}
$stmt->close();
?>
