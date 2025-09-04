// pagination_usuarios.js - Sistema de paginación avanzado para múltiples tablas de usuarios
console.log('[pagination_usuarios.js] Cargando sistema avanzado...');

// Protección contra carga múltiple
if (window.paginationUsuariosLoaded) {
  console.warn('[pagination_usuarios.js] ⚠️ Ya está cargado, saltando inicialización');
  // Solo exportar las funciones públicas
  if (!window.paginationUsuarios) {
    window.paginationUsuarios = {
      goToPage: window._paginationGoToPage,
      goToFirstPage: window._paginationGoToFirstPage,
      goToPrevPage: window._paginationGoToPrevPage,
      goToNextPage: window._paginationGoToNextPage,
      goToLastPage: window._paginationGoToLastPage,
      toggleAutoSize: window._paginationToggleAutoSize,
      changeRowsPerPage: window._paginationChangeRowsPerPage,
      reinicializarTabla: window._paginationReinicializarTabla,
      setVisibleRows: window._paginationSetVisibleRows,
      state: window._paginationState
    };
  }
} else {
  window.paginationUsuariosLoaded = true;

// Estado de paginación para cada tabla (protegido contra redeclaración)
let paginationState;
if (!window._paginationState) {
  paginationState = {
    personas: {
      currentPage: 1,
      rowsPerPage: 10,
      totalPages: 1,
      totalRegistros: 0,
      autoSize: true
    },
    usuarios: {
      currentPage: 1,
      rowsPerPage: 10,
      totalPages: 1,
      totalRegistros: 0,
      autoSize: true
    },
    permisos: {
      currentPage: 1,
      rowsPerPage: 10,
      totalPages: 1,
      totalRegistros: 0,
      autoSize: true,
      filtros: {
        usuario: '',
        modulo: '',
        entidad: '',
        bus: ''
      }
    },
    lotes: {
      currentPage: 1,
      rowsPerPage: 10,
      totalPages: 1,
      totalRegistros: 0,
      autoSize: true,
      filtros: {
        usuario: '',
        modulo: '',
        entidad: '',
        bus: ''
      }
    },
    permisosMapas: {
      currentPage: 1,
      rowsPerPage: 10,
      totalPages: 1,
      totalRegistros: 0,
      autoSize: true,
      filtros: {
        usuario: '',
        modulo: '',
        entidad: '',
        bus: ''
      }
    }
  };
  window._paginationState = paginationState;
} else {
  paginationState = window._paginationState;
}

// Variable de timeout para resize (protegida)
let resizeTimeout = window._paginationResizeTimeout || null;

// Función para calcular filas visibles automáticamente
function setVisibleRows(tabla = null) {
  const rowHeight = 48;
  const headerOffset = 320; // Offset aumentado para headers, tabs y otros elementos
  
  const availableHeight = window.innerHeight - headerOffset;
  const visibleRows = Math.floor(availableHeight / rowHeight) - 1;
  const newRowsPerPage = Math.max(5, Math.min(50, visibleRows)); // Entre 5 y 50 filas
  
  console.log(`📏 Cálculo automático: altura disponible=${availableHeight}px, filas visibles=${newRowsPerPage}`);
  
  if (tabla) {
    // Solo actualizar una tabla específica
    if (paginationState[tabla] && paginationState[tabla].autoSize) {
      const changed = paginationState[tabla].rowsPerPage !== newRowsPerPage;
      paginationState[tabla].rowsPerPage = newRowsPerPage;
      paginationState[tabla].currentPage = 1;
      if (changed) recargarTablaActiva(tabla);
    }
  } else {
    // Actualizar todas las tablas en modo automático
    Object.keys(paginationState).forEach(t => {
      if (paginationState[t].autoSize) {
        const changed = paginationState[t].rowsPerPage !== newRowsPerPage;
        paginationState[t].rowsPerPage = newRowsPerPage;
        paginationState[t].currentPage = 1;
        // Solo recargar si hay cambio y es la tabla activa
        if (changed && getActiveTab() === t) {
          recargarTablaActiva(t);
        }
      }
    });
  }
}

// Debounce para el resize
function debounceResize() {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => {
    const activeTab = getActiveTab();
    if (activeTab && paginationState[activeTab]) {
      setVisibleRows(activeTab);
    }
  }, 300);
  window._paginationResizeTimeout = resizeTimeout;
}

