<?php
require_once '../../../server/config.php';
header('Content-Type: application/json');

function soloNombre($p) {
  if (!$p) return null;
  $p = str_replace('\\', '/', $p);
  $base = basename(trim($p));
  return $base ?: null;
}

try {
  // Parámetros de paginación
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = max(5, min(50, (int)($_GET['limit'] ?? 10))); // Entre 5 y 50 registros
  $offset = ($page - 1) * $limit;
  
  // Búsqueda opcional
  $search = trim($_GET['search'] ?? '');
  $whereClause = '';
  $params = [];
  
  if (!empty($search)) {
    $whereClause = ' WHERE descripcion LIKE ?';
    $params[] = '%' . $search . '%';
  }
  
  // Contar total de registros
  $countSql = "SELECT COUNT(*) FROM bus" . $whereClause;
  $stmt = $pdo->prepare($countSql);
  $stmt->execute($params);
  $totalRecords = (int)$stmt->fetchColumn();
  
  // Obtener registros paginados
  $sql = "SELECT * FROM bus" . $whereClause . " ORDER BY ID LIMIT ? OFFSET ?";
  $params[] = $limit;
  $params[] = $offset;
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Detecta base pública hasta '/public/'
  $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/';
  $needle    = '/public/';
  $pos       = strpos($scriptDir, $needle);
  $publicBase = ($pos !== false)
    ? substr($scriptDir, 0, $pos + strlen($needle)) // e.g. "/final/mapa/public/"
    : $scriptDir;

  $ICONS_BASE = $publicBase . 'icons/';

  foreach ($rows as &$r) {
    $name            = soloNombre($r['imagen'] ?? '');
    $r['imagen']     = $name; // solo nombre
    $r['imagen_url'] = $ICONS_BASE . ($name ?: '_placeholder.png'); // URL absoluta correcta
  }
  
  // Calcular información de paginación
  $totalPages = ceil($totalRecords / $limit);
  
  $response = [
    'data' => $rows,
    'pagination' => [
      'current_page' => $page,
      'per_page' => $limit,
      'total_records' => $totalRecords,
      'total_pages' => $totalPages,
      'has_previous' => $page > 1,
      'has_next' => $page < $totalPages,
      'search' => $search
    ]
  ];

  echo json_encode($response);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
