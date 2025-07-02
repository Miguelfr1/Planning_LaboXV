<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap');

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins',sans-serif;
}
body{
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #dfdfdf;
}
.login-box{
    display: flex;
    justify-content: center;
    flex-direction: column;
    width: 440px;
    height: 480px;
    padding: 30px;
}
.login-header{
    text-align: center;
    margin: 20px 0 40px 0;
}
.login-header header{
    color: #333;
    font-size: 30px;
    font-weight: 600;
}
.input-box .input-field{
    width: 100%;
    height: 60px;
    font-size: 17px;
    padding: 0 25px;
    margin-bottom: 15px;
    border-radius: 30px;
    border: none;
    box-shadow: 0px 5px 10px 1px rgba(0,0,0, 0.05);
    outline: none;
    transition: .3s;
}
::placeholder{
    font-weight: 500;
    color: grey;
}
.input-field:focus{
    width: 105%;
}
.input-submit{
    position: relative;
}
.submit-btn{
    width: 100%;
    height: 60px;
    background: #3C91E7;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    transition: .3s;
    color: #fff;
    font-size: 18px;
}
.submit-btn:hover{
    background: #000;
    transform: scale(1.05,1);
}

.planning-link {
    text-align: center;
    margin-top: 20px;
}
.planning-link a {
    color: #3C91E7;
    font-size: 16px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}
.planning-link a:hover {
    color: #000;
}
</style>
