console.log("✅ buses.js cargado");

// IIFE para no contaminar el global
(function () {
  // Base de la sección (defínela en buses.php). Fallback absoluto por si acaso.
 const BASE = () => (window.BUSES_PATH || 'sections/buses/');
const ICONS_BASE_URL = (window.ICONS_BASE_URL || 'icons/');

  // Estado de paginación
  let currentPage = 1;
  let perPage = 8; // Valor inicial, se calculará dinámicamente
  let currentSearch = '';
  let totalPages = 1;
  let totalRecords = 0;
  let searchTimeout = null;
  let autoSizeEnabled = true; // Controla si se calcula automáticamente el tamaño

  function iconURL(imagen) {
    if (!imagen) return ICONS_BASE_URL + '_placeholder.png';
    const name = String(imagen).replace(/\\/g, '/').split('/').pop().trim();
    const url = ICONS_BASE_URL + name;
    // DEBUG opcional
    // console.log('icon src ->', url);
    return url;
  }


  // Evita doble inicialización
let yaInicializado = false;

window.initBuses = function initBuses() {
  // Enlaza eventos solo la primera vez
  if (!yaInicializado) {
    const form = document.getElementById('formBus');
    if (form && !form.dataset.bound) {
      form.addEventListener('submit', onSubmitFormBus);
      form.dataset.bound = '1'; // marca para no duplicar
    }
    
    // Inicializar eventos de paginación
    initPaginationEvents();
    
    // Configurar cálculo automático de filas visibles
    initAutoSizing();
    
    yaInicializado = true;
  }

  // ✅ Siempre recarga la tabla al (re)insertar la vista
  cargarBuses();
};

  // Inicializar eventos de paginación y búsqueda
  function initPaginationEvents() {
    // Búsqueda con debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          currentSearch = e.target.value.trim();
          currentPage = 1; // Resetear a primera página al buscar
          cargarBuses();
        }, 300); // Esperar 300ms después de que deje de escribir
      });
    }

    // Selector de registros por página
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
      perPageSelect.addEventListener('change', (e) => {
        const value = e.target.value;
        if (value === 'auto') {
          autoSizeEnabled = true;
          setVisibleRows();
        } else {
          autoSizeEnabled = false;
          perPage = parseInt(value);
          currentPage = 1; // Resetear a primera página
          cargarBuses();
        }
      });
    }
  }

  // Configurar cálculo automático de tamaño
  function initAutoSizing() {
    // Agregar opción "Auto" al selector si no existe
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect && !perPageSelect.querySelector('option[value="auto"]')) {
      const autoOption = document.createElement('option');
      autoOption.value = 'auto';
      autoOption.textContent = 'Auto';
      autoOption.selected = true;
      perPageSelect.insertBefore(autoOption, perPageSelect.firstChild);
    }

    // Configurar eventos de redimensionamiento
    window.addEventListener('resize', debounceResize);
    
    // Calcular tamaño inicial
    setTimeout(() => setVisibleRows(), 100);
  }

  // Debounce para el resize
  let resizeTimeout = null;
  function debounceResize() {
    if (!autoSizeEnabled) return;
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(setVisibleRows, 250);
  }

  // Calcular filas visibles basándose en la altura de la ventana
  function setVisibleRows() {
    if (!autoSizeEnabled) return;

    const rowHeight = 58; // Altura aproximada de cada fila en modo compacto
    
    // Obtener alturas de elementos fijos
    const header = document.querySelector('.header-container') || 
                  document.querySelector('h4'); // fallback al título
    const paginationControls = document.querySelector('.pagination-controls');
    const paginationNav = document.querySelector('.pagination-nav');
    const tableHead = document.querySelector('.table thead');

    const totalOffset =
      (header?.offsetHeight ?? 60) +
      (paginationControls?.offsetHeight ?? 80) +
      (paginationNav?.offsetHeight ?? 50) +
      (tableHead?.offsetHeight ?? 45) +
      200; // Margen adicional para padding, márgenes, etc.

    const availableHeight = window.innerHeight - totalOffset;
    const visibleRows = Math.floor(availableHeight / rowHeight);

    // Asegurar un mínimo y máximo razonable
    const newPerPage = Math.max(5, Math.min(50, visibleRows));
    
    console.log(`📏 Cálculo automático: altura disponible=${availableHeight}px, filas visibles=${newPerPage}`);

    if (newPerPage !== perPage) {
      perPage = newPerPage;
      currentPage = 1;
      cargarBuses();
    }
  }


  function cargarBuses() {
    // Construir URL con parámetros de paginación
    const params = new URLSearchParams({
      page: currentPage,
      limit: perPage
    });
    
    if (currentSearch) {
      params.set('search', currentSearch);
    }

    const url = BASE() + 'buses_datos.php?' + params.toString();
    
    fetch(url)
      .then(res => res.json())
      .then(response => {
        console.log('📦 datos buses:', response);

        if (!response.data || !Array.isArray(response.data)) {
          console.error('Los datos no son válidos:', response);
          return;
        }

        const data = response.data;
        const pagination = response.pagination;

        // Actualizar estado de paginación
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
        totalRecords = pagination.total_records;

        // Actualizar información de paginación
        updatePaginationInfo(pagination);
        
        // Actualizar navegación de páginas
        updatePaginationNav(pagination);

        const cuerpo = document.getElementById('tablaBuses');
        if (!cuerpo) {
          console.warn('No se encontró #tablaBuses');
          return;
        }

        cuerpo.innerHTML = '';
        
        if (data.length === 0) {
          cuerpo.innerHTML = `
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                ${currentSearch ? 'No se encontraron buses con ese criterio de búsqueda' : 'No hay buses registrados'}
              </td>
            </tr>
          `;
          return;
        }
        
        data.forEach(bus => {
          const src = bus.imagen_url; // viene absoluta del backend

            // Normaliza el color (acepta '#f59e0b' o 'f59e0b')

cuerpo.innerHTML += `<tr>
  <td class="col-id"><span class="id-chip">${bus.ID}</span></td>
  <td class="text-start"><div class="bus-name">${bus.descripcion}</div></td>

  <!-- Colores usando variable CSS -->
 <td class="td-color">
  <div class="color-pill" style="--chip:${chip(bus.color_implementado)}">
    <span class="dot-sq"></span>
    <span>${hexText(bus.color_implementado)}</span>
  </div>
</td>

<td class="td-color">
  <div class="color-pill" style="--chip:${chip(bus.pruebas)}">
    <span class="dot-sq"></span>
    <span>${hexText(bus.pruebas)}</span>
  </div>
</td>

<td class="td-color col-sinimpl">
  <div class="color-pill" style="--chip:${chip(bus.color_sin_implementar)}">
    <span class="dot-sq"></span>
    <span>${hexText(bus.color_sin_implementar)}</span>
  </div>
</td>

  <td class="col-icono">
    <span class="bus-icon">
      <img src="${src}" height="24" alt="icono"
           onerror="this.onerror=null;this.src='${(bus.imagen_url || 'icons/default.png').replace(/[^/]+$/, '_placeholder.png')}'">
    </span>
  </td>

  <td>
    <span class="badge-estado ${bus.activo == 1 ? 'estado-activo' : 'estado-inactivo'}">
      ${bus.activo == 1 ? 'Activo' : 'Inactivo'}
    </span>
  </td>

  <td>
    <div class="acciones">
      <button class="btn btn-soft btn-edit btn-sm" onclick='editarBus(${JSON.stringify(bus)})'>✏️ Editar</button>
 <button
  class="btn btn-soft ${bus.activo == 1 ? "btn-danger-soft" : ""} btn-sm"
  onclick="cambiarEstado(${bus.ID}, ${bus.activo == 1 ? 0 : 1})">
  ${bus.activo == 1 ? '⛔ Desactivar' : '✅ Activar'}
</button>
    </div>
  </td>
</tr>`;
        });
      })
      .catch(error => {
        console.error("Error cargando buses:", error);
        const cuerpo = document.getElementById('tablaBuses');
        if (cuerpo) {
          cuerpo.innerHTML = `
            <tr>
              <td colspan="8" class="text-center py-4 text-danger">
                Error al cargar los datos: ${error.message}
              </td>
            </tr>
          `;
        }
      });
  }

  // Actualizar información de paginación
  function updatePaginationInfo(pagination) {
    const info = document.getElementById('paginationInfo');
    if (!info) return;

    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(start + pagination.per_page - 1, pagination.total_records);
    
    let text = '';
    if (pagination.total_records > 0) {
      text = `Mostrando ${start}–${end} de ${pagination.total_records}`;
      if (autoSizeEnabled) {
        text += ` (${pagination.per_page} por página - Auto)`;
      }
    } else {
      text = 'Mostrando 0–0 de 0';
    }
    
    if (pagination.search) {
      text += ` | Filtrado por "${pagination.search}"`;
    }
    
    info.textContent = text;
  }

  // Actualizar navegación de páginas
  function updatePaginationNav(pagination) {
    const nav = document.getElementById('paginationNav');
    if (!nav) return;

    if (pagination.total_pages <= 1) {
      nav.innerHTML = '';
      return;
    }

    let navHTML = '';

    // Botón primera página
    navHTML += `
      <button class="page-btn" onclick="goToFirstPage()" 
              ${!pagination.has_previous ? 'disabled' : ''} 
              title="Primera página">
        ⏮️
      </button>
    `;

    // Botón anterior
    navHTML += `
      <button class="page-btn" onclick="goToPreviousPage()" 
              ${!pagination.has_previous ? 'disabled' : ''} 
              title="Página anterior">
        ← Anterior
      </button>
    `;

    // Números de página
    navHTML += '<div class="page-numbers">';
    
    const maxVisible = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxVisible - 1);
    
    // Ajustar si estamos cerca del final
    if (endPage - startPage < maxVisible - 1) {
      startPage = Math.max(1, endPage - maxVisible + 1);
    }

    // Primera página si no está visible
    if (startPage > 1) {
      navHTML += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
      if (startPage > 2) {
        navHTML += '<span class="page-ellipsis">...</span>';
      }
    }

    // Páginas visibles
    for (let i = startPage; i <= endPage; i++) {
      navHTML += `
        <button class="page-btn ${i === pagination.current_page ? 'active' : ''}" 
                onclick="goToPage(${i})" 
                title="Página ${i}">
          ${i}
        </button>
      `;
    }

    // Última página si no está visible
    if (endPage < pagination.total_pages) {
      if (endPage < pagination.total_pages - 1) {
        navHTML += '<span class="page-ellipsis">...</span>';
      }
      navHTML += `<button class="page-btn" onclick="goToPage(${pagination.total_pages})" title="Página ${pagination.total_pages}">${pagination.total_pages}</button>`;
    }

    navHTML += '</div>';

    // Botón siguiente
    navHTML += `
      <button class="page-btn" onclick="goToNextPage()" 
              ${!pagination.has_next ? 'disabled' : ''} 
              title="Página siguiente">
        Siguiente →
      </button>
    `;

    // Botón última página
    navHTML += `
      <button class="page-btn" onclick="goToLastPage()" 
              ${!pagination.has_next ? 'disabled' : ''} 
              title="Última página">
        ⏭️
      </button>
    `;

    nav.innerHTML = navHTML;
  }

  // Navegar a una página específica
  window.goToPage = function(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    cargarBuses();
  };

  // Funciones de navegación rápida
  window.goToFirstPage = function() {
    goToPage(1);
  };

  window.goToLastPage = function() {
    goToPage(totalPages);
  };

  window.goToPreviousPage = function() {
    goToPage(currentPage - 1);
  };

  window.goToNextPage = function() {
    goToPage(currentPage + 1);
  };

  // Función para cambiar el tamaño de página manualmente
  window.setPerPage = function(newPerPage) {
    autoSizeEnabled = false;
    perPage = newPerPage;
    currentPage = 1;
    
    // Actualizar el selector
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
      perPageSelect.value = newPerPage;
    }
    
    cargarBuses();
  };

  // Función para alternar entre modo automático y manual
  window.toggleAutoSize = function() {
    autoSizeEnabled = !autoSizeEnabled;
    if (autoSizeEnabled) {
      setVisibleRows();
    }
  };

  // Abre modal para nuevo
  window.abrirModalBus = function abrirModalBus() {
    const form = document.getElementById('formBus');
    if (form) form.reset();
    const id = document.getElementById('ID');
    if (id) id.value = '';
    const modal = document.getElementById('modalBus');
    if (modal) new bootstrap.Modal(modal).show();
  };

  // Editar
  window.editarBus = function editarBus(bus) {
    document.getElementById('ID').value = bus.ID ?? '';
    document.getElementById('descripcion').value = bus.descripcion ?? '';
    document.getElementById('color_implementado').value = bus.color_implementado ?? '';
    document.getElementById('color_sin_implementar').value = bus.color_sin_implementar ?? '';
    document.getElementById('pruebas').value = bus.pruebas ?? '';
    new bootstrap.Modal(document.getElementById('modalBus')).show();
  };

  // Submit del formulario (guardar/actualizar)
  function onSubmitFormBus(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch(BASE() + 'guardar_bus.php', {
      method: 'POST',
      body: formData
    })
      .then(async res => {
        // Verificar content-type para detectar errores HTML
        const contentType = res.headers.get('content-type') || '';
        
        if (!contentType.includes('application/json')) {
          const htmlResponse = await res.text();
          console.error('Respuesta no es JSON:', htmlResponse);
          throw new Error('El servidor devolvió una respuesta inválida (HTML en lugar de JSON)');
        }
        
        if (!res.ok) {
          throw new Error(`Error HTTP: ${res.status}`);
        }
        
        return res.json();
      })
      .then(resp => {
        if (resp.success) {
          const modalEl = document.getElementById('modalBus');
          const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          modal.hide();
          cargarBuses();
          // Opcional: mostrar mensaje de éxito
          console.log('✅', resp.message);
        } else {
          alert('❌ Error: ' + (resp.message || 'No especificado'));
        }
      })
      .catch(err => {
        console.error('Error guardando bus:', err);
        alert('❌ Error de conexión: ' + err.message);
      });
  }

  // Cambiar estado
 window.cambiarEstado = function cambiarEstado(id, estadoNuevo) {
  fetch(`${BASE()}cambiar_estado_bus.php?id=${encodeURIComponent(id)}&estado=${encodeURIComponent(estadoNuevo)}`)
    .then(async res => {
      // Evita el “Unexpected token '<'” si el servidor manda HTML
      const ct = res.headers.get('content-type') || '';
      const data = ct.includes('application/json') ? await res.json() : { success: false, error: await res.text() };
      if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
      cargarBuses();
    })
    .catch(err => {
      console.error('Error cambiando estado:', err);
      alert('Error cambiando estado: ' + err.message);
    });
};


})(); 
