// pagination_usuarios.js - Sistema de paginación para múltiples tablas de usuarios
console.log('[pagination_usuarios.js] Cargando...');

// Estado de paginación para cada tabla
const paginationState = {
  personas: {
    currentPage: 1,
    rowsPerPage: 10,
    totalPages: 1,
    totalRegistros: 0,
    busqueda: ''
  },
  usuarios: {
    currentPage: 1,
    rowsPerPage: 10,
    totalPages: 1,
    totalRegistros: 0,
    busqueda: ''
  },
  permisos: {
    currentPage: 1,
    rowsPerPage: 10,
    totalPages: 1,
    totalRegistros: 0,
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
    filtros: {
      usuario: '',
      modulo: '',
      entidad: '',
      bus: ''
    },
    busqueda: ''
  },
  modulos: {
    currentPage: 1,
    rowsPerPage: 10,
    totalPages: 1,
    totalRegistros: 0,
    busqueda: ''
  }
};

// Determinar la ruta base de la API
const scriptPath = document.currentScript?.src || '';
const apiBase = scriptPath.includes('/sections/usuarios/') 
  ? '/final/mapa/public/sections/usuarios/api/'
  : 'api/';

// Helper para actualizar información de página
function updatePageInfo(tabla) {
  const state = paginationState[tabla];
  const pageInfoEl = document.getElementById(`pageInfo${tabla.charAt(0).toUpperCase() + tabla.slice(1)}`);
  const rangeInfoEl = document.getElementById(`rangeInfo${tabla.charAt(0).toUpperCase() + tabla.slice(1)}`);
  
  if (pageInfoEl) {
    pageInfoEl.textContent = `Página ${state.currentPage} / ${state.totalPages || 1}`;
  }

  if (rangeInfoEl) {
    const start = (state.currentPage - 1) * state.rowsPerPage + 1;
    const end = Math.min(start + state.rowsPerPage - 1, state.totalRegistros);
    rangeInfoEl.textContent = state.totalRegistros > 0 
      ? `Mostrando ${start}–${end} de ${state.totalRegistros}`
      : "Mostrando 0–0 de 0";
  }

  // Actualizar estado de botones de navegación
  updateNavigationButtons(tabla);
}

// Actualizar estado de botones de navegación
function updateNavigationButtons(tabla) {
  const state = paginationState[tabla];
  const prefix = tabla.charAt(0).toUpperCase() + tabla.slice(1);
  
  const btnFirst = document.getElementById(`btnFirst${prefix}`);
  const btnPrev = document.getElementById(`btnPrev${prefix}`);
  const btnNext = document.getElementById(`btnNext${prefix}`);
  const btnLast = document.getElementById(`btnLast${prefix}`);
  
  if (btnFirst) btnFirst.disabled = state.currentPage <= 1;
  if (btnPrev) btnPrev.disabled = state.currentPage <= 1;
  if (btnNext) btnNext.disabled = state.currentPage >= state.totalPages;
  if (btnLast) btnLast.disabled = state.currentPage >= state.totalPages;
}

// Navegar a página específica
function goToPage(tabla, page) {
  const state = paginationState[tabla];
  if (page < 1 || page > state.totalPages) return;
  
  state.currentPage = page;
  
  // Llamar a la función de carga correspondiente
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
    case 'modulos':
      if (window.cargarModulosPaginado) window.cargarModulosPaginado();
      break;
  }
}

// Función genérica para cargar datos con paginación
async function cargarDatosPaginados(tabla, endpoint, params = {}) {
  const state = paginationState[tabla];
  const tbodyId = `tb${tabla.charAt(0).toUpperCase() + tabla.slice(1)}`;
  const tbody = document.getElementById(tbodyId);
  
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

    // Preparar parámetros
    const queryParams = new URLSearchParams({
      page: state.currentPage,
      rowsPerPage: state.rowsPerPage,
      ...params
    });

    // Agregar filtros específicos
    if (state.busqueda) queryParams.append('buscar', state.busqueda);
    if (state.filtros) {
      Object.entries(state.filtros).forEach(([key, value]) => {
        if (value) queryParams.append(key, value);
      });
    }

    const url = `${apiBase}${endpoint}?${queryParams}`;
    console.log(`🌐 Cargando ${tabla}:`, url);

    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    
    const data = await response.json();
    
    // Actualizar estado
    state.totalRegistros = data.total || 0;
    state.totalPages = data.totalPages || 1;
    
    // Renderizar datos
    tbody.innerHTML = data.html || '';
    
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
  const state = paginationState.personas;
  state.busqueda = document.getElementById('buscarPersona')?.value || '';
  await cargarDatosPaginados('personas', 'personas_listar_paginado.php');
};

