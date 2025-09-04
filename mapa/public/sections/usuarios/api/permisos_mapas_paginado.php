<?php
// permisos_mapas_paginado.php - API para listar permisos de mapas con paginación
ob_start(); // Iniciar buffer de salida para evitar warnings
header('Content-Type: application/json; charset=utf-8');

require_once '../../../../server/config.php';

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
    $offset = ($page - 1) * $rowsPerPage;
    
    // Filtros
    $usuario = $_GET['usuario'] ?? '';
    $modulo = $_GET['modulo'] ?? '';
    $entidad = $_GET['entidad'] ?? '';
    $bus = $_GET['bus'] ?? '';
    
    // Primero obtener los IDs de módulos de mapa
    $modulosMapaStmt = $pdo->prepare("SELECT ID FROM modulo WHERE activo = 1 AND descripcion IN ('mapa_bus', 'mapa_general')");
    $modulosMapaStmt->execute();
    $modulosMapaIds = $modulosMapaStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($modulosMapaIds)) {
        // Si no hay módulos de mapa, devolver respuesta vacía
        ob_clean();
        echo json_encode([
            'html' => '',
            'total' => 0,
            'totalPages' => 0
        ]);
        exit;
    }
    
    $modulosMapaPlaceholders = str_repeat('?,', count($modulosMapaIds) - 1) . '?';
    
    // Construir WHERE clause
    $whereConditions = ["p.activo = 1", "p.Fk_modulo IN ($modulosMapaPlaceholders)"];
    $params = $modulosMapaIds; // Empezar con los IDs de módulos de mapa
    
    if (!empty($usuario)) {
        $whereConditions[] = "u.cuenta LIKE ?";
        $params[] = "%$usuario%";
    }
    
    if (!empty($modulo)) {
        $whereConditions[] = "m.descripcion LIKE ?";
        $params[] = "%$modulo%";
    }
    
    if (!empty($entidad)) {
        $whereConditions[] = "e.descripcion LIKE ?";
        $params[] = "%$entidad%";
    }
    
    if (!empty($bus)) {
        $whereConditions[] = "b.numero LIKE ?";
        $params[] = "%$bus%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total de registros
    $countQuery = "
        SELECT COUNT(*) 
        FROM permiso_usuario p
        LEFT JOIN usuario u ON p.Fk_usuario = u.ID
        LEFT JOIN modulo m ON p.Fk_modulo = m.ID
        LEFT JOIN entidad e ON p.FK_entidad = e.ID
        LEFT JOIN bus b ON p.FK_bus = b.ID
        WHERE $whereClause
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $rowsPerPage);
    
    // Consulta principal con LIMIT y OFFSET
    $query = "
        SELECT p.*, 
               u.cuenta as usuario,
               m.descripcion as modulo,
               e.descripcion as entidad,
               b.descripcion as bus
        FROM permiso_usuario p
        LEFT JOIN usuario u ON p.Fk_usuario = u.ID
        LEFT JOIN modulo m ON p.Fk_modulo = m.ID
        LEFT JOIN entidad e ON p.FK_entidad = e.ID
        LEFT JOIN bus b ON p.FK_bus = b.ID
        WHERE $whereClause
        ORDER BY p.ID DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$rowsPerPage, $offset]));
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $p) {
        $activo = $p['activo'] == '1' ? 'Sí' : 'No';
        $activoClass = $p['activo'] == '1' ? 'text-success' : 'text-muted';
        $btnToggleText = $p['activo'] == '1' ? 'Desactivar' : 'Activar';
        $btnToggleClass = $p['activo'] == '1' ? 'btn-outline-secondary' : 'btn-outline-success';
        
        // Convertir acción para mostrar
        $accionMostrar = ($p['accion'] === 'READ' || $p['accion'] === 'READ') ? 'Leer' : ($p['accion'] ?? '');
        
        $permisoJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
        
        $html .= "<tr>
            <td>{$p['ID']}</td>
            <td class=\"text-start\">" . ($p['usuario'] ?? '') . "</td>
            <td class=\"text-start\">" . ($p['modulo'] ?? '') . "</td>
            <td class=\"text-start\">" . ($p['entidad'] ?? 'Todas') . "</td>
            <td class=\"text-start\">" . ($p['bus'] ?? 'Todos') . "</td>
            <td>$accionMostrar</td>
            <td class=\"$activoClass\">$activo</td>
            <td>
                <button class=\"btn btn-sm btn-outline-primary me-1\" 
                    data-permiso='$permisoJson' 
                    onclick=\"abrirModalPermisoMapa(JSON.parse(this.dataset.permiso))\" 
                    title=\"Editar\">
                    <i class=\"fas fa-edit\"></i>
                </button>
                <button class=\"btn btn-sm $btnToggleClass\" 
                    onclick='togglePermisoMapa({$p['ID']})' 
                    title=\"$btnToggleText\">
                    <i class=\"fas fa-" . ($p['activo'] == '1' ? 'eye-slash' : 'eye') . "\"></i>
                </button>
            </td>
        </tr>";
    }
    
    // Respuesta JSON
    ob_clean(); // Limpiar cualquier output anterior
    echo json_encode([
        'html' => $html,
        'total' => (int)$total,
        'totalPages' => (int)$totalPages
    ]);
    
} catch (Exception $e) {
    ob_clean(); // Limpiar cualquier output anterior
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>
