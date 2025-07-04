<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']); // <-- Ajout du champ email
    $password = trim($_POST['password']);
    $roles = $_POST['role'] ?? [];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $laboratories = $_POST['laboratories'] ?? [];


        // Vérification unicité de l'email
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param('s', $email);
        $checkEmail->execute();
        $checkEmail->store_result();
        
        $checkEmail->close();

        // Hacher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Transformer les rôles en une chaîne séparée par des virgules
        $roleString = implode(',', array_map('trim', $roles));

        // Insérer l'utilisateur dans la base de données
        $query = "INSERT INTO users (name, email, password, role, is_admin) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('ssssi', $name, $email, $hashedPassword, $roleString, $is_admin);

            if ($stmt->execute()) {
                $userId = $stmt->insert_id;

                // Insérer les laboratoires associés
                if (!empty($laboratories)) {
                    $insertLabQuery = "INSERT INTO user_laboratories (user_id, laboratory) VALUES (?, ?)";
                    $labStmt = $conn->prepare($insertLabQuery);

                    if ($labStmt) {
                        foreach ($laboratories as $lab) {
                            $labStmt->bind_param('is', $userId, $lab);
                            $labStmt->execute();
                        }
                        $labStmt->close();
                    } else {
                        echo "<script>alert('Erreur lors de la préparation de la requête pour les laboratoires.');</script>";
                    }
                }

                echo "<script>window.location.href = 'employers.php';</script>";
            } else {
                echo "<script>alert('Erreur lors de la création de l\\'utilisateur : " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Erreur de préparation de la requête.');</script>";
        }
 }

?>
