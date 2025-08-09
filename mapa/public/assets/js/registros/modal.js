// /js/registros/modal.js
import { cargarCatalogos } from './catalogos.js';

function getForm() {
  const form = document.getElementById('formRegistro');
  if (!form) {
    console.warn('modal.js: #formRegistro no encontrado.');
  }
  return form;
}

// Asigna valor solo si existe la opción (evita selects en estado inconsistente)
function setValueSafe(form, name, value) {
  const el = form?.querySelector(`[name="${name}"]`);
  if (!el) return;
  // Para inputs normales
  if (el.tagName !== 'SELECT') {
    el.value = value ?? '';
    return;
  }
  // Para selects: solo asigna si existe la opción
  const exists = Array.from(el.options).some(o => String(o.value) === String(value));
  if (exists) el.value = String(value);
}

export async function abrirModal() {
  const form = getForm();
  if (!form) return;

  // Reset seguro
  form.reset();
  const inputID = form.querySelector('[name="ID"]');
  if (inputID) inputID.value = '';

  // Carga catálogos (si tu cargarCatalogos no es async, igual funciona con await)
  await cargarCatalogos();

  // Muestra modal de forma robusta
  const modalEl = document.getElementById('modalRegistro');
  if (!modalEl) {
    console.warn('modal.js: #modalRegistro no encontrado.');
    return;
  }
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

export async function editar(datos) {
  const form = getForm();
  if (!form) return;

  await cargarCatalogos();

  // Asignación segura de campos
  for (const [k, v] of Object.entries(datos || {})) {
    setValueSafe(form, k, v ?? '');
  }

  // Mostrar modal
  const modalEl = document.getElementById('modalRegistro');
  if (!modalEl) {
    console.warn('modal.js: #modalRegistro no encontrado.');
    return;
  }
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}
