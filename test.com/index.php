<?php
$to = 'migofr1@gmail.com';
$subject = 'Test d\'envoi d\'email via le port 587';
$message = 'Ceci est un test d\'envoi d\'email depuis rdv@laboxv.com via le port 587.';
$headers = 'From: rdv@laboxv.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

// Envoie de l'email
if(mail($to, $subject, $message, $headers)) {
    echo 'Email envoyé avec succès !';
} else {
    echo 'Échec de l\'envoi de l\'email.';
}
?>
