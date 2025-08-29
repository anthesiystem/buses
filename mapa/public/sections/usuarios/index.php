<?php
// Si es una petición AJAX o se solicita sólo el contenido, incluye content.php
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['content_only'])) {
    include __DIR__ . '/content.php';
    exit;
}

// Si no, es una carga directa y muestra la página completa
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Personas / Usuarios / Permisos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --brand:#7b1e2b; --brand-600:#8e2433; --brand-700:#661822; --brand-rgb:123,30,43;
      --ink:#1f2937; --muted:#6b7280; --row-hover:rgba(var(--brand-rgb),.04); --row-selected:rgba(var(--brand-rgb),.08);
      --header-bg:#ffffff; --header-border:#e5e7eb; --table-border:#e5e7eb; --badge-bg:#f3f4f6;
    }
    body{ color:var(--ink); background:#fafafa; }
    .page-title{ font-weight:700; letter-spacing:.2px; }
    .btn-brand{
      --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand);
      --bs-btn-hover-bg:var(--brand-600); --bs-btn-hover-border-color:var(--brand-600);
      --bs-btn-active-bg:var(--brand-700); --bs-btn-active-border-color:var(--brand-700);
      --bs-btn-color:#fff;
    }
    .btn-outline-brand{
      --bs-btn-color:var(--brand); --bs-btn-border-color:var(--brand);
      --bs-btn-hover-bg:var(--brand); --bs-btn-hover-border-color:var(--brand);
      --bs-btn-hover-color:#fff;
    }
    .table-card{
      background:#fff; border:1px solid var(--table-border);
      border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.04);
    }
    .table-responsive{ max-height:70vh; }
    .table-brand thead th{
      position:sticky; top:0; z-index:5; background:var(--header-bg);
      border-bottom:1px solid var(--header-border); color:var(--muted);
      font-weight:700; text-transform:uppercase; font-size:.78rem; letter-spacing:.5px; cursor:pointer;
    }
    .table-brand tbody td{ vertical-align:middle; border-color:var(--table-border); }
    .table-brand tbody tr:hover{ background:var(--row-hover); }
    .table-brand tbody tr.selected{ background:var(--row-selected); box-shadow:inset 4px 0 0 var(--brand); }
    .badge-soft{ background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb; font-weight:600; }
    .actions .btn{ padding:.25rem .5rem; }
    @media (max-width:768px){
      .col-sm-hide{ display:none; }
      .actions .btn .text{ display:none; }
    }
    
    #main-content {
      max-width: 90%;
      padding-left: 12%;
      padding-top: 5%;
    }
  </style>
</head>
<body class="bg-light">

