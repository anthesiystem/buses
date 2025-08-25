<?php
// Etapa actual (texto + color HEX de tu mapa)
$etDesc  = '';
foreach ($etapas as $e) if ((int)$e['ID'] === (int)$etapaActualId) { $etDesc = $e['descripcion']; break; }
$etColor = etapa_color_hex($etDesc);
$etText  = etapa_text_color($etColor);
?>

<form id="formComentario"
      method="post"
      action="sections/lineadetiempo/guardar_comentario.php"
      onsubmit="return guardarComentario(event, this)"
      class="card shadow-sm border-0 mb-3">

  <input type="hidden" name="Fk_registro" value="<?= (int)$idRegistro ?>">
  <input type="hidden" name="FK_etapa"    value="<?= (int)$etapaActualId ?>">

  <div class="card-body">
    <div class="row g-3 align-items-start">
      <!-- Encabezado -->
      <div class="col-md-4">
        <label class="form-label">Encabezado</label>
        <input type="text" name="encabezado" class="form-control" maxlength="45" data-max="45" required>
        <small class="text-muted d-block text-end" data-count="encabezado">0/45</small>
      </div>

      <!-- Comentario -->
      <div class="col-md-5">
        <label class="form-label">Comentario</label>
        <textarea name="comentario" class="form-control" rows="3" maxlength="500" data-max="500" required></textarea>
        <small class="text-muted d-block text-end" data-count="comentario">0/500</small>
      </div>

      <!-- Etapa (sólo lectura, con color HEX) -->
      <div class="col-md-3">
        <label class="form-label">Etapa</label>
        <div class="form-control d-flex align-items-center" style="height: 38px;">
          <span class="badge" style="background: <?= h($etColor) ?>; color: <?= h($etText) ?>;">
            <?= h($etDesc ?: 'Sin etapa') ?>
          </span>
        </div>
      </div>

      <!-- Etiqueta (radios tipo “píldora”) -->
      <div class="col-12">
        <label class="form-label">Etiqueta</label>
        <div class="d-flex flex-wrap align-items-center gap-2">

          <input class="btn-check" type="radio" name="color" id="etqUrgente"     value="bg-danger"   data-label="URGENTE"     autocomplete="off">
          <label class="btn btn-outline-danger btn-sm" for="etqUrgente">URGENTE</label>

          <input class="btn-check" type="radio" name="color" id="etqPrioritario" value="bg-warning"  data-label="PRIORITARIO" autocomplete="off">
          <label class="btn btn-outline-warning btn-sm" for="etqPrioritario">PRIORITARIO</label>

          <input class="btn-check" type="radio" name="color" id="etqImportante"  value="bg-primary"  data-label="IMPORTANTE"  autocomplete="off">
          <label class="btn btn-outline-primary btn-sm" for="etqImportante">IMPORTANTE</label>

          <input class="btn-check" type="radio" name="color" id="etqDesfasado"   value="bg-secondary" data-label="DESFASADO"  autocomplete="off">
          <label class="btn btn-outline-secondary btn-sm" for="etqDesfasado">DESFASADO</label>

          <input class="btn-check" type="radio" name="color" id="etqSeguimiento" value="bg-success"   data-label="SEGUIMIENTO" autocomplete="off" checked>
          <label class="btn btn-outline-success btn-sm" for="etqSeguimiento">SEGUIMIENTO</label>

          <!-- Preview -->
          <span id="pillEtiqueta" class="badge bg-success ms-1">SEGUIMIENTO</span>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-success px-4" type="submit" <?= $etapaActualId ? '' : 'disabled' ?>>Guardar</button>
      <?php if (!$etapaActualId): ?>
        <small class="text-danger ms-2 align-self-center">Asigna primero una etapa al registro.</small>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
// Este script queda “pegado” al modal y se ejecuta siempre (aunque se recargue por innerHTML)
(() => {
  const root = (document.currentScript && document.currentScript.closest('.modal-content')) || document;

  // 1) Preview de etiqueta (lee el radio seleccionado)
  const radios = root.querySelectorAll('input[name="color"].btn-check');
  const pill   = root.querySelector('#pillEtiqueta');
  function syncPill() {
    const r = [...radios].find(x => x.checked);
    if (!r || !pill) return;
    pill.className = 'badge ' + r.value;
    pill.textContent = r.getAttribute('data-label') || r.nextElementSibling?.textContent?.trim() || '';
  }
  radios.forEach(r => r.addEventListener('change', syncPill));
  syncPill();

  // 2) Contadores de caracteres
  const countable = root.querySelectorAll('[data-max]');
  countable.forEach(el => {
    const max = parseInt(el.getAttribute('data-max'),10)||0;
    const label = el.name ? root.querySelector(`[data-count="${el.name}"]`) : null;
    const update = () => { if (label) label.textContent = `${el.value.length}/${max}`; };
    el.addEventListener('input', update);
    update();
  });
})();
</script>
