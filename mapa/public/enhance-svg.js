// Script para mejorar img-map.svg con datos de mapa.svg
// Mapeo de estados mexicanos con sus códigos y datos

const estadosMexicanosDatos = {
    'Aguascalientes': { codigo: 'MX-AGU', id: 1, clase: 'mx-state is-blocked' },
    'Baja California': { codigo: 'MX-BCN', id: 2, clase: 'mx-state-disabled' },
    'Baja California Sur': { codigo: 'MX-BCS', id: 3, clase: 'mx-state-disabled' },
    'Campeche': { codigo: 'MX-CAM', id: 4, clase: 'mx-state-disabled' },
    'Chiapas': { codigo: 'MX-CHP', id: 5, clase: 'mx-state-disabled' },
    'Chihuahua': { codigo: 'MX-CHH', id: 6, clase: 'mx-state-disabled' },
    'Ciudad de México': { codigo: 'MX-CMX', id: 7, clase: 'mx-state-disabled' },
    'Coahuila': { codigo: 'MX-COA', id: 8, clase: 'mx-state-disabled' },
    'Colima': { codigo: 'MX-COL', id: 9, clase: 'mx-state-disabled' },
    'Durango': { codigo: 'MX-DUR', id: 10, clase: 'mx-state-disabled' },
    'Guanajuato': { codigo: 'MX-GUA', id: 11, clase: 'mx-state-disabled' },
    'Guerrero': { codigo: 'MX-GRO', id: 12, clase: 'mx-state-disabled' },
    'Hidalgo': { codigo: 'MX-HID', id: 13, clase: 'mx-state-disabled' },
    'Jalisco': { codigo: 'MX-JAL', id: 14, clase: 'mx-state-disabled' },
    'Estado de México': { codigo: 'MX-MEX', id: 15, clase: 'mx-state-disabled' },
    'Michoacán': { codigo: 'MX-MIC', id: 16, clase: 'mx-state-disabled' },
    'Morelos': { codigo: 'MX-MOR', id: 17, clase: 'mx-state-disabled' },
    'Nayarit': { codigo: 'MX-NAY', id: 18, clase: 'mx-state-disabled' },
    'Nuevo León': { codigo: 'MX-NLE', id: 19, clase: 'mx-state-disabled' },
    'Oaxaca': { codigo: 'MX-OAX', id: 20, clase: 'mx-state-disabled' },
    'Puebla': { codigo: 'MX-PUE', id: 21, clase: 'mx-state-disabled' },
    'Querétaro': { codigo: 'MX-QUE', id: 22, clase: 'mx-state-disabled' },
    'Quintana Roo': { codigo: 'MX-ROO', id: 23, clase: 'mx-state-disabled' },
    'San Luis Potosí': { codigo: 'MX-SLP', id: 24, clase: 'mx-state-disabled' },
    'Sinaloa': { codigo: 'MX-SIN', id: 25, clase: 'mx-state-disabled' },
    'Sonora': { codigo: 'MX-SON', id: 26, clase: 'mx-state-disabled' },
    'Tabasco': { codigo: 'MX-TAB', id: 27, clase: 'mx-state-disabled' },
    'Tamaulipas': { codigo: 'MX-TAM', id: 28, clase: 'mx-state-disabled' },
    'Tlaxcala': { codigo: 'MX-TLA', id: 29, clase: 'mx-state-disabled' },
    'Veracruz': { codigo: 'MX-VER', id: 30, clase: 'mx-state-disabled' },
    'Yucatán': { codigo: 'MX-YUC', id: 31, clase: 'mx-state-disabled' },
    'Zacatecas': { codigo: 'MX-ZAC', id: 32, clase: 'mx-state-disabled' }
};

