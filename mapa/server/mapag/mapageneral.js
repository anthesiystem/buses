// /final/mapa/server/mapag/mapageneral.js
console.log("mapageneral.js (general) cargado");

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
    const endpointDatos    = s.dataset.urlDatos   || "/final/mapa/server/mapag/generalindex.php";
    const endpointDetalle  = s.dataset.urlDetalle || "/final/mapa/server/mapag/detalle.php";

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

    // 1) Traer datos y esperar estadoMap
    Promise.all([fetchJsonSafe(endpointDatos), esperarEstadoMap]).then(([raw]) => {
      const data = {};
      Object.keys(raw || {}).forEach(k => { data[normalize(k)] = String(raw[k] ?? "").toUpperCase().trim(); });

      // 2) Esperar a que el SVG y las 3 leyendas existan
      const wait = setInterval(() => {
        const paths = document.querySelectorAll('path[id^="MX-"]');
        const a = document.getElementById("legendConcluido");
        const b = document.getElementById("legendPruebas");
        const c = document.getElementById("legendSinEjecutar");
        if (!paths.length || !a || !b || !c) return; // aún no está todo
        clearInterval(wait);

        // 🔹 PINTAR LEYENDAS (aquí ya existen)
        a.setAttribute("fill", colorConcluido);
        b.setAttribute("fill", colorOtro);
        c.setAttribute("fill", colorSinEjecutar);

        // 3) Pintar estados
        const estMap = window.estadoMap || {};
        let pintados = 0;

        paths.forEach(path => {
          const nombre = estMap[path.id];
          if (!nombre) return;

          const key = normalize(nombre);
          const estatus = data[key] || ""; // IMPLEMENTADO / SIN IMPLEMENTAR / PRUEBAS / MIXTO

          if (estatus === "IMPLEMENTADO")         path.style.fill = colorConcluido;
          else if (estatus === "SIN IMPLEMENTAR") path.style.fill = colorSinEjecutar;
          else if (estatus)                       path.style.fill = colorOtro;

          if (!path.dataset.listenersBound) {
            path.addEventListener("mouseenter", () => { tooltip.textContent = nombre; tooltip.style.display = "block"; });
            path.addEventListener("mousemove", (e) => { tooltip.style.left = (e.pageX + 10) + "px"; tooltip.style.top = (e.pageY + 10) + "px"; });
            path.addEventListener("mouseleave", () => { tooltip.style.display = "none"; });
            path.addEventListener("click", () => {
              const det = document.getElementById("detalle");
              document.getElementById("estadoNombre").innerText = nombre;
              det.setAttribute("data-estado", nombre);
              det.innerHTML = `
                <div class="text-center p-3">
                  <div class="spinner-border"></div>
                  <div class="mt-2">Cargando detalle…</div>
                </div>`;
              fetch(`${endpointDetalle}?estado=${encodeURIComponent(nombre)}`, { cache:"no-store" })
                .then(r => r.text())
                .then(html => { det.innerHTML = html; })
                .catch(e => { console.error("detalle.php:", e); det.innerHTML = `<div class="alert alert-danger">Error al cargar detalle</div>`; });
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
