// /js/registros/modal.js

import { cargarCatalogos } from './catalogos.js';

export async function abrirModal() {
  const form = document.getElementById("formRegistro");
  form.reset();
  form.ID.value = "";
  await cargarCatalogos();

  const modal = new bootstrap.Modal(document.getElementById("modalRegistro"));
  modal.show();
}

export async function editar(datos) {
  await cargarCatalogos();
  const form = document.getElementById("formRegistro");

  Object.entries(datos).forEach(([k, v]) => {
    const campo = form.querySelector(`[name="${k}"]`);
    if (campo) campo.value = v ?? "";
  });

  const modal = new bootstrap.Modal(document.getElementById("modalRegistro"));
  modal.show();
}