// Obtener la pestaña activa
function getActiveTab() {
  const activeTabElement = document.querySelector('.nav-tabs .nav-link.active');
  if (!activeTabElement) return null;
  
  const tabId = activeTabElement.id;
  if (tabId === 'tab-personas') return 'personas';
  if (tabId === 'tab-usuarios') return 'usuarios';
  if (tabId === 'tab-permisos') return 'permisos';
  if (tabId === 'tab-permisos-mapas') return 'permisosMapas';
  if (tabId === 'tab-lotes') return 'lotes';
  
  return null;
}

// Determinar la ruta base de la API (con protección contra redeclaración)
if (typeof window.paginationApiBase === 'undefined') {
  const scriptPath = document.currentScript?.src || '';
  window.paginationApiBase = scriptPath.includes('/sections/usuarios/') 
    ? '/final/mapa/public/sections/usuarios/api/'
    : 'api/';
}
const apiBase = window.paginationApiBase;

// Función para recargar tabla activa
function recargarTablaActiva(tabla) {
  console.log(`📄 Recargando tabla: ${tabla}`);
  
  switch(tabla) {
    case 'personas':
      if (window.cargarPersonasPaginado) window.cargarPersonasPaginado();
      break;
    case 'usuarios':
      if (window.cargarUsuariosPaginado) window.cargarUsuariosPaginado();
      break;
    case 'permisos':
      if (window.cargarPermisosPaginado) window.cargarPermisosPaginado();
      break;
    case 'lotes':
      if (window.cargarLotesPaginado) window.cargarLotesPaginado();
      break;
    case 'permisosMapas':
      if (window.cargarPermisosMapasPaginado) window.cargarPermisosMapasPaginado();
      break;
  }
}

// Función para cambiar el modo de paginación
function toggleAutoSize(tabla, enable) {
  if (!paginationState[tabla]) {
    console.error(`❌ Tabla ${tabla} no encontrada`);
    return;
  }
  
  paginationState[tabla].autoSize = enable;
  console.log(`🔧 Modo automático ${enable ? 'activado' : 'desactivado'} para tabla ${tabla}`);
  
  if (enable) {
    setVisibleRows(tabla);
  }
  
  // Actualizar el selector de filas
  updateRowsPerPageSelect(tabla);
}

// Función para cambiar filas por página manualmente
function changeRowsPerPage(tabla, newRows) {
  if (!paginationState[tabla]) return;
  
  console.log(`📊 Cambiando filas por página para ${tabla}: ${newRows}`);
  paginationState[tabla].rowsPerPage = parseInt(newRows);
  paginationState[tabla].currentPage = 1;
  paginationState[tabla].autoSize = false; // Desactivar modo automático
  
  updateRowsPerPageSelect(tabla);
  recargarTablaActiva(tabla);
}

// Función para actualizar el selector de filas por página
function updateRowsPerPageSelect(tabla) {
  const suffix = getSuffix(tabla);
  const rowsSelect = document.getElementById(`rowsPerPage${suffix}`);
  const autoToggle = document.getElementById(`autoSize${suffix}`);
  
  if (rowsSelect) {
    rowsSelect.disabled = paginationState[tabla].autoSize;
    if (!paginationState[tabla].autoSize) {
      rowsSelect.value = paginationState[tabla].rowsPerPage;
    }
  }
  
  if (autoToggle) {
    autoToggle.checked = paginationState[tabla].autoSize;
  }
}

// Helper para obtener sufijo de tabla
function getSuffix(tabla) {
  const suffixMap = {
    'personas': 'Personas',
    'usuarios': 'Usuarios', 
    'permisos': 'Permisos',
    'permisosMapas': 'PermisosMapas',
    'lotes': 'Lotes'
  };
  return suffixMap[tabla] || '';
}

// Helper para obtener ID del tbody
function getTbodyId(tabla) {
  const tbodyMap = {
    'personas': 'tbPersonas',
    'usuarios': 'tbUsuarios',
    'permisos': 'tbPermisos', 
    'permisosMapas': 'tbPermisosMapas',
    'lotes': 'tbLotes'
  };
  return tbodyMap[tabla] || '';
}

