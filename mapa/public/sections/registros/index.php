<?php
// index.php - Archivo principal modular
require_once 'actions.php'; // Maneja las acciones POST
require_once 'catalogos.php'; // Obtiene los datos de catálogos
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <?php include 'styles.php'; ?>
</head>

<body class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title m-0">Registros</h1>
    <button class="btn btn-brand" onclick="abrirModal()"><i class="bi bi-plus-lg me-2"></i>Agregar</button>
  </div>

  <!-- Filtros -->
  <div class="row g-2 mb-3">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Estado</label>
      <select id="filtroEstado" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($estatuses as $e): ?>
          <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Entidad</label>
      <select id="filtroEntidad" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($entidades as $ent): ?>
          <option value="<?= (int)$ent['ID'] ?>"><?= h($ent['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Categoría</label>
      <select id="filtroCategoria" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= (int)$cat['ID'] ?>"><?= h($cat['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Bus</label>
      <select id="filtroBus" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($buses as $bus): ?>
          <option value="<?= (int)$bus['ID'] ?>"><?= h($bus['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Engine</label>
      <select id="filtroEngine" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($engines as $engine): ?>
          <option value="<?= (int)$engine['ID'] ?>"><?= h($engine['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Tecnología</label>
      <select id="filtroTecnologia" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($tecnologias as $tec): ?>
          <option value="<?= (int)$tec['ID'] ?>"><?= h($tec['descripcion']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
      <label class="form-label small">Etapa</label>
      <select id="filtroEtapa" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($etapas as $etapa): ?>
          <option value="<?= (int)$etapa['ID'] ?>"><?= h($etapa['descripcion']) ?> (<?= (int)$etapa['avance'] ?>%)</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-card">
    <div class="table-responsive">
      <table id="tablaReg" class="table table-hover table-brand align-middle m-0">
        <thead>
          <tr>
            <th style="width:42px">
              <input class="form-check-input" type="checkbox" id="checkAll" title="Seleccionar todo">
            </th>
            <th>ID</th>
            <th>Entidad</th>
            <th class="col-sm-hide">Dependencia</th>
            <th>Bus</th>
            <th>Engine</th>
            <th class="col-sm-hide">Tecnología</th>
            <th>Estado</th>
            <th>Etapa / Avance</th>
            <th class="col-sm-hide">Inicio</th>
            <th class="col-sm-hide">Migración</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyReg">
          <!-- Se llenará dinámicamente -->
        </tbody>
      </table>

      <!-- Paginación -->
      <div id="paginacion" class="d-flex align-items-center justify-content-between gap-2 p-3 border-top">
        <div>
          <span id="rangeInfo" class="small text-muted">Mostrando 0–0 de 0</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button id="btnFirst" class="btn btn-outline-secondary btn-sm" title="Primera">&laquo;</button>
          <button id="btnPrev"  class="btn btn-outline-secondary btn-sm" title="Anterior">&lsaquo;</button>
          <span id="pageInfo" class="small text-muted mx-3">Página 1 / 1</span>
          <button id="btnNext"  class="btn btn-outline-secondary btn-sm" title="Siguiente">&rsaquo;</button>
          <button id="btnLast"  class="btn btn-outline-secondary btn-sm" title="Última">&raquo;</button>
        </div>
      </div>
    </div>
  </div>

  <?php include 'modal.php'; ?>

  <!-- Overlays -->
  <div id="cargando" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.8); z-index:2000; backdrop-filter: blur(2px);">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
      <img id="loadingImg1" src="../../img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
      <div class="mt-2 fw-semibold">Espere un momento...</div>
    </div>
  </div>

  <div id="guardadoExitoAnimado" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2050; background:rgba(255,255,255,0.95); padding:30px 40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.2); text-align:center;">
    <div style="font-size:60px; color:green;">
      <img id="loadingImg2" src="../../img/escudospiner.gif" style="height: 180px; width: 180px;" alt="Cargando">
    </div>
    <div style="font-size:18px; margin-top:10px;">Guardado exitosamente</div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="pagination.js"></script>
  <script src="modal.js"></script>

  <!-- Sistema de registro de vistas en bitácora -->
  <script src="../../assets/js/bitacora_tracker.js"></script>

</body>
</html>
