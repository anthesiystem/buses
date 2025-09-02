<?php
/**
 * Script para verificar y corregir la estructura de la tabla permiso_usuario
 * Específicamente para la columna 'activo' que está causando el error de truncamiento
 */

require_once __DIR__ . '/../../../../server/config.php';

try {
    echo "🔍 Verificando estructura de la tabla permiso_usuario...\n\n";
    
    // Verificar estructura actual de la tabla
    $stmt = $pdo->query("DESCRIBE permiso_usuario");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Estructura actual de la tabla:\n";
    echo "+-----------------+---------------------+------+-----+---------+-------+\n";
    echo "| Field           | Type                | Null | Key | Default | Extra |\n";
    echo "+-----------------+---------------------+------+-----+---------+-------+\n";
    
    $activoColumn = null;
    foreach ($columns as $column) {
        echo sprintf("| %-15s | %-19s | %-4s | %-3s | %-7s | %-5s |\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'],
            $column['Default'] ?? 'NULL',
            $column['Extra']
        );
        
        if ($column['Field'] === 'activo') {
            $activoColumn = $column;
        }
    }
    echo "+-----------------+---------------------+------+-----+---------+-------+\n\n";
    
    if (!$activoColumn) {
        echo "❌ ERROR: No se encontró la columna 'activo' en la tabla permiso_usuario\n";
        exit(1);
    }
    
    echo "🔍 Análisis de la columna 'activo':\n";
    echo "   Tipo actual: {$activoColumn['Type']}\n";
    echo "   Permite NULL: {$activoColumn['Null']}\n";
    echo "   Valor por defecto: " . ($activoColumn['Default'] ?? 'NULL') . "\n\n";
    
    // Verificar si el tipo es adecuado
    $tipoActual = strtolower($activoColumn['Type']);
    $tiposValidos = ['tinyint(1)', 'boolean', 'bool', 'int(1)', 'tinyint'];
    
    $tipoEsValido = false;
    foreach ($tiposValidos as $tipoValido) {
        if (strpos($tipoActual, $tipoValido) !== false) {
            $tipoEsValido = true;
            break;
        }
    }
    
    if (!$tipoEsValido) {
        echo "⚠️  PROBLEMA IDENTIFICADO:\n";
        echo "   La columna 'activo' tiene tipo '{$activoColumn['Type']}' que puede ser demasiado restrictivo.\n";
        echo "   Tipo recomendado: TINYINT(1) o BOOLEAN\n\n";
        
        echo "🔧 ¿Deseas corregir la columna? (s/n): ";
        $respuesta = trim(fgets(STDIN));
        
        if (strtolower($respuesta) === 's' || strtolower($respuesta) === 'si') {
            echo "\n🔧 Corrigiendo estructura de la columna 'activo'...\n";
            
            // Backup de datos actuales
            echo "1. Creando backup de datos actuales...\n";
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM permiso_usuario WHERE activo NOT IN (0, 1)");
            $problemRows = $stmt->fetch()['total'];
            
            if ($problemRows > 0) {
                echo "⚠️  Se encontraron $problemRows filas con valores de 'activo' no estándar (no 0 ni 1)\n";
                echo "   Estos valores se normalizarán a 0 o 1\n\n";
                
                // Normalizar valores problemáticos
                $pdo->exec("UPDATE permiso_usuario SET activo = 1 WHERE activo != 0 AND activo != 1");
                echo "✅ Valores normalizados\n";
            }
            
            // Modificar la columna
            echo "2. Modificando estructura de la columna...\n";
            $alterSQL = "ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1";
            $pdo->exec($alterSQL);
            echo "✅ Columna 'activo' modificada a TINYINT(1) NOT NULL DEFAULT 1\n\n";
            
            // Verificar el cambio
            echo "3. Verificando cambios...\n";
            $stmt = $pdo->query("DESCRIBE permiso_usuario");
            $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($newColumns as $column) {
                if ($column['Field'] === 'activo') {
                    echo "✅ Nueva estructura de 'activo':\n";
                    echo "   Tipo: {$column['Type']}\n";
                    echo "   Permite NULL: {$column['Null']}\n";
                    echo "   Valor por defecto: " . ($column['Default'] ?? 'NULL') . "\n";
                    break;
                }
            }
            
            echo "\n🎉 ¡Estructura corregida exitosamente!\n";
            
        } else {
            echo "\n⏭️  Corrección cancelada por el usuario.\n";
        }
        
    } else {
        echo "✅ La columna 'activo' tiene un tipo de dato adecuado.\n";
        
        // Verificar si hay valores problemáticos en los datos
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_registros,
                SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN activo NOT IN (0,1) THEN 1 ELSE 0 END) as problematicos
            FROM permiso_usuario
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n📊 Estadísticas de datos:\n";
        echo "   Total de registros: {$stats['total_registros']}\n";
        echo "   Activos (1): {$stats['activos']}\n";
        echo "   Inactivos (0): {$stats['inactivos']}\n";
        echo "   Problemáticos (no 0 ni 1): {$stats['problematicos']}\n";
        
        if ($stats['problematicos'] > 0) {
            echo "\n⚠️  Se encontraron {$stats['problematicos']} registros con valores problemáticos en 'activo'\n";
            echo "🔧 ¿Deseas normalizar estos valores? (s/n): ";
            $respuesta = trim(fgets(STDIN));
            
            if (strtolower($respuesta) === 's' || strtolower($respuesta) === 'si') {
                $pdo->exec("UPDATE permiso_usuario SET activo = 1 WHERE activo != 0 AND activo != 1");
                echo "✅ Valores problemáticos normalizados a 1\n";
            }
        }
    }
    
    echo "\n🏁 Verificación completada.\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE BASE DE DATOS: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
