<?php
// /final/mapa/server/mapabus/entidades_permitidas.php
require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();
header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$nivel  = (int)($_SESSION['nivel'] ?? 0);
$busId  = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : null;

// Resolver ID del módulo "mapa_bus" (fallback a 9)
$modId = 9;
try {
  $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_bus' LIMIT 1");
  if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) $modId = (int)$row['ID'];
} catch (\Throwable $e) {}

// Catálogo de entidades activas
$rowsEnt = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$allIds  = array_map('intval', array_column($rowsEnt, 'ID'));

if ($nivel >= 3) {
  echo json_encode(['permitidas' => $allIds], JSON_UNESCAPED_UNICODE);
  exit;
}

// Une TODAS las filas READ aplicables (comodines de bus/acción)
$cond   = "Fk_usuario = :u AND Fk_modulo = :m AND activo = 1 AND (accion IS NULL OR accion = 'READ')";
$params = [':u' => $userId, ':m' => $modId];
if ($busId === null) {
  $cond .= " AND (FK_bus IS NULL OR FK_bus = 0)";
} else {
  $cond .= " AND (FK_bus IS NULL OR FK_bus = 0 OR FK_bus = :b)";
  $params[':b'] = $busId;
}

$st = $pdo->prepare("SELECT FK_entidad FROM permiso_usuario WHERE $cond");
$st->execute($params);
$perms = $st->fetchAll(PDO::FETCH_ASSOC);

$ids = [];
$todas = false;
foreach ($perms as $p) {
  $val = $p['FK_entidad'];
  if ($val === null) { $todas = true; break; }

  $tok = trim((string)$val);
  $up  = strtoupper($tok);
  if ($tok === '0' || $tok === '*' || $up === 'ALL' || $up === 'TODAS') { $todas = true; break; }

  foreach (preg_split('/\s*,\s*/', $tok, -1, PREG_SPLIT_NO_EMPTY) as $t) {
    if (ctype_digit($t)) {
      $id = (int)$t;
      if (in_array($id, $allIds, true)) $ids[] = $id;
    } else {
      // (Opcional) Si alguna vez guardaron nombres exactos
      foreach ($rowsEnt as $r) {
        if (mb_strtoupper($r['descripcion'], 'UTF-8') === mb_strtoupper($t, 'UTF-8')) {
          $ids[] = (int)$r['ID']; break;
        }
      }
    }
  }
}
$permitidas = $todas ? $allIds : array_values(array_unique($ids));

echo json_encode(['permitidas' => $permitidas], JSON_UNESCAPED_UNICODE);
