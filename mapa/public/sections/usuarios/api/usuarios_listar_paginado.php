<?php
// usuarios_listar_paginado.php - API para listar usuarios con paginación
ob_start(); // Iniciar buffer de salida para evitar warnings
header('Content-Type: application/json; charset=utf-8');

require_once '../../../../server/config.php';

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
    $offset = ($page - 1) * $rowsPerPage;
    
    // Contar total de registros
    $total = $pdo->query("SELECT COUNT(*) FROM usuario u WHERE u.activo = 1")->fetchColumn();
    $totalPages = ceil($total / $rowsPerPage);
    
    // Consulta con LIMIT y OFFSET
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CONCAT(p.nombre, ' ', p.apaterno, ' ', p.amaterno) as persona
        FROM usuario u 
        LEFT JOIN personas p ON u.Fk_persona = p.ID
        WHERE u.activo = 1
        ORDER BY u.ID DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    $html = '';
    foreach ($data as $u) {
        $activo = $u['activo'] == '1' ? 'Sí' : 'No';
        $activoClass = $u['activo'] == '1' ? 'text-success' : 'text-muted';
        $btnToggleText = $u['activo'] == '1' ? 'Desactivar' : 'Activar';
        $btnToggleClass = $u['activo'] == '1' ? 'btn-outline-danger' : 'btn-outline-success';
        $btnToggleIcon = $u['activo'] == '1' ? 'user-slash' : 'user-check';
        
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
                <button class=\"btn btn-sm btn-outline-warning me-1\" 
                    onclick=\"resetPass({$u['ID']})\" 
                    title=\"Reset contraseña\">
                    <i class=\"fas fa-key\"></i>
                </button>
                <button class=\"btn btn-sm $btnToggleClass\" 
                    onclick=\"toggleUsuario({$u['ID']})\" 
                    title=\"$btnToggleText\">
                    <i class=\"fas fa-$btnToggleIcon\"></i>
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
