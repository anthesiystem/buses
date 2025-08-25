<?php
require_once '../../../server/config.php';

// JSON siempre
header('Content-Type: application/json; charset=utf-8');
// No mezclar HTML de errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
if (ob_get_level() > 0) { ob_clean(); }

// 🔧 Activa excepciones en PDO (por si tu config no lo hace)
if (isset($pdo)) {
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$DEBUG = true; // ponlo en false cuando quede

try {
  $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $estado = isset($_GET['estado']) ? (int)$_GET['estado'] : null; // 0 o 1

  if ($id <= 0 || ($estado !== 0 && $estado !== 1)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
  }

  // ⚠️ Evitar problemas con BIT(1): castear a UNSIGNED en SQL.
  // Alternativas que también funcionan:
  //   - "SET activo = ? + 0"
  //   - "SET activo = CAST(:activo AS UNSIGNED)"
  $sql = "UPDATE bus SET activo = CAST(:activo AS UNSIGNED) WHERE ID = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':activo', $estado, PDO::PARAM_INT);
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute();

  // Verifica que realmente afectó una fila (por si el ID no existe)
  if ($stmt->rowCount() === 0) {
    // Puede ser porque el valor ya era el mismo; no lo trates como error grave
    echo json_encode(['success' => true, 'id' => $id, 'estado' => $estado, 'note' => 'Sin cambios (posible mismo valor)']);
    exit;
  }

  echo json_encode(['success' => true, 'id' => $id, 'estado' => $estado]);
} catch (Throwable $e) {
  error_log('[cambiar_estado_bus] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error'   => $DEBUG ? $e->getMessage() : 'Error interno del servidor'
  ]);
}
