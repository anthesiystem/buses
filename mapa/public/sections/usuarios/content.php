<?php
// Ruta: /final/mapa/public/sections/usuarios/content.php
// Versión modular para cargar en contenedor
?>
<div class="container-fluid py-3">
  <h3 class="mb-3">Gestión de Personas, Usuarios y Permisos</h3>

  <ul class="nav nav-tabs" id="tabUsuarios" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-personas" data-bs-toggle="tab" data-bs-target="#pane-personas" type="button" role="tab">Personas</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-usuarios" data-bs-toggle="tab" data-bs-target="#pane-usuarios" type="button" role="tab">Usuarios</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-permisos" data-bs-toggle="tab" data-bs-target="#pane-permisos" type="button" role="tab">Permisos</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-modulos" data-bs-toggle="tab" data-bs-target="#pane-modulos" type="button" role="tab">Módulos</button>
    </li>
  </ul>

  <div class="tab-content border-start border-end border-bottom p-3 bg-white rounded-bottom" id="tabUsuariosContent">
    <!-- PERSONAS -->
    <div class="tab-pane fade show active" id="pane-personas" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="input-group" style="max-width:380px;">
          <span class="input-group-text">Buscar</span>
          <input type="text" class="form-control" id="buscarPersona" placeholder="Nombre, correo o número de empleado">
        </div>
        <button class="btn btn-primary" onclick="abrirModalPersona()">Nueva persona</button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>No. Empleado</th>
              <th>Correo</th>
              <th>Dependencia</th>
              <th>Entidad</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbPersonas"></tbody>
        </table>
      </div>
    </div>

    <!-- USUARIOS -->
    <div class="tab-pane fade" id="pane-usuarios" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="input-group" style="max-width:380px;">
          <span class="input-group-text">Buscar</span>
          <input type="text" class="form-control" id="buscarUsuario" placeholder="Cuenta o persona">
        </div>
        <button class="btn btn-primary" onclick="abrirModalUsuario()">Nuevo usuario</button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Cuenta</th>
              <th>Nivel</th>
              <th>Persona</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbUsuarios"></tbody>
        </table>
      </div>
    </div>

    <!-- PERMISOS -->
    <div class="tab-pane fade" id="pane-permisos" role="tabpanel">
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label class="form-label">Usuario</label>
          <select id="filtroUsuarioPerm" class="form-select"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Módulo</label>
          <select id="filtroModuloPerm" class="form-select"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Entidad</label>
          <select id="filtroEntidadPerm" class="form-select"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Bus</label>
          <select id="filtroBusPerm" class="form-select"></select>
        </div>
      </div>

      <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" onclick="abrirModalPermiso()">Nuevo permiso</button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Módulo</th>
              <th>Entidad</th>
              <th>Bus</th>
              <th>Acción</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbPermisos"></tbody>
        </table>
      </div>
    </div>

    <!-- MODULOS -->
    <div class="tab-pane fade" id="pane-modulos" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="input-group" style="max-width:380px;">
          <span class="input-group-text">Buscar</span>
          <input type="text" class="form-control" id="buscarModulo" placeholder="Slug del módulo">
        </div>
        <button class="btn btn-primary" onclick="abrirModalModulo()">Nuevo módulo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Descripción (slug)</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbModulos"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODALES -->
<?php include __DIR__ . '/modales.php'; ?>

<!-- Scripts específicos del módulo -->
<script>
console.log('Cargando módulo usuarios...');
console.log('Path:', <?= json_encode($_SERVER['PHP_SELF']) ?>);
console.log('Dir:', <?= json_encode(dirname($_SERVER['PHP_SELF'])) ?>);
</script>
<script src="/final/mapa/public/sections/usuarios/usuarios.js?v=<?= time() ?>"></script>
<script>
console.log('Script usuarios.js cargado');
// Arranque robusto: si el DOM ya está listo, inicializa; si no, espera.
if (document.readyState !== 'loading') {
  console.log('DOM listo, inicializando...');
  window.initUsuarios && window.initUsuarios();
} else {
  console.log('DOM cargando, esperando...');
  document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM listo (evento), inicializando...');
    window.initUsuarios && window.initUsuarios();
  });
}
</script>
