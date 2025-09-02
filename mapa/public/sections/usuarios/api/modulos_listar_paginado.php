<?php
// modulos_listar_paginado.php - API para listar módulos con paginación
header('Content-Type: application/json; charset=utf-8');

require_once '../../../server/config.php';

try {
    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $rowsPerPage = max(1, min(100, intval($_GET['rowsPerPage'] ?? 10)));
    $offset = ($page - 1) * $rowsPerPage;
    
    // Parámetro de búsqueda
    $buscar = trim($_GET['buscar'] ?? '');
    
    // Construir condición WHERE
    $whereConditions = [];
    $params = [];
    
    if (!empty($buscar)) {
        $whereConditions[] = "descripcion LIKE ?";
        $params[] = "%$buscar%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM modulos $whereClause";
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $rowsPerPage);
    
    // Obtener registros con paginación
    $sql = "SELECT * FROM modulos 
            $whereClause
            ORDER BY ID DESC
            LIMIT $rowsPerPage OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $m) {
        $activo = ($m['activo'] == 1 || $m['activo'] == '1') ? 'Sí' : 'No';
        $activoClass = ($m['activo'] == 1 || $m['activo'] == '1') ? 'text-success' : 'text-muted';
        $btnToggleText = ($m['activo'] == 1 || $m['activo'] == '1') ? 'Desactivar' : 'Activar';
        $btnToggleClass = ($m['activo'] == 1 || $m['activo'] == '1') ? 'btn-outline-secondary' : 'btn-outline-success';
        
        $moduloJson = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');
        
        $html .= "<tr>
            <td>{$m['ID']}</td>
            <td class=\"text-start\">{$m['descripcion']}</td>
            <td class=\"$activoClass\">$activo</td>
            <td>
                <button class=\"btn btn-sm btn-outline-primary me-1\" 
                    data-modulo='$moduloJson' 
                    onclick=\"abrirModalModulo(JSON.parse(this.dataset.modulo))\" 
                    title=\"Editar\">
                    <i class=\"fas fa-edit\"></i>
                </button>
                <button class=\"btn btn-sm $btnToggleClass\" 
                    onclick='toggleModulo({$m['ID']})' 
                    title=\"$btnToggleText\">
                    <i class=\"fas fa-" . (($m['activo'] == 1 || $m['activo'] == '1') ? 'eye-slash' : 'eye') . "\"></i>
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
