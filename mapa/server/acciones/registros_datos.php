<?php
require_once '../../server/config.php';
session_start();

header('Content-Type: application/json');

// Construir WHERE dinámico
$where = [];
$params = [];

$input = $_POST; // o $_REQUEST si quieres aceptar ambos


if (!empty($input['entidad'])) {
  $where[] = 'r.Fk_entidad = ?';
  $params[] = $input['entidad'];
}
if (!empty($input['dependencia'])) {
  $where[] = 'r.Fk_dependencia = ?';
  $params[] = $input['dependencia'];
}
if (!empty($input['bus'])) {
  $where[] = 'r.Fk_bus = ?';
  $params[] = $input['bus'];
}
if (!empty($input['engine'])) {
  $where[] = 'r.Fk_engine = ?';
  $params[] = $input['engine'];
}
if (!empty($input['version'])) {
  $where[] = 'r.Fk_version = ?';
  $params[] = $input['version'];
}
if (!empty($input['categoria'])) {
  $where[] = 'r.Fk_categoria = ?';
  $params[] = $input['categoria'];
}
if (!empty($input['estatus'])) {
  $where[] = 'r.Fk_estado_bus = ?';
  $params[] = $input['estatus'];
}
if (!empty($input['fecha_inicio'])) {
  $where[] = 'r.fecha_inicio >= ?';
  $params[] = $input['fecha_inicio'];
}
if (!empty($input['fecha_migracion'])) {
  $where[] = 'r.fecha_migracion >= ?';
  $params[] = $input['fecha_migracion'];
}



$where[] = 'r.activo = 1';
$sqlWhere = 'WHERE ' . implode(' AND ', $where);

// Consulta principal
$sql = "
SELECT r.*, 
    d.descripcion AS Dependencia,
    e.descripcion AS Entidad,
    b.descripcion AS Bus,
    en.descripcion AS Engine,
    v.descripcion AS Version,
    c.descripcion AS Categoria,
    eb.descripcion AS Estado,
    r.fecha_inicio AS Inicio,
    r.fecha_migracion AS Migracion
FROM registro r
INNER JOIN dependencia d ON d.ID = r.Fk_dependencia
INNER JOIN entidad e ON e.ID = r.Fk_entidad
LEFT JOIN bus b ON b.ID = r.Fk_bus
INNER JOIN engine en ON en.ID = r.Fk_engine
INNER JOIN version v ON v.ID = r.Fk_version
INNER JOIN categoria c ON c.ID = r.Fk_categoria
INNER JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
  $sqlWhere
  ORDER BY r.fecha_creacion DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $registros]);