// Función para aplicar los datos a los paths del SVG
function enhanceSVGWithStateData() {
    console.log('🔧 Mejorando SVG con datos de estados...');
    
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('❌ No se encontró SVG');
        return;
    }
    
    const paths = svg.querySelectorAll('path');
    console.log(`📄 Procesando ${paths.length} paths...`);
    
    let enhancedCount = 0;
    
    paths.forEach((path, index) => {
        // Verificar si ya tiene estado asignado (automático o manual)
        const existingState = path.getAttribute('data-state') || 
                              window.manualMappings?.[index] || 
                              window.detectedStates && Object.entries(window.detectedStates).find(([name, data]) => data.pathIndex === index)?.[0];
        
        if (existingState && estadosMexicanosDatos[existingState]) {
            const datos = estadosMexicanosDatos[existingState];
            
            // Añadir atributos del proyecto
            path.setAttribute('class', datos.clase);
            path.setAttribute('id', datos.codigo);
            path.setAttribute('data-entidad-id', datos.id);
            path.setAttribute('data-entidad-nombre', existingState.toUpperCase());
            
            // Conservar atributos existentes
            if (!path.getAttribute('data-state')) {
                path.setAttribute('data-state', existingState);
            }
            
            // Añadir estilos compatibles con el proyecto
            if (!path.getAttribute('fill')) {
                path.setAttribute('fill', '#CCCCCC');
            }
            if (!path.getAttribute('stroke')) {
                path.setAttribute('stroke', '#262A27');
            }
            if (!path.getAttribute('stroke-width')) {
                path.setAttribute('stroke-width', '0.5');
            }
            
            enhancedCount++;
            console.log(`✅ Mejorado path ${index + 1}: ${existingState} (${datos.codigo})`);
        }
    });
    
    console.log(`🎯 Resultado: ${enhancedCount} paths mejorados de ${paths.length} totales`);
    return enhancedCount;
}

// Función para exportar el SVG mejorado
function exportEnhancedSVG() {
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('❌ No se encontró SVG para exportar');
        return;
    }
    
    // Clonar el SVG para no modificar el original
    const clonedSVG = svg.cloneNode(true);
    
    // Añadir estilos CSS del proyecto original
    const style = document.createElement('style');
    style.textContent = `
        .mx-state {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .mx-state:hover {
            fill: #999999 !important;
            stroke: #000000 !important;
            stroke-width: 1 !important;
        }
        .mx-state.is-blocked {
            fill: #ff6b6b !important;
        }
        .mx-state-disabled {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .mx-state-disabled:hover {
            fill: #999999 !important;
            stroke: #000000 !important;
            stroke-width: 1 !important;
        }
    `;
    
    // Insertar estilos al principio del SVG
    const firstChild = clonedSVG.firstChild;
    clonedSVG.insertBefore(style, firstChild);
    
    // Crear el contenido del archivo
    const svgContent = new XMLSerializer().serializeToString(clonedSVG);
    const formattedContent = '<?xml version="1.0" encoding="utf-8"?>\n' + svgContent;
    
    // Descargar el archivo
    const blob = new Blob([formattedContent], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'img-map-enhanced.svg';
    link.click();
    URL.revokeObjectURL(url);
    
    console.log('💾 SVG mejorado exportado como img-map-enhanced.svg');
}

// Función para generar reporte de compatibilidad
function generateCompatibilityReport() {
    const validation = window.validateAllStatesAssigned ? window.validateAllStatesAssigned() : null;
    
    if (!validation) {
        console.warn('⚠️ Sistema de validación no disponible');
        return;
    }
    
    const report = {
        fecha: new Date().toLocaleString(),
        resumen: {
            estadosAsignados: validation.assignedCount,
            estadosConDatos: 0,
            compatibilidadProyecto: '0%'
        },
        estadosCompatibles: [],
        estadosIncompletos: [],
        recomendaciones: []
    };
    
    // Verificar compatibilidad de cada estado asignado
    validation.assignedStates.forEach(estado => {
        if (estadosMexicanosDatos[estado]) {
            report.estadosCompatibles.push({
                nombre: estado,
                codigo: estadosMexicanosDatos[estado].codigo,
                id: estadosMexicanosDatos[estado].id
            });
            report.resumen.estadosConDatos++;
        } else {
            report.estadosIncompletos.push(estado);
        }
    });
    
    report.resumen.compatibilidadProyecto = 
        ((report.resumen.estadosConDatos / 32) * 100).toFixed(1) + '%';
    
    // Generar recomendaciones
    if (report.estadosIncompletos.length > 0) {
        report.recomendaciones.push(
            `Completar mapeo manual para ${report.estadosIncompletos.length} estados faltantes`
        );
    }
    
    if (validation.unassignedCount > 0) {
        report.recomendaciones.push(
            `Asignar ${validation.unassignedCount} estados no detectados automáticamente`
        );
    }
    
    if (report.resumen.compatibilidadProyecto === '100.0%') {
        report.recomendaciones.push('¡SVG completamente compatible con el proyecto!');
    }
    
    console.log('📊 REPORTE DE COMPATIBILIDAD:', report);
    return report;
}

// Exportar funciones para uso global
if (typeof window !== 'undefined') {
    window.enhanceSVGWithStateData = enhanceSVGWithStateData;
    window.exportEnhancedSVG = exportEnhancedSVG;
    window.generateCompatibilityReport = generateCompatibilityReport;
    window.estadosMexicanosDatos = estadosMexicanosDatos;
}
