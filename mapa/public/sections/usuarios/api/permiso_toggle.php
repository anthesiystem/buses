<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$id = (int)($_POST['ID'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $cur = $pdo->prepare("SELECT CAST(activo AS UNSIGNED) a FROM permiso_usuario WHERE ID=?");
  $cur->execute([$id]);
  $a = $cur->fetchColumn();
  if ($a===false){ echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }
  $nuevo = $a ? 0 : 1;

  $up = $pdo->prepare("UPDATE permiso_usuario SET activo=?, fecha_modificacion=NOW() WHERE ID=?");
  $up->bindValue(1,(int)$nuevo, PDO::PARAM_INT);
  $up->bindValue(2,(int)$id,    PDO::PARAM_INT);
  $up->execute();

  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
