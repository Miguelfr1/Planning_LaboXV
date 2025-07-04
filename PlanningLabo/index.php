<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Connexion</title>
</head>
<body>
    <?php
   session_start();

   // Si l'utilisateur est déjà connecté, NE PAS rediriger en boucle
   if (isset($_SESSION['user_id'])) {
       header("Location: dashboard.php");
       exit();
   }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "db.php";

        
        $name = trim($_POST["name"]);
        $password = trim($_POST["password"]);

        if (!empty($name) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $hashed_password, $role);
                $stmt->fetch();
                
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["user_id"] = $id;
                    $_SESSION["user_name"] = $name;
                    $_SESSION["user_role"] = $role;
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Mot de passe incorrect.";
                }
            } else {
                $error = "Utilisateur introuvable.";
            }
            $stmt->close();
        } else {
            $error = "Veuillez remplir tous les champs.";
        }
        $conn->close();
    }
    ?>

    <div class="login-box">
        <div class="login-header">
            <header>Connexion</header>
        </div>
        <?php if (!empty($error)) { echo "<p style='color: red; text-align: center;'>$error</p>"; } ?>
        <form action="" method="POST">
            <div class="input-box">
                <input type="text" name="name" class="input-field" placeholder="Nom" autocomplete="off" required>
            </div>
            <div class="input-box">
                <input type="password" name="password" class="input-field" placeholder="Mot de passe" autocomplete="off" required>
            </div>
            <div class="input-submit">
                <button type="submit" class="submit-btn">Se connecter</button>
            </div>
        </form>

         <!-- Lien vers le planning -->
    <div class="planning-link">
        <a href="dashboard.php">Voir le planning</a>
    </div>
    </div>
</body>
</html>

