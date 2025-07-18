<?php include '../server/auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>BUS INTEGRACION</title>
  <link rel="stylesheet" href="../server/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="contenedor">
  <div id="mapa">
    <?php echo file_get_contents("mapa.svg"); ?>
  </div>
  <div id="info" style="padding-top: 70px;">
    <center>
      <h2 id="estadoNombre">Información del Estado</h2>
      <div id="detalle" ></div>
    </center>
  </div>
</div>

<script src="../server/estadoMap.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const tooltip = document.createElement("div");
  tooltip.id = "tooltip";
  document.body.appendChild(tooltip);

  fetch('../server/dindex.php')
    .then(response => response.json())
    .then(data => {
      document.querySelectorAll('path[id^="MX-"]').forEach(path => {
        const clave = path.id;
        const nombreEstado = estadoMap[clave];

        if (!nombreEstado || !(nombreEstado in data)) return;

        const estatus = data[nombreEstado];
        if (estatus === 'IMPLEMENTADO') path.style.fill = '#95e039';
        else if (estatus === 'SIN IMPLEMENTAR') path.style.fill = '#de4f33';
        else path.style.fill = '#f6e62f';

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

          fetch('../server/detalle.php?estado=' + encodeURIComponent(nombreEstado))
            .then(response => response.text())
            .then(html => {
              document.getElementById('detalle').innerHTML = html;
            });
        });

      });
    });
});
</script>


<?php
// Obtener todos los buses desde la BD, excepto VACIA
$conexion = new mysqli("localhost", "admin", "admin1234", "busmap");
$catalogoBuses = [];
$result = $conexion->query("SELECT Nombre FROM bus WHERE Nombre != 'VACIA'");
while ($row = $result->fetch_assoc()) {
    $catalogoBuses[] = $row['Nombre'];
}
$conexion->close();
?>
<script 
  id="mapaScript"
  data-catalogo-buses='<?php echo json_encode($catalogoBuses); ?>'>
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const interval = setInterval(() => {
    const rectConcluido = document.getElementById("legendConcluido");
    const rectPruebas = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    if (rectConcluido && rectPruebas && rectSinEjecutar) {
      rectConcluido.setAttribute("fill", "#95e039");
      rectPruebas.setAttribute("fill", "#f6e62f");
      rectSinEjecutar.setAttribute("fill", "#de4f33");
      clearInterval(interval);
    }
  }, 200);
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>


  <script>
async function generarPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'pt',
    format: 'letter'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const estado = document.querySelector("h2")?.innerText ?? "Estado";
  const fecha = new Date().toLocaleDateString("es-MX");

  const escudoEstado = await safeToBase64('../img/escudos/' + estado + '.png');
  const imgMapa = await safeToBase64('../img/mapa_estados/' + estado + '.png');
  const plantillaBase = await safeToBase64('../img/hojaplantilla.jpg');

  // Extraer buses únicos del detalle
// Extraer buses únicos del detalle (columna 6 ahora es BUS)
const busesSet = new Set();
document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
  const bus = tr.querySelector("td:nth-child(6)")?.innerText.trim(); 
  if (bus) busesSet.add(bus);
});
const busesChecklist = Array.from(busesSet);



  // ░░░ PORTADA ░
if (plantillaBase) {
  doc.addImage(plantillaBase, 'PNG', 0, 0, pageWidth, pageHeight); // <-- PRIMERO la plantilla
}

// TÍTULOS DE ENCABEZADO
doc.setFont('helvetica', 'bold');
doc.setFontSize(16);
doc.text("Dirección de Base de Datos e Integración de información", pageWidth / 2, 140, { align: 'center' });
doc.text("Reporte diagnóstico, seguimiento a buses", pageWidth / 2, 160, { align: 'center' });

doc.setFontSize(20);
doc.text(estado.toUpperCase(), pageWidth / 2, 200, { align: 'center' });


// ESCUDO Y MAPA DESPUÉS DEL FONDO
if (escudoEstado) {
  const x1 = 100;
  const y1 = 230;
  const w1 = 170;
  const h1 = 170;
  doc.addImage(escudoEstado, 'PNG', x1, y1, w1, h1, { align: 'center' });
  doc.setDrawColor(0); // negro
  doc.setLineWidth(0.5);
  doc.rect(x1, y1, w1, h1); // borde al escudo
}

