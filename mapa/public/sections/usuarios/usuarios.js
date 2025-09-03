// ---- Helper de notificaciones ----
function toast(msg, tipo='success') {
  if (window.Swal) {
    Swal.fire({ toast:true, position:'top-end', icon:tipo, title:msg,
      showConfirmButton:false, timer:3000 });
  } else { alert(msg); }
}

// ---- DIAGNÓSTICO Y HELPERS ----
console.log('[usuarios.js] cargado');

const $ = (sel, root=document) => root.querySelector(sel);
// Determina la ruta base de la API basándose en la ubicación del script
const scriptPath = document.currentScript?.src || '';
const apiBase = scriptPath.includes('/sections/usuarios/') 
  ? '/final/mapa/public/sections/usuarios/api/'  // Ruta absoluta 
  : 'api/';  // Ruta relativa si estamos en el directorio

function showDiag(msg, isError=true){
  let box = $('#diag');
  if (!box) {
    box = document.createElement('div');
    box.id = 'diag';
    box.className = 'alert mt-3';
    document.body.appendChild(box);
  }
  box.classList.remove('d-none','alert-info','alert-danger');
  box.classList.add(isError ? 'alert-danger' : 'alert-info');
  box.style.whiteSpace = 'pre-wrap';
  box.textContent = (box.textContent ? box.textContent + '\n' : '') + msg;
}

async function fetchJSON(url, body=null){
  const opt = body
    ? { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body }
    : {};
  let r;
  try { r = await fetch(url, opt); }
  catch(e){ showDiag(`FETCH ERROR: ${url}\n${e}`); return null; }

  if (!r.ok){
    const txt = await r.text().catch(()=> '');
    showDiag(`HTTP ${r.status}: ${url}\n${txt}`);
    return null;
  }
  const text = await r.text();
  try { return JSON.parse(text); }
  catch(e){ showDiag(`[No-JSON] ${url}\n${text}`); return null; }
}

const toArray = (v) => Array.isArray(v) ? v : (v && Array.isArray(v.data) ? v.data : []);

// Objeto global para almacenar datos para los selects
const globales = {
  usuarios: [],
  modulos: [],
  modulosMapa: [], // Módulos específicos para permisos de mapa
  entidades: [],
  buses: []
};

// Función helper para llenar selects
function fillSelect(selectElement, data, includeEmpty = true) {
  if (!selectElement) return;
  
  let optionsHtml = '';
  if (includeEmpty) {
    optionsHtml += '<option value="">Seleccionar...</option>';
  }
  
  data.forEach(item => {
    let value = item.ID || item.id;
    let text = item.cuenta || item.descripcion || item.nombre || `${item.nombre} ${item.apaterno || ''}`.trim() || item.text || item.value;
    optionsHtml += `<option value="${value}">${text}</option>`;
  });
  
  selectElement.innerHTML = optionsHtml;
}

// ---------- Catálogos ----------
function llenarSelect(sel, data, valKey, textKey, incluirTodos=false, textoTodos='Todos'){
  if (!sel) return;
  if (!Array.isArray(data)) data = [];
  sel.innerHTML = '';
  if (incluirTodos) {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = textoTodos;
    sel.appendChild(opt);
  }
  data.forEach(it => {
    const opt = document.createElement('option');
    opt.value = it[valKey];
    opt.textContent = it[textKey];
    sel.appendChild(opt);
  });
}

async function cargarCatalogos(){
  const depData = await fetchJSON(apiBase+'entidades_listar.php?catalogo=dependencias') || [];
  llenarSelect($('#personaDep'), depData, 'ID', 'descripcion');

  const entData = await fetchJSON(apiBase+'entidades_listar.php') || [];
  llenarSelect($('#personaEnt'), entData, 'ID', 'descripcion');
  llenarSelect($('#permEntidad'), entData, 'ID', 'descripcion', true, 'Todas');

  const busData = await fetchJSON(apiBase+'permisos_listar.php?catalogo=bus') || [];
  llenarSelect($('#permBus'), busData, 'ID', 'descripcion', true, 'Todos');

  // Cargar módulos filtrados solo para permisos
  const modData = await fetchJSON(apiBase+'permisos_listar.php?catalogo=modulo') || [];
  llenarSelect($('#permModulo'), modData, 'ID', 'descripcion');
  
  // Actualizar objeto globales para los lotes
  globales.modulos = modData;

  const userData = await fetchJSON(apiBase+'usuarios_listar.php?catalogo=usuarios') || [];
  llenarSelect($('#permUsuario'), userData, 'ID', 'cuenta');
  
  // Actualizar objeto globales para los lotes
  globales.usuarios = userData;

  const personaData = await fetchJSON(apiBase+'usuarios_listar.php?catalogo=personas') || [];
  llenarSelect($('#usuarioPersona'), personaData, 'ID', 'nombre_completo');

  // Para filtros
  llenarSelect($('#filtroUsuarioPerm'), userData, 'ID', 'cuenta', true);
  llenarSelect($('#filtroModuloPerm'), modData, 'ID', 'descripcion', true);
  llenarSelect($('#filtroEntidadPerm'), entData, 'ID', 'descripcion', true);
  llenarSelect($('#filtroBusPerm'), busData, 'ID', 'descripcion', true);
}

