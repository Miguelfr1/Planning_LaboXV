<?php
header('Content-Type: application/json');
require_once "db.php";

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = 0;
if ($user_id) {
    $userQuery = "SELECT is_admin FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($userQuery);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();
    $userData = $userResult->fetch_assoc();
    
    $is_admin = $userData['is_admin'] ?? 0;
    $stmtUser->close();
}

// Jours fériés
$jours_feries = [
    '2025-01-01','2025-04-21','2025-05-01',
    '2025-05-08','2025-05-29','2025-07-14',
    '2025-08-15','2025-11-01','2025-11-11','2025-12-25'
];

if (!isset($_GET['start']) || !isset($_GET['end'])) {
    echo json_encode(["error" => "Paramètres de date manquants."]);
    exit;
}

$start      = $_GET['start'];
$end        = $_GET['end'];
$start_time = $_GET['start_time'] ?? '00:00:00';
$end_time   = $_GET['end_time']   ?? '23:59:59';

$jours_fr = [
    1 => "Lundi",   2 => "Mardi",    3 => "Mercredi",
    4 => "Jeudi",   5 => "Vendredi", 6 => "Samedi",
    7 => "Dimanche"
];

$start_date = new DateTime($start);
$end_date   = new DateTime($end);
$jours_selectionnes = [];
$interval = new DateInterval('P1D');
$period   = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

foreach ($period as $date) {
    $num_jour = $date->format('N');
    if (isset($jours_fr[$num_jour])) {
        $jours_selectionnes[] = $jours_fr[$num_jour];
    }
}
$jours_sql = "'" . implode("','", $jours_selectionnes) . "'";

$sql = "
WITH biweekly_hours AS (
  SELECT
    s.user_id,
    u.name AS nom,
    u.role,
    FLOOR(WEEK(s.week_start)/2) AS biweek,
    ROUND(SUM(
      CASE WHEN s.day_of_week = 'Samedi' THEN
        (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
         - COALESCE(s.break_duration,0)
        )/60.0
      ELSE 0 END
    ),2) AS samedi_hours,
    ROUND(SUM(
      CASE WHEN s.day_of_week = 'Dimanche' THEN
        (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
         - COALESCE(s.break_duration,0)
        )/60.0
      ELSE 0 END
    ),2) AS dimanche_hours,
    ROUND(SUM(
      CASE WHEN s.day_of_week NOT IN ('Dimanche') THEN
        (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
         - COALESCE(s.break_duration,0)
        )/60.0
      ELSE 0 END
    ),2) AS heures_normales
  FROM schedules s
  JOIN users u ON s.user_id = u.id
  WHERE s.week_start <= ?
    AND DATE_ADD(
          s.week_start,
          INTERVAL (FIELD(
            s.day_of_week,
            'Lundi','Mardi','Mercredi','Jeudi',
            'Vendredi','Samedi','Dimanche'
          ) - 1) DAY
        ) BETWEEN ? AND ?
    AND s.day_of_week IN ($jours_sql)
    AND s.start_time >= ?
    AND s.end_time   <= ?
  GROUP BY s.user_id, FLOOR(WEEK(s.week_start)/2), u.role
),
extra_hours AS (
  SELECT
    user_id,
    SUM(
      CASE
        WHEN role = 'Apprenti'
          THEN samedi_hours
               + GREATEST(heures_normales - 70, 0)
        WHEN role != 'Apprenti' AND heures_normales > 70
          THEN heures_normales - 70
        ELSE 0
      END
    ) AS heures_supp_total,
    SUM(
      CASE WHEN heures_normales > 70
        THEN LEAST(8, heures_normales - 70)
        ELSE 0
      END
    ) AS heures_supp_25,
    SUM(
      CASE WHEN heures_normales > 78
        THEN heures_normales - 78
        ELSE 0
      END
    ) AS heures_supp_50
  FROM biweekly_hours
  GROUP BY user_id
)
SELECT
  u.name AS nom,
  ROUND(
    SUM(
      CASE WHEN s.day_of_week NOT IN ('Dimanche') THEN
        (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
         - COALESCE(s.break_duration,0)
        )/60.0
      ELSE 0 END
    )
    - COALESCE(e.heures_supp_total,0)
  ,2) AS heures,
  ROUND(
    SUM(
      CASE WHEN s.day_of_week = 'Dimanche' THEN
        (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
         - COALESCE(s.break_duration,0)
        )/60.0
      ELSE 0 END
    )
  ,2) AS heures_dimanche,
  ROUND(
    SUM(
      CASE WHEN DATE_ADD(
                  s.week_start,
                  INTERVAL (FIELD(
                    s.day_of_week,
                    'Lundi','Mardi','Mercredi','Jeudi',
                    'Vendredi','Samedi','Dimanche'
                  ) - 1) DAY
                ) IN ('" . implode("','", $jours_feries) . "')
        THEN (TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)
              - COALESCE(s.break_duration,0)
             )/60.0
        ELSE 0 END
    )
  ,2) AS heures_feries,
  COALESCE(e.heures_supp_25,0) AS heures_supp_25,
  COALESCE(e.heures_supp_50,0) AS heures_supp_50,
  SEC_TO_TIME(
    SUM(
      IF(
        b.entree_time IS NOT NULL
        AND b.sortie_time IS NOT NULL,
        TIME_TO_SEC(TIMEDIFF(b.sortie_time, b.entree_time))
        - TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)),
        0
      )
    )
  ) AS difference_total
FROM schedules s
JOIN users u ON s.user_id = u.id
LEFT JOIN extra_hours e ON s.user_id = e.user_id
LEFT JOIN badgeages b
  ON s.user_id = b.user_id
 AND DATE_ADD(
       s.week_start,
       INTERVAL (FIELD(
         s.day_of_week,
         'Lundi','Mardi','Mercredi','Jeudi',
         'Vendredi','Samedi','Dimanche'
       ) - 1) DAY
     ) = b.date
WHERE s.week_start <= ?
  AND DATE_ADD(
        s.week_start,
        INTERVAL (FIELD(
          s.day_of_week,
          'Lundi','Mardi','Mercredi','Jeudi',
          'Vendredi','Samedi','Dimanche'
        ) - 1) DAY
      ) BETWEEN ? AND ?
  AND s.day_of_week IN ($jours_sql)
  AND s.start_time >= ?
  AND s.end_time   <= ?
GROUP BY u.name, u.id, e.heures_supp_25, e.heures_supp_50
";


if (!$is_admin) {
  // Pour les non-admin : ajoute la condition user_id
  $sql = str_replace("FROM schedules s", "FROM schedules s", $sql);
  $sql .= " HAVING u.id = ?";
}


if ($is_admin) {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(
      "ssssssssss",
      $end, $start, $end,
      $start_time, $end_time,
      $end, $start, $end,
      $start_time, $end_time
  );
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(
      "ssssssssssi",
      $end, $start, $end,
      $start_time, $end_time,
      $end, $start, $end,
      $start_time, $end_time,
      $user_id
  );
}

$stmt->execute();
$result = $stmt->get_result();

$hoursData = [];
while ($row = $result->fetch_assoc()) {
    $row['difference_total'] = $row['difference_total'] ?? "Non terminé";
    $hoursData[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($hoursData);
