<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$id = (int)($_POST['ID'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $pdo->beginTransaction();
  $cur = $pdo->prepare("SELECT activo FROM modulo WHERE ID=?");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception('No existe');

  $nuevo = ($row['activo']=='1' || $row['activo']==1) ? 0 : 1;
  $upd = $pdo->prepare("UPDATE modulo SET activo=?, fecha_modificacion=NOW() WHERE ID=?");
  $upd->execute([(int)$nuevo, $id]);
  $pdo->commit();
  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
