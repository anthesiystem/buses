<!-- modal.php - Modal de registro -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content modal-modern">
      <form id="formRegistro" method="post" action="actions.php">
        <div class="modal-header">
          <h5 class="modal-title">Registro</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="ID" id="ID">

          <!-- Ubicación -->
          <fieldset class="fieldset-card mb-3">
            <legend>Ubicación</legend>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Dependencia</label>
                <select class="form-select" name="Fk_dependencia">
                  <option value="">— Sin dependencia —</option>
                  <?php foreach ($dependencias as $d): ?>
                    <option value="<?= (int)$d['ID'] ?>"><?= h($d['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="help-inline">Opcional</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Entidad</label>
                <select class="form-select" name="Fk_entidad" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($entidades as $e): ?>
                    <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Bus</label>
                <select class="form-select" name="Fk_bus">
                  <option value="">—</option>
                  <?php foreach ($buses as $b): ?>
                    <option value="<?= (int)$b['ID'] ?>"><?= h($b['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <!-- Tecnología -->
          <fieldset class="fieldset-card mb-3">
            <legend>Tecnología</legend>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Motor Base</label>
                <select class="form-select" name="Fk_motor_base" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($engines as $en): ?>
                    <option value="<?= (int)$en['ID'] ?>"><?= h($en['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tecnología (versión - descripción)</label>
                <select class="form-select" name="Fk_tecnologia" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($tecnologias as $t): ?>
                    <option value="<?= (int)$t['ID'] ?>"><?= h($t['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <!-- Estatus -->
          <fieldset class="fieldset-card">
            <legend>Estatus</legend>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Estatus</label>
                <select class="form-select" name="Fk_estado_bus" id="Fk_estado_bus" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($estatuses as $e): ?>
                    <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" max="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha Migración</label>
                <input type="date" class="form-control" name="fecha_migracion" max="<?= date('Y-m-d') ?>">
                <div class="help-inline" id="hintFmig">Opcional según estatus</div>
              </div>

              <div class="col-md-6 mt-2" id="wrapEtapa">
                <label class="form-label">Etapa</label>
                <select class="form-select" name="Fk_etapa" id="Fk_etapa">
                  <option value="">—</option>
                  <?php foreach ($etapas as $et): ?>
                    <option value="<?= (int)$et['ID'] ?>" data-avance="<?= (int)$et['avance'] ?>">
                      <?= h($et['descripcion']) ?> (<?= (int)$et['avance'] ?>%)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text" id="helperEtapa">Seleccione una etapa para ver su porcentaje.</div>
              </div>

              <div class="col-md-6 mt-2">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="Fk_categoria" id="Fk_categoria" required>
                  <option value="">Seleccione</option>
                  <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['ID'] ?>"><?= h($c['descripcion']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
