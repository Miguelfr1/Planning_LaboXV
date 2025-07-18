<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST["employee"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $absence_type = $_POST["absence_type"]; // Récupération du type d'absence

    if (!$employee_id || !$start_date || !$end_date || !$absence_type) {
        die("Données invalides.");
    }

    // Liste des jours fériés en France pour 2025
    $jours_feries = [
        "2025-01-01", "2025-04-21", "2025-05-01", "2025-05-08", "2025-05-29",
        "2025-06-09", "2025-07-14", "2025-08-15", "2025-11-01", "2025-11-11", "2025-12-25"
    ];

    // Calcul des jours de congé (excluant dimanches et jours fériés)
    $date = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days_off = 0;

    while ($date <= $end) {
        $day_of_week = $date->format("N"); // 1 = Lundi, 7 = Dimanche
        $current_date = $date->format("Y-m-d");

        if ($day_of_week != 7 && !in_array($current_date, $jours_feries)) {
            $days_off++;
        }

        $date->modify("+1 day");
    }

    // Insérer dans la table des congés avec le type d'absence
    $stmt = $conn->prepare("INSERT INTO conges (user_id, start_date, end_date, days_off, absence_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $employee_id, $start_date, $end_date, $days_off, $absence_type);

    if ($stmt->execute()) {

        // SUPPRESSION DES CRENEAUX DU PLANNING SUR LA PERIODE DU CONGE
        $jours_semaine = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];
        $date = new DateTime($start_date);
        $endDateObj = new DateTime($end_date);
        while ($date <= $endDateObj) {
            $day_of_week = $date->format("N");
            $current_date = $date->format("Y-m-d");
            if ($day_of_week != 7 && !in_array($current_date, $jours_feries)) {
                $week_start = clone $date;
                $week_start->modify('monday this week');
                $week_start_str = $week_start->format('Y-m-d');
                $day_name = $jours_semaine[$date->format('l')];
                $deleteQuery = $conn->prepare("
                    DELETE FROM schedules
                    WHERE user_id = ?
                      AND week_start = ?
                      AND day_of_week = ?
                ");
                $deleteQuery->bind_param("iss", $employee_id, $week_start_str, $day_name);
                $deleteQuery->execute();
                $deleteQuery->close();
            }
            $date->modify("+1 day");
        }

        header("Location: conges.php?success=1");
        exit;
    } else {
        echo "Erreur lors de l'enregistrement.";
    }

    $stmt->close();
}

$conn->close();
?>
