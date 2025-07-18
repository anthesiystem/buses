<?php
ini_set('session.gc_maxlifetime', 3600); // 3600 seg = 1 hora de vida máxima por inactividad
session_set_cookie_params(1800);    // 30 minutos para la cookie
session_start();

// Restaura sesión desde la cookie
if(!isset($_SESSION['usuario']) && isset($_COOKIE['usuario'])) {
    $_SESSION['usuario'] = $_COOKIE['usuario'];
    $_SESSION['nivel'] = $_COOKIE['nivel'];
    $_SESSION['ultima_actividad'] = time();
}


// Verificar la sesión activa
if(!isset($_SESSION['usuario'])) {
    header("Location: login.php"); // ajusta la ruta si es necesario
    exit();
}

// Verificar el tiempo de inactividad. Más de 1 hora inactivo se cierra la sesión
if(!isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 3600)) {
    session_unset();
    session_destroy();

    setcookie("usuario", "", time() - 3600, "/");
    setcookie("nivel", "", time() - 3600, "/");
    
    header("Location: ../public/login.php");
    exit();
}

$_SESSION['ultima_actividad'] = time(); // Actualizar el tiempo de la última actividad
?>