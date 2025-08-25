<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$catalogo = $_GET['catalogo'] ?? '';
if ($catalogo==='modulo'){
  $rs=$pdo->query("SELECT ID, descripcion FROM modulo WHERE activo=1 ORDER BY descripcion");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}
if ($catalogo==='bus'){
  $rs=$pdo->query("SELECT ID, descripcion FROM bus WHERE activo=1 ORDER BY descripcion");
  echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC)); exit;
}

$u  = $_GET['usuario'] ?? '';
$m  = $_GET['modulo']  ?? '';
$e  = $_GET['entidad'] ?? '';
$b  = $_GET['bus']     ?? '';

$sql = "SELECT pu.ID, pu.Fk_usuario, pu.Fk_modulo, pu.FK_entidad, pu.FK_bus, pu.accion, pu.activo,
                CAST(pu.activo AS UNSIGNED) AS activo,
               u.cuenta AS usuario, mo.descripcion AS modulo, en.descripcion AS entidad, bu.descripcion AS bus
        FROM permiso_usuario pu
        JOIN usuario u ON u.ID=pu.Fk_usuario
        JOIN modulo  mo ON mo.ID=pu.Fk_modulo
        LEFT JOIN entidad en ON en.ID=pu.FK_entidad
        LEFT JOIN bus     bu ON bu.ID=pu.FK_bus
        WHERE 1";
$p=[];

if ($u!==''){ $sql.=" AND pu.Fk_usuario=?"; $p[]=(int)$u; }
if ($m!==''){ $sql.=" AND pu.Fk_modulo=?";  $p[]=(int)$m; }
if ($e!==''){ $sql.=" AND pu.FK_entidad=?"; $p[]=(int)$e; }
if ($b!==''){ $sql.=" AND pu.FK_bus=?";     $p[]=(int)$b; }

$sql.=" ORDER BY pu.ID DESC";
$stmt=$pdo->prepare($sql);
$stmt->execute($p);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
