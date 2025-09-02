<?php
/**
 * Script de migración para agregar soporte de lotes al sistema de permisos
 * Ejecutar una sola vez después de implementar el nuevo sistema
 */

require_once __DIR__ . '/../server/config.php';

function migrarSistemaLotes($pdo) {
    try {
        echo "=== Iniciando migración del sistema de lotes ===\n\n";
        
        // 1. Verificar si ya existe el campo group_token
        echo "1. Verificando estructura de tabla...\n";
        $result = $pdo->query("SHOW COLUMNS FROM permiso_usuario LIKE 'group_token'");
        
        if ($result->rowCount() == 0) {
            echo "   - Agregando campo group_token...\n";
            $pdo->exec("ALTER TABLE permiso_usuario ADD COLUMN group_token CHAR(36) NULL DEFAULT NULL AFTER ID");
            echo "   ✓ Campo group_token agregado\n";
        } else {
            echo "   ✓ Campo group_token ya existe\n";
        }
        
        // 2. Agregar índice para mejorar performance
        echo "\n2. Verificando índices...\n";
        try {
            $pdo->exec("CREATE INDEX idx_group_token ON permiso_usuario (group_token)");
            echo "   ✓ Índice idx_group_token creado\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "   ✓ Índice idx_group_token ya existe\n";
            } else {
                throw $e;
            }
        }
        
        // 3. Estadísticas actuales
        echo "\n3. Estadísticas actuales:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM permiso_usuario");
        $total = $stmt->fetch()['total'];
        echo "   - Total de permisos: $total\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as con_token FROM permiso_usuario WHERE group_token IS NOT NULL");
        $conToken = $stmt->fetch()['con_token'];
        echo "   - Permisos con group_token: $conToken\n";
        echo "   - Permisos individuales: " . ($total - $conToken) . "\n";
        
        // 4. Sugerir agrupaciones automáticas (opcional)
        echo "\n4. Analizando posibles agrupaciones automáticas...\n";
        $stmt = $pdo->query("
            SELECT Fk_usuario, Fk_modulo, accion, COUNT(*) as cantidad
            FROM permiso_usuario 
            WHERE group_token IS NULL
            GROUP BY Fk_usuario, Fk_modulo, accion
            HAVING cantidad > 1
            ORDER BY cantidad DESC
            LIMIT 10
        ");
        
        $candidatos = $stmt->fetchAll();
        if (count($candidatos) > 0) {
            echo "   Candidatos para agrupar automáticamente:\n";
            foreach ($candidatos as $candidato) {
                echo "   - Usuario {$candidato['Fk_usuario']}, Módulo {$candidato['Fk_modulo']}, Acción '{$candidato['accion']}': {$candidato['cantidad']} permisos\n";
            }
            echo "\n   Para agrupar automáticamente, ejecutar: agruparAutomaticamente()\n";
        } else {
            echo "   ✓ No se encontraron candidatos obvios para agrupación automática\n";
        }
        
        echo "\n=== Migración completada exitosamente ===\n";
        
    } catch (Exception $e) {
        echo "\n❌ Error durante la migración: " . $e->getMessage() . "\n";
        throw $e;
    }
}

function agruparAutomaticamente($pdo) {
    try {
        echo "\n=== Iniciando agrupación automática ===\n";
        
        // Buscar grupos de permisos similares
        $stmt = $pdo->query("
            SELECT Fk_usuario, Fk_modulo, accion, COUNT(*) as cantidad,
                   GROUP_CONCAT(ID) as ids
            FROM permiso_usuario 
            WHERE group_token IS NULL
            GROUP BY Fk_usuario, Fk_modulo, accion
            HAVING cantidad > 1
        ");
        
        $grupos = $stmt->fetchAll();
        $totalAgrupados = 0;
        
        foreach ($grupos as $grupo) {
            // Generar UUID para el grupo
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Actualizar permisos con el nuevo group_token
            $ids = explode(',', $grupo['ids']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $updateStmt = $pdo->prepare("UPDATE permiso_usuario SET group_token = ? WHERE ID IN ($placeholders)");
            $updateStmt->execute([$uuid, ...$ids]);
            
            echo "   ✓ Agrupados {$grupo['cantidad']} permisos (Usuario: {$grupo['Fk_usuario']}, Módulo: {$grupo['Fk_modulo']}) con token: $uuid\n";
            $totalAgrupados += $grupo['cantidad'];
        }
        
        echo "\n=== Agrupación automática completada ===\n";
        echo "Total de permisos agrupados: $totalAgrupados\n";
        echo "Grupos creados: " . count($grupos) . "\n";
        
    } catch (Exception $e) {
        echo "\n❌ Error durante la agrupación: " . $e->getMessage() . "\n";
        throw $e;
    }
}

function verificarIntegridad($pdo) {
    echo "\n=== Verificando integridad del sistema ===\n";
    
    // Verificar que no hay tokens duplicados
    $stmt = $pdo->query("
        SELECT group_token, COUNT(*) as cantidad 
        FROM permiso_usuario 
        WHERE group_token IS NOT NULL 
        GROUP BY group_token 
        HAVING cantidad = 1
    ");
    $tokensUnicos = $stmt->rowCount();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT group_token) as total FROM permiso_usuario WHERE group_token IS NOT NULL");
    $totalTokens = $stmt->fetch()['total'];
    
    echo "   - Tokens únicos: $totalTokens\n";
    echo "   - Tokens con un solo permiso: $tokensUnicos\n";
    echo "   - Tokens con múltiples permisos: " . ($totalTokens - $tokensUnicos) . "\n";
    
    // Verificar constraints de FK
    $stmt = $pdo->query("
        SELECT pu.ID 
        FROM permiso_usuario pu 
        LEFT JOIN usuario u ON u.ID = pu.Fk_usuario 
        LEFT JOIN modulo m ON m.ID = pu.Fk_modulo 
        WHERE u.ID IS NULL OR m.ID IS NULL
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "   ❌ Se encontraron permisos con referencias inválidas\n";
    } else {
        echo "   ✓ Todas las referencias de FK son válidas\n";
    }
    
    echo "=== Verificación completada ===\n";
}

// Ejecución del script
if (php_sapi_name() === 'cli') {
    // Modo línea de comandos
    echo "Sistema de Migración de Permisos por Lotes\n";
    echo "==========================================\n\n";
    
    if ($argc > 1) {
        switch ($argv[1]) {
            case 'migrar':
                migrarSistemaLotes($pdo);
                break;
            case 'agrupar':
                agruparAutomaticamente($pdo);
                break;
            case 'verificar':
                verificarIntegridad($pdo);
                break;
            default:
                echo "Uso: php migracion.php [migrar|agrupar|verificar]\n";
                exit(1);
        }
    } else {
        // Ejecutar migración completa
        migrarSistemaLotes($pdo);
        
        $respuesta = readline("\n¿Desea ejecutar la agrupación automática? (y/N): ");
        if (strtolower($respuesta) === 'y') {
            agruparAutomaticamente($pdo);
        }
        
        verificarIntegridad($pdo);
    }
} else {
    // Modo web (solo migración básica)
    header('Content-Type: text/plain; charset=utf-8');
    
    try {
        migrarSistemaLotes($pdo);
        echo "\n✅ Migración completada. El sistema de lotes está listo para usar.\n";
        echo "\nPuedes probar el sistema en:\n";
        echo "- demo-lotes.html (interfaz completa)\n";
        echo "- test-lotes.html (página de pruebas)\n";
        
    } catch (Exception $e) {
        http_response_code(500);
        echo "❌ Error en la migración: " . $e->getMessage();
    }
}
?>