// Helper para actualizar información de página
function updatePageInfo(tabla) {
  const state = paginationState[tabla];
  const suffix = getSuffix(tabla);
  
  const pageInfoEl = document.getElementById(`pageInfo${suffix}`);
  const totalInfoEl = document.getElementById(`totalInfo${suffix}`);
  
  if (pageInfoEl) {
    pageInfoEl.textContent = `Página ${state.currentPage} de ${state.totalPages || 1}`;
  }

  if (totalInfoEl) {
    const start = (state.currentPage - 1) * state.rowsPerPage + 1;
    const end = Math.min(start + state.rowsPerPage - 1, state.totalRegistros);
    totalInfoEl.textContent = state.totalRegistros > 0 
      ? `Mostrando ${start}–${end} de ${state.totalRegistros}`
      : "Sin resultados";
  }

  // Actualizar estado de botones de navegación
  updateNavigationButtons(tabla);
  updateRowsPerPageSelect(tabla);
}

// Actualizar estado de botones de navegación
function updateNavigationButtons(tabla) {
  const state = paginationState[tabla];
  const suffix = getSuffix(tabla);
  
  const btnFirst = document.getElementById(`btnFirst${suffix}`);
  const btnPrev = document.getElementById(`btnPrev${suffix}`);
  const btnNext = document.getElementById(`btnNext${suffix}`);
  const btnLast = document.getElementById(`btnLast${suffix}`);
  
  if (btnFirst) btnFirst.disabled = state.currentPage <= 1;
  if (btnPrev) btnPrev.disabled = state.currentPage <= 1;
  if (btnNext) btnNext.disabled = state.currentPage >= state.totalPages;
  if (btnLast) btnLast.disabled = state.currentPage >= state.totalPages;
}

// Navegar a página específica
function goToPage(tabla, page) {
  const state = paginationState[tabla];
  if (page < 1 || page > state.totalPages) return;
  
  console.log(`📄 Navegando a página ${page} de tabla ${tabla}`);
  state.currentPage = page;
  recargarTablaActiva(tabla);
}

function goToFirstPage(tabla) {
  console.log(`⏮️ Primera página de tabla ${tabla}`);
  goToPage(tabla, 1);
}

function goToPrevPage(tabla) {
  const currentPage = paginationState[tabla]?.currentPage || 1;
  console.log(`⏪ Página anterior de tabla ${tabla}: ${currentPage - 1}`);
  goToPage(tabla, currentPage - 1);
}

function goToNextPage(tabla) {
  const currentPage = paginationState[tabla]?.currentPage || 1;
  console.log(`⏩ Página siguiente de tabla ${tabla}: ${currentPage + 1}`);
  goToPage(tabla, currentPage + 1);
}

function goToLastPage(tabla) {
  const totalPages = paginationState[tabla]?.totalPages || 1;
  console.log(`⏭️ Última página de tabla ${tabla}: ${totalPages}`);
  goToPage(tabla, totalPages);
}

// Función genérica para cargar datos con paginación
async function cargarDatosPaginados(tabla, endpoint, params = {}) {
  console.log(`🌐 [cargarDatosPaginados] Cargando tabla: ${tabla}, endpoint: ${endpoint}`);
  const state = paginationState[tabla];
  const tbodyId = getTbodyId(tabla); // Usar la nueva función
  const tbody = document.getElementById(tbodyId);
  
  console.log(`📋 Buscando tbody: ${tbodyId}`, tbody ? '✅ Encontrado' : '❌ No encontrado');
  
  if (!tbody) {
    console.warn(`⚠️ Tabla ${tbodyId} no encontrada`);
    return;
  }

  try {
    // Mostrar loading
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Cargando...
    </td></tr>`;
    console.log('🔄 Loading mostrado en tabla');

    // Preparar parámetros
    const queryParams = new URLSearchParams({
      page: state.currentPage,
      rowsPerPage: state.rowsPerPage,
      ...params
    });

    // Agregar filtros específicos (solo para permisos y lotes)
    if (state.filtros) {
      Object.entries(state.filtros).forEach(([key, value]) => {
        if (value) queryParams.append(key, value);
      });
    }

    const url = `${apiBase}${endpoint}?${queryParams}`;
    console.log(`🌐 Haciendo petición a: ${url}`);

    const response = await fetch(url);
    console.log(`📡 Respuesta recibida: ${response.status} ${response.statusText}`);
    
    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    
    const data = await response.json();
    console.log(`📊 Datos recibidos:`, data);
    
    // Actualizar estado
    state.totalRegistros = data.total || 0;
    state.totalPages = data.totalPages || 1;
    
    // Renderizar datos
    tbody.innerHTML = data.html || '';
    console.log(`✅ HTML renderizado en tabla ${tbodyId}`);
    
    // Actualizar información de paginación
    updatePageInfo(tabla);
    
    console.log(`✅ ${tabla} cargado: ${state.totalRegistros} registros, página ${state.currentPage}/${state.totalPages}`);
    
  } catch (error) {
    console.error(`❌ Error cargando ${tabla}:`, error);
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error: ${error.message}</td></tr>`;
  }
}

