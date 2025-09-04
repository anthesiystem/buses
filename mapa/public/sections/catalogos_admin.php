<?php
/************************************************************
 * catalogos_admin.php  —  Vista única para 4 catálogos
 * Tablas: categoria, dependencia, motor_base, tecnologia
 * Requiere: server/config.php con $pdo (PDO MySQL)
 ************************************************************/

// --- Conexión (ajusta la ruta si aplica) ---
session_start();
require_once '../../server/config.php';
require_once '../../server/bitacora_helper.php';

// (Opcional) Autenticación / ACL
// require_once __DIR__ . '/../../../server/auth.php';
// require_login_or_redirect();

// ----------------- Metadatos de tablas --------------------
$TABLES = [
  'categoria' => [
    'label'   => 'Categorías',
    'fields'  => [
      ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'text', 'required' => true, 'maxlength' => 100],
    ],
    'listCols' => ['ID','descripcion','fecha_creacion','fecha_modificacion','activo'],
    'hasFechaBaja' => false,
  ],
  'dependencia' => [
    'label'   => 'Dependencias',
    'fields'  => [
      ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'text', 'required' => true, 'maxlength' => 120],
      ['name' => 'siglas',      'label' => 'Siglas',      'type' => 'text', 'required' => true, 'maxlength' => 10],
    ],
    'listCols' => ['ID','descripcion','siglas','fecha_creacion','fecha_modificacion','activo'],
    'hasFechaBaja' => true,
  ],
  'motor_base' => [
    'label'   => 'Motores de Base',
    'fields'  => [
      ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'text', 'required' => true, 'maxlength' => 100],
    ],
    'listCols' => ['ID','descripcion','fecha_creacion','fecha_modificacion','activo'],
    'hasFechaBaja' => true,
  ],
  'tecnologia' => [
    'label'   => 'Tecnologías',
    'fields'  => [
      ['name' => 'numero_version', 'label' => 'Número de versión', 'type' => 'text', 'required' => true, 'maxlength' => 25],
      ['name' => 'descripcion',    'label' => 'Tecnología',         'type' => 'text', 'required' => true, 'maxlength' => 100],
    ],
    'listCols' => ['ID','numero_version','descripcion','fecha_creacion','fecha_modificacion','activo'],
    'hasFechaBaja' => true,
  ],
];

// Utilidades
function bad_request($msg='Solicitud inválida'){ 
  // Limpiar cualquier salida previa
  if (ob_get_level()) {
    ob_clean();
  }
  http_response_code(400); 
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'msg'=>$msg]); 
  exit; 
}
function ok($data=[]){ 
  // Limpiar cualquier salida previa
  if (ob_get_level()) {
    ob_clean();
  }
  http_response_code(200);
  header('Content-Type: application/json; charset=utf-8'); 
  echo json_encode(['ok'=>true]+$data); 
  exit; 
}
function tbl_is_allowed($t,$TABLES){ return isset($TABLES[$t]); }