window.cargarUsuariosPaginado = async function() {
  const state = paginationState.usuarios;
  state.busqueda = document.getElementById('buscarUsuario')?.value || '';
  await cargarDatosPaginados('usuarios', 'usuarios_listar_paginado.php');
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
  state.busqueda = document.getElementById('buscarLote')?.value || '';
  
  await cargarDatosPaginados('lotes', 'permisos_grupos_paginado.php');
};

window.cargarModulosPaginado = async function() {
  const state = paginationState.modulos;
  state.busqueda = document.getElementById('buscarModulo')?.value || '';
  await cargarDatosPaginados('modulos', 'modulos_listar_paginado.php');
};

// Asignar eventos de paginación para todas las tablas
function asignarEventosPaginacion() {
  const tablas = ['Personas', 'Usuarios', 'Permisos', 'Lotes', 'Modulos'];
  
  tablas.forEach(tabla => {
    const tablaLower = tabla.toLowerCase();
    
    const btnFirst = document.getElementById(`btnFirst${tabla}`);
    const btnPrev = document.getElementById(`btnPrev${tabla}`);
    const btnNext = document.getElementById(`btnNext${tabla}`);
    const btnLast = document.getElementById(`btnLast${tabla}`);
    
    if (btnFirst) btnFirst.addEventListener("click", () => goToPage(tablaLower, 1));
    if (btnPrev) btnPrev.addEventListener("click", () => goToPage(tablaLower, paginationState[tablaLower].currentPage - 1));
    if (btnNext) btnNext.addEventListener("click", () => goToPage(tablaLower, paginationState[tablaLower].currentPage + 1));
    if (btnLast) btnLast.addEventListener("click", () => goToPage(tablaLower, paginationState[tablaLower].totalPages));
  });
}

// Asignar eventos de búsqueda y filtros
function asignarEventosBusqueda() {
  // Búsquedas
  const buscarPersona = document.getElementById('buscarPersona');
  if (buscarPersona) {
    buscarPersona.addEventListener('input', () => {
      clearTimeout(window._debPersonas);
      window._debPersonas = setTimeout(() => {
        paginationState.personas.currentPage = 1;
        cargarPersonasPaginado();
      }, 300);
    });
  }

  const buscarUsuario = document.getElementById('buscarUsuario');
  if (buscarUsuario) {
    buscarUsuario.addEventListener('input', () => {
      clearTimeout(window._debUsuarios);
      window._debUsuarios = setTimeout(() => {
        paginationState.usuarios.currentPage = 1;
        cargarUsuariosPaginado();
      }, 300);
    });
  }

  const buscarModulo = document.getElementById('buscarModulo');
  if (buscarModulo) {
    buscarModulo.addEventListener('input', () => {
      clearTimeout(window._debModulos);
      window._debModulos = setTimeout(() => {
        paginationState.modulos.currentPage = 1;
        cargarModulosPaginado();
      }, 300);
    });
  }

  const buscarLote = document.getElementById('buscarLote');
  if (buscarLote) {
    buscarLote.addEventListener('input', () => {
      clearTimeout(window._debLotes);
      window._debLotes = setTimeout(() => {
        paginationState.lotes.currentPage = 1;
        cargarLotesPaginado();
      }, 300);
    });
  }

  // Filtros de permisos
  ['filtroUsuarioPerm', 'filtroModuloPerm', 'filtroEntidadPerm', 'filtroBusPerm'].forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.addEventListener('change', () => {
        paginationState.permisos.currentPage = 1;
        cargarPermisosPaginado();
      });
    }
  });

  // Filtros de lotes
  ['filtroUsuarioLote', 'filtroModuloLote', 'filtroEntidadLote', 'filtroBusLote'].forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.addEventListener('change', () => {
        paginationState.lotes.currentPage = 1;
        cargarLotesPaginado();
      });
    }
  });
}

