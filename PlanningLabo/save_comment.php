<?php
require_once "db.php";

$week_start = $_POST['week_start'] ?? '';
$laboratory = $_POST['laboratory'] ?? '';
$comment_content = $_POST['comment_content'] ?? '';

if ($week_start && $laboratory) {
    // Vérifie si le commentaire existe déjà
    $checkStmt = $conn->prepare("SELECT id FROM weekly_comments WHERE week_start = ? AND laboratory = ?");
    $checkStmt->bind_param("ss", $week_start, $laboratory);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Mettre à jour
        $updateStmt = $conn->prepare("UPDATE weekly_comments SET content = ? WHERE week_start = ? AND laboratory = ?");
        $updateStmt->bind_param("sss", $comment_content, $week_start, $laboratory);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insérer
        $insertStmt = $conn->prepare("INSERT INTO weekly_comments (week_start, laboratory, content) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $week_start, $laboratory, $comment_content);
        $insertStmt->execute();
        $insertStmt->close();
    }

    $checkStmt->close();
}

$conn->close();
header("Location: dashboard.php?week_start=$week_start&laboratory=$laboratory&alert=" . urlencode("Commentaire enregistré avec succès !"));
exit();
