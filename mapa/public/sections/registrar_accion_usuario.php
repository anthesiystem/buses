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
    $accion = trim($_POST['accion'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $url = trim($_POST['url'] ?? '');
    
    if (empty($accion)) {
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }

    $usuario_info = obtenerUsuarioSession();
    
    // Mapear acciones a tipos de bitácora
    $acciones_permitidas = [
        'abrir_modal_registro' => 'Abrir modal de registro',
        'abrir_editar' => 'Abrir formulario de edición',
        'cambiar_estado' => 'Cambiar estado de registro',
        'aplicar_filtro' => 'Aplicar filtro',
        'exportar_datos' => 'Exportar datos',
        'buscar' => 'Realizar búsqueda',
        'paginar' => 'Cambiar página',
        'ordenar' => 'Ordenar tabla'
    ];

    if (!isset($acciones_permitidas[$accion])) {
        echo json_encode(['success' => false, 'message' => 'Acción no permitida']);
        exit;
    }

    $descripcion_accion = $acciones_permitidas[$accion];
    $descripcion_completa = "Acción del usuario: $descripcion_accion";
    
    if (!empty($detalle)) {
        $descripcion_completa .= " - $detalle";
    }
    
    if (!empty($url)) {
        $descripcion_completa .= " - URL: $url";
    }

    // Solo registrar acciones importantes, no todas las interacciones menores
    $acciones_importantes = ['abrir_editar', 'cambiar_estado', 'exportar_datos'];
    
    if (in_array($accion, $acciones_importantes)) {
        $resultado = registrarBitacora(
            $pdo,
            $usuario_info['user_id'],
            'accion_usuario',
            'INTERACCION',
            $descripcion_completa
        );

        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Acción registrada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar acción']);
        }
    } else {
        // Para acciones menores, solo responder OK sin registrar
        echo json_encode(['success' => true, 'message' => 'Acción procesada']);
    }

} catch (Exception $e) {
    error_log("Error en registrar_accion_usuario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
?>
