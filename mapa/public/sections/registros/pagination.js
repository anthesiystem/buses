// pagination.js - Funcionalidad de paginación y filtros
let rowsPerPage = 10;
let currentPage = 1;
let totalPages = 1;
let totalRegistros = 0;
let filtros = {
  estado: '',
  entidad: '',
  categoria: '',
  bus: '',
  engine: '',
  tecnologia: '',
  etapa: ''
};

function cargarDatos() {
  console.log("🔄 cargarDatos() iniciado");
  
  const tbodyReg = document.querySelector("#tbodyReg");
  const pageInfo = document.getElementById("pageInfo");
  const rangeInfo = document.getElementById("rangeInfo");
  
  if (!tbodyReg || !pageInfo || !rangeInfo) {
    console.warn("⚠️ Elementos de la tabla no encontrados, reintentando en 200ms...");
    setTimeout(cargarDatos, 200);
    return;
  }

  const params = new URLSearchParams({
    page: currentPage,
    rowsPerPage: rowsPerPage
  });

  // Añadir filtros solo si tienen valor
  if (filtros.estado) params.append('estado', filtros.estado);
  if (filtros.entidad) params.append('entidad', filtros.entidad);
  if (filtros.categoria) params.append('categoria', filtros.categoria);
  if (filtros.bus) params.append('bus', filtros.bus);
  if (filtros.engine) params.append('engine', filtros.engine);
  if (filtros.tecnologia) params.append('tecnologia', filtros.tecnologia);
  if (filtros.etapa) params.append('etapa', filtros.etapa);

  // Determinar la ruta correcta según el contexto
  let baseUrl = '';
  const esCargaDinamica = window.parent !== window || document.getElementById('main-content');
  
  if (esCargaDinamica) {
    baseUrl = 'sections/registros/';
  }
  
  const url = `${baseUrl}mis_registros.php?${params}`;
  console.log("🌐 Haciendo fetch a:", url);

  fetch(url)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      return res.json();
    })
    .then(data => {
      tbodyReg.innerHTML = data.html;
      totalRegistros = data.total;
      totalPages = data.totalPages;
      updatePageInfo();
      reconectarEventos();
      console.log(`✅ Datos cargados: ${totalRegistros} registros, página ${currentPage}/${totalPages}`);
    })
    .catch(error => {
      console.error('❌ Error cargando datos:', error);
      tbodyReg.innerHTML = `<tr><td colspan="12" class="text-center text-danger">Error: ${error.message}</td></tr>`;
    });
}

function updatePageInfo() {
  const pageInfoEl = document.getElementById("pageInfo");
  const rangeInfoEl = document.getElementById("rangeInfo");
  
  if (pageInfoEl) {
    pageInfoEl.textContent = `Página ${currentPage} / ${totalPages || 1}`;
  }

  if (rangeInfoEl) {
    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(start + rowsPerPage - 1, totalRegistros);
    rangeInfoEl.textContent = totalRegistros > 0 
      ? `Mostrando ${start}–${end} de ${totalRegistros}`
      : "Mostrando 0–0 de 0";
  }
}

function goToPage(page) {
  if (page < 1 || page > totalPages) return;
  currentPage = page;
  cargarDatos();
}

function aplicarFiltros() {
  currentPage = 1;
  cargarDatos();
}

function reconectarEventos() {
  const tbody = document.querySelector('#tbodyReg');
  if (!tbody) return;
  
  tbody.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', (e) => {
      const tr = e.target.closest('tr');
      tr.classList.toggle('selected', e.target.checked);
      actualizarCheckAll();
    });
  });
}

function actualizarCheckAll() {
  const tbody = document.querySelector('#tbodyReg');
  const checkAll = document.getElementById('checkAll');
  if (!tbody || !checkAll) return;
  
  const checkboxes = tbody.querySelectorAll('.row-check');
  const checkedBoxes = tbody.querySelectorAll('.row-check:checked');
  
  if (checkboxes.length === 0) {
    checkAll.indeterminate = false;
    checkAll.checked = false;
  } else if (checkedBoxes.length === checkboxes.length) {
    checkAll.indeterminate = false;
    checkAll.checked = true;
  } else if (checkedBoxes.length > 0) {
    checkAll.indeterminate = true;
    checkAll.checked = false;
  } else {
    checkAll.indeterminate = false;
    checkAll.checked = false;
  }
}

