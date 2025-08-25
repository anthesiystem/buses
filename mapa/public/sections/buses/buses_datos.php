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
  $sql  = "SELECT * FROM bus ORDER BY ID";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

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

  echo json_encode($rows);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