// Funciones específicas para cada tabla
window.cargarPersonasPaginado = async function() {
  console.log('🔄 [cargarPersonasPaginado] Iniciando carga con paginación...');
  const state = paginationState.personas;
  const busqueda = document.getElementById('buscarPersona')?.value || '';
  console.log(`📋 Estado actual: página ${state.currentPage}, filas ${state.rowsPerPage}, búsqueda: "${busqueda}"`);
  await cargarDatosPaginados('personas', 'personas_listar_paginado.php', { busqueda });
};

window.cargarUsuariosPaginado = async function() {
  const state = paginationState.usuarios;
  const busqueda = document.getElementById('buscarUsuario')?.value || '';
  await cargarDatosPaginados('usuarios', 'usuarios_listar_paginado.php', { busqueda });
};

window.cargarPermisosPaginado = async function() {
  const state = paginationState.permisos;
  
  // Actualizar filtros
  state.filtros.usuario = document.getElementById('filtroUsuarioPerm')?.value || '';
  state.filtros.modulo = document.getElementById('filtroModuloPerm')?.value || '';
  state.filtros.entidad = document.getElementById('filtroEntidadPerm')?.value || '';
  state.filtros.bus = document.getElementById('filtroBusPerm')?.value || '';
  
  await cargarDatosPaginados('permisos', 'permisos_listar_paginado.php');
};

window.cargarLotesPaginado = async function() {
  const state = paginationState.lotes;
  
  // Actualizar filtros
  state.filtros.usuario = document.getElementById('filtroUsuarioLote')?.value || '';
  state.filtros.modulo = document.getElementById('filtroModuloLote')?.value || '';
  state.filtros.entidad = document.getElementById('filtroEntidadLote')?.value || '';
  state.filtros.bus = document.getElementById('filtroBusLote')?.value || '';
  const busqueda = document.getElementById('buscarLote')?.value || '';
  
  await cargarDatosPaginados('lotes', 'permisos_grupos_paginado.php', { busqueda });
};

window.cargarPermisosMapasPaginado = async function() {
  const state = paginationState.permisosMapas;
  
  // Actualizar filtros
  state.filtros.usuario = document.getElementById('filtroUsuarioPermMapas')?.value || '';
  state.filtros.modulo = document.getElementById('filtroModuloPermMapas')?.value || '';
  state.filtros.entidad = document.getElementById('filtroEntidadPermMapas')?.value || '';
  state.filtros.bus = document.getElementById('filtroBusPermMapas')?.value || '';
  
  await cargarDatosPaginados('permisosMapas', 'permisos_mapas_paginado.php');
};

// Función para configurar event listeners de paginación
function setupPaginationListeners() {
  console.log('🎛️ Configurando listeners de paginación...');
  
  Object.keys(paginationState).forEach(tabla => {
    const suffix = getSuffix(tabla);
    
    // Botones de navegación
    const setupButton = (id, action) => {
      const button = document.getElementById(id);
      if (button) {
        button.addEventListener('click', () => action(tabla));
        console.log(`✅ Listener configurado: ${id}`);
      }
    };
    
    setupButton(`btnFirst${suffix}`, goToFirstPage);
    setupButton(`btnPrev${suffix}`, goToPrevPage);
    setupButton(`btnNext${suffix}`, goToNextPage);
    setupButton(`btnLast${suffix}`, goToLastPage);
    
    // Selector de filas por página
    const rowsPerPageSelect = document.getElementById(`rowsPerPage${suffix}`);
    if (rowsPerPageSelect) {
      rowsPerPageSelect.addEventListener('change', function() {
        changeRowsPerPage(tabla, this.value);
      });
    }
    
    // Toggle de modo automático
    const autoSizeToggle = document.getElementById(`autoSize${suffix}`);
    if (autoSizeToggle) {
      autoSizeToggle.addEventListener('change', function() {
        toggleAutoSize(tabla, this.checked);
      });
    }
  });
}

