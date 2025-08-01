<?php
session_start();
require_once __DIR__ . '/../server/config.php';

if (isset($_SESSION['usuario_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO sesion (Fk_User, Tipo_Evento) VALUES (?, 'LOGOUT')");
        $stmt->execute([$_SESSION['usuario_id']]);
    } catch (Exception $e) {
        // log error opcional
    }
}

// Limpiar sesión y cookies
$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Borrar cookies personalizadas
setcookie("usuario", "", time() - 3600, "/");
setcookie("usuario_id", "", time() - 3600, "/");
setcookie("fk_perfiles", "", time() - 3600, "/");

header("Location: login.php?logout=1");
exit;
