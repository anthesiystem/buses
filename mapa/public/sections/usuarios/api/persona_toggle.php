<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
$id = (int)($_POST['ID'] ?? 0); if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $pdo->beginTransaction();
  // Seleccionar el valor actual
  $cur = $pdo->prepare("SELECT activo FROM persona WHERE ID=?");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception('No existe');

  // Alternar entre true/false para campos BIT
  $upd = $pdo->prepare("UPDATE persona SET activo = NOT activo WHERE ID=?");
  $upd->execute([$id]);
  $pdo->commit();
  echo json_encode(['ok'=>true]);
}catch(Exception $e){
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
