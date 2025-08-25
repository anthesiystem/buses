<?php
declare(strict_types=1);
/************************************************************
 * API Dashboard (JSON robusto)
 ************************************************************/
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0'); // evita que avisos rompan el JSON

require_once __DIR__ . '/../../../server/config.php';

try {
  // --- Filtros ---
  $entidades = isset($_GET['entidades']) && trim($_GET['entidades']) !== ''
    ? array_values(array_filter(array_map('intval', explode(',', $_GET['entidades']))))
    : [];
  $busId = (isset($_GET['bus_id']) && $_GET['bus_id'] !== '') ? (int)$_GET['bus_id'] : null;
  $desde = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null;
  $hasta = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null;

  $reFecha = '/^\d{4}-\d{2}-\d{2}$/';
  if ($desde && !preg_match($reFecha, $desde)) $desde = null;
  if ($hasta && !preg_match($reFecha, $hasta)) $hasta = null;

  // --- WHERE dinámico ---
  $where = ["r.activo = 1"];
  $params = [];

  if (!empty($entidades)) {
    $in = implode(',', array_fill(0, count($entidades), '?'));
    $where[] = "r.Fk_entidad IN ($in)";
    array_push($params, ...$entidades);
  }
  if ($busId) {
    $where[] = "r.Fk_bus = ?";
    $params[] = $busId;
  }
  if ($desde) { $where[] = "DATE(r.fecha_creacion) >= ?"; $params[] = $desde; }
  if ($hasta) { $where[] = "DATE(r.fecha_creacion) <= ?"; $params[] = $hasta; }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // --- Mapeo de estatus (ajusta descripciones si difieren) ---
  $CASE_CONC = "CASE WHEN UPPER(eb.descripcion) IN ('IMPLEMENTADO','CONCLUIDO') THEN 1 ELSE 0 END";
  $CASE_PRUE = "CASE WHEN UPPER(eb.descripcion) LIKE 'PRUEB%' OR UPPER(eb.descripcion) = 'EN PRUEBAS' THEN 1 ELSE 0 END";
  $CASE_SIN  = "CASE WHEN UPPER(eb.descripcion) IN ('SIN IMPLEMENTAR','SIN EJECUTAR') THEN 1 ELSE 0 END";

  // Avance derivado 0-100 según estatus (sin depender de r.avance)
  $AVANCE_DER = "
    CASE
      WHEN UPPER(eb.descripcion) IN ('IMPLEMENTADO','CONCLUIDO') THEN 100
      WHEN UPPER(eb.descripcion) LIKE 'PRUEB%' OR UPPER(eb.descripcion) = 'EN PRUEBAS' THEN 50
      ELSE 0
    END
  ";

  // --- Query por entidad ---
  $sql = "
    SELECT
      e.ID                                   AS entidad_id,
      e.descripcion                          AS entidad,
      SUM($CASE_CONC)                        AS concluidos,
      SUM($CASE_PRUE)                        AS pruebas,
      SUM($CASE_SIN)                         AS sin_ejecutar,
      COUNT(*)                               AS total,
      AVG($AVANCE_DER)                       AS avance_promedio -- 0-100
    FROM registro r
    INNER JOIN entidad e     ON e.ID = r.Fk_entidad
    INNER JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
    LEFT  JOIN bus b         ON b.ID = r.Fk_bus
    $whereSql
    GROUP BY e.ID, e.descripcion
    ORDER BY e.descripcion
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // --- KPIs globales ---
  $totCon = $totPr = $totSin = $totAll = 0;
  $sumAv  = 0.0; $nAv = 0;

  foreach ($rows as $r) {
    $c = (int)$r['concluidos'];
    $p = (int)$r['pruebas'];
    $s = (int)$r['sin_ejecutar'];
    $t = (int)$r['total'];
    $totCon += $c; $totPr += $p; $totSin += $s; $totAll += $t;

    if ($r['avance_promedio'] !== null) { $sumAv += (float)$r['avance_promedio']; $nAv++; }
  }
  $avgGlobal = $nAv ? ($sumAv / $nAv) : null;

  // --- Respuesta ---
  echo json_encode([
    'kpi' => [
      'concluidos' => $totCon,
      'pruebas'    => $totPr,
      'sin_ejecutar' => $totSin,
      'avance_promedio_global' => $avgGlobal, // 0-100
    ],
    'entities' => array_map(function ($r) {
      return [
        'entidad_id'      => (int)$r['entidad_id'],
        'entidad'         => $r['entidad'],
        'concluidos'      => (int)$r['concluidos'],
        'pruebas'         => (int)$r['pruebas'],
        'sin_ejecutar'    => (int)$r['sin_ejecutar'],
        'total'           => (int)$r['total'],
        'avance_promedio' => $r['avance_promedio'] !== null ? round((float)$r['avance_promedio'], 1) : null
      ];
    }, $rows)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'No se pudo generar el dashboard.',
    'details' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
