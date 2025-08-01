<style>
  .bg-primary {
    --bs-bg-opacity: 1;
    background-color: rgb(113 36 36) !important;
}

#guardadoExitoAnimado {
  transition: opacity 0.5s ease;
  opacity: 1;
}
#guardadoExitoAnimado.oculto {
  opacity: 0;
}
</style>

<!-- Modal de Registro -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formRegistro">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="ID">

          <fieldset class="border p-2 mb-3">
            <legend class="w-auto fs-6">Ubicación</legend>
            <div class="row">
              <div class="col-md-4">
                <label>Dependencia</label>
<select name="Fk_dependencia" id="Fk_dependencia" class="form-select" required></select>
              </div>
              <div class="col-md-4">
                <label>Entidad</label>
                <select class="form-select" name="Fk_entidad" required></select>
              </div>
              <div class="col-md-4">
                <label>Bus</label>
                <select class="form-select" name="Fk_bus" required></select>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-2 mb-3">
            <legend class="w-auto fs-6">Tecnología</legend>
            <div class="row">
              <div class="col-md-4">
                <label>Engine</label>
                <select class="form-select" name="Fk_engine" required></select>
              </div>
              <div class="col-md-4">
                <label>Versión</label>
                <select class="form-select" name="Fk_version" required></select>
              </div>
              <div class="col-md-4">
                <label>Categoría</label>
                <select class="form-select" name="Fk_categoria" required></select>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-2 mb-3">
            <legend class="w-auto fs-6">Estatus</legend>
            <div class="row">
              <div class="col-md-4">
                <label>Estatus</label>
                <select class="form-select" name="Fk_estado_bus" required></select>
              </div>
              <div class="col-md-4">
                <label>Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio">
              </div>
              <div class="col-md-4">
                <label>Fecha Migración</label>
                <input type="date" class="form-control" name="fecha_migracion">
              </div>
              <div class="col-md-4 mt-2">
                <label>Avance (%)</label>
                <input type="number" class="form-control" name="avance" min="0" max="100" required>
              </div>
            </div>
          </fieldset>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
