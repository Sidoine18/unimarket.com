<?php
/**
 * UniMarket - Activation de compte via OTP
 */
require_once 'config.php'; // Initialise session_start() et $pdo

// 1. Sécurité : Si aucun email n'est en session, on redirige vers l'inscription
if (!isset($_SESSION['temp_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['temp_email'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Récupération du code (Nettoyage pour éviter les espaces inutiles)
    $otp_input = trim($_POST['otp_code']);

    try {
        // 3. Vérification du code dans la base de données
        $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->execute([$email, $otp_input]);
        $user = $stmt->fetch();

        if ($user) {
            // 4. Succès : Activation du compte et nettoyage du code
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
            $update->execute([$user['id']]);

            // 5. Connexion automatique immédiate (Expérience Utilisateur Fluide)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            unset($_SESSION['temp_email']); // On nettoie la session temporaire

            header('Location: dashboard.php?msg=welcome');
            exit;
        } else {
            $error = "Code invalide. Veuillez vérifier vos emails ou spam.";
        }
    } catch (PDOException $e) {
        $error = "Erreur système : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du compte | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .verify-container { max-width: 400px; margin: 80px auto; padding: 20px; text-align: center; }
        .verify-card { background: white; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg); }
        .icon-box { width: 70px; height: 70px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.8rem; }
        .otp-input { 
            width: 100%; 
            padding: 15px; 
            font-size: 2rem; 
            text-align: center; 
            letter-spacing: 8px; 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            margin: 20px 0;
            font-weight: 800;
            color: var(--primary);
        }
        .otp-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px var(--primary-soft); }
        .resend-link { display: block; margin-top: 20px; font-size: 0.85rem; color: var(--gray); text-decoration: none; }
        .resend-link strong { color: var(--primary); cursor: pointer; }
    </style>
</head>
<body style="background: #f8fafc;">

    <div class="verify-container">
        <div class="verify-card">
            <div class="icon-box"><i class="fa-solid fa-envelope-open-text"></i></div>
            <h2>Vérifie ton compte</h2>
            <p style="color: var(--gray); font-size: 0.9rem; margin-top: 10px;">
                Saisis le code à 6 chiffres envoyé à <br><strong><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <?php if ($error): ?>
                <div style="color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 8px; margin-top: 15px; font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="verify.php" method="POST">
                <input type="text" name="otp_code" class="otp-input" placeholder="000000" maxlength="6" required pattern="[0-9]{6}" inputmode="numeric">
                <button type="submit" class="btn-auth" style="width:100%; padding: 16px;">Activer mon compte</button>
            </form>

            <a href="#" class="resend-link">Tu n'as rien reçu ? <strong>Renvoyer le code</strong></a>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.85rem;">
            <a href="register.php" style="color: var(--gray); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Retour à l'inscription</a>
        </p>
    </div>

</body>
</html>