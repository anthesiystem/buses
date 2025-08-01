console.log("mapa.js cargado");

function iniciarMapa() {
  const script = document.getElementById("mapaScript");
  if (!script) return console.error("No se encontró el script con id=mapaScript");

  const busSeleccionado = script.dataset.bus;
  const colorConcluido = script.dataset.colorConcluido;
  const colorSinEjecutar = script.dataset.colorSinEjecutar;
  const colorOtro = script.dataset.colorOtro;

  console.log("📍 Bus seleccionado:", busSeleccionado);

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

  // Pintar leyenda
  function pintarLeyenda() {
    const rectConcluido = document.getElementById("legendConcluido");
    const rectPruebas = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    console.log("🎨 Leyenda pintada desde mapa.js");

    if (rectConcluido) rectConcluido.setAttribute("fill", colorConcluido);
    if (rectPruebas) rectPruebas.setAttribute("fill", colorOtro);
    if (rectSinEjecutar) rectSinEjecutar.setAttribute("fill", colorSinEjecutar);
  }

  pintarLeyenda();

  fetch('../server/mapabus/datos.php?bus=' + encodeURIComponent(busSeleccionado))
    .then(response => response.json())
    .then(data => {
      console.log("📦 Datos recibidos:", data);

      document.querySelectorAll('path[id^="MX-"]').forEach(path => {
        const clave = path.id;
        const nombreEstado = estadoMap[clave];

        console.log("🧩 Estado detectado:", clave, "→", nombreEstado);

        if (!nombreEstado || !(nombreEstado in data)) return;

        const estadoData = data[nombreEstado];
        const estatus = (estadoData.estatus || "").trim().toUpperCase();

        if (estatus === "IMPLEMENTADO") {
          path.style.fill = colorConcluido;
        } else if (estatus === "SIN IMPLEMENTAR") {
          path.style.fill = colorSinEjecutar;
        } else {
          path.style.fill = colorOtro;
        }

        path.addEventListener('mouseenter', () => {
          tooltip.textContent = nombreEstado;
          tooltip.style.display = 'block';
        });

        path.addEventListener('mousemove', (e) => {
          tooltip.style.left = (e.pageX + 10) + 'px';
          tooltip.style.top = (e.pageY + 10) + 'px';
        });

        path.addEventListener('mouseleave', () => {
          tooltip.style.display = 'none';
        });

        path.addEventListener('click', () => {
          fetch('../server/mapabus/busvista.php?estado=' + encodeURIComponent(nombreEstado) + '&bus=' + encodeURIComponent(busSeleccionado))
            .then(response => response.text())
            .then(html => {
              document.getElementById('detalle').innerHTML = html;
            });
        });
      });
    });
}

// ⏱️ Esperar que SVG esté disponible en DOM
const esperarMapa = setInterval(() => {
  const path = document.querySelector('path[id^="MX-"]');
  if (path) {
    clearInterval(esperarMapa);
    iniciarMapa();
  }
}, 100);