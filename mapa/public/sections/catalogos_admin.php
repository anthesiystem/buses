<?php
/************************************************************
 * catalogos_admin.php  —  Vista única para 4 catálogos
 * Tablas: categoria, dependencia, motor_base, tecnologia
 * Requiere: server/config.php con $pdo (PDO MySQL)
 ************************************************************/

// --- Conexión (ajusta la ruta si aplica) ---
require_once '../../server/config.php';

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
function bad_request($msg='Solicitud inválida'){ http_response_code(400); echo json_encode(['ok'=>false,'msg'=>$msg]); exit; }
function ok($data=[]){ header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true]+$data); exit; }
function tbl_is_allowed($t,$TABLES){ return isset($TABLES[$t]); }

// ------------------- API (AJAX) ---------------------------
if (isset($_GET['api'])) {
  header('Content-Type: application/json; charset=utf-8');

  $action = $_GET['api'] ?? '';
  $tabla  = strtolower(trim($_POST['tabla'] ?? ($_GET['tabla'] ?? '')));

  if (!tbl_is_allowed($tabla, $TABLES)) {
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
        $id = (int)($_POST['ID'] ?? 0);
        $data = [];
        foreach ($cols as $c) { $data[$c] = trim((string)($_POST[$c] ?? '')); }

        if ($id > 0) {
          $sets = [];
          $params = [];
          foreach ($data as $c=>$v){ $sets[] = "`$c` = ?"; $params[] = $v; }
          $params[] = $id;
          $sql = "UPDATE `$tabla` SET ".implode(", ",$sets)." WHERE ID = ?";
          $st  = $pdo->prepare($sql);
          $st->execute($params);
          ok(['msg'=>'Actualizado','id'=>$id]);
        } else {
          $colsSql = implode(",", array_map(fn($c)=>"`$c`", array_keys($data)));
          $qsSql   = implode(",", array_fill(0, count($data), "?"));
          $sql = "INSERT INTO `$tabla` ($colsSql) VALUES ($qsSql)";
          $st  = $pdo->prepare($sql);
          $st->execute(array_values($data));
          ok(['msg'=>'Creado','id'=>$pdo->lastInsertId()]);
        }
      }

      case 'toggle': {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) bad_request('ID inválido');

        // Lee estado actual
        $st = $pdo->prepare("SELECT CAST(activo AS UNSIGNED) AS act FROM `$tabla` WHERE ID=?");
        $st->execute([$id]);
        $act = (int)$st->fetchColumn();

        $new = $act ? 0 : 1;
        $bit = $new ? "b'1'" : "b'0'";

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

        ok(['msg'=>'Estado actualizado','activo'=>$new]);
      }

      default:
        bad_request('Acción no reconocida');
    }
  } catch (Throwable $e) {
    bad_request('Error: ' . $e->getMessage());
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
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
          <div class="table-wrap">
            <table class="table table-striped table-hover align-middle text-center mb-0" id="table-<?= $key ?>">
              <thead class="table-dark">
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
  // Cargar la primera pestaña (o todas, como lo tenías)
  for (const t of Object.keys(TABLES)) { loadTable(t); }

  // Cambios de pestaña
  root.querySelectorAll('[data-tabla]').forEach(btn=>{
    btn.addEventListener('shown.bs.tab', ev=>{
      activeTab = ev.target.getAttribute('data-tabla');
      if (!cache[activeTab]) loadTable(activeTab);
    });
  });

  // Buscar
  root.querySelectorAll('input[data-role="search"]').forEach(i=>{
    i.addEventListener('input', debounce(() => {
      const tabla = i.getAttribute('data-tabla');
      loadTable(tabla, i.value.trim());
    }, 300));
  });

  // Submit modal
  root.querySelector('#formCat')?.addEventListener('submit', saveForm);
}

// Autoinit si entramos directo a catalogos_admin.php
if (document.readyState !== 'loading') {
  initCatalogosAdmin(document);
} else {
  document.addEventListener('DOMContentLoaded', () => initCatalogosAdmin(document));
}

// Deja accesible para que index.php pueda llamar tras inyectar
window.initCatalogosAdmin = initCatalogosAdmin;


    // Debounce
    function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms);} }

    // Cargar tabla
    async function loadTable(tabla, q='') {
      const resp = await fetch(`${ENDPOINT}?api=list&tabla=${encodeURIComponent(tabla)}&q=${encodeURIComponent(q)}`);
      const json = await resp.json();
      if(!json.ok){ alert(json.msg||'Error al cargar'); return; }

      cache[tabla] = { rows: json.rows, q };
      renderTable(tabla, 1);
    }

    function renderTable(tabla, page=1){
      const rows = (cache[tabla]?.rows)||[];
      const cols = TABLES[tabla].listCols;
      const tbody = document.querySelector(`#table-${tabla} tbody`);
      const pager = document.getElementById(`pager-${tabla}`);
      tbody.innerHTML = '';

      const start = (page-1)*pagerSize;
      const slice = rows.slice(start, start+pagerSize);

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
      const resp = await fetch(`${ENDPOINT}?api=save`, { method:'POST', body:fd });

      const json = await resp.json();
      if(!json.ok){ alert(json.msg||'Error al guardar'); return; }
      modal.hide();
      loadTable($('#f_tabla').value);
    }

    // Toggle activo
async function toggleActivo(tabla, id) {
  if (!confirm('¿Cambiar estado activo/inactivo?')) return;

  const url = `${ENDPOINT}?api=toggle&tabla=${encodeURIComponent(tabla)}`;


  const fd = new FormData();
  fd.append('tabla', tabla);
  fd.append('id', id);

  const resp = await fetch(url, {
    method: 'POST',
    body: fd,
    headers: { 'Accept': 'application/json' },
    cache: 'no-store'
  });

  let json;
  try { json = await resp.json(); }
  catch { alert(`Respuesta inválida (HTTP ${resp.status})`); return; }

  if (!json.ok) { alert(json.msg || 'Error al actualizar'); return; }

  loadTable(tabla, cache[tabla]?.q || '');
}



    // Utilidad: escapado simple
    function escapeHtml(s){ return String(s??'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  </script>
</body>
</html>
