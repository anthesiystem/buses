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
  <!-- Font Awesome -->
  <link href="https://cdnjs.clou                  <select class="form-select form-select-lg" name="accion" id="loteAccion">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
background:var(--header-bg);
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
    
    /* Estilos para permisos por lotes */
    .badge-soft { 
      background: var(--badge-bg); 
      color: var(--ink); 
      border: 1px solid #e5e7eb; 
      font-weight: 600; 
    }
    
    .form-check-input:checked {
      background-color: var(--brand);
      border-color: var(--brand);
    }
    
    .modal-lg .modal-body {
      max-height: 70vh;
      overflow-y: auto;
    }
    
    .switch-toggle {
      width: 38px;
      height: 20px;
    }
    
    #entidadesContainer, #busesContainer {
      max-height: 150px;
      overflow-y: auto;
    }
    
    .combo-badge {
      font-size: 9px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .combo-badge:hover {
      transform: scale(1.05);
    }
    
    /* Mejoras para el modal de lotes */
    .modal-xl {
      max-width: 95%;
    }
    
    .form-check-input-lg {
      width: 1.5rem;
      height: 1.5rem;
    }
    
    .card {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border: 1px solid #e0e6ed;
    }
    
    .card-header {
      border-bottom: 2px solid #e0e6ed;
    }
    
    .form-select-lg, .form-control-lg {
      padding: 0.75rem 1rem;
      font-size: 1.1rem;
    }
    
    .border-primary {
      border-color: var(--brand) !important;
    }
    
    .border-success {
      border-color: #198754 !important;
    }
    
    .text-primary {
      color: var(--brand) !important;
    }
    
    .bg-primary {
      background-color: var(--brand) !important;
    }
    
    /* Estilos para paginación */
    .btn-outline-secondary:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    
    .btn-outline-secondary:hover:not(:disabled) {
      background-color: var(--brand);
      border-color: var(--brand);
      color: white;
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
<button class="nav-link" id="tab-permisos" data-bs-toggle="tab" data-bs-target="#pane-permisos" type="button" role="tab">Permisos Vistas</button>
</li>
<li class="nav-item" role="presentation">
<button class="nav-link" id="tab-permisos-mapas" data-bs-toggle="tab" data-bs-target="#pane-permisos-mapas" type="button" role="tab">Permisos Mapas</button>
</li>
<li class="nav-item" role="presentation">
<button class="nav-link" id="tab-lotes" data-bs-toggle="tab" data-bs-target="#pane-lotes" type="button" role="tab">
<i class="fas fa-layer-group me-1"></i>Lotes de Permisos
</button>
</li>
</ul><div class="tab-content border-start border-end border-bottom p-3 bg-white rounded-bottom" id="tabUsuariosContent">
<!-- PERSONAS -->
<div class="tab-pane fade show active" id="pane-personas" role="tabpanel">
<div class="d-flex justify-content-end align-items-center mb-2">
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
        
        <!-- Paginación -->
        <div id="paginacionPersonas" class="d-flex align-items-center justify-content-between gap-2 p-3 border-top">
          <div>
            <span id="rangeInfoPersonas" class="small text-muted">Mostrando 0–0 de 0</span>
          </div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <!-- Información de registros -->
            <div class="d-flex align-items-center gap-3">
              <span id="totalInfoPersonas" class="small text-muted">Sin resultados</span>
              
              <!-- Controles de filas por página -->
              <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0">Filas:</label>
                <select id="rowsPerPagePersonas" class="form-select form-select-sm" style="width: auto;">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="15">15</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
                
                <!-- Toggle modo automático -->
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="autoSizePersonas" checked>
                  <label class="form-check-label small text-muted" for="autoSizePersonas">Auto</label>
                </div>
              </div>
            </div>
            
            <!-- Controles de navegación -->
            <div class="d-flex align-items-center gap-2">
              <button id="btnFirstPersonas" class="btn btn-outline-secondary btn-sm" title="Primera página">
                <i class="fas fa-angle-double-left"></i>
              </button>
              <button id="btnPrevPersonas" class="btn btn-outline-secondary btn-sm" title="Página anterior">
                <i class="fas fa-angle-left"></i>
              </button>
              <span id="pageInfoPersonas" class="small text-muted mx-3 fw-medium">Página 1 de 1</span>
              <button id="btnNextPersonas" class="btn btn-outline-secondary btn-sm" title="Página siguiente">
                <i class="fas fa-angle-right"></i>
              </button>
              <button id="btnLastPersonas" class="btn btn-outline-secondary btn-sm" title="Última página">
                <i class="fas fa-angle-double-right"></i>
              </button>
            </div>
          </div>
        </div>
</div>
</div>

<!-- USUARIOS -->
<div class="tab-pane fade" id="pane-usuarios" role="tabpanel">
<div class="d-flex justify-content-end align-items-center mb-2">
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
        
        <!-- Paginación -->
        <div id="paginacionUsuarios" class="d-flex align-items-center justify-content-between gap-2 p-3 border-top">
          <div>
            <span id="rangeInfoUsuarios" class="small text-muted">Mostrando 0–0 de 0</span>
          </div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <!-- Información de registros -->
            <div class="d-flex align-items-center gap-3">
              <span id="totalInfoUsuarios" class="small text-muted">Sin resultados</span>
              
              <!-- Controles de filas por página -->
              <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0">Filas:</label>
                <select id="rowsPerPageUsuarios" class="form-select form-select-sm" style="width: auto;">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="15">15</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
                
                <!-- Toggle modo automático -->
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="autoSizeUsuarios" checked>
                  <label class="form-check-label small text-muted" for="autoSizeUsuarios">Auto</label>
                </div>
              </div>
            </div>
            
            <!-- Controles de navegación -->
            <div class="d-flex align-items-center gap-2">
              <button id="btnFirstUsuarios" class="btn btn-outline-secondary btn-sm" title="Primera página">
                <i class="fas fa-angle-double-left"></i>
              </button>
              <button id="btnPrevUsuarios" class="btn btn-outline-secondary btn-sm" title="Página anterior">
                <i class="fas fa-angle-left"></i>
              </button>
              <span id="pageInfoUsuarios" class="small text-muted mx-3 fw-medium">Página 1 de 1</span>
              <button id="btnNextUsuarios" class="btn btn-outline-secondary btn-sm" title="Página siguiente">
                <i class="fas fa-angle-right"></i>
              </button>
              <button id="btnLastUsuarios" class="btn btn-outline-secondary btn-sm" title="Última página">
                <i class="fas fa-angle-double-right"></i>
              </button>
            </div>
          </div>
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
        
        <!-- Paginación -->
        <div id="paginacionPermisos" class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-top">
          <!-- Información de registros -->
          <div class="d-flex align-items-center gap-3">
            <span id="totalInfoPermisos" class="small text-muted">Sin resultados</span>
            
            <!-- Controles de filas por página -->
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Filas:</label>
              <select id="rowsPerPagePermisos" class="form-select form-select-sm" style="width: auto;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              
              <!-- Toggle modo automático -->
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="autoSizePermisos" checked>
                <label class="form-check-label small text-muted" for="autoSizePermisos">Auto</label>
              </div>
            </div>
          </div>
          
          <!-- Controles de navegación -->
          <div class="d-flex align-items-center gap-2">
            <button id="btnFirstPermisos" class="btn btn-outline-secondary btn-sm" title="Primera página">
              <i class="fas fa-angle-double-left"></i>
            </button>
            <button id="btnPrevPermisos" class="btn btn-outline-secondary btn-sm" title="Página anterior">
              <i class="fas fa-angle-left"></i>
            </button>
            <span id="pageInfoPermisos" class="small text-muted mx-3 fw-medium">Página 1 de 1</span>
            <button id="btnNextPermisos" class="btn btn-outline-secondary btn-sm" title="Página siguiente">
              <i class="fas fa-angle-right"></i>
            </button>
            <button id="btnLastPermisos" class="btn btn-outline-secondary btn-sm" title="Última página">
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PERMISOS MAPAS -->
    <div class="tab-pane fade" id="pane-permisos-mapas" role="tabpanel">
      <!-- Filtros -->
      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <label class="form-label">👤 Usuario</label>
          <select id="filtroUsuarioPermMapa" class="form-select">
            <option value="">Todos los usuarios</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">📋 Módulo</label>
          <select id="filtroModuloPermMapa" class="form-select">
            <option value="">Todos los módulos</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">🏢 Entidad</label>
          <select id="filtroEntidadPermMapa" class="form-select">
            <option value="">Todas las entidades</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">🚌 Bus</label>
          <select id="filtroBusPermMapa" class="form-select">
            <option value="">Todos los buses</option>
          </select>
        </div>
      </div>

      <!-- Barra de herramientas -->
      <div class="d-flex justify-content-end align-items-center mb-3">
        <button class="btn btn-primary" onclick="abrirModalPermisoMapa()">
          <i class="fas fa-plus me-1"></i>Nuevo permiso de mapa
        </button>
      </div>

      <!-- Tabla de permisos de mapas -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="table table-hover table-brand align-middle m-0">
            <thead>
              <tr>
                <th>👤 Usuario</th>
                <th>📋 Módulo</th>
                <th>🏢 Entidad</th>
                <th>🚌 Bus</th>
                <th>⚡ Acción</th>
                <th>📊 Estado</th>
                <th>🔧 Acciones</th>
              </tr>
            </thead>
            <tbody id="tbPermisosMapas"></tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div id="paginacionPermisosMapas" class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-top">
          <!-- Información de registros -->
          <div class="d-flex align-items-center gap-3">
            <span id="totalInfoPermisosMapas" class="small text-muted">Sin resultados</span>
            
            <!-- Controles de filas por página -->
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Filas:</label>
              <select id="rowsPerPagePermisosMapas" class="form-select form-select-sm" style="width: auto;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              
              <!-- Toggle modo automático -->
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="autoSizePermisosMapas" checked>
                <label class="form-check-label small text-muted" for="autoSizePermisosMapas">Auto</label>
              </div>
            </div>
          </div>
          
          <!-- Controles de navegación -->
          <div class="d-flex align-items-center gap-2">
            <button id="btnFirstPermisosMapas" class="btn btn-outline-secondary btn-sm" title="Primera página">
              <i class="fas fa-angle-double-left"></i>
            </button>
            <button id="btnPrevPermisosMapas" class="btn btn-outline-secondary btn-sm" title="Página anterior">
              <i class="fas fa-angle-left"></i>
            </button>
            <span id="pageInfoPermisosMapas" class="small text-muted mx-3 fw-medium">Página 1 de 1</span>
            <button id="btnNextPermisosMapas" class="btn btn-outline-secondary btn-sm" title="Página siguiente">
              <i class="fas fa-angle-right"></i>
            </button>
            <button id="btnLastPermisosMapas" class="btn btn-outline-secondary btn-sm" title="Última página">
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PERMISOS POR LOTES -->
    <div class="tab-pane fade" id="pane-lotes" role="tabpanel">
      <!-- Filtros -->
      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <label class="form-label">👤 Usuario</label>
          <select id="filtroUsuarioLote" class="form-select">
            <option value="">Todos los usuarios</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">📋 Módulo</label>
          <select id="filtroModuloLote" class="form-select">
            <option value="">Todos los módulos</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">🏢 Entidad</label>
          <select id="filtroEntidadLote" class="form-select">
            <option value="">Todas las entidades</option>
            <option value="ALL">Solo "Todas"</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">🚌 Bus</label>
          <select id="filtroBusLote" class="form-select">
            <option value="">Todos los buses</option>
            <option value="ALL">Solo "Todos"</option>
          </select>
        </div>
      </div>

      <!-- Barra de herramientas -->
      <div class="d-flex justify-content-end align-items-center mb-3">
        <button class="btn btn-primary" onclick="abrirModalLote()">
          <i class="fas fa-plus me-1"></i>Nuevo lote de permisos
        </button>
      </div>

      <!-- Tabla de lotes -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="table table-hover table-brand align-middle m-0">
            <thead>
              <tr>
                <th>👤 Usuario</th>
                <th>📋 Módulo</th>
                <th>⚡ Acción</th>
                <th>📊 Estado</th>
                <th class="col-sm-hide">🔗 Combinaciones</th>
                <th class="col-sm-hide">🏷️ Token</th>
                <th>⚙️ Acciones</th>
              </tr>
            </thead>
            <tbody id="tbLotes">
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                  Cargando grupos de permisos...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div id="paginacionLotes" class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-top">
          <!-- Información de registros -->
          <div class="d-flex align-items-center gap-3">
            <span id="totalInfoLotes" class="small text-muted">Sin resultados</span>
            
            <!-- Controles de filas por página -->
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Filas:</label>
              <select id="rowsPerPageLotes" class="form-select form-select-sm" style="width: auto;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              
              <!-- Toggle modo automático -->
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="autoSizeLotes" checked>
                <label class="form-check-label small text-muted" for="autoSizeLotes">Auto</label>
              </div>
            </div>
          </div>
          
          <!-- Controles de navegación -->
          <div class="d-flex align-items-center gap-2">
            <button id="btnFirstLotes" class="btn btn-outline-secondary btn-sm" title="Primera página">
              <i class="fas fa-angle-double-left"></i>
            </button>
            <button id="btnPrevLotes" class="btn btn-outline-secondary btn-sm" title="Página anterior">
              <i class="fas fa-angle-left"></i>
            </button>
            <span id="pageInfoLotes" class="small text-muted mx-3 fw-medium">Página 1 de 1</span>
            <button id="btnNextLotes" class="btn btn-outline-secondary btn-sm" title="Página siguiente">
              <i class="fas fa-angle-right"></i>
            </button>
            <button id="btnLastLotes" class="btn btn-outline-secondary btn-sm" title="Última página">
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Estado vacío -->
      <div id="estadoVacioLotes" class="text-center py-5" style="display:none;">
        <div class="mb-3" style="font-size:3rem;">📝</div>
        <h5 class="text-muted">Aún no hay grupos de permisos</h5>
        <p class="text-muted">Crea el primero con "Nuevo lote de permisos"</p>
</div>
</div>
</div>
<div id="diag" class="alert alert-info d-none mt-3" style="white-space:pre-wrap"></div>

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
<div class="col-md-6"><label class="form-label">Acción</label><select class="form-select" name="accion" id="permAccion" ><option value="read">Leer</option></select></div>
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


</div>
</div>

<!-- Modal PERMISOS MAPAS -->
<div class="modal fade" id="modalPermisoMapa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPermisoMapa">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloPermisoMapa">🗺️ Nuevo permiso de mapa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="permisoMapaID">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">👤 Usuario</label>
              <select class="form-select" name="Fk_usuario" id="permisoMapaUsuario" required>
                <option value="">Seleccione un usuario</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">📋 Módulo de mapa</label>
              <select class="form-select" name="Fk_modulo" id="permisoMapaModulo" required>
                <option value="">Seleccione un módulo</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">🏢 Entidad</label>
              <select class="form-select" name="FK_entidad" id="permisoMapaEntidad">
                <option value="">Todas las entidades</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">🚌 Bus</label>
              <select class="form-select" name="FK_bus" id="permisoMapaBus">
                <option value="">Todos los buses</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">⚡ Acción</label>
              <select class="form-select" name="accion" id="permisoMapaAccion" required>
                <option value="">Seleccione una acción</option>
                <option value="read">👁️ Leer</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">📊 Estado</label>
              <select class="form-select" name="activo" id="permisoMapaActivo">
                <option value="1">✅ Activo</option>
                <option value="0">❌ Inactivo</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">💾 Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal LOTES DE PERMISOS -->
<div class="modal fade" id="modalLote" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formLote">
        <div class="modal-header bg-primary text-white">
          <h4 class="modal-title d-flex align-items-center" id="tituloLote">
            <i class="fas fa-layer-group me-2"></i>
            <span>✨ Nuevo lote de permisos</span>
          </h4>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
          <input type="hidden" name="action" id="loteAction" value="crear">
          <input type="hidden" name="group_token" id="loteToken">
          
          <!-- Información básica -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="card-title mb-0">
                <i class="fas fa-info-circle text-primary me-2"></i>
                Información básica del lote
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-bold">
                    <i class="fas fa-user text-primary me-1"></i>
                    Usuario *
                  </label>
                  <select class="form-select form-select-lg" name="Fk_usuario" id="loteUsuario" required>
                    <option value="">👤 Seleccionar usuario</option>
                  </select>
                  <small class="text-muted">Usuario al que se aplicarán los permisos</small>
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-bold">
                    <i class="fas fa-puzzle-piece text-success me-1"></i>
                    Módulo *
                  </label>
                  <select class="form-select form-select-lg" name="Fk_modulo" id="loteModulo" required>
                    <option value="">📋 Seleccionar módulo</option>
                  </select>
                  <small class="text-muted">Sistema o módulo de la aplicación</small>
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-bold">
                    <i class="fas fa-bolt text-warning me-1"></i>
                    Acción
                  </label>
                  <select class="form-select form-select-lg" name="accion" id="loteAccion">
                    <option value="READ">👁️ Leer</option>
                  </select>
                  <small class="text-muted">Tipo de acción permitida</small>
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-bold">
                    <i class="fas fa-toggle-on text-info me-1"></i>
                    Estado inicial
                  </label>
                  <select class="form-select form-select-lg" name="activo" id="loteActivo">
                    <option value="1">✅ Activo</option>
                    <option value="0">❌ Inactivo</option>
                  </select>
                  <small class="text-muted">Estado por defecto del lote</small>
                </div>
              </div>
              
              <div class="row g-3 mt-2">
                <div class="col-12">
                  <label class="form-label fw-bold">
                    <i class="fas fa-tag text-secondary me-1"></i>
                    Token de grupo
                  </label>
                  <input type="text" class="form-control form-control-lg bg-light" id="loteTokenDisplay" readonly placeholder="🏷️ Se generará automáticamente al guardar">
                  <small class="text-muted">Identificador único del grupo de permisos</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Selección de alcance -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="card-title mb-0">
                <i class="fas fa-sitemap text-primary me-2"></i>
                Alcance de aplicación
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <!-- Entidades -->
                <div class="col-lg-6">
                  <div class="border border-2 border-primary rounded-3 p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                      <i class="fas fa-building text-primary me-2" style="font-size: 1.2rem;"></i>
                      <h6 class="mb-0 fw-bold">Entidades *</h6>
                    </div>
                    
                    <div class="form-check mb-3 p-3 bg-light rounded">
                      <input class="form-check-input form-check-input-lg" type="checkbox" value="ALL" id="entidadAll">
                      <label class="form-check-label fw-bold fs-5" for="entidadAll">
                        <i class="fas fa-globe text-success me-2"></i>
                        Todas las entidades
                      </label>
                      <div class="text-muted small mt-1">Aplicar permisos a todas las entidades existentes y futuras</div>
                    </div>
                    
                    <div class="border-top pt-3">
                      <label class="fw-semibold text-muted mb-2">
                        <i class="fas fa-list me-1"></i>
                        O seleccionar entidades específicas:
                      </label>
                      <div id="entidadesEspecificas" style="max-height: 200px; overflow-y: auto;" class="border rounded p-2 bg-white">
                        <!-- Se cargarán dinámicamente -->
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Buses -->
                <div class="col-lg-6">
                  <div class="border border-2 border-success rounded-3 p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                      <i class="fas fa-bus text-success me-2" style="font-size: 1.2rem;"></i>
                      <h6 class="mb-0 fw-bold">Buses *</h6>
                    </div>
                    
                    <div class="form-check mb-3 p-3 bg-light rounded">
                      <input class="form-check-input form-check-input-lg" type="checkbox" value="ALL" id="busAll">
                      <label class="form-check-label fw-bold fs-5" for="busAll">
                        <i class="fas fa-globe text-success me-2"></i>
                        Todos los buses
                      </label>
                      <div class="text-muted small mt-1">Aplicar permisos a todos los buses existentes y futuros</div>
                    </div>
                    
                    <div class="border-top pt-3">
                      <label class="fw-semibold text-muted mb-2">
                        <i class="fas fa-list me-1"></i>
                        O seleccionar buses específicos:
                      </label>
                      <div id="busesEspecificos" style="max-height: 200px; overflow-y: auto;" class="border rounded p-2 bg-white">
                        <!-- Se cargarán dinámicamente -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Matriz de combinaciones (solo visible cuando se seleccionan específicos) -->
          <div id="matrizContainer" style="display:none;">
            <div class="card">
              <div class="card-header bg-warning bg-opacity-10">
                <h6 class="card-title mb-0">
                  <i class="fas fa-th text-warning me-2"></i>
                  Matriz de combinaciones específicas
                </h6>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle me-2"></i>
                  <div>
                    <strong>Configuración avanzada:</strong> Puedes activar o desactivar permisos específicos para cada combinación Entidad × Bus.
                    Los switches en verde indican permisos activos.
                  </div>
                </div>
                
                <div class="table-responsive" style="max-height:400px; border: 1px solid #dee2e6; border-radius: 0.5rem;">
                  <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark sticky-top">
                      <tr>
                        <th class="text-center" style="width: 35%;">
                          <i class="fas fa-building me-1"></i>
                          Entidad
                        </th>
                        <th class="text-center" style="width: 35%;">
                          <i class="fas fa-bus me-1"></i>
                          Bus
                        </th>
                        <th class="text-center" style="width: 30%;">
                          <i class="fas fa-toggle-on me-1"></i>
                          Estado del Permiso
                        </th>
                      </tr>
                    </thead>
                    <tbody id="matrizCombinaciones">
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer bg-light d-flex justify-content-between">
          <div>
            <button type="button" class="btn btn-outline-danger" id="btnEliminarLote" style="display:none;" onclick="eliminarLote()">
              <i class="fas fa-trash me-2"></i>Eliminar lote completo
            </button>
          </div>
          <div>
            <button type="button" class="btn btn-secondary btn-lg me-2" data-bs-dismiss="modal">
              <i class="fas fa-times me-2"></i>Cancelar
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-save me-2"></i>Guardar lote de permisos
            </button>
          </div>
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

<!-- Sistema de paginación -->
<script src="<?= dirname($_SERVER['PHP_SELF']) ?>/pagination_usuarios.js?v=<?= time() ?>"></script>

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