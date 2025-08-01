import { cargarCatalogos } from './catalogos.js';
import { cargarRegistrosDesdeJSON, setupFiltros } from './filtros.js';
import { abrirModal, editar } from './modal.js';
import { inicializarGuardado } from './guardar.js';

document.addEventListener('DOMContentLoaded', () => {
  console.log("✅ Ejecutando setupFiltros...");

  cargarCatalogos();
  setupFiltros();
  cargarRegistrosDesdeJSON();
  inicializarGuardado();

  window.abrirModal = abrirModal;
  window.editar = editar;
  window.setupFiltros = setupFiltros;

});