// Función para reinicializar paginación cuando se cambia de tab
function reinicializarTabla(tabla) {
  const state = paginationState[tabla];
  state.currentPage = 1;
  
  switch(tabla) {
    case 'personas':
      cargarPersonasPaginado();
      break;
    case 'usuarios':
      cargarUsuariosPaginado();
      break;
    case 'permisos':
      cargarPermisosPaginado();
      break;
    case 'lotes':
      cargarLotesPaginado();
      break;
    case 'modulos':
      cargarModulosPaginado();
      break;
  }
}

// Eventos para tabs
function asignarEventosTabs() {
  const tabs = document.querySelectorAll('#tabUsuarios button[data-bs-toggle="tab"]');
  tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', (e) => {
      const target = e.target.getAttribute('data-bs-target');
      
      switch(target) {
        case '#pane-personas':
          reinicializarTabla('personas');
          break;
        case '#pane-usuarios':
          reinicializarTabla('usuarios');
          break;
        case '#pane-permisos':
          reinicializarTabla('permisos');
          break;
        case '#pane-lotes':
          reinicializarTabla('lotes');
          break;
        case '#pane-modulos':
          reinicializarTabla('modulos');
          break;
      }
    });
  });
}

// Sobrescribir las funciones originales para usar paginación
function sobrescribirFuncionesOriginales() {
  // Sobrescribir funciones del archivo usuarios.js
  if (window.cargarPersonas) {
    const originalCargarPersonas = window.cargarPersonas;
    window.cargarPersonas = function() {
      if (document.getElementById('paginacionPersonas')) {
        cargarPersonasPaginado();
      } else {
        originalCargarPersonas();
      }
    };
  }

  if (window.cargarUsuarios) {
    const originalCargarUsuarios = window.cargarUsuarios;
    window.cargarUsuarios = function() {
      if (document.getElementById('paginacionUsuarios')) {
        cargarUsuariosPaginado();
      } else {
        originalCargarUsuarios();
      }
    };
  }

  if (window.cargarPermisos) {
    const originalCargarPermisos = window.cargarPermisos;
    window.cargarPermisos = function() {
      if (document.getElementById('paginacionPermisos')) {
        cargarPermisosPaginado();
      } else {
        originalCargarPermisos();
      }
    };
  }

  if (window.cargarModulos) {
    const originalCargarModulos = window.cargarModulos;
    window.cargarModulos = function() {
      if (document.getElementById('paginacionModulos')) {
        cargarModulosPaginado();
      } else {
        originalCargarModulos();
      }
    };
  }

  if (window.cargarLotes) {
    const originalCargarLotes = window.cargarLotes;
    window.cargarLotes = function() {
      if (document.getElementById('paginacionLotes')) {
        cargarLotesPaginado();
      } else {
        originalCargarLotes();
      }
    };
  }
}

// Inicialización
function inicializarPaginacionUsuarios() {
  console.log('🔄 Inicializando sistema de paginación para usuarios...');
  
  asignarEventosPaginacion();
  asignarEventosBusqueda();
  asignarEventosTabs();
  sobrescribirFuncionesOriginales();
  
  // Cargar datos de la tabla activa por defecto (personas)
  setTimeout(() => {
    const activeTab = document.querySelector('#tabUsuarios .nav-link.active');
    if (activeTab && activeTab.getAttribute('data-bs-target') === '#pane-personas') {
      reinicializarTabla('personas');
    }
  }, 100);
  
  console.log('✅ Sistema de paginación de usuarios inicializado');
}

// Exponer funciones globalmente
window.paginationUsuarios = {
  goToPage,
  reinicializarTabla,
  state: paginationState
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inicializarPaginacionUsuarios);
} else {
  inicializarPaginacionUsuarios();
}

console.log('[pagination_usuarios.js] Cargado completamente');
