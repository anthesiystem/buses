<?php
session_start();
require_once '../server/config.php';
require_once '../server/bitacora_helper.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $vista = trim($_POST['vista'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $url = trim($_POST['url'] ?? '');
    
    if (empty($vista)) {
        echo json_encode(['success' => false, 'message' => 'Vista no especificada']);
        exit;
    }

    // Throttling: no registrar la misma vista más de una vez por minuto por usuario
    $usuario_info = obtenerUsuarioSession();
    $cache_key = md5($usuario_info['user_id'] . '_' . $vista);
    
    // Verificar último registro de esta vista
    $stmt_ultimo = $pdo->prepare("
        SELECT Fecha_Accion 
        FROM bitacora 
        WHERE Fk_Usuario = ? 
        AND Tabla_Afectada = 'vista' 
        AND Tipo_Accion = 'ACCESO'
        AND Descripcion LIKE ?
        ORDER BY Fecha_Accion DESC 
        LIMIT 1
    ");
    $stmt_ultimo->execute([$usuario_info['user_id'], "%$vista%"]);
    $ultimo_acceso = $stmt_ultimo->fetchColumn();
    
    // Si el último acceso fue hace menos de 1 minuto, no registrar
    if ($ultimo_acceso && (time() - strtotime($ultimo_acceso)) < 60) {
        echo json_encode(['success' => true, 'message' => 'Acceso ya registrado recientemente']);
        exit;
    }

    $descripcion_completa = "Acceso a $descripcion";
    if (!empty($url)) {
        $descripcion_completa .= " - URL: $url";
    }

    $resultado = registrarBitacora(
        $pdo,
        $usuario_info['user_id'],
        'vista',
        'ACCESO',
        $descripcion_completa
    );

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Vista registrada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar vista']);
    }

} catch (Exception $e) {
    error_log("Error en registrar_vista_bitacora.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
?>
