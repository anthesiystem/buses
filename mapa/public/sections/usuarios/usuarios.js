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
const apiBase = '/final/mapa/public/sections/usuarios/api/';

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

async function cargarCatalogos() {
  try {
    const [deps, ents, personas, usuarios, modulos, buses] = await Promise.all([
      fetchJSON(apiBase+'personas_listar.php?catalogo=dependencia'),
      fetchJSON(apiBase+'personas_listar.php?catalogo=entidad'),
      fetchJSON(apiBase+'usuarios_listar.php?catalogo=personas'),
      fetchJSON(apiBase+'usuarios_listar.php?catalogo=usuarios'),
      fetchJSON(apiBase+'permisos_listar.php?catalogo=modulo'),
      fetchJSON(apiBase+'permisos_listar.php?catalogo=bus'),
    ]);

    // Persona modal
    llenarSelect($('#personaDep'), deps, 'ID','descripcion');
    llenarSelect($('#personaEnt'), ents, 'ID','descripcion');
    // Usuario modal
    llenarSelect($('#usuarioPersona'), personas, 'ID','nombre_completo');

    // Permisos filtro + modal
    llenarSelect($('#filtroUsuarioPerm'), usuarios, 'ID','cuenta', true, 'Todos');
    llenarSelect($('#permUsuario'), usuarios, 'ID','cuenta');

    llenarSelect($('#filtroModuloPerm'), modulos, 'ID','descripcion', true, 'Todos');
    llenarSelect($('#permModulo'), modulos, 'ID','descripcion');

    llenarSelect($('#filtroEntidadPerm'), ents, 'ID','descripcion', true, 'Todas');
    llenarSelect($('#permEntidad'), ents, 'ID','descripcion', true, 'Todas'); // incluye "Todos" arriba

    llenarSelect($('#filtroBusPerm'), buses, 'ID','descripcion', true, 'Todos');
    llenarSelect($('#permBus'), buses, 'ID','descripcion', true, 'Todos');     // incluye "Todos" arriba

    // Acción (si tu select no trae catálogo, asegúrate de tener opciones fijas en el HTML)
    // Aquí solo garantizamos que exista la opción "Todos"
    ensureTodosOption($('#permAccion'), 'Todos');

  } catch(e){
    console.error(e);
    toast('No se pudieron cargar catálogos');
  }
}

