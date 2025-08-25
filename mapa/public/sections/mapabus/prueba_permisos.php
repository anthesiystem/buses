<?php
/* Normalizador para comparar nombres de entidad */
function norm_es(string $s): string {
  $s = mb_strtoupper($s, 'UTF-8');
  $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
                  'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N']);
  return trim(preg_replace('/\s+/', ' ', $s));
}

/**
 * Une TODAS las filas READ aplicables y devuelve IDs de entidad permitidos.
 * Soporta:
 *  - FK_entidad = NULL / '*' / '0' / 'ALL' / 'TODAS'  -> TODAS las entidades
 *  - IDs numéricos
 *  - Nombres (mapeados por entidad.descripcion, case/acentos indiferente)
 *  - CSV: "6,10"
 *  - FK_bus NULL o 0 = comodín; o igual al bus solicitado
 *  - accion NULL = comodín; o 'READ'
 */
function entidadesPermitidasPorUsuario_MULTI(PDO $pdo, int $usuarioId, ?int $busId, int $modId): array {
  // Bypass por nivel alto
  $nivel = (int)($_SESSION['nivel'] ?? 0);
  if ($nivel >= 3) {
    $stAll = $pdo->query("SELECT ID FROM entidad WHERE activo = 1");
    return array_map('intval', array_column($stAll->fetchAll(PDO::FETCH_ASSOC), 'ID'));
  }

  // Trae TODAS las filas aplicables (sin LIMIT 1)
  $cond = "Fk_usuario = :u AND Fk_modulo = :m AND activo = 1 AND (accion IS NULL OR accion = 'READ')";
  $params = [':u'=>$usuarioId, ':m'=>$modId];

  if ($busId === null) {
    $cond .= " AND (FK_bus IS NULL OR FK_bus = 0)";
  } else {
    $cond .= " AND (FK_bus IS NULL OR FK_bus = 0 OR FK_bus = :b)";
    $params[':b'] = $busId;
  }

  $sql = "SELECT FK_entidad, FK_bus, accion FROM permiso_usuario WHERE $cond";
  $st  = $pdo->prepare($sql);
  $st->execute($params);
  $perms = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!$perms) return [];

  // Carga entidades y prepara mapa nombre->ID
  $rows = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
  $allIds = array_map('intval', array_column($rows, 'ID'));
  $name2id = [];
  foreach ($rows as $r) $name2id[norm_es($r['descripcion'])] = (int)$r['ID'];

  // Acumula
  $ids = [];
  foreach ($perms as $p) {
    $entRaw = $p['FK_entidad'];
    // comodín de entidad
    if ($entRaw === null) return $allIds;

    $ent = trim((string)$entRaw);
    $upper = strtoupper($ent);
    if ($ent === '*' || $ent === '0' || $upper === 'ALL' || $upper === 'TODAS') return $allIds;

    // Puede ser CSV / id / nombre
    foreach (preg_split('/\s*,\s*/', $ent, -1, PREG_SPLIT_NO_EMPTY) as $tok) {
      if (ctype_digit($tok)) {
        $id = (int)$tok;
        if (in_array($id, $allIds, true)) $ids[] = $id;
      } else {
        $nid = $name2id[norm_es($tok)] ?? null;
        if ($nid) $ids[] = $nid;
      }
    }
  }

  return array_values(array_unique($ids));
}
?>


<?php
// public/sections/mapabus/prueba_permisos.php

require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();

/* -------------------- helpers de sesión/entrada -------------------- */
function ses_get(array $keys, $default=null) {
  foreach ($keys as $k) if (isset($_SESSION[$k])) return $_SESSION[$k];
  if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
    foreach (['ID','id','user_id','usuario_id'] as $k) if (isset($_SESSION['usuario'][$k])) return $_SESSION['usuario'][$k];
  }
  return $default;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Permite forzar usuario por query para pruebas: ?uid=4 */
$forceUid = isset($_GET['uid']) ? (int)$_GET['uid'] : null;
$busId    = (int)($_GET['bus'] ?? 1);
$debug    = isset($_GET['debug']) ? (int)$_GET['debug'] : 0;

/* Usuario de sesión (o forzado por ?uid=) */
$userId = $forceUid ?? (int) ses_get(['user_id','usuario_id','id_usuario','ID','id','uid'], 0);
$nivel  = (int) ses_get(['nivel','role_level','user_level'], 0);

/* Módulo: por defecto ID 9 (mapa_bus). Acepta ?mod=9 o ?mod=mapa_bus */
$modParam = $_GET['mod'] ?? 9;
if (ctype_digit((string)$modParam)) {
  $modId = (int)$modParam;
  $modLabel = "#{$modId}";
} else {
  $st = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = ? LIMIT 1");
  $st->execute([(string)$modParam]);
  $id = $st->fetchColumn();
  $modId = ($id !== false) ? (int)$id : 9;
  $modLabel = (string)$modParam;
}

