<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hora de inactividad
session_set_cookie_params(1800);         // 30 minutos para la cookie
session_start();

// Restaurar sesión si hay cookie (puedes eliminar esta sección si no usas cookies manuales)
// Ejemplo simple (requiere token firmado en cookie)
if (!isset($_SESSION['usuario']) && isset($_COOKIE['usuario'])) {
    // Validar token o volver a consultar al usuario
    $stmt = $pdo->prepare("SELECT ID, cuenta FROM usuario WHERE cuenta = ? AND activo = 1");
    $stmt->execute([$_COOKIE['usuario']]);
    $row = $stmt->fetch();

    if ($row && $row['ID'] == $_COOKIE['usuario_id']) {
        $_SESSION['usuario'] = $row['cuenta'];
        $_SESSION['usuario_id'] = $row['ID'];
        $_SESSION['ultima_actividad'] = time();
    }
}


// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Verificar tiempo de inactividad
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 3600)) {
    session_unset();
    session_destroy();

    setcookie("usuario", "", time() - 3600, "/");
    setcookie("usuario_id", "", time() - 3600, "/");

    header("Location: ../public/login.php");
    exit();
}

// Actualizar tiempo de actividad
$_SESSION['ultima_actividad'] = time();
?>
