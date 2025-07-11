<?php
require '../vendor/autoload.php';
require_once "db.php";

function normalise_nom($nom) {
    $n = mb_strtoupper(trim($nom), 'UTF-8');
    $n = preg_replace('/^DR[\s\.]+/i', '', $n);
    $n = strtr($n, [
        'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','À'=>'A','Â'=>'A','Ä'=>'A',
        'Ô'=>'O','Ö'=>'O','Ï'=>'I','Î'=>'I','Û'=>'U','Ü'=>'U','Ç'=>'C'
    ]);
    $n = preg_replace('/\s+/', ' ', $n);
    $n = trim($n);
    $parts = explode(' ', $n);
    sort($parts);
    return implode(' ', $parts);
}

// Période
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-t');
$start_time = $_GET['start_time'] ?? '00:00:00';
$end_time   = $_GET['end_time']   ?? '23:59:59';

// Mois courant pour les congés
$firstDay = date('Y-m-01', strtotime($start));
$lastDay  = date('Y-m-t', strtotime($start));
$month = date('m', strtotime($start));
$year = date('Y', strtotime($start));

// Chargement Excel
$template = __DIR__ . "/Consignes de paie VIERGE.xlsx";
use PhpOffice\PhpSpreadsheet\IOFactory;
$spreadsheet = IOFactory::load($template);
$sheet = $spreadsheet->getActiveSheet();

// DEBUG : Liste noms Excel (colonne B)
$excelRows = [];
foreach ($sheet->getRowIterator(2) as $excelRow) {
    $nomExcel = $sheet->getCell("B" . $excelRow->getRowIndex())->getValue() ?? '';
    $nomExcelNorm = normalise_nom($nomExcel);
    $excelRows[] = [
        'row' => $excelRow->getRowIndex(),
        'nom' => $nomExcel,
        'norm' => $nomExcelNorm
    ];
}

// Jours fériés (comme dans l’API)
$jours_fr = [1=>"Lundi",2=>"Mardi",3=>"Mercredi",4=>"Jeudi",5=>"Vendredi",6=>"Samedi",7=>"Dimanche"];
$start_date = new DateTime($start);
$end_date = new DateTime($end);
$jours_selectionnes = [];
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
foreach ($period as $date) {
    $num_jour = $date->format('N');
    if (isset($jours_fr[$num_jour])) $jours_selectionnes[] = $jours_fr[$num_jour];
}
$jours_sql = "'" . implode("','", $jours_selectionnes) . "'";

$jours_feries = [
    '2025-01-01','2025-04-21','2025-05-01',
    '2025-05-08','2025-05-29','2025-07-14',
    '2025-08-15','2025-11-01','2025-11-11','2025-12-25'
];

// Labels absence
$typeLabels = [
    'conge'           => 'Congé Payé',
    'arret_maladie'   => 'Arrêt Maladie',
    'conge_maternite' => 'Congé Maternité',
    'enfant_malade'   => 'Enfant Malade',
    'Revision'        => 'Jour(s) de révision',
    'Exam'            => 'Examen',
    'Formation'       => 'Formation'
];

// === SQL comme avant ===
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
  u.id as user_id,
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
  COALESCE(e.heures_supp_50,0) AS heures_supp_50
FROM schedules s
JOIN users u ON s.user_id = u.id
LEFT JOIN extra_hours e ON s.user_id = e.user_id
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
GROUP BY u.id, u.name, e.heures_supp_25, e.heures_supp_50
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "ssssssssss",
  $end, $start, $end,
  $start_time, $end_time,
  $end, $start, $end,
  $start_time, $end_time
);

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $nomBdd = $row['nom'];
    $nomBddNorm = normalise_nom($nomBdd);
    $user_id = (int)$row['user_id'];

    // Récupère TOUS les congés du mois pour ce user
    $sqlConge = "SELECT start_date, end_date, absence_type FROM conges
        WHERE user_id = $user_id
        AND (start_date <= '$lastDay') AND (end_date >= '$firstDay')";
    $resConge = $conn->query($sqlConge);

    $congesInfos = [];
    $motifs = [];
    $jours_conges = 0;
    if ($resConge) while ($rowConge = $resConge->fetch_assoc()) {
        $startC = max($rowConge['start_date'], $firstDay);
        $endC   = min($rowConge['end_date'], $lastDay);
        $startObj = new DateTime($startC);
        $endObj   = new DateTime($endC);
        $nbj = 0;
        while ($startObj <= $endObj) {
            $cur = $startObj->format('Y-m-d');
            $dayOfWeek = $startObj->format('N');
            if ($dayOfWeek != 7 && !in_array($cur, $jours_feries)) $nbj++;
            $startObj->modify('+1 day');
        }
        $jours_conges += $nbj;
        
        // Affiche *pour le mois courant uniquement*
        $dateDebut = (new DateTime($startC))->format('d/m');
        $dateFin   = (new DateTime($endC))->format('d/m');
        $motifLib = $typeLabels[$rowConge['absence_type']] ?? $rowConge['absence_type'];
        $congesInfos[] = "$dateDebut-$dateFin";
        $motifs[] = $motifLib;
    }
    $dateColN = implode("\n", $congesInfos);
    $motifColP = implode("\n", $motifs);

  
    // Recherche la ligne Excel (par nom normalisé)
    $ligneExcel = null;
    foreach ($excelRows as $excel) {
        if ($excel['norm'] === $nomBddNorm) {
            $ligneExcel = $excel['row'];
            break;
        }
    }
    if (!$ligneExcel) {
        continue;
    }
    if (trim($sheet->getCell("B" . $ligneExcel)->getValue()) == '') {
        continue;
    }

    $colonnes = [
        'J' => $row['heures_supp_25'],
        'K' => $row['heures_supp_50'],
        'L' => $row['heures_dimanche'],
        'M' => $row['heures_feries'],
        'N' => $dateColN,
        'O' => $jours_conges,
        'P' => $motifColP
    ];
    foreach ($colonnes as $col => $val) {
        $current = $sheet->getCell($col . $ligneExcel)->getValue();
        if (trim((string)($current ?? '')) === '') {
            $sheet->setCellValue($col . $ligneExcel, $val);
            // Si colonne N (dates) ou P (motif), activer retour à la ligne
            if ($col == 'N' || $col == 'P') {
                $sheet->getStyle($col . $ligneExcel)->getAlignment()->setWrapText(true);
            }
        }
    }
    

}

$dateExport = date('Ym', strtotime($start)); // Ex: 202508 si $start = "2025-08-01"
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Consignes_de_paie_'.$dateExport.'.xlsx"');
header('Cache-Control: max-age=0');


$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;

?>