// ------------------- API (AJAX) ---------------------------
if (isset($_GET['api'])) {
  // Configuración para API: no mostrar errores PHP en salida
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  
  // Limpiar cualquier salida previa
  if (ob_get_level()) {
    ob_clean();
  }
  ob_start();
  
  header('Content-Type: application/json; charset=utf-8');

  $action = $_GET['api'] ?? '';
  $tabla  = strtolower(trim($_POST['tabla'] ?? ($_GET['tabla'] ?? '')));

  if (!tbl_is_allowed($tabla, $TABLES)) {
    ob_clean();
    bad_request('Tabla no permitida: ' . $tabla);
  }

  // Columnas editables definidas en $TABLES
  $cols = array_column($TABLES[$tabla]['fields'], 'name');

  try {
    switch ($action) {
      case 'list': {
        $q = trim($_GET['q'] ?? '');
        $selectCols = "ID";
        if (!empty($cols)) $selectCols .= ", " . implode(",", $cols);
        $selectCols .= ", fecha_creacion, fecha_modificacion, CAST(activo AS UNSIGNED) AS activo";

        $sql = "SELECT $selectCols FROM `$tabla`";
        $params = [];
        if ($q !== '') {
          $likeCols = array_map(fn($c) => "$c LIKE ?", $cols);
          $sql .= " WHERE (" . implode(" OR ", $likeCols) . ")";
          $params = array_fill(0, count($likeCols), "%$q%");
        }
        $sql .= " ORDER BY " . (in_array('descripcion',$cols) ? "descripcion" : "ID") . " ASC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        ok(['rows'=>$rows, 'cols'=>$TABLES[$tabla]['listCols']]);
      }

      case 'get': {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) bad_request('ID inválido');

        $select = "SELECT ID";
        if (!empty($cols)) $select .= ", " . implode(",", $cols);
        $select .= ", CAST(activo AS UNSIGNED) AS activo FROM `$tabla` WHERE ID=?";

        $st = $pdo->prepare($select);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) bad_request('No encontrado');
        ok(['row'=>$row]);
      }

      case 'save': {
        // Validar que tenemos datos mínimos
        if (empty($_POST)) {
          bad_request('No se recibieron datos');
        }
        
        $id = (int)($_POST['ID'] ?? 0);
        $data = [];
        foreach ($cols as $c) { $data[$c] = trim((string)($_POST[$c] ?? '')); }

        // Validar datos requeridos
        foreach ($TABLES[$tabla]['fields'] as $field) {
          if (($field['required'] ?? false) && empty($data[$field['name']])) {
            bad_request("El campo '{$field['label']}' es requerido");
          }
        }

        try {
          $usuario_id = obtenerUsuarioSession();
          if (!$usuario_id || $usuario_id <= 0) {
            bad_request('No se pudo obtener información del usuario');
          }
        } catch (Exception $e) {
          error_log("Error obteniendo usuario: " . $e->getMessage());
          bad_request('Error de autenticación');
        }

        if ($id > 0) {
          // Obtener datos anteriores para el log
          $selectCols = implode(",", array_map(fn($c)=>"`$c`", $cols));
          $stmt_anterior = $pdo->prepare("SELECT $selectCols FROM `$tabla` WHERE ID = ?");
          $stmt_anterior->execute([$id]);
          $datos_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
          
          $sets = [];
          $params = [];
          foreach ($data as $c=>$v){ $sets[] = "`$c` = ?"; $params[] = $v; }
          $params[] = $id;
          $sql = "UPDATE `$tabla` SET ".implode(", ",$sets)." WHERE ID = ?";
          $st  = $pdo->prepare($sql);
          $st->execute($params);
          
          // Registrar en bitácora
          $nombre_item = $data['descripcion'] ?? $data[array_keys($data)[0]] ?? "ID $id";
          $cambios = [];
          foreach ($cols as $col) {
            if (($datos_anteriores[$col] ?? '') !== ($data[$col] ?? '')) {
              $cambios[] = "$col: '" . ($datos_anteriores[$col] ?? '') . "' → '" . ($data[$col] ?? '') . "'";
            }
          }
          $descripcion_bitacora = "Actualización en $tabla '$nombre_item'";
          if (!empty($cambios)) {
            $descripcion_bitacora .= " - Cambios: " . implode(", ", $cambios);
          }
          
          registrarBitacora(
            $pdo, 
            $usuario_id, 
            $tabla, 
            'UPDATE', 
            $descripcion_bitacora, 
            $id
          );
          
          ok(['msg'=>'Actualizado','id'=>$id]);
        } else {
          $colsSql = implode(",", array_map(fn($c)=>"`$c`", array_keys($data)));
          $qsSql   = implode(",", array_fill(0, count($data), "?"));
          $sql = "INSERT INTO `$tabla` ($colsSql) VALUES ($qsSql)";
          $st  = $pdo->prepare($sql);
          $st->execute(array_values($data));
          $new_id = $pdo->lastInsertId();
          
          // Registrar en bitácora
          $nombre_item = $data['descripcion'] ?? $data[array_keys($data)[0]] ?? "ID $new_id";
          $descripcion_bitacora = "Nuevo registro en $tabla '$nombre_item'";
          $detalles = [];
          foreach ($data as $key => $value) {
            if (!empty($value)) {
              $detalles[] = "$key: '$value'";
            }
          }
          if (!empty($detalles)) {
            $descripcion_bitacora .= " - " . implode(", ", $detalles);
          }
          
          registrarBitacora(
            $pdo, 
            $usuario_id, 
            $tabla, 
            'INSERT', 
            $descripcion_bitacora, 
            $new_id
          );
          
          ok(['msg'=>'Creado','id'=>$new_id]);
        }
      }

      case 'toggle': {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) bad_request('ID inválido');

        // Validar autenticación
        try {
          $usuario_id = obtenerUsuarioSession();
          if (!$usuario_id || $usuario_id <= 0) {
            bad_request('No se pudo obtener información del usuario');
          }
        } catch (Exception $e) {
          error_log("Error obteniendo usuario en toggle: " . $e->getMessage());
          bad_request('Error de autenticación');
        }

        // Obtener información del registro antes del cambio
        $selectCols = "CAST(activo AS UNSIGNED) AS act";
        if (in_array('descripcion', $cols)) {
          $selectCols .= ", descripcion";
        } else if (!empty($cols)) {
          $selectCols .= ", " . $cols[0];
        }
        $st_info = $pdo->prepare("SELECT $selectCols FROM `$tabla` WHERE ID=?");
        $st_info->execute([$id]);
        $info = $st_info->fetch(PDO::FETCH_ASSOC);
        
        if (!$info) bad_request('Registro no encontrado');
        
        $act = (int)$info['act'];
        $new = $act ? 0 : 1;
        $bit = $new ? "b'1'" : "b'0'";

        // Ejecutar el update
        try {
          if (!empty($TABLES[$tabla]['hasFechaBaja']) && $TABLES[$tabla]['hasFechaBaja']) {
            $sql = "UPDATE `$tabla`
                    SET activo = $bit,
                        fecha_baja = " . ($new ? "NULL" : "NOW()") . "
                    WHERE ID = ?";
          } else {
            $sql = "UPDATE `$tabla` SET activo = $bit WHERE ID = ?";
          }
          $st = $pdo->prepare($sql);
          $st->execute([$id]);
        } catch (Exception $e) {
          error_log("Error ejecutando UPDATE en toggle: " . $e->getMessage());
          bad_request('Error al actualizar el registro');
        }

        // Registrar en bitácora
        try {
          $nombre_item = $info['descripcion'] ?? $info[$cols[0]] ?? "ID $id";
          $accion = $new ? 'ACTIVAR' : 'DESACTIVAR';
          $accion_texto = $new ? 'activado' : 'desactivado';
          $descripcion_bitacora = "Registro en $tabla '$nombre_item' $accion_texto";
          
          registrarBitacora(
            $pdo, 
            $usuario_id, 
            $tabla, 
            $accion, 
            $descripcion_bitacora, 
            $id
          );
        } catch (Exception $e) {
          error_log("Error registrando en bitácora toggle: " . $e->getMessage());
          // No fallar la operación por error en bitácora
        }

        ok(['msg'=>'Estado actualizado','activo'=>$new]);
      }

      default:
        bad_request('Acción no reconocida');
    }
  } catch (Throwable $e) {
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
      ob_clean();
    }
    
    // Log del error completo para debugging
    error_log("Error en catalogos_admin.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Respuesta JSON de error
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'ok' => false, 
      'msg' => 'Error interno del servidor: ' . $e->getMessage(),
      'debug' => [
        'action' => $action ?? 'unknown',
        'tabla' => $tabla ?? 'unknown',
        'file' => $e->getFile(),
        'line' => $e->getLine()
      ]
    ]);
    exit;
  }
}

