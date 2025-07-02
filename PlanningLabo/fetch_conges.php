<?php
require_once "db.php";


$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$query = "SELECT c.id, u.name AS nom, c.start_date, c.end_date, c.days_off, c.absence_type 
          FROM conges c 
          JOIN users u ON c.user_id = u.id 
          WHERE (c.start_date BETWEEN '$start_date' AND '$end_date') 
          OR (c.end_date BETWEEN '$start_date' AND '$end_date')
          ORDER BY SUBSTRING_INDEX(u.name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC, u.name ASC
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periode = date("d/m/Y", strtotime($row["start_date"])) . " - " . date("d/m/Y", strtotime($row["end_date"]));

        // Reformater le type d'absence pour affichage propre
        $absence_types = [
            "conge" => "Congé Payé",
            "arret_maladie" => "Arrêt Maladie",
            "conge_maternite" => "Congé Maternité",
            "enfant_malade" => "Enfant Malade"
        ];
        $absence_type = $absence_types[$row["absence_type"]] ?? "Inconnu";

        echo "<tr>
                <td>{$row['nom']}</td>
                <td>{$absence_type}</td>
                <td>{$row['days_off']}</td>
                <td>{$periode}</td>
                <td><a href='edit_conges.php?id={$row['id']}'><i class='bx bx-edit'></i></a></td>
                <td><i class='bx bx-message-square-x bx-rotate-270' onclick='deleteConges({$row['id']})'></i></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6'>Aucun congé trouvé pour cette période</td></tr>";
}

$conn->close();
?>
