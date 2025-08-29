<?php
session_start();
require_once '../server/config.php';
require_once '../server/bitacora_helper.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$estado = $_POST['estado'] ?? 'Desconocido';
$usuario_info = obtenerUsuarioSession();

try {
    $resultado = registrarDescargaPDF($pdo, $usuario_info['user_id'], $usuario_info['user_name'], 'estado', $estado);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Descarga registrada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar la descarga']);
    }
} catch (Exception $e) {
    error_log("Error registrando descarga PDF: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al registrar la descarga']);
}
?>