if (imgMapa) {
  const x2 = pageWidth - 280;
  const y2 = 230;
  const w2 = 170;
  const h2 = 170;
  doc.addImage(imgMapa, 'PNG', x2, y2, w2, h2);
  doc.setDrawColor(0); // negro
  doc.setLineWidth(0.5);
  doc.rect(x2, y2, w2, h2); // borde al mapa
}


// Agregar checklist de BUSES
// Obtener lista completa de buses desde el atributo data
// Obtener catálogo completo desde el atributo personalizado
const script = document.getElementById("mapaScript");
const catalogoCompleto = JSON.parse(script.dataset.catalogoBuses);

// Buses presentes en el estado (de la tabla HTML)
const busesDelEstado = [];
document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
  const bus = tr.children[5]?.innerText.trim(); 
  if (bus && !busesDelEstado.includes(bus)) {
    busesDelEstado.push(bus);
  }
});



// Título antes de la tabla
doc.setFontSize(14);
doc.text("Buses con los que cuenta el estado:", pageWidth / 2, 440, { align: 'center' });


// Leyenda de Estatus centrada
const leyendaY = 600;
const spacing = 120; // separación horizontal entre columnas
const startX = (pageWidth - (3.7 * spacing)) / 2;

doc.setFontSize(10);
doc.setTextColor(0, 0, 0);

// IMPLEMENTADO
doc.setFillColor(31, 157, 11); // verde
doc.triangle(startX, leyendaY + 10, startX, leyendaY + 20, startX + 10, leyendaY + 15, 'F');
doc.text("Implementado", startX + 15, leyendaY + 18);

// PRUEBAS
doc.setFillColor(255, 204, 0); // amarillo
doc.triangle(startX + spacing, leyendaY + 10, startX + spacing, leyendaY + 20, startX + spacing + 10, leyendaY + 15, 'F');
doc.text("Pruebas", startX + spacing + 15, leyendaY + 18);

// SIN IMPLEMENTAR
doc.setFillColor(204, 0, 0); // rojo
doc.triangle(startX + 2 * spacing, leyendaY + 10, startX + 2 * spacing, leyendaY + 20, startX + 2 * spacing + 10, leyendaY + 15, 'F');
doc.text("Sin implementar", startX + 2 * spacing + 15, leyendaY + 18);

// SIN REGISTROS
doc.setFillColor(143, 143, 143); // rojo
doc.triangle(startX + 3 * spacing, leyendaY + 10, startX + 3 * spacing, leyendaY + 20, startX + 3 * spacing + 10, leyendaY + 15, 'F');
doc.text("Sin registros", startX + 3 * spacing + 15, leyendaY + 18);

// Función para obtener lista de estatus por bus
function getEstatusListForBus(bus) {
  const filas = Array.from(document.querySelectorAll("#modalDetalles table tbody tr"))
    .filter(tr => tr.children[5]?.innerText.trim() === bus);

  if (filas.length === 0) return ['SIN REGISTROS'];

  return Array.from(new Set(filas.map(tr => tr.children[9]?.innerText.trim().toUpperCase())));
}


// Función para convertir estatus a color RGB
function colorFromEstatus(estatus) {
  if (estatus === 'IMPLEMENTADO') return [31, 157, 11];   // verde
  if (estatus === 'PRUEBAS') return [255, 204, 0];        // amarillo
  if (estatus === 'SIN IMPLEMENTAR') return [204, 0, 0];  // rojo
  if (estatus === 'SIN REGISTROS') return [143, 143, 143]; // gris
  return [200, 200, 200]; // por si acaso
}

// Coordenadas iniciales
let y = 480;
const col1X = pageWidth / 2 - 230;
const col2X = pageWidth / 2 + 10;

