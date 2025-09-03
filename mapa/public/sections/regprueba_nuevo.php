<?php
// registros.php
session_start();
require_once '../../server/config.php';
require_once '../../server/bitacora_helper.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.php");
  exit;
}

/* ===========================
   POST: insertar / actualizar
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
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
  $usuario_info = obtenerUsuarioSession();
  $user_id = is_array($usuario_info) ? $usuario_info['user_id'] : $usuario_info;
  
  if ($ID > 0) {
    // Obtener datos anteriores para el log
    $stmt_anterior = $pdo->prepare("
      SELECT r.*, 
             e.descripcion AS entidad_nombre,
             d.descripcion AS dependencia_nombre,
             b.descripcion AS bus_nombre,
             t.descripcion AS tecnologia_nombre
      FROM registro r
      LEFT JOIN entidad e ON e.ID = r.Fk_entidad
      LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia  
      LEFT JOIN bus b ON b.ID = r.Fk_bus
      LEFT JOIN tecnologia t ON t.ID = r.Fk_tecnologia
      WHERE r.ID = ?
    ");
    $stmt_anterior->execute([$ID]);
    $datos_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
    
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
    
    // Registrar en bitácora
    $descripcion_bitacora = "Registro ID $ID actualizado";
    if ($datos_anteriores) {
      $descripcion_bitacora .= " - Entidad: " . ($datos_anteriores['entidad_nombre'] ?? 'N/A');
      if ($datos_anteriores['dependencia_nombre']) {
        $descripcion_bitacora .= ", Dependencia: " . $datos_anteriores['dependencia_nombre'];
      }
      if ($datos_anteriores['bus_nombre']) {
        $descripcion_bitacora .= ", Bus: " . $datos_anteriores['bus_nombre'];
      }
    }
    
    registrarBitacora(
      $pdo, 
      $user_id, 
      'registro', 
      'UPDATE', 
      $descripcion_bitacora, 
      $ID
    );
    
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
    $ID = $pdo->lastInsertId();
    
    // Obtener nombres para el log
    $stmt_nombres = $pdo->prepare("
      SELECT e.descripcion AS entidad_nombre,
             d.descripcion AS dependencia_nombre,
             b.descripcion AS bus_nombre,
             t.descripcion AS tecnologia_nombre
      FROM registro r
      LEFT JOIN entidad e ON e.ID = r.Fk_entidad
      LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia  
      LEFT JOIN bus b ON b.ID = r.Fk_bus
      LEFT JOIN tecnologia t ON t.ID = r.Fk_tecnologia
      WHERE r.ID = ?
    ");
    $stmt_nombres->execute([$ID]);
    $nombres = $stmt_nombres->fetch(PDO::FETCH_ASSOC);
    
    // Registrar en bitácora
    $descripcion_bitacora = "Nuevo registro ID $ID creado";
    if ($nombres) {
      $descripcion_bitacora .= " - Entidad: " . ($nombres['entidad_nombre'] ?? 'N/A');
      if ($nombres['dependencia_nombre']) {
        $descripcion_bitacora .= ", Dependencia: " . $nombres['dependencia_nombre'];
      }
      if ($nombres['bus_nombre']) {
        $descripcion_bitacora .= ", Bus: " . $nombres['bus_nombre'];
      }
      if ($nombres['tecnologia_nombre']) {
        $descripcion_bitacora .= ", Tecnología: " . $nombres['tecnologia_nombre'];
      }
    }
    
    registrarBitacora(
      $pdo, 
      $user_id, 
      'registro', 
      'INSERT', 
      $descripcion_bitacora, 
      $ID
    );
  }

  // 5) Responder con JSON para AJAX
  $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

  if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'ok'     => true,
      'status' => ($ID > 0 ? 'updated' : 'created'),
      'id'     => $ID
    ]);
    exit;
  }

  // Si NO es AJAX, redirigir con parámetros
  $baseUrl = 'regprueba.php?ok=' . ($ID > 0 ? 'updated' : 'created');
  header("Location: $baseUrl");
  exit;

  } catch (Exception $e) {
    error_log("Error en regprueba.php: " . $e->getMessage());
    
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'ok'  => false,
        'msg' => 'Error interno del servidor: ' . $e->getMessage()
      ]);
      exit;
    } else {
      header("Location: regprueba.php?error=" . urlencode($e->getMessage()));
      exit;
    }
  }
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

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="../server/style/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="../server/font/bootstrap-icons.css" rel="stylesheet">

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
            <th style="width:42px">
              <input class="form-check-input" type="checkbox" id="checkAll" title="Seleccionar todo">
            </th>
            <th>ID</th>
            <th>Entidad</th>
            <th class="col-sm-hide">Dependencia</th>
            <th>Bus</th>
            <th>Engine</th>
            <th class="col-sm-hide">Tecnología</th>
            <th>Estado</th>
            <th>Etapa / Avance</th>
            <th class="col-sm-hide">Inicio</th>
            <th class="col-sm-hide">Migración</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyReg">
          <!-- Se llenará dinámicamente -->
        </tbody>
      </table>

      <!-- Paginación -->
      <div id="paginacion" class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 p-2 border-top">
        <div class="d-flex align-items-center gap-2">
          <label for="perPage" class="form-label m-0">Filas por página:</label>
          <select id="perPage" class="form-select form-select-sm" style="width:auto">
            <option value="10" selected>10</option>
            <option value="20">20</option>
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

  <!-- Modal -->
  <div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content modal-modern">
        <form id="formRegistro" method="post" action="regprueba.php">
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
      <img src="../mapa/public/img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
      <div class="mt-2 fw-semibold">Espere un momento...</div>
    </div>
  </div>

  <div id="guardadoExitoAnimado" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2050; background:rgba(255,255,255,0.95); padding:30px 40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.2); text-align:center;">
    <div style="font-size:60px; color:green;">
      <img src="../mapa/public/img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
    </div>
    <div style="font-size:18px; margin-top:10px;">Guardado exitosamente</div>
  </div>

  <!-- JS -->
  <script src="../server/js/bootstrap.bundle.min.js"></script>

  <script>
  /* =======================
     Paginación con AJAX
     ======================= */
  let rowsPerPage = 10;
  let currentPage = 1;
  let totalPages = 1;
  let totalRegistros = 0;

  function setVisibleRows() {
    const rowHeight = 48;
    const header = document.querySelector('.header-container');
    const subHeader = document.querySelector('.sub-header');
    const buttonBar = document.querySelector('.custom-button-bar');

    const totalOffset =
      (header?.offsetHeight ?? 0) +
      (subHeader?.offsetHeight ?? 0) +
      (buttonBar?.offsetHeight ?? 0) +
      280;

    const availableHeight = window.innerHeight - totalOffset;
    const visibleRows = Math.floor(availableHeight / rowHeight) - 1;

    rowsPerPage = visibleRows > 0 ? visibleRows : 10;
    currentPage = 1;
    cargarDatos();
  }

  function cargarDatos() {
    fetch(`mis_registros.php?page=${currentPage}&rowsPerPage=${rowsPerPage}`)
      .then(res => res.json())
      .then(data => {
        document.querySelector("#tbodyReg").innerHTML = data.html;
        totalRegistros = data.total;
        totalPages = data.totalPages;
        updatePageInfo();
        
        // Reconectar eventos después de cargar nuevo HTML
        reconectarEventos();
      })
      .catch(error => {
        console.error('Error cargando datos:', error);
      });
  }

  function updatePageInfo() {
    document.getElementById("pageInfo").textContent = `Página ${currentPage} / ${totalPages || 1}`;

    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(start + rowsPerPage - 1, totalRegistros);

    document.getElementById("rangeInfo").textContent =
      totalRegistros > 0
        ? `Mostrando ${start}–${end} de ${totalRegistros}`
        : "Mostrando 0–0 de 0";
  }

  function goToPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    cargarDatos();
  }

  function reconectarEventos() {
    // Reconectar eventos de selección para las nuevas filas
    const tbody = document.querySelector('#tbodyReg');
    const clickSelect = document.getElementById('clickSelect');
    const checkAll = document.getElementById('checkAll');
    const selCount = document.getElementById('selCount');
    
    // Eventos para checkboxes individuales
    tbody.querySelectorAll('.row-check').forEach(cb => {
      cb.addEventListener('change', (e) => {
        const tr = e.target.closest('tr');
        tr.classList.toggle('selected', e.target.checked);
        refreshCount();
      });
    });

    // Clic en fila para seleccionar (remover evento anterior si existe)
    const existingHandler = tbody.getAttribute('data-click-handler');
    if (existingHandler === 'true') {
      // Clonar el tbody para remover todos los event listeners
      const newTbody = tbody.cloneNode(true);
      tbody.parentNode.replaceChild(newTbody, tbody);
      // Actualizar referencia
      const updatedTbody = document.querySelector('#tbodyReg');
      
      // Reagregar eventos para checkboxes
      updatedTbody.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', (e) => {
          const tr = e.target.closest('tr');
          tr.classList.toggle('selected', e.target.checked);
          refreshCount();
        });
      });
      
      updatedTbody.addEventListener('click', handleRowClick);
      updatedTbody.setAttribute('data-click-handler', 'true');
    } else {
      tbody.addEventListener('click', handleRowClick);
      tbody.setAttribute('data-click-handler', 'true');
    }

    function handleRowClick(e) {
      if (!clickSelect?.checked) return;
      if (e.target.closest('button, .form-check-input, a, [data-bs-toggle]')) return;
      const tr = e.target.closest('tr');
      if (!tr) return;
      const cb = tr.querySelector('.row-check');
      if (!cb) return;
      cb.checked = !cb.checked;
      tr.classList.toggle('selected', cb.checked);
      refreshCount();
    }

    function refreshCount() {
      const tbody = document.querySelector('#tbodyReg');
      const visibles = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
      const n = visibles.filter(r => r.querySelector('.row-check')?.checked).length;
      if (selCount) selCount.textContent = `${n} seleccionada${n===1?'':'s'}`;

      const visiblesChecked = visibles.length > 0 && visibles.every(r => r.querySelector('.row-check')?.checked);
      if (checkAll) {
        checkAll.indeterminate = !visiblesChecked && n > 0;
        checkAll.checked = visiblesChecked;
      }
    }
  }

  // Eventos de paginación
  document.getElementById("btnFirst").addEventListener("click", () => goToPage(1));
  document.getElementById("btnPrev").addEventListener("click", () => goToPage(currentPage - 1));
  document.getElementById("btnNext").addEventListener("click", () => goToPage(currentPage + 1));
  document.getElementById("btnLast").addEventListener("click", () => goToPage(totalPages));

  document.getElementById("perPage").addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value, 10);
    currentPage = 1;
    cargarDatos();
  });

  // Seleccionar todo (solo filas visibles de la página actual)
  document.getElementById("checkAll").addEventListener("change", (e) => {
    const checked = e.target.checked;
    const tbody = document.querySelector('#tbodyReg');
    tbody.querySelectorAll('.row-check').forEach(cb => {
      cb.checked = checked;
      const tr = cb.closest('tr');
      tr.classList.toggle('selected', checked);
    });
    
    const n = checked ? tbody.querySelectorAll('.row-check').length : 0;
    document.getElementById('selCount').textContent = `${n} seleccionada${n===1?'':'s'}`;
  });

  // Exponer estado para otros scripts
  window.paginationState = {
    get page() { return currentPage; },
    get perPage() { return rowsPerPage; },
    get term() { return ''; } // Para compatibilidad con búsqueda futura
  };

  window.addEventListener("resize", setVisibleRows);
  window.addEventListener("load", () => setVisibleRows());
  </script>

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
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        const btns = form.querySelectorAll('button, input[type="submit"]');
        btns.forEach(b => b.disabled = true);
        overlayCargando.style.display = 'block';

        try {
          const fd = new FormData(form);
          fd.set('ajax', '1');

          const resp = await fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          if (!resp.ok) {
            throw new Error('Error del servidor: ' + resp.status);
          }

          const data = await resp.json();

          if (data.ok) {
            // Cierra modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistro'));
            modal && modal.hide();

            // Mostrar éxito
            if (overlayExito) {
              if (data.status === 'updated') {
                overlayExito.querySelector('div:last-child').textContent = 'Actualizado exitosamente';
              }
              overlayExito.style.display = 'block';
              setTimeout(() => {
                overlayExito.style.display = 'none';
              }, 1200);
            }

            // Recargar datos
            cargarDatos();
          } else {
            alert(data.msg || 'No se pudo guardar.');
          }
        } catch (err) {
          console.error('Error:', err);
          alert('Error de conexión: ' + err.message);
        } finally {
          overlayCargando.style.display = 'none';
          btns.forEach(b => b.disabled = false);
        }
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
  })();
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
  });
  </script>



</body>
</html>
