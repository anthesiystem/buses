<form id="formRegistro">
  <input type="hidden" name="ID" id="ID">

  <div class="row">
    <div class="col-md-4">
      <label for="Fk_dependencia">Dependencia</label>
      <select name="Fk_dependencia" id="Fk_dependencia" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label for="Fk_entidad">Entidad</label>
      <select name="Fk_entidad" id="Fk_entidad" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label for="Fk_bus">Bus</label>
      <select name="Fk_bus" id="Fk_bus" class="form-select"></select>
    </div>
  </div>

  <div class="row mt-2">
    <div class="col-md-4">
      <label for="Fk_engine">Engine</label>
      <select name="Fk_engine" id="Fk_engine" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label for="Fk_version">Versión</label>
      <select name="Fk_version" id="Fk_version" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label for="Fk_estado_bus">Estatus</label>
      <select name="Fk_estado_bus" id="Fk_estado_bus" class="form-select" required></select>
    </div>
  </div>

  <div class="row mt-2">
    <div class="col-md-4">
      <label for="Fk_categoria">Categoría</label>
      <select name="Fk_categoria" id="Fk__categoria" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label for="fecha_inicio">Fecha Inicio</label>
      <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
    </div>
    <div class="col-md-4">
      <label for="fecha_migracion">Fecha Migración</label>
      <input type="date" name="fecha_migracion" id="fecha_migracion" class="form-control">
    </div>
  </div>

  <div class="row mt-2">
    <div class="col-md-4">
      <label for="avance">Avance (%)</label>
      <input type="number" name="avance" id="avance" class="form-control" min="0" max="100" required>
    </div>
  </div>
</form>
