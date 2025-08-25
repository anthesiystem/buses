<?php
// /final/mapa/public/server/mapabus/detalle.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_login_or_redirect();

header('Content-Type: application/json; charset=utf-8');

$busId     = (int)($_GET['bus'] ?? $_GET['bus_id'] ?? 0);
$entidadId = (int)($_GET['entidad'] ?? $_GET['entidad_id'] ?? 0);
if ($busId <= 0 || $entidadId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Parámetros inválidos']); exit; }

// === SESIÓN robusta (toma nivel de sesión o BD; admin >=3) ===
$usuarioId = (int)($_SESSION['user_id'] ?? ($_SESSION['usuario']['ID'] ?? 0));
$nivel = (int)($_SESSION['nivel'] ?? ($_SESSION['usuario']['nivel'] ?? 0));
if ($usuarioId && $nivel === 0) {
  $q = $pdo->prepare("SELECT cuenta, nivel FROM usuario WHERE ID = :id LIMIT 1");
  $q->execute([':id'=>$usuarioId]);
  if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    if (strtolower((string)$row['cuenta']) === 'admin') $nivel = max($nivel, 4);
    else $nivel = (int)($row['nivel'] ?? 0);
  }
}
if ($nivel >= 3) {
  $permitidas = array_map('intval', $pdo->query("SELECT ID FROM entidad WHERE activo=1")->fetchAll(PDO::FETCH_COLUMN));
} else {
  $modId = 9;
  try {
    $stm = $pdo->query("SELECT ID FROM modulo WHERE descripcion='mapa_bus' LIMIT 1");
    if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $modId = (int)$r['ID'];
  } catch (\Throwable $e) {}
  $st = $pdo->prepare("SELECT FK_entidad FROM permiso_usuario
                       WHERE Fk_usuario=:u AND Fk_modulo=:m AND activo=1
                         AND (accion IS NULL OR accion='READ')
                         AND (FK_bus IS NULL OR FK_bus=0 OR FK_bus=:b)");
  $st->execute([':u'=>$usuarioId, ':m'=>$modId, ':b'=>$busId]);
  $ids=[]; $todas=false;
  foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
    $raw = $p['FK_entidad'];
    if ($raw === null) { $todas=true; break; }
    $tok = trim((string)$raw); $up=strtoupper($tok);
    if ($tok==='0'||$tok==='*'||$up==='ALL'||$up==='TODAS'){ $todas=true; break; }
    foreach (preg_split('/\s*,\s*/', $tok,-1,PREG_SPLIT_NO_EMPTY) as $t) if (ctype_digit($t)) $ids[]=(int)$t;
  }
  $permitidas = $todas ? array_map('intval', $pdo->query("SELECT ID FROM entidad WHERE activo=1")->fetchAll(PDO::FETCH_COLUMN))
                       : array_values(array_unique($ids));
}
if (!in_array($entidadId, $permitidas, true)) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Sin permiso']); exit; }

// === Consulta según tu esquema ===
try {
  $sql = "
    SELECT
      r.ID,
      d.descripcion  AS dependencia,
      e.descripcion  AS entidad,
      b.descripcion  AS bus,
      en.descripcion AS engine,
      t.descripcion  AS tecnologia,
      eb.descripcion AS estatus,
      c.descripcion  AS categoria,
      et.descripcion AS etapa,
      DATE_FORMAT(r.fecha_inicio, '%Y-%m-%d')    AS fecha_inicio,
      DATE_FORMAT(r.fecha_migracion, '%Y-%m-%d') AS fecha_migracion,
      r.Fk_entidad   AS entidad_id
    FROM registro r
    INNER JOIN dependencia d   ON d.ID  = r.Fk_dependencia
    INNER JOIN entidad e       ON e.ID  = r.Fk_entidad
    LEFT  JOIN bus b           ON b.ID  = r.Fk_bus
    INNER JOIN motor_base en   ON en.ID = r.Fk_motor_base
    INNER JOIN tecnologia t    ON t.ID  = r.Fk_tecnologia
    INNER JOIN categoria c     ON c.ID  = r.Fk_categoria
    INNER JOIN estado_bus eb   ON eb.ID = r.Fk_estado_bus
    LEFT  JOIN etapa et        ON et.ID = r.Fk_etapa
    WHERE r.activo = 1
      AND r.Fk_entidad = :ent
      AND (r.Fk_bus = :bus OR r.Fk_bus IS NULL)
    ORDER BY r.fecha_creacion DESC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':bus'=>$busId, ':ent'=>$entidadId]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'rows'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'DB','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
