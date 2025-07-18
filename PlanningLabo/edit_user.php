    <?php
    session_start(); // <-- à ajouter !

    require_once 'db.php';

    if (!isset($_GET['id'])) {
        die('ID utilisateur non spécifié.');
    }

    if (!isset($_SESSION['user_id'])) {
        die('Non connecté.');
    }
    $my_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
    $stmt->bind_param('i', $my_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $me = $res->fetch_assoc();
    $is_admin = ($me && $me['is_admin']) ? true : false;

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
    $laboratories = ['vaugirard', '351_vaugirard', 'mozart', 'grignon'];

    // Liste fixe des rôles possibles
    $roles = ['Boss', 'Biologiste', 'Qualité', 'Préleveur', 'Bactério', 'Immuno', 'Secrétaire', 'Apprenti','Apprenti Bacterio','Apprenti Immuno', 'Apprenti Secretaire', 'Employé', 'Stagiaire', "Agent d'entretien", 'Coursier' ];

    $error = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : $user['password'];

        if ($is_admin) {
            $selectedRoles = $_POST['role'] ?? [];
            $role = implode(',', array_map('trim', $selectedRoles));
            $is_admin_post = isset($_POST['is_admin']) ? 1 : 0;
            $laboratories = $_POST['laboratories'] ?? [];
        } else {
            $role = $user['role'];
            $is_admin_post = $user['is_admin'];
            $laboratories = $selectedLaboratories;
        }


        if (!$error) {
            // Mettre à jour les informations de l'utilisateur
            $updateQuery = "UPDATE users SET name = ?, email = ?, password = ?, role = ?, is_admin = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param('ssssii', $name, $email, $password, $role, $is_admin_post, $id);
            
            if ($stmt->execute()) {
                // Supprimer les laboratoires existants
                $deleteQuery = "DELETE FROM user_laboratories WHERE user_id = ?";
                $stmt2 = $conn->prepare($deleteQuery);
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $stmt2->close();

                // Ajouter les nouveaux laboratoires sélectionnés
                if (!empty($laboratories)) {
                    $laboratories = array_unique($laboratories); // AJOUTE CETTE LIGNE !
                    $insertQuery = "INSERT INTO user_laboratories (user_id, laboratory) VALUES (?, ?)";
                    $stmt3 = $conn->prepare($insertQuery);
                    foreach ($laboratories as $laboratory) {
                        $stmt3->bind_param('is', $id, $laboratory);
                        $stmt3->execute();
                    }
                    $stmt3->close();
                }
                
                echo "<script>window.location.href = 'employers.php';</script>";
                exit();
            } else {
                $error = "Erreur lors de la mise à jour de l'utilisateur.";
            }
            $stmt->close();
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

            .error {
                color: #c00;
                margin-bottom: 14px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
    <div class="form-container">
            <h1>Modifier l'utilisateur</h1>
            <?php if (!empty($error)) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>
            <form method="POST" action="">
                <label for="name">Nom :</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">

                <label for="password">Mot de passe (laisser vide pour ne pas changer) :</label>
                <input type="password" id="password" name="password">

                <?php if ($is_admin): ?>
                    <label>Rôle(s) :</label>
<div class="dropdown-multiselect" tabindex="0" onclick="toggleDropdown(event)">
    <div class="dropdown-selected" id="dropdown-selected">
        <!-- Rôles sélectionnés affichés ici -->
    </div>
    <div class="dropdown-list" id="dropdown-list" style="display:none;">
        <div class="roles-flex">
        <?php
        $userRoles = array_map('trim', explode(',', $user['role']));
        foreach ($roles as $role): ?>
            <label class="role-square<?php echo in_array($role, $userRoles) ? ' checked' : ''; ?>">
                <input type="checkbox" name="role[]" value="<?php echo htmlspecialchars($role); ?>"
                    <?php echo in_array($role, $userRoles) ? 'checked' : ''; ?>
                    onchange="updateSelectedRoles()" />
                <span><?php echo htmlspecialchars($role); ?></span>
            </label>
        <?php endforeach; ?>
        </div>
    </div>
</div>



    <style>
    .custom-multiselect {
        position: relative;
        width: 100%;
        background: #fff;
        border: 1.5px solid #3498db;
        border-radius: 7px;
        min-height: 44px;
        cursor: pointer;
        padding: 6px 12px;
        margin-bottom: 17px;
        transition: border-color 0.2s;
    }
    .custom-multiselect:focus, .custom-multiselect.active {
        border-color: #0056b3;
    }
    .selected-options {
        min-height: 24px;
        color: #333;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        font-size: 15px;
    }
    .selected-options:empty::after {
        content: "Sélectionnez un ou plusieurs rôles…";
        color: #aaa;
        font-size: 14px;
    }
   
    .roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 14px;
    justify-items: center;
    margin-bottom: 18px;
}
.dropdown-multiselect {
    width: 100%;
    min-height: 46px;
    background: #fff;
    border: 2px solid #3498db;
    border-radius: 8px;
    padding: 7px 12px;
    margin-bottom: 20px;
    cursor: pointer;
    position: relative;
    box-sizing: border-box;
}
.dropdown-multiselect:focus,
.dropdown-multiselect.active {
    border-color: #0056b3;
}
.dropdown-selected {
    display: flex !important;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 28px;
    align-items: center;
    font-size: 15px;
}
.dropdown-selected:empty::after {
    content: "Sélectionnez un ou plusieurs rôles…";
    color: #aaa;
    font-size: 14px;
}

.dropdown-list {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    border: 2px solid #3498db;
    border-radius: 8px;
    z-index: 10;
    margin-top: 3px;
    box-shadow: 0 6px 20px rgba(80,80,140,0.11);
    padding: 14px;
}

.roles-flex {
    display: flex !important
;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-evenly;
    align-items: flex-start;
    flex-direction: row;
}

.role-square {
    display: flex !important; 
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #e3f2fd;
    border: 2px solid #3498db;
    border-radius: 9px;
    width: 105px;
    height: 65px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
    margin-bottom: 5px;
    position: relative;
    box-sizing: border-box;
    text-align : center !important;
}

.role-square input[type="checkbox"] {
    display: none;
}

.role-square.checked {
    background: #2196f3 !important;
    border-color: #1565c0 !important;
    color: #fff !important;
}

.role-square.checked span {
    color: #fff !important;
}

    </style>

    <script>

function toggleRole(cb) {
    const label = cb.parentNode;
    if (cb.checked) {
        label.classList.add('checked');
    } else {
        label.classList.remove('checked');
    }
}

// Initialiser l’état au chargement (pour garder les checked déjà présents au reload)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.role-square input[type="checkbox"]').forEach(cb => {
        if (cb.checked) cb.parentNode.classList.add('checked');
    });
});
function toggleDropdown(e) {
    const dropdown = e.currentTarget;
    // N'ouvre/ferme que si on clique sur la div principale (pas sur un input à l'intérieur)
    if (e.target === dropdown || e.target.classList.contains("dropdown-selected")) {
        const list = dropdown.querySelector('.dropdown-list');
        const isOpen = list.style.display === "block";
        list.style.display = isOpen ? "none" : "block";
        dropdown.classList.toggle('active', !isOpen);
    }
}



