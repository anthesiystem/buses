// Versión de prueba con rutas fijas
async function generarPDFTest() {
  console.log("🚀 Iniciando generación de PDF de prueba...");
  
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'pt',
    format: 'letter'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  
  // Estado de prueba
  const estado = "Aguascalientes";
  console.log("Estado de prueba:", estado);

  const fecha = new Date().toLocaleDateString("es-MX");

  // Función simple para cargar imagen
  async function cargarImagen(url) {
    try {
      console.log("Cargando:", url);
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const blob = await response.blob();
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    } catch (error) {
      console.error("Error cargando imagen:", url, error);
      return null;
    }
  }

  // Cargar imágenes con rutas absolutas
  const escudoEstado = await cargarImagen(`/final/mapa/public/img/escudos/${estado}.png`);
  const imgMapa = await cargarImagen(`/final/mapa/public/img/mapa_estados/${estado}.png`);
  const plantillaBase = await cargarImagen('/final/mapa/public/img/hojaplantilla.png');

  // Verificar cargas
  console.log("Escudo cargado:", !!escudoEstado);
  console.log("Mapa cargado:", !!imgMapa);
  console.log("Plantilla cargada:", !!plantillaBase);

  // Crear PDF
  if (plantillaBase) {
    doc.addImage(plantillaBase, 'PNG', 0, 0, pageWidth, pageHeight);
  }

  // Títulos
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(16);
  doc.text("Dirección de Base de Datos e Integración de información", pageWidth / 2, 140, { align: 'center' });
  doc.text("Reporte diagnóstico, seguimiento a buses", pageWidth / 2, 160, { align: 'center' });

  doc.setFontSize(20);
  doc.text(estado.toUpperCase(), pageWidth / 2, 200, { align: 'center' });

  // Escudo
  if (escudoEstado) {
    const x1 = 100;
    const y1 = 230;
    const w1 = 170;
    const h1 = 170;
    doc.addImage(escudoEstado, 'PNG', x1, y1, w1, h1);
    doc.setDrawColor(0);
    doc.setLineWidth(0.5);
    doc.rect(x1, y1, w1, h1);
  }

  // Mapa
  if (imgMapa) {
    const x2 = pageWidth - 280;
    const y2 = 230;
    const w2 = 170;
    const h2 = 170;
    doc.addImage(imgMapa, 'PNG', x2, y2, w2, h2);
    doc.setDrawColor(0);
    doc.setLineWidth(0.5);
    doc.rect(x2, y2, w2, h2);
  }

  // Fecha
  doc.setFontSize(9);
  doc.text(fecha, pageWidth - 80, pageHeight - 30);

  // Segunda página con tabla de prueba
  doc.addPage('letter', 'l');

  // Datos de prueba con diferentes longitudes de texto
  const headers = [["CATEGORÍA","BUS","MOTOR BASE","TECNOLOGÍA","VER.","DEPEND.","F.INICIO","F.MIGRAC.","ESTATUS","AVANCE"]];
  const rows = [
    ["NUEVOS", "CUP (CERTIFICADO)", "SQL SERVER", "IWAY", "1.0", "DEPENDENCIA A", "01/01/2024", "01/06/2024", "IMPLEMENTADO", "100%"],
    ["MIGRACIONES", "MJ (MANDAMIENTOS)", "POSTGRESQL", "IWAY", "2.1", "DEPENDENCIA B", "15/02/2024", "15/07/2024", "PRUEBAS", "85%"],
    ["NUEVOS", "RNAE (ARMAMENTO)", "POSTGRESQL", "DATA MIGRATOR", "1.5", "DEPENDENCIA C", "01/03/2024", "01/08/2024", "SIN IMPLEMENTAR", "0%"],
    ["MIGRACIONES", "VEH (OFICIALES)", "POSTGRESQL", "IWAY", "1.2", "DEPENDENCIA D", "20/03/2024", "20/08/2024", "IMPLEMENTADO", "100%"]
  ];

  // Tabla con la nueva configuración mejorada
  doc.autoTable({
    head: headers,
    body: rows,
    startY: 120,
    margin: { top: 80, bottom: 40, left: 15, right: 15 },
    styles: { 
      fontSize: 7,           // Reducido de 8 a 7 para mejor balance
      halign: 'center',
      valign: 'middle',
      cellPadding: 3,
      lineColor: [0, 0, 0],
      lineWidth: 0.1,
      overflow: 'linebreak'
    },
    headStyles: { 
      fillColor: [155, 34, 71], 
      textColor: 255, 
      fontStyle: 'bold', 
      fontSize: 5,           // Tamaño aún más pequeño para headers
      halign: 'center',
      valign: 'middle'       // Agregado para mejor alineación
    },
    columnStyles: {
      0: { cellWidth: 70, fontSize: 6, halign: 'center' },   // CATEGORÍA - más ancho, fuente más pequeña
      1: { cellWidth: 85, fontSize: 8, halign: 'center' },   // BUS
      2: { cellWidth: 75, fontSize: 7, halign: 'center' },   // MOTOR BASE - más ancho
      3: { cellWidth: 70, fontSize: 7, halign: 'center' },   // TECNOLOGÍA - más ancho
      4: { cellWidth: 45, fontSize: 7, halign: 'center' },   // VER.
      5: { cellWidth: 65, fontSize: 7, halign: 'center' },   // DEPEND.
      6: { cellWidth: 60, fontSize: 7, halign: 'center' },   // F.INICIO
      7: { cellWidth: 60, fontSize: 7, halign: 'center' },   // F.MIGRAC.
      8: { cellWidth: 65, fontSize: 7, halign: 'center' },   // ESTATUS
      9: { cellWidth: 50, fontSize: 7, halign: 'center' }    // AVANCE
    },
    alternateRowStyles: { fillColor: [245, 245, 245] },
    showHead: 'everyPage',
    theme: 'grid',
    minCellHeight: 12,
    willDrawCell: function (data) {
      // Forzar tamaño de fuente pequeño en headers
      if (data.row.section === 'head') {
        doc.setFontSize(5);  // Forzar tamaño muy pequeño para headers
        doc.setFont('helvetica', 'bold');
      }
    },
    didDrawPage: function (data) {
      // Título
      doc.setFontSize(14);
      doc.setFont('helvetica', 'bold');
      doc.text("Tabla de Prueba - Formato Mejorado", doc.internal.pageSize.getWidth() / 2, 50, { align: 'center' });
      
      // Pie de página
      const fechaHoy = new Date().toLocaleDateString("es-MX");
      doc.setFontSize(9);
      doc.setTextColor(0);
      doc.text(fechaHoy, doc.internal.pageSize.getWidth() - 80, doc.internal.pageSize.getHeight() - 25);
      doc.text(`Página ${data.pageNumber}`, 30, doc.internal.pageSize.getHeight() - 25);
    }
  });

  // Descargar
  doc.save(`test_formato_${estado}_${fecha.replaceAll('/', '-')}.pdf`);
  console.log("✅ PDF de prueba con formato mejorado generado exitosamente");
}

// Hacer disponible globalmente
window.generarPDFTest = generarPDFTest;
console.log("💡 Función de prueba disponible: generarPDFTest()");
