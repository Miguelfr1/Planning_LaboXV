<?php
            session_start(); // Démarrer la session en haut de ton fichier PHP

        date_default_timezone_set('Europe/Paris'); // Utilisez le fuseau horaire approprié


        // Inclure la connexion à la base de données
        include 'db.php';

        $laboratoire = isset($_GET['lab']) ? $_GET['lab'] : 'Mozart';
        $dateRdv = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $annee = date('Y', strtotime($dateRdv)); // Récupère l'année de la date du rendez-vous

        // Ajoute cette vérification au début du fichier pour savoir si un admin est connecté
$adminConnecte = false; // Par défaut, on considère que l'admin n'est pas connecté

// Si l'admin est authentifié, on le met à vrai
if (isset($_POST['nomAdmin']) && !empty($_POST['nomAdmin'])) {
    // Ici tu vérifies si le nom d'admin est correct dans ta table 'admins'
    $nomAdmin = $_POST['nomAdmin'];
    $stmt = $conn->prepare("SELECT * FROM admins WHERE nom = ?");
    $stmt->bind_param("s", $nomAdmin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $adminConnecte = true; // L'admin est connecté
    }
    $stmt->close();
}


$joursFeries = [
    "$annee-01-01", // Jour de l'An
    date('Y-m-d', strtotime("easter monday $annee")), // Lundi de Pâques
    "$annee-05-01", // Fête du Travail
    "$annee-05-08", // Victoire 1945
    "$annee-07-14", // Fête Nationale
    "$annee-08-15", // Assomption
    "$annee-11-01", // Toussaint
    "$annee-12-25", // Noël
    "$annee-05-29", // Ascension

];

// Ensuite dans la partie des créneaux


        $adresses = [
            'Mozart' => '16 avenue Mozart - 75016 Paris',
            'Vaugirard' => '353 rue de Vaugirard - 75015 Paris',
            'Grignon - Thiais' => '19 rue du pavé de Grignon - 94320 Thiais'
        ];

        $horaires = [
            'Mozart' => [
                'Lundi' => ['ouverture' => '07:30', 'fermeture' => '18:00'],
                'Mardi' => ['ouverture' => '07:30', 'fermeture' => '18:00'],
                'Mercredi' => ['ouverture' => '07:30', 'fermeture' => '18:00'],
                'Jeudi' => ['ouverture' => '07:30', 'fermeture' => '18:00'],
                'Vendredi' => ['ouverture' => '07:30', 'fermeture' => '18:00'],
                'Samedi' => ['ouverture' => '07:30', 'fermeture' => '16:00'],
                'Dimanche' => ['ouverture' => null, 'fermeture' => null], // Fermé
                'Férié' => ['ouverture' => null, 'fermeture' => null] // Fermé

            ],
            'Vaugirard' => [
                'Lundi' => ['ouverture' => '07:30', 'fermeture' => '20:00'],
                'Mardi' => ['ouverture' => '07:30', 'fermeture' => '20:00'],
                'Mercredi' => ['ouverture' => '07:30', 'fermeture' => '20:00'],
                'Jeudi' => ['ouverture' => '07:30', 'fermeture' => '20:00'],
                'Vendredi' => ['ouverture' => '07:30', 'fermeture' => '20:00'],
                'Samedi' => ['ouverture' => '07:30', 'fermeture' => '16:00'],
                'Dimanche' => ['ouverture' => '09:00', 'fermeture' => '14:00'],
                'Férié' => ['ouverture' => '09:00', 'fermeture' => '14:00'] // Horaires spécifiques aux jours fériés

            ],
            'Grignon - Thiais' => [
                'Lundi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Mardi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Mercredi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Jeudi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Vendredi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Samedi' => ['ouverture' => '07:30', 'fermeture' => '14:30'],
                'Dimanche' => ['ouverture' => '09:00', 'fermeture' => '13:00'],
                'Férié' => ['ouverture' => '09:00', 'fermeture' => '13:00'] // Horaires spécifiques aux jours fériés

            ]
        ];

       

        if (in_array($dateRdv, $joursFeries)) {
            $jour = 'Férié'; // Utilisez les horaires du dimanche
        } else {
            $jour_semaine = date('l', strtotime($dateRdv));
            $jour_semaine_fr = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'];
            $jour = $jour_semaine_fr[$jour_semaine];
        }

        $adresse_labo = isset($adresses[$laboratoire]) ? $adresses[$laboratoire] : 'Adresse inconnue';
        $ouverture = $horaires[$laboratoire][$jour]['ouverture'];
