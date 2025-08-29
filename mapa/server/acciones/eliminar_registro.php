<?php
require_once '../config.php';
require_once '../bitacora_helper.php';
session_start();

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['success' => false, 'error' => 'No autorizado']);
  exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
  echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
  exit;
}

try {
  // Obtener información del registro antes de desactivar
  $stmt_info = $pdo->prepare("
    SELECT r.*,
           e.descripcion AS entidad_nombre,
           d.descripcion AS dependencia_nombre,
           b.descripcion AS bus_nombre,
           t.descripcion AS tecnologia_nombre
    FROM registro r
    LEFT JOIN entidad e ON e.ID = r.Fk_entidad
    LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia  
    LEFT JOIN bus b ON b.ID = r.Fk_bus
    LEFT JOIN tecnologia t ON t.ID = r.Fk_tecnologia
    WHERE r.ID = ? AND r.activo = 1
  ");
  $stmt_info->execute([$id]);
  $registro_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
  
  if (!$registro_info) {
    echo json_encode(['success' => false, 'error' => 'Registro no encontrado o ya está inactivo']);
    exit;
  }

  // Desactivar registro
  $stmt = $pdo->prepare("UPDATE registro SET activo = 0, fecha_modificacion = NOW() WHERE ID = ?");
  $stmt->execute([$id]);

  // Registrar en bitácora usando helper
  $usuario_info = obtenerUsuarioSession();
  $descripcion_bitacora = "Registro ID $id desactivado";
  $descripcion_bitacora .= " - Entidad: " . ($registro_info['entidad_nombre'] ?? 'N/A');
  if ($registro_info['dependencia_nombre']) {
    $descripcion_bitacora .= ", Dependencia: " . $registro_info['dependencia_nombre'];
  }
  if ($registro_info['bus_nombre']) {
    $descripcion_bitacora .= ", Bus: " . $registro_info['bus_nombre'];
  }

  registrarBitacora(
    $pdo, 
    $usuario_info['user_id'], 
    'registro', 
    'DESACTIVAR', 
    $descripcion_bitacora, 
    $id
  );

  echo json_encode(['success' => true, 'message' => 'Registro desactivado correctamente']);

} catch (Exception $e) {
  error_log("Error en eliminar_registro.php: " . $e->getMessage());
  echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