// Función para configurar event listeners de búsqueda y filtros
function setupSearchListeners() {
  console.log('🔍 Configurando listeners de búsqueda...');
  
  // Búsquedas con debounce
  const setupSearch = (id, tabla) => {
    const input = document.getElementById(id);
    if (input) {
      input.addEventListener('input', () => {
        clearTimeout(input._debounce);
        input._debounce = setTimeout(() => {
          paginationState[tabla].currentPage = 1;
          recargarTablaActiva(tabla);
        }, 300);
      });
    }
  };
  
  setupSearch('buscarPersona', 'personas');
  setupSearch('buscarUsuario', 'usuarios');
  setupSearch('buscarLote', 'lotes');
  
  // Filtros de permisos
  ['filtroUsuarioPerm', 'filtroModuloPerm', 'filtroEntidadPerm', 'filtroBusPerm'].forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.addEventListener('change', () => {
        paginationState.permisos.currentPage = 1;
        recargarTablaActiva('permisos');
      });
    }
  });

  // Filtros de lotes
  ['filtroUsuarioLote', 'filtroModuloLote', 'filtroEntidadLote', 'filtroBusLote'].forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.addEventListener('change', () => {
        paginationState.lotes.currentPage = 1;
        recargarTablaActiva('lotes');
      });
    }
  });
  
  // Filtros de permisos de mapas
  ['filtroUsuarioPermMapas', 'filtroModuloPermMapas', 'filtroEntidadPermMapas', 'filtroBusPermMapas'].forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.addEventListener('change', () => {
        paginationState.permisosMapas.currentPage = 1;
        recargarTablaActiva('permisosMapas');
      });
    }
  });
}

// Función para configurar event listeners de tabs
function setupTabListeners() {
  console.log('📑 Configurando listeners de tabs...');
  
  document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function() {
      const activeTab = getActiveTab();
      console.log(`📑 Tab activo: ${activeTab}`);
      
      if (activeTab && paginationState[activeTab]) {
        // Si está en modo automático, recalcular filas visibles
        if (paginationState[activeTab].autoSize) {
          setVisibleRows(activeTab);
        } else {
          // Solo recargar la tabla
          recargarTablaActiva(activeTab);
        }
      }
    });
  });
}

// Función para reinicializar paginación cuando se cambia de tab
function reinicializarTabla(tabla) {
  console.log(`🔄 Reinicializando tabla: ${tabla}`);
  const state = paginationState[tabla];
  if (state) {
    state.currentPage = 1;
    recargarTablaActiva(tabla);
  }
}

// Función de inicialización principal
function inicializarPaginacionUsuarios() {
  console.log('� Inicializando sistema de paginación avanzado para usuarios...');
  
  // Calcular filas visibles al inicio
  setVisibleRows();
  
  // Configurar todos los event listeners
  setupPaginationListeners();
  setupSearchListeners();
  setupTabListeners();
  
  // Configurar listener de redimensionamiento
  window.addEventListener('resize', debounceResize);
  
  // Sobrescribir funciones originales para forzar uso de paginación
  if (window.cargarPersonas) {
    const originalCargarPersonas = window.cargarPersonas;
    window.cargarPersonas = function() {
      console.log('🔄 Interceptando cargarPersonas -> usando paginación');
      if (window.cargarPersonasPaginado) {
        return window.cargarPersonasPaginado();
      } else {
        return originalCargarPersonas();
      }
    };
  }
  
  if (window.cargarUsuarios) {
    const originalCargarUsuarios = window.cargarUsuarios;
    window.cargarUsuarios = function() {
      console.log('🔄 Interceptando cargarUsuarios -> usando paginación');
      if (window.cargarUsuariosPaginado) {
        return window.cargarUsuariosPaginado();
      } else {
        return originalCargarUsuarios();
      }
    };
  }
  
  if (window.cargarPermisos) {
    const originalCargarPermisos = window.cargarPermisos;
    window.cargarPermisos = function() {
      console.log('🔄 Interceptando cargarPermisos -> usando paginación');
      if (window.cargarPermisosPaginado) {
        return window.cargarPermisosPaginado();
      } else {
        return originalCargarPermisos();
      }
    };
  }
  
  if (window.cargarLotes) {
    const originalCargarLotes = window.cargarLotes;
    window.cargarLotes = function() {
      console.log('🔄 Interceptando cargarLotes -> usando paginación');
      if (window.cargarLotesPaginado) {
        return window.cargarLotesPaginado();
      } else {
        return originalCargarLotes();
      }
    };
  }
  
  if (window.cargarPermisosMapas) {
    const originalCargarPermisosMapas = window.cargarPermisosMapas;
    window.cargarPermisosMapas = function() {
      console.log('🔄 Interceptando cargarPermisosMapas -> usando paginación');
      if (window.cargarPermisosMapasPaginado) {
        return window.cargarPermisosMapasPaginado();
      } else {
        return originalCargarPermisosMapas();
      }
    };
  }
  
  // Cargar datos de la tabla activa por defecto
  setTimeout(() => {
    const activeTab = getActiveTab();
    if (activeTab && paginationState[activeTab]) {
      console.log(`📊 Cargando datos iniciales para tab: ${activeTab}`);
      recargarTablaActiva(activeTab);
    } else {
      // Si no hay tab activo, forzar carga de personas (primera pestaña)
      console.log('📊 Forzando carga inicial de personas con paginación');
      if (window.cargarPersonasPaginado) {
        window.cargarPersonasPaginado();
      }
    }
  }, 500); // Aumentar el timeout para asegurar que todo esté listo
  
  console.log('✅ Sistema de paginación avanzado inicializado');
}