// Dibujo de tabla de buses con hasta 2 triángulos por celda
for (let i = 0; i < catalogoCompleto.length; i += 2) {
  const bus1 = catalogoCompleto[i];
  const bus2 = catalogoCompleto[i + 1];

  // Columna izquierda
  doc.setDrawColor(0);
  doc.setLineWidth(0.5);
  doc.rect(col1X, y - 10, 220, 20);

  const estatuses1 = getEstatusListForBus(bus1);
  if (estatuses1[0]) {
    doc.setFillColor(...colorFromEstatus(estatuses1[0]));
    doc.triangle(col1X + 12, y - 7, col1X + 12, y + 7, col1X + 22, y, 'F');
  }
  if (estatuses1[1]) {
    doc.setFillColor(...colorFromEstatus(estatuses1[1]));
    doc.triangle(col1X + 24, y - 7, col1X + 24, y + 7, col1X + 34, y, 'F');
  }
  doc.setTextColor(0, 0, 0);
  doc.text(bus1, col1X + 42, y + 4);

  // Columna derecha (si hay bus2)
  if (bus2) {
    doc.rect(col2X, y - 10, 220, 20);

    const estatuses2 = getEstatusListForBus(bus2);
    if (estatuses2[0]) {
      doc.setFillColor(...colorFromEstatus(estatuses2[0]));
      doc.triangle(col2X + 12, y - 7, col2X + 12, y + 7, col2X + 22, y, 'F');
    }
    if (estatuses2[1]) {
      doc.setFillColor(...colorFromEstatus(estatuses2[1]));
      doc.triangle(col2X + 24, y - 7, col2X + 24, y + 7, col2X + 34, y, 'F');
    }
    doc.setTextColor(0, 0, 0);
    doc.text(bus2, col2X + 42, y + 4);
  }

  y += 25;
}





  doc.setFontSize(9);
  doc.text(fecha, pageWidth - 80, pageHeight - 30);






  // ░░░ NUEVA PÁGINA CON FORMATO HORIZONTAL ░░░
const plantillaHorizontal = await safeToBase64('../img/hojaplantillahorizontal.jpg');

doc.addPage('letter', 'landscape'); // Cambia orientación
const pageWidthH = doc.internal.pageSize.getWidth();
const pageHeightH = doc.internal.pageSize.getHeight();

if (plantillaHorizontal) {
  doc.addImage(plantillaHorizontal, 'PNG', 0, 0, pageWidthH, pageHeightH);
}

doc.setFontSize(14);
doc.setFont('helvetica', 'bold');
doc.text("Resumen de Registros", pageWidthH / 2, 80, { align: 'center' });

const headers = [["CATEGORÍA", "ENGINE", "TECNOLOGÍA", "DEPENDENCIA", "ENTIDAD", "BUS", "VERSIÓN", "INICIO", "MIGRACIÓN", "ESTATUS", "AVANCE"]];
const rows = [];

document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
  const tds = tr.querySelectorAll("td");
  const dataRow = Array.from(tds).map(td => td.innerText.trim());
  rows.push(dataRow);
});

doc.autoTable({
  head: headers,
  body: rows,
  startY: 120,
  margin: { left: 20, right: 20 },
  styles: {
    fontSize: 8,
    halign: 'center'
  },
  headStyles: {
    fillColor: [155, 34, 71],
    fontStyle: 'bold'
  },
  alternateRowStyles: {
    fillColor: [245, 245, 245]
  },
  didDrawPage: function (data) {
    // fondo y pie de página para cada hoja horizontal
    if (data.pageNumber > 1 && plantillaHorizontal) {
      doc.addImage(plantillaHorizontal, 'PNG', 0, 0, pageWidthH, pageHeightH);
      doc.setFontSize(9);
      doc.text(fecha, pageWidthH - 80, pageHeightH - 30);
    }
  }
});






  doc.save(`reporte_${estado}_${fecha.replaceAll('/', '-')}.pdf`);

  // Registrar en la bitácora
      fetch('./registrar_descarga_pdf.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ estado })
      });

  // ░░░ FUNCIONES AUXILIARES ░░░
  async function safeToBase64(path) {
    try {
      const res = await fetch(path);
      if (!res.ok) throw new Error("404");
      const blob = await res.blob();
      return await toBase64(blob);
    } catch (err) {
      console.warn("Imagen no encontrada:", path);
      return null;
    }
  }

  function toBase64(blob) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }
}
</script>


<button type="button" class="btn btn-danger btn-sm ms-3" onclick="generarPDF()">Descargar PDF</button>

</body>
</html>
