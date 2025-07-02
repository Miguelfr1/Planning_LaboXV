<?php
include 'db.php'; // Connexion à la base de données

// Récupérer les paramètres de la requête
$heure = $_GET['heure'];
$date = $_GET['date'];
$laboratoire = $_GET['laboratoire'];

// Requête pour récupérer les informations du réservateur
$query = "SELECT u.prenom, u.nom, u.email, u.telephone 
          FROM utilisateurs u
          JOIN rendez_vous r ON u.id = r.utilisateur_id
          WHERE r.laboratoire = ? AND r.date_rdv = ? AND r.heure_rdv = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $laboratoire, $date, $heure);
$stmt->execute();
$result = $stmt->get_result();

// Si un résultat est trouvé, renvoyer les informations en JSON
if ($result->num_rows > 0) {
    $reservateur = $result->fetch_assoc();
    echo json_encode($reservateur);
} else {
    echo json_encode(['error' => 'Aucune réservation trouvée']);
}

$stmt->close();
$conn->close();
?>
