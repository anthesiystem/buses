<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$catalogo = $_GET['catalogo'] ?? '';
if ($catalogo === 'dependencia') {
  $rs = $pdo->query("SELECT ID, descripcion FROM dependencia WHERE activo=1 ORDER BY descripcion");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}
if ($catalogo === 'entidad') {
  $rs = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo=1 ORDER BY descripcion");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT p.ID, p.nombre, p.apaterno, p.amaterno, p.numero_empleado, p.correo, 
               p.Fk_dependencia, p.Fk_entidad, p.activo,
               d.descripcion AS dependencia, e.descripcion AS entidad
        FROM persona p
        JOIN dependencia d ON d.ID=p.Fk_dependencia
        JOIN entidad e ON e.ID=p.Fk_entidad
        WHERE 1";
$params = [];
if ($buscar !== '') {
  $sql .= " AND (p.nombre LIKE ? OR p.apaterno LIKE ? OR p.amaterno LIKE ? OR p.correo LIKE ? OR p.numero_empleado LIKE ?)";
  for ($i=0;$i<5;$i++) $params[] = "%$buscar%";
}
$sql .= " ORDER BY p.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $r['nombre_completo'] = trim($r['nombre'].' '.$r['apaterno'].' '.$r['amaterno']);
  $data[] = $r;
}
echo json_encode($data);
