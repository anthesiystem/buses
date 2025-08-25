<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
$id = (int)($_POST['ID'] ?? 0); if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
try{
  $hash = password_hash('admin', PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("UPDATE usuario SET contrasenia=?, fecha_modificacion=NOW() WHERE ID=?");
  $ok   = $stmt->execute([$hash, $id]);
  echo json_encode(['ok'=>(bool)$ok]);
}catch(PDOException $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
