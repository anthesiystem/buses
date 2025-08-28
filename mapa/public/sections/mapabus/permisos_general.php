<?php
// Debe incluirse ANTES de renderizar la vista que usa los permisos.
require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();
require_once __DIR__ . '/../../../server/acl.php';

// ---------------------------------------------------------
// 0) Resolver ID del módulo "mapa_general" (fallback a 10)
// ---------------------------------------------------------
$modId = 10;
try {
  $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
  if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
    $modId = (int)$row['ID'];
  }
} catch (\Throwable $e) { /* ignorar */ }

// ---------------------------------------------------------
// 1) Datos base de sesión
// ---------------------------------------------------------
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$nivel  = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);

// Catálogos activos (para validar IDs y nombres)
$rowsEnt = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$rowsBus = $pdo->query("SELECT ID, descripcion FROM bus     WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);

$allEntIds = array_map('intval', array_column($rowsEnt, 'ID'));
$allBusIds = array_map('intval', array_column($rowsBus, 'ID'));

$entNameById = []; foreach ($rowsEnt as $r) $entNameById[(int)$r['ID']] = $r['descripcion'];
$busNameById = []; foreach ($rowsBus as $r) $busNameById[(int)$r['ID']] = $r['descripcion'];

// ---------------------------------------------------------
// 2) Reglas de permisos
//    - Nivel >= 3 => acceso total (todas entidades y todos los buses)
//    - Nivel < 3  => leer de permiso_usuario (accion NULL o 'READ') para este módulo
//      • ENTIDADES: cualquier fila concede entidad (no filtramos por FK_bus).
//      • BUSES:     se toman los FK_bus explícitos (o comodines).
// ---------------------------------------------------------
$entPermitidas = [];
$busPermitidos = [];

if ($nivel >= 3) {
  $entPermitidas = $allEntIds;
  $busPermitidos = $allBusIds;
} else {
  // ENTIDADES
  $cond  = "Fk_usuario = :u AND Fk_modulo = :m AND activo = 1 AND (accion IS NULL OR accion = 'READ')";
  $stEnt = $pdo->prepare("SELECT FK_entidad FROM permiso_usuario WHERE $cond");
  $stEnt->execute([':u' => $userId, ':m' => $modId]);

  $ids = []; $todas = false;
  foreach ($stEnt->fetchAll(PDO::FETCH_ASSOC) as $p) {
    $val = $p['FK_entidad'];
    if ($val === null) { $todas = true; break; }

    $tok = trim((string)$val);
    $up  = mb_strtoupper($tok, 'UTF-8');
    if ($tok === '0' || $tok === '*' || $up === 'ALL' || $up === 'TODAS') { $todas = true; break; }

    foreach (preg_split('/\s*,\s*/', $tok, -1, PREG_SPLIT_NO_EMPTY) as $t) {
      if (ctype_digit($t)) {
        $id = (int)$t;
        if (in_array($id, $allEntIds, true)) $ids[] = $id;
      } else {
        // Permiten guardar por nombre exacto en algunos casos
        $needle = mb_strtoupper($t, 'UTF-8');
        foreach ($rowsEnt as $r) {
          if (mb_strtoupper($r['descripcion'], 'UTF-8') === $needle) { $ids[] = (int)$r['ID']; break; }
        }
      }
    }
  }
  $entPermitidas = $todas ? $allEntIds : array_values(array_unique($ids));

  // BUSES
  $stBus = $pdo->prepare("SELECT FK_bus FROM permiso_usuario WHERE $cond");
  $stBus->execute([':u' => $userId, ':m' => $modId]);

  $bids = []; $todos = false;
  foreach ($stBus->fetchAll(PDO::FETCH_ASSOC) as $p) {
    $val = $p['FK_bus'];
    if ($val === null) { $todos = true; break; }

    $tok = trim((string)$val);
    $up  = mb_strtoupper($tok, 'UTF-8');
    if ($tok === '0' || $tok === '*' || $up === 'ALL' || $up === 'TODOS')) { $todos = true; break; }

    foreach (preg_split('/\s*,\s*/', $tok, -1, PREG_SPLIT_NO_EMPTY) as $t) {
      if (ctype_digit($t)) {
        $id = (int)$t;
        if (in_array($id, $allBusIds, true)) $bids[] = $id;
      } else {
        $needle = mb_strtoupper($t, 'UTF-8');
        foreach ($rowsBus as $r) {
          if (mb_strtoupper($r['descripcion'], 'UTF-8') === $needle) { $bids[] = (int)$r['ID']; break; }
        }
      }
    }
  }
  $busPermitidos = $todos ? $allBusIds : array_values(array_unique($bids));
}

