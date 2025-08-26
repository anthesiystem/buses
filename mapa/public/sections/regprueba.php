<?php
// registros.php
session_start();
require_once '../../server/config.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.php");
  exit;
}

/* ===========================
   POST: insertar / actualizar
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) Tomar y sanear datos
  $ID              = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
  $Fk_dependencia  = ($_POST['Fk_dependencia'] ?? '') !== '' ? (int)$_POST['Fk_dependencia'] : null; // NULL permitido
  $Fk_entidad      = (int)($_POST['Fk_entidad'] ?? 0);
  $Fk_bus          = ($_POST['Fk_bus'] ?? '') !== '' ? (int)$_POST['Fk_bus'] : null;                 // NULL permitido
  $Fk_motor_base   = (int)($_POST['Fk_motor_base'] ?? 0);
  $Fk_tecnologia   = (int)($_POST['Fk_tecnologia'] ?? 0);
  $Fk_estado_bus   = (int)($_POST['Fk_estado_bus'] ?? 0);
  $Fk_categoria    = (int)($_POST['Fk_categoria'] ?? 0);
  $Fk_etapa        = ($_POST['Fk_etapa'] ?? '') !== '' ? (int)$_POST['Fk_etapa'] : null;             // NULL permitido
  $fecha_inicio    = ($_POST['fecha_inicio'] ?? '') ?: null;
  $fecha_migracion = ($_POST['fecha_migracion'] ?? '') ?: null;

  // === Helpers ===
  $getEstadoTexto = function(int $id) use ($pdo): string {
    $st = $pdo->prepare("SELECT LOWER(TRIM(descripcion)) FROM estado_bus WHERE ID = ? AND activo = 1 LIMIT 1");
    $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '');
  };
  $getEtapaPct = function($id) use ($pdo): int {
    if (empty($id)) return 0;
    $st = $pdo->prepare("SELECT avance FROM etapa WHERE ID = ? AND activo = 1 LIMIT 1");
    $st->execute([$id]);
    return (int)($st->fetchColumn() ?: 0);
  };
  $getEtapaImplId = function() use ($pdo) {
    // Preferir EXACTO 100%; si no existe, toma la mayor
    $id = $pdo->query("SELECT ID FROM etapa WHERE activo=1 AND avance=100 ORDER BY ID LIMIT 1")->fetchColumn();
    if ($id) return (int)$id;
    $id = $pdo->query("SELECT ID FROM etapa WHERE activo=1 ORDER BY avance DESC, ID ASC LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };
  $getEstadoImplId = function() use ($pdo) {
    $id = $pdo->query("SELECT ID FROM estado_bus WHERE activo=1 AND LOWER(descripcion) LIKE 'implementado%' LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };
  $getCatProdId = function() use ($pdo) {
    $id = $pdo->query("SELECT ID FROM categoria WHERE activo=1 AND LOWER(descripcion) LIKE 'productiv%' LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };

  // 2) Reglas por ESTATUS (SIEMPRE antes de guardar)
  $estadoTxt = $getEstadoTexto($Fk_estado_bus); // ej: "sin implementar", "en pruebas", "implementado"

  if (preg_match('/sin\s*implement/i', $estadoTxt)) {
    // SIN IMPLEMENTAR → Etapa y Fecha de migración deben ir NULL
    $Fk_etapa = null;
    $fecha_migracion = null;

  } elseif (preg_match('/prueba/i', $estadoTxt)) {
    // EN PRUEBAS → Etapa puede ser NULL, pero NO puede ser 100%; Fecha de migración opcional
    if ($getEtapaPct($Fk_etapa) === 100) {
      $Fk_etapa = null; // evita 100% en pruebas
    }

  } elseif (preg_match('/implementado/i', $estadoTxt)) {
    // IMPLEMENTADO → Forzar Etapa=100% aunque usuario no la seleccione
    $etapaImplId = $getEtapaImplId();
    if (!empty($etapaImplId)) {
      $Fk_etapa = $etapaImplId;
    }
    // Si quieres hacer obligatoria la fecha_migracion en implementado, valida aquí.
  }

  // 3) Coherencia final: si la ETAPA (ya ajustada) es 100%, forzar estado y categoría
  if ($getEtapaPct($Fk_etapa) === 100) {
    $implId = $getEstadoImplId();
    if ($implId) $Fk_estado_bus = $implId;

    $prodId = $getCatProdId();
    if ($prodId) $Fk_categoria = $prodId;
  }

  // 4) Guardar
  if ($ID > 0) {
    // UPDATE
    $stm = $pdo->prepare("
      UPDATE registro SET
        Fk_dependencia = ?, 
        Fk_entidad     = ?, 
        Fk_bus         = ?, 
        Fk_motor_base  = ?, 
        Fk_tecnologia  = ?,
        Fk_estado_bus  = ?, 
        Fk_categoria   = ?, 
        Fk_etapa       = ?, 
        fecha_inicio   = ?, 
        fecha_migracion= ?,
        fecha_modificacion = NOW()
      WHERE ID = ?
    ");
    $stm->execute([
      $Fk_dependencia, $Fk_entidad, $Fk_bus,
      $Fk_motor_base, $Fk_tecnologia, $Fk_estado_bus,
      $Fk_categoria, $Fk_etapa,
      $fecha_inicio, $fecha_migracion,
      $ID
    ]);
  } else {
    // INSERT
    $stmt = $pdo->prepare("
      INSERT INTO registro
        (Fk_dependencia, Fk_entidad, Fk_bus, Fk_motor_base, Fk_tecnologia,
         Fk_estado_bus, Fk_categoria, Fk_etapa, fecha_inicio, fecha_migracion, fecha_creacion)
      VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
    ");
    $stmt->execute([
      $Fk_dependencia, $Fk_entidad, $Fk_bus,
      $Fk_motor_base, $Fk_tecnologia, $Fk_estado_bus,
      $Fk_categoria, $Fk_etapa,
      $fecha_inicio, $fecha_migracion
    ]);
  }

  // 5) Redirigir al listado correcto
  // 5) Responder o redirigir
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
          && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok'     => true,
    'status' => ($ID > 0 ? 'updated' : 'created')
  ]);
  exit;
}

// Si NO es AJAX, utiliza return_url si viene; si no, manda a regprueba.php
$return = $_POST['return_url'] ?? ('regprueba.php?ok=' . ($ID > 0 ? 'updated' : 'created'));
header("Location: $return");
exit;

}

/* ===========================
   Catálogos
   =========================== */
function catalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}
$dependencias = catalogo($pdo, 'dependencia');
$entidades    = catalogo($pdo, 'entidad');
$buses        = catalogo($pdo, 'bus');
$engines      = catalogo($pdo, 'motor_base');
$estatuses    = catalogo($pdo, 'estado_bus');
$categorias   = catalogo($pdo, 'categoria');

$tecnologias = $pdo->query("
  SELECT ID, CONCAT(numero_version, ' - ', descripcion) AS descripcion
  FROM tecnologia
  WHERE activo = 1
  ORDER BY numero_version, descripcion
")->fetchAll(PDO::FETCH_ASSOC);

$etapas = $pdo->query("
  SELECT ID, descripcion, avance
  FROM etapa
  WHERE activo = 1
  ORDER BY ID
")->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Registros (IDs y textos)
   =========================== */
$stmt = $pdo->query("
SELECT
  r.ID,
  r.Fk_dependencia, r.Fk_entidad, r.Fk_bus, r.Fk_motor_base, r.Fk_tecnologia,
  r.Fk_estado_bus, r.Fk_categoria, r.Fk_etapa,
  r.fecha_inicio, r.fecha_migracion, r.fecha_creacion, r.fecha_modificacion,

  COALESCE(d.descripcion,'—')  AS Dependencia,
  e.descripcion                AS Entidad,
  b.descripcion                AS Bus,
  en.descripcion               AS Engine,
  CONCAT(t.numero_version, ' - ', t.descripcion) AS Tecnologia,
  c.descripcion                AS Categoria,
  eb.descripcion               AS Estado,
  et.descripcion               AS Etapa,
  et.avance                    AS EtapaPorcentaje
FROM registro r
LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia        
JOIN entidad e       ON e.ID = r.Fk_entidad
LEFT JOIN bus b      ON b.ID = r.Fk_bus
JOIN motor_base en   ON en.ID = r.Fk_motor_base
JOIN tecnologia t    ON t.ID  = r.Fk_tecnologia
JOIN categoria c     ON c.ID  = r.Fk_categoria
JOIN estado_bus eb   ON eb.ID = r.Fk_estado_bus
LEFT JOIN etapa et   ON et.ID = r.Fk_etapa
WHERE r.activo = 1
ORDER BY r.fecha_creacion DESC, r.ID DESC;
");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#7b1e2b; --brand-600:#8e2433; --brand-700:#661822; --brand-rgb:123,30,43;
      --ink:#1f2937; --muted:#6b7280; --row-hover:rgba(var(--brand-rgb),.04); --row-selected:rgba(var(--brand-rgb),.08);
      --header-bg:#ffffff; --header-border:#e5e7eb; --table-border:#e5e7eb; --badge-bg:#f3f4f6;
    }
    body{ color:var(--ink); background:#fafafa; }
    .page-title{ font-weight:700; letter-spacing:.2px; }
    .btn-brand{
      --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand);
      --bs-btn-hover-bg:var(--brand-600); --bs-btn-hover-border-color:var(--brand-600);
      --bs-btn-active-bg:var(--brand-700); --bs-btn-active-border-color:var(--brand-700);
      --bs-btn-color:#fff;
    }
    .btn-outline-brand{
      --bs-btn-color:var(--brand); --bs-btn-border-color:var(--brand);
      --bs-btn-hover-bg:var(--brand); --bs-btn-hover-border-color:var(--brand);
      --bs-btn-hover-color:#fff;
    }
    .toolbar{
      background:#fff; border:1px solid var(--header-border);
      border-radius:14px; padding:12px 14px; box-shadow:0 2px 10px rgba(0,0,0,.03);
    }
    .table-card{
      background:#fff; border:1px solid var(--table-border);
      border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.04);
    }
    .table-responsive{ max-height:70vh; }
    .table-brand thead th{
      position:sticky; top:0; z-index:5; background:var(--header-bg);
      border-bottom:1px solid var(--header-border); color:var(--muted);
      font-weight:700; text-transform:uppercase; font-size:.78rem; letter-spacing:.5px; cursor:pointer;
    }
    .table-brand tbody td{ vertical-align:middle; border-color:var(--table-border); }
    .table-brand tbody tr:hover{ background:var(--row-hover); }
    .table-brand tbody tr.selected{ background:var(--row-selected); box-shadow:inset 4px 0 0 var(--brand); }
    .table-brand .progress{ height:8px; background:#efe7e9; }
    .progress-bar.brand{ background:var(--brand); }
    .badge-soft{ background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb; font-weight:600; }
    .badge-implementado{ border-color:#d1fae5; background:#f0fdf4; color:#065f46; }
    .badge-pruebas{ border-color:#fde68a; background:#fffbeb; color:#92400e; }
    .actions .btn{ padding:.25rem .5rem; }
    @media (max-width:768px){
      .col-sm-hide{ display:none; }
      .actions .btn .text{ display:none; }
    }

      .modal-modern .modal-header{
    background: linear-gradient(135deg, rgba(123,30,43,.95), rgba(102,24,34,.95));
    color:#fff; border-bottom:0;
  }
  .modal-modern .modal-content{
    border:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,.15);
  }
  .modal-modern .modal-body{
    background:#fafafa;
  }
  .fieldset-card{
    background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.03);
  }
  .fieldset-card legend{
    font-size:.85rem; font-weight:700; color:#6b7280; padding:0 6px;
  }
  .help-inline{ font-size:.85rem; color:#6b7280; }
  .is-disabled{ opacity:.6; pointer-events:none; }

  /* Chips para la celda de Bus */
.chip{
  display:inline-flex; align-items:center; gap:.35rem;
  padding:.2rem .6rem; border-radius:9999px; font-weight:600;
  background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb;
  white-space:nowrap; max-width:100%; overflow:hidden; text-overflow:ellipsis;
}
.chip i{ font-size:1rem; line-height:1; }

.chip-impl{ background:rgba(var(--brand-rgb), .08); color:var(--brand); border-color:rgba(var(--brand-rgb), .35); }
.chip-pru { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.chip-sin { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }

/* Acento de fila según estado (opcional) */
.row-impl{ box-shadow: inset 4px 0 0 var(--brand); }
.row-pru { box-shadow: inset 4px 0 0 #f59e0b; }   /* ámbar */
.row-sin { box-shadow: inset 4px 0 0 #9ca3af; }   /* gris */

#main-content {
    max-width: 90%;
    padding-left: 12%;
    padding-top: 5%;
}

/* Sin scroll interno en la tarjeta/tabla */
.table-card { max-height: none !important; overflow: visible !important; }
.table-responsive { overflow: visible !important; }

  </style>
</head>

<body class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title m-0">Registros</h1>
    <button class="btn btn-brand" onclick="abrirModal()"><i class="bi bi-plus-lg me-2"></i>Agregar</button>
  </div>

  <!-- Toolbar -->
  <div class="toolbar mb-3">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input id="q" type="search" class="form-control" placeholder="Buscar en todas las columnas…">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="clickSelect" checked>
          <label class="form-check-label" for="clickSelect">Seleccionar fila con clic</label>
        </div>
      </div>
      <div class="col-6 col-md-3 text-md-end">
        <span id="selCount" class="text-muted small">0 seleccionadas</span>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-card">
    <div class="table-responsive">
      <table id="tablaReg" class="table table-hover table-brand align-middle m-0">
        <thead>
          <tr>
            <th style="width:42px" data-sort="none">
              <input class="form-check-input" type="checkbox" id="checkAll" title="Seleccionar todo">
            </th>
            <th data-sort="num">ID</th>
            <th data-sort="text">Entidad</th>
            <th class="col-sm-hide" data-sort="text">Dependencia</th>
            <th data-sort="text">Bus</th>
            <th data-sort="text">Engine</th>
            <th class="col-sm-hide" data-sort="text">Tecnología</th>
            <th data-sort="text">Estado</th>
            <th data-sort="num">Etapa / Avance</th>
            <th class="col-sm-hide" data-sort="date">Inicio</th>
            <th class="col-sm-hide" data-sort="date">Migración</th>
            <th class="text-end" data-sort="none">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r):
            $id   = (int)$r['ID'];
            $ent  = h($r['Entidad'] ?? '');
            $dep  = h($r['Dependencia'] ?? '');
            $bus  = h($r['Bus'] ?? '');
            $eng  = h($r['Engine'] ?? '');
            $tec  = h($r['Tecnologia'] ?? '');
            $est  = h($r['Estado'] ?? '');
            $etap = h($r['Etapa'] ?? '—');
            $pct  = isset($r['EtapaPorcentaje']) && $r['EtapaPorcentaje'] !== null ? (int)$r['EtapaPorcentaje'] : 0;
            $fini = h($r['fecha_inicio'] ?? '');
            $fmig = h($r['fecha_migracion'] ?? '');
  $pct  = isset($r['EtapaPorcentaje']) && $r['EtapaPorcentaje'] !== null ? (int)$r['EtapaPorcentaje'] : 0;

  // BADGE por estado (sin confundir "Sin implementar" con "Implementado")
  $badgeClass = 'badge-soft';                       // default
  $estTxt = mb_strtolower($est, 'UTF-8');

  if (preg_match('/sin\s*implement/i', $estTxt)) {  // "Sin implementar"
    $badgeClass = 'text-bg-secondary';              // Bootstrap secondary
  } elseif (preg_match('/\bimplementado\b/i', $estTxt)) { // "Implementado" exacto
    $badgeClass = 'badge-implementado';
  } elseif (preg_match('/prueba/i', $estTxt)) {     // "En pruebas"
    $badgeClass = 'badge-pruebas';
  }

 $json = json_encode([
    'ID'             => (int)$r['ID'],
    'Fk_dependencia' => $r['Fk_dependencia'],
    'Fk_entidad'     => $r['Fk_entidad'],
    'Fk_bus'         => $r['Fk_bus'],
    'Fk_motor_base'  => $r['Fk_motor_base'],
    'Fk_tecnologia'  => $r['Fk_tecnologia'],
    'Fk_estado_bus'  => $r['Fk_estado_bus'],
    'Fk_categoria'   => $r['Fk_categoria'],
    'Fk_etapa'       => $r['Fk_etapa'],
    'fecha_inicio'   => $r['fecha_inicio'],
    'fecha_migracion'=> $r['fecha_migracion'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

          ?>
          <tr>
            <td><input class="row-check form-check-input" type="checkbox"></td>
            <td><?= $id ?></td>
            <td><?= $ent ?></td>
            <td class="col-sm-hide"><?= $dep ?></td>
            <td><?= $bus ?></td>
            <td><?= $eng ?></td>
            <td class="col-sm-hide"><?= $tec ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $est ?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-fill">
                  <div class="progress-bar brand" role="progressbar" style="width: <?= max(0,min(100,$pct)) ?>%"></div>
                </div>
                <small class="text-muted"><?= $pct ?>%</small>
              </div>
              <div class="small text-muted"><?= $etap ?></div>
            </td>
            <td class="col-sm-hide"><?= $fini ?></td>
            <td class="col-sm-hide"><?= $fmig ?></td>
            <td class="actions text-end">
              <div class="btn-group">
                <button class="btn btn-outline-brand btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar" onclick='editar(<?= $json ?>)'>
                  <i class="bi bi-pencil"></i><span class="text ms-1">Editar</span>
                </button>

              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Paginación -->
<div id="paginacion" class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 p-2 border-top">
  <div class="d-flex align-items-center gap-2">
    <label for="perPage" class="form-label m-0">Filas por página:</label>
    <select id="perPage" class="form-select form-select-sm" style="width:auto">
      <option value="10" selected>10</option>
      <option value="20" >20</option>
      <option value="30">30</option>
      <option value="50">50</option>
      <option value="100">100</option>
    </select>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button id="btnFirst" class="btn btn-outline-secondary btn-sm" title="Primera">&laquo;</button>
    <button id="btnPrev"  class="btn btn-outline-secondary btn-sm" title="Anterior">&lsaquo;</button>
    <span id="pageInfo" class="small text-muted">Página 1 / 1</span>
    <button id="btnNext"  class="btn btn-outline-secondary btn-sm" title="Siguiente">&rsaquo;</button>
    <button id="btnLast"  class="btn btn-outline-secondary btn-sm" title="Última">&raquo;</button>
  </div>
  <div>
    <span id="rangeInfo" class="small text-muted">Mostrando 0–0 de 0</span>
  </div>
</div>


    </div>
  </div>

  <!-- Modal existente (Registro) -->
 <div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content modal-modern"> <!-- 👈 -->
      <form id="formRegistro" method="post" action="/final/mapa/public/sections/regprueba.php">
        <div class="modal-header">
          <h5 class="modal-title">Registro</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="ID" id="ID">

          <!-- Ubicación -->
          <fieldset class="fieldset-card mb-3">
            <legend>Ubicación</legend>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Dependencia</label>
                <!-- 👇 deja de ser required y agrega opción vacía -->
                <select class="form-select" name="Fk_dependencia">
                  <option value="">— Sin dependencia —</option>
                  <?php foreach ($dependencias as $d): ?>
                    <option value="<?= (int)$d['ID'] ?>"><?= h($d['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="help-inline">Opcional</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Entidad</label>
                <select class="form-select" name="Fk_entidad" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($entidades as $e): ?>
                    <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Bus</label>
                <select class="form-select" name="Fk_bus">
                  <option value="">—</option>
                  <?php foreach ($buses as $b): ?>
                    <option value="<?= (int)$b['ID'] ?>"><?= h($b['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <!-- Tecnología -->
          <fieldset class="fieldset-card mb-3">
            <legend>Tecnología</legend>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Motor Base</label>
                <select class="form-select" name="Fk_motor_base" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($engines as $en): ?>
                    <option value="<?= (int)$en['ID'] ?>"><?= h($en['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tecnología (versión - descripción)</label>
                <select class="form-select" name="Fk_tecnologia" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($tecnologias as $t): ?>
                    <option value="<?= (int)$t['ID'] ?>"><?= h($t['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <!-- Estatus / Fechas / Etapa / Categoría -->
          <fieldset class="fieldset-card">
            <legend>Estatus</legend>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Estatus</label>
                <select class="form-select" name="Fk_estado_bus" id="Fk_estado_bus" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($estatuses as $e): ?>
                    <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" max="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha Migración</label>
                <input type="date" class="form-control" name="fecha_migracion" max="<?= date('Y-m-d') ?>">
                <div class="help-inline" id="hintFmig">Opcional según estatus</div>
              </div>

              <div class="col-md-6 mt-2" id="wrapEtapa">
                <label class="form-label">Etapa</label>
                <select class="form-select" name="Fk_etapa" id="Fk_etapa">
                  <option value="">—</option>
                  <?php foreach ($etapas as $et): ?>
                    <option value="<?= (int)$et['ID'] ?>" data-avance="<?= (int)$et['avance'] ?>">
                      <?= h($et['descripcion']) ?> (<?= (int)$et['avance'] ?>%)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text" id="helperEtapa">Seleccione una etapa para ver su porcentaje.</div>
              </div>

              <div class="col-md-6 mt-2">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="Fk_categoria" id="Fk_categoria" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['ID'] ?>"><?= h($c['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>


 
  <!-- Overlays -->
  <div id="cargando" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.8); z-index:2000; backdrop-filter: blur(2px);">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
      <img src="/mapa/public/img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
      <div class="mt-2 fw-semibold">Espere un momento...</div>
    </div>
  </div>

  <div id="guardadoExitoAnimado" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2050; background:rgba(255,255,255,0.95); padding:30px 40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.2); text-align:center;">
    <div style="font-size:60px; color:green;">
      <img src="/mapa/public/img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
    </div>
    <div style="font-size:18px; margin-top:10px;">Guardado exitosamente</div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



  
  <script>
  // Modal: abrir/editar
  function abrirModal() {
    const form = document.getElementById('formRegistro');
    form.reset();
    form.ID.value = '';
    const helper = document.getElementById('helperEtapa');
    if (helper) helper.textContent = 'Seleccione una etapa para ver su porcentaje.';
    new bootstrap.Modal(document.getElementById('modalRegistro')).show();
  }

  function editar(datos) {
    const f = document.getElementById('formRegistro');
    f.reset();
    const campos = [
      'ID','Fk_dependencia','Fk_entidad','Fk_bus','Fk_motor_base','Fk_tecnologia',
      'Fk_estado_bus','Fk_categoria','Fk_etapa','fecha_inicio','fecha_migracion'
    ];
    campos.forEach(k => { if (k in datos && f[k]) f[k].value = datos[k] ?? ''; });

    const selEtapa = f.querySelector('#Fk_etapa');
    const helperEtapa = document.getElementById('helperEtapa');
    const opt = selEtapa?.selectedOptions?.[0];
    if (opt && opt.dataset.avance && helperEtapa) {
      helperEtapa.textContent = 'Porcentaje de etapa: ' + opt.dataset.avance + '%';
    } else if (helperEtapa) {
      helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
    }

    new bootstrap.Modal(document.getElementById('modalRegistro')).show();
  }

  document.addEventListener('DOMContentLoaded', () => {
    const hoy = new Date().toISOString().split('T')[0];
    const fi  = document.querySelector('[name="fecha_inicio"]');
    const fm  = document.querySelector('[name="fecha_migracion"]');
    if (fi) fi.max = hoy;
    if (fm) fm.max = hoy;

    const form         = document.getElementById('formRegistro');
    const selEstado    = form.querySelector('[name="Fk_estado_bus"]');
    const selCategoria = form.querySelector('[name="Fk_categoria"]');
    const selEtapa     = form.querySelector('#Fk_etapa');
    const helperEtapa  = document.getElementById('helperEtapa');

    selEtapa?.addEventListener('change', () => {
      const opt = selEtapa.selectedOptions[0];
      const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance, 10) : null;
      if (Number.isInteger(pct) && helperEtapa) {
        helperEtapa.textContent = 'Porcentaje de etapa: ' + pct + '%';
      } else if (helperEtapa) {
        helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
      }
    });

    // Forzar Implementado/Productivos si etapa es 100%
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) { form.reportValidity(); e.preventDefault(); return; }

      const optEtapa = selEtapa?.selectedOptions?.[0] || null;
      const etapaPct = optEtapa && optEtapa.dataset.avance ? parseInt(optEtapa.dataset.avance, 10) : 0;

      const textoEstado    = selEstado?.options[selEstado.selectedIndex]?.text?.trim() || '';
      const textoCategoria = selCategoria?.options[selCategoria.selectedIndex]?.text?.trim() || '';

      const findValueByText = (selectEl, txt) => {
        if (!selectEl) return null;
        const opt = Array.from(selectEl.options).find(o => (o.text || '').trim() === txt);
        return opt ? opt.value : null;
      };

      if (etapaPct === 100) {
        if (!/Implementado/i.test(textoEstado)) {
          const v = findValueByText(selEstado, 'Implementado');
          if (v) selEstado.value = v;
        }
        if (/^(Migraciones|Pruebas)$/i.test(textoCategoria)) {
          const v = findValueByText(selCategoria, 'Productivos');
          if (v) selCategoria.value = v;
        }
      }
    });
  });
  </script>

  <script>
  // Overlays de Cargando / Exito
  (function() {
    const form = document.getElementById('formRegistro');
    const overlayCargando = document.getElementById('cargando');
    const overlayExito = document.getElementById('guardadoExitoAnimado');

    if (form && overlayCargando) {
      form.addEventListener('submit', function() {
        const btns = form.querySelectorAll('button, input[type="submit"]');
        btns.forEach(b => b.disabled = true);
        overlayCargando.style.display = 'block';
      });
    }

    const params = new URLSearchParams(window.location.search);
    const ok = params.get('ok'); // 'created' | 'updated'
    if (ok && overlayExito) {
      if (ok === 'updated') {
        overlayExito.querySelector('div:last-child').textContent = 'Actualizado exitosamente';
      }
      overlayExito.style.display = 'block';
      setTimeout(() => {
        overlayExito.style.display = 'none';
        const url = new URL(window.location.href);
        url.searchParams.delete('ok');
        window.history.replaceState({}, document.title, url.toString());
      }, 1200);
    }

    if (form && overlayCargando) {
      form.addEventListener('invalid', () => {
        overlayCargando.style.display = 'none';
        const btns = form.querySelectorAll('button, input[type="submit"]');
        btns.forEach(b => b.disabled = false);
      }, true);
    }
  })();
  </script>

  <script>
  // Tabla: tooltips, búsqueda, selección y ordenamiento
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));

  const table  = document.getElementById('tablaReg');
  const tbody  = table.querySelector('tbody');
  const rows   = Array.from(tbody.rows);
  const q      = document.getElementById('q');
  const selCount   = document.getElementById('selCount');
  const clickSelect= document.getElementById('clickSelect');
  const checkAll   = document.getElementById('checkAll');

  // Búsqueda global
  q.addEventListener('input', () => {
    const term = q.value.trim().toLowerCase();
    rows.forEach(r => {
      const visible = r.innerText.toLowerCase().includes(term);
      r.style.display = visible ? '' : 'none';
    });
    refreshCount();
  });

  // Selección por clic en fila
  tbody.addEventListener('click', (e) => {
    if (!clickSelect.checked) return;
    if (e.target.closest('button, .form-check-input, a, [data-bs-toggle]')) return;
    const tr = e.target.closest('tr');
    if (!tr) return;
    const cb = tr.querySelector('.row-check');
    cb.checked = !cb.checked;
    toggleRow(tr, cb.checked);
    refreshCount();
  });

  // Selección con checkbox individual
  tbody.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', (e)=>{
      const tr = e.target.closest('tr');
      toggleRow(tr, e.target.checked);
      refreshCount();
    });
  });

  // Seleccionar todo (solo visibles)
  checkAll.addEventListener('change', ()=>{
    const checked = checkAll.checked;
    rows.forEach(row=>{
      if (row.style.display === 'none') return;
      const cb = row.querySelector('.row-check');
      cb.checked = checked;
      toggleRow(row, checked);
    });
    refreshCount();
  });

  function toggleRow(tr, isSelected){
    tr.classList.toggle('selected', isSelected);
  }
  function refreshCount(){
    const visibles = rows.filter(r => r.style.display !== 'none');
    const n = visibles.filter(r => r.querySelector('.row-check').checked).length;
    selCount.textContent = `${n} seleccionada${n===1?'':'s'}`;
    const visiblesChecked = visibles.length>0 && visibles.every(r => r.querySelector('.row-check').checked);
    checkAll.indeterminate = !visiblesChecked && n>0;
    checkAll.checked = visiblesChecked;
  }

  // Ordenamiento por columna
  const getCellValue = (tr, idx) => tr.children[idx]?.innerText.trim() ?? '';
  const parseVal = (val, type) => {
    if (type==='num'){ const n = Number(val.replace(/[^\d.-]+/g,'')); return isNaN(n)?Number.NEGATIVE_INFINITY:n; }
    if (type==='date'){ const t = Date.parse(val); return isNaN(t)?-Infinity:t; }
    return val.toLowerCase();
  };
  table.querySelectorAll('thead th[data-sort]').forEach((th, idx)=>{
    if (th.getAttribute('data-sort') === 'none') return;
    let asc = true;
    th.addEventListener('click', ()=>{
      const type = th.getAttribute('data-sort');
      rows.sort((a,b)=>{
        const A = parseVal(getCellValue(a, idx), type);
        const B = parseVal(getCellValue(b, idx), type);
        if (A===B) return 0;
        return asc ? (A>B?1:-1) : (A<B?1:-1);
      });
      rows.forEach(r=>tbody.appendChild(r));
      asc = !asc;
      table.querySelectorAll('thead th').forEach(x=>x.classList.remove('text-decoration-underline'));
      th.classList.add('text-decoration-underline');
    });
  });
  </script>

  <script>
document.addEventListener('DOMContentLoaded', () => {
  const form         = document.getElementById('formRegistro');
  const selEstado    = form.querySelector('#Fk_estado_bus');
  const selCategoria = form.querySelector('#Fk_categoria');
  const selEtapa     = form.querySelector('#Fk_etapa');
  const fi           = form.querySelector('[name="fecha_inicio"]');
  const fm           = form.querySelector('[name="fecha_migracion"]');
  const helperEtapa  = document.getElementById('helperEtapa');
  const hintFmig     = document.getElementById('hintFmig');

  // Helper: obtiene texto limpio del select actual
  const getText = (selectEl) => selectEl?.options[selectEl.selectedIndex]?.text?.trim() || '';

  // Helper: aplicar/retirar disabled "suave" (con estilo)
  const setDisabled = (el, disabled) => {
    if (!el) return;
    el.disabled = !!disabled;
    const wrap = el.closest('.col-md-6, .col-md-4') || el.parentElement;
    if (wrap) wrap.classList.toggle('is-disabled', !!disabled);
  };

  // Al cambiar Etapa, mostrar su porcentaje
  selEtapa?.addEventListener('change', () => {
    const opt = selEtapa.selectedOptions[0];
    const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance, 10) : null;
    if (Number.isInteger(pct) && helperEtapa) {
      helperEtapa.textContent = 'Porcentaje de etapa: ' + pct + '%';
    } else if (helperEtapa) {
      helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
    }
  });

  // Encontrar opción de etapa = 100% (Implementado)
  const pickEtapa100 = () => {
    const opt100 = Array.from(selEtapa.options).find(o => (o.dataset?.avance|0) === 100);
    return opt100 || null;
  };

  // Reglas dinámicas por estatus
  const applyEstadoRules = () => {
    const txt = getText(selEstado).toLowerCase();

    // Reset base (habilitar todo)
    setDisabled(selEtapa, false);
    setDisabled(fm, false);
    if (hintFmig) hintFmig.textContent = 'Opcional según estatus';

    // SIN IMPLEMENTAR: bloquear Etapa y Fecha de migración, enviar NULL
    if (/sin implementar/.test(txt)) {
      selEtapa.value = '';        // -> NULL
      fm.value = '';              // -> NULL
      setDisabled(selEtapa, true);
      setDisabled(fm, true);
      if (helperEtapa) helperEtapa.textContent = 'Indisponible en "Sin implementar".';
      if (hintFmig)    hintFmig.textContent    = 'Indisponible en "Sin implementar".';
      return;
    }

    // EN PRUEBAS: habilitar; etapa NO puede ser 100%; fecha de migración puede ser NULL
    if (/prueba/.test(txt)) {
      setDisabled(selEtapa, false);
      setDisabled(fm, false);  // opcional, puede ir null
      const opt = selEtapa.selectedOptions[0];
      const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance,10) : null;
      if (pct === 100) { // no permitido
        selEtapa.value = '';
        if (helperEtapa) helperEtapa.textContent = 'En "En pruebas" la etapa no puede ser 100%.';
      }
      return;
    }

    // IMPLEMENTADO: fijar etapa = 100% (Implementado) y bloquear
    if (/implementado/.test(txt)) {
      const opt100 = pickEtapa100();
      if (opt100) selEtapa.value = opt100.value;
      setDisabled(selEtapa, true);
      if (helperEtapa) helperEtapa.textContent = 'Etapa fijada a Implementado (100%).';
      // la fecha de migración queda habilitada (puedes volverla requerida si quieres)
      return;
    }

    // Otros estados: default libre
  };

  selEstado?.addEventListener('change', applyEstadoRules);
  applyEstadoRules(); // al cargar (por si se abre para editar)

  // Validación extra al enviar (refuerza reglas por si el usuario truquea DOM)
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) { form.reportValidity(); e.preventDefault(); return; }

    const txt = getText(selEstado).toLowerCase();
    const optEtapa = selEtapa?.selectedOptions?.[0] || null;
    const etapaPct = optEtapa && optEtapa.dataset.avance ? parseInt(optEtapa.dataset.avance, 10) : null;

    // Sin implementar -> forzar NULL en etapa y fecha_migracion
    if (/sin implementar/.test(txt)) {
      selEtapa.value = '';
      fm.value = '';
    }

    // En pruebas -> etapa NO 100
    if (/prueba/.test(txt) && etapaPct === 100) {
      e.preventDefault();
      selEtapa.value = '';
      alert('En "En pruebas" la etapa no puede ser 100%. Selecciona otra etapa.');
      return;
    }

    // Implementado -> etapa = 100 automática
    if (/implementado/.test(txt)) {
      const opt100 = pickEtapa100();
      if (opt100) selEtapa.value = opt100.value;
    }
  });
});
</script>