function asignarEventosPaginacion() {
  const btnFirst = document.getElementById("btnFirst");
  const btnPrev = document.getElementById("btnPrev");
  const btnNext = document.getElementById("btnNext");
  const btnLast = document.getElementById("btnLast");
  
  if (btnFirst) btnFirst.addEventListener("click", () => goToPage(1));
  if (btnPrev) btnPrev.addEventListener("click", () => goToPage(currentPage - 1));
  if (btnNext) btnNext.addEventListener("click", () => goToPage(currentPage + 1));
  if (btnLast) btnLast.addEventListener("click", () => goToPage(totalPages));
}

function asignarEventosFiltros() {
  const filtroEstado = document.getElementById("filtroEstado");
  const filtroEntidad = document.getElementById("filtroEntidad");
  const filtroCategoria = document.getElementById("filtroCategoria");
  const filtroBus = document.getElementById("filtroBus");
  const filtroEngine = document.getElementById("filtroEngine");
  const filtroTecnologia = document.getElementById("filtroTecnologia");
  const filtroEtapa = document.getElementById("filtroEtapa");
  const checkAll = document.getElementById("checkAll");
  
  if (filtroEstado) {
    filtroEstado.addEventListener("change", (e) => {
      filtros.estado = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroEntidad) {
    filtroEntidad.addEventListener("change", (e) => {
      filtros.entidad = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroCategoria) {
    filtroCategoria.addEventListener("change", (e) => {
      filtros.categoria = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroBus) {
    filtroBus.addEventListener("change", (e) => {
      filtros.bus = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroEngine) {
    filtroEngine.addEventListener("change", (e) => {
      filtros.engine = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroTecnologia) {
    filtroTecnologia.addEventListener("change", (e) => {
      filtros.tecnologia = e.target.value;
      aplicarFiltros();
    });
  }

  if (filtroEtapa) {
    filtroEtapa.addEventListener("change", (e) => {
      filtros.etapa = e.target.value;
      aplicarFiltros();
    });
  }

  if (checkAll) {
    checkAll.addEventListener("change", (e) => {
      const checked = e.target.checked;
      const tbody = document.querySelector('#tbodyReg');
      if (tbody) {
        tbody.querySelectorAll('.row-check').forEach(cb => {
          cb.checked = checked;
          const tr = cb.closest('tr');
          tr.classList.toggle('selected', checked);
        });
      }
    });
  }
}

function desactivarRegistro(id) {
  if (!confirm('¿Está seguro de desactivar este registro?')) {
    return;
  }

  const esCargaDinamica = window.parent !== window || document.getElementById('main-content');
  const submitUrl = esCargaDinamica ? 'sections/registros/actions.php' : 'actions.php';

  const formData = new FormData();
  formData.append('action', 'desactivar_multiple');
  formData.append('ids', JSON.stringify([id]));
  formData.append('ajax', '1');

  fetch(submitUrl, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.ok) {
      cargarDatos();
      alert('Registro desactivado exitosamente');
    } else {
      alert('Error: ' + (data.error || 'Error desconocido'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error al desactivar registro');
  });
}

function ajustarRutasImagenes() {
  const esCargaDinamica = window.parent !== window || document.getElementById('main-content');
  const imgPrefix = esCargaDinamica ? 'img/' : '../../img/';
  
  const loadingImg1 = document.getElementById('loadingImg1');
  const loadingImg2 = document.getElementById('loadingImg2');
  
  if (loadingImg1) {
    loadingImg1.src = imgPrefix + 'escudospiner.gif';
  }
  
  if (loadingImg2) {
    loadingImg2.src = imgPrefix + 'escudospiner.gif';
  }
  
  console.log(`🖼️ Rutas de imágenes ajustadas para: ${esCargaDinamica ? 'carga dinámica' : 'carga directa'}`);
}

function inicializarPagina() {
  const esCargaDinamica = window.parent !== window || document.getElementById('main-content');
  console.log("🔄 Inicializando página de registros...", esCargaDinamica ? "(carga dinámica)" : "(carga directa)");
  
  ajustarRutasImagenes();
  asignarEventosPaginacion();
  asignarEventosFiltros();
  
  if (esCargaDinamica) {
    setTimeout(() => {
      cargarDatos();
    }, 100);
  } else {
    cargarDatos();
  }
}

// Exponer funciones globalmente
window.desactivarRegistro = desactivarRegistro;
window.paginationState = {
  get page() { return currentPage; },
  get perPage() { return rowsPerPage; },
  get term() { return ''; }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener("DOMContentLoaded", inicializarPagina);
} else {
  inicializarPagina();
}
