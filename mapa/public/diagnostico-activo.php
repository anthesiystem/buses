<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos - Columna Activo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn.danger { background: #dc3545; }
        .btn.danger:hover { background: #c82333; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico: Problema de Columna 'activo'</h1>
        
        <?php
        require_once __DIR__ . '/../server/config.php';
        
        try {
            echo '<div class="info"><strong>Conexión a la base de datos:</strong> ✅ Exitosa</div>';
            
            // 1. Verificar estructura de la tabla
            echo '<h2>📋 Estructura de la tabla permiso_usuario</h2>';
            $stmt = $pdo->query("DESCRIBE permiso_usuario");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            
            $activoColumn = null;
            foreach ($columns as $column) {
                $rowClass = $column['Field'] === 'activo' ? 'style="background-color: #fff3cd;"' : '';
                echo "<tr $rowClass>";
                echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                echo '</tr>';
                
                if ($column['Field'] === 'activo') {
                    $activoColumn = $column;
                }
            }
            echo '</table>';
            
            if (!$activoColumn) {
                echo '<div class="error"><strong>❌ ERROR:</strong> No se encontró la columna "activo" en la tabla permiso_usuario</div>';
            } else {
                echo '<h2>🔍 Análisis de la columna "activo"</h2>';
                echo '<div class="code">';
                echo '<strong>Tipo actual:</strong> ' . htmlspecialchars($activoColumn['Type']) . '<br>';
                echo '<strong>Permite NULL:</strong> ' . htmlspecialchars($activoColumn['Null']) . '<br>';
                echo '<strong>Valor por defecto:</strong> ' . htmlspecialchars($activoColumn['Default'] ?? 'NULL') . '<br>';
                echo '</div>';
                
                // Verificar si el tipo es problemático
                $tipoActual = strtolower($activoColumn['Type']);
                $esTipoProblematico = false;
                
                // Tipos que pueden causar problemas
                if (strpos($tipoActual, 'char') !== false || strpos($tipoActual, 'varchar') !== false) {
                    if (preg_match('/\((\d+)\)/', $tipoActual, $matches)) {
                        $length = (int)$matches[1];
                        if ($length < 2) {
                            $esTipoProblematico = true;
                        }
                    }
                }
                
                if ($esTipoProblematico) {
                    echo '<div class="error">';
                    echo '<strong>⚠️ PROBLEMA IDENTIFICADO:</strong><br>';
                    echo 'La columna "activo" tiene tipo "' . htmlspecialchars($activoColumn['Type']) . '" que es demasiado restrictivo.<br>';
                    echo '<strong>Solución recomendada:</strong> Cambiar a TINYINT(1) o BOOLEAN';
                    echo '</div>';
                } else {
                    echo '<div class="success">';
                    echo '<strong>✅ TIPO ADECUADO:</strong> La columna tiene un tipo de dato apropiado.';
                    echo '</div>';
                }
            }
            
            // 2. Verificar datos existentes
            echo '<h2>📊 Análisis de datos existentes</h2>';
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN activo = '0' OR activo = 0 THEN 1 ELSE 0 END) as inactivos,
                    SUM(CASE WHEN activo = '1' OR activo = 1 THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN activo NOT IN ('0', '1', 0, 1) THEN 1 ELSE 0 END) as problematicos
                FROM permiso_usuario
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>Categoría</th><th>Cantidad</th><th>Descripción</th></tr>';
            echo '<tr><td>Total de registros</td><td>' . $stats['total'] . '</td><td>Todos los permisos en la tabla</td></tr>';
            echo '<tr><td>Activos</td><td>' . $stats['activos'] . '</td><td>Permisos con valor 1 o "1"</td></tr>';
            echo '<tr><td>Inactivos</td><td>' . $stats['inactivos'] . '</td><td>Permisos con valor 0 o "0"</td></tr>';
            echo '<tr style="background-color: ' . ($stats['problematicos'] > 0 ? '#f8d7da' : '#d4edda') . '"><td>Problemáticos</td><td>' . $stats['problematicos'] . '</td><td>Valores que no son 0 ni 1</td></tr>';
            echo '</table>';
            
            if ($stats['problematicos'] > 0) {
                echo '<div class="warning">';
                echo '<strong>⚠️ DATOS PROBLEMÁTICOS:</strong> Se encontraron ' . $stats['problematicos'] . ' registros con valores no estándar en la columna "activo".';
                echo '</div>';
                
                // Mostrar ejemplos de valores problemáticos
                $stmt = $pdo->query("
                    SELECT DISTINCT activo, COUNT(*) as cantidad 
                    FROM permiso_usuario 
                    WHERE activo NOT IN ('0', '1', 0, 1)
                    GROUP BY activo 
                    LIMIT 10
                ");
                $problematicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($problematicos)) {
                    echo '<h3>Ejemplos de valores problemáticos:</h3>';
                    echo '<table>';
                    echo '<tr><th>Valor</th><th>Cantidad</th><th>Tipo PHP</th></tr>';
                    foreach ($problematicos as $p) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($p['activo']) . '</td>';
                        echo '<td>' . $p['cantidad'] . '</td>';
                        echo '<td>' . gettype($p['activo']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }
            
            // 3. Scripts de corrección
            echo '<h2>🔧 Scripts de Corrección</h2>';
            
            if ($esTipoProblematico) {
                echo '<div class="code">';
                echo '<strong>Script SQL para corregir tipo de columna:</strong><br>';
                echo 'ALTER TABLE permiso_usuario MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;';
                echo '</div>';
            }
            
            if ($stats['problematicos'] > 0) {
                echo '<div class="code">';
                echo '<strong>Script SQL para normalizar datos:</strong><br>';
                echo 'UPDATE permiso_usuario SET activo = 1 WHERE activo NOT IN (0, 1);<br>';
                echo '-- O más conservador:<br>';
                echo 'UPDATE permiso_usuario SET activo = CASE WHEN activo = \'0\' OR activo = 0 THEN 0 ELSE 1 END;';
                echo '</div>';
            }
            
            // 4. Verificar la función to01
            echo '<h2>🧪 Prueba de la función to01</h2>';
            
            // Simular la función to01
            function to01_test($v) { 
                $v = strtolower(trim((string)$v)); 
                $result = in_array($v, ['1','true','on','si','sí','yes']) ? 1 : 0;
                return (int)$result;
            }
            
            $testValues = ['1', '0', 'true', 'false', 'on', 'off', 'si', 'no', '', null, 'yes', 'no'];
            
            echo '<table>';
            echo '<tr><th>Valor de entrada</th><th>Tipo PHP</th><th>Resultado to01</th><th>Tipo resultado</th></tr>';
            foreach ($testValues as $val) {
                $result = to01_test($val);
                echo '<tr>';
                echo '<td>' . htmlspecialchars(var_export($val, true)) . '</td>';
                echo '<td>' . gettype($val) . '</td>';
                echo '<td>' . $result . '</td>';
                echo '<td>' . gettype($result) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
        } catch (PDOException $e) {
            echo '<div class="error"><strong>❌ ERROR DE BASE DE DATOS:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="error"><strong>❌ ERROR:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <h2>💡 Próximos pasos recomendados</h2>
        <ol>
            <li><strong>Si hay problema de tipo de columna:</strong> Ejecuta el script SQL para cambiar a TINYINT(1)</li>
            <li><strong>Si hay datos problemáticos:</strong> Ejecuta el script de normalización</li>
            <li><strong>Reinicia el servidor web</strong> para asegurar que los cambios se apliquen</li>
            <li><strong>Prueba nuevamente</strong> la creación/edición de permisos</li>
        </ol>
        
        <div class="info">
            <strong>💡 Nota:</strong> Este diagnóstico te ayuda a identificar el problema específico con la columna 'activo' 
            que está causando el error "Data too long for column". Una vez corregido, el sistema de permisos debería funcionar correctamente.
        </div>
    </div>
</body>
</html>