// ---------- Personas ----------
async function cargarPersonas(){
  const q = encodeURIComponent($('#buscarPersona')?.value || '');
  const data = await fetchJSON(apiBase+'personas_listar.php?buscar='+q) || [];
  const tb = $('#tbPersonas'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(p => {
    tb.innerHTML += `
      <tr>
        <td>${p.ID}</td>
        <td class="text-start">${p.nombre || ''} ${p.apaterno || ''} ${p.amaterno || ''}</td>
        <td>${p.numero_empleado || ''}</td>
        <td>${p.correo || ''}</td>
        <td class="text-start">${p.dependencia || ''}</td>
        <td class="text-start">${p.entidad || ''}</td>
        <td>${p.activo=='1'?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" 
            data-persona='${JSON.stringify(p).replace(/'/g, "&apos;")}' 
            onclick="abrirModalPersona(JSON.parse(this.dataset.persona))">Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick='togglePersona(${p.ID})'>${p.activo=='1'?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

window.abrirModalPersona = function(p=null){
  console.log('Abriendo modal persona:', p);
  $('#tituloPersona').textContent = p ? 'Editar persona' : 'Nueva persona';
  $('#personaID').value       = p?.ID || '';
  $('#personaNombre').value   = p?.nombre || '';
  $('#personaApaterno').value = p?.apaterno || '';
  $('#personaAmaterno').value = p?.amaterno || '';
  $('#personaNumero').value   = p?.numero_empleado || '';
  $('#personaCorreo').value   = p?.correo || '';
  $('#personaDep').value      = p?.Fk_dependencia || '';
  $('#personaEnt').value      = p?.Fk_entidad || '';
  $('#personaActivo').value   = p?.activo ?? '1';
  if (!p) new bootstrap.Modal($('#modalPersona')).show();
};

async function guardarPersona(form){
  const formData = new FormData(form);
  const r = await fetch(apiBase+'persona_guardar.php', { method:'POST', body:formData });
  if (!r.ok) { toast('Error al guardar persona', 'error'); return; }
  toast('Persona guardada correctamente');
  bootstrap.Modal.getInstance($('#modalPersona')).hide();
  await cargarPersonas();
}

async function togglePersona(id){
  const r = await fetch(apiBase+'persona_toggle.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`ID=${id}`
  });
  if (!r.ok) { toast('Error al cambiar estado', 'error'); return; }
  toast('Estado cambiado');
  await cargarPersonas();
}

// Eventos personas
$('#buscarPersona')?.addEventListener('input', ()=>{ clearTimeout(window._deb1); window._deb1=setTimeout(cargarPersonas,250); });
$('#formPersona')?.addEventListener('submit', e => { e.preventDefault(); guardarPersona(e.target); });

// ---------- Usuarios ----------
async function cargarUsuarios(){
  const q = encodeURIComponent($('#buscarUsuario')?.value || '');
  const data = await fetchJSON(apiBase+'usuarios_listar.php?buscar='+q) || [];
  
  // Actualizar el objeto global para los selects
  globales.usuarios = data;
  
  const tb = $('#tbUsuarios'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(u => {
    const activo = u.activo == '1' ? 'Sí' : 'No';
    const activoClass = u.activo == '1' ? 'text-success' : 'text-muted';
    const btnToggleText = u.activo == '1' ? 'Desactivar' : 'Activar';
    const btnToggleClass = u.activo == '1' ? 'btn-outline-secondary' : 'btn-outline-success';
    const btnToggleIcon = u.activo == '1' ? 'eye-slash' : 'eye';
    
    tb.innerHTML += `
      <tr>
        <td>${u.ID}</td>
        <td class="text-start">${u.cuenta}</td>
        <td>${u.nivel}</td>
        <td class="text-start">${u.persona||''}</td>
        <td class="${activoClass}">${activo}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary me-1" 
            data-usuario='${JSON.stringify(u).replace(/'/g, "&apos;")}' 
            onclick="abrirModalUsuario(JSON.parse(this.dataset.usuario))" 
            title="Editar">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-warning me-1" 
            onclick="resetPass(${u.ID})" 
            title="Reset contraseña">
            <i class="fas fa-key"></i>
          </button>
          <button class="btn btn-sm ${btnToggleClass}" 
            onclick="toggleUsuario(${u.ID})" 
            title="${btnToggleText}">
            <i class="fas fa-${btnToggleIcon}"></i>
          </button>
        </td>
      </tr>`;
  });
}

window.abrirModalUsuario = function(u=null){
  console.log('Abriendo modal usuario:', u);
  $('#tituloUsuario').textContent = u ? 'Editar usuario' : 'Nuevo usuario';
  $('#usuarioID').value       = u?.ID || '';
  $('#usuarioPersona').value  = u?.Fk_persona || '';
  $('#usuarioCuenta').value   = u?.cuenta || '';
  $('#usuarioNivel').value    = u?.nivel || '';
  $('#usuarioPass').value     = '';
  $('#usuarioActivo').value   = u?.activo ?? '1';
  if (!u) new bootstrap.Modal($('#modalUsuario')).show();
};

async function guardarUsuario(form){
  const formData = new FormData(form);
  const r = await fetch(apiBase+'usuario_guardar.php', { method:'POST', body:formData });
  if (!r.ok) { toast('Error al guardar usuario', 'error'); return; }
  toast('Usuario guardado correctamente');
  bootstrap.Modal.getInstance($('#modalUsuario')).hide();
  await cargarUsuarios();
}

async function resetPass(id){
  if (!confirm('¿Resetear contraseña del usuario?')) return;
  const r = await fetch(apiBase+'usuario_reset.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`ID=${id}`
  });
  if (!r.ok) { toast('Error al resetear contraseña', 'error'); return; }
  toast('Contraseña reseteada');
}

async function toggleUsuario(id){
  if (!confirm('¿Cambiar el estado del usuario?')) return;
  const r = await fetch(apiBase+'usuario_toggle.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`ID=${id}`
  });
  
  if (!r.ok) { 
    toast('Error al cambiar estado', 'error'); 
    return; 
  }
  
  const result = await r.json();
  if (!result.ok) {
    toast(result.msg || 'Error al cambiar estado', 'error');
    return;
  }
  
  toast('Estado cambiado');
  await cargarUsuarios();
}

// Eventos usuarios
$('#buscarUsuario')?.addEventListener('input', ()=>{ clearTimeout(window._deb2); window._deb2=setTimeout(cargarUsuarios,250); });
$('#formUsuario')?.addEventListener('submit', e => { e.preventDefault(); guardarUsuario(e.target); });

// ---------- Permisos ----------
async function cargarPermisos(){
  const params = new URLSearchParams();
  ['filtroUsuarioPerm', 'filtroModuloPerm', 'filtroEntidadPerm', 'filtroBusPerm'].forEach(id => {
    const val = $(`#${id}`)?.value;
    if (val) params.append(id.replace('filtro','').replace('Perm','').toLowerCase(), val);
  });
  
  const data = await fetchJSON(apiBase+'permisos_listar.php?'+params) || [];
  const tb = $('#tbPermisos'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(p => {
    // Convertir acción READ a Leer para mostrar
    const accionMostrar = (p.accion === 'READ' || p.accion === 'READ') ? 'Leer' : (p.accion || '');
    
    tb.innerHTML += `
      <tr>
        <td>${p.ID}</td>
        <td class="text-start">${p.usuario}</td>
        <td class="text-start">${p.modulo}</td>
        <td class="text-start">${p.entidad||'Todas'}</td>
        <td class="text-start">${p.bus||'Todos'}</td>
        <td>${accionMostrar}</td>
        <td>${p.activo=='1'?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" 
            data-permiso='${JSON.stringify(p).replace(/'/g, "&apos;")}' 
            onclick="abrirModalPermiso(JSON.parse(this.dataset.permiso))">Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick='togglePermiso(${p.ID})'>${p.activo=='1'?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

window.abrirModalPermiso = function(p=null){
  console.log('Abriendo modal permiso:', p);
  $('#tituloPermiso').textContent = p ? 'Editar permiso' : 'Nuevo permiso';
  $('#permisoID').value    = p?.ID || '';
  $('#permUsuario').value  = p?.Fk_usuario || '';
  $('#permModulo').value   = p?.Fk_modulo || '';
  $('#permEntidad').value  = p?.FK_entidad || '';
  $('#permBus').value      = p?.FK_bus || '';
  $('#permAccion').value   = p?.accion || 'READ';
  $('#permActivo').value   = p?.activo ?? '1';
  new bootstrap.Modal($('#modalPermiso')).show();
};

async function guardarPermiso(form){
  const formData = new FormData(form);
  const r = await fetch(apiBase+'permiso_guardar.php', { method:'POST', body:formData });
  if (!r.ok) { toast('Error al guardar permiso', 'error'); return; }
  toast('Permiso guardado correctamente');
  bootstrap.Modal.getInstance($('#modalPermiso')).hide();
  await cargarPermisos();
}

async function togglePermiso(id){
  const r = await fetch(apiBase+'permiso_toggle.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`ID=${id}`
  });
  if (!r.ok) { toast('Error al cambiar estado', 'error'); return; }
  toast('Estado cambiado');
  await cargarPermisos();
}

// Eventos permisos
['filtroUsuarioPerm', 'filtroModuloPerm', 'filtroEntidadPerm', 'filtroBusPerm'].forEach(id => {
  $(`#${id}`)?.addEventListener('change', cargarPermisos);
});
$('#formPermiso')?.addEventListener('submit', e => { e.preventDefault(); guardarPermiso(e.target); });

// ---------- Módulos ----------
async function cargarModulos(){
  const q = encodeURIComponent($('#buscarModulo')?.value || '');
  let data = await fetchJSON(apiBase+'modulos_listar.php?buscar='+q);
  if (!Array.isArray(data)) data = [];
  
  // Actualizar el objeto global para los selects
  globales.modulos = data;
  
  const tb = $('#tbModulos'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(m=>{
    tb.innerHTML += `
      <tr>
        <td>${m.ID}</td>
        <td class="text-start">${m.descripcion}</td>
        <td>${(m.activo==1||m.activo=='1')?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" 
            data-modulo='${JSON.stringify(m).replace(/'/g, "&apos;")}' 
            onclick="abrirModalModulo(JSON.parse(this.dataset.modulo))">Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick='toggleModulo(${m.ID})'>${(m.activo==1||m.activo=='1')?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

window.abrirModalModulo = function(m=null){
  console.log('Abriendo modal módulo:', m);
  $('#tituloModulo').textContent = m ? 'Editar módulo' : 'Nuevo módulo';
  $('#moduloID').value = m?.ID || '';
  $('#moduloDesc').value = m?.descripcion || '';
  $('#moduloActivo').value = m?.activo ?? '1';
  if (!m) new bootstrap.Modal($('#modalModulo')).show();
};

async function guardarModulo(form){
  const formData = new FormData(form);
  const r = await fetch(apiBase+'modulo_guardar.php', { method:'POST', body:formData });
  if (!r.ok) { toast('Error al guardar módulo', 'error'); return; }
  toast('Módulo guardado correctamente');
  bootstrap.Modal.getInstance($('#modalModulo')).hide();
  await cargarModulos();
}

async function toggleModulo(id){
  const r = await fetch(apiBase+'modulo_toggle.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`ID=${id}`
  });
  if (!r.ok) { toast('Error al cambiar estado', 'error'); return; }
  toast('Estado cambiado');
  await cargarModulos();
}

// Eventos módulos
$('#buscarModulo')?.addEventListener('input', ()=>{ clearTimeout(window._deb4); window._deb4=setTimeout(cargarModulos,250); });
$('#formModulo')?.addEventListener('submit', e => { e.preventDefault(); guardarModulo(e.target); });

// ---------- Modal helpers ----------
function initModal(id) {
  const modal = $(`#${id}`);
  if (!modal) return null;
  return new bootstrap.Modal(modal);
}

// ---------- SISTEMA DE LOTES DE PERMISOS ----------

// Estado global para lotes
const estadoLotes = {
  grupos: [],
  entidades: [],
  buses: [],
  editando: null,
  filtros: {
    usuario: '',
    modulo: '',
    entidad: '',
    bus: ''
  }
};

// Cargar lotes de permisos
async function cargarLotes() {
  try {
    showLoading('#tbLotes', 'Cargando grupos de permisos...');
    
    const params = new URLSearchParams();
    Object.entries(estadoLotes.filtros).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });
    
    const response = await fetch(`${apiBase}permisos_grupos.php?${params}`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const grupos = await response.json();
    estadoLotes.grupos = grupos;
    renderLotes();
    
  } catch (error) {
    console.error('Error cargando lotes:', error);
    toast('Error al cargar los grupos de permisos', 'error');
    $('#tbLotes').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar datos</td></tr>';
  }
}

// Renderizar tabla de lotes
function renderLotes() {
  const tbody = $('#tbLotes');
  const busqueda = $('#buscarLote')?.value?.toLowerCase() || '';
  
  let grupos = estadoLotes.grupos;
  
  // Filtrar por búsqueda
  if (busqueda) {
    grupos = grupos.filter(g => 
      g.usuario?.toLowerCase().includes(busqueda) ||
      g.modulo?.toLowerCase().includes(busqueda) ||
      g.entidad?.toLowerCase().includes(busqueda) ||
      g.bus?.toLowerCase().includes(busqueda)
    );
  }
  
  if (grupos.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay grupos de permisos</td></tr>';
    $('#estadoVacioLotes').style.display = 'block';
    return;
  }
  
  $('#estadoVacioLotes').style.display = 'none';
  
  tbody.innerHTML = grupos.map(grupo => {
    const combos = grupo.combos || [];
    const token = grupo.group_token || 'individual';
    
    // Convertir acción para mostrar
    const accionMostrar = (grupo.accion === 'read') ? 'Leer' : (grupo.accion || 'General');
    
    return `
      <tr data-token="${token}">
        <td><strong>${grupo.usuario}</strong></td>
        <td><code>${grupo.modulo}</code></td>
        <td><span class="badge bg-primary">${accionMostrar}</span></td>
        <td>${renderEstadoCombinado(combos)}</td>
        <td class="col-sm-hide">${renderCombinaciones(combos)}</td>
        <td class="col-sm-hide"><small class="text-muted">${token.substring(0,8)}...</small></td>
        <td class="actions">
          <div class="btn-group" role="group">
            <button class="btn btn-sm btn-outline-primary" onclick="editarLote('${token}')" title="Editar">
              <i class="fas fa-edit"></i> <span class="text">Editar</span>
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="duplicarLote('${token}')" title="Duplicar">
              <i class="fas fa-copy"></i> <span class="text">Duplicar</span>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// Renderizar estado combinado de las combinaciones
function renderEstadoCombinado(combos) {
  if (!combos || combos.length === 0) return '<span class="text-muted">Sin datos</span>';
  
  const activos = combos.filter(c => c.activo == 1).length;
  const total = combos.length;
  
  if (activos === 0) {
    return '<span class="badge bg-danger">Inactivo</span>';
  } else if (activos === total) {
    return '<span class="badge bg-success">Activo</span>';
  } else {
    return `<span class="badge bg-warning">Parcial (${activos}/${total})</span>`;
  }
}

// Renderizar combinaciones como badges
function renderCombinaciones(combos) {
  if (!combos || combos.length === 0) return '<span class="text-muted">-</span>';
  
  return combos.map(c => {
    const entNombre = c.entidad_nombre || 'Todas';
    const busNombre = c.bus_nombre || 'Todos';
    const estadoClass = c.activo == 1 ? 'success' : 'secondary';
    const estadoIcon = c.activo == 1 ? '✓' : '×';
    
    return `<span class="badge bg-${estadoClass} combo-badge me-1" 
                  title="Estado: ${c.activo == 1 ? 'Activo' : 'Inactivo'}">
              ${estadoIcon} ${entNombre}/${busNombre}
            </span>`;
  }).join('');
}

// Abrir modal para nuevo lote
window.abrirModalLote = async function() {
  console.log('Abriendo modal de lote...');
  
  estadoLotes.editando = null;
  $('#tituloLote').textContent = '✨ Nuevo lote de permisos';
  $('#loteAction').value = 'crear';
  $('#btnEliminarLote').style.display = 'none';
  
  // Limpiar formulario
  $('#formLote').reset();
  $('#loteTokenDisplay').value = '(se generará automáticamente)';
  
  // Verificar si los datos están cargados
  if (globales.usuarios.length === 0 || globales.modulosMapa.length === 0) {
    console.log('Datos no cargados, cargando...');
    await cargarCatalogosLotes();
    await poblarFiltrosLotes();
  } else {
    console.log('Datos ya cargados, usando caché');
    // Solo llenar los selects del modal
    fillSelect($('#loteUsuario'), globales.usuarios, false);
    fillSelect($('#loteModulo'), globales.modulosMapa, false);
  }
  
  console.log('Usuarios disponibles:', globales.usuarios.length);
  console.log('Módulos de mapa disponibles:', globales.modulosMapa.length);
  
  // Mostrar modal
  new bootstrap.Modal($('#modalLote')).show();
};

// Cargar entidades y buses específicos cuando se carga la página
async function cargarCatalogosLotes() {
  try {
    // Cargar entidades
    const entidadesResponse = await fetch(`${apiBase}entidades_listar.php`);
    estadoLotes.entidades = await entidadesResponse.json().catch(() => [
      {ID: 1, descripcion: 'CDMX'},
      {ID: 2, descripcion: 'Jalisco'},
      {ID: 3, descripcion: 'Puebla'},
      {ID: 4, descripcion: 'Yucatán'}
    ]);
    
    // Cargar buses
    const busesResponse = await fetch(`${apiBase}permisos_listar.php?catalogo=bus`);
    estadoLotes.buses = await busesResponse.json();
    
    // Cargar módulos específicos para mapas
    const modulosMapaResponse = await fetch(`${apiBase}permisos_listar.php?catalogo=modulo_mapa`);
    globales.modulosMapa = await modulosMapaResponse.json();
    
    // Actualizar objeto globales para los selects
    globales.entidades = estadoLotes.entidades;
    globales.buses = estadoLotes.buses;
    
    // Poblar checkboxes de entidades
    $('#entidadesEspecificas').innerHTML = estadoLotes.entidades.map(e => `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${e.ID}" id="entidad${e.ID}">
        <label class="form-check-label" for="entidad${e.ID}">${e.descripcion}</label>
      </div>
    `).join('');
    
    // Poblar checkboxes de buses
    $('#busesEspecificos').innerHTML = estadoLotes.buses.map(b => `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${b.ID}" id="bus${b.ID}">
        <label class="form-check-label" for="bus${b.ID}">${b.descripcion}</label>
      </div>
    `).join('');
    
    // Event listeners para "ALL" checkboxes
    $('#entidadAll')?.addEventListener('change', (e) => {
      if (e.target.checked) {
        document.querySelectorAll('#entidadesEspecificas input').forEach(input => input.checked = false);
      }
      construirMatriz();
    });
    
    $('#busAll')?.addEventListener('change', (e) => {
      if (e.target.checked) {
        document.querySelectorAll('#busesEspecificos input').forEach(input => input.checked = false);
      }
      construirMatriz();
    });
    
    // Event listeners para checkboxes específicos
    document.querySelectorAll('#entidadesEspecificas input, #busesEspecificos input').forEach(input => {
      input.addEventListener('change', () => {
        // Si se marca uno específico, desmarcar el "ALL"
        const isEntidad = input.closest('#entidadesEspecificas');
        if (isEntidad) {
          $('#entidadAll').checked = false;
        } else {
          $('#busAll').checked = false;
        }
        construirMatriz();
      });
    });
    
  } catch (error) {
    console.error('Error cargando catálogos de lotes:', error);
  }
}

// Construir matriz de combinaciones
function construirMatriz() {
  const entidadesSeleccionadas = Array.from(document.querySelectorAll('#entidadesEspecificas input:checked')).map(i => i.value);
  const busesSeleccionados = Array.from(document.querySelectorAll('#busesEspecificos input:checked')).map(i => i.value);
  
  const matrizContainer = $('#matrizContainer');
  const matrizBody = $('#matrizCombinaciones');
  
  if (!matrizContainer || !matrizBody) return;
  
  // Mostrar u ocultar matriz según selecciones
  if (entidadesSeleccionadas.length > 0 && busesSeleccionados.length > 0) {
    matrizContainer.style.display = 'block';
    
    // Generar combinaciones
    let combinaciones = '';
    entidadesSeleccionadas.forEach(entidadId => {
      busesSeleccionados.forEach(busId => {
        const entidadNombre = estadoLotes.entidades.find(e => e.ID == entidadId)?.descripcion || entidadId;
        const busNombre = estadoLotes.buses.find(b => b.ID == busId)?.descripcion || busId;
        
        combinaciones += `
          <tr>
            <td>${entidadNombre}</td>
            <td>${busNombre}</td>
            <td class="text-center">
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" id="combo_${entidadId}_${busId}" 
                       data-entidad="${entidadId}" data-bus="${busId}" checked>
              </div>
            </td>
          </tr>
        `;
      });
    });
    
    matrizBody.innerHTML = combinaciones;
  } else {
    matrizContainer.style.display = 'none';
  }
}

// Editar lote existente
window.editarLote = async function(identifier) {
  try {
    const grupo = estadoLotes.grupos.find(g => 
      g.group_token === identifier || g.ID == identifier
    );
    
    if (!grupo) {
      toast('Grupo no encontrado', 'error');
      return;
    }

    // Cargar catálogos primero
    await cargarCatalogosLotes();

    estadoLotes.editando = identifier;
    $('#tituloLote').textContent = grupo.group_token ? '✏️ Editar lote' : '✏️ Editar permiso individual';
    $('#loteAction').value = 'editar';
    $('#btnEliminarLote').style.display = 'block';

    // Llenar formulario con datos del grupo
    $('#loteUsuario').value = grupo.Fk_usuario;
    $('#loteModulo').value = grupo.Fk_modulo;
    $('#loteAccion').value = grupo.accion || '';
    $('#loteActivo').value = grupo.activo;
    $('#loteToken').value = grupo.group_token;
    $('#loteTokenDisplay').value = grupo.group_token || 'Sin token';

    // Cargar combinaciones existentes
    if (grupo.combos && grupo.combos.length > 0) {
      // Determinar si son "ALL" o específicos
      const tieneEntidadAll = grupo.combos.some(c => c.FK_entidad === null);
      const tieneBusAll = grupo.combos.some(c => c.FK_bus === null);
      
      if (tieneEntidadAll || tieneBusAll) {
        $('#entidadAll').checked = tieneEntidadAll;
        $('#busAll').checked = tieneBusAll;
        
        // Ocultar secciones específicas si están en ALL
        if (tieneEntidadAll) {
          $('#entidadesEspecificas').style.display = 'none';
        } else {
          $('#entidadesEspecificas').style.display = 'block';
        }
        
        if (tieneBusAll) {
          $('#busesEspecificos').style.display = 'none';
        } else {
          $('#busesEspecificos').style.display = 'block';
        }
      } else {
        // Marcar entidades y buses específicos
        $('#entidadAll').checked = false;
        $('#busAll').checked = false;
        $('#entidadesEspecificas').style.display = 'block';
        $('#busesEspecificos').style.display = 'block';
        
        // Limpiar selecciones previas
        document.querySelectorAll('#entidadesEspecificas input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('#busesEspecificos input[type="checkbox"]').forEach(cb => cb.checked = false);
        
        grupo.combos.forEach(combo => {
          if (combo.FK_entidad) {
            const entidadCheck = $(`#entidad${combo.FK_entidad}`);
            if (entidadCheck) entidadCheck.checked = true;
          }
          if (combo.FK_bus) {
            const busCheck = $(`#bus${combo.FK_bus}`);
            if (busCheck) busCheck.checked = true;
          }
        });
        
        construirMatriz();
        
        // Aplicar estados específicos en la matriz
        setTimeout(() => {
          grupo.combos.forEach(combo => {
            const entidadId = combo.FK_entidad || 'ALL';
            const busId = combo.FK_bus || 'ALL';
            const matrizCheck = $(`#combo_${entidadId}_${busId}`);
            if (matrizCheck) matrizCheck.checked = combo.activo == 1;
          });
        }, 100);
      }
    }

    new bootstrap.Modal($('#modalLote')).show();
    
  } catch (error) {
    console.error('Error editando lote:', error);
    toast('Error al cargar los datos del lote', 'error');
  }
};