/* -------------------- cálculo de permisos (temporal, según tu esquema actual) -------------------- */
/**
 * Compatible con tu tabla permiso_usuario:
 * - FK_entidad: VARCHAR (puede ser '*', nombre, o un número de ID)
 * - FK_bus: INT (0 = comodín para todos los buses)
 * - accion: 'READ'
 */
function entidadesPermitidasPorUsuario_PRUEBA(PDO $pdo, int $usuarioId, ?int $busId, int $modId): array {
  // Bypass por nivel alto (>=3)
  $nivel = (int)($_SESSION['nivel'] ?? 0);
  if ($nivel >= 3) {
    $stAll = $pdo->query("SELECT ID FROM entidad WHERE activo = 1");
    return array_map('intval', array_column($stAll->fetchAll(PDO::FETCH_ASSOC), 'ID'));
  }

  // 1) leer permiso READ aplicable (bus comodín 0)
  $sql = "SELECT FK_entidad, FK_bus 
          FROM permiso_usuario 
          WHERE Fk_usuario = :u 
            AND Fk_modulo  = :m
            AND accion     = 'READ'
            AND activo     = 1
            AND (:b1 IS NULL OR FK_bus = 0 OR FK_bus = :b2)
          LIMIT 1";
  $st  = $pdo->prepare($sql);
  $st->execute([
    ':u'  => $usuarioId,
    ':m'  => $modId,
    ':b1' => $busId,
    ':b2' => $busId,
  ]);
  $perm = $st->fetch(PDO::FETCH_ASSOC);
  if (!$perm) return [];

  $ent = trim((string)$perm['FK_entidad']);

  // 2) comodín → todas
  if ($ent === '*' || $ent === '0' || strtoupper($ent) === 'TODAS' || strtoupper($ent) === 'ALL') {
    $stAll = $pdo->query("SELECT ID FROM entidad WHERE activo = 1");
    return array_map('intval', array_column($stAll->fetchAll(PDO::FETCH_ASSOC), 'ID'));
  }

  // 3) número → ID
  if (ctype_digit($ent)) {
    $id = (int)$ent;
    $ex = $pdo->prepare("SELECT 1 FROM entidad WHERE ID = ? AND activo = 1");
    $ex->execute([$id]);
    return $ex->fetchColumn() ? [$id] : [];
  }

  // 4) nombre → buscar por descripcion (case-insensitive)
  $ex = $pdo->prepare("SELECT ID FROM entidad WHERE UPPER(TRIM(descripcion)) = UPPER(TRIM(?)) AND activo = 1 LIMIT 1");
  $ex->execute([$ent]);
  $id = $ex->fetchColumn();
  return $id ? [(int)$id] : [];
}

/* -------------------- datos para pintar la demo -------------------- */
// Antes:
// $permitidas = entidadesPermitidasPorUsuario_PRUEBA($pdo, $userId, $busId, $modId);

// Ahora:
$permitidas = entidadesPermitidasPorUsuario_MULTI($pdo, $userId, $busId, $modId);


$entidades = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1 ORDER BY ID LIMIT 12")
                 ->fetchAll(PDO::FETCH_ASSOC);
