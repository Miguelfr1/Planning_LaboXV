<?php
require_once 'db.php';

if (!isset($_GET['id'])) {
    die('ID utilisateur non spécifié.');
}

$id = intval($_GET['id']);

// Récupérer les informations de l'utilisateur
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die('Utilisateur non trouvé.');
}

// Récupérer les laboratoires existants pour cet utilisateur
$query = "SELECT laboratory FROM user_laboratories WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$selectedLaboratories = [];
while ($row = $result->fetch_assoc()) {
    $selectedLaboratories[] = $row['laboratory'];
}

// Laboratoires disponibles
$laboratories = ['Vaugirard', 'Grignon', 'Mozart'];

// Liste fixe des rôles possibles
$roles = ['Boss', 'Docteur', 'Qualité', 'Préléveur', 'Bactério', 'Immuno', 'Secrétaire', 'Apprenti','Apprenti Bacterio','Apprenti Immuno', 'Apprenti Secretaire', 'Employé', 'Stagiaire'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : $user['password'];
    $selectedRoles = $_POST['role'] ?? [];
    $role = implode(',', array_map('trim', $selectedRoles));
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $laboratories = $_POST['laboratories'] ?? [];

    // Mettre à jour les informations de l'utilisateur
    $updateQuery = "UPDATE users SET name = ?, password = ?, role = ?, is_admin = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('sssii', $name, $password, $role, $is_admin, $id);

    if ($stmt->execute()) {
        // Supprimer les laboratoires existants
        $deleteQuery = "DELETE FROM user_laboratories WHERE user_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        // Ajouter les nouveaux laboratoires sélectionnés
        if (!empty($laboratories)) {
            $insertQuery = "INSERT INTO user_laboratories (user_id, laboratory) VALUES (?, ?)";
            $stmt = $conn->prepare($insertQuery);
            foreach ($laboratories as $laboratory) {
                $stmt->bind_param('is', $id, $laboratory);
                $stmt->execute();
            }
        }

        echo "<script> window.location.href = 'employers.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour de l\'utilisateur.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
    <style>
        :root {
            --blue: #3498db;
            --light: #ecf0f1;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--light);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background-color: white;
            border: 1px solid var(--blue);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 400px;
            text-align: center;
        }

        .form-container h1 {
            color: var(--blue);
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-container input,
        .form-container select,
        .form-container button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-container button {
            background-color: var(--blue);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

        .laboratory-buttons {
            display: flex;
            justify-content: space-between;
        }

        .laboratory-buttons button {
            flex: 1;
            margin: 5px;
            padding: 10px;
            background-color: var(--blue);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .laboratory-buttons button.active {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Modifier l'utilisateur</h1>
        <form method="POST" action="">
            <label for="name">Nom :</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

            <label for="password">Mot de passe (laisser vide pour ne pas changer) :</label>
            <input type="password" id="password" name="password">

            <label for="role">Rôle(s) :</label>
            <select id="role" name="role[]" multiple required>
                <?php 
                $userRoles = explode(',', $user['role']);
                foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role); ?>" <?php echo in_array($role, $userRoles) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="is_admin">Administrateur :</label>
            <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>

            <label>Laboratoires :</label>
            <div class="laboratory-buttons">
                <?php foreach (['Vaugirard', 'Grignon', 'Mozart'] as $lab): ?>
                    <button type="button" value="<?php echo htmlspecialchars($lab); ?>" class="<?php echo in_array($lab, $selectedLaboratories) ? 'active' : ''; ?>" onclick="toggleLaboratory(this)"><?php echo htmlspecialchars($lab); ?></button>
                <?php endforeach; ?>
            </div>
            <div id="laboratories-container">
                <?php foreach ($selectedLaboratories as $selectedLab): ?>
                    <input type="hidden" name="laboratories[]" value="<?php echo htmlspecialchars($selectedLab); ?>">
                <?php endforeach; ?>
            </div>

            <button type="submit">Mettre à jour</button>
        </form>
    </div>

    <script>
    function toggleLaboratory(button) {
        const labName = button.value;
        const container = document.getElementById("laboratories-container");

        // Vérifier si un input caché avec cette valeur existe déjà
        const existingInput = container.querySelector(`input[value="${labName}"]`);

        if (existingInput) {
            // Supprimer l'input caché si le labo est déjà sélectionné
            existingInput.remove();
            button.classList.remove("active");
        } else {
            // Ajouter un input caché si le labo n'est pas encore sélectionné
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "laboratories[]";
            input.value = labName;
            container.appendChild(input);
            button.classList.add("active");
        }
    }
    </script>

</body>
</html>
