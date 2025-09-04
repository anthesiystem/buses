<?php
// modulos_listar_paginado.php - API para listar módulos con paginación
header('Content-Type: application/json; charset=utf-8');

require_once '../../../server/config.php';

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
    $offset = ($page - 1) * $rowsPerPage;
    
    // Contar total de registros
    $total = $pdo->query("SELECT COUNT(*) FROM modulo WHERE activo = 1")->fetchColumn();
    $totalPages = ceil($total / $rowsPerPage);
    
    // Consulta con LIMIT y OFFSET
    $stmt = $pdo->prepare("
        SELECT * FROM modulo 
        WHERE activo = 1
        ORDER BY ID DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
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
        'total' => (int)$total,
        'totalPages' => (int)$totalPages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>
