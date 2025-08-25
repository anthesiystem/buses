async function generarPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'pt',
    format: 'letter'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const estado = document.getElementById("detalle").dataset.estado;
if (!estado) {
  alert("⚠️ Por favor selecciona un estado antes de generar el PDF.");
  return;
}

  const fecha = new Date().toLocaleDateString("es-MX");

const escudoEstado = await safeToBase64('/mapa/public/img/escudos/' + estado + '.png');
const imgMapa = await safeToBase64('/mapa/public/img/mapa_estados/' + estado + '.png');
const plantillaBase = await safeToBase64('/mapa/public/img/hojaplantilla.jpg');


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
const leyendaY = 660;
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
// ░░ PÁGINA HORIZONTAL ░░
const plantillaHorizontal = await safeToBase64('/mapa/public/img/hojaplantillahorizontal.jpg');

// 📌 Ojo: en jsPDF 2.x la firma correcta es:
doc.addPage('letter', 'l');   // 'l' = landscape (también vale 'landscape')

// Encabezados
const headers = [[
  "CATEGORÍA","ENGINE","TECNOLOGÍA","DEPENDENCIA","ENTIDAD",
  "BUS","VERSIÓN","INICIO","MIGRACIÓN","ESTATUS","AVANCE"
]];

// Construir filas
const rows = [];
document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
  const t = tr.querySelectorAll("td");
  rows.push([
    t[0]?.innerText.trim() || "",
    t[1]?.innerText.trim() || "",
    t[2]?.innerText.trim() || "",
    t[3]?.innerText.trim() || "",
    t[4]?.innerText.trim() || "",
    t[5]?.innerText.trim() || "",
    t[6]?.innerText.trim() || "",
    t[7]?.innerText.trim() || "",
    t[8]?.innerText.trim() || "",
    t[9]?.innerText.trim() || "",
    t[10]?.innerText.trim() || ""
  ]);
});

// Tabla
doc.autoTable({
  head: headers,
  body: rows,
  startY: 120,
  margin: { top: 80, bottom: 40, left: 20, right: 20 },
  styles: { fontSize: 8, halign: 'center' },
  headStyles: { fillColor: [155, 34, 71], textColor: 255, fontStyle: 'bold', fontSize: 8 },
  alternateRowStyles: { fillColor: [245, 245, 245] },
  showHead: 'everyPage',
  theme: 'grid',

  // Dibuja la PLANTILLA debajo de la tabla en cada página
  willDrawCell: function (data) {
    if (data.row.section === 'head' && data.row.index === 0 && data.column.index === 0) {
      if (plantillaHorizontal) {
        doc.addImage(
          plantillaHorizontal,
          'JPEG',    // pon 'JPEG' si tu archivo es .jpg/.jpeg
          0, 0,
          doc.internal.pageSize.getWidth(),
          doc.internal.pageSize.getHeight()
        );
      }
      // Título (repite si hay salto de página)
      doc.setFontSize(14);
      doc.setFont('helvetica', 'bold');
      doc.text("Resumen de Registros", doc.internal.pageSize.getWidth() / 2, 50, { align: 'center' });
    }
  },

  didDrawPage: function (data) {
    // Pie de página
    const fechaHoy = new Date().toLocaleDateString("es-MX");
    doc.setFontSize(9);
    doc.setTextColor(0);
    doc.text(fechaHoy, doc.internal.pageSize.getWidth() - 80, doc.internal.pageSize.getHeight() - 25);
    doc.text(`Página ${data.pageNumber}`, 30, doc.internal.pageSize.getHeight() - 25);
  }
});










// Finalmente descarga
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
