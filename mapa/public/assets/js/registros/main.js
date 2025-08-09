import { cargarCatalogos } from './catalogos.js';
import { cargarRegistrosDesdeJSON, setupFiltros } from './filtros.js';
import { abrirModal, editar } from './modal.js';
import { inicializarGuardado } from './guardar.js';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRegistro');
  console.log('✅ Ejecutando setup... form existe?', !!form);

  cargarCatalogos();
  setupFiltros();
  cargarRegistrosDesdeJSON();
  inicializarGuardado();

  // expone funciones globales usadas por botones/HTML
  window.abrirModal = abrirModal;
  window.editar = editar;
  window.setupFiltros = setupFiltros;
});
