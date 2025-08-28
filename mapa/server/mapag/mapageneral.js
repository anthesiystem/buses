// /final/mapa/server/mapag/mapageneral.js
console.log("mapageneral.js (general) cargado v2");

// Reintentos controlados
window.reintentosMapa ??= 0;
window.MAX_REINTENTOS_MAPA ??= 2;

(function () {
  function iniciarMapa() {
    const s = document.getElementById("mapaScript");
    if (!s) return console.error("No se encontró #mapaScript");

    const colorConcluido   = s.dataset.colorConcluido   || "#95e039";
    const colorSinEjecutar = s.dataset.colorSinEjecutar || "gray";
    const colorOtro        = s.dataset.colorOtro        || "#de4f33";
    const colorBloqueado   = "#B0B0B0";  // Color para estados sin permiso
    const endpointDatos    = s.dataset.urlDatos   || "/final/mapa/server/mapag/generalindex.php";
    const endpointDetalle  = s.dataset.urlDetalle || "/final/mapa/server/mapag/detalle.php";

    // Obtener permisos del objeto global
    const permisosGenerales = window.__ACL_GENERAL__ || { entidades: [], buses: [] };

    // Tooltip único
    let tooltip = document.getElementById("tooltipMapa");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.id = "tooltipMapa";
      Object.assign(tooltip.style, {
        position: "absolute", padding: "4px 8px", background: "rgba(0,0,0,0.75)",
        color: "#fff", fontSize: "12px", borderRadius: "4px", pointerEvents: "none",
        display: "none", zIndex: 1000,
      });
      document.body.appendChild(tooltip);
    }

    const normalize = (t) =>
      (t || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .toUpperCase().replace(/\s+/g, " ").trim();

    const fetchJsonSafe = (url) =>
      fetch(url, { cache: "no-store", headers: { "Accept":"application/json" } })
        .then(async r => {
          const txt = await r.text();
          try { return JSON.parse(txt); }
          catch (e) { console.error("❌ Respuesta NO‑JSON de", url, "\n", txt); throw e; }
        });

    // 0) Esperar a que estadoMap exista
    const esperarEstadoMap = new Promise((resolve) => {
      if (window.estadoMap && Object.keys(window.estadoMap).length) return resolve();
      const iv = setInterval(() => {
        if (window.estadoMap && Object.keys(window.estadoMap).length) {
          clearInterval(iv); resolve();
        }
      }, 60);
      setTimeout(() => resolve(), 3000); // red de seguridad
    });

    // 1) Obtener permisos y datos
    Promise.all([fetchJsonSafe(endpointDatos), esperarEstadoMap]).then(([raw]) => {
      // Obtener permisos del objeto global
      const permisos = window.__ACL_GENERAL__ || { entidades: [], buses: [] };
      console.log('Permisos cargados:', permisos);
      console.log('Datos crudos del servidor:', raw);
      
      const data = {};
      Object.keys(raw || {}).forEach(k => {
        const estadoData = raw[k];
        const estadoNormalizado = normalize(k);
        console.log(`Procesando estado ${k}:`, estadoData);
        
        // Solo incluir estados que el usuario tenga permiso de ver
        if (typeof estadoData === 'object' && estadoData.entidad) {
          // Si el estado pertenece a una entidad permitida
          if (permisos.entidades.includes(estadoData.entidad)) {
            data[estadoNormalizado] = estadoData;
          }
        } else {
          // Compatibilidad con formato anterior
          data[estadoNormalizado] = estadoData;
        }
      });

    // 2) Esperar a que el SVG y las 3 leyendas existan
      const wait = setInterval(() => {
        const paths = document.querySelectorAll('path[id^="MX-"]');
        const a = document.getElementById("legendConcluido");
        const b = document.getElementById("legendPruebas");
        const c = document.getElementById("legendSinEjecutar");
        if (!paths.length || !a || !b || !c) return; // aún no está todo
        clearInterval(wait);

        // 🎨 INYECTAR FILTROS SVG ELEGANTES (como en demo.html)
        const svg = document.querySelector('#mapa svg');
        if (svg && !svg.querySelector('#dropShadowBlue')) {
          let defs = svg.querySelector('defs');
          if (!defs) {
            defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            svg.insertBefore(defs, svg.firstChild);
          }
          
          // Filtro de sombra azul elegante
          const dropShadowBlue = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
          dropShadowBlue.setAttribute('id', 'dropShadowBlue');
          dropShadowBlue.setAttribute('x', '-40%');
          dropShadowBlue.setAttribute('y', '-40%');
          dropShadowBlue.setAttribute('width', '180%');
          dropShadowBlue.setAttribute('height', '180%');
          dropShadowBlue.innerHTML = `
            <feDropShadow dx="0" dy="2.5" stdDeviation="3.2" flood-color="#4192ff" flood-opacity="0.65"/>
          `;
          
          // Filtro de glow azul intenso
          const glowBlue = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
          glowBlue.setAttribute('id', 'glowBlue');
          glowBlue.setAttribute('x', '-80%');
          glowBlue.setAttribute('y', '-80%');
          glowBlue.setAttribute('width', '260%');
          glowBlue.setAttribute('height', '260%');
          glowBlue.innerHTML = `
            <feGaussianBlur in="SourceGraphic" stdDeviation="2.2" result="blur"/>
            <feMerge>
              <feMergeNode in="blur"/>
              <feMergeNode in="blur"/>
              <feMergeNode in="SourceGraphic"/>
            </feMerge>
          `;
          
          defs.appendChild(dropShadowBlue);
          defs.appendChild(glowBlue);
          
          console.log('✨ Filtros SVG elegantes inyectados');
        }

        // 🔹 PINTAR LEYENDAS (aquí ya existen)
        a.setAttribute("fill", colorConcluido);
        b.setAttribute("fill", colorOtro);
        c.setAttribute("fill", colorSinEjecutar);        // 3) Pintar estados
        const estMap = window.estadoMap || {};
        let pintados = 0;

        // Debug de permisos disponibles
        console.log('🔐 Permisos disponibles:', {
          entidades: permisosGenerales.entidades,
          buses: permisosGenerales.buses
        });

        paths.forEach(path => {
          const nombre = estMap[path.id];
          if (!nombre) return;

          // Verificar permisos primero
          const entidadId = parseInt(path.getAttribute('data-entidad-id'), 10);
          // Convertir a número el ID de entidad para comparación estricta
          const tienePermiso = permisosGenerales.entidades.some(id => parseInt(id) === entidadId);
          console.log(`🔒 Estado ${nombre} (ID: ${entidadId}) - Permiso: ${tienePermiso}`, {
              entidadId,
              tipo: typeof entidadId,
              permisosDisponibles: permisosGenerales.entidades,
              encontrado: tienePermiso
          });

          // Si no tiene permiso, pintar gris y deshabilitar
          if (!tienePermiso) {
            path.style.fill = colorBloqueado;
            path.style.cursor = 'not-allowed';
            path.classList.add('estado-bloqueado');
            return;
          }

          const key = normalize(nombre);
          const estadoData = data[key];
          console.log(`Estado ${nombre}:`, { estadoData, key });

          // Color por defecto si no hay datos
          let colorFinal = colorSinEjecutar;

          if (estadoData) {
              const estatus = (typeof estadoData === 'object' ? estadoData.estado : estadoData || "").toString().toUpperCase().trim();
              console.log(`Estado ${nombre} - Estatus:`, estatus);

              switch (estatus) {
                  case "IMPLEMENTADO":
                      colorFinal = colorConcluido;
                      break;
                  case "SIN IMPLEMENTAR":
                      colorFinal = colorSinEjecutar;
                      break;
                  case "PRUEBAS":
                  case "EN PRUEBAS":
                      colorFinal = colorOtro;
                      break;
                  default:
                      if (estatus) {
                          console.log(`Estado desconocido para ${nombre}:`, estatus);
                          colorFinal = colorOtro;
                      }
              }
          }

          path.style.fill = colorFinal;

          if (!path.dataset.listenersBound) {
            path.addEventListener("mouseenter", () => { tooltip.textContent = nombre; tooltip.style.display = "block"; });
            path.addEventListener("mousemove", (e) => { tooltip.style.left = (e.pageX + 10) + "px"; tooltip.style.top = (e.pageY + 10) + "px"; });
            path.addEventListener("mouseleave", () => { tooltip.style.display = "none"; });
            path.addEventListener("click", () => {
              // Remover efecto de todos los estados
              document.querySelectorAll('path[id^="MX-"]').forEach(p => {
                p.classList.remove('estado-seleccionado');
                // Resetear estilos inline
                p.style.stroke = '';
                p.style.strokeWidth = '';
                p.style.strokeDasharray = '';
                p.style.filter = '';
                p.style.animation = '';
              });
              
              // Agregar borde punteado al estado seleccionado
              path.classList.add('estado-seleccionado');
              
              console.log('✨ Estado seleccionado:', nombre, '- Clase aplicada:', path.classList.contains('estado-seleccionado'));
              
              const det = document.getElementById("detalle");
              document.getElementById("estadoNombre").innerText = nombre;
              det.setAttribute("data-estado", nombre);
              det.innerHTML = `
                <div class="text-center p-3">
                  <div class="spinner-border"></div>
                  <div class="mt-2">Cargando detalle…</div>
                </div>`;
              
              // Obtener permisos
              const permisos = window.__ACL_GENERAL__ || { entidades: [], buses: [] };
              
              // Agregar los buses permitidos como parámetro
              const url = `${endpointDetalle}?estado=${encodeURIComponent(nombre)}&buses=${permisos.buses.join(',')}`;
              fetch(url, { 
                cache: "no-store",
                headers: {
                  'X-Permitted-Buses': permisos.buses.join(','),
                  'X-Permitted-Entities': permisos.entidades.join(',')
                }
              })
                .then(r => r.text())
                .then(html => { 
                  det.innerHTML = html; 
                })
                .catch(e => { 
                  console.error("detalle.php:", e); 
                  det.innerHTML = `<div class="alert alert-danger">Error al cargar detalle</div>`;
                  // Remover borde en caso de error
                  path.classList.remove('estado-seleccionado');
                });
            });
            path.dataset.listenersBound = "1";
          }

          if (path.style.fill) pintados++;
        });

        // 4) Reintento defensivo si nada se pintó
        setTimeout(() => {
          if (pintados === 0 && window.reintentosMapa < window.MAX_REINTENTOS_MAPA) {
            window.reintentosMapa++;
            console.warn("🎨 Reintentando pintar mapa (general). Intento:", window.reintentosMapa);
            iniciarMapa();
          } else if (pintados > 0) {
            console.log(`✅ Mapa general pintado (${pintados} estados)`);
          } else {
            console.error("⚠️ No se pudo pintar el mapa (general) tras reintento.");
          }
        }, 400);
      }, 60);
    }).catch(err => {
      console.error("Error cargando generalindex.php:", err);
    });
  }

  // Arranque único: esperamos a que exista #mapaScript y algún path del SVG
  if (window.__timerGeneral) clearInterval(window.__timerGeneral);
  window.__timerGeneral = setInterval(() => {
    const listoScript = !!document.getElementById("mapaScript");
    const listoSVG    = !!document.querySelector('path[id^="MX-"]');
    if (listoScript && listoSVG) {
      clearInterval(window.__timerGeneral);
      window.__timerGeneral = null;
      iniciarMapa();
    }
  }, 60);
})();