<script>
(function () {
  const form = document.getElementById('formRegistro');
  const modalEl = document.getElementById('modalRegistro');
  const overlayCargando = document.getElementById('cargando');

  // Asegura return_url al abrir modal (nuevo)
  window.abrirModal = function() {
    form.reset();
    form.ID.value = '';
    const helper = document.getElementById('helperEtapa');
    if (helper) helper.textContent = 'Seleccione una etapa para ver su porcentaje.';
    // set return_url al URL actual
    const ru = document.getElementById('return_url');
    if (ru) ru.value = window.location.href;

    new bootstrap.Modal(modalEl).show();
  };

  // Asegura return_url al abrir modal (editar)
  const _editar = window.editar;
  window.editar = function(datos) {
    form.reset();
    const campos = [
      'ID','Fk_dependencia','Fk_entidad','Fk_bus','Fk_motor_base','Fk_tecnologia',
      'Fk_estado_bus','Fk_categoria','Fk_etapa','fecha_inicio','fecha_migracion'
    ];
    campos.forEach(k => { if (k in datos && form[k]) form[k].value = datos[k] ?? ''; });

    // helper de etapa
    const selEtapa = form.querySelector('#Fk_etapa');
    const helperEtapa = document.getElementById('helperEtapa');
    const opt = selEtapa?.selectedOptions?.[0];
    if (opt && opt.dataset.avance && helperEtapa) {
      helperEtapa.textContent = 'Porcentaje de etapa: ' + opt.dataset.avance + '%';
    } else if (helperEtapa) {
      helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
    }

    // set return_url
    const ru = document.getElementById('return_url');
    if (ru) ru.value = window.location.href;

    new bootstrap.Modal(modalEl).show();
  };

  // Submit por AJAX (mantiene la vista en index.php)
  form.addEventListener('submit', async (e) => {
    // Si el form no es válido, deja que tus validaciones previas actúen
    if (!form.checkValidity()) return;

    // Evita navegación
    e.preventDefault();

    // Muestra overlay (ya lo hacías en otro listener; por si acaso)
    if (overlayCargando) overlayCargando.style.display = 'block';

    try {
      const fd = new FormData(form);
      const resp = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, // <- activa respuesta JSON en PHP
        cache: 'no-store'
      });

      const ct = resp.headers.get('content-type') || '';
      if (!resp.ok) {
        const txt = await resp.text();
        console.error('HTTP', resp.status, txt);
        alert('Error al guardar.');
        return;
      }

      let data = {};
      if (ct.includes('application/json')) {
        data = await resp.json();
      } else {
        // Si el servidor devolviera HTML, puedes inyectarlo:
        const dlg = modalEl.querySelector('.modal-dialog');
        dlg.innerHTML = await resp.text();
        return;
      }

      if (data.ok) {
        // Cierra modal
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal && modal.hide();

        // ¿Estoy en index.php? refresca tabla vía función si existe, o recarga suave
        if (location.pathname.endsWith('/index.php')) {
          if (window.recargarTablaRegistros) {
            await window.recargarTablaRegistros();
          } else {
            // fallback
            location.reload();
          }
        } else {
          // En regprueba.php, puedes simular la redirección con parámetro ok
          const url = new URL(window.location.href);
          url.searchParams.set('ok', data.status); // created|updated
          window.location.href = url.toString();
        }
      } else {
        alert(data.msg || 'No se pudo guardar.');
      }
    } catch (err) {
      console.error(err);
      alert('Error de red al guardar.');
    } finally {
      if (overlayCargando) overlayCargando.style.display = 'none';
    }
  }, true);
})();
</script>
<script>
/* =======================
   Paginación + Integración
   ======================= */