// Duplicar lote
window.duplicarLote = async function(identifier) {
  try {
    const grupo = estadoLotes.grupos.find(g => 
      g.group_token === identifier || g.ID == identifier
    );
    
    if (!grupo) {
      toast('Grupo no encontrado', 'error');
      return;
    }

    // Abrir modal como nuevo
    await abrirModalLote();
    
    // Llenar con los datos del grupo a duplicar (excepto el token)
    $('#loteUsuario').value = grupo.Fk_usuario;
    $('#loteModulo').value = grupo.Fk_modulo;
    $('#loteAccion').value = grupo.accion || '';
    $('#loteActivo').value = grupo.activo;
    
    toast('Datos copiados. Modifica lo necesario y guarda.', 'info');
    
  } catch (error) {
    console.error('Error duplicando lote:', error);
    toast('Error al duplicar el lote', 'error');
  }
};

// Eliminar lote
window.eliminarLote = async function() {
  if (!confirm('¿Estás seguro de eliminar este lote de permisos? Esta acción no se puede deshacer.')) {
    return;
  }
  
  try {
    const token = $('#loteToken').value;
    
    const response = await fetch(`${apiBase}permiso_lote.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=eliminar&group_token=${encodeURIComponent(token)}`
    });
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const result = await response.json();
    
    if (result.success) {
      toast('Lote eliminado correctamente', 'success');
      bootstrap.Modal.getInstance($('#modalLote')).hide();
      await cargarLotes();
    } else {
      toast(result.error || 'Error al eliminar el lote', 'error');
    }
    
  } catch (error) {
    console.error('Error eliminando lote:', error);
    toast('Error al eliminar el lote', 'error');
  }
};

