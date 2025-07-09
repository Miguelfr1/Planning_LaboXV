<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/vendor/phpmailer/phpmailer/src/Exception.php';
require '../phpmailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../phpmailer/vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST["employee"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $absence_type = $_POST["absence_type"];

    if (!$employee_id || !$start_date || !$end_date || !$absence_type) {
        die("Données invalides.");
    }

    // Vérifier si c’est un admin
    $isAdmin = false;
    if (isset($_SESSION['user_id'])) {
        $myId = $_SESSION['user_id'];
        $userRes = $conn->query("SELECT is_admin FROM users WHERE id='$myId'");
        if ($userData = $userRes->fetch_assoc()) $isAdmin = $userData['is_admin'];
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

    // Labels propres pour les types d’absence
    $absenceLabels = [
        'conge'           => 'Congé Payé',
        'arret_maladie'   => 'Arrêt Maladie',
        'conge_maternite' => 'Congé Maternité',
        'enfant_malade'   => 'Enfant Malade',
        'Revision'        => 'Jour(s) de révision',
        'Exam'            => 'Examen',
        'Formation'       => 'Formation'
    ];
    $absence_label = $absenceLabels[$absence_type] ?? $absence_type;

    // Formatage des dates en français
    function formatDateFr($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : $date;
    }
    $start_date_fr = formatDateFr($start_date);
    $end_date_fr = formatDateFr($end_date);

    if ($isAdmin) {
        // ADMIN : insertion directe dans conges
        $stmt = $conn->prepare("INSERT INTO conges (user_id, start_date, end_date, days_off, absence_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $employee_id, $start_date, $end_date, $days_off, $absence_type);

        if ($stmt->execute()) {
            // Suppression des horaires pendant congé
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
            $end = new DateTime($end_date);

            while ($date <= $end) {
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

                $date->modify('+1 day');
            }

            header("Location: conges.php?success=1");
            exit();
        } else {
            echo "Erreur lors de l'enregistrement.";
        }
        $stmt->close();

    } else {
        // UTILISATEUR SIMPLE : insertion dans conges_pending + envoi mail DRH + confirmation utilisateur

        $stmt = $conn->prepare("INSERT INTO conges_pending (user_id, absence_type, start_date, end_date, etat) VALUES (?, ?, ?, ?, 'attente')");
        $stmt->bind_param("isss", $employee_id, $absence_type, $start_date, $end_date);

        if ($stmt->execute()) {
            // Récupérer nom + email utilisateur pour mail
            $userName = '';
            $userEmail = '';
            $userRes = $conn->query("SELECT name, email FROM users WHERE id = $employee_id");
            if ($userRes && $row = $userRes->fetch_assoc()) {
                $userName = $row['name'];
                $userEmail = $row['email'];
            }

            // Envoi mail DRH
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'pro3.mail.ovh.net';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'drh@laboxv.com';
                $mail->Password   = 'L@boxv75015';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('drh@laboxv.com', 'LABO XV');
                $mail->addAddress('drh@laboxv.com', 'DRH LABO XV');

                $mail->Subject = "Nouvelle demande de congé";
                $mail->Body = "Une nouvelle demande de congé a été soumise.\n\n"
                            . "Collaborateur : $userName\n"
                            . "Type d'absence : $absence_label\n"
                            . "Du : $start_date_fr\n"
                            . "Au : $end_date_fr\n";

                $mail->send();

                // Envoi mail confirmation au collaborateur
                $mail->clearAddresses();
                $mail->setFrom('drh@laboxv.com', 'LABO XV');
                $mail->addAddress($userEmail, $userName);

                $mail->Subject = "Confirmation de réception de votre demande de congé";
                $mail->Body = "Bonjour $userName,\n\n"
                            . "Nous avons bien reçu votre demande de congé.\n"
                            . "Type d'absence : $absence_label\n"
                            . "Du : $start_date_fr\n"
                            . "Au : $end_date_fr\n\n"
                            . "Nous vous tiendrons informé(e) de sa validation.\n\n"
                            . "Cordialement,\n"
                            . "Service RH - LABO XV";

                $mail->send();

            } catch (Exception $e) {
                error_log("Erreur envoi mail DRH ou confirmation : " . $mail->ErrorInfo);
            }

            header("Location: conges.php?pending=1");
            exit();
        } else {
            echo "Erreur lors de la demande de congé.";
        }
        $stmt->close();
    }
}

$conn->close();
?>
