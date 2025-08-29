<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

function jerr($m){ echo json_encode(['ok'=>false,'msg'=>$m], JSON_UNESCAPED_UNICODE); exit; }
$id   = $_POST['ID'] ?? '';
$desc = trim($_POST['descripcion'] ?? '');
$act  = isset($_POST['activo']) && $_POST['activo']=='1' ? 1 : 0;

if ($desc === '') jerr('La descripción (slug) es obligatoria.');
// Valida slug sencillo (minúsculas, números, guiones bajos)
if (!preg_match('/^[a-z0-9_]+$/', $desc)) jerr('Usa solo minúsculas, números y "_" (sin espacios ni acentos).');

try{
  if ($id===''){
    $sql = "INSERT INTO modulo (descripcion, activo) VALUES (?, ?)";
    $st  = $pdo->prepare($sql);
    $st->bindValue(1, $desc);
    $st->bindValue(2, (int)$act, PDO::PARAM_INT);
    $ok  = $st->execute();
    
    if ($ok) {
      $new_id = $pdo->lastInsertId();
      $usuario_session = obtenerUsuarioSession();
      $descripcion = "Nuevo módulo creado - descripción: '$desc', activo: " . ($act ? 'Sí' : 'No');
      registrarBitacora($pdo, $usuario_session, 'modulo', 'modulo_crear', $descripcion, $new_id);
    }
    
  } else {
    // UPDATE - Obtener datos actuales para el log de cambios
    $stmt_prev = $pdo->prepare("SELECT descripcion, activo FROM modulo WHERE ID = ?");
    $stmt_prev->execute([(int)$id]);
    $datos_anteriores = $stmt_prev->fetch(PDO::FETCH_ASSOC);
    
    if ($datos_anteriores) {
      $sql = "UPDATE modulo SET descripcion=?, activo=?, fecha_modificacion=NOW() WHERE ID=?";
      $st  = $pdo->prepare($sql);
      $st->bindValue(1, $desc);
      $st->bindValue(2, (int)$act, PDO::PARAM_INT);
      $st->bindValue(3, (int)$id, PDO::PARAM_INT);
      $ok  = $st->execute();
      
      if ($ok) {
        // Registrar cambios en bitácora
        $cambios = [];
        if ($datos_anteriores['descripcion'] !== $desc) {
          $cambios[] = "descripción: '{$datos_anteriores['descripcion']}' → '$desc'";
        }
        if ($datos_anteriores['activo'] != $act) {
          $estado_anterior = $datos_anteriores['activo'] ? 'Activo' : 'Inactivo';
          $estado_nuevo = $act ? 'Activo' : 'Inactivo';
          $cambios[] = "estado: '$estado_anterior' → '$estado_nuevo'";
        }

        if (!empty($cambios)) {
          $usuario_session = obtenerUsuarioSession();
          $descripcion = "Módulo actualizado ($desc) - " . implode(', ', $cambios);
          registrarBitacora($pdo, $usuario_session, 'modulo', 'modulo_editar', $descripcion, (int)$id);
        }
      }
    }
  }
  echo json_encode(['ok'=>(bool)$ok]);
} catch(PDOException $e){
  if (strpos($e->getMessage(),'1062')!==false) jerr('Ya existe un módulo con ese slug.');
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
