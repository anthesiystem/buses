<?php
/**
 * Diagnóstico rápido para el problema de la columna 'activo'
 */
require_once __DIR__ . '/../server/config.php';

header('Content-Type: application/json');

try {
    // 1. Verificar estructura de la columna activo
    $stmt = $pdo->query("SHOW COLUMNS FROM permiso_usuario WHERE Field = 'activo'");
    $activoColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activoColumn) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Columna activo no encontrada',
            'solucion' => 'ALTER TABLE permiso_usuario ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1'
        ]);
        exit;
    }
    
    // 2. Analizar el tipo de dato
    $tipo = strtolower($activoColumn['Type']);
    $esProblematico = false;
    $razon = '';
    
    if (strpos($tipo, 'char') !== false) {
        if (preg_match('/\((\d+)\)/', $tipo, $matches)) {
            $length = (int)$matches[1];
            if ($length < 2) {
                $esProblematico = true;
                $razon = "CHAR($length) es demasiado restrictivo";
            }
        }
    }
    
    // 3. Verificar datos problemáticos
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN activo NOT IN ('0', '1', 0, 1) THEN 1 ELSE 0 END) as problematicos
        FROM permiso_usuario
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Ejemplos de valores problemáticos
    $ejemplos = [];
    if ($stats['problematicos'] > 0) {
        $stmt = $pdo->query("
            SELECT DISTINCT activo, COUNT(*) as cantidad 
            FROM permiso_usuario 
            WHERE activo NOT IN ('0', '1', 0, 1)
            GROUP BY activo 
            LIMIT 5
        ");
        $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 5. Generar respuesta
    $response = [
        'error' => false,
        'columna' => [
            'tipo' => $activoColumn['Type'],
            'null' => $activoColumn['Null'],
            'default' => $activoColumn['Default'],
            'es_problematico' => $esProblematico,
            'razon' => $razon
        ],
        'datos' => [
            'total_registros' => $stats['total'],
            'problematicos' => $stats['problematicos'],
            'ejemplos_problematicos' => $ejemplos
        ],
        'scripts_correccion' => []
    ];
    
    // 6. Generar scripts de corrección
    if ($esProblematico) {
        $response['scripts_correccion'][] = [
            'tipo' => 'estructura',
            'sql' => 'ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;',
            'descripcion' => 'Corrige el tipo de dato de la columna activo'
        ];
    }
    
    if ($stats['problematicos'] > 0) {
        $response['scripts_correccion'][] = [
            'tipo' => 'datos',
            'sql' => 'UPDATE permiso_usuario SET activo = CASE WHEN activo = \'0\' OR activo = 0 THEN 0 ELSE 1 END;',
            'descripcion' => 'Normaliza los valores problemáticos en la columna activo'
        ];
    }
    
    // 7. Estado general
    $response['estado'] = 'ok';
    if ($esProblematico || $stats['problematicos'] > 0) {
        $response['estado'] = 'requiere_correccion';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'tipo' => 'database',
        'mensaje' => $e->getMessage(),
        'codigo' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'tipo' => 'general',
        'mensaje' => $e->getMessage()
    ]);
}
?>
