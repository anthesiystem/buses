<?php
// /final/mapa/server/mapabus/conteos.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_login_or_redirect();

header('Content-Type: application/json; charset=utf-8');

$busId = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
if ($busId <= 0) {
  echo json_encode(['error' => 'bus_id inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Normaliza a MAYÚSCULAS sin acentos para empatar con CODE_TO_NAME del mapa
function norm_es($s) {
  $s = mb_strtoupper((string)$s, 'UTF-8');
  $s = strtr($s, [
    'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
    'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
  ]);
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s);
}

/*
  Regresa por ENTIDAD un estatus global para este bus:
  - IMPLEMENTADO      => si TODOS sus registros están en 'IMPLEMENTADO'
  - SIN IMPLEMENTAR   => si TODOS están en 'SIN IMPLEMENTAR'
  - PRUEBAS (u otro)  => si hay mezcla de estados
*/
$sql = "
  SELECT
    e.ID                        AS entidad_id,
    e.descripcion               AS entidad,
    SUM(CASE WHEN eb.descripcion = 'IMPLEMENTADO'      THEN 1 ELSE 0 END) AS cnt_impl,
    SUM(CASE WHEN eb.descripcion = 'SIN IMPLEMENTAR'   THEN 1 ELSE 0 END) AS cnt_sin,
    COUNT(*)                    AS total
  FROM registro r
  JOIN entidad e     ON e.ID = r.Fk_entidad       AND e.activo = 1
  LEFT JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
  WHERE r.activo = 1 AND r.Fk_bus = :bus
  GROUP BY e.ID, e.descripcion
";
$st = $pdo->prepare($sql);
$st->execute([':bus' => $busId]);

$out = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  $total   = (int)$row['total'];
  $nImpl   = (int)$row['cnt_impl'];
  $nSin    = (int)$row['cnt_sin'];
  $estatus = 'PRUEBAS';

  if ($total > 0) {
    if ($nImpl === $total)      $estatus = 'IMPLEMENTADO';
    elseif ($nSin === $total)   $estatus = 'SIN IMPLEMENTAR';
    else                        $estatus = 'PRUEBAS';
  }

  $clave = norm_es($row['entidad']); // p.ej. "CIUDAD DE MEXICO"
  $out[$clave] = [
    'estatus'          => $estatus,
    'total'            => $total,
    'implementado'     => $nImpl,
    'sin_implementar'  => $nSin
  ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
