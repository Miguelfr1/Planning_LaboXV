<?php
session_start(); // ← pour $_SESSION['user_id']
require_once "db.php";

// Récupère le statut admin (on suppose que tu utilises la session !)
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $userRes = $conn->query("SELECT is_admin FROM users WHERE id = '$user_id'");
    if ($userRes && $u = $userRes->fetch_assoc()) $isAdmin = $u['is_admin'];
}

$start_date = $_GET['start_date'];
$end_date   = $_GET['end_date'];
$selectedLab = $_GET['laboratory'] ?? 'vaugirard';
$allLabs     = isset($_GET['all_labs']) && $_GET['all_labs'] == '1';


// Libellés des types d'absence
$absence_types = [
    'conge'           => 'Congé Payé',
    'arret_maladie'   => 'Arrêt Maladie',
    'conge_maternite' => 'Congé Maternité',
    'enfant_malade'   => 'Enfant Malade',
    'Revision'        => 'Jour(s) de révision',
    'Exam'            => 'Examen',
    'Formation'       => 'Formation'
];

// ----- Récupération des utilisateurs selon le labo -----
$usersSql = "SELECT DISTINCT u.id, u.name
            FROM users u";
if (!$allLabs) {
    $usersSql .= " JOIN user_laboratories ul ON u.id = ul.user_id WHERE ul.laboratory = '" . $conn->real_escape_string($selectedLab) . "'";
}
$usersSql .= " ORDER BY SUBSTRING_INDEX(u.name, ' ', -1) COLLATE utf8mb4_unicode_ci ASC, u.name ASC";

$usersRes = $conn->query($usersSql);

if ($usersRes && $usersRes->num_rows > 0) {
    while ($user = $usersRes->fetch_assoc()) {
        $uid = (int)$user['id'];
        $name = htmlspecialchars($user['name']);

        $congesSql = "SELECT id, start_date, end_date, days_off, absence_type
                      FROM conges
                      WHERE user_id = $uid
                        AND ((start_date BETWEEN '$start_date' AND '$end_date')
                             OR (end_date BETWEEN '$start_date' AND '$end_date'))
                      ORDER BY start_date DESC";
        $congesRes = $conn->query($congesSql);

        if ($congesRes && $congesRes->num_rows > 0) {
            while ($row = $congesRes->fetch_assoc()) {
                $periode = date('d/m/Y', strtotime($row['start_date'])) . ' - ' . date('d/m/Y', strtotime($row['end_date']));
                $absence_type = $absence_types[$row['absence_type']] ?? $row['absence_type'];

                echo "<tr>";
                echo "<td>{$name}</td>";
                echo "<td>{$absence_type}</td>";
                echo "<td>{$row['days_off']}</td>";
                echo "<td>{$periode}</td>";
                if ($isAdmin) {
                    echo "<td><a href='edit_conges.php?id={$row['id']}'><i class='bx bx-edit'></i></a></td>";
                    echo "<td><i class='bx bx-message-square-x bx-rotate-270' onclick='deleteConges({$row['id']})'></i></td>";
                }
                echo "</tr>";
            }
        } else {
            $colspan = $isAdmin ? 5 : 3;
            echo "<tr><td>{$name}</td><td colspan='{$colspan}'>Aucun congé sur cette période</td></tr>";
        }
    }
} else {
    // Adapter le colspan au nombre de colonnes selon admin
    $colspan = $isAdmin ? 6 : 4;
    echo "<tr><td colspan='{$colspan}'>Aucun collaborateur trouvé</td></tr>";
}

$conn->close();
?>
