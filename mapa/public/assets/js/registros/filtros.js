import { renderizarTabla, renderizarPaginacion } from './tabla.js';

let registrosCompletos = [];
let paginaActual = 1;
const registrosPorPagina = 10;
let filtrosActuales = {};
let fetchController = null; // para cancelar peticiones previas

// Leer página actual desde sessionStorage
const paginaGuardada = parseInt(sessionStorage.getItem('paginaActual'), 10);
if (!Number.isNaN(paginaGuardada)) {
  paginaActual = paginaGuardada;
}

function manejarCambioPagina(nuevaPagina) {
  paginaActual = nuevaPagina;
  sessionStorage.setItem('paginaActual', nuevaPagina);

  renderizarTabla(registrosCompletos, paginaActual, registrosPorPagina);
  renderizarPaginacion(registrosCompletos.length, paginaActual, registrosPorPagina, manejarCambioPagina);
}

export function setupFiltros() {
  const formFiltro = document.getElementById('filtrosForm');
  if (!formFiltro) return;

  // Envío normal del formulario
  formFiltro.addEventListener('submit', e => {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(formFiltro));
    console.log("📤 Filtros enviados:", datos);
    paginaActual = 1; // reset a la primera página cuando cambian filtros
    sessionStorage.setItem('paginaActual', 1);
    cargarRegistrosDesdeJSON(datos);
  });

  // (Opcional) Si tienes un botón "limpiar filtros" con id="btnLimpiarFiltros"
  const btnLimpiar = document.getElementById('btnLimpiarFiltros');
  if (btnLimpiar) {
    btnLimpiar.addEventListener('click', () => {
      formFiltro.reset();
      paginaActual = 1;
      sessionStorage.setItem('paginaActual', 1);
      cargarRegistrosDesdeJSON({});
    });
  }
}

export function cargarRegistrosDesdeJSON(filtros = filtrosActuales) {
  filtrosActuales = filtros;

  // Cancela fetch previo si aún no termina
  if (fetchController) {
    fetchController.abort();
  }
  fetchController = new AbortController();

  const loader = document.getElementById('cargando');
  if (loader) loader.style.display = 'block';

  const body = new URLSearchParams(filtros);

  fetch('../../server/acciones/registros_datos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body,
    signal: fetchController.signal
  })
    .then(async res => {
      if (!res.ok) {
        const texto = await res.text().catch(() => '');
        throw new Error(`HTTP ${res.status} ${res.statusText} - ${texto}`);
      }
      return res.json();
    })
    .then(resp => {
      console.log("📥 Respuesta del servidor:", resp);

      registrosCompletos = Array.isArray(resp?.data) ? resp.data : [];

      // Ajuste de página si queda fuera de rango
      const totalPaginas = Math.max(1, Math.ceil(registrosCompletos.length / registrosPorPagina));
      if (paginaActual > totalPaginas) {
        paginaActual = 1;
        sessionStorage.setItem('paginaActual', 1);
      }

      // Render
      renderizarTabla(registrosCompletos, paginaActual, registrosPorPagina);
      renderizarPaginacion(registrosCompletos.length, paginaActual, registrosPorPagina, manejarCambioPagina);

      // Mensaje opcional si no hay datos
      if (registrosCompletos.length === 0) {
        const contenedor = document.getElementById('contenedorTabla');
        if (contenedor && !contenedor.querySelector('.alert-no-datos')) {
          const div = document.createElement('div');
          div.className = 'alert alert-warning mt-3 alert-no-datos';
          div.textContent = 'No se encontraron registros con los filtros seleccionados.';
          contenedor.appendChild(div);
          // remueve el mensaje cuando haya resultados en próxima carga
          setTimeout(() => div.remove(), 3000);
        }
      }
    })
    .catch(err => {
      if (err.name === 'AbortError') {
        console.log('⏭️ Petición anterior cancelada.');
        return;
      }
      console.error("Error al cargar registros:", err);
    })
    .finally(() => {
      if (loader) loader.style.display = 'none';
      fetchController = null;
    });
}
