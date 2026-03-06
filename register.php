<?php
/**
 * UniMarket - Inscription Expert avec Système OTP
 */
require_once 'config.php'; // Initialise session_start() et $pdo

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération et Nettoyage (Sanitization)
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $whatsapp = htmlspecialchars(trim($_POST['whatsapp']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 2. Validations de sécurité
    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // 3. Vérifier si l'utilisateur existe déjà (Éviter les doublons)
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR whatsapp_phone = ?");
        $check->execute([$email, $whatsapp]);
        
        if ($check->fetch()) {
            $error = "Cet email ou ce numéro WhatsApp est déjà utilisé.";
        } else {
            // 4. Hachage et Génération du code OTP
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $otp_code = rand(100000, 999999); // Code à 6 chiffres

            try {
                // 5. Insertion (Compte inactif par défaut : is_verified = 0)
                $sql = "INSERT INTO users (fullname, email, whatsapp_phone, password, verification_code, is_verified) 
                        VALUES (?, ?, ?, ?, ?, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fullname, $email, $whatsapp, $hashedPassword, $otp_code]);

                // 6. Préparation de la session pour la vérification
                $_SESSION['temp_email'] = $email;
                
                // Ici, tu intégrerais l'envoi réel (mail() ou API WhatsApp)
                // Pour le test, on peut afficher le code ou l'imaginer envoyé.
                
                header('Location: verify.php');
                exit;

            } catch (PDOException $e) {
                $error = "Erreur système : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container { max-width: 450px; margin: 50px auto; padding: 20px; }
        .auth-card { background: white; padding: 30px; border-radius: 24px; box-shadow: var(--shadow-md); }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #f1f5f9; border-radius: 12px; transition: 0.3s; }
        .form-group input:focus { border-color: var(--primary); outline: none; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; }
        .btn-auth { width: 100%; background: var(--primary); color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-auth:hover { background: #1e40af; transform: translateY(-2px); }
    </style>
</head>
<body style="background: #f8fafc;">

    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" style="text-decoration: none; color: var(--primary); font-weight: 800; font-size: 1.5rem;">UniMarket</a>
            <p style="color: var(--gray); margin-top: 10px;">Rejoins la communauté étudiante.</p>
        </div>

        <div class="auth-card">
            <?php if ($error): ?>
                <div class="error-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="fullname" placeholder="Ex: Sidoine BOSSOU" required>
                </div>

                <div class="form-group">
                    <label>Email Universitaire (ou personnel)</label>
                    <input type="email" name="email" placeholder="etudiant@uac.bj" required>
                </div>

                <div class="form-group">
                    <label>Numéro WhatsApp</label>
                    <input type="tel" name="whatsapp" placeholder="Ex: 229XXXXXXXX" required>
                </div>

                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-auth">S'inscrire</button>
            </form>

            <p style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--gray);">
                Déjà un compte ? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Se connecter</a>
            </p>
        </div>
    </div>

</body>
</html>