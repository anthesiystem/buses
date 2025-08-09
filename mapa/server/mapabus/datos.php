<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

$busID = $_GET['bus_id'] ?? null;
if (!$busID || !is_numeric($busID)) {
  echo json_encode([]); 
  exit;
}

try {
  $sql = "
      SELECT 
          UPPER(e.descripcion) AS entidad_descripcion,
          GROUP_CONCAT(DISTINCT UPPER(eb.descripcion)) AS estatuses
      FROM registro r
      INNER JOIN entidad e     ON e.Id = r.Fk_entidad
      INNER JOIN estado_bus eb ON eb.Id = r.Fk_estado_bus
      WHERE r.Fk_bus = :busID
      GROUP BY e.descripcion
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':busID' => $busID]);

  $datos = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $estado = $row['entidad_descripcion']; // MAYÚSCULAS
      $estatuses = $row['estatuses'] !== null ? array_map('trim', explode(",", $row['estatuses'])) : [];
      $estatusesUnicos = array_unique($estatuses);

      if (count($estatusesUnicos) === 1) {
          $estatusFinal = $estatusesUnicos[0];
      } else {
          $estatusFinal = in_array('PRUEBAS', $estatusesUnicos) ? 'PRUEBAS' : 'MIXTO';
      }

      $datos[$estado] = [
          'estatus' => $estatusFinal
      ];
  }

  echo json_encode($datos);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db']);
}