// ---------------- Render de la vista (HTML) ---------------
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <title>Catálogos (Categoría / Dependencia / Motor Base / Tecnología)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
<link href="../server/style/bootstrap.min.css" rel="stylesheet">
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
    .table-card{
      background:#fff; border:1px solid var(--table-border);
      border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.04);
    }
    .table-responsive{ max-height:70vh; }
    .table-brand thead th{
      background:var(--header-bg);
      border-bottom:1px solid var(--header-border); color:var(--muted);
      font-weight:700; text-transform:uppercase; font-size:.78rem; letter-spacing:.5px; cursor:pointer;
    }
    .table-brand tbody td{ vertical-align:middle; border-color:var(--table-border); }
    .table-brand tbody tr:hover{ background:var(--row-hover); }
    .table-brand tbody tr.selected{ background:var(--row-selected); box-shadow:inset 4px 0 0 var(--brand); }
    .badge-soft{ background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb; font-weight:600; }
    .actions .btn{ padding:.25rem .5rem; }
    @media (max-width:768px){
      .col-sm-hide{ display:none; }
      .actions .btn .text{ display:none; }
    }
    
    .toolbar{ gap:.5rem; }
    .density-compact table tbody tr td{ padding:.35rem .5rem; }
    .state-dot{ display:inline-block; width:.65rem; height:.65rem; border-radius:50%; vertical-align:middle; margin-right:.35rem; }
    .dot-on{ background:#16a34a; }  /* activo */
    .dot-off{ background:#9ca3af; } /* inactivo */
    .table thead th{ white-space:nowrap; }
    .badge-on  { background:#198754; }
    .badge-off { background:#6c757d; }
    .table-wrap{ overflow:auto; }

    #main-content {
    max-width: 90%;
    padding-left: 12%;
    padding-top: 5%;}
  </style>
</head>
<body class="p-3">
  <div class="container-fluid">
    <h3 class="mb-3">Administración de Catálogos</h3>

    <!-- NAV TABS -->
    <ul class="nav nav-tabs" id="tabsCat" role="tablist">
      <?php $i=0; foreach($TABLES as $key=>$meta): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $i===0?'active':'' ?>" id="tab-<?= $key ?>" data-bs-toggle="tab"
                  data-bs-target="#pane-<?= $key ?>" type="button" role="tab"
                  aria-controls="pane-<?= $key ?>" aria-selected="<?= $i===0?'true':'false' ?>"
                  data-tabla="<?= $key ?>">
            <?= htmlspecialchars($meta['label']) ?>
          </button>
        </li>
      <?php $i++; endforeach; ?>
    </ul>

    <div class="tab-content border border-top-0 p-3 rounded-bottom">
      <?php $i=0; foreach($TABLES as $key=>$meta): ?>
        <div class="tab-pane fade <?= $i===0?'show active':'' ?>" id="pane-<?= $key ?>" role="tabpanel" aria-labelledby="tab-<?= $key ?>">
          <!-- Toolbar -->
          <div class="d-flex flex-wrap align-items-center mb-3 toolbar">
            <div class="input-group" style="max-width:400px;">
              <span class="input-group-text">Buscar</span>
              <input class="form-control" type="search" placeholder="Texto" data-role="search" data-tabla="<?= $key ?>">
            </div>

            <button class="btn btn-primary" onclick="openNew('<?= $key ?>')">
              <i class="bi bi-plus-lg"></i> Nuevo
            </button>

            <div class="form-check form-switch ms-auto">
              <input class="form-check-input" type="checkbox" id="density-<?= $key ?>" onchange="toggleDensity('<?= $key ?>')">
              <label class="form-check-label" for="density-<?= $key ?>">Compacta</label>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-card">
            <div class="table-responsive">
              <table class="table table-hover table-brand align-middle m-0" id="table-<?= $key ?>">
                <thead>
                  <tr>
                    <?php foreach($meta['listCols'] as $c): ?>
                      <th><?= htmlspecialchars(ucfirst(str_replace('_',' ',$c))) ?></th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- Paginación (cliente) -->
          <div class="d-flex justify-content-center mt-3" id="pager-<?= $key ?>"></div>
        </div>
      <?php $i++; endforeach; ?>
    </div>
  </div>

  <!-- Modal Alta/Edición -->
  <div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="formCat">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="f_ID">
          <input type="hidden" name="tabla" id="f_tabla">

          <div id="formFields" class="row g-3">
            <!-- se llenará dinámicamente -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap + Icons -->
  <script src="/final/mapa/server/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../server/font/bootstrap-icons.css">
  <script>
  // Usa el base global si lo tienes en el layout: window.APP_BASE = "/final/mapa/public/";
  const BASE = (window.APP_BASE || "/final/mapa/public/").replace(/\/+$/,'') + "/";
  // Este archivo PHP es el endpoint de la API:
  const ENDPOINT = BASE + "sections/catalogos_admin.php";
</script>

  <script>
    // ---------- Config del lado del cliente (coincide con PHP) ----------
    const TABLES = <?= json_encode($TABLES, JSON_UNESCAPED_UNICODE) ?>;

    // Estado UI
    let activeTab = Object.keys(TABLES)[0];
    const pagerSize = 10; // filas por página (cliente)
    const cache = {};     // cache por tabla: { rows:[], q:'' }

    // Helpers
    const $ = sel => document.querySelector(sel);
    const $all = sel => document.querySelectorAll(sel);
    const modal = new bootstrap.Modal(document.getElementById('modalForm'));

    // Cargar al iniciar
    function initCatalogosAdmin(root=document) {
      console.log('Iniciando catalogos admin...', root);
      
      // Verificar que tenemos las tablas necesarias
      if (!TABLES || Object.keys(TABLES).length === 0) {
        console.error('TABLES no está definido o está vacío');
        return;
      }
      
      // Cargar primera pestaña inmediatamente
      const firstTable = Object.keys(TABLES)[0];
      console.log('Cargando primera tabla:', firstTable);
      
      loadTable(firstTable).then(() => {
        console.log('Primera tabla cargada, cargando el resto...');
        // Cargar el resto en segundo plano
        Object.keys(TABLES).slice(1).forEach(t => {
          console.log('Cargando tabla:', t);
          loadTable(t);
        });
      }).catch(err => {
        console.error('Error cargando primera tabla:', err);
      });

      // Cambios de pestaña
      root.querySelectorAll('[data-tabla]').forEach(btn => {
        console.log('Configurando evento para tab:', btn.getAttribute('data-tabla'));
        btn.addEventListener('shown.bs.tab', ev => {
          activeTab = ev.target.getAttribute('data-tabla');
          console.log('Cambiando a tab:', activeTab);
          if (!cache[activeTab]) {
            console.log('Cargando nueva tab:', activeTab);
            loadTable(activeTab);
          }
        });
      });

      // Buscar
      root.querySelectorAll('input[data-role="search"]').forEach(i => {
        console.log('Configurando busqueda para:', i.getAttribute('data-tabla'));
        i.addEventListener('input', debounce(() => {
          const tabla = i.getAttribute('data-tabla');
          console.log('Buscando en tabla:', tabla, i.value.trim());
          loadTable(tabla, i.value.trim());
        }, 300));
      });

      // Submit modal
      const form = root.querySelector('#formCat');
      if (form) {
        console.log('Configurando evento submit del formulario');
        form.addEventListener('submit', saveForm);
      } else {
        console.warn('Formulario #formCat no encontrado');
      }
    }

    // Función para reinicializar cuando se carga en contenedor
    function reinitCatalogos() {
      console.log('🔄 Reinicializando catálogos...');
      
      // Verificar si realmente necesitamos reinicializar
      const tabsContainer = document.getElementById('tabsCat');
      if (!tabsContainer) {
        console.log('📋 No hay contenido de catálogos para reinicializar');
        return;
      }
      
      // Limpiar cache
      Object.keys(cache).forEach(k => delete cache[k]);
      
      // Limpiar event listeners previos
      const existingElements = document.querySelectorAll('[data-tabla], input[data-role="search"], #formCat');
      existingElements.forEach(el => {
        const newEl = el.cloneNode(true);
        el.parentNode.replaceChild(newEl, el);
      });
      
      // Reiniciar con límite de intentos
      setTimeout(() => {
        initWhenReady(3, 1); // Máximo 3 intentos en reinicialización
      }, 100);
    }

    // Función de inicialización robusta
    function initWhenReady(maxAttempts = 10, currentAttempt = 1) {
      // Verificar si los elementos necesarios están presentes
      const tabsContainer = document.getElementById('tabsCat');
      const firstTable = document.querySelector('table[id^="table-"]');
      
      if (!tabsContainer || !firstTable) {
        if (currentAttempt >= maxAttempts) {
          console.warn(`⚠️ No se pudieron encontrar los elementos de catálogos después de ${maxAttempts} intentos. Saltando inicialización.`);
          return;
        }
        
        console.log(`🔄 Elementos no encontrados (intento ${currentAttempt}/${maxAttempts}), reintentando...`);
        setTimeout(() => initWhenReady(maxAttempts, currentAttempt + 1), 200);
        return;
      }
      
      console.log('✅ Elementos encontrados, inicializando catálogos...');
      initCatalogosAdmin(document);
      
      // Disparar evento de inicialización completada
      window.dispatchEvent(new CustomEvent('catalogos-initialized'));
    }

    // Init en carga directa
    if (document.readyState !== 'loading') {
      initWhenReady();
    } else {
      document.addEventListener('DOMContentLoaded', initWhenReady);
    }

    // Exponer funciones para llamada externa
    window.reinitCatalogos = reinitCatalogos;
    window.initCatalogosAdmin = initCatalogosAdmin;
    window.initCatalogosWhenReady = initWhenReady;

    // Auto-detección de carga dinámica
    // Si el script se ejecuta y no hay elementos en el DOM inicial,
    // probablemente se está cargando dinámicamente
    const scriptElement = document.currentScript;
    if (scriptElement) {
      // Marcar que este script ya se ejecutó
      scriptElement.setAttribute('data-catalogos-loaded', 'true');
    }

    // Observer para detectar cuando se agrega contenido al DOM
    if (typeof MutationObserver !== 'undefined') {
      let observerActive = true;
      let initializationPending = false;
      
      const observer = new MutationObserver((mutations) => {
        if (!observerActive || initializationPending) return;
        
        mutations.forEach((mutation) => {
          if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
            mutation.addedNodes.forEach((node) => {
              if (node.nodeType === Node.ELEMENT_NODE) {
                // Verificar si se agregó contenido de catálogos
                const tabsCat = node.querySelector ? node.querySelector('#tabsCat') : null;
                const isTabsCat = node.id === 'tabsCat';
                
                if (tabsCat || isTabsCat) {
                  console.log('🔍 Detectado contenido de catálogos agregado al DOM');
                  
                  if (!initializationPending) {
                    initializationPending = true;
                    // Esperar un poco para que el DOM se estabilice
                    setTimeout(() => {
                      initWhenReady(5, 1); // Máximo 5 intentos desde el observer
                      initializationPending = false;
                    }, 300);
                  }
                }
              }
            });
          }
        });
      });

      // Observar cambios en el documento
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
      
      // Desactivar observer después de una inicialización exitosa
      window.addEventListener('catalogos-initialized', () => {
        console.log('📋 Catálogos inicializados, desactivando observer');
        observerActive = false;
        observer.disconnect();
      });
    }


    // Debounce
    function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms);} }

    // Cargar tabla
    async function loadTable(tabla, q='') {
      try {
        console.log(`Cargando tabla ${tabla} con query: "${q}"`);
        const url = `${ENDPOINT}?api=list&tabla=${encodeURIComponent(tabla)}&q=${encodeURIComponent(q)}`;
        console.log('URL:', url);
        
        const resp = await fetch(url);
        console.log('Response status:', resp.status);
        
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
        }
        
        const json = await resp.json();
        console.log('Response JSON:', json);
        
        if(!json.ok){ 
          console.error('API error:', json.msg);
          alert(json.msg||'Error al cargar'); 
          return; 
        }

        cache[tabla] = { rows: json.rows, q };
        console.log(`Tabla ${tabla} cargada con ${json.rows.length} filas`);
        renderTable(tabla, 1);
      } catch (error) {
        console.error('Error en loadTable:', error);
        alert('Error al cargar los datos: ' + error.message);
      }
    }

    function renderTable(tabla, page=1){
      console.log(`🎨 Renderizando tabla ${tabla}, página ${page}`);
      const rows = (cache[tabla]?.rows)||[];
      const cols = TABLES[tabla].listCols;
      const tbody = document.querySelector(`#table-${tabla} tbody`);
      const pager = document.getElementById(`pager-${tabla}`);
      
      if (!tbody) {
        console.error(`❌ No se encontró tbody para tabla ${tabla}`);
        return;
      }
      
      tbody.innerHTML = '';

      const start = (page-1)*pagerSize;
      const slice = rows.slice(start, start+pagerSize);
      console.log(`📊 Mostrando ${slice.length} filas de ${rows.length} total`);

      for (const r of slice){
        const tr = document.createElement('tr');

        for (const c of cols){
          const td = document.createElement('td');
          let val = r[c] ?? '';
          if (c === 'activo'){
            td.innerHTML = `<span class="state-dot ${r.activo==1?'dot-on':'dot-off'}"></span>
                            <span class="badge ${r.activo==1?'badge-on':'badge-off'}">${r.activo==1?'ACTIVO':'INACTIVO'}</span>`;
          } else {
            td.textContent = val ?? '';
          }
          tr.appendChild(td);
        }

        // Acciones
        const tdAcc = document.createElement('td');
        tdAcc.innerHTML = `
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" title="Editar" onclick="openEdit('${tabla}', ${r.ID})">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-outline-secondary" title="Activar/Desactivar" onclick="toggleActivo('${tabla}', ${r.ID})">
              <i class="bi ${r.activo==1?'bi-toggle-on':'bi-toggle-off'}"></i>
            </button>
          </div>`;
        tr.appendChild(tdAcc);

        tbody.appendChild(tr);
      }

      // Paginación simple
      if (pager) {
        const pages = Math.max(1, Math.ceil(rows.length / pagerSize));
        let html = `<nav><ul class="pagination pagination-sm mb-0">`;
        for (let p=1; p<=pages; p++){
          html += `<li class="page-item ${p===page?'active':''}">
                    <a class="page-link" href="javascript:void(0)" onclick="renderTable('${tabla}', ${p})">${p}</a>
                  </li>`;
        }
        html += `</ul></nav>`;
        pager.innerHTML = html;
      }
      
      console.log(`✅ Tabla ${tabla} renderizada correctamente`);
    }

    // Densidad
    function toggleDensity(tabla){
      const pane = document.getElementById(`pane-${tabla}`);
      pane.classList.toggle('density-compact');
    }

    // Abrir modal: nuevo
    function openNew(tabla){
      $('#modalTitle').textContent = `Nuevo — ${TABLES[tabla].label}`;
      $('#f_ID').value = '';
      $('#f_tabla').value = tabla;
      buildFormFields(tabla, null);
      modal.show();
      setTimeout(()=>{ const f = $('#formFields input, #formFields select, #formFields textarea'); if(f) f.focus(); }, 100);
    }

    // Abrir modal: editar
    async function openEdit(tabla, id){
      const r = await fetch(`${ENDPOINT}?api=get&tabla=${encodeURIComponent(tabla)}&id=${id}`).then(r=>r.json());

      if(!r.ok){ alert(r.msg||'No se pudo cargar'); return; }
      $('#modalTitle').textContent = `Editar — ${TABLES[tabla].label}`;
      $('#f_ID').value = r.row.ID;
      $('#f_tabla').value = tabla;
      buildFormFields(tabla, r.row);
      modal.show();
    }

    // Construye inputs dinámicamente
    function buildFormFields(tabla, row){
      const wrap = $('#formFields');
      wrap.innerHTML = '';
      const fields = TABLES[tabla].fields;
      for (const f of fields){
        const val = row ? (row[f.name] ?? '') : '';
        const col = document.createElement('div');
        col.className = 'col-12';
        col.innerHTML = `
          <label class="form-label">${f.label}${f.required?' *':''}</label>
          <input type="${f.type}" class="form-control"
                 name="${f.name}" id="f_${f.name}"
                 maxlength="${f.maxlength||''}"
                 value="${escapeHtml(val)}" ${f.required?'required':''}>
        `;
        wrap.appendChild(col);
      }
    }

    // Guardar
    async function saveForm(ev){
      ev.preventDefault();
      const fd = new FormData(ev.target);
      
      // Debug: mostrar qué datos se están enviando
      console.log('📤 Enviando datos:');
      for (let [key, value] of fd.entries()) {
        console.log(`  ${key}: ${value}`);
      }
      
      try {
        const resp = await fetch(`${ENDPOINT}?api=save`, { 
          method:'POST', 
          body:fd,
          headers: {
            'Accept': 'application/json'
          }
        });

        console.log('📨 Response status:', resp.status);
        console.log('📨 Response headers:', resp.headers.get('content-type'));

        // Verificar el tipo de contenido
        const contentType = resp.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const text = await resp.text();
          console.error('❌ Respuesta no es JSON:', text);
          alert('Error del servidor: La respuesta no es válida. Revisa la consola para más detalles.');
          return;
        }

        const json = await resp.json();
        console.log('📥 Response JSON:', json);
        
        if(!json.ok){ 
          console.error('❌ Error de API:', json.msg);
          alert(json.msg||'Error al guardar'); 
          return; 
        }
        
        console.log('✅ Guardado exitoso');
        modal.hide();
        loadTable($('#f_tabla').value);
        
      } catch (error) {
        console.error('❌ Error en saveForm:', error);
        if (error instanceof SyntaxError && error.message.includes('JSON')) {
          alert('Error: El servidor devolvió una respuesta inválida. Puede haber un error de PHP.');
        } else {
          alert('Error al guardar: ' + error.message);
        }
      }
    }

    // Toggle activo
