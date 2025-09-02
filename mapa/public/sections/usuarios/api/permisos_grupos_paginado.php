<?php
// permisos_grupos_paginado.php - API para listar grupos de permisos con paginación
header('Content-Type: application/json; charset=utf-8');

require_once '../../../server/config.php';

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
    $buscar = trim($_GET['buscar'] ?? '');
    
    // Construir condición WHERE para el subquery de agrupación
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
        if ($filtroEntidad === 'ALL') {
            $whereConditions[] = "p.FK_entidad IS NULL";
        } else {
            $whereConditions[] = "p.FK_entidad = ?";
            $params[] = $filtroEntidad;
        }
    }
    
    if (!empty($filtroBus)) {
        if ($filtroBus === 'ALL') {
            $whereConditions[] = "p.FK_bus IS NULL";
        } else {
            $whereConditions[] = "p.FK_bus = ?";
            $params[] = $filtroBus;
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Query base para obtener grupos únicos
    $baseQuery = "
        SELECT 
            p.Fk_usuario,
            p.Fk_modulo,
            p.accion,
            COALESCE(p.group_token, CONCAT('individual_', p.ID)) as group_token,
            u.cuenta as usuario,
            m.descripcion as modulo,
            MIN(p.ID) as min_id,
            COUNT(*) as combo_count,
            SUM(CASE WHEN p.activo = 1 THEN 1 ELSE 0 END) as activos_count,
            p.activo as primer_activo
        FROM permisos p
        LEFT JOIN usuarios u ON p.Fk_usuario = u.ID
        LEFT JOIN modulos m ON p.Fk_modulo = m.ID
        $whereClause
        GROUP BY p.Fk_usuario, p.Fk_modulo, p.accion, COALESCE(p.group_token, CONCAT('individual_', p.ID))
    ";
    
    // Aplicar filtro de búsqueda si existe
    $havingClause = '';
    if (!empty($buscar)) {
        $havingClause = "HAVING (u.cuenta LIKE ? OR m.descripcion LIKE ?)";
        $params = array_merge($params, ["%$buscar%", "%$buscar%"]);
    }
    
    // Contar total de grupos
    $countQuery = "SELECT COUNT(*) as total FROM ($baseQuery $havingClause) as grupos";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $rowsPerPage);
    
    // Obtener grupos con paginación
    $finalQuery = "$baseQuery $havingClause ORDER BY min_id DESC LIMIT $rowsPerPage OFFSET $offset";
    $stmt = $pdo->prepare($finalQuery);
    $stmt->execute($params);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada grupo, obtener sus combinaciones detalladas
    $gruposConCombos = [];
    foreach ($grupos as $grupo) {
        // Obtener combinaciones del grupo
        $comboSql = "
            SELECT p.*, 
                   e.descripcion as entidad_nombre,
                   b.descripcion as bus_nombre
            FROM permisos p
            LEFT JOIN entidades e ON p.FK_entidad = e.ID
            LEFT JOIN bus b ON p.FK_bus = b.ID
            WHERE p.Fk_usuario = ? AND p.Fk_modulo = ? AND p.accion = ?
        ";
        
        $comboParams = [$grupo['Fk_usuario'], $grupo['Fk_modulo'], $grupo['accion']];
        
        // Si tiene group_token, filtrar por él
        if (!str_starts_with($grupo['group_token'], 'individual_')) {
            $comboSql .= " AND p.group_token = ?";
            $comboParams[] = $grupo['group_token'];
        } else {
            // Es individual, obtener solo ese registro específico
            $individualId = str_replace('individual_', '', $grupo['group_token']);
            $comboSql .= " AND p.ID = ?";
            $comboParams[] = $individualId;
        }
        
        $comboStmt = $pdo->prepare($comboSql);
        $comboStmt->execute($comboParams);
        $combos = $comboStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $grupo['combos'] = $combos;
        $gruposConCombos[] = $grupo;
    }
    
    // Generar HTML
    $html = '';
    foreach ($gruposConCombos as $grupo) {
        $combos = $grupo['combos'] ?? [];
        $token = $grupo['group_token'];
        
        // Determinar estado del grupo
        $activosCount = intval($grupo['activos_count']);
        $totalCombos = intval($grupo['combo_count']);
        
        if ($activosCount === 0) {
            $estadoBadge = '<span class="badge bg-danger">Inactivo</span>';
        } elseif ($activosCount === $totalCombos) {
            $estadoBadge = '<span class="badge bg-success">Activo</span>';
        } else {
            $estadoBadge = "<span class=\"badge bg-warning\">Parcial ($activosCount/$totalCombos)</span>";
        }
        
        // Generar badges de combinaciones
        $combosBadges = '';
        if (!empty($combos)) {
            $maxBadges = 3; // Limitar número de badges mostrados
            $badgesCount = 0;
            
            foreach ($combos as $combo) {
                if ($badgesCount >= $maxBadges) {
                    $remaining = count($combos) - $maxBadges;
                    $combosBadges .= "<span class=\"badge bg-secondary combo-badge me-1\">+$remaining más</span>";
                    break;
                }
                
                $entNombre = $combo['entidad_nombre'] ?: 'Todas';
                $busNombre = $combo['bus_nombre'] ?: 'Todos';
                $estadoClass = $combo['activo'] == 1 ? 'success' : 'secondary';
                $estadoIcon = $combo['activo'] == 1 ? '✓' : '×';
                
                $combosBadges .= "<span class=\"badge bg-$estadoClass combo-badge me-1\" 
                                        title=\"Estado: " . ($combo['activo'] == 1 ? 'Activo' : 'Inactivo') . "\">
                                    $estadoIcon $entNombre/$busNombre
                                  </span>";
                $badgesCount++;
            }
        } else {
            $combosBadges = '<span class="text-muted">-</span>';
        }
        
        $html .= "<tr data-token=\"$token\">
            <td><strong>{$grupo['usuario']}</strong></td>
            <td><code>{$grupo['modulo']}</code></td>
            <td><span class=\"badge bg-primary\">" . ($grupo['accion'] ?: 'General') . "</span></td>
            <td>$estadoBadge</td>
            <td class=\"col-sm-hide\">$combosBadges</td>
            <td class=\"col-sm-hide\"><small class=\"text-muted\">" . substr($token, 0, 8) . "...</small></td>
            <td class=\"actions\">
                <div class=\"btn-group\" role=\"group\">
                    <button class=\"btn btn-sm btn-outline-primary\" onclick=\"editarLote('$token')\" title=\"Editar\">
                        <i class=\"fas fa-edit\"></i> <span class=\"text\">Editar</span>
                    </button>
                    <button class=\"btn btn-sm btn-outline-warning\" onclick=\"duplicarLote('$token')\" title=\"Duplicar\">
                        <i class=\"fas fa-copy\"></i> <span class=\"text\">Duplicar</span>
                    </button>
                </div>
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
