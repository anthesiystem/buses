<?php
session_start();
require_once '../server/config.php';

// Debug de estadísticas de bitácora
echo "<h3>Debug Estadísticas Bitácora</h3>";

try {
    // Verificar tipos de acción existentes
    $tipos_sql = "SELECT Tipo_Accion, COUNT(*) as cantidad FROM bitacora GROUP BY Tipo_Accion ORDER BY cantidad DESC";
    $tipos_stmt = $pdo->prepare($tipos_sql);
    $tipos_stmt->execute();
    $tipos_result = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Tipos de Acción en la DB:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Tipo Acción</th><th>Cantidad</th></tr>";
    foreach ($tipos_result as $tipo) {
        echo "<tr><td>{$tipo['Tipo_Accion']}</td><td>{$tipo['cantidad']}</td></tr>";
    }
    echo "</table><br>";
    
    // Verificar específicamente ACCESO e INTERACCION
    $acceso_sql = "SELECT COUNT(*) as total FROM bitacora WHERE Tipo_Accion = 'ACCESO'";
    $acceso_stmt = $pdo->prepare($acceso_sql);
    $acceso_stmt->execute();
    $acceso_count = $acceso_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $interaccion_sql = "SELECT COUNT(*) as total FROM bitacora WHERE Tipo_Accion = 'INTERACCION'";
    $interaccion_stmt = $pdo->prepare($interaccion_sql);
    $interaccion_stmt->execute();
    $interaccion_count = $interaccion_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h4>Contadores específicos:</h4>";
    echo "<p><strong>ACCESO:</strong> $acceso_count registros</p>";
    echo "<p><strong>INTERACCION:</strong> $interaccion_count registros</p>";
    
    // Verificar las estadísticas calculadas como en bitacora.php
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Tipo_Accion = 'ACCESO' THEN 1 ELSE 0 END) as accesos_vista,
            SUM(CASE WHEN Tipo_Accion = 'INTERACCION' THEN 1 ELSE 0 END) as interacciones
        FROM bitacora b
        INNER JOIN usuario u ON u.ID = b.Fk_Usuario
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Estadísticas calculadas (como en bitacora.php):</h4>";
    echo "<p><strong>Total registros:</strong> {$stats['total']}</p>";
    echo "<p><strong>Accesos a Vista:</strong> {$stats['accesos_vista']}</p>";
    echo "<p><strong>Interacciones:</strong> {$stats['interacciones']}</p>";
    
    // Mostrar últimos 10 registros de ACCESO e INTERACCION
    echo "<h4>Últimos registros ACCESO:</h4>";
    $ultimos_acceso = $pdo->query("
        SELECT b.*, u.cuenta as usuario 
        FROM bitacora b 
        INNER JOIN usuario u ON u.ID = b.Fk_Usuario 
        WHERE b.Tipo_Accion = 'ACCESO' 
        ORDER BY b.Fecha_Accion DESC 
        LIMIT 5
    ");
    
    if ($ultimos_acceso->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Usuario</th><th>Descripción</th><th>Fecha</th></tr>";
        while ($row = $ultimos_acceso->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['usuario']}</td><td>{$row['Descripcion']}</td><td>{$row['Fecha_Accion']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros de ACCESO</p>";
    }
    
    echo "<h4>Últimos registros INTERACCION:</h4>";
    $ultimos_interaccion = $pdo->query("
        SELECT b.*, u.cuenta as usuario 
        FROM bitacora b 
        INNER JOIN usuario u ON u.ID = b.Fk_Usuario 
        WHERE b.Tipo_Accion = 'INTERACCION' 
        ORDER BY b.Fecha_Accion DESC 
        LIMIT 5
    ");
    
    if ($ultimos_interaccion->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Usuario</th><th>Descripción</th><th>Fecha</th></tr>";
        while ($row = $ultimos_interaccion->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['usuario']}</td><td>{$row['Descripcion']}</td><td>{$row['Fecha_Accion']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros de INTERACCION</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
