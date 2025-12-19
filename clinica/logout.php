<?php
session_start();

// Remove todas as variáveis
session_unset();

// Destroi a sessão
session_destroy();

// Remove o cookie da sessão (boa prática)
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

// Redireciona
header("Location: login.php");
exit;
?>