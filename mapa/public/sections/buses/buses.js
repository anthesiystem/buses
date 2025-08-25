console.log("✅ buses.js cargado");

// IIFE para no contaminar el global
(function () {
  // Base de la sección (defínela en buses.php). Fallback absoluto por si acaso.
 const BASE = () => (window.BUSES_PATH || 'sections/buses/');
const ICONS_BASE_URL = (window.ICONS_BASE_URL || 'icons/');

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
    yaInicializado = true;
  }

  // ✅ Siempre recarga la tabla al (re)insertar la vista
  cargarBuses();
};


  function cargarBuses() {
    fetch(BASE() + 'buses_datos.php')
      .then(res => res.json())
      .then(data => {
        console.log('📦 datos buses:', data);

        if (!Array.isArray(data)) {
          console.error('Los datos no son un arreglo:', data);
          return;
        }

        const cuerpo = document.getElementById('tablaBuses');
        if (!cuerpo) {
          console.warn('No se encontró #tablaBuses');
          return;
        }

        cuerpo.innerHTML = '';
        data.forEach(bus => {
          const src = bus.imagen_url; // viene absoluta del backend

            // Normaliza el color (acepta '#f59e0b' o 'f59e0b')

cuerpo.innerHTML += `




<tr>
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
</tr>
`;
        });
      })
      .catch(error => {
        console.error("Error cargando buses:", error);
      });
  }

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
      .then(res => res.json())
      .then(resp => {
        if (resp.success) {
          const modalEl = document.getElementById('modalBus');
          const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          modal.hide();
          cargarBuses();
        } else {
          alert('❌ Error: ' + (resp.message || 'No especificado'));
        }
      })
      .catch(err => console.error('Error guardando bus:', err));
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
