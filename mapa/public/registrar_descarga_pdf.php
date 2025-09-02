<?php
session_start();
require_once '../server/config.php';
require_once '../server/bitacora_helper.php';

// Configurar headers JSON
header('Content-Type: application/json; charset=utf-8');

// Obtener información del usuario
$usuario_id = obtenerUsuarioSession();

// Verificar si hay usuario válido
if ($usuario_id <= 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'No autorizado - No hay usuario válido en sesión',
        'debug' => [
            'session_usuario_id' => $_SESSION['usuario_id'] ?? null,
            'session_ID' => $_SESSION['ID'] ?? null,
            'usuario_id_function' => $usuario_id
        ]
    ]);
    exit;
}

$estado = $_POST['estado'] ?? 'Desconocido';
$usuario_id = obtenerUsuarioSession();
$usuario_nombre = obtenerNombreUsuarioSession();

// Debug info
error_log("Registro PDF - Usuario ID: $usuario_id, Nombre: $usuario_nombre, Estado: $estado");

try {
    $resultado = registrarDescargaPDF($pdo, $usuario_id, $usuario_nombre, 'estado', $estado);
    
    if ($resultado) {
        echo json_encode([
            'success' => true, 
            'message' => 'Descarga registrada correctamente',
            'debug' => [
                'usuario_id' => $usuario_id,
                'usuario_nombre' => $usuario_nombre,
                'estado' => $estado
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al registrar la descarga - función retornó false'
        ]);
    }
} catch (Exception $e) {
    error_log("Error registrando descarga PDF: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al registrar la descarga: ' . $e->getMessage()
    ]);
}
?>