$fermeture = $horaires[$laboratoire][$jour]['fermeture'];


        function generer_creneaux($ouverture, $fermeture) {
            $creneaux = [];
            if ($ouverture && $fermeture) {
                $debut = new DateTime($ouverture);
                $fin = new DateTime($fermeture);
                while ($debut < $fin) {
                    $creneaux[] = $debut->format('H:i');
                    $debut->modify('+5 minutes');
                }
            }
            return $creneaux;
        }

        $date_rdv = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $creneaux_disponibles = generer_creneaux($ouverture, $fermeture);

        // Récupérer la date et l'heure actuelles
        $datetime_actuel = new DateTime();

        // Filtrer les créneaux antérieurs à la date et l'heure actuelles
        $creneaux_disponibles = array_filter($creneaux_disponibles, function($creneau) use ($date_rdv, $datetime_actuel) {
            $datetime_creneau = new DateTime("$date_rdv $creneau");
            return $datetime_creneau >= $datetime_actuel;
        });

        $creneaux_pris = [];
        $query = "SELECT heure_rdv FROM rendez_vous WHERE laboratoire='$laboratoire' AND date_rdv='$date_rdv' AND statut='confirmé'";
        $result = $conn->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $creneaux_pris[] = date('H:i', strtotime($row['heure_rdv']));
            }
        } else {
            echo "Erreur dans la requête : " . $conn->error;
        }

        
        $erreur = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Vérification de l'existence de 'nomAdmin' dans le POST
            if (isset($_POST['nomAdmin'])) {
                $nomAdmin = $_POST['nomAdmin'];
        
                // Préparation de la requête SQL pour vérifier le nom dans la table `admins`
                $stmt = $conn->prepare("SELECT * FROM admins WHERE nom = ?");
                $stmt->bind_param("s", $nomAdmin);
                $stmt->execute();
                $result = $stmt->get_result();
        
                // Si un admin est trouvé, transformer la page ou rediriger
                if ($result->num_rows > 0) {
                    // Admin trouvé, on transforme le contenu de la page ici
                    
                    
                    // Ici, tu pourrais afficher d'autres éléments spécifiques à l'admin
                    // Par exemple, afficher les rendez-vous ou des outils admin
                } else {
                    // Si le nom d'admin est incorrect, afficher un message d'erreur
                    echo "<p style='color:red;'>Nom administrateur invalide.</p>";
                }
            }
        }


        $insertionReussie = false;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclure les fichiers PHPMailer avec des chemins relatifs