// ---------------------------------------------------------
// 3) Debug opcional: ?debug=permisos
// ---------------------------------------------------------
if (isset($_GET['debug']) && $_GET['debug'] === 'permisos') {
  header('Content-Type: text/html; charset=utf-8');
  echo "<h2>Debug permisos — mapa_general</h2>";
  echo "<p><b>Usuario:</b> {$userId} | <b>Nivel:</b> {$nivel}</p>";

  echo "<h3>Entidades permitidas (".count($entPermitidas).")</h3>";
  if ($entPermitidas) {
    echo "<ul>";
    foreach ($entPermitidas as $id) {
      $nom = htmlspecialchars($entNameById[$id] ?? "(ID {$id})", ENT_QUOTES, 'UTF-8');
      echo "<li><b>{$id}</b> — {$nom}</li>";
    }
    echo "</ul>";
  } else {
    echo "<p><i>Sin permisos de entidades.</i></p>";
  }

  echo "<h3>Buses permitidos (".count($busPermitidos).")</h3>";
  if ($busPermitidos) {
    echo "<ul>";
    foreach ($busPermitidos as $id) {
      $nom = htmlspecialchars($busNameById[$id] ?? "(ID {$id})", ENT_QUOTES, 'UTF-8');
      echo "<li><b>{$id}</b> — {$nom}</li>";
    }
    echo "</ul>";
  } else {
    echo "<p><i>Sin permisos de buses.</i></p>";
  }

  echo "<h3>JSON</h3>";
  $json = [
    'entidades' => array_values($entPermitidas),
    'buses'     => array_values($busPermitidos),
  ];
  echo "<pre>".json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."</pre>";

  $base = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
  echo "<p><a href='{$base}'>Ir a la vista normal</a></p>";
  exit;
}

// ---------------------------------------------------------
// 4) Exponer permisos a JS para el mapa/tabla
// ---------------------------------------------------------
$__ACL_GENERAL__ = [
  'entidades' => array_values($entPermitidas),
  'buses'     => array_values($busPermitidos),
];

// Inyectar en el HTML (deja este echo donde convenga en tu layout)
function echo_acl_general_json() {
  global $__ACL_GENERAL__;
  echo "<script>window.__ACL_GENERAL__ = ".json_encode($__ACL_GENERAL__, JSON_UNESCAPED_UNICODE).";</script>";
}

// ---------------------------------------------------------
// 5) Helpers para filtrar SQL del detalle (servidor)
//    • Úsalos en consultas de la tabla de detalle (no en el mapa).
// ---------------------------------------------------------
function build_where_entidad_bus(array $entidades, array $buses) {
  $clauses = []; $params = [];

  if (!empty($entidades)) {
    $in = implode(',', array_fill(0, count($entidades), '?'));
    $clauses[] = "r.Fk_entidad IN ($in)";
    $params = array_merge($params, array_map('intval', $entidades));
  } else {
    // Sin entidades => nada debería mostrarse
    $clauses[] = "1=0";
  }

  if (!empty($buses)) {
    $in = implode(',', array_fill(0, count($buses), '?'));
    $clauses[] = "r.Fk_bus IN ($in)";
    $params = array_merge($params, array_map('intval', $buses));
  } else {
    // Sin buses => no hay detalle
    $clauses[] = "1=0";
  }

  $where = implode(' AND ', $clauses);
  return [$where, $params];
}
