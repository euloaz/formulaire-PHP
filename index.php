<?php
/* CONFIGURATION DE LA BASE DE DONNÉES*/


$host = 'localhost';
$db = 'conferences';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


$errors = [];
$values = [];
$pays_autorises = ['Montenegro', 'Mexique', 'Somalie', 'Australie', 'Philippines'];
$interets_autorises = ['PHP', 'JavaScript', 'DevOPs', 'IA'];
$types_autorises = ['Etudiant', 'Professionnel', 'Speaker'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //    Récupérations des données du formulaire
    $nom = isset($_POST['nom']) ? trim(strip_tags($_POST['nom'])) : '';
    $values['nom'] = $nom;

    $prenom = isset($_POST['prenom']) ? trim(strip_tags($_POST['prenom'])) : '';
    $values['prenom'] = $prenom;

    $email = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
    $values['email'] = $email;

    $mdp = isset($_POST['mdp']) ? $_POST['mdp'] : '';
    $verifmdp = isset($_POST['verifmdp']) ? $_POST['verifmdp'] : '';

    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $values['date'] = $date;

    $phone = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
    $values['phone'] = $phone;

    $pays = isset($_POST['pays']) ? $_POST['pays'] : '';
    $values['pays'] = $pays;

    $type_participant = isset($_POST['type_participant']) ? $_POST['type_participant'] : '';
    $values['type_participant'] = $type_participant;

    $centres_interet = isset($_POST['centres_interet']) ? $_POST['centres_interet'] : [];
    $values['centres_interet'] = $centres_interet;

    $conditions = isset($_POST['conditions']) ? true : false;
    $values['conditions'] = $conditions;


    // Verification des données du formulaire
    if (empty($nom)) {
        $errors['nom'] = "Le nom est obligatoire.";
    } elseif (strlen($nom) < 2 || strlen($nom) > 30) {
        $errors['nom'] = "Le nom doit contenir entre 2 et 30 caractères.";
    }

    if (empty($prenom)) {
        $errors['prenom'] = "Le prénom est obligatoire.";
    } elseif (strlen($prenom) < 2 || strlen($prenom) > 30) {
        $errors['prenom'] = "Le prénom doit contenir entre 2 et 30 caractères.";
    }

    if (empty($email)) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'email n'est pas valide.";
    }

    if (empty($mdp)) {
        $errors['mdp'] = "Le mot de passe est obligatoire.";
    } elseif (strlen($mdp) < 8 || !preg_match('/[A-Z]/', $mdp) || !preg_match('/[a-z]/', $mdp) || !preg_match('/[0-9]/', $mdp)) {
        $errors['mdp'] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.";
    }

    if (empty($verifmdp)) {
        $errors['verifmdp'] = "La confirmation du mot de passe est obligatoire.";
    } elseif ($mdp !== $verifmdp) {
        $errors['verifmdp'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($date)) {
        $errors['date'] = "La date de naissance est obligatoire.";
    } else {

        $date_naissance = strtotime($date);
        $aujourd_hui = time();

        $age = floor(($aujourd_hui - $date_naissance) / (365 * 24 * 60 * 60));

        if ($age < 18) {
            $errors['date'] = "Vous devez avoir au moins 18 ans.";
        }
    }

    if (empty($phone)) {
        $errors['phone'] = "Le numéro de téléphone est obligatoire.";
    } else {
        $phone_chiffres = preg_replace('/[^0-9]/', '', $phone);

        if (!preg_match('/^[\+0-9]/', $phone)) {
            $errors['phone'] = "Le téléphone doit commencer par + ou un chiffre.";
        } elseif (strlen($phone_chiffres) < 10 || strlen($phone_chiffres) > 15) {
            $errors['phone'] = "Le téléphone doit contenir entre 10 et 15 chiffres.";
        }
    }

    if (empty($pays)) {
        $errors['pays'] = "Le pays est obligatoire.";
    } elseif (!in_array($pays, $pays_autorises)) {
        $errors['pays'] = "Le pays sélectionné n'est pas valide.";
    }

    if (empty($type_participant)) {
        $errors['type_participant'] = "Le type de participant est obligatoire.";
    } elseif (!in_array($type_participant, $types_autorises)) {
        $errors['type_participant'] = "Le type de participant sélectionné n'est pas valide.";
    }

    if (empty($centres_interet)) {
        $errors['centres_interet'] = "Vous devez sélectionner au moins un centre d'intérêt.";
    } else {
        foreach ($centres_interet as $interet) {
            if (!in_array($interet, $interets_autorises)) {
                $errors['centres_interet'] = "Un ou plusieurs centres d'intérêt ne sont pas valides.";
                break;
            }
        }
    }

    if (!$conditions) {
        $errors['conditions'] = "Vous devez accepter les conditions générales d'utilisation.";
    }


    // insertion des données en BDD
    if (empty($errors)) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $centres_interet_string = implode(', ', $centres_interet);
            $password_hash = password_hash($mdp, PASSWORD_DEFAULT);
            $conditions_valide = $conditions ? 1 : 0;

            $sql = "INSERT INTO participants 
                    (nom, prenom, email, password_hash, date_naissance, telephone, pays, type_participant, centres_interet, conditions_valide, date_inscription) 
                    VALUES 
                    (:nom, :prenom, :email, :password_hash, :date_naissance, :telephone, :pays, :type_participant, :centres_interet, :conditions_valide, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':date_naissance' => $date,
                ':telephone' => $phone,
                ':pays' => $pays,
                ':type_participant' => $type_participant,
                ':centres_interet' => $centres_interet_string,
                ':conditions_valide' => $conditions_valide
            ]);

            header("Location: success.php");
            exit();
        } catch (PDOException $e) {
            $errors['database'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// Inclusion du header
include_once "header.php";
?>

<!-- Affichage des erreurs globales (si nécessaire) -->
<?php if (!empty($errors)): ?>
    <div class="container mt-3">
        <div class="alert alert-danger">
            <strong>Erreur :</strong> Veuillez corriger les erreurs ci-dessous.

            <!-- Affichage de l'erreur de base de données si elle existe -->
            <?php if (isset($errors['database'])): ?>
                <br><br><strong>Base de données :</strong> <?php echo $errors['database']; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="container">
    <form method="post">
        <fieldset>
            <legend>Formulaire</legend>
            <div>
                <label class="col-form-label mt-4" for="nom">Nom</label>
                <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>"
                    placeholder="nom" id="nom" name="nom"
                    value="<?php echo isset($values['nom']) ? htmlspecialchars($values['nom']) : ''; ?>">
                <?php if (isset($errors['nom'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['nom']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label class="col-form-label mt-4" for="prenom">Prénom</label>
                <input type="text" class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>"
                    placeholder="prenom" id="prenom" name="prenom"
                    value="<?php echo isset($values['prenom']) ? htmlspecialchars($values['prenom']) : ''; ?>">
                <?php if (isset($errors['prenom'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['prenom']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label for="email" class="form-label mt-4">Email address</label>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    id="email" name="email" placeholder="Enter email"
                    value="<?php echo isset($values['email']) ? htmlspecialchars($values['email']) : ''; ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label for="mdp" class="form-label mt-4">Mot de passe</label>
                <input type="password" class="form-control <?php echo isset($errors['mdp']) ? 'is-invalid' : ''; ?>"
                    id="mdp" name="mdp" placeholder="Password" autocomplete="off">
                <?php if (isset($errors['mdp'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['mdp']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label for="verifmdp" class="form-label mt-4">Confirmation du mot de passe</label>
                <input type="password" class="form-control <?php echo isset($errors['verifmdp']) ? 'is-invalid' : ''; ?>"
                    id="verifmdp" name="verifmdp" placeholder="Password" autocomplete="off">
                <?php if (isset($errors['verifmdp'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['verifmdp']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label class="col-form-label mt-4" for="date">Date de naissance</label>
                <input type="date" class="form-control <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>"
                    placeholder="date" id="date" name="date"
                    value="<?php echo isset($values['date']) ? htmlspecialchars($values['date']) : ''; ?>">
                <?php if (isset($errors['date'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label class="col-form-label mt-4" for="phone">Téléphone</label>
                <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                    placeholder="N° de téléphone" id="phone" name="phone"
                    value="<?php echo isset($values['phone']) ? htmlspecialchars($values['phone']) : ''; ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>


            <div>
                <label for="pays" class="form-label mt-4">Pays</label>
                <select class="form-select <?php echo isset($errors['pays']) ? 'is-invalid' : ''; ?>"
                    id="pays" name="pays">
                    <option value="">-- Sélectionnez un pays --</option>
                    <?php foreach ($pays_autorises as $p): ?>
                        <option value="<?php echo $p; ?>"
                            <?php echo (isset($values['pays']) && $values['pays'] === $p) ? 'selected' : ''; ?>>
                            <?php echo $p; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['pays'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['pays']; ?></div>
                <?php endif; ?>
            </div>

            <fieldset>
                <legend class="mt-4">Type de participant</legend>
                <?php if (isset($errors['type_participant'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['type_participant']; ?></div>
                <?php endif; ?>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type_participant"
                        id="Etudiant" value="Etudiant"
                        <?php echo (isset($values['type_participant']) && $values['type_participant'] === 'Etudiant') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Etudiant">Etudiant</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type_participant"
                        id="Professionnel" value="Professionnel"
                        <?php echo (isset($values['type_participant']) && $values['type_participant'] === 'Professionnel') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Professionnel">Professionnel</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type_participant"
                        id="Speaker" value="Speaker"
                        <?php echo (isset($values['type_participant']) && $values['type_participant'] === 'Speaker') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Speaker">Speaker</label>
                </div>
            </fieldset>


            <fieldset>
                <legend class="mt-4">Centres d'intérêt</legend>
                <?php if (isset($errors['centres_interet'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['centres_interet']; ?></div>
                <?php endif; ?>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="centres_interet[]"
                        value="PHP" id="interet_php"
                        <?php echo (isset($values['centres_interet']) && in_array('PHP', $values['centres_interet'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="interet_php">PHP</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="centres_interet[]"
                        value="JavaScript" id="interet_js"
                        <?php echo (isset($values['centres_interet']) && in_array('JavaScript', $values['centres_interet'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="interet_js">JavaScript</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="centres_interet[]"
                        value="DevOPs" id="interet_devops"
                        <?php echo (isset($values['centres_interet']) && in_array('DevOPs', $values['centres_interet'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="interet_devops">DevOPs</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="centres_interet[]"
                        value="IA" id="interet_ia"
                        <?php echo (isset($values['centres_interet']) && in_array('IA', $values['centres_interet'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="interet_ia">IA</label>
                </div>
            </fieldset>

            <fieldset>
                <legend class="mt-4">Conditions</legend>
                <div class="form-check">
                    <input class="form-check-input <?php echo isset($errors['conditions']) ? 'is-invalid' : ''; ?>"
                        type="checkbox" name="conditions" value="1" id="conditions"
                        <?php echo (isset($values['conditions']) && $values['conditions']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="conditions">
                        J'accepte les conditions générales d'utilisation
                    </label>
                    <?php if (isset($errors['conditions'])): ?>
                        <div class="invalid-feedback d-block"><?php echo $errors['conditions']; ?></div>
                    <?php endif; ?>
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary mt-3">Envoyer</button>
        </fieldset>
    </form>
</div>

<?php
include_once "footer.php";
?>