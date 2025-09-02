<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

// Obtener filtros
$usuario = $_GET['usuario'] ?? '';
$modulo = $_GET['modulo'] ?? '';
$entidad = $_GET['entidad'] ?? '';
$bus = $_GET['bus'] ?? '';

try {
    // Query principal para obtener permisos agrupados
    $sql = "SELECT 
                pu.group_token,
                pu.Fk_usuario, 
                pu.Fk_modulo, 
                pu.accion, 
                pu.activo,
                u.cuenta AS usuario,
                mo.descripcion AS modulo,
                COUNT(*) as total_combinaciones,
                GROUP_CONCAT(
                    CONCAT(
                        COALESCE(en.descripcion, 'Todas'), 
                        ' × ', 
                        COALESCE(bu.descripcion, 'Todos'),
                        IF(pu.activo = 1, ' ✓', ' ✗')
                    ) 
                    ORDER BY en.descripcion, bu.descripcion 
                    SEPARATOR ' | '
                ) as combinaciones_texto
            FROM permiso_usuario pu
            JOIN usuario u ON u.ID = pu.Fk_usuario
            JOIN modulo mo ON mo.ID = pu.Fk_modulo
            LEFT JOIN entidad en ON en.ID = pu.FK_entidad
            LEFT JOIN bus bu ON bu.ID = pu.FK_bus
            WHERE 1";
    
    $params = [];
    
    if ($usuario !== '') {
        $sql .= " AND pu.Fk_usuario = ?";
        $params[] = (int)$usuario;
    }
    
    if ($modulo !== '') {
        $sql .= " AND pu.Fk_modulo = ?";
        $params[] = (int)$modulo;
    }
    
    if ($entidad !== '') {
        if ($entidad === 'ALL') {
            $sql .= " AND pu.FK_entidad IS NULL";
        } else {
            $sql .= " AND pu.FK_entidad = ?";
            $params[] = $entidad;
        }
    }
    
    if ($bus !== '') {
        if ($bus === 'ALL') {
            $sql .= " AND pu.FK_bus IS NULL";
        } else {
            $sql .= " AND pu.FK_bus = ?";
            $params[] = (int)$bus;
        }
    }
    
    $sql .= " GROUP BY pu.group_token, pu.Fk_usuario, pu.Fk_modulo, pu.accion, pu.activo
              ORDER BY u.cuenta, mo.descripcion, pu.accion";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada grupo, obtener el detalle de combinaciones
    foreach ($grupos as &$grupo) {
        if ($grupo['group_token']) {
            $detailSql = "SELECT 
                            pu.FK_entidad,
                            pu.FK_bus,
                            pu.activo,
                            COALESCE(en.descripcion, 'Todas') as entidad_nombre,
                            COALESCE(bu.descripcion, 'Todos') as bus_nombre
                          FROM permiso_usuario pu
                          LEFT JOIN entidad en ON en.ID = pu.FK_entidad
                          LEFT JOIN bus bu ON bu.ID = pu.FK_bus
                          WHERE pu.group_token = ?
                          ORDER BY entidad_nombre, bus_nombre";
            
            $detailStmt = $pdo->prepare($detailSql);
            $detailStmt->execute([$grupo['group_token']]);
            $grupo['combos'] = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas
            $activos = array_filter($grupo['combos'], function($c) { return $c['activo'] == 1; });
            $grupo['activos'] = count($activos);
            $grupo['inactivos'] = $grupo['total_combinaciones'] - $grupo['activos'];
        } else {
            // Permisos sin group_token (legado)
            $grupo['combos'] = [];
            $grupo['activos'] = $grupo['activo'] ? 1 : 0;
            $grupo['inactivos'] = $grupo['activo'] ? 0 : 1;
        }
    }
    
    echo json_encode($grupos, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
