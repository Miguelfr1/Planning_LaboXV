<?php
// db.php - Connexion à la base de données
$host = 'localhost';
$username = 'root';
$password = 'DL@sante91420';
$database = 'labordv';

// Connexion à la base de données
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
