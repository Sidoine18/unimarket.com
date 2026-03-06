<?php
/**
 * UniMarket - Script de déconnexion sécurisé
 * @author Expert Dev
 */

// 1. Initialiser la session pour pouvoir la manipuler
session_start();

// 2. Vider toutes les variables de session ($_SESSION['user_id'], etc.)
$_SESSION = array();

// 3. Détruire le cookie de session côté navigateur (Action Expert)
// Cela garantit que l'identifiant de session ne peut pas être réutilisé.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Détruire la session sur le serveur
session_destroy();

// 5. Redirection avec un message de confirmation
// On ajoute un paramètre 'msg' pour afficher un petit toast de succès sur l'index
header("Location: index.php?msg=logout_success");
exit;