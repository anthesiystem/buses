<?php
session_start();
require_once '../server/config.php';

header('Content-Type: application/json');

try {
    // Verificar registros de ACCESO e INTERACCION
    $sql = "SELECT 
                Tipo_Accion,
                COUNT(*) as cantidad,
                MAX(Fecha_Accion) as ultima_fecha
            FROM bitacora 
            WHERE Tipo_Accion IN ('ACCESO', 'INTERACCION')
            GROUP BY Tipo_Accion";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // También obtener todos los tipos de acción para debug
    $sql_all = "SELECT DISTINCT Tipo_Accion, COUNT(*) as total 
                FROM bitacora 
                GROUP BY Tipo_Accion 
                ORDER BY total DESC";
    $stmt_all = $pdo->prepare($sql_all);
    $stmt_all->execute();
    $all_types = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular las estadísticas como en bitacora.php
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
    
    echo json_encode([
        'success' => true,
        'registros_especificos' => $result,
        'todos_los_tipos' => $all_types,
        'estadisticas_calculadas' => $stats
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
