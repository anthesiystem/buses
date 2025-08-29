<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

$id = (int)($_POST['ID'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $pdo->beginTransaction();
  
  $cur = $pdo->prepare("SELECT activo, descripcion FROM modulo WHERE ID=?");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception('No existe');

  $estado_anterior = $row['activo'] ? 'Activo' : 'Inactivo';
  $estado_nuevo = $row['activo'] ? 'Inactivo' : 'Activo';

  // Usando NOT para alternar el valor del bit directamente
  $upd = $pdo->prepare("UPDATE modulo SET activo = NOT activo WHERE ID=?");
  $upd->execute([$id]);
  
  // Registrar en bitácora
  $usuario_session = obtenerUsuarioSession();
  $descripcion = "Estado de módulo cambiado ({$row['descripcion']}) - estado: '$estado_anterior' → '$estado_nuevo'";
  registrarBitacora($pdo, $usuario_session, 'modulo', 'modulo_toggle', $descripcion, $id);
  
  $pdo->commit();
  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
