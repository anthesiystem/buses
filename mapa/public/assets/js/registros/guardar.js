// /js/registros/guardar.js
import { cargarRegistrosDesdeJSON } from './filtros.js';

export function inicializarGuardado() {
  const form = document.getElementById('formRegistro');
  if (!form) {
    console.warn('guardar.js: #formRegistro no encontrado. ¿Se cargó el modal?');
    return;
  }

  // Evita registrar múltiples veces si se llama dos veces a inicializarGuardado()
  if (form.dataset.guardarInit === '1') return;
  form.dataset.guardarInit = '1';

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = 'Guardando...';
    }

    try {
      const datos = new FormData(form);

      const res = await fetch('../../server/acciones/guardar_registro.php', {
        method: 'POST',
        body: datos
      });

      if (!res.ok) {
        const txt = await res.text().catch(() => '');
        throw new Error(`HTTP ${res.status} ${res.statusText} - ${txt}`);
      }

      const resp = await res.json();
      console.log('Respuesta:', resp);

      if (resp.success === true || resp.success === 'true') {
        // Cerrar modal de forma segura
        const modalEl = document.getElementById('modalRegistro');
        if (modalEl) {
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.hide();
        }

        // Toast
        const fueNuevo = !form.querySelector('[name="ID"]')?.value;
        const mensaje = fueNuevo
          ? '✅ Registro creado exitosamente'
          : '✅ Registro actualizado exitosamente';

        const toastBody = document.getElementById('mensajeToast');
        if (toastBody) toastBody.textContent = mensaje;

        const toastEl = document.getElementById('toastExito');
        if (toastEl) new bootstrap.Toast(toastEl).show();

        // Limpia form
        form.reset();

        // Recargar tabla con filtros actuales
        cargarRegistrosDesdeJSON();

        // Animación de éxito opcional
        const animacion = document.getElementById('guardadoExitoAnimado');
        if (animacion) {
          animacion.style.display = 'block';
          setTimeout(() => {
            animacion.style.display = 'none';
          }, 2500);
        }
      } else {
        alert('Error al guardar: ' + (resp.error || 'Desconocido'));
      }
    } catch (err) {
      console.error('Error al guardar:', err);
      alert('Error al guardar');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalText || 'Guardar';
      }
    }
  });
}
