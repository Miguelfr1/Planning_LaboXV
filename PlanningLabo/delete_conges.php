<?php
header('Content-Type: application/json');
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"];

if (!$id) {
    echo json_encode(["success" => false, "error" => "ID invalide."]);
    exit;
}

$query = "DELETE FROM conges WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Erreur lors de la suppression."]);
}

$stmt->close();
$conn->close();
?>