// Ferme le dropdown si clic à l’extérieur
document.addEventListener('click', function(e) {
    const sel = document.querySelector('.dropdown-multiselect');
    if (sel && !sel.contains(e.target)) {
        sel.querySelector('.dropdown-list').style.display = 'none';
        sel.classList.remove('active');
    }
});
// Affichage initial si précoché
document.addEventListener('DOMContentLoaded', updateSelectedRoles);


function updateSelectedRoles() {
    const dropdown = document.querySelector('.dropdown-multiselect');
    const checked = dropdown.querySelectorAll('.dropdown-list input[type="checkbox"]:checked');
    const selected = Array.from(checked).map(cb => cb.nextSibling.textContent.trim());
    const selectedDiv = document.getElementById('dropdown-selected');
    if (selected.length === 0) {
        selectedDiv.innerHTML = '';
    } else {
        selectedDiv.innerHTML = selected.map(s => `<span style="background:#e3f2fd;padding:2px 9px 2px 9px;border-radius:4px;display:inline-block">${s}</span>`).join(' ');
    }
    // Update visuel checked sur carré
    dropdown.querySelectorAll('.role-square').forEach(label => {
        const cb = label.querySelector('input[type="checkbox"]');
        if (cb.checked) label.classList.add('checked');
        else label.classList.remove('checked');
    });
}

    // Ferme dropdown si clic ailleurs
    document.addEventListener('click', function(e) {
        const sel = document.querySelector('.custom-multiselect');
        if (!sel.contains(e.target)) {
            sel.querySelector('.dropdown-list').style.display = 'none';
            sel.classList.remove('active');
        }
    });
    // Met à jour à l’ouverture/chargement (pour affichage initial)
    document.addEventListener('DOMContentLoaded', updateSelectedRoles);
    </script>


                    <label for="is_admin">Administrateur :</label>
                    <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>

                    <label>Laboratoires :</label>
                    <div class="laboratory-buttons">
                    <?php foreach (['Plateau Technique', '351_vaugirard', 'Mozart', 'Grignon'] as $lab): ?>
    <button type="button" value="<?php echo htmlspecialchars($lab); ?>" class="<?php echo in_array($lab, $selectedLaboratories) ? 'active' : ''; ?>" onclick="toggleLaboratory(this)">
        <?php 
            $labels = ['Plateau Technique' => 'Plateau Technique', '351_vaugirard' => '351, Vaugirard', 'Mozart' => 'Mozart', 'Grignon' => 'Grignon'];
            echo $labels[$lab];
        ?>
    </button>
<?php endforeach; ?>



                    </div>
                    <div id="laboratories-container">
                        <?php foreach ($selectedLaboratories as $selectedLab): ?>
                            <input type="hidden" name="laboratories[]" value="<?php echo htmlspecialchars($selectedLab); ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button type="submit">Mettre à jour</button>
            </form> <!-- <-- il manquait la fermeture ici ! -->
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
