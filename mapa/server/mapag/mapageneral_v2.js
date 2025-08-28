/**
 * MAPA GENERAL V2 - Enhanced Version
 * Versión mejorada del mapa general que utiliza img-map-enhanced.svg
 * con soporte para estados con datos de entidad completos
 */

(function() {
    'use strict';
    
    // Variables globales para V2
    let estadosDataV2 = {};
    let currentSelectedStateV2 = null;
    let mapInteractionsV2 = {
        isEnabled: true,
        zoomSensitivity: 0.1,
        maxZoom: 3,
        minZoom: 0.5
    };
    
    console.log('🚀 Inicializando Mapa General V2 Enhanced...');
    
    // Configuración específica para V2
    const configV2 = {
        colors: {
            concluido: '#4caf50',
            sinEjecutar: '#bdbdbd', 
            otro: '#f44336',
            hover: '#2196f3',
            selected: '#ff6b6b'
        },
        animations: {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        enhanced: {
            supportDataAttributes: true,
            preferEnhancedSVG: true,
            fallbackToOriginal: true
        }
    };
    
    /**
     * Inicialización principal del mapa V2
     */
    function initMapaGeneralV2() {
        console.log('🔧 Configurando Mapa General V2...');
        
        // Verificar que tengamos el contenedor correcto
        const mapaContainer = document.getElementById('mapa-v2');
        const infoContainer = document.getElementById('info-v2');
        
        if (!mapaContainer || !infoContainer) {
            console.error('❌ Contenedores V2 no encontrados');
            return;
        }
        
        // Esperar a que el SVG se cargue
        setTimeout(() => {
            const svg = mapaContainer.querySelector('svg');
            if (svg) {
                setupSVGV2(svg);
                detectSVGTypeV2(svg);
                setupEventListenersV2(svg);
                loadDataV2();
                updateUIStatsV2();
                console.log('✅ Mapa General V2 inicializado correctamente');
            } else {
                console.error('❌ SVG no encontrado en el contenedor V2');
            }
        }, 500);
    }
    
    /**
     * Configurar el SVG para V2
     */
    function setupSVGV2(svg) {
        console.log('🎨 Configurando SVG para V2...');
        
        // Marcar como inicializado para evitar doble inicialización
        svg.setAttribute('data-v2-initialized', 'true');
        
        // Añadir clases CSS para identificación
        svg.classList.add('mapa-v2-svg');
        
        // Configurar paths
        const paths = svg.querySelectorAll('path');
        paths.forEach((path, index) => {
            // Añadir data-index para referencia
            path.setAttribute('data-path-index', index);
            
            // Configurar eventos básicos
            path.style.cursor = 'pointer';
            path.style.transition = `all ${configV2.animations.duration}ms ${configV2.animations.easing}`;
            
            // Event listeners
            path.addEventListener('mouseenter', (e) => handlePathHoverV2(e, true));
            path.addEventListener('mouseleave', (e) => handlePathHoverV2(e, false));
            path.addEventListener('click', (e) => handlePathClickV2(e));
        });
        
        console.log(`🎯 ${paths.length} paths configurados para V2`);
    }
    
    /**
     * Detectar tipo de SVG (enhanced, standard, basic)
     */
    function detectSVGTypeV2(svg) {
        const pathsWithDataEntidad = svg.querySelectorAll('path[data-entidad-id]');
        const pathsWithIds = svg.querySelectorAll('path[id^="MX-"]');
        const pathsWithClasses = svg.querySelectorAll('path[class]');
        
        let svgType = 'basic';
        let features = [];
        
        if (pathsWithDataEntidad.length > 0) {
            svgType = 'enhanced';
            features.push(`${pathsWithDataEntidad.length} estados con data-entidad-id`);
        }
        
        if (pathsWithIds.length > 0) {
            if (svgType === 'basic') svgType = 'standard';
            features.push(`${pathsWithIds.length} estados con IDs`);
        }
        
        if (pathsWithClasses.length > 0) {
            features.push(`${pathsWithClasses.length} estados con clases`);
        }
        
        console.log(`📊 Tipo de SVG detectado: ${svgType.toUpperCase()}`);
        console.log(`✨ Características: ${features.join(', ')}`);
        
        // Actualizar UI con el tipo detectado
        const versionElement = document.getElementById('versionTypeV2');
        if (versionElement) {
            versionElement.textContent = svgType.charAt(0).toUpperCase() + svgType.slice(1);
        }
        
        // Configurar comportamiento basado en el tipo
        configV2.enhanced.isEnhanced = svgType === 'enhanced';
        configV2.enhanced.hasIds = pathsWithIds.length > 0;
        configV2.enhanced.hasClasses = pathsWithClasses.length > 0;
    }
    
    /**
     * Configurar event listeners específicos para V2
     */
    function setupEventListenersV2(svg) {
        console.log('🎮 Configurando event listeners V2...');
        
        // Event listeners ya configurados en los paths en setupSVGV2
        
        // Configurar zonas específicas si es SVG enhanced
        if (configV2.enhanced.isEnhanced) {
            const pathsWithData = svg.querySelectorAll('path[data-entidad-id]');
            pathsWithData.forEach(path => {
                const entidadId = path.getAttribute('data-entidad-id');
                const entidadNombre = path.getAttribute('data-entidad-nombre');
                
                if (entidadId && entidadNombre) {
                    // Guardar datos del estado
                    estadosDataV2[entidadId] = {
                        id: entidadId,
                        nombre: entidadNombre,
                        element: path,
                        pathIndex: path.getAttribute('data-path-index')
                    };
                }
            });
            
            console.log(`📦 ${Object.keys(estadosDataV2).length} estados con datos cargados`);
        }
    }
    
    /**
     * Manejar hover en paths
     */
    function handlePathHoverV2(event, isEntering) {
        const path = event.target;
        
        if (isEntering) {
            // Aplicar efecto hover
            path.style.stroke = configV2.colors.hover;
            path.style.strokeWidth = '2px';
            path.style.filter = 'brightness(1.1) saturate(1.2)';
            
            // Mostrar información si está disponible
            showTooltipV2(event, path);
        } else {
            // Remover efecto hover (solo si no está seleccionado)
            if (currentSelectedStateV2 !== path) {
                path.style.stroke = '';
                path.style.strokeWidth = '';
                path.style.filter = '';
            }
            
            hideTooltipV2();
        }
    }
    
    /**
     * Manejar click en paths
     */
    function handlePathClickV2(event) {
        const path = event.target;
        
        // Deseleccionar estado anterior
        if (currentSelectedStateV2 && currentSelectedStateV2 !== path) {
            currentSelectedStateV2.classList.remove('estado-seleccionado-v2');
            currentSelectedStateV2.style.stroke = '';
            currentSelectedStateV2.style.strokeWidth = '';
        }
        
        // Seleccionar nuevo estado
        currentSelectedStateV2 = path;
        path.classList.add('estado-seleccionado-v2');
        
        // Cargar información del estado
        loadStateInfoV2(path);
        
        console.log('🎯 Estado seleccionado:', path.getAttribute('data-entidad-nombre') || path.id || 'Sin identificar');
    }
    
    /**
     * Mostrar tooltip mejorado
     */
    function showTooltipV2(event, path) {
        const entidadNombre = path.getAttribute('data-entidad-nombre');
        const entidadId = path.getAttribute('data-entidad-id');
        const stateId = path.id;
        
        let tooltipText = 'Estado no identificado';
        
        if (entidadNombre) {
            tooltipText = entidadNombre;
            if (entidadId) tooltipText += ` (ID: ${entidadId})`;
        } else if (stateId) {
            tooltipText = stateId;
        }
        
        // Crear o actualizar tooltip
        let tooltip = document.getElementById('tooltip-v2');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'tooltip-v2';
            tooltip.style.cssText = `
                position: fixed;
                background: linear-gradient(135deg, #1976d2, #42a5f5);
                color: white;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 0.8rem;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s ease;
                backdrop-filter: blur(10px);
            `;
            document.body.appendChild(tooltip);
        }
        
        tooltip.textContent = tooltipText;
        tooltip.style.left = (event.clientX + 10) + 'px';
        tooltip.style.top = (event.clientY - 30) + 'px';
        tooltip.style.opacity = '1';
    }
    
    /**
     * Ocultar tooltip
     */
    function hideTooltipV2() {
        const tooltip = document.getElementById('tooltip-v2');
        if (tooltip) {
            tooltip.style.opacity = '0';
        }
    }
    
    /**
     * Cargar información del estado seleccionado
     */
    function loadStateInfoV2(path) {
        const infoContainer = document.getElementById('detalle-v2');
        const titleElement = document.getElementById('estadoNombreV2');
        
        if (!infoContainer || !titleElement) return;
        
        // Obtener datos del estado
        const entidadId = path.getAttribute('data-entidad-id');
        const entidadNombre = path.getAttribute('data-entidad-nombre');
        const stateId = path.id;
        const pathIndex = path.getAttribute('data-path-index');
        
        // Actualizar título
        let title = '🗺️ Información del Estado';
        if (entidadNombre) {
            title = `🏛️ ${entidadNombre}`;
        } else if (stateId) {
            title = `📍 ${stateId}`;
        }
        titleElement.textContent = title;
        
        // Crear información detallada
        let infoHTML = '';
        
        if (configV2.enhanced.isEnhanced && entidadId) {
            // Información completa para SVG enhanced
            infoHTML = `
                <div class="card-estado">
                    <div class="estado-header" style="display: flex; align-items: center; gap: 10px;">
                        <div class="estado-icon">🏛️</div>
                        <div class="estado-info">
                            <h3>${entidadNombre || 'Estado sin nombre'}</h3>
                            <h5>ID: ${entidadId}</h5>
                        </div>
                    </div>
                    <div class="estado-kv">
                        <strong>Información Técnica:</strong><br>
                        • ID de Entidad: ${entidadId}<br>
                        • Nombre: ${entidadNombre || 'No disponible'}<br>
                        • Índice de Path: ${pathIndex || 'No disponible'}<br>
                        • Tipo de SVG: Enhanced<br>
                        • Estado: Datos completos disponibles
                    </div>
                </div>
            `;
        } else if (stateId) {
            // Información básica para SVG standard
            infoHTML = `
                <div class="card-estado">
                    <div class="estado-header" style="display: flex; align-items: center; gap: 10px;">
                        <div class="estado-icon">📍</div>
                        <div class="estado-info">
                            <h3>${stateId}</h3>
                            <h5>Estado identificado</h5>
                        </div>
                    </div>
                    <div class="estado-kv">
                        <strong>Información Básica:</strong><br>
                        • ID: ${stateId}<br>
                        • Índice de Path: ${pathIndex || 'No disponible'}<br>
                        • Tipo de SVG: Standard<br>
                        • Estado: Identificación básica
                    </div>
                </div>
            `;
        } else {
            // Información mínima para SVG basic
            infoHTML = `
                <div class="card-estado">
                    <div class="estado-header" style="display: flex; align-items: center; gap: 10px;">
                        <div class="estado-icon">❓</div>
                        <div class="estado-info">
                            <h3>Estado no identificado</h3>
                            <h5>Path #${(parseInt(pathIndex) + 1) || 'Desconocido'}</h5>
                        </div>
                    </div>
                    <div class="estado-kv">
                        <strong>Información Limitada:</strong><br>
                        • Índice de Path: ${pathIndex || 'No disponible'}<br>
                        • Tipo de SVG: Basic<br>
                        • Estado: Sin datos de identificación<br>
                        • Sugerencia: Usar SVG enhanced para más información
                    </div>
                </div>
            `;
        }
        
        infoContainer.innerHTML = infoHTML;
        infoContainer.setAttribute('data-estado', entidadId || stateId || pathIndex || '');
        
        // Cargar datos adicionales si están disponibles
        if (entidadId) {
            loadAdditionalStateDataV2(entidadId);
        }
    }
    
    /**
     * Cargar datos adicionales del estado (de la base de datos)
     */
    function loadAdditionalStateDataV2(entidadId) {
        console.log(`📊 Cargando datos adicionales para entidad ${entidadId}...`);
        
        // Aquí se puede hacer una llamada AJAX para obtener más datos
        // Por ahora solo mostramos un placeholder
        
        const infoContainer = document.getElementById('detalle-v2');
        if (!infoContainer) return;
        
        // Añadir sección de datos adicionales
        const additionalInfo = document.createElement('div');
        additionalInfo.className = 'additional-info-v2';
        additionalInfo.innerHTML = `
            <div style="margin-top: 15px; padding: 10px; background: rgba(33, 150, 243, 0.1); border-radius: 8px; border-left: 3px solid #2196f3;">
                <h4 style="margin: 0 0 8px 0; color: #1976d2; font-size: 0.9rem;">📈 Datos del Sistema</h4>
                <div style="font-size: 0.75rem; color: #666;">
                    <div style="margin-bottom: 4px;">🔄 Cargando información desde la base de datos...</div>
                    <div style="margin-bottom: 4px;">🎯 Entidad ID: ${entidadId}</div>
                    <div>⏰ ${new Date().toLocaleString()}</div>
                </div>
            </div>
        `;
        
        infoContainer.appendChild(additionalInfo);
        
        // Simular carga de datos
        setTimeout(() => {
            additionalInfo.innerHTML = `
                <div style="margin-top: 15px; padding: 10px; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border-left: 3px solid #4caf50;">
                    <h4 style="margin: 0 0 8px 0; color: #388e3c; font-size: 0.9rem;">✅ Datos Cargados</h4>
                    <div style="font-size: 0.75rem; color: #666;">
                        <div style="margin-bottom: 4px;">📊 Registros encontrados: Disponibles</div>
                        <div style="margin-bottom: 4px;">🎯 Entidad ID: ${entidadId}</div>
                        <div style="margin-bottom: 4px;">📅 Última actualización: ${new Date().toLocaleDateString()}</div>
                        <div>🔗 Conexión: Establecida</div>
                    </div>
                </div>
            `;
        }, 1500);
    }
    
    /**
     * Cargar datos generales del mapa
     */
    function loadDataV2() {
        console.log('📡 Cargando datos para Mapa General V2...');
        
        // Aquí iría la lógica para cargar datos de la base de datos
        // Por ahora solo simulamos la carga
        
        updateUIStatsV2();
    }
    
    /**
     * Actualizar estadísticas en la UI
     */
    function updateUIStatsV2() {
        const svg = document.querySelector('#mapa-v2 svg');
        if (!svg) return;
        
        const totalPaths = svg.querySelectorAll('path').length;
        const pathsWithData = svg.querySelectorAll('path[data-entidad-id]').length;
        const pathsWithIds = svg.querySelectorAll('path[id]').length;
        
        // Actualizar contadores
        const elements = {
            loadedStatesV2: totalPaths,
            activeStatesV2: pathsWithData || pathsWithIds,
            totalStatesV2: 32
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
        
        console.log(`📊 Stats actualizadas: ${totalPaths} paths, ${pathsWithData} enhanced, ${pathsWithIds} con IDs`);
    }
    
    /**
     * Función de depuración para V2
     */
    function debugInfoV2() {
        console.log('🐛 === DEBUG INFO V2 ===');
        console.log('Configuración:', configV2);
        console.log('Estados con datos:', Object.keys(estadosDataV2).length);
        console.log('Estado seleccionado actual:', currentSelectedStateV2?.getAttribute('data-entidad-nombre') || 'Ninguno');
        console.log('Interacciones habilitadas:', mapInteractionsV2.isEnabled);
        
        const svg = document.querySelector('#mapa-v2 svg');
        if (svg) {
            console.log('SVG encontrado:', true);
            console.log('Total paths:', svg.querySelectorAll('path').length);
            console.log('Paths con data-entidad-id:', svg.querySelectorAll('path[data-entidad-id]').length);
            console.log('Paths con ID:', svg.querySelectorAll('path[id]').length);
        } else {
            console.log('SVG encontrado:', false);
        }
    }
    
    // Exponer funciones globales para debugging
    window.debugInfoV2 = debugInfoV2;
    // Exponer función de inicialización globalmente para el sistema de carga de secciones
    window.initMapaGeneralV2 = initMapaGeneralV2;
    
    window.mapV2 = {
        config: configV2,
        estados: estadosDataV2,
        interactions: mapInteractionsV2,
        updateStats: updateUIStatsV2,
        loadStateInfo: loadStateInfoV2
    };
    
    // Inicialización automática
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMapaGeneralV2);
    } else {
        // DOM ya está listo
        setTimeout(initMapaGeneralV2, 100);
    }
    
    // Forzar inicialización si no se ejecuta en 2 segundos
    setTimeout(() => {
        const svg = document.querySelector('#mapa-v2 svg');
        if (!svg) {
            console.warn('⚠️ SVG no detectado después de 2 segundos, forzando inicialización...');
            initMapaGeneralV2();
        }
    }, 2000);
    
    console.log('✅ Mapa General V2 script cargado. Usa debugInfoV2() para información de depuración.');
    
})();
