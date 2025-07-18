console.log("mapa.js cargado");

document.addEventListener("DOMContentLoaded", () => {
  const script = document.getElementById("mapaScript");
  if (!script) return console.error("No se encontró el script con id=mapaScript");

  const busSeleccionado = script.dataset.bus;
  const colorConcluido = script.dataset.colorConcluido;         // IMPLEMENTADO
  const colorSinEjecutar = script.dataset.colorSinEjecutar;     // SIN IMPLEMENTAR
  const colorOtro = script.dataset.colorOtro;                   // PRUEBAS u otros

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

  // Tooltip
  const tooltip = document.createElement("div");
  tooltip.id = "tooltip";
  document.body.appendChild(tooltip);

  // Pintar leyenda
  function pintarLeyenda() {
    const rectConcluido = document.getElementById("legendConcluido");
    const rectPruebas = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    if (rectConcluido) rectConcluido.setAttribute("fill", colorConcluido);
    if (rectPruebas) rectPruebas.setAttribute("fill", colorOtro);
    if (rectSinEjecutar) rectSinEjecutar.setAttribute("fill", colorSinEjecutar);
  }

  pintarLeyenda();

  // Pintar estados del mapa
  fetch('../server/datos.php?bus=' + encodeURIComponent(busSeleccionado))
    .then(response => response.json())
    .then(data => {
      console.log("📦 Datos recibidos:", data);

      document.querySelectorAll('path[id^="MX-"]').forEach(path => {
        const clave = path.id;
        const nombreEstado = estadoMap[clave];

        if (!nombreEstado || !(nombreEstado in data)) return;

        const estatus = data[nombreEstado]?.trim().toUpperCase();

        if (estatus === "IMPLEMENTADO") {
          path.style.fill = colorConcluido;
        } else if (estatus === "SIN IMPLEMENTAR") {
          path.style.fill = colorSinEjecutar;
        } else {
          path.style.fill = colorOtro; // PRUEBAS o mixtos
        }

        // Eventos
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
          fetch('../server/busvista.php?estado=' + encodeURIComponent(nombreEstado) + '&bus=' + encodeURIComponent(busSeleccionado))
            .then(response => response.text())
            .then(html => {
              document.getElementById('detalle').innerHTML = html;
            });
        });
      });
    });
});