// Toggle individual de permisos en combinaciones
window.togglePermisoIndividual = async function(groupToken, entidadId, busId) {
  try {
    const response = await fetch(`${apiBase}permiso_toggle_individual.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `group_token=${encodeURIComponent(groupToken)}&entidad=${entidadId}&bus=${busId}`
    });
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const result = await response.json();
    
    if (result.success) {
      toast(`Estado cambiado a: ${result.nuevo_estado == 1 ? 'Activo' : 'Inactivo'}`, 'success');
      await cargarLotes();
    } else {
      toast(result.error || 'Error al cambiar el estado', 'error');
    }
    
  } catch (error) {
    console.error('Error en toggle individual:', error);
    toast('Error al cambiar el estado del permiso', 'error');
  }
};

// Manejar envío del formulario de lotes
$('#formLote')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  try {
    const formData = new FormData();
    formData.append('action', $('#loteAction').value);
    formData.append('Fk_usuario', $('#loteUsuario').value);
    formData.append('Fk_modulo', $('#loteModulo').value);
    formData.append('accion', $('#loteAccion').value);
    formData.append('activo', $('#loteActivo').value);
    
    if ($('#loteAction').value === 'editar') {
      formData.append('group_token', $('#loteToken').value);
    }
    
    // Determinar entidades y buses seleccionados
    const entidadAll = $('#entidadAll')?.checked;
    const busAll = $('#busAll')?.checked;
    
    if (entidadAll) {
      formData.append('entidades[]', 'ALL');
    } else {
      document.querySelectorAll('#entidadesEspecificas input:checked').forEach(input => {
        formData.append('entidades[]', input.value);
      });
    }
    
    if (busAll) {
      formData.append('buses[]', 'ALL');
    } else {
      document.querySelectorAll('#busesEspecificos input:checked').forEach(input => {
        formData.append('buses[]', input.value);
      });
    }
    
    // Si hay matriz visible, incluir estados específicos
    if ($('#matrizContainer').style.display !== 'none') {
      document.querySelectorAll('#matrizCombinaciones input').forEach(input => {
        const entidad = input.dataset.entidad;
        const bus = input.dataset.bus;
        const activo = input.checked ? '1' : '0';
        formData.append(`matriz[${entidad}][${bus}]`, activo);
      });
    }
    
    const response = await fetch(`${apiBase}permiso_lote.php`, {
      method: 'POST',
      body: formData
    });
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const result = await response.json();
    
    if (result.ok) {
      toast('Lote guardado correctamente', 'success');
      bootstrap.Modal.getInstance($('#modalLote')).hide();
      await cargarLotes();
    } else {
      toast(result.msg || 'Error al guardar el lote', 'error');
    }
    
  } catch (error) {
    console.error('Error guardando lote:', error);
    toast('Error al guardar el lote', 'error');
  }
});

// Event listeners para filtros de lotes
['filtroUsuarioLote', 'filtroModuloLote', 'filtroEntidadLote', 'filtroBusLote'].forEach(id => {
  $(`#${id}`)?.addEventListener('change', (e) => {
    const key = id.replace('filtro', '').replace('Lote', '').toLowerCase();
    estadoLotes.filtros[key] = e.target.value;
    cargarLotes();
  });
});

