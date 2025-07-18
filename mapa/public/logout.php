<?php
session_start();

// registrar LOGOUT antes de destruir la sesión
if (isset($_SESSION['usuario_id'])) {
    $conn = new mysqli('localhost', 'admin', 'admin1234', 'busmap');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("INSERT INTO logsesion (Fk_Id_User, Tipo_Evento) VALUES (?, 'LOGOUT')");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}

// borrar todas las variables de sesión
$_SESSION = [];

// destruir la sesión
session_destroy();

// invalidar la cookie de sesión
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

// redirigir
header("Location: login.php");
exit;
?>
