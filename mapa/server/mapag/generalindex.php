<?php
require_once __DIR__ . '/../config.php';

$sql = "
SELECT
  UPPER(TRIM(e.descripcion)) AS entidad_nombre,
  GROUP_CONCAT(
    DISTINCT UPPER(TRIM(eb.descripcion))
    ORDER BY eb.descripcion
    SEPARATOR ','
  ) AS estatuses
FROM registro r
INNER JOIN entidad     e  ON e.ID  = r.Fk_entidad
INNER JOIN estado_bus  eb ON eb.ID = r.Fk_estado_bus
LEFT  JOIN bus         b  ON b.ID  = r.Fk_bus
WHERE r.activo = 1
  AND (r.Fk_bus IS NULL OR b.activo = 1)   -- solo cuenta buses activos; permite NULL
GROUP BY e.ID, e.descripcion
";
$stmt = $pdo->query($sql);

$datos = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $estado = $row['entidad_nombre']; // 'CIUDAD DE MÉXICO' u otros con acento
  $arr = array_filter(array_map('trim', explode(',', $row['estatuses'] ?? '')));
  $arr = array_unique($arr);

  if (!$arr) continue;

  if (count($arr) === 1) {
    $datos[$estado] = $arr[0]; // IMPLEMENTADO / SIN IMPLEMENTAR / PRUEBAS (etc)
  } else {
    $datos[$estado] = in_array('PRUEBAS', $arr, true) || in_array('EN PRUEBAS', $arr, true)
      ? 'PRUEBAS'
      : 'MIXTO';
  }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($datos, JSON_UNESCAPED_UNICODE);
