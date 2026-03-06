<?php
/**
 * UniMarket - Connexion Expert & Gestion de Session
 */
require_once 'config.php'; // Initialise session_start() et $pdo

// Si l'utilisateur est déjà connecté, on le redirige vers son dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // 1. Recherche de l'utilisateur par Email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Vérification du mot de passe (Hash BCrypt)
        if ($user && password_verify($password, $user['password'])) {
            
            // 3. Vérification de l'activation du compte (Étape OTP passée ?)
            if ($user['is_verified'] == 0) {
                $_SESSION['temp_email'] = $user['email'];
                header('Location: verify.php?msg=not_verified');
                exit;
            }

            // 4. Succès : Création de la Session (Identité de l'utilisateur)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // 5. Redirection intelligente (Vers le dashboard ou la page précédente)
            header('Location: dashboard.php');
            exit;

        } else {
            $error = "Email ou mot de passe incorrect.";
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
    <title>Connexion | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 60px auto; padding: 20px; }
        .login-card { background: white; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg); }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group i { position: absolute; left: 15px; top: 42px; color: var(--gray); }
        .form-group input { width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #f1f5f9; border-radius: 12px; }
        .btn-login { width: 100%; background: var(--primary); color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .forgot-link { display: block; text-align: right; margin-top: 10px; font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body style="background: #f8fafc;">

    <div class="login-container">
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="index.php" style="font-size: 2rem; font-weight: 800; color: var(--primary); text-decoration: none;">UniMarket</a>
            <p style="color: var(--gray); margin-top: 10px;">Ravi de vous revoir !</p>
        </div>

        <div class="login-card">
            <?php if ($error): ?>
                <div style="color: #dc2626; background: #fee2e2; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'verified'): ?>
                <div style="color: #16a34a; background: #dcfce7; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; text-align: center;">
                    <i class="fa-solid fa-circle-check"></i> Compte activé ! Connectez-vous.
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem;">Email</label>
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem;">Mot de passe</label>
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="••••••••" required>
                    <a href="forgot_password.php" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-login">Se connecter</button>
            </form>
        </div>

        <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--gray);">
            Nouveau sur UniMarket ? <a href="register.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Créer un compte</a>
        </p>
    </div>

</body>
</html>