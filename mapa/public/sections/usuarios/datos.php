<?php
// /final/mapa/public/sections/usuarios/datos.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../server/config.php';
// Si tu login está listo, habilita:
// require_once __DIR__ . '/../../../server/auth.php';
// require_login_or_redirect();

function jerr($msg, $code = 500) {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // ------- PERSONAS -------
  $sql = "
    SELECT p.ID, p.nombre, p.apaterno, p.amaterno, p.numero_empleado, p.correo,
           p.Fk_dependencia, COALESCE(d.descripcion,'') AS dependencia,
           p.Fk_entidad, COALESCE(e.descripcion,'') AS entidad,
           p.activo
    FROM persona p
    LEFT JOIN dependencia d ON d.ID = p.Fk_dependencia
    LEFT JOIN entidad    e ON e.ID = p.Fk_entidad
    ORDER BY p.ID DESC
  ";
  $personas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ------- USUARIOS -------
  $sql = "
    SELECT u.ID, u.cuenta, u.nivel, u.Fk_persona, u.activo,
           CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apaterno,''),' ',COALESCE(p.amaterno,'')) AS persona
    FROM usuario u
    LEFT JOIN persona p ON p.ID = u.Fk_persona
    ORDER BY u.ID DESC
  ";
  $usuarios = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ------- MÓDULOS -------
  $sql = "SELECT m.ID, m.descripcion, m.activo FROM modulo m ORDER BY m.ID DESC";
  $modulos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ------- PERMISOS -------
  $sql = "
    SELECT pu.ID, pu.Fk_usuario, COALESCE(u.cuenta,'') AS usuario,
           pu.Fk_modulo, COALESCE(m.descripcion,'') AS modulo,
           pu.Fk_entidad, COALESCE(e.descripcion,'') AS entidad,
           pu.Fk_bus, COALESCE(b.descripcion,'') AS bus,
           pu.accion, pu.activo
    FROM permiso_usuario pu
    LEFT JOIN usuario u  ON u.ID = pu.Fk_usuario
    LEFT JOIN modulo m   ON m.ID = pu.Fk_modulo
    LEFT JOIN entidad e  ON e.ID = pu.Fk_entidad
    LEFT JOIN bus b      ON b.ID = pu.Fk_bus
    ORDER BY pu.ID DESC
  ";
  $permisos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ------- SELECTS (catálogos para combos) -------
  $deps   = $pdo->query("SELECT ID, descripcion FROM dependencia WHERE 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
  $ents   = $pdo->query("SELECT ID, descripcion FROM entidad     WHERE 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
  $buses  = $pdo->query("SELECT ID, descripcion FROM bus         WHERE 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
  $users  = $pdo->query("SELECT ID, cuenta FROM usuario          WHERE 1 ORDER BY cuenta")->fetchAll(PDO::FETCH_ASSOC);
  $mods   = $pdo->query("SELECT ID, descripcion FROM modulo      WHERE 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
  $pers   = $pdo->query("SELECT ID, CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre FROM persona ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'personas' => $personas,
    'usuarios' => $usuarios,
    'modulos'  => $modulos,
    'permisos' => $permisos,
    'selects'  => [
      'dependencias' => $deps,
      'entidades'    => $ents,
      'buses'        => $buses,
      'usuarios'     => $users,
      'modulos'      => $mods,
      'personas'     => $pers,
    ],
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  jerr('Error al consultar datos: '.$e->getMessage());
}