// Exponer funciones globalmente para compatibilidad
window.paginationUsuarios = {
  goToPage,
  goToFirstPage,
  goToPrevPage,
  goToNextPage,
  goToLastPage,
  toggleAutoSize,
  changeRowsPerPage,
  reinicializarTabla,
  setVisibleRows,
  state: paginationState
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  console.log('📋 DOM listo, inicializando paginación...');
  
  // Esperar un poco más para asegurar que usuarios.js esté completamente cargado
  setTimeout(() => {
    inicializarPaginacionUsuarios();
    
    // También interceptar la función initUsuarios si existe
    if (window.initUsuarios) {
      const originalInitUsuarios = window.initUsuarios;
      window.initUsuarios = function() {
        console.log('🔄 Interceptando initUsuarios');
        return originalInitUsuarios().then(() => {
          console.log('✅ initUsuarios completado, forzando uso de paginación');
          // Forzar uso de funciones paginadas
          setTimeout(() => {
            if (window.cargarPersonasPaginado) {
              console.log('📊 Ejecutando cargarPersonasPaginado después de initUsuarios');
              window.cargarPersonasPaginado();
            }
          }, 100);
        });
      };
    }
  }, 100);
});

// Guardar referencias globales para protección contra recarga
window._paginationGoToPage = goToPage;
window._paginationGoToFirstPage = goToFirstPage;
window._paginationGoToPrevPage = goToPrevPage;
window._paginationGoToNextPage = goToNextPage;
window._paginationGoToLastPage = goToLastPage;
window._paginationToggleAutoSize = toggleAutoSize;
window._paginationChangeRowsPerPage = changeRowsPerPage;
window._paginationReinicializarTabla = reinicializarTabla;
window._paginationSetVisibleRows = setVisibleRows;
window._paginationState = paginationState;

console.log('[pagination_usuarios.js] ✅ Sistema avanzado cargado completamente');

// Función de prueba para diagnóstico
window.testCargarPersonas = async function() {
  console.log('🧪 [TEST] Probando carga directa de personas...');
  
  try {
    const tbody = document.getElementById('tbPersonas');
    console.log('📋 [TEST] tbody personas:', tbody);
    
    if (!tbody) {
      console.error('❌ [TEST] No se encontró tbPersonas');
      return;
    }
    
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">🔄 Probando carga...</td></tr>';
    
    const url = '/final/mapa/public/sections/usuarios/api/personas_listar_paginado.php?page=1&rowsPerPage=10';
    console.log('🌐 [TEST] URL:', url);
    
    const response = await fetch(url);
    console.log('📡 [TEST] Response:', response.status, response.statusText);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const text = await response.text();
    console.log('📄 [TEST] Response text:', text.substring(0, 200) + '...');
    
    // Verificar si es JSON válido
    let data;
    try {
      data = JSON.parse(text);
      console.log('📊 [TEST] Data:', data);
    } catch (parseError) {
      console.error('❌ [TEST] Error parseando JSON:', parseError);
      console.error('📄 [TEST] Respuesta completa:', text);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">❌ Error: Respuesta no es JSON válido</td></tr>`;
      return;
    }
    
    tbody.innerHTML = data.html || '<tr><td colspan="8" class="text-center">❌ Sin datos</td></tr>';
    console.log('✅ [TEST] HTML insertado exitosamente');
    
    return data;
    
  } catch (error) {
    console.error('❌ [TEST] Error:', error);
    const tbody = document.getElementById('tbPersonas');
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">❌ Error: ${error.message}</td></tr>`;
    }
  }
};

console.log('🧪 Función de diagnóstico disponible: window.testCargarPersonas()');

} // Cierre del bloque de protección contra carga múltiple