(function() {
  const table   = document.getElementById('tablaReg');
  const tbody   = table.querySelector('tbody');
  const rows    = Array.from(tbody.rows);     // colección base (todas las filas)
  const q       = document.getElementById('q');
  const selCount   = document.getElementById('selCount');
  const checkAll   = document.getElementById('checkAll');
  const clickSelect= document.getElementById('clickSelect');

  // Controles de paginación
  const perPageSel = document.getElementById('perPage');
  const btnFirst   = document.getElementById('btnFirst');
  const btnPrev    = document.getElementById('btnPrev');
  const btnNext    = document.getElementById('btnNext');
  const btnLast    = document.getElementById('btnLast');
  const pageInfo   = document.getElementById('pageInfo');
  const rangeInfo  = document.getElementById('rangeInfo');

  // Estado
  const state = {
    term: '',
    page: 1,
    perPage: parseInt(perPageSel?.value || '10', 10),
    filtered: rows  // filas que pasan el filtro de búsqueda
  };

  // Helpers
  const normalize = (s) => (s || '').toString().trim().toLowerCase();

  function applySearch() {
    const t = normalize(state.term);
    state.filtered = t
      ? rows.filter(r => r.innerText.toLowerCase().includes(t))
      : rows.slice();

    // Reinicia a primera página si se reduce el universo
    state.page = 1;
    renderPage();
  }

  function getTotalPages() {
    return Math.max(1, Math.ceil(state.filtered.length / state.perPage));
  }

  function clampPage() {
    const total = getTotalPages();
    if (state.page > total) state.page = total;
    if (state.page < 1) state.page = 1;
  }

  function currentSlice() {
    const start = (state.page - 1) * state.perPage;
    const end   = start + state.perPage;
    return state.filtered.slice(start, end);
  }

  function renderPage() {
    clampPage();

    // Ocultar todas, mostrar solo las del slice actual
    rows.forEach(r => r.style.display = 'none');
    const slice = currentSlice();
    slice.forEach(r => r.style.display = '');

    // Actualizar info
    const total = state.filtered.length;
    const totalPages = getTotalPages();
    const startIdx = total ? ((state.page - 1) * state.perPage + 1) : 0;
    const endIdx = total ? Math.min(state.page * state.perPage, total) : 0;

    if (pageInfo)  pageInfo.textContent  = `Página ${state.page} / ${totalPages}`;
    if (rangeInfo) rangeInfo.textContent = `Mostrando ${startIdx}–${endIdx} de ${total}`;

    // Habilitar/deshabilitar botones
    const onFirst = state.page === 1;
    const onLast  = state.page === totalPages;
    [btnFirst, btnPrev].forEach(b => b && (b.disabled = onFirst));
    [btnNext, btnLast].forEach(b => b && (b.disabled = onLast));

    // Recalcular “Seleccionar todo” e indicador
    refreshCount();
  }

  // Integración: búsqueda
  if (q) {
    q.addEventListener('input', () => {
      state.term = q.value;
      applySearch();
    });
  }

  // Integración: ordenamiento (tu código ya ordena "rows" y las re-anexa)
  // Solo necesitamos volver a paginar después de cada click de orden:
  table.querySelectorAll('thead th[data-sort]').forEach((th) => {
    if (th.getAttribute('data-sort') === 'none') return;
    th.addEventListener('click', () => {
      // Después de reordenar "rows" por tu código original,
      // actualizamos estado.filtered con el mismo orden nuevo + filtro vigente:
      // (si hay término de búsqueda, filtramos el "rows" ordenado)
      const t = normalize(state.term);
      state.filtered = t
        ? rows.filter(r => r.innerText.toLowerCase().includes(t))
        : rows.slice();
      state.page = 1;
      renderPage();
    });
  });

  // Selección por clic/checkbox ya la tienes; solo ajustamos a "visibles de la página":
  function visibleInPage(tr) {
    return tr.style.display !== 'none';
  }

  function refreshCount() {
    const visibles = rows.filter(visibleInPage);
    const n = visibles.filter(r => r.querySelector('.row-check')?.checked).length;
    if (selCount) selCount.textContent = `${n} seleccionada${n===1?'':'s'}`;

    const visiblesChecked = visibles.length > 0 && visibles.every(r => r.querySelector('.row-check')?.checked);
    if (checkAll) {
      checkAll.indeterminate = !visiblesChecked && n > 0;
      checkAll.checked = visiblesChecked;
    }
  }

  // Re-enganchar selección individual (por si hay filas dinámicas)
  tbody.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', (e)=> {
      const tr = e.target.closest('tr');
      tr.classList.toggle('selected', e.target.checked);
      refreshCount();
    });
  });

  // Seleccionar todo (solo visibles de la página)
  if (checkAll) {
    checkAll.addEventListener('change', () => {
      const checked = checkAll.checked;
      rows.forEach(row => {
        if (!visibleInPage(row)) return;
        const cb = row.querySelector('.row-check');
        if (!cb) return;
        cb.checked = checked;
        row.classList.toggle('selected', checked);
      });
      refreshCount();
    });
  }

  // Clic en fila (mantener tu comportamiento)
  tbody.addEventListener('click', (e) => {
    if (!clickSelect?.checked) return;
    if (e.target.closest('button, .form-check-input, a, [data-bs-toggle]')) return;
    const tr = e.target.closest('tr');
    if (!tr || !visibleInPage(tr)) return;
    const cb = tr.querySelector('.row-check');
    if (!cb) return;
    cb.checked = !cb.checked;
    tr.classList.toggle('selected', cb.checked);
    refreshCount();
  });

  // Eventos de paginación
  if (perPageSel) perPageSel.addEventListener('change', () => {
    state.perPage = parseInt(perPageSel.value, 10) || 10;
    state.page = 1;
    renderPage();
  });

  btnFirst?.addEventListener('click', () => { state.page = 1; renderPage(); });
  btnPrev ?.addEventListener('click', () => { state.page--;  renderPage(); });
  btnNext ?.addEventListener('click', () => { state.page++;  renderPage(); });
  btnLast ?.addEventListener('click', () => { state.page = getTotalPages(); renderPage(); });

  // Inicializa
  applySearch(); // esto ya llama a renderPage()
})();
</script>

</body>
</html>
