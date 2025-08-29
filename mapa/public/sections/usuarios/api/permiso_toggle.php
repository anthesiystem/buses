<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

$id = (int)($_POST['ID'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $cur = $pdo->prepare("SELECT activo, Fk_usuario, Fk_modulo, accion FROM permiso_usuario WHERE ID=?");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row){ 
    echo json_encode(['ok'=>false,'msg'=>'No encontrado']); 
    exit; 
  }

  $estado_anterior = $row['activo'] ? 'Activo' : 'Inactivo';
  $estado_nuevo = $row['activo'] ? 'Inactivo' : 'Activo';

  // Usando NOT para alternar el valor del bit
  $up = $pdo->prepare("UPDATE permiso_usuario SET activo = NOT activo WHERE ID=?");
  $up->execute([$id]);

  // Registrar en bitácora
  $usuario_session = obtenerUsuarioSession();
  $accion_texto = $row['accion'] ? $row['accion'] : 'General';
  $descripcion = "Estado de permiso cambiado (Usuario: {$row['Fk_usuario']}, Módulo: {$row['Fk_modulo']}, Acción: $accion_texto) - estado: '$estado_anterior' → '$estado_nuevo'";
  registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_toggle', $descripcion, $id);

  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
