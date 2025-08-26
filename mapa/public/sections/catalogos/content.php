<?php
// Ruta: /final/mapa/public/sections/catalogos/content.php
require_once dirname(__DIR__, 3) . '/server/config.php';

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
?>

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

<!-- Estilos -->
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
</style>

<!-- Scripts -->
<script>
// Config del lado del cliente (coincide con PHP)
const TABLES = <?= json_encode($TABLES, JSON_UNESCAPED_UNICODE) ?>;

// Estado UI
let activeTab = Object.keys(TABLES)[0];
const pagerSize = 10; // filas por página (cliente)
const cache = {};     // cache por tabla: { rows:[], q:'' }
const BASE = "/final/mapa/public/";
const ENDPOINT = BASE + "sections/catalogos_admin.php";

// Helpers
const $ = sel => document.querySelector(sel);
const $all = sel => document.querySelectorAll(sel);

function limpiarModal() {
  // Remover cualquier backdrop que haya quedado
  const backdrops = document.getElementsByClassName('modal-backdrop');
  Array.from(backdrops).forEach(el => el.remove());
  
  // Limpiar clases del body
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('overflow');
  document.body.style.removeProperty('padding-right');
}

let modal = null;

// Cargar al iniciar
function initCatalogos(root=document) {
  console.log('Iniciando módulo catálogos...');

  // Inicializar modal
  const modalEl = document.getElementById('modalForm');
  if (modalEl && window.bootstrap) {
    modal = new bootstrap.Modal(modalEl);
    
    // Limpiar cuando se cierra
    modalEl.addEventListener('hidden.bs.modal', limpiarModal);
  } else {
    console.error('Modal o Bootstrap no disponible');
  }

  // Cargar primera pestaña
  for (const t of Object.keys(TABLES)) { 
    loadTable(t); 
  }

  // Cambios de pestaña
  root.querySelectorAll('[data-tabla]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', ev => {
      activeTab = ev.target.getAttribute('data-tabla');
      if (!cache[activeTab]) loadTable(activeTab);
    });
  });

  // Buscar
  root.querySelectorAll('input[data-role="search"]').forEach(i => {
    i.addEventListener('input', debounce(() => {
      const tabla = i.getAttribute('data-tabla');
      loadTable(tabla, i.value.trim());
    }, 300));
  });

  // Submit modal
  root.querySelector('#formCat')?.addEventListener('submit', saveForm);
}

// Init cuando esté listo
if (document.readyState !== 'loading') {
  initCatalogos(document);
} else {
  document.addEventListener('DOMContentLoaded', () => initCatalogos(document));
}

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
        <button class="btn btn-outline-primary" title="Editar" data-item='${JSON.stringify(r).replace(/'/g, "&apos;")}' onclick="openEdit('${tabla}', ${r.ID})">
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
  if (!modal) {
    console.error('Modal no inicializado');
    return;
  }
  $('#modalTitle').textContent = `Nuevo — ${TABLES[tabla].label}`;
  $('#f_ID').value = '';
  $('#f_tabla').value = tabla;
  buildFormFields(tabla, null);
  modal.show();
  setTimeout(()=>{ const f = $('#formFields input, #formFields select, #formFields textarea'); if(f) f.focus(); }, 100);
}

// Abrir modal: editar
async function openEdit(tabla, id){
  if (!modal) {
    console.error('Modal no inicializado');
    return;
  }
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
  
  if (modal) {
    modal.hide();
    setTimeout(limpiarModal, 300);
  }
  
  loadTable($('#f_tabla').value);
}

// Toggle activo
async function toggleActivo(tabla, id) {
  if (!confirm('¿Cambiar estado activo/inactivo?')) return;

  const fd = new FormData();
  fd.append('tabla', tabla);
  fd.append('id', id);

  const resp = await fetch(`${ENDPOINT}?api=toggle`, {
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
