<?php
session_start();
require_once '../server/config.php';
require_once '../server/bitacora_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $debug_info = [
        'session_status' => session_status(),
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? [],
        'usuario_id_function' => obtenerUsuarioSession(),
        'usuario_nombre_function' => obtenerNombreUsuarioSession(),
        'post_data' => $_POST,
        'functions_exist' => [
            'obtenerUsuarioSession' => function_exists('obtenerUsuarioSession'),
            'obtenerNombreUsuarioSession' => function_exists('obtenerNombreUsuarioSession'),
            'registrarDescargaPDF' => function_exists('registrarDescargaPDF'),
            'registrarBitacora' => function_exists('registrarBitacora')
        ]
    ];
    
    // Test de la función registrarDescargaPDF si hay datos POST
    if (!empty($_POST['estado'])) {
        $usuario_id = obtenerUsuarioSession();
        $usuario_nombre = obtenerNombreUsuarioSession();
        $estado = $_POST['estado'];
        
        if ($usuario_id > 0) {
            $resultado = registrarDescargaPDF($pdo, $usuario_id, $usuario_nombre, 'estado', $estado);
            $debug_info['test_result'] = [
                'usuario_id' => $usuario_id,
                'usuario_nombre' => $usuario_nombre,
                'estado' => $estado,
                'resultado' => $resultado
            ];
            
            // Verificar si se insertó en la bitácora
            $stmt = $pdo->prepare("SELECT * FROM bitacora WHERE Fk_Usuario = ? ORDER BY Id_Bitacora DESC LIMIT 1");
            $stmt->execute([$usuario_id]);
            $last_bitacora = $stmt->fetch(PDO::FETCH_ASSOC);
            $debug_info['last_bitacora_entry'] = $last_bitacora;
        } else {
            $debug_info['error'] = 'Usuario ID es 0 o inválido';
        }
    }
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