require 'phpmailer/vendor/phpmailer/phpmailer/src/Exception.php';
require 'phpmailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'phpmailer/vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $formType = isset($_POST['formType']) ? $_POST['formType'] : '';

    if ($formType === 'reservation') {
        $prenom = isset($_POST['prenom']) ? ucfirst(strtolower($_POST['prenom'])) : '';
        $nom = isset($_POST['nom']) ? ucfirst(strtolower($_POST['nom'])) : '';
        

        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $telephone = isset($_POST['telephone']) ? $_POST['telephone'] : '';
        $laboratoire = isset($_POST['laboratoire']) ? $_POST['laboratoire'] : '';
        $date_rdv = isset($_POST['date_rdv']) ? $_POST['date_rdv'] : '';
        $heure_rdv = isset($_POST['heure_rdv']) ? $_POST['heure_rdv'] : '';

        // Étape 1 : Insérer les informations de l'utilisateur dans la table `utilisateurs`
        $stmt_user = $conn->prepare("INSERT INTO utilisateurs (prenom, nom, email, telephone) VALUES (?, ?, ?, ?)");
        $stmt_user->bind_param("ssss", $prenom, $nom, $email, $telephone);

        if ($stmt_user->execute()) {
            // Récupérer l'ID de l'utilisateur récemment inséré
            $utilisateur_id = $conn->insert_id;

            // Étape 2 : Insérer les données de rendez-vous dans la table `rendez_vous`
            $stmt_rdv = $conn->prepare("INSERT INTO rendez_vous (utilisateur_id, laboratoire, date_rdv, heure_rdv, statut) VALUES (?, ?, ?, ?, 'confirmé')");
            $stmt_rdv->bind_param("isss", $utilisateur_id, $laboratoire, $date_rdv, $heure_rdv);

            if ($stmt_rdv->execute()) {
                // Étape 3 : Envoyer les emails de confirmation avec PHPMailer
                $mail = new PHPMailer(true);
            
                try {
                    // Configurer le serveur SMTP
                    $mail->isSMTP();
                    $mail->Host = 'pro3.mail.ovh.net';  
                    $mail->SMTPAuth = true;
                    $mail->Username = 'rdv@laboxv.com';  
                    $mail->Password = 'L@boxv75015';  
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
            

                    $mail->CharSet = 'UTF-8';  // Cela permet de gérer les accents correctement

                    
                    // **1. Envoyer l'email au patient**
                    // Définir les informations de l'expéditeur et du destinataire
                    $mail->setFrom('rdv@laboxv.com', 'LABO XV');
                    $mail->addAddress($email, "$prenom $nom");

                    $baseURL = "http://www.laboxv.com/annuler_rdv.php"; // URL de la page pour gérer les annulations
                    $cancelURL = "$baseURL?id={$stmt_rdv->insert_id}&token=" . md5($stmt_rdv->insert_id . 'cle_secrete'); // Générer un jeton sécurisé
               	    $adresse_labo = isset($adresses[$laboratoire]) ? $adresses[$laboratoire] : 'Adresse inconnue';

                    // Contenu de l'email pour le patient
                    $mail->isHTML(false);  
                    $date_rdv_formatee = date('d-m-Y', strtotime($date_rdv));     

                    $mail->Subject = "Confirmation de votre rendez-vous";
                    $mail->Body = "Bonjour $prenom $nom,\n\nVotre rendez-vous au laboratoire est confirmé !\n\nLaboratoire : $laboratoire\nAdresse : $adresse_labo\n\nDate : $date_rdv_formatee\nHeure : $heure_rdv\n\nPensez à vous munir de :\n- Votre Ordonnance Médicale\n- Votre Carte Vitale ou Attestation\n- Votre Carte Mutuelle ou Attestation\n\nMerci de votre confiance.\nDr Natalio AWAIDA\n\nPour annuler votre rendez-vous, cliquez sur le lien suivant :\n$cancelURL";
            
                    // Envoyer l'email au patient
                    $mail->send();
            
                    // **2. Envoyer l'email à Dr. Awaida**
                    // Vider les destinataires précédents pour éviter toute confusion
                    $mail->clearAddresses();
                    
                    // Ajouter l'adresse de Dr. Awaida
                    $mail->addAddress('rdv@laboxv.com', 'Dr. Awaida');

                    // Contenu de l'email pour le patron
                    $mail->Subject = "RdV";
                    $mail->Body    = "Laboratoire : $laboratoire \n$date_rdv_formatee\n$heure_rdv\n\nNom : $nom \nPrénom : $prenom\nEmail : $email\nTéléphone : $telephone";
            
                    // Envoyer l'email à Dr. Awaida
                    $mail->send();
            
                    // Message de succès
                    $_SESSION['message'] = 'Votre rendez-vous a été enregistré et des emails de confirmation ont été envoyés.';
            
                    // Redirection pour éviter le rafraîchissement
                    header("Location: rendez_vous.php");
                    exit;
            
                } catch (Exception $e) {
                    echo "<script>alert('Erreur lors de l\'envoi des emails : {$mail->ErrorInfo}');</script>";
                }
            } else {
                echo "<script>alert('Erreur lors de la réservation: " . $stmt_rdv->error . "');</script>";
            }
            
            $stmt_rdv->close();
        } else {
            echo "<script>alert('Erreur lors de l\'insertion de l\'utilisateur: " . $stmt_user->error . "');</script>";
        }
        $stmt_user->close();
    }
}


        ?>


    





