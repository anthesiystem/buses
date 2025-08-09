console.log("mapa.js cargado");

// Reintento global (máx 1)
//let reintentosMapa = 0;
//const MAX_REINTENTOS_MAPA = 1;

window.reintentosMapa ??= 0;            // solo se asigna si no existe
window.MAX_REINTENTOS_MAPA ??= 1;

function iniciarMapa() {
  const script = document.getElementById("mapaScript");
  if (!script) return console.error("No se encontró el script con id=mapaScript");

  // ⚙️ Datos del script
  const busSeleccionado   = script.dataset.bus;
  const colorConcluido    = script.dataset.colorConcluido;
  const colorSinEjecutar  = script.dataset.colorSinEjecutar;
  const colorOtro         = script.dataset.colorOtro;
  const busID             = script.dataset.busId;
  const endpointDatos     = script.dataset.urlDatos   || '../server/mapabus/datos.php';
  const endpointDetalle   = script.dataset.urlDetalle || '../server/mapabus/busvista.php';

  console.log("📍 Bus:", busSeleccionado, "ID:", busID);
  console.log("🔗 Endpoints:", { endpointDatos, endpointDetalle });

  // ⚠️ Crear/obtener tooltip en un scope visible para los handlers
  let tooltip = document.getElementById("tooltipMapa");
  if (!tooltip) {
    tooltip = document.createElement("div");
    tooltip.id = "tooltipMapa";
    tooltip.style.position = "absolute";
    tooltip.style.padding = "4px 8px";
    tooltip.style.background = "rgba(0,0,0,0.75)";
    tooltip.style.color = "#fff";
    tooltip.style.fontSize = "12px";
    tooltip.style.borderRadius = "4px";
    tooltip.style.pointerEvents = "none";
    tooltip.style.display = "none";
    document.body.appendChild(tooltip);
  }

  const estadoMap = {
    'MX-AGU': 'AGUASCALIENTES',
    'MX-BCN': 'BAJA CALIFORNIA',
    'MX-BCS': 'BAJA CALIFORNIA SUR',
    'MX-CAM': 'CAMPECHE',
    'MX-CHP': 'CHIAPAS',
    'MX-CHH': 'CHIHUAHUA',
    'MX-CMX': 'CIUDAD DE MEXICO',
    'MX-COA': 'COAHUILA',
    'MX-COL': 'COLIMA',
    'MX-DUR': 'DURANGO',
    'MX-GUA': 'GUANAJUATO',
    'MX-GRO': 'GUERRERO',
    'MX-HID': 'HIDALGO',
    'MX-JAL': 'JALISCO',
    'MX-MEX': 'ESTADO DE MEXICO',
    'MX-MIC': 'MICHOACAN',
    'MX-MOR': 'MORELOS',
    'MX-NAY': 'NAYARIT',
    'MX-NLE': 'NUEVO LEON',
    'MX-OAX': 'OAXACA',
    'MX-PUE': 'PUEBLA',
    'MX-QUE': 'QUERETARO',
    'MX-ROO': 'QUINTANA ROO',
    'MX-SLP': 'SAN LUIS POTOSI',
    'MX-SIN': 'SINALOA',
    'MX-SON': 'SONORA',
    'MX-TAB': 'TABASCO',
    'MX-TAM': 'TAMAULIPAS',
    'MX-TLA': 'TLAXCALA',
    'MX-VER': 'VERACRUZ',
    'MX-YUC': 'YUCATAN',
    'MX-ZAC': 'ZACATECAS'
  };

  // 🎨 Pintar leyenda
  (function pintarLeyenda() {
    const rectConcluido   = document.getElementById("legendConcluido");
    const rectPruebas     = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    if (rectConcluido)   rectConcluido.setAttribute("fill", colorConcluido);
    if (rectPruebas)     rectPruebas.setAttribute("fill", colorOtro);
    if (rectSinEjecutar) rectSinEjecutar.setAttribute("fill", colorSinEjecutar);

    console.log("🎨 Leyenda pintada desde mapa.js");
  })();

  // 🛡️ JSON seguro (muestra HTML si el endpoint falla)
  const fetchJsonSafe = (url) =>
    fetch(url, { cache: 'no-store' }).then(async (res) => {
      const txt = await res.text();
      try { return JSON.parse(txt); }
      catch (e) { console.error('❌ Respuesta NO-JSON de', url, '\n', txt); throw e; }
    });

  // 📥 Cargar datos y pintar
  fetchJsonSafe(`${endpointDatos}?bus_id=${encodeURIComponent(busID)}`)
    .then(data => {
      console.log("📦 Datos recibidos:", data);

      document.querySelectorAll('path[id^="MX-"]').forEach(path => {
        const clave = path.id;
        const nombreEstado = estadoMap[clave];

        if (!nombreEstado) return;

        // Coincide también si la clave viene en mayúsculas
        const estadoData = data[nombreEstado] || data[nombreEstado.toUpperCase()];
        if (!estadoData) return;

        const estatus = (estadoData.estatus || "").trim().toUpperCase();

        if (estatus === "IMPLEMENTADO") {
          path.style.fill = colorConcluido;
        } else if (estatus === "SIN IMPLEMENTAR") {
          path.style.fill = colorSinEjecutar;
        } else {
          path.style.fill = colorOtro; // PRUEBAS o MIXTO
        }

        // Evitar duplicar listeners en reintentos
        if (!path.dataset.listenersBound) {
          path.addEventListener('mouseenter', () => {
            tooltip.textContent = nombreEstado;
            tooltip.style.display = 'block';
          });

          path.addEventListener('mousemove', (e) => {
            tooltip.style.left = (e.pageX + 10) + 'px';
            tooltip.style.top  = (e.pageY + 10) + 'px';
          });

          path.addEventListener('mouseleave', () => {
            tooltip.style.display = 'none';
          });

          path.addEventListener('click', () => {
            const detalle = document.getElementById('detalle');
            if (detalle) {
              detalle.innerHTML = `
                <div class="d-flex flex-column align-items-center my-3">
                  <div class="spinner-border" role="status"></div>
                  <div class="mt-2">Cargando detalle...</div>
                </div>`;
            }
            fetch(`${endpointDetalle}?estado=${encodeURIComponent(nombreEstado)}&bus_id=${encodeURIComponent(busID)}`)
              .then(response => response.text())
              .then(html => { if (detalle) detalle.innerHTML = html; })
              .catch(() => { if (detalle) detalle.innerHTML = '<div class="alert alert-danger">Error al cargar detalle</div>'; });
          });

          path.dataset.listenersBound = "1";
        }
      });
    })
    .catch(err => {
      console.error('Error cargando datos del mapa:', err);
    })
    .finally(() => {
      // Oculta loader si existe
      const loader = document.getElementById("loaderMapa");
      if (loader) loader.style.display = "none";

      // Reintento controlado (backoff corto)
      setTimeout(() => {
        const estadosPintados = Array.from(document.querySelectorAll('path[id^="MX-"]')).filter(p => p.style.fill);
        if (estadosPintados.length === 0 && reintentosMapa < MAX_REINTENTOS_MAPA) {
          reintentosMapa++;
          console.warn("🎨 Reintentando pintar mapa por precaución... Intento:", reintentosMapa);
          iniciarMapa();
        } else if (estadosPintados.length > 0) {
          console.log("✅ Mapa pintado correctamente con", estadosPintados.length, "estados.");
        } else {
          console.error("⚠️ No se pudo pintar el mapa tras reintento.");
        }
      }, 800);
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


// ⏱️ Esperar que SVG + leyenda + script estén disponibles en DOM
//const esperarMapa = setInterval(() => {
  //const pathListo    = document.querySelector('path[id^="MX-"]');
  //const leyendaListo = document.getElementById("legendConcluido");
  //const scriptListo  = document.getElementById("mapaScript");

  //if (pathListo && leyendaListo && scriptListo) {
    //clearInterval(esperarMapa);
    //iniciarMapa();
   //}
//}, 100);
