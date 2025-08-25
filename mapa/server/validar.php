<?php
// /final/mapa/server/validar.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/acl.php';

if (!isset($_POST['usuario'], $_POST['password'])) {
  header("Location: ../public/login.php?error=2"); exit;
}

$usuario  = trim($_POST['usuario']);
$password = (string)$_POST['password'];

try {
  $sql = "SELECT u.ID, u.cuenta, u.contrasenia, u.nivel, CAST(u.activo AS UNSIGNED) AS activo,
                 p.nombre, p.apaterno, p.amaterno
          FROM usuario u
          INNER JOIN persona p ON u.Fk_persona = p.ID
          WHERE u.cuenta = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || (int)$row['activo'] !== 1) {
    header("Location: ../public/login.php?error=1"); exit;
  }

  $hash = (string)$row['contrasenia'];
  $ok   = password_verify($password, $hash);

  // Compatibilidad con cuentas antiguas (texto plano)
  if (!$ok && trim($password) === trim($hash)) {
    $ok = true;
    // (Opcional) actualizar de inmediato a hash
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    $up = $pdo->prepare("UPDATE usuario SET contrasenia=?, fecha_modificacion=NOW() WHERE ID=?");
    $up->execute([$newHash, (int)$row['ID']]);
  }

  if (!$ok) {
    header("Location: ../public/login.php?error=1"); exit;
  }

  // ---- SESIÓN ----
  $usuarioSesion = [
    'ID'      => (int)$row['ID'],
    'cuenta'  => $row['cuenta'],
    'nivel'   => (int)$row['nivel'],
    'nombre'  => $row['nombre'],
    'apaterno'=> $row['apaterno'],
    'amaterno'=> $row['amaterno'],
  ];
  $_SESSION['usuario'] = $usuarioSesion;
  // Compatibilidad con llaves antiguas
  $_SESSION['usuario_id']     = $usuarioSesion['ID'];
  $_SESSION['fk_perfiles']    = $usuarioSesion['nivel'];
  $_SESSION['nombre_completo']= $row['nombre'].' '.$row['apaterno'].' '.$row['amaterno'];
  $_SESSION['ultima_actividad']= time();

  // ---- ACL en sesión ----
$_SESSION['acl'] = acl_build_from_db($_SESSION['usuario']['ID'], $_SESSION['usuario']['nivel']);



  // ---- LOG de login ----
  $stmtLog = $pdo->prepare("INSERT INTO sesion (Fk_usuario, Tipo_evento) VALUES (?, 'LOGIN')");
  $stmtLog->execute([$usuarioSesion['ID']]);

  // Redirige al dashboard
  header("Location: ../public/index.php");
  exit;

} catch (PDOException $e) {
  echo "❌ Error de conexión o consulta: " . htmlspecialchars($e->getMessage());
  exit;
}
