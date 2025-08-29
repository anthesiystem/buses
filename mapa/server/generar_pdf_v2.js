// Función principal para generar PDF (Versión corregida v2.1)
async function generarPDF_v2() {
  // Mostrar información de debug
  console.log("🚀 Iniciando generación de PDF... [VERSIÓN CORREGIDA v2.1]");
  console.log("📍 URL actual:", window.location.href);
  console.log("📁 Base URL:", window.location.origin);
  
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'pt',
    format: 'letter'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  
  // Intentar obtener el estado de diferentes fuentes
  let estado = null;
  
  // Método 1: desde el elemento detalle
  const detalleElement = document.getElementById("detalle");
  if (detalleElement && detalleElement.dataset.estado) {
    estado = detalleElement.dataset.estado;
    console.log("Estado obtenido desde elemento #detalle:", estado);
  }
  
  // Método 2: desde el elemento detalle-v2 (fallback)
  if (!estado) {
    const detalleV2Element = document.getElementById("detalle-v2");
    if (detalleV2Element && detalleV2Element.dataset.estado) {
      estado = detalleV2Element.dataset.estado;
      console.log("Estado obtenido desde elemento #detalle-v2:", estado);
    }
  }
  
  // Método 3: desde el modal de detalles si está visible
  if (!estado) {
    const modalTitulo = document.querySelector("#modalDetalles .modal-title");
    if (modalTitulo && modalTitulo.textContent) {
      // Extraer el estado del título del modal
      const match = modalTitulo.textContent.match(/Detalle de (.+)/);
      if (match) {
        estado = match[1].trim();
        console.log("Estado obtenido desde título del modal:", estado);
      }
    }
  }
  
  // Verificar si se pudo obtener el estado
  if (!estado) {
    alert("⚠️ Por favor selecciona un estado antes de generar el PDF.");
    console.error("No se pudo obtener el estado desde ninguna fuente");
    return;
  }

  console.log("Estado final a usar:", estado);

  // Función para detectar la ruta base correcta
  function detectarRutaBase() {
      const currentPath = window.location.pathname;
      console.log("📍 Ruta actual:", currentPath);
      
      if (currentPath.includes('/final/')) {
          return '/final/mapa/public/img';
      } else {
          return '/mapa/public/img';
      }
  }

  const rutaBase = detectarRutaBase();
  console.log("📁 Ruta base detectada:", rutaBase);

  const fecha = new Date().toLocaleDateString("es-MX");

  // Función para formatear el nombre del estado para coincidir con los archivos
  function formatearNombreEstado(estado) {
      if (!estado) return "";
      
      // Limpiar el estado de espacios extra
      let estadoLimpio = estado.trim();
      
      // Casos especiales conocidos
      const casosEspeciales = {
          'ciudad de mexico': 'Ciudad de Mexico',
          'ciudad de méxico': 'Ciudad de Mexico',
          'estado de mexico': 'Estado de Mexico',
          'estado de méxico': 'Estado de Mexico',
          'nuevo leon': 'Nuevo Leon',
          'nuevo león': 'Nuevo Leon',
          'san luis potosi': 'San Luis Potosi',
          'san luis potosí': 'San Luis Potosi',
          'queretaro': 'Queretaro',
          'querétaro': 'Queretaro',
          'yucatan': 'Yucatan',
          'yucatán': 'Yucatan',
          'michoacan': 'Michoacan',
          'michoacán': 'Michoacan',
          'baja california sur': 'Baja California Sur',
          'baja california': 'Baja California',
          'quintana roo': 'Quintana Roo'
      };
      
      // Convertir a minúsculas para la comparación
      const estadoMinusculas = estadoLimpio.toLowerCase();
      
      // Verificar casos especiales
      if (casosEspeciales[estadoMinusculas]) {
          return casosEspeciales[estadoMinusculas];
      }
      
      // Formateo general: capitalizar primera letra de cada palabra
      return estadoLimpio.split(' ').map(word => 
          word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
      ).join(' ');
  }

  // Formatear el nombre del estado para coincidir con el nombre del archivo
  const estadoFormateado = formatearNombreEstado(estado);
  console.log("Buscando imágenes para:", estadoFormateado);

  // Cargar todas las imágenes con rutas dinámicas
  console.log(`🔍 Cargando imágenes para el estado: ${estadoFormateado}`);

  const escudoEstado = await safeToBase64(`${rutaBase}/escudos/${estadoFormateado}.png`);
  const imgMapa = await safeToBase64(`${rutaBase}/mapa_estados/${estadoFormateado}.png`);
  const plantillaBase = await safeToBase64(`${rutaBase}/hojaplantilla.jpg`);

  // Verificar qué imágenes se cargaron exitosamente
  const imagenesNoEncontradas = [];
  if (!escudoEstado) imagenesNoEncontradas.push(`Escudo del estado (${estadoFormateado})`);
  if (!imgMapa) imagenesNoEncontradas.push(`Mapa del estado (${estadoFormateado})`);
  if (!plantillaBase) imagenesNoEncontradas.push('Plantilla base');

  if (imagenesNoEncontradas.length > 0) {
      console.warn("⚠️ Imágenes no encontradas:", imagenesNoEncontradas);
      const mensaje = `⚠️ No se pudieron cargar las siguientes imágenes:\n• ${imagenesNoEncontradas.join('\n• ')}\n\n¿Deseas continuar generando el PDF sin estas imágenes?`;
      if (!confirm(mensaje)) {
          console.log("❌ Generación de PDF cancelada por el usuario");
          return;
      }
  } else {
      console.log("✅ Todas las imágenes se cargaron correctamente");
  }

  // Extraer buses únicos del detalle (columna 1 es BUS)
  const busesSet = new Set();
  document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
    const bus = tr.querySelector("td:nth-child(2)")?.innerText.trim(); // Cambié de 6 a 2
    if (bus) busesSet.add(bus);
  });
  const busesChecklist = Array.from(busesSet);
  
  console.log("🚌 Buses encontrados en la tabla:", busesChecklist);

  // ░░░ PORTADA ░
  if (plantillaBase) {
    doc.addImage(plantillaBase, 'PNG', 0, 0, pageWidth, pageHeight);
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
    doc.setDrawColor(0);
    doc.setLineWidth(0.5);
    doc.rect(x1, y1, w1, h1);
  }

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

  // Obtener catálogo completo desde el atributo personalizado
  const script = document.getElementById("mapaScript");
  let catalogoCompleto = [];
  
  if (script && script.dataset.catalogoBuses) {
    try {
      catalogoCompleto = JSON.parse(script.dataset.catalogoBuses);
      console.log("📋 Catálogo completo cargado:", catalogoCompleto.length, "buses");
    } catch (error) {
      console.error("❌ Error al parsear catálogo de buses:", error);
      // Fallback: usar los buses encontrados en la tabla
      catalogoCompleto = busesChecklist;
    }
  } else {
    console.warn("⚠️ No se encontró el elemento mapaScript o dataset.catalogoBuses");
    // Fallback: usar los buses encontrados en la tabla
    catalogoCompleto = busesChecklist;
  }
  
  console.log("📋 Usando catálogo de buses:", catalogoCompleto);

  // Buses presentes en el estado (de la tabla HTML) - Columna 1 (índice 1)
  const busesDelEstado = [];
  document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
    const bus = tr.children[1]?.innerText.trim(); // Cambié de 5 a 1
    if (bus && !busesDelEstado.includes(bus)) {
      busesDelEstado.push(bus);
    }
  });
  
  console.log("🚌 Buses del estado encontrados:", busesDelEstado);
  
  // Debug: mostrar estructura de la primera fila de la tabla
  const primeraFila = document.querySelector("#modalDetalles table tbody tr");
  if (primeraFila) {
    console.log("🔍 Debug - Primera fila de la tabla:");
    const celdas = primeraFila.querySelectorAll("td");
    celdas.forEach((celda, index) => {
      console.log(`  Columna ${index}: "${celda.innerText.trim()}"`);
    });
  }

  // Título antes de la tabla
  doc.setFontSize(14);
  doc.text("Buses con los que cuenta el estado:", pageWidth / 2, 440, { align: 'center' });

  // Leyenda de Estatus centrada
  const leyendaY = 660;
  const spacing = 120;
  const startX = (pageWidth - (3.7 * spacing)) / 2;

  doc.setFontSize(10);
  doc.setTextColor(0, 0, 0);

  // IMPLEMENTADO
  doc.setFillColor(31, 157, 11);
  doc.triangle(startX, leyendaY + 10, startX, leyendaY + 20, startX + 10, leyendaY + 15, 'F');
  doc.text("Implementado", startX + 15, leyendaY + 18);

  // PRUEBAS
  doc.setFillColor(255, 204, 0);
  doc.triangle(startX + spacing, leyendaY + 10, startX + spacing, leyendaY + 20, startX + spacing + 10, leyendaY + 15, 'F');
  doc.text("Pruebas", startX + spacing + 15, leyendaY + 18);

  // SIN IMPLEMENTAR
  doc.setFillColor(204, 0, 0);
  doc.triangle(startX + 2 * spacing, leyendaY + 10, startX + 2 * spacing, leyendaY + 20, startX + 2 * spacing + 10, leyendaY + 15, 'F');
  doc.text("Sin implementar", startX + 2 * spacing + 15, leyendaY + 18);

  // SIN REGISTROS
  doc.setFillColor(143, 143, 143);
  doc.triangle(startX + 3 * spacing, leyendaY + 10, startX + 3 * spacing, leyendaY + 20, startX + 3 * spacing + 10, leyendaY + 15, 'F');
  doc.text("Sin registros", startX + 3 * spacing + 15, leyendaY + 18);

  // Función para obtener lista de estatus por bus
  function getEstatusListForBus(bus) {
    console.log(`🔍 Buscando estatus para bus: ${bus}`);
    
    const filas = Array.from(document.querySelectorAll("#modalDetalles table tbody tr"))
      .filter(tr => {
        const busName = tr.children[1]?.innerText.trim(); // Bus está en columna 1
        const match = busName === bus;
        if (match) {
          console.log(`  ✅ Encontrado: ${busName} = ${bus}`);
        }
        return match;
      });

    console.log(`  📊 Filas encontradas para ${bus}: ${filas.length}`);

    if (filas.length === 0) {
      console.log(`  ⚠️ Sin registros para ${bus}`);
      return ['SIN REGISTROS'];
    }

    const estatuses = Array.from(new Set(filas.map(tr => {
      const estatus = tr.children[8]?.innerText.trim().toUpperCase(); // Estatus está en columna 8
      console.log(`  📋 Estatus encontrado: ${estatus}`);
      return estatus;
    })));
    
    console.log(`  🎯 Estatus únicos para ${bus}:`, estatuses);
    return estatuses;
  }

  // Función para convertir estatus a color RGB
  function colorFromEstatus(estatus) {
    if (!estatus) return [143, 143, 143]; // gris para valores vacíos
    
    const estatusLimpio = estatus.toString().toUpperCase().trim();
    console.log(`🎨 Convirtiendo estatus: "${estatusLimpio}"`);
    
    if (estatusLimpio.includes('IMPLEMENTADO') || estatusLimpio === 'IMPLEMENTADO') {
      console.log(`  ✅ Verde para: ${estatusLimpio}`);
      return [31, 157, 11];   // verde
    }
    if (estatusLimpio.includes('PRUEBA') || estatusLimpio === 'PRUEBAS') {
      console.log(`  🟡 Amarillo para: ${estatusLimpio}`);
      return [255, 204, 0];   // amarillo
    }
    if (estatusLimpio.includes('SIN IMPLEMENTAR') || estatusLimpio === 'SIN IMPLEMENTAR') {
      console.log(`  🔴 Rojo para: ${estatusLimpio}`);
      return [204, 0, 0];     // rojo
    }
    if (estatusLimpio.includes('SIN REGISTROS') || estatusLimpio === 'SIN REGISTROS') {
      console.log(`  ⚫ Gris para: ${estatusLimpio}`);
      return [143, 143, 143]; // gris
    }
    
    console.log(`  ❓ Color por defecto para: ${estatusLimpio}`);
    return [200, 200, 200]; // gris claro por defecto
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
  console.log("🔄 Cargando plantilla horizontal...");
  const plantillaHorizontal = await safeToBase64(`${rutaBase}/hojaplantillahorizontal.jpg`);
  if (!plantillaHorizontal) {
      console.warn("⚠️ No se pudo cargar la plantilla horizontal");
  }

  doc.addPage('letter', 'l');

  // Encabezados completos con texto más pequeño
  const headers = [[
    "CATEGORÍA","BUS","MOTOR BASE","TECNOLOGÍA","VER.","DEPEND.",
    "F.INICIO","F.MIGRAC.","ESTATUS","AVANCE"
  ]];

  // Función para truncar texto si es muy largo
  function truncarTexto(texto, maxLength) {
    if (!texto) return "";
    const textoLimpio = texto.toString().trim();
    return textoLimpio.length > maxLength ? textoLimpio.substring(0, maxLength - 3) + "..." : textoLimpio;
  }

  // Construir filas con el orden correcto y texto menos truncado
  const rows = [];
  document.querySelectorAll("#modalDetalles table tbody tr").forEach(tr => {
    const t = tr.querySelectorAll("td");
    rows.push([
      truncarTexto(t[0]?.innerText, 15),  // Categoría - aumentado a 15 chars
      truncarTexto(t[1]?.innerText, 20),  // Bus - aumentado a 20 chars
      truncarTexto(t[2]?.innerText, 15),  // Motor base - aumentado a 15 chars
      truncarTexto(t[3]?.innerText, 12),  // Tecnología - aumentado a 12 chars
      truncarTexto(t[4]?.innerText, 10),  // Versión - aumentado a 10 chars
      truncarTexto(t[5]?.innerText, 15),  // Dependencia - aumentado a 15 chars
      truncarTexto(t[6]?.innerText, 12),  // Fecha inicio - aumentado a 12 chars
      truncarTexto(t[7]?.innerText, 12),  // Fecha migración - aumentado a 12 chars
      truncarTexto(t[8]?.innerText, 12),  // Estatus - aumentado a 12 chars
      truncarTexto(t[9]?.innerText, 8)    // Avance - aumentado a 8 chars
    ]);
  });

  // Tabla con configuración mejorada
  doc.autoTable({
    head: headers,
    body: rows,
    startY: 120,
    margin: { top: 80, bottom: 40, left: 15, right: 15 }, // Márgenes más pequeños
    styles: { 
      fontSize: 6,           // Reducido de 8 a 7 para mejor balance
      halign: 'center',
      valign: 'middle',
      cellPadding: 3,        // Más padding
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
      1: { cellWidth: 85, fontSize: 6, halign: 'center' },   // BUS
      2: { cellWidth: 75, fontSize: 6, halign: 'center' },   // MOTOR BASE - más ancho
      3: { cellWidth: 70, fontSize: 6, halign: 'center' },   // TECNOLOGÍA - más ancho
      4: { cellWidth: 45, fontSize: 6, halign: 'center' },   // VER.
      5: { cellWidth: 65, fontSize: 6, halign: 'center' },   // DEPEND.
      6: { cellWidth: 60, fontSize: 6, halign: 'center' },   // F.INICIO
      7: { cellWidth: 60, fontSize: 6, halign: 'center' },   // F.MIGRAC.
      8: { cellWidth: 65, fontSize: 6, halign: 'center' },   // ESTATUS
      9: { cellWidth: 50, fontSize: 6, halign: 'center' }    // AVANCE
    },
    alternateRowStyles: { fillColor: [245, 245, 245] },
    showHead: 'everyPage',
    theme: 'grid',
    tableLineColor: [0, 0, 0],
    tableLineWidth: 0.1,
    rowPageBreak: 'auto',
    minCellHeight: 12,  // Altura mínima de celda

    willDrawCell: function (data) {
      // Forzar tamaño de fuente pequeño en headers
      if (data.row.section === 'head') {
        doc.setFontSize(5);  // Forzar tamaño muy pequeño para headers
        doc.setFont('helvetica', 'bold');
      }
      
      // Plantilla de fondo solo en la primera celda del header de la primera página
      if (data.row.section === 'head' && data.row.index === 0 && data.column.index === 0) {
        if (plantillaHorizontal) {
          doc.addImage(
            plantillaHorizontal,
            'PNG',
            0, 0,
            doc.internal.pageSize.getWidth(),
            doc.internal.pageSize.getHeight()
          );
        }
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text("Resumen de Registros", doc.internal.pageSize.getWidth() / 2, 50, { align: 'center' });
        // Resetear después del título
        doc.setFontSize(5);
        doc.setFont('helvetica', 'bold');
      }
    },

    didDrawPage: function (data) {
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
      console.log("🔄 Cargando imagen:", path);
      
      const res = await fetch(path, {
        method: 'GET',
        cache: 'no-cache'
      });
      
      if (!res.ok) {
        console.error(`❌ Error HTTP ${res.status}: ${res.statusText} para ${path}`);
        return null;
      }
      
      const contentType = res.headers.get('content-type');
      if (!contentType || !contentType.startsWith('image/')) {
        console.warn(`⚠️ Tipo de contenido inesperado: ${contentType} para ${path}`);
      }
      
      const blob = await res.blob();
      if (blob.size === 0) {
        console.error(`❌ Imagen vacía: ${path}`);
        return null;
      }
      
      const base64 = await toBase64(blob);
      console.log(`✅ Imagen cargada exitosamente: ${path} (${blob.size} bytes)`);
      return base64;
    } catch (err) {
      console.error(`❌ Error cargando imagen ${path}:`, err.message);
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

// Alias para compatibilidad con versiones anteriores
async function generarPDF() {
  return await generarPDF_v2();
}
