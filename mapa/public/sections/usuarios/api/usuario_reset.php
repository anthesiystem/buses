<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

$id = (int)($_POST['ID'] ?? 0); 
if ($id<=0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  // Obtener datos del usuario antes del reset
  $stmt_prev = $pdo->prepare("SELECT cuenta FROM usuario WHERE ID = ?");
  $stmt_prev->execute([$id]);
  $usuario_data = $stmt_prev->fetch(PDO::FETCH_ASSOC);
  
  if (!$usuario_data) {
    echo json_encode(['ok'=>false,'msg'=>'Usuario no encontrado']); 
    exit;
  }

  $hash = password_hash('admin', PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("UPDATE usuario SET contrasenia=?, fecha_modificacion=NOW() WHERE ID=?");
  $ok   = $stmt->execute([$hash, $id]);
  
  if ($ok) {
    // Registrar en bitácora
    $usuario_session = obtenerUsuarioSession();
    $descripcion = "Contraseña reseteada a 'admin' para usuario: {$usuario_data['cuenta']}";
    registrarBitacora($pdo, $usuario_session, 'usuario', 'usuario_reset', $descripcion, $id);
  }
  
  echo json_encode(['ok'=>(bool)$ok]);
}catch(PDOException $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
