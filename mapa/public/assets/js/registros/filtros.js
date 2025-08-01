import { renderizarTabla, renderizarPaginacion } from './tabla.js';

let registrosCompletos = [];
let paginaActual = 1;
const registrosPorPagina = 10;
let filtrosActuales = {};

// ✅ Leer página actual desde sessionStorage
const paginaGuardada = parseInt(sessionStorage.getItem('paginaActual'));
if (!isNaN(paginaGuardada)) {
  paginaActual = paginaGuardada;
}

function manejarCambioPagina(nuevaPagina) {
  paginaActual = nuevaPagina;

  // ✅ Guardar nueva página en sessionStorage
  sessionStorage.setItem('paginaActual', nuevaPagina);

  renderizarTabla(registrosCompletos, paginaActual, registrosPorPagina);
  renderizarPaginacion(registrosCompletos.length, paginaActual, registrosPorPagina, manejarCambioPagina);
}

export function setupFiltros() {
  const formFiltro = document.getElementById('filtrosForm');
  if (!formFiltro) return;

  formFiltro.addEventListener('submit', e => {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(formFiltro));
      console.log("📤 Filtros enviados:", datos); // DEBE aparecer en la consola
    cargarRegistrosDesdeJSON(datos);
  });
}

export function cargarRegistrosDesdeJSON(filtros = filtrosActuales) {
  filtrosActuales = filtros;

  const loader = document.getElementById('cargando');
  if (loader) loader.style.display = 'block';

  fetch('../../server/acciones/registros_datos.php', {
    method: 'POST',
    body: new URLSearchParams(filtros)
  })
    .then(res => res.json())
    .then(resp => {
      console.log("📥 Respuesta del servidor:", resp); // Verifica que lleguen datos filtrados
      registrosCompletos = resp.data || [];

      // Si la página guardada es mayor que las disponibles, vuelve a la 1
      const totalPaginas = Math.ceil(registrosCompletos.length / registrosPorPagina);
      if (paginaActual > totalPaginas) {
        paginaActual = 1;
        sessionStorage.setItem('paginaActual', 1);
      }

      renderizarTabla(registrosCompletos, paginaActual, registrosPorPagina);
      renderizarPaginacion(registrosCompletos.length, paginaActual, registrosPorPagina, manejarCambioPagina);
    })
    .catch(err => {
      console.error("Error al cargar registros:", err);
    })
    .finally(() => {
      if (loader) loader.style.display = 'none';
    });
}