<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Prendre Rendez-vous - <?php echo $laboratoire; ?></title>
        <link rel="stylesheet" href="headerFooter_style.css">
        <link rel="stylesheet" href="stylerendezvous.css">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <link rel="icon" href="./favicon.ico" type="image/x-icon">

   
    </head>
    <body>


    <header class="header">
            <div class="logo">
            <a href="index.html">LABO XV</a>
            </div>
    
        </header>
        
    <div class="container">
            <h1>Prise de rendez-vous pour Laboratoire <?php echo $laboratoire; ?></h1>
            <h3><?php echo "Laboratoire $laboratoire, $adresse_labo"; ?></h3>
            
            <!-- Sélection de laboratoire -->
            <label for="laboratoire">Sélectionnez un laboratoire :</label>
            <select id="laboratoire" onchange="changerLaboratoire()">
                <?php foreach ($horaires as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo $key === $laboratoire ? 'selected' : ''; ?>><?php echo $key; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Sélection de la date -->
            <label for="date">Sélectionnez une date :</label>
            <input type="date" id="date" value="<?php echo $date_rdv; ?>" onchange="changerDate()">
                            
            <div id="confirmationMessage" style="display: none; color: green; text-align: center; margin-bottom: 20px;">
                Votre réservation a été confirmée ! Vous recevrez une confirmation par email.
            </div>

                    
            <div class="creneaux">
    <?php foreach ($creneaux_disponibles as $creneau): ?>
        <div class="creneau <?php echo in_array($creneau, $creneaux_pris) ? 'pris' : 'libre'; ?>">
            <?php if (in_array($creneau, $creneaux_pris)): ?>
                <?php if ($adminConnecte): ?>
                    <!-- Si un admin est connecté, afficher le bouton Infos -->
                    <button class="bouton-infos" onclick="afficherInfos('<?php echo $creneau; ?>')">Infos rdv</button>
                <?php else: ?>
                    <!-- Sinon, le créneau est juste marqué comme réservé -->
                    <button class="bouton-reserver" disabled>Réserver</button>
                <?php endif; ?>
            <?php else: ?>
                <!-- Si le créneau est libre, afficher le bouton Réserver -->
                <button class="bouton-reserver" onclick="ouvrirModal('<?php echo $creneau; ?>')">Réserver</button>
            <?php endif; ?>
            <span><?php echo $creneau; ?></span>
            <span class="status <?php echo in_array($creneau, $creneaux_pris) ? 'pris' : 'libre'; ?>">
                <?php echo in_array($creneau, $creneaux_pris) ? 'Réservé' : 'Libre'; ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>
        
        

        <!-- Formulaire pour entrer le nom clé -->
    <form method="POST" action="">
            <input type="text" id="nomAdmin" name="nomAdmin" required>
        </form>
        </div>


            </div>

            
    
        <!-- Modale pour le formulaire de réservation -->
        <div id="modalReservation" class="modal">
            <div class="modal-content">
                <span class="close" onclick="fermerModal()">&times;</span>
                <h3 class="bouton_form" id="titreReservation">Réserver un créneau</h3>
                <form id="formReservation" method="POST" action="rendez_vous.php"onsubmit="desactiverBouton()">
    <input type="hidden" id="formType" name="formType" value="reservation">
    <input type="hidden" id="heureRdv" name="heure_rdv" value="">
    <input type="hidden" id="laboratoireRdv" name="laboratoire" value="<?php echo $laboratoire; ?>">
    <input type="hidden" id="dateRdv" name="date_rdv" value="<?php echo $date_rdv; ?>">
    
    <label for="prenom">Prénom :</label>
    <input type="text" id="prenom" name="prenom" required>
    
    <label for="nom">Nom :</label>
    <input type="text" id="nom" name="nom" required>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" required>

    <label for="telephone">Téléphone :</label>
    <input type="tel" id="telephone" name="telephone" required>

    <button type="submit" id="bouton_form" class="bouton-reserver">Confirmer la réservation</button>
</form>



            </div>
        
        </div>



<script>
    function desactiverBouton() {
        // Désactive le bouton pour éviter plusieurs clics
        const bouton = document.getElementById("bouton_form");
        bouton.disabled = true;
        bouton.innerText = "Envoi en cours...";
    }
</script>

        <footer>
        <p>&copy; 2024 LABO XV. Tous droits réservés.</p>
        </footer>



        <script>

    document.addEventListener("DOMContentLoaded", function() {
        var insertionReussie = <?php echo json_encode($insertionReussie); ?>;
        
        if (insertionReussie) {
            document.getElementById('confirmationMessage').style.display = 'block';
        }
    });

            function changerLaboratoire() {
                const laboratoire = document.getElementById('laboratoire').value;
                const date = document.getElementById('date').value;
                window.location.href = `rendez_vous.php?lab=${laboratoire}&date=${date}`;
            }

            function changerDate() {
                const date = document.getElementById('date').value;
                const laboratoire = document.getElementById('laboratoire').value;
                window.location.href = `rendez_vous.php?lab=${laboratoire}&date=${date}`;
            }

            function ouvrirModal(heure) {
        // Récupérer la date sélectionnée
        const date = document.getElementById('date').value;
        
        // Formater la date en JJ-MM-AAAA
        const dateFormatee = new Date(date).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });

        // Mettre à jour le titre de la modale avec la date et l'heure sélectionnées
        const titreReservation = document.getElementById('titreReservation');
        titreReservation.textContent = `Réservation le ${dateFormatee} à ${heure}`;

        // Ouvrir la modale
        document.getElementById('heureRdv').value = heure;
        document.getElementById('modalReservation').style.display = "block";
    }


            function fermerModal() {
                document.getElementById('modalReservation').style.display = "none";
            }

            window.onclick = function(event) {
                const modal = document.getElementById('modalReservation');
                if (event.target === modal) {
                    fermerModal();
                }
            }


            // Fonction pour afficher les infos du réservateur dans une pop-up
