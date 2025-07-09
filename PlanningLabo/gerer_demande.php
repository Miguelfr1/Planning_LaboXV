<?php
require_once "db.php";
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/vendor/phpmailer/phpmailer/src/Exception.php';
require '../phpmailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../phpmailer/vendor/phpmailer/phpmailer/src/SMTP.php';

if (!isset($_SESSION['user_id'])) die("Non autorisé.");
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT is_admin FROM users WHERE id='$user_id'");
$isAdmin = $res->fetch_assoc()['is_admin'] ?? 0;

if (!$isAdmin) die("Accès réservé à l’admin.");

// Tableau de correspondance des types d’absence
$typeLabels = [
    'conge'           => 'Congé Payé',
    'arret_maladie'   => 'Arrêt Maladie',
    'conge_maternite' => 'Congé Maternité',
    'enfant_malade'   => 'Enfant Malade',
    'Revision'        => 'Jour(s) de révision',
    'Exam'            => 'Examen',
    'Formation'       => 'Formation'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    // Récupère la demande
    $demande = $conn->query("SELECT * FROM conges_pending WHERE id='$id'")->fetch_assoc();
    if (!$demande) die("Demande introuvable.");

    // Récupérer info utilisateur demandeur
    $userRes = $conn->query("SELECT email, name FROM users WHERE id = " . intval($demande['user_id']));
    $userInfo = $userRes->fetch_assoc();
    $userEmail = $userInfo['email'] ?? '';
    $userName = $userInfo['name'] ?? '';

    $start_date = $demande['start_date'];
    $end_date = $demande['end_date'];
    $absence_type = $demande['absence_type'];
    $user_demande = $demande['user_id'];

    // Trouver le libellé formaté
    $libelleAbsence = $typeLabels[$absence_type] ?? $absence_type;

    // Liste des jours fériés pour calcul des jours_off (optionnel ici)
    $jours_feries = [
        "2025-01-01", "2025-04-21", "2025-05-01", "2025-05-08", "2025-05-29",
        "2025-06-09", "2025-07-14", "2025-08-15", "2025-11-01", "2025-11-11", "2025-12-25"
    ];

    if ($action == 'accepter') {
        // Calcul jours off
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

        // Marquer la demande comme acceptée
        $conn->query("UPDATE conges_pending SET etat='accepte' WHERE id='$id'");

        // Envoi email notification acceptation
        if ($userEmail) {
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

                $mail->setFrom('drh@laboxv.com', 'LABO XV - DRH');
                $mail->addAddress($userEmail, $userName);

                $mail->Subject = "Votre demande de congé a été acceptée";
                $mail->Body = "Bonjour $userName,\n\nVotre demande de congé du " . date('d/m/Y', strtotime($start_date)) .
                              " au " . date('d/m/Y', strtotime($end_date)) . " a été ACCEPTÉE.\n\n" .
                              "Type d'absence : $libelleAbsence\n\nCordialement,\nService RH LABO XV";

                $mail->send();
            } catch (Exception $e) {
                error_log("Erreur envoi mail acceptation: " . $mail->ErrorInfo);
            }
        }

        header("Location: conges.php?demande=acceptee");
        exit;

    } elseif ($action == 'refuser') {
        $motif = $_POST['motif_refus'] ?? '';
        $conn->query("UPDATE conges_pending SET etat='refuse', motif_refus='" . $conn->real_escape_string($motif) . "' WHERE id='$id'");

        // Envoi email notification refus
        if ($userEmail) {
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

                $mail->setFrom('drh@laboxv.com', 'LABO XV - DRH');
                $mail->addAddress($userEmail, $userName);

                $mail->Subject = "Votre demande de congé a été refusée";
                $mail->Body = "Bonjour $userName,\n\nVotre demande de congé du " . date('d/m/Y', strtotime($start_date)) .
                              " au " . date('d/m/Y', strtotime($end_date)) . " a été REFUSÉE.\n\n" .
                              "Motif du refus : $motif\n\nCordialement,\nService RH LABO XV";

                $mail->send();
            } catch (Exception $e) {
                error_log("Erreur envoi mail refus: " . $mail->ErrorInfo);
            }
        }

        header("Location: conges.php?demande=refusee");
        exit;
    }
}
?>
