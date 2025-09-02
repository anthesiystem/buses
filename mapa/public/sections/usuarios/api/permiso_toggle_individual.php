<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../../../../server/config.php';
include_once __DIR__ . '/../../../../server/bitacora_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    if ($action !== 'toggle') {
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
        exit;
    }

    $group_token = $_POST['group_token'] ?? '';
    $entidad = $_POST['entidad'] ?? '';
    $bus = $_POST['bus'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    
    // Normalizar nuevo_estado a entero estricto 0 o 1
    if ($nuevo_estado === '1' || $nuevo_estado === 1 || $nuevo_estado === true || $nuevo_estado === 'true') {
        $nuevo_estado = 1;
    } else {
        $nuevo_estado = 0;
    }
    
    // Debug para identificar problemas
    error_log("DEBUG Toggle: group_token=$group_token, entidad=$entidad, bus=$bus, nuevo_estado_original=" . var_export($_POST['nuevo_estado'] ?? '', true) . ", nuevo_estado_normalizado=$nuevo_estado");

    // Validaciones
    if (empty($group_token)) {
        echo json_encode(['ok' => false, 'msg' => 'Token de grupo requerido']);
        exit;
    }

    if (empty($entidad) || empty($bus)) {
        echo json_encode(['ok' => false, 'msg' => 'Entidad y bus son requeridos']);
        exit;
    }
    
    // Validación estricta del nuevo estado
    if ($nuevo_estado !== 0 && $nuevo_estado !== 1) {
        echo json_encode(['ok' => false, 'msg' => 'Estado debe ser 0 o 1, recibido: ' . var_export($nuevo_estado, true)]);
        exit;
    }

    // Construir condiciones WHERE según el tipo de selección
    $where_conditions = ["group_token = :group_token"];
    $params = [':group_token' => $group_token];

    // Manejo de entidades
    if ($entidad === 'ALL') {
        $where_conditions[] = "FK_entidad IS NULL";
    } else {
        $where_conditions[] = "FK_entidad = :entidad";
        $params[':entidad'] = $entidad;
    }

    // Manejo de buses
    if ($bus === 'ALL') {
        $where_conditions[] = "FK_bus IS NULL";
    } else {
        $where_conditions[] = "FK_bus = :bus";
        $params[':bus'] = $bus;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Verificar que existen permisos con el token especificado
    $check_sql = "SELECT COUNT(*) as total FROM permiso_usuario WHERE $where_clause";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute($params);
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($check_result['total'] == 0) {
        echo json_encode(['ok' => false, 'msg' => 'No se encontraron permisos para actualizar']);
        exit;
    }

    // Actualizar el estado de los permisos
    $update_sql = "UPDATE permiso_usuario SET activo = ? WHERE $where_clause";
    $params[':nuevo_estado'] = $nuevo_estado;
    
    // Debug final antes de la consulta
    error_log("DEBUG SQL: " . $update_sql);
    error_log("DEBUG Params: " . json_encode($params));
    
    $update_stmt = $pdo->prepare($update_sql);
    $affected_rows = $update_stmt->execute($params) ? $update_stmt->rowCount() : 0;

    if ($affected_rows > 0) {
        // Registrar en bitácora
        $entidad_desc = $entidad === 'ALL' ? 'Todas las entidades' : "Entidad $entidad";
        $bus_desc = $bus === 'ALL' ? 'Todos los buses' : "Bus $bus";
        $estado_desc = $nuevo_estado ? 'activado' : 'desactivado';
        
        try {
            if (function_exists('registrar_bitacora')) {
                registrar_bitacora(
                    'PERMISO_TOGGLE',
                    "Permiso $estado_desc para grupo $group_token: $entidad_desc × $bus_desc",
                    [
                        'group_token' => $group_token,
                        'entidad' => $entidad,
                        'bus' => $bus,
                        'nuevo_estado' => $nuevo_estado,
                        'affected_rows' => $affected_rows
                    ]
                );
            }
        } catch (Exception $e) {
            // Error en bitácora no debe afectar la operación principal
            error_log("Error registrando en bitácora: " . $e->getMessage());
        }

        echo json_encode([
            'ok' => true,
            'msg' => "Permiso $estado_desc correctamente",
            'affected_rows' => $affected_rows,
            'details' => [
                'group_token' => $group_token,
                'entidad' => $entidad_desc,
                'bus' => $bus_desc,
                'nuevo_estado' => $nuevo_estado
            ]
        ]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'No se actualizó ningún registro']);
    }

} catch (PDOException $e) {
    error_log("Error en toggle individual: " . $e->getMessage());
    echo json_encode([
        'ok' => false, 
        'msg' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general en toggle individual: " . $e->getMessage());
    echo json_encode([
        'ok' => false, 
        'msg' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
