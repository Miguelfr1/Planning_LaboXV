<?php
require_once 'db.php';

if (!isset($_GET['id'])) {
    die('ID du congé non spécifié.');
}

$id = intval($_GET['id']);

// Récupérer les informations du congé avec le nom de l'employé
$query = "SELECT c.user_id, c.start_date, c.end_date, u.name AS nom FROM conges c JOIN users u ON c.user_id = u.id WHERE c.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$conge = $result->fetch_assoc();

if (!$conge) {
    die('Congé non trouvé.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);

    // Liste des jours fériés en France pour 2025
    $jours_feries = [
        "2025-01-01", "2025-04-21", "2025-05-01", "2025-05-08", "2025-05-29",
        "2025-06-09", "2025-07-14", "2025-08-15", "2025-11-01", "2025-11-11", "2025-12-25"
    ];

    // Recalcul des jours de congés (hors dimanches et jours fériés)
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

    // Mettre à jour les informations du congé avec les jours recalculés
    $updateQuery = "UPDATE conges SET start_date = ?, end_date = ?, days_off = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssii', $start_date, $end_date, $days_off, $id);

    if ($stmt->execute()) {
        // Supprimer les horaires prévus pendant la nouvelle période de congé
        $user_id = $conge['user_id'];
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
            $deleteQuery->bind_param("iss", $user_id, $week_start_str, $day_name);
            $deleteQuery->execute();
            $deleteQuery->close();
    
            $date->modify('+1 day');
        }
    
        echo "<script> window.location.href = 'conges.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour du congé.');</script>";
    }
    
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le congé</title>
    <style>
        :root { --blue: #3498db; --light: #ecf0f1; }
        body { font-family: Arial, sans-serif; background-color: var(--light); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background-color: white; border: 1px solid var(--blue); border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 30px; width: 400px; text-align: center; }
        .form-container h1 { color: var(--blue); margin-bottom: 20px; }
        .form-container label { display: block; text-align: left; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-container input, .form-container button { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .form-container button { background-color: var(--blue); color: white; border: none; cursor: pointer; font-size: 16px; }
        .form-container button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Modifier le congé de <?php echo htmlspecialchars($conge['nom']); ?></h1>
        <form method="POST" action="">
            <label for="start-date">Date de début :</label>
            <input type="date" id="start-date" name="start_date" value="<?php echo htmlspecialchars($conge['start_date']); ?>" required>

            <label for="end-date">Date de fin :</label>
            <input type="date" id="end-date" name="end_date" value="<?php echo htmlspecialchars($conge['end_date']); ?>" required>

            <button type="submit">Mettre à jour</button>
        </form>
    </div>
</body>
</html>