<div class="container-fluid py-3">
  <h3 class="mb-3">Gestión de Usuarios</h3>

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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPersona" onclick="abrirModalPersona()">Nueva persona</button>
      </div>
      <div class="table-card">
        <div class="table-responsive">
          <table class="table table-hover table-brand align-middle m-0">
            <thead>
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
    </div>

    <!-- USUARIOS -->
    <div class="tab-pane fade" id="pane-usuarios" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="input-group" style="max-width:380px;">
          <span class="input-group-text">Buscar</span>
          <input type="text" class="form-control" id="buscarUsuario" placeholder="Cuenta o persona">
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="abrirModalUsuario()">Nuevo usuario</button>
      </div>
      <div class="table-card">
        <div class="table-responsive">
          <table class="table table-hover table-brand align-middle m-0">
            <thead>
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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPermiso" onclick="abrirModalPermiso()">Nuevo permiso</button>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="table table-hover table-brand align-middle m-0">
            <thead>
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
    </div>
  </div>
  <div id="diag" class="alert alert-info d-none mt-3" style="white-space:pre-wrap"></div>

  <!-- MODULOS -->
   
  <div class="tab-pane fade" id="pane-modulos" role="tabpanel">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="input-group" style="max-width:380px;">
      <span class="input-group-text">Buscar</span>
      <input type="text" class="form-control" id="buscarModulo" placeholder="Slug del módulo">
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalModulo" onclick="abrirModalModulo()">Nuevo módulo</button>
  </div>
  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-hover table-brand align-middle m-0">
        <thead>
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
<div class="modal fade" id="modalPersona" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPersona">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloPersona">Nueva persona</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="personaID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" id="personaNombre" required></div>
            <div class="col-md-6"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="apaterno" id="personaApaterno" required></div>
            <div class="col-md-6"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="amaterno" id="personaAmaterno" required></div>
            <div class="col-md-6"><label class="form-label">No. empleado</label><input type="text" class="form-control" name="numero_empleado" id="personaNumero" required></div>
            <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" name="correo" id="personaCorreo" required></div>
            <div class="col-md-6"><label class="form-label">Dependencia</label><select class="form-select" name="Fk_dependencia" id="personaDep" required></select></div>
            <div class="col-md-6"><label class="form-label">Entidad</label><select class="form-select" name="Fk_entidad" id="personaEnt" required></select></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="personaActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUsuario">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloUsuario">Nuevo usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="usuarioID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Persona</label><select class="form-select" name="Fk_persona" id="usuarioPersona" required></select></div>
            <div class="col-md-6"><label class="form-label">Cuenta</label><input type="text" class="form-control" name="cuenta" id="usuarioCuenta" required></div>
            <div class="col-md-6">
              <label class="form-label">Nivel</label>
              <select class="form-select" name="nivel" id="usuarioNivel" required>
                <option value="0">Enlace externo (0)</option>
                <option value="1">Enlace local (1)</option>
                <option value="2">General (2)</option>
                <option value="3">Admin (3)</option>
                <option value="4">Supersu (4)</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Contraseña</label><input type="password" class="form-control" name="contrasenia" id="usuarioPass" placeholder="••••••"><small class="text-muted">Déjalo vacío para no cambiarla al editar.</small></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="usuarioActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPermiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPermiso">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloPermiso">Nuevo permiso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="permisoID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Usuario</label><select class="form-select" name="Fk_usuario" id="permUsuario" required></select></div>
            <div class="col-md-6"><label class="form-label">Módulo</label><select class="form-select" name="Fk_modulo" id="permModulo" required></select></div>
            <div class="col-md-6"><label class="form-label">Entidad</label><select class="form-select" name="FK_entidad" id="permEntidad" ></select></div>
            <div class="col-md-6"><label class="form-label">Bus</label><select class="form-select" name="FK_bus" id="permBus" ></select></div>
            <div class="col-md-6"><label class="form-label">Acción</label><select class="form-select" name="accion" id="permAccion" ><option>CREATE</option><option>READ</option><option>UPDATE</option><option>DELETE</option></select></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="permActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
          <div class="mt-2 small text-muted">La combinación usuario+módulo+acción+entidad+bus debe ser única.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Modal MÓDULO -->
<div class="modal fade" id="modalModulo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formModulo">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModulo">Nuevo módulo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="moduloID">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label">Descripción (slug)</label>
              <input type="text" class="form-control" name="descripcion" id="moduloDesc" placeholder="rnl_licencias" required>
              <small class="text-muted">Usa minúsculas, sin espacios ni acentos (ej. <code>mapa_general</code>).</small>
            </div>
            <div class="col-12">
              <label class="form-label">Activo</label>
              <select class="form-select" name="activo" id="moduloActivo">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<!-- Módulo JS externo -->
<script src="<?= dirname($_SERVER['PHP_SELF']) ?>/usuarios.js?v=<?= time() ?>"></script>

<!-- Sistema de registro de vistas en bitácora -->
<script src="../../assets/js/bitacora_tracker.js"></script>

<script>
  // Arranque robusto: si el DOM ya está listo, inicializa; si no, espera.
  if (document.readyState !== 'loading') {
    window.initUsuarios && window.initUsuarios();
  } else {
    document.addEventListener('DOMContentLoaded', () => {
      window.initUsuarios && window.initUsuarios();
    });
  }
</script>
