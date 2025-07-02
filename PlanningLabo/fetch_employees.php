

<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "db.php";


$query = "SELECT id, name FROM users ORDER BY SUBSTRING_INDEX(name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC";
$result = $conn->query($query);

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = ["id" => $row["id"], "name" => $row["name"]];
}

echo json_encode($employees);
$conn->close();
?>
