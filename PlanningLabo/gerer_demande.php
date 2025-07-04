<?php
require_once "db.php";
session_start();

if (!isset($_SESSION['user_id'])) die("Non autorisé.");
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT is_admin FROM users WHERE id='$user_id'");
$isAdmin = $res->fetch_assoc()['is_admin'] ?? 0;

if (!$isAdmin) die("Accès réservé à l’admin.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];
    if ($action == 'accepter') {
        // Récupère la demande
        $demande = $conn->query("SELECT * FROM conges_pending WHERE id='$id'")->fetch_assoc();
        if (!$demande) die("Demande introuvable.");

        // Calculer days_off
        $start_date = $demande['start_date'];
        $end_date = $demande['end_date'];
        $absence_type = $demande['absence_type'];
        $user_demande = $demande['user_id'];

        $jours_feries = [
            "2025-01-01", "2025-04-21", "2025-05-01", "2025-05-08", "2025-05-29",
            "2025-06-09", "2025-07-14", "2025-08-15", "2025-11-01", "2025-11-11", "2025-12-25"
        ];
        $date = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days_off = 0;
        while ($date <= $end) {
            $dow = $date->format("N");
            $cur = $date->format("Y-m-d");
            if ($dow != 7 && !in_array($cur, $jours_feries)) $days_off++;
            $date->modify("+1 day");
        }

        // Insérer dans conges
        $stmt = $conn->prepare("INSERT INTO conges (user_id, start_date, end_date, days_off, absence_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $user_demande, $start_date, $end_date, $days_off, $absence_type);
        $stmt->execute();
        $stmt->close();

        // Supprimer horaires (optionnel, même logique que dans create_conges)
        // ...

        // Marquer la demande comme acceptée
        $conn->query("UPDATE conges_pending SET etat='accepte' WHERE id='$id'");

        header("Location: conges.php?demande=acceptee");
        exit;

    } elseif ($action == 'refuser') {
        $motif = $_POST['motif_refus'] ?? '';
        $conn->query("UPDATE conges_pending SET etat='refuse', motif_refus='" . $conn->real_escape_string($motif) . "' WHERE id='$id'");
        header("Location: conges.php?demande=refusee");
        exit;
    }
}
?>
