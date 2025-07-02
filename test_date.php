<?php
date_default_timezone_set('Europe/Paris');

$date_rdv = '25-12-2024'; // Remplacez par une autre valeur pour tester
if (preg_match('/\d{2}-\d{2}-\d{4}/', $date_rdv)) {
    $date_rdv = DateTime::createFromFormat('d-m-Y', $date_rdv)->format('Y-m-d');
}

if ($timestamp = strtotime($date_rdv)) {
    $date_rdv_formatee = date('d-m-Y', $timestamp);
} else {
    $date_rdv_formatee = date('d m Y');
}

echo "Date brute : $date_rdv\n";
echo "Date formatée : $date_rdv_formatee\n";
?>