async function toggleActivo(tabla, id) {
  if (!confirm('¿Cambiar estado activo/inactivo?')) return;

  // Deshabilitar el botón durante la operación
  const button = event.target.closest('button');
  const originalContent = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

  try {
    console.log(`🔄 Cambiando estado de ${tabla} ID: ${id}`);
    
    const url = `${ENDPOINT}?api=toggle`;
    const fd = new FormData();
    fd.append('tabla', tabla);
    fd.append('id', id);

    console.log('📤 Enviando datos de toggle:');
    for (let [key, value] of fd.entries()) {
      console.log(`  ${key}: ${value}`);
    }

    const resp = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    });

    console.log('📨 Toggle response status:', resp.status);
    console.log('📨 Toggle response headers:', resp.headers.get('content-type'));

    // Verificar el tipo de contenido
    const contentType = resp.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await resp.text();
      console.error('❌ Respuesta toggle no es JSON:', text);
      console.error('❌ Content-Type recibido:', contentType);
      console.error('❌ Status:', resp.status);
      console.error('❌ Headers completos:', [...resp.headers.entries()]);
      alert('Error del servidor: La respuesta no es válida (Content-Type: ' + contentType + '). Revisa la consola para más detalles.');
      return;
    }

    const json = await resp.json();
    console.log('📥 Toggle response JSON:', json);

    if (!json.ok) { 
      console.error('❌ Error de API toggle:', json.msg);
      alert(json.msg || 'Error al actualizar estado'); 
      return; 
    }

    console.log('✅ Estado actualizado exitosamente');
    
    // Recargar la tabla específica en lugar de depender de activeTab
    const currentQuery = cache[tabla]?.q || '';
    console.log(`🔄 Recargando tabla ${tabla} con query: "${currentQuery}"`);
    await loadTable(tabla, currentQuery);
    
    // También actualizar activeTab si es necesario
    const currentTab = document.querySelector('.nav-tabs .nav-link.active');
    if (currentTab) {
      const currentTabla = currentTab.getAttribute('data-tabla');
      if (currentTabla !== tabla) {
        console.log(`⚠️ Tab activo (${currentTabla}) diferente a tabla modificada (${tabla})`);
      }
      activeTab = currentTabla || tabla;
    } else {
      activeTab = tabla;
    }
    
  } catch (error) {
    console.error('❌ Error en toggleActivo:', error);
    if (error instanceof SyntaxError && error.message.includes('JSON')) {
      alert('Error: El servidor devolvió una respuesta inválida. Puede haber un error de PHP.');
    } else {
      alert('Error al cambiar el estado: ' + error.message);
    }
  } finally {
    // Restaurar el botón
    if (button) {
      button.disabled = false;
      button.innerHTML = originalContent;
    }
  }
}



    // Utilidad: escapado simple
    function escapeHtml(s){ return String(s??'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  </script>
  

</body>
</html>
