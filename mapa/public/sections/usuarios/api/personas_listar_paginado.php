<?php
// personas_listar_paginado.php - API para listar personas con paginación
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
        $whereConditions[] = "(p.nombre LIKE ? OR p.apaterno LIKE ? OR p.amaterno LIKE ? OR p.correo LIKE ? OR p.numero_empleado LIKE ?)";
        $searchTerm = "%$buscar%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total 
                 FROM personas p 
                 LEFT JOIN dependencias d ON p.Fk_dependencia = d.ID
                 LEFT JOIN entidades e ON p.Fk_entidad = e.ID
                 $whereClause";
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $rowsPerPage);
    
    // Obtener registros con paginación
    $sql = "SELECT p.*, 
                   d.descripcion as dependencia,
                   e.descripcion as entidad
            FROM personas p 
            LEFT JOIN dependencias d ON p.Fk_dependencia = d.ID
            LEFT JOIN entidades e ON p.Fk_entidad = e.ID
            $whereClause
            ORDER BY p.ID DESC
            LIMIT $rowsPerPage OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $p) {
        $nombreCompleto = trim(($p['nombre'] ?? '') . ' ' . ($p['apaterno'] ?? '') . ' ' . ($p['amaterno'] ?? ''));
        $activo = $p['activo'] == '1' ? 'Sí' : 'No';
        $activoClass = $p['activo'] == '1' ? 'text-success' : 'text-muted';
        $btnToggleText = $p['activo'] == '1' ? 'Desactivar' : 'Activar';
        $btnToggleClass = $p['activo'] == '1' ? 'btn-outline-secondary' : 'btn-outline-success';
        
        $personaJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
        
        $html .= "<tr>
            <td>{$p['ID']}</td>
            <td class=\"text-start\">$nombreCompleto</td>
            <td>" . ($p['numero_empleado'] ?? '') . "</td>
            <td>" . ($p['correo'] ?? '') . "</td>
            <td class=\"text-start\">" . ($p['dependencia'] ?? '') . "</td>
            <td class=\"text-start\">" . ($p['entidad'] ?? '') . "</td>
            <td class=\"$activoClass\">$activo</td>
            <td>
                <button class=\"btn btn-sm btn-outline-primary me-1\" 
                    data-persona='$personaJson' 
                    onclick=\"abrirModalPersona(JSON.parse(this.dataset.persona))\" 
                    title=\"Editar\">
                    <i class=\"fas fa-edit\"></i>
                </button>
                <button class=\"btn btn-sm $btnToggleClass\" 
                    onclick='togglePersona({$p['ID']})' 
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
