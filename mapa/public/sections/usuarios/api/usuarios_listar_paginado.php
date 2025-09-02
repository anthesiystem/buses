<?php
// usuarios_listar_paginado.php - API para listar usuarios con paginación
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
        $whereConditions[] = "(u.cuenta LIKE ? OR CONCAT(p.nombre, ' ', p.apaterno, ' ', p.amaterno) LIKE ?)";
        $searchTerm = "%$buscar%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total 
                 FROM usuarios u 
                 LEFT JOIN personas p ON u.Fk_persona = p.ID
                 $whereClause";
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $rowsPerPage);
    
    // Obtener registros con paginación
    $sql = "SELECT u.*, 
                   CONCAT(p.nombre, ' ', p.apaterno, ' ', p.amaterno) as persona
            FROM usuarios u 
            LEFT JOIN personas p ON u.Fk_persona = p.ID
            $whereClause
            ORDER BY u.ID DESC
            LIMIT $rowsPerPage OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $u) {
        $activo = $u['activo'] == '1' ? 'Sí' : 'No';
        $activoClass = $u['activo'] == '1' ? 'text-success' : 'text-muted';
        
        // Definir niveles de usuario
        $nivelesTexto = [
            0 => 'Enlace externo (0)',
            1 => 'Enlace local (1)', 
            2 => 'General (2)',
            3 => 'Admin (3)',
            4 => 'Supersu (4)'
        ];
        $nivelTexto = $nivelesTexto[$u['nivel']] ?? $u['nivel'];
        
        $usuarioJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
        
        $html .= "<tr>
            <td>{$u['ID']}</td>
            <td class=\"text-start\">{$u['cuenta']}</td>
            <td>$nivelTexto</td>
            <td class=\"text-start\">" . ($u['persona'] ?? '') . "</td>
            <td class=\"$activoClass\">$activo</td>
            <td>
                <button class=\"btn btn-sm btn-outline-primary me-1\" 
                    data-usuario='$usuarioJson' 
                    onclick=\"abrirModalUsuario(JSON.parse(this.dataset.usuario))\" 
                    title=\"Editar\">
                    <i class=\"fas fa-edit\"></i>
                </button>
                <button class=\"btn btn-sm btn-outline-warning\" 
                    onclick=\"resetPass({$u['ID']})\" 
                    title=\"Reset contraseña\">
                    <i class=\"fas fa-key\"></i>
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
