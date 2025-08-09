<div class="modal fade" id="modalBus" tabindex="-1" aria-labelledby="modalBusLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form id="formBus" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalBusLabel">Agregar / Editar Bus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">

          <input type="hidden" name="ID" id="ID">

          <div class="col-md-6">
            <label for="descripcion" class="form-label">Descripción</label>
            <input type="text" class="form-control" id="descripcion" name="descripcion" required>
          </div>

          <div class="col-md-6">
            <label for="imagen" class="form-label">Imagen (.png, .jpg)</label>
            <input type="file" class="form-control" id="imagen" name="imagen" accept=".png,.jpg,.jpeg">
            <small class="text-muted">Se guardará en /icons/</small>
          </div>

          <div class="col-md-4">
            <label for="color_implementado" class="form-label">Color Implementado</label>
            <input type="color" class="form-control form-control-color" id="color_implementado" name="color_implementado">
          </div>

          <div class="col-md-4">
            <label for="color_sin_implementar" class="form-label">Color Sin Implementar</label>
            <input type="color" class="form-control form-control-color" id="color_sin_implementar" name="color_sin_implementar">
          </div>

          <div class="col-md-4">
            <label for="pruebas" class="form-label">Color Pruebas</label>
            <input type="color" class="form-control form-control-color" id="pruebas" name="pruebas">
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>
