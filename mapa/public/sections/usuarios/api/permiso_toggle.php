<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$id = (int)($_POST['ID'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $cur = $pdo->prepare("SELECT activo FROM permiso_usuario WHERE ID=?");
  $cur->execute([$id]);
  if (!$cur->fetch()){ 
    echo json_encode(['ok'=>false,'msg'=>'No encontrado']); 
    exit; 
  }

  // Usando NOT para alternar el valor del bit
  $up = $pdo->prepare("UPDATE permiso_usuario SET activo = NOT activo WHERE ID=?");
  $up->execute([$id]);

  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
