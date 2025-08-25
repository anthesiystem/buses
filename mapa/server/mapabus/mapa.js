console.log("mapa.js cargado");

// Reintentos (suaves)
window.reintentosMapa ??= 0;
window.MAX_REINTENTOS_MAPA ??= 1;

function iniciarMapa() {
  const s = document.getElementById("mapaScript");
  if (!s) return console.error("No se encontró el script con id=mapaScript");

  // Endpoints desde data-attrs (con fallback compatibles)
  const busID            = +(s.dataset.busId || s.dataset.bus_id || 0);
  const endpointConteos  = s.dataset.urlConteos   || s.dataset.urlDatos   || "/final/mapa/server/mapabus/datos.php";
  const endpointDetalle  = s.dataset.urlDetalle   || "/final/mapa/server/mapabus/detalle.php";
  const endpointEnts     = s.dataset.urlEntidades || "/final/mapa/public/sections/mapabus/entidades_permitidas.php";

  const colorConcluido   = s.dataset.colorConcluido;
  const colorSinEjecutar = s.dataset.colorSinEjecutar;
  const colorOtro        = s.dataset.colorOtro;

  console.log("🔗 Endpoints:", { endpointConteos, endpointDetalle, endpointEnts, busID });

  // Tooltip
  let tooltip = document.getElementById("tooltipMapa");
  if (!tooltip) {
    tooltip = document.createElement("div");
    tooltip.id = "tooltipMapa";
    Object.assign(tooltip.style, {
      position: "absolute", padding: "4px 8px", background: "rgba(0,0,0,.75)",
      color: "#fff", fontSize: "12px", borderRadius: "6px", pointerEvents: "none",
      display: "none", zIndex: 1000
    });
    document.body.appendChild(tooltip);
  }

  // Mapas Estado<->Nombre/ID
  const CODE_TO_NAME = {
    'MX-AGU':'AGUASCALIENTES','MX-BCN':'BAJA CALIFORNIA','MX-BCS':'BAJA CALIFORNIA SUR','MX-CAM':'CAMPECHE',
    'MX-CHP':'CHIAPAS','MX-CHH':'CHIHUAHUA','MX-CMX':'CIUDAD DE MEXICO','MX-COA':'COAHUILA','MX-COL':'COLIMA',
    'MX-DUR':'DURANGO','MX-GUA':'GUANAJUATO','MX-GRO':'GUERRERO','MX-HID':'HIDALGO','MX-JAL':'JALISCO',
    'MX-MEX':'ESTADO DE MEXICO','MX-MIC':'MICHOACAN','MX-MOR':'MORELOS','MX-NAY':'NAYARIT','MX-NLE':'NUEVO LEON',
    'MX-OAX':'OAXACA','MX-PUE':'PUEBLA','MX-QUE':'QUERETARO','MX-ROO':'QUINTANA ROO','MX-SLP':'SAN LUIS POTOSI',
    'MX-SIN':'SINALOA','MX-SON':'SONORA','MX-TAB':'TABASCO','MX-TAM':'TAMAULIPAS','MX-TLA':'TLAXCALA',
    'MX-VER':'VERACRUZ','MX-YUC':'YUCATAN','MX-ZAC':'ZACATECAS'
  };
  const CODE_TO_ID = {
    'MX-AGU':1,'MX-BCN':2,'MX-BCS':3,'MX-CAM':4,'MX-CHP':5,'MX-CHH':6,'MX-CMX':7,'MX-COA':8,'MX-COL':9,'MX-DUR':10,
    'MX-GUA':11,'MX-GRO':12,'MX-HID':13,'MX-JAL':14,'MX-MEX':15,'MX-MIC':16,'MX-MOR':17,'MX-NAY':18,'MX-NLE':19,
    'MX-OAX':20,'MX-PUE':21,'MX-QUE':22,'MX-ROO':23,'MX-SLP':24,'MX-SIN':25,'MX-SON':26,'MX-TAB':27,'MX-TAM':28,
    'MX-TLA':29,'MX-VER':30,'MX-YUC':31,'MX-ZAC':32
  };

  // Pintar leyenda
  (function pintarLeyenda() {
    const a = document.getElementById("legendConcluido");
    const b = document.getElementById("legendPruebas");
    const c = document.getElementById("legendSinEjecutar");
    if (a) a.setAttribute("fill", colorConcluido);
    if (b) b.setAttribute("fill", colorOtro);
    if (c) c.setAttribute("fill", colorSinEjecutar);
  })();

  const fetchJsonSafe = async (url) => {
    const r = await fetch(url, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
    const t = await r.text();
    try { return JSON.parse(t); }
    catch (e){ console.error('❌ Respuesta NO-JSON de', url, '\n', t); throw e; }
  };

async function obtenerPermitidas() {
  // 1) inyectadas desde PHP (si existen)
  const local = (window.MAPA_BUS && Array.isArray(window.MAPA_BUS.permitidas))
    ? window.MAPA_BUS.permitidas : [];
  if (local.length) return new Set(local.map(Number));

  // 2) intenta con bus_id y luego bus
  const tryParse = (d) => {
    if (Array.isArray(d)) return new Set(d.map(Number));
    if (d && Array.isArray(d.permitidas)) return new Set(d.permitidas.map(Number));
    return null;
  };

  const urls = [
    `${endpointEnts}?bus_id=${encodeURIComponent(busID)}`,
    `${endpointEnts}?bus=${encodeURIComponent(busID)}`,
  ];

  for (const u of urls) {
    try {
      const d = await fetchJsonSafe(u);
      const s = tryParse(d);
      if (s) {
        console.log('🔐 Permisos desde', u, '=>', [...s]);
        return s;
      }
    } catch (e) {
      console.warn('No se pudieron obtener permisos desde', u, e);
    }
  }
  console.warn('🔐 [ACL] No llegaron permisos; todo bloqueado');
  return new Set();
}


  const cargarConteos = () => fetchJsonSafe(`${endpointConteos}?bus_id=${encodeURIComponent(busID)}`);

  Promise.all([obtenerPermitidas(), cargarConteos()])
    .then(([permitidas, data]) => {
      if (!permitidas.size) console.warn('🔐 [ACL] Sin entidades permitidas; todo bloqueado.');

      let pintados = 0;

      document.querySelectorAll('path[id^="MX-"]').forEach(path => {
        const code = path.id;
        const nombreEstado = CODE_TO_NAME[code];
        if (!nombreEstado) return;

        const entId  = Number(path.dataset.entidadId || CODE_TO_ID[code] || 0);
        const allowed = entId > 0 && permitidas.has(entId);

        // Estatus desde conteos
        const estadoData = data[nombreEstado] || data[nombreEstado?.toUpperCase()];
        const estatus = ((estadoData && estadoData.estatus) || "").trim().toUpperCase();

        // Color segun estatus si allowed; gris si no
        if (allowed) {
          if (estatus === "IMPLEMENTADO")           path.style.fill = colorConcluido;
          else if (estatus === "SIN IMPLEMENTAR")   path.style.fill = colorSinEjecutar;
          else                                      path.style.fill = colorOtro;
          pintados++;
        } else {
          path.style.fill = "#CCCCCC";
        }
// antes: if (allowed) { ... pinta color ... } else { gris }
const hasData = !!estadoData;
const canClick = allowed && hasData;

// Pintado
if (canClick) {
  if (estatus === "IMPLEMENTADO")         path.style.fill = colorConcluido;
  else if (estatus === "SIN IMPLEMENTAR") path.style.fill = colorSinEjecutar;
  else                                    path.style.fill = colorOtro;
} else {
  path.style.fill = "#CCCCCC";
}

// Tooltip
if (!path.dataset.listenersBound) {
  path.addEventListener('mouseenter', () => {
    let extra = '';
    if (!allowed)   extra = ' (sin permiso)';
    else if (!hasData) extra = ' (sin registros)';
    tooltip.textContent = nombreEstado + extra;
    tooltip.style.display = 'block';
  });
  path.addEventListener('mousemove', (e) => {
    tooltip.style.left = (e.pageX + 10) + 'px';
    tooltip.style.top  = (e.pageY + 10) + 'px';
  });
  path.addEventListener('mouseleave', () => { tooltip.style.display = 'none'; });
  path.dataset.listenersBound = "1";
}

// 🔒 Click: quita el anterior (si existía) y sólo añade si canClick
if (path._clickHandler) {
  path.removeEventListener('click', path._clickHandler);
  path._clickHandler = null;
}

if (canClick) {
  path._clickHandler = async () => {
    const detalle = document.getElementById('detalle');
    if (!detalle) return;
    detalle.innerHTML = `
      <div class="d-flex flex-column align-items-center my-3">
        <div class="spinner-border" role="status"></div>
        <div class="mt-2">Cargando detalle...</div>
      </div>`;

    try {
      const url = `${endpointDetalle}?bus=${encodeURIComponent(busID)}&bus_id=${encodeURIComponent(busID)}&entidad=${encodeURIComponent(entId)}&estado=${encodeURIComponent(nombreEstado)}&_=${Date.now()}`;
      const res = await fetch(url, { cache: 'no-store', headers: { 'Accept': 'application/json, text/html' }});
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (ct.includes('application/json')) {
        const data = await res.json();
        const rows = Array.isArray(data) ? data : (data.rows || []);
        detalle.innerHTML = renderDetalleHTML(rows, nombreEstado);
      } else {
        detalle.innerHTML = await res.text();
      }
    } catch (err) {
      console.error('Error al cargar detalle:', err);
      detalle.innerHTML = `
        <div class="alert alert-danger">
          Error al cargar detalle. Por favor, intente nuevamente.
          <button class="btn btn-sm btn-outline-danger ms-2" onclick="iniciarMapa()">Reintentar</button>
        </div>`;
    }
  };
  path.addEventListener('click', path._clickHandler);
  path.style.cursor = 'pointer';
} else {
  // Sin click permitido
  path.style.cursor = 'not-allowed'; // o 'default' si prefieres
}

      });

      console.log(`✅ Pintado con permisos: ${pintados} estados habilitados.`);
    })
    .catch(err => {
      console.error('Error cargando datos/permisos del mapa:', err);
    })
    .finally(() => {
      const loader = document.getElementById("loaderMapa");
      if (loader) loader.style.display = "none";

      setTimeout(() => {
        const estados = Array.from(document.querySelectorAll('path[id^="MX-"]'));
        const pintados = estados.filter(p => p.style.fill && p.style.fill !== "").length;

        if (pintados === 0 && reintentosMapa < MAX_REINTENTOS_MAPA) {
          reintentosMapa++;
          console.warn("🎨 Reintentando pintar mapa por precaución... Intento:", reintentosMapa);
          iniciarMapa();
        } else if (pintados > 0) {
          console.log(`🟢 Pintura OK (${pintados}/${estados.length} estados pintados)`);
        } else {
          console.error("⚠️ No se pudo pintar el mapa tras reintento.");
        }
      }, 700);
    });
}

if (window.__esperarMapaTimer) clearInterval(window.__esperarMapaTimer);
window.__esperarMapaTimer = setInterval(() => {
  const pathListo    = document.querySelector('path[id^="MX-"]');
  const leyendaListo = document.getElementById("legendConcluido");
  const scriptListo  = document.getElementById("mapaScript");
  if (pathListo && leyendaListo && scriptListo) {
    clearInterval(window.__esperarMapaTimer);
    window.__esperarMapaTimer = null;
    iniciarMapa();
  }
}, 100);

// Helpers de render
function h(s){ return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

function renderDetalleHTML(rows, titulo) {
  if (!Array.isArray(rows) || rows.length === 0) {
    return `
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Detalle — ${h(titulo)}</div>
        <div class="card-body"><span class="text-muted">No hay registros para esta entidad.</span></div>
      </div>`;
  }

  const body = rows.map(r => `
    <tr>
      <td>${h(r.ID)}</td>
      <td>${h(r.dependencia)}</td>
      <td>${h(r.engine)}</td>
      <td>${h(r.tecnologia || r.version || '')}</td>
      <td>${h(r.estatus)}</td>
      <td>${h(r.fecha_inicio)}</td>
      <td>${h(r.fecha_migracion)}</td>
    </tr>
  `).join('');

  return `
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Detalle — ${h(titulo)}</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Dependencia</th>
                <th>Motor</th>
                <th>Tecnología</th>
                <th>Estatus</th>
                <th>F. Inicio</th>
                <th>F. Migración</th>
              </tr>
            </thead>
            <tbody>${body}</tbody>
          </table>
        </div>
      </div>
    </div>`;
}