// Event listener para búsqueda de lotes
$('#buscarLote')?.addEventListener('input', () => {
  clearTimeout(window._debLotes);
  window._debLotes = setTimeout(renderLotes, 250);
});

// Poblar los selects de filtros para lotes
async function poblarFiltrosLotes() {
  try {
    // Poblar filtro de usuarios
    fillSelect($('#filtroUsuarioLote'), globales.usuarios, true);
    
    // Poblar filtro de módulos  
    fillSelect($('#filtroModuloLote'), globales.modulosMapa, true);
    
    // Poblar filtro de entidades
    const filtroEntidad = $('#filtroEntidadLote');
    if (filtroEntidad) {
      let optionsHtml = '<option value="">Todas las entidades</option><option value="ALL">Solo "Todas"</option>';
      estadoLotes.entidades.forEach(e => {
        optionsHtml += `<option value="${e.ID}">${e.descripcion}</option>`;
      });
      filtroEntidad.innerHTML = optionsHtml;
    }
    
    // Poblar filtro de buses
    const filtroBus = $('#filtroBusLote');
    if (filtroBus) {
      let optionsHtml = '<option value="">Todos los buses</option><option value="ALL">Solo "Todos"</option>';
      estadoLotes.buses.forEach(b => {
        optionsHtml += `<option value="${b.ID}">${b.descripcion}</option>`;
      });
      filtroBus.innerHTML = optionsHtml;
    }
    
    // Poblar selects del modal
    fillSelect($('#loteUsuario'), globales.usuarios, false);
    fillSelect($('#loteModulo'), globales.modulosMapa, false);
    
  } catch (error) {
    console.error('Error poblando filtros de lotes:', error);
  }
}

