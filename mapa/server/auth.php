<?php
// /final/mapa/server/auth.php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  session_set_cookie_params([
    'lifetime' => 3600,       // 1 hora
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

// URL absoluta del login (ajústala si es otra)
const LOGIN_URL = '/final/mapa/public/login.php';

function is_logged_in() {
  return isset($_SESSION['usuario']) && isset($_SESSION['usuario']['ID']);
}

function estaAutenticado() {
  return is_logged_in();
}

function require_login_or_redirect() {
  // Inactividad (1h)
  if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 3600)) {
    // limpiar sesión
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    header("Location: " . LOGIN_URL);
    exit;
  }

  if (!is_logged_in()) {
    header("Location: " . LOGIN_URL);
    exit;
  }
  // refresca actividad
  $_SESSION['ultima_actividad'] = time();
}