// ---------- Personas ----------
async function cargarPersonas(){
  const q = encodeURIComponent($('#buscarPersona')?.value || '');
  let data = await fetchJSON(apiBase+'personas_listar.php?buscar='+q);
  if (!Array.isArray(data)) data = [];
  const tb = $('#tbPersonas'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(p => {
    tb.innerHTML += `
      <tr>
        <td>${p.ID}</td>
        <td class="text-start">${p.nombre_completo}</td>
        <td>${p.numero_empleado||''}</td>
        <td>${p.correo||''}</td>
        <td>${p.dependencia||''}</td>
        <td>${p.entidad||''}</td>
        <td>${p.activo=='1'?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick='abrirModalPersona(${JSON.stringify(p)})'>Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick="togglePersona(${p.ID})">${p.activo=='1'?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

window.abrirModalPersona = function(p=null){
  $('#tituloPersona').textContent = p ? 'Editar persona' : 'Nueva persona';
  $('#personaID').value       = p?.ID || '';
  $('#personaNombre').value   = p?.nombre || '';
  $('#personaApaterno').value = p?.apaterno || '';
  $('#personaAmaterno').value = p?.amaterno || '';
  $('#personaNumero').value   = p?.numero_empleado || '';
  $('#personaCorreo').value   = p?.correo || '';
  $('#personaDep').value      = p?.Fk_dependencia || '';
  $('#personaEnt').value      = p?.Fk_entidad || '';
  $('#personaActivo').value   = p?.activo || '1';
};

$('#formPersona')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const body = new URLSearchParams(new FormData(e.target)).toString();
  const r = await fetchJSON(apiBase+'persona_guardar.php', body);
  if (r && r.ok){
    toast('Persona guardada','success');
    bootstrap.Modal.getInstance(document.getElementById('modalPersona')).hide();
    await cargarPersonas();
    await cargarCatalogos();
  } else { toast((r && r.msg) ? r.msg : 'No se pudo guardar','error'); }
});

window.togglePersona = async function(id){
  const r = await fetchJSON(apiBase+'persona_toggle.php','ID='+id);
  if (r && r.ok){ toast('Actualizado','success'); cargarPersonas(); }
  else { toast((r && r.msg) ? r.msg : 'No se pudo actualizar','error'); }
};

// ---------- Usuarios ----------
async function cargarUsuarios(){
  const q = encodeURIComponent($('#buscarUsuario')?.value || '');
  const data = await fetchJSON(apiBase+'usuarios_listar.php?buscar='+q) || [];
  const tb = $('#tbUsuarios'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(u => {
    tb.innerHTML += `
      <tr>
        <td>${u.ID}</td>
        <td class="text-start">${u.cuenta}</td>
        <td>${u.nivel}</td>
        <td class="text-start">${u.persona||''}</td>
        <td>${u.activo=='1'?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick='abrirModalUsuario(${JSON.stringify(u)})'>Editar</button>
          <button class="btn btn-sm btn-outline-warning" onclick="resetPass(${u.ID})">Reset pass</button>
        </td>
      </tr>`;
  });
}

window.abrirModalUsuario = function(u=null){
  $('#tituloUsuario').textContent = u ? 'Editar usuario' : 'Nuevo usuario';
  $('#usuarioID').value       = u?.ID || '';
  $('#usuarioPersona').value  = u?.Fk_persona || '';
  $('#usuarioCuenta').value   = u?.cuenta || '';
  $('#usuarioNivel').value    = u?.nivel || '0';
  $('#usuarioActivo').value   = u?.activo || '1';
  $('#usuarioPass').value     = '';
};

$('#formUsuario')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const body = new URLSearchParams(new FormData(e.target)).toString();
  const r = await fetchJSON(apiBase+'usuario_guardar.php', body);
  if (r && r.ok){
    toast('Usuario guardado');
    bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
    await cargarUsuarios();
    await cargarCatalogos();
  } else toast((r && r.msg) || 'No se pudo guardar');
});

window.resetPass = async function(id){
  if (!confirm('¿Resetear contraseña a "admin"?')) return;
  const r = await fetchJSON(apiBase+'usuario_reset.php','ID='+id);
  toast(r?.ok ? 'Contraseña reseteada' : (r?.msg||'No se pudo resetear'));
};

// ---------- Permisos ----------
function ensureTodosOption(sel, label='Todos'){
  if (!sel) return;
  const has = Array.from(sel.options).some(o => o.value === '');
  if (!has){
    const opt = document.createElement('option');
    opt.value = ''; opt.textContent = label;
    sel.insertBefore(opt, sel.firstChild);
  }
}
function setSelectTodosAware(sel, raw){
  if (!sel) return;
  const isTodos = raw === null || raw === undefined || raw === '' || raw === '0' || raw === 0 || raw === '*';
  sel.value = isTodos ? '' : String(raw);
  if (sel.value !== String(raw) && raw !== null && raw !== undefined && raw !== ''){
    const opt = document.createElement('option');
    opt.value = String(raw);
    opt.text = String(raw);
    sel.appendChild(opt);
    sel.value = String(raw);
  }
}

async function cargarPermisos(){
  const params = new URLSearchParams({
    usuario: $('#filtroUsuarioPerm')?.value || '',
    modulo:  $('#filtroModuloPerm')?.value  || '',
    entidad: $('#filtroEntidadPerm')?.value || '',
    bus:     $('#filtroBusPerm')?.value     || ''
  }).toString();

  const data = await fetchJSON(apiBase+'permisos_listar.php?'+params) || [];
  const tb = $('#tbPermisos'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(p => {
    tb.innerHTML += `
      <tr>
        <td>${p.ID}</td>
        <td>${p.usuario}</td>
        <td>${p.modulo}</td>
        <td>${p.entidad ?? 'Todos'}</td>
        <td>${p.bus ?? 'Todos'}</td>
        <td>${p.accion ?? 'Todos'}</td>
        <td>${p.activo=='1'?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick='abrirModalPermiso(${JSON.stringify(p)})'>Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick="togglePermiso(${p.ID})">${p.activo=='1'?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

// Modal permiso (NULL ↔ "Todos")
window.abrirModalPermiso = function(p=null){
  $('#tituloPermiso').textContent = p ? 'Editar permiso' : 'Nuevo permiso';
  $('#permisoID').value   = p?.ID || '';
  $('#permUsuario').value = p?.Fk_usuario || '';
  $('#permModulo').value  = p?.Fk_modulo  || '';
  setSelectTodosAware($('#permEntidad'), p?.FK_entidad ?? null);
  setSelectTodosAware($('#permBus'),     p?.FK_bus     ?? null);
  setSelectTodosAware($('#permAccion'),  p?.accion     ?? null);
  $('#permActivo').value  = p?.activo ?? '1';
};

// Guardar permiso: '' => NULL (omitimos clave)
$('#formPermiso')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = new URLSearchParams();
  for (const [k, v] of fd.entries()){
    if ((k === 'FK_entidad' || k === 'FK_bus' || k === 'accion') && v === '') continue;
    payload.append(k, v);
  }
  const r = await fetchJSON(apiBase+'permiso_guardar.php', payload.toString());
  if (r?.ok){
    toast('Permiso guardado');
    bootstrap.Modal.getInstance(document.getElementById('modalPermiso')).hide();
    await cargarPermisos();
  } else { toast(r?.msg || 'No se pudo guardar'); }
});

window.togglePermiso = async function(id){
  const r = await fetchJSON(apiBase+'permiso_toggle.php','ID='+id);
  if (r?.ok){ toast('Actualizado'); cargarPermisos(); }
  else { toast(r?.msg||'No se pudo actualizar'); }
};

// Filtros
['filtroUsuarioPerm','filtroModuloPerm','filtroEntidadPerm','filtroBusPerm'].forEach(id=>{
  const el = document.getElementById(id);
  el && el.addEventListener('change', cargarPermisos);
});

// Buscadores
$('#buscarPersona')?.addEventListener('input', ()=>{ clearTimeout(window._deb1); window._deb1=setTimeout(cargarPersonas,250); });
$('#buscarUsuario')?.addEventListener('input', ()=>{ clearTimeout(window._deb2); window._deb2=setTimeout(cargarUsuarios,250); });
$('#buscarModulo') ?.addEventListener('input', ()=>{ clearTimeout(window._debM); window._debM=setTimeout(cargarModulos,250); });

// --- MÓDULOS (listado CRUD simple) ---
async function cargarModulos(){
  const q = encodeURIComponent($('#buscarModulo')?.value || '');
  let data = await fetchJSON(apiBase+'modulos_listar.php?buscar='+q);
  if (!Array.isArray(data)) data = [];
  const tb = $('#tbModulos'); if (!tb) return;
  tb.innerHTML = '';
  data.forEach(m=>{
    tb.innerHTML += `
      <tr>
        <td>${m.ID}</td>
        <td class="text-start">${m.descripcion}</td>
        <td>${(m.activo==1||m.activo=='1')?'Sí':'No'}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick='abrirModalModulo(${JSON.stringify(m)})'>Editar</button>
          <button class="btn btn-sm btn-outline-secondary" onclick='toggleModulo(${m.ID})'>${(m.activo==1||m.activo=='1')?'Desactivar':'Activar'}</button>
        </td>
      </tr>`;
  });
}

window.abrirModalModulo = m=>{
  $('#tituloModulo').textContent = m ? 'Editar módulo' : 'Nuevo módulo';
  $('#moduloID').value     = m?.ID || '';
  $('#moduloDesc').value   = m?.descripcion || '';
  $('#moduloActivo').value = (m?.activo==1||m?.activo=='1') ? '1' : '0';
};

$('#formModulo')?.addEventListener('submit', async e=>{
  e.preventDefault();
  const body = new URLSearchParams(new FormData(e.target)).toString();
  const r = await fetchJSON(apiBase+'modulo_guardar.php', body);
  if (r?.ok){
    toast('Módulo guardado','success');
    bootstrap.Modal.getInstance(document.getElementById('modalModulo')).hide();
    await cargarModulos();
    await cargarCatalogos();
  } else { toast(r?.msg||'No se pudo guardar','error'); }
});

window.toggleModulo = async id=>{
  const r = await fetchJSON(apiBase+'modulo_toggle.php','ID='+id);
  if (r?.ok){ toast('Actualizado','success'); cargarModulos(); cargarCatalogos(); }
  else { toast(r?.msg||'No se pudo actualizar','error'); }
};

// ---- INIT ----
window.initUsuarios = async function(){
  const ping = await fetchJSON(apiBase + '_ping.php');
  if (!ping || ping.ok === false){
    showDiag(`PING FALLÓ o inválido:\n${JSON.stringify(ping,null,2)}`);
    return;
  }
  showDiag(`Ping OK: usuarios=${ping.usuarios}, personas=${ping.personas}`, false);

  await cargarCatalogos();
  await Promise.all([cargarPersonas(), cargarUsuarios(), cargarPermisos(), cargarModulos()]);
};

// Inyectar opción "Todos" en selects del modal (en caso de que el HTML venga estático)
(function initTodosOnLoad(){
  ensureTodosOption($('#permEntidad'),'Todos');
  ensureTodosOption($('#permBus'),'Todos');
  ensureTodosOption($('#permAccion'),'Todos');
})();
