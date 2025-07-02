<?php
include 'db.php'; // Connexion à la base de données

if (isset($_GET['id']) && isset($_GET['token'])) {
    $id = $_GET['id'];
    $token = $_GET['token'];

    // Vérifier que le jeton est valide
    $expectedToken = md5($id . 'cle_secrete'); // Utilisez la même clé secrète que dans l'e-mail
    if ($token === $expectedToken) {
        // Annuler le rendez-vous en mettant à jour son statut
        $stmt = $conn->prepare("UPDATE rendez_vous SET statut = 'annulé' WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<p>Votre rendez-vous a été annulé avec succès.</p>";
        } else {
            echo "<p>Impossible d'annuler le rendez-vous. Veuillez réessayer.</p>";
        }
        $stmt->close();
    } else {
        echo "<p>Jeton invalide. Annulation impossible.</p>";
    }
} else {
    echo "<p>Informations manquantes. Annulation impossible.</p>";
}
?>
