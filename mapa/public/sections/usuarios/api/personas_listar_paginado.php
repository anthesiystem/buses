<?php
// personas_listar_paginado.php - API para listar personas con paginación
ob_start(); // Iniciar buffer de salida para evitar warnings
header('Content-Type: application/json; charset=utf-8');

require_once '../../../../server/config.php';

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
    $offset = ($page - 1) * $rowsPerPage;
    
    // Contar total de registros
    $total = $pdo->query("SELECT COUNT(*) FROM personas p WHERE p.activo = 1")->fetchColumn();
    $totalPages = ceil($total / $rowsPerPage);
    
    // Consulta con LIMIT y OFFSET
    $stmt = $pdo->prepare("
        SELECT p.*, 
               d.descripcion as dependencia,
               e.descripcion as entidad
        FROM personas p 
        LEFT JOIN dependencias d ON p.Fk_dependencia = d.ID
        LEFT JOIN entidades e ON p.Fk_entidad = e.ID
        WHERE p.activo = 1
        ORDER BY p.ID DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
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
