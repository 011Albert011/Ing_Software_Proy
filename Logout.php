<?php
session_start();

// Limpiamos todas las variables superglobales de la sesión en memoria
$_SESSION = array();

// También se debe borrar la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destruimos la sesión en el servidor
session_destroy();

header("Location: index.php");
exit();
?>