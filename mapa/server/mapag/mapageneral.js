console.log("mapageneral.js cargado");

function iniciarMapa() {
  const script = document.getElementById("mapaScript");
  if (!script) return console.error("No se encontró el script con id=mapaScript");

  const colorConcluido = script.dataset.colorConcluido;
  const colorSinEjecutar = script.dataset.colorSinEjecutar;
  const colorOtro = script.dataset.colorOtro;

  const tooltip = document.createElement("div");
  tooltip.id = "tooltip";
  document.body.appendChild(tooltip);

  function pintarLeyendas(colorConcluido, colorSinEjecutar, colorOtro) {
  const rectConcluido = document.getElementById("legendConcluido");
  const rectPruebas = document.getElementById("legendPruebas");
  const rectSinEjecutar = document.getElementById("legendSinEjecutar");

  if (rectConcluido) rectConcluido.setAttribute("fill", colorConcluido);
  if (rectPruebas) rectPruebas.setAttribute("fill", colorOtro);
  if (rectSinEjecutar) rectSinEjecutar.setAttribute("fill", colorSinEjecutar);

  console.log("🎨 Leyendas pintadas correctamente");
}



  fetch('/mapa/server/mapag/generalindex.php')
    .then(response => response.json())
    .then(data => {
      const interval = setInterval(() => {
        const paths = document.querySelectorAll('path[id^="MX-"]');
        if (paths.length === 0) return;

        clearInterval(interval);

        pintarLeyendas(colorConcluido, colorSinEjecutar, colorOtro);


        paths.forEach(path => {
          const estadoMap = window.estadoMap;
          const clave = path.id;
          const nombreEstado = estadoMap[clave];

          if (!nombreEstado || !(nombreEstado in data)) return;

          const estatus = data[nombreEstado].trim().toUpperCase();
          if (estatus === 'IMPLEMENTADO') path.style.fill = colorConcluido;
          else if (estatus === 'SIN IMPLEMENTAR') path.style.fill = colorSinEjecutar;
          else path.style.fill = colorOtro;

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
            document.getElementById('estadoNombre').innerText = nombreEstado;
            document.getElementById('detalle').setAttribute('data-estado', nombreEstado);

            fetch('/mapa/server/mapag/detalle.php?estado=' + encodeURIComponent(nombreEstado))
              .then(response => response.text())
              .then(html => {
                document.getElementById('detalle').innerHTML = html;
              });
          });
        });
      }, 150);
    });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", iniciarMapa);
} else {
  iniciarMapa();
}
