<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$catalogo = $_GET['catalogo'] ?? '';
if ($catalogo === 'personas') {
  $rs = $pdo->query("SELECT ID, CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre_completo FROM persona WHERE activo=1 ORDER BY nombre, apaterno");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}
if ($catalogo === 'usuarios') {
  $rs = $pdo->query("SELECT u.ID, u.cuenta FROM usuario u WHERE u.activo=1 ORDER BY u.cuenta");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT u.ID, u.cuenta, u.nivel, u.Fk_persona, u.activo,
                CAST(u.activo AS UNSIGNED) AS activo,
               CONCAT(p.nombre,' ',p.apaterno,' ',p.amaterno) AS persona
        FROM usuario u
        JOIN persona p ON p.ID=u.Fk_persona
        WHERE 1";
$params=[];
if ($buscar!==''){
  $sql.=" AND (u.cuenta LIKE ? OR p.nombre LIKE ? OR p.apaterno LIKE ? OR p.amaterno LIKE ?)";
  for($i=0;$i<4;$i++) $params[]="%$buscar%";
}
$sql.=" ORDER BY u.fecha_creacion DESC";

$stmt=$pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