function afficherInfos(heure) {
    // Récupérer la date et le laboratoire sélectionnés
    const date = document.getElementById('date').value;
    const laboratoire = document.getElementById('laboratoire').value;

    // Requête AJAX pour récupérer les informations du réservateur
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `infos_reservateur.php?heure=${heure}&date=${date}&laboratoire=${laboratoire}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Afficher les infos dans une pop-up ou une modale
            const infos = JSON.parse(xhr.responseText);
            alert(`Réservation pour ${infos.prenom} ${infos.nom}\nEmail: ${infos.email}\nTéléphone: ${infos.telephone}`);
        }
    };
    xhr.send();
}

        </script>







<style>
          body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
            }

            .container {
                max-width: 800px;
                background: white;
                border-radius: 10px;
                box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            }

            h1 {
                font-size: 2.5rem;
                color: #333;
                margin-bottom: 20px;
                text-align: center;
            }

            h3 {
                font-size: 1.5rem;
                color: #555;
                margin-bottom: 30px;
                text-align: center;
            }

            select, input[type="date"] {
                width: 100%;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                border: 2px solid #ddd;
                font-size: 1rem;
            }

            .creneaux {
                display: flex;
                flex-direction: column;
            }

            .creneau {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 5px;
                border: 1px solid #ddd;
                background-color: #fff;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                font-size: 14px;
            }

            .bouton-reserver {
                padding: 8px 15px;
                background-color: #7d8894;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
            .bouton-infos{
                padding: 8px 15px;
                background-color: #0056b3;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s ease;
                
            }

            .bouton-reserver:hover {
                background-color: #0056b3;
            }

            .bouton-infos:hover {
                background-color : #7d8894;
                
            }


            .bouton-reserver:disabled {
                background-color: #ccc;
                cursor: not-allowed;
            }

            .heure-creneau {
                flex-grow: 1;
                text-align: center;
            }

            .status {
                flex-shrink: 0;
                width: 80px;
                text-align: right;
            }

            .bouton-reserver.disabled {
                background-color: #ccc;
                cursor: not-allowed;
            }

            .status {
                font-weight: bold;
            }

            .status.libre {
                color: #28a745;
            }

            .status.pris {
                color: #dc3545;
            }

            /* Style pour la modale */
            .modal {
                z-index: 1;
                left: 0%;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                background-color: rgba(0, 0, 0, 0.5);
        justify-content: center; /* Centrer horizontalement */
        align-items: center; /* Centrer verticalement */

            }

    

            .modal-content {
                
                
                top: 20%;

                margin-top:20px;
        background-color: #fff;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2); /* Ajout d'ombre pour la profondeur */
        border-radius: 8px; /* Coins arrondis pour un style plus doux */
        /* Utiliser margin pour ajuster l'espacement */
        margin: auto; /* Centre automatiquement horizontalement */
    }

            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }
            #nomAdmin{
                    border: white;
        margin: auto;
        align-items: center;
        text-align: center;
        display: flex;
        margin-top: 20px;
    }
                
      
        .bouton_form{
            text-align:center;
        }
        #formReservation{
            display:contents
        }
        #bouton_form{
            margin :10px 0 10px 0;
        }
        #modalReservation{
          

        }

        html, body {
        height: 100%; /* Fait en sorte que le corps prenne toute la hauteur */
        margin: 0; /* Retire les marges par défaut */
    }

    body {
        display: flex; /* Utilise flexbox */
        flex-direction: column; /* Colonne pour aligner le contenu verticalement */
    }

    .container {
        flex: 1; /* Permet au conteneur de s'étendre pour remplir l'espace restant */
    }

</style>







    </body>
    </html>