function showLoading(selector, message) {
  const element = $(selector);
  if (element) {
    element.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      ${message}
    </td></tr>`;
  }
}

// ---------- Inicialización ----------
window.initUsuarios = async function(){
  console.log('Iniciando módulo usuarios...');
  
  // Verificar Bootstrap
  if (!window.bootstrap) {
    console.error('Bootstrap no está disponible');
    showDiag('Error: Bootstrap no está disponible. Recargue la página.', true);
    return;
  }

  // Inicializar modales
  ['modalPersona', 'modalUsuario', 'modalPermiso', 'modalModulo'].forEach(id => {
    const modal = initModal(id);
    if (!modal) console.error(`Modal ${id} no encontrado`);
  });

  const ping = await fetchJSON(apiBase + '_ping.php');
  if (!ping || ping.ok === false){
    showDiag(`PING FALLÓ o inválido:\n${JSON.stringify(ping,null,2)}`);
    return;
  }
  showDiag(`Ping OK: usuarios=${ping.usuarios}, personas=${ping.personas}`, false);

  await cargarCatalogos();
  await Promise.all([cargarPersonas(), cargarUsuarios(), cargarPermisos(), cargarModulos(), cargarLotes()]);
  await cargarCatalogosLotes(); // Asegurar que los catálogos de lotes están cargados
  await poblarFiltrosLotes(); // Poblar filtros y selects de lotes
};

console.log('[usuarios.js] Archivo cargado completamente');
