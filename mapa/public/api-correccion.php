<?php
/**
 * Script para ejecutar correcciones automáticas en la columna activo
 */
require_once __DIR__ . '/../server/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'mensaje' => 'Solo se permiten peticiones POST']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'corregir_estructura':
            // Modificar el tipo de columna
            $pdo->exec("ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Estructura de columna corregida exitosamente',
                'sql_ejecutado' => 'ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1'
            ]);
            break;
            
        case 'normalizar_datos':
            // Normalizar valores problemáticos
            $stmt = $pdo->exec("UPDATE permiso_usuario SET activo = CASE WHEN activo = '0' OR activo = 0 THEN 0 ELSE 1 END");
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Datos normalizados exitosamente',
                'registros_afectados' => $stmt,
                'sql_ejecutado' => "UPDATE permiso_usuario SET activo = CASE WHEN activo = '0' OR activo = 0 THEN 0 ELSE 1 END"
            ]);
            break;
            
        case 'correccion_completa':
            // Ejecutar ambas correcciones
            $pdo->beginTransaction();
            
            // 1. Normalizar datos primero
            $affected = $pdo->exec("UPDATE permiso_usuario SET activo = CASE WHEN activo = '0' OR activo = 0 THEN 0 ELSE 1 END");
            
            // 2. Luego corregir estructura
            $pdo->exec("ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Corrección completa ejecutada exitosamente',
                'registros_normalizados' => $affected,
                'acciones' => [
                    'datos_normalizados' => true,
                    'estructura_corregida' => true
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'error' => true,
                'mensaje' => 'Acción no válida. Acciones disponibles: corregir_estructura, normalizar_datos, correccion_completa'
            ]);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'error' => true,
        'tipo' => 'database',
        'mensaje' => $e->getMessage(),
        'codigo' => $e->getCode()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'error' => true,
        'tipo' => 'general',
        'mensaje' => $e->getMessage()
    ]);
}
?>