$colorPermitido = '#10B981'; // verde
$colorBloqueado = '#E5E7EB'; // gris claro
$colorBorde     = '#111827';
$cols=4; $cellW=180; $cellH=110; $padX=40; $padY=40;
$rows = max(1, (int)ceil(count($entidades)/$cols));
$svgW = $cols*$cellW + $padX*2;
$svgH = $rows*$cellH + $padY*2;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Prueba de permisos (SVG simple)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family: system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif; background:#f7fafc; margin:0; padding:24px;}
    .card{background:#fff; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.06); padding:18px; margin:auto; max-width:1100px;}
    .muted{color:#6B7280; font-size:12px}
    .pill{display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; background:#EEF2FF}
    .legend{display:flex; gap:16px; margin:8px 0 16px}
    .legend .item{display:flex; align-items:center; gap:8px; font-size:14px}
    .square{width:14px; height:14px; border-radius:3px; outline:1px solid rgba(0,0,0,.08)}
    #tooltip{position:absolute; padding:6px 8px; background:rgba(17,24,39,.92); color:#fff; font-size:12px; border-radius:6px; pointer-events:none; display:none; z-index:1000}
    svg text{font-size:12px; fill:#111827}
    .node-label{font-weight:600}
    .node-click{cursor:pointer;}
    .node-block{opacity:.35; pointer-events:none; filter:grayscale(1);}
    .debug{background:#0b1020; color:#D1FAE5; padding:12px; border-radius:8px; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; overflow:auto;}
    .hdr{display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px}
    .warn{padding:12px; border:1px dashed #F59E0B; background:#FFFBEB; color:#92400E; border-radius:8px; margin-bottom:12px;}
  </style>
</head>
<body>

<div class="card">
  <div class="hdr">
    <div>
      <h2 style="margin:0;font-size:18px;">Prueba de permisos (SVG simple)</h2>
      <div class="muted">
        Usuario: <span class="pill">#<?= (int)$userId ?></span>
        &nbsp; Nivel: <span class="pill"><?= (int)$nivel ?></span>
        &nbsp; Bus: <span class="pill">#<?= (int)$busId ?></span>
        &nbsp; Módulo: <span class="pill"><?= h($modLabel) ?></span>
        &nbsp; <span class="muted">(usa ?uid=4&bus=1&mod=9&debug=1)</span>
      </div>
    </div>
    <div class="legend">
      <div class="item"><span class="square" style="background: <?= $colorPermitido ?>"></span> Permitido (clic habilitado)</div>
      <div class="item"><span class="square" style="background: <?= $colorBloqueado ?>"></span> Bloqueado (sin clic)</div>
    </div>
  </div>

  <?php if (!$permitidas): ?>
    <div class="warn">No hay permisos <strong>READ</strong> para este usuario/bus/módulo. Todo aparecerá bloqueado.</div>
  <?php endif; ?>

  <div style="position:relative">
    <div id="tooltip"></div>
    <svg id="svgPerms" width="<?= $svgW ?>" height="<?= $svgH ?>" viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" role="img" aria-label="SVG de permisos">
      <?php
      $i=0;
      foreach ($entidades as $e):
        $id   = (int)$e['ID'];
        $name = (string)$e['descripcion'];
        $r = (int)floor($i/$cols); $c = (int)($i%$cols);
        $x = $padX + $c*$cellW; $y = $padY + $r*$cellH;
        $w=140; $h=70; $rx=14;

        $allowed = in_array($id, $permitidas, true);
        $fill    = $allowed ? $colorPermitido : $colorBloqueado;
        $cls     = $allowed ? 'node-click' : 'node-block';
      ?>
      <g class="node <?= $cls ?>" data-entidad-id="<?= $id ?>" data-entidad-nombre="<?= h($name) ?>" transform="translate(<?= $x ?>,<?= $y ?>)">
        <rect x="0" y="0" width="<?= $w ?>" height="<?= $h ?>" rx="<?= $rx ?>" fill="<?= $fill ?>" stroke="<?= $colorBorde ?>" stroke-opacity=".12"></rect>
        <text x="<?= $w/2 ?>" y="<?= ($h/2)-2 ?>" text-anchor="middle" class="node-label"><?= h($name) ?></text>
        <text x="<?= $w/2 ?>" y="<?= ($h/2)+16 ?>" text-anchor="middle" class="muted">ID #<?= $id ?> · <?= $allowed ? 'PERMITIDO' : 'BLOQUEADO' ?></text>
      </g>
      <?php $i++; endforeach; ?>
    </svg>
  </div>

  <?php if ($debug): ?>
    <h3 style="margin:16px 0 8px;font-size:16px;">Debug</h3>
    <div class="debug">
      <strong>$permitidas</strong>: <?= h(json_encode($permitidas)) ?><br>
      <strong>Primeras entidades</strong>: <?= h(json_encode($entidades)) ?><br>
      <?php
      $st = $pdo->prepare("SELECT FK_entidad, FK_bus, accion, activo 
                           FROM permiso_usuario 
                           WHERE Fk_usuario=? AND Fk_modulo=? AND accion='READ'
                           ORDER BY ID DESC LIMIT 5");
      $st->execute([$userId, $modId]);
      $dbg = $st->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <strong>READ aplicado (usuario <?= (int)$userId ?>, módulo <?= (int)$modId ?>)</strong>: <?= h(json_encode($dbg)) ?>
    </div>
  <?php endif; ?>

  <p class="muted" style="margin-top:10px">
    Pruébalo con: <code>?uid=4&bus=1&mod=9</code> &nbsp;|&nbsp; Añade <code>&debug=1</code> para ver datos crudos.
  </p>
</div>

<script>
  (function(){
    const svg = document.getElementById('svgPerms');
    const tip = document.getElementById('tooltip');

    svg.addEventListener('mousemove', (e) => {
      const g = e.target.closest('.node');
      if (!g) { tip.style.display='none'; return; }
      const nombre = g.getAttribute('data-entidad-nombre') || '';
      const id     = g.getAttribute('data-entidad-id') || '';
      const blocked = g.classList.contains('node-block');
      tip.textContent = blocked ? `${nombre} (ID ${id}) · SIN PERMISO` : `${nombre} (ID ${id}) · PERMITIDO`;
      tip.style.left = (e.pageX + 12) + 'px';
      tip.style.top  = (e.pageY + 12) + 'px';
      tip.style.display = 'block';
    });
    svg.addEventListener('mouseleave', () => { tip.style.display='none'; });
    svg.addEventListener('click', (e) => {
      const g = e.target.closest('.node.node-click');
      if (!g) return; // bloqueados no son clicables
      const nombre = g.getAttribute('data-entidad-nombre') || '';
      const id     = g.getAttribute('data-entidad-id') || '';
      alert(`Click permitido en: ${nombre} (ID ${id})`);
    });
  })();
</script>
</body>
</html>
