<?php
// permisos_listar_paginado.php - API para listar permisos con paginación
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../server/config.php';

try {
    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $rowsPerPage = max(1, min(100, intval($_GET['rowsPerPage'] ?? 10)));
    $offset = ($page - 1) * $rowsPerPage;
    
    // Parámetros de filtro
    $filtroUsuario = trim($_GET['usuario'] ?? '');
    $filtroModulo = trim($_GET['modulo'] ?? '');
    $filtroEntidad = trim($_GET['entidad'] ?? '');
    $filtroBus = trim($_GET['bus'] ?? '');
    
    // Construir condición WHERE
    $whereConditions = [];
    $params = [];
    
    if (!empty($filtroUsuario)) {
        $whereConditions[] = "p.Fk_usuario = ?";
        $params[] = $filtroUsuario;
    }
    
    if (!empty($filtroModulo)) {
        $whereConditions[] = "p.Fk_modulo = ?";
        $params[] = $filtroModulo;
    }
    
    if (!empty($filtroEntidad)) {
        $whereConditions[] = "p.FK_entidad = ?";
        $params[] = $filtroEntidad;
    }
    
    if (!empty($filtroBus)) {
        $whereConditions[] = "p.FK_bus = ?";
        $params[] = $filtroBus;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total 
                 FROM permiso_usuario p 
                 LEFT JOIN usuario u ON p.Fk_usuario = u.ID
                 LEFT JOIN modulo m ON p.Fk_modulo = m.ID
                 LEFT JOIN entidad e ON p.FK_entidad = e.ID
                 LEFT JOIN bus b ON p.FK_bus = b.ID
                 $whereClause";
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $rowsPerPage);
    
    // Obtener registros con paginación
    $sql = "SELECT p.*, 
                   u.cuenta as usuario,
                   m.descripcion as modulo,
                   e.descripcion as entidad,
                   b.descripcion as bus
            FROM permiso_usuario p 
            LEFT JOIN usuario u ON p.Fk_usuario = u.ID
            LEFT JOIN modulo m ON p.Fk_modulo = m.ID
            LEFT JOIN entidad e ON p.FK_entidad = e.ID
            LEFT JOIN bus b ON p.FK_bus = b.ID
            $whereClause
            ORDER BY p.ID DESC
            LIMIT $rowsPerPage OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $p) {
        $activo = $p['activo'] == '1' ? 'Sí' : 'No';
        $activoClass = $p['activo'] == '1' ? 'text-success' : 'text-muted';
        $btnToggleText = $p['activo'] == '1' ? 'Desactivar' : 'Activar';
        $btnToggleClass = $p['activo'] == '1' ? 'btn-outline-secondary' : 'btn-outline-success';
        
        $permisoJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
        
        // Convertir acción READ a Leer para mostrar
        $accionMostrar = ($p['accion'] === 'READ' || $p['accion'] === 'READ') ? 'Leer' : ($p['accion'] ?? '');
        
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
                    onclick=\"abrirModalPermiso(JSON.parse(this.dataset.permiso))\" 
                    title=\"Editar\">
                    <i class=\"fas fa-edit\"></i>
                </button>
                <button class=\"btn btn-sm $btnToggleClass\" 
                    onclick='togglePermiso({$p['ID']})' 
                    title=\"$btnToggleText\">
                    <i class=\"fas fa-" . ($p['activo'] == '1' ? 'eye-slash' : 'eye') . "\"></i>
                </button>
            </td>
        </tr>";
    }
    
    // Respuesta JSON
    echo json_encode([
        'html' => $html,
        'total' => intval($total),
        'totalPages' => intval($totalPages),
        'currentPage' => $page,
        'rowsPerPage' => $rowsPerPage
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>
