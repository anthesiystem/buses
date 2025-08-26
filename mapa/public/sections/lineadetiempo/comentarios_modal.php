<?php
// /mapa/public/sections/lineadetiempo/comentarios_modal.php
session_start();
require_once '../../../server/config.php';
require_once __DIR__ . '/helpers.php';

$idRegistro = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idRegistro <= 0) {
  echo '<div class="modal-content"><div class="modal-body">ID inválido.</div></div>'; exit;
}

/* ===== Registro + etapa actual ===== */
$qReg = $pdo->prepare("
  SELECT r.ID, r.Fk_etapa,
         e.descripcion AS Entidad, d.descripcion AS Dependencia,
         b.descripcion AS Bus, t.descripcion AS Tecnologia,
         et.descripcion AS EtapaActual, et.avance AS AvanceActual
  FROM registro r
  LEFT JOIN entidad    e  ON e.ID = r.Fk_entidad
  LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia
  LEFT JOIN bus        b  ON b.ID = r.Fk_bus
  LEFT JOIN tecnologia t  ON t.ID = r.Fk_tecnologia
  LEFT JOIN etapa      et ON et.ID = r.Fk_etapa
  WHERE r.ID = ?
");
$qReg->execute([$idRegistro]);
$reg = $qReg->fetch(PDO::FETCH_ASSOC);
if (!$reg) {
  echo '<div class="modal-content"><div class="modal-body">Registro no encontrado.</div></div>'; exit;
}

/* ===== Etapas (stepper & puntos) ===== */
$etapas = $pdo->query("SELECT ID, descripcion, orden, avance FROM etapa ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
$totalEtapas   = max(1, count($etapas));
$etapaActualId = (int)($reg['Fk_etapa'] ?? 0);
$etapaActualTx = (string)($reg['EtapaActual'] ?? '');
$ordenActual   = 0;                    // orden de la etapa actual
$porc          = (int)($reg['AvanceActual'] ?? 0); // % directo de etapa.avance si existe

foreach ($etapas as $e) {
  if ((int)$e['ID'] === $etapaActualId) {
    $ordenActual = (int)($e['orden'] ?? 0);
    if (!$porc && isset($e['avance'])) $porc = (int)$e['avance'];
    break;
  }
}
if (!$porc) {
  // Fallback: porcentaje por posición (si no hay 'avance' definido)
  $porc = ($totalEtapas > 1 && $ordenActual > 0)
    ? round((($ordenActual - 1) / ($totalEtapas - 1)) * 100)
    : 0;
}

$barFill = etapa_color_hex($etapaActualTx);
$barTxt  = etapa_text_color($barFill);

/* ===== Comentarios ===== */
$qC = $pdo->prepare("
  SELECT c.ID, c.encabezado, c.comentario, c.color,
         u.cuenta AS usuario, rc.fecha_enlace AS fecha,
         e.descripcion AS etapa, rc.FK_etapa AS etapa_id
  FROM registro_comentario rc
  JOIN comentario c ON c.ID = rc.FK_comentario
  JOIN usuario    u ON u.ID = c.FK_usuario
  LEFT JOIN etapa e ON e.ID = rc.FK_etapa
  WHERE rc.FK_registro = ? AND rc.Activo=b'1' AND c.activo=b'1'
  ORDER BY rc.fecha_enlace DESC
");
$qC->execute([$idRegistro]);
$comentarios = $qC->fetchAll(PDO::FETCH_ASSOC);

/* ===== Bases de URL ===== */
$ltBase      = rtrim(dirname($_SERVER['PHP_SELF']), '/'); // carpeta del modal
$defaultIcon = lt_default_icon_url();                      // default.png existente
?>
<link rel="stylesheet" href="<?= h($ltBase) ?>/comentarios_ui.css">
<script>window.LT_BASE = <?= json_encode($ltBase, JSON_UNESCAPED_SLASHES) ?>;</script>

<div class="modal-content">
  <div class="header p-3 border-bottom">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="m-0"> <?= h($reg['Entidad'] ?? '') ?> · <?= h($reg['Dependencia'] ?? '') ?> · <?= h($reg['Bus'] ?? '') ?> · TEC: <?= h($reg['Tecnologia'] ?? '') ?></h5>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cerrar</button>
    </div>

    <!-- Barra de progreso -->
    <div class="stagebar mt-2" style="--value: <?= (int)$porc ?>%; --bar-fill: <?= h($barFill) ?>; --bar-text: <?= h($barTxt) ?>;">
      <div class="fill"><?= (int)$porc ?>%</div>
    </div>
  </div>

  <div class="layout" style="display:grid; grid-template-columns: 360px 1fr; gap:18px; height: calc(100vh - 150px); overflow: hidden;">
    <!-- ===== Formulario ===== -->
    <div class="col-left" style="padding:18px; overflow: hidden;">
    <div style="height: 100%;">
      <form id="formComentario" class="card border-0 card-soft p-3" method="post" action="<?= h($ltBase) ?>/guardar_comentario.php" onsubmit="return window.guardarComentario?.(this) ?? true">
        <input type="hidden" name="Fk_registro" value="<?= (int)$idRegistro ?>">
        <input type="hidden" name="FK_etapa"    value="<?= (int)$etapaActualId ?>">

        <div class="d-flex align-items-center justify-content-between rounded-3 px-3 py-2 mb-3"
             style="background: <?= h($barFill) ?>; color: <?= h($barTxt) ?>;">
          <div class="d-flex gap-2 align-items-center">
            <span class="badge rounded-pill" style="background: rgba(0,0,0,.12); color: <?= h($barTxt) ?>;">ETAPA</span>
            <strong><?= h($etapaActualTx ?: 'Sin etapa') ?></strong>
          </div>
          <small><?= (int)$porc ?>% de avance</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Encabezado</label>
          <input type="text" name="encabezado" class="form-control" maxlength="45" required>
          <div class="d-flex justify-content-end"><small class="text-muted counter">0/45</small></div>
        </div>

        <div class="mb-3">
          <label class="form-label">Comentario</label>
          <textarea name="comentario" rows="3" maxlength="500" class="form-control" required></textarea>
          <div class="d-flex justify-content-end"><small class="text-muted counter">0/500</small></div>
        </div>

        <div class="mb-2">
          <label class="form-label">Etiqueta</label><br>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <input class="btn-check" type="radio" name="color" id="etqUrgente"     value="urgente"     data-class="bg-danger"    data-label="URGENTE">
            <label class="btn btn-outline-danger btn-sm" for="etqUrgente">URGENTE</label>

            <input class="btn-check" type="radio" name="color" id="etqPrioritario" value="prioritario" data-class="bg-warning"   data-label="PRIORITARIO">
            <label class="btn btn-outline-warning btn-sm" for="etqPrioritario">PRIORITARIO</label>

            <input class="btn-check" type="radio" name="color" id="etqImportante"  value="importante"  data-class="bg-primary"   data-label="IMPORTANTE">
            <label class="btn btn-outline-primary btn-sm" for="etqImportante">IMPORTANTE</label>

            <input class="btn-check" type="radio" name="color" id="etqDesfasado"   value="desfasado"   data-class="bg-secondary" data-label="DESFASADO">
            <label class="btn btn-outline-secondary btn-sm" for="etqDesfasado">DESFASADO</label>

            <input class="btn-check" type="radio" name="color" id="etqSeguimiento" value="seguimiento" data-class="bg-success"    data-label="SEGUIMIENTO" checked>
            <label class="btn btn-outline-success btn-sm" for="etqSeguimiento">SEGUIMIENTO</label>

            
          </div>
        </div>

        <div class="text-end mt-2">
          <button class="btn btn-success px-4" type="submit">Guardar</button>
        </div>
      </form>
    </div></div>

    <!-- ===== Stepper + Timeline ===== -->
     <div class="col-right" style="padding:18px; display:flex; flex-direction:column; gap:12px; height:100%; overflow-y:auto;">
    <?php
      $publicIcons = lt_public_base_url() . '/icons';
    ?>
    <div class="card-soft p-3 mb-3">
      <div class="stepper-wrap">
        <button id="btnAll" type="button" title="Ver todas las etapas">Todos</button>
        <ol id="barEtapas" class="stepper">
          <?php foreach ($etapas as $e):
            $isCurrent = ((int)$e['ID'] === (int)$etapaActualId);
            $isDone    = ((int)($e['orden'] ?? 0) <= (int)$ordenActual);
            $iconUrl   = etapa_icon_url((string)$e['descripcion']);
          ?>
            <li class="step <?= $isDone ? 'done' : '' ?> <?= $isCurrent ? 'current' : '' ?>"
                data-id="<?= (int)$e['ID'] ?>" title="<?= h($e['descripcion']) ?>">
              <span class="node">
                <img src="<?= h($iconUrl) ?>" alt="<?= h($e['descripcion']) ?>"
                     onerror="this.onerror=null;this.src='<?= h($defaultIcon) ?>'">
              </span>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>

    <div class="timeline-wrap" id="listaComentarios" style="position:relative; padding-left:11px;">
      <div style="content:''; position:absolute; left:16px; top:0; bottom:0; width:3px; background:#eef1f4; border-radius:2px;"></div>

      <?php if (!$comentarios): ?>
        <div class="text-muted text-center p-4">Aún no hay comentarios.</div>
      <?php else: ?>
        <?php foreach ($comentarios as $c): $dot = dotClassFromColor($c['color'] ?? ''); ?>
          <div class="t-item tl-item" data-etapa-id="<?= (int)($c['etapa_id'] ?? 0) ?>" style="position:relative; margin-bottom:14px;">
            <span class="t-dot <?= $dot ?>" style="position:absolute; top:50%; width:14px; height:14px; border-radius:50%; box-shadow:0 0 0 4px #fff;"></span>
            <div class="t-card" style="margin-left:24px; border:1px solid #edf0f3; padding:12px 14px; border-radius:12px; background:#fff; box-shadow:0 8px 16px rgba(0,0,0,.04);">
              <div class="d-flex justify-content-between gap-2 fw-bold">
                <div>
                  <?= h($c['encabezado'] ?? '') ?>
                  <?php if (!empty($c['etapa'])):
                    $cHex = etapa_color_hex($c['etapa']); $cTx = etapa_text_color($cHex); ?>
                    <span class="badge ms-1" style="background:<?= h($cHex) ?>; color:<?= h($cTx) ?>; border-radius:10px;">
                      <?= h($c['etapa']) ?>
                    </span>
                  <?php endif; ?>

                  <?php
                    $raw = strtolower((string)($c['color'] ?? ''));
                    $labelMap = [
                      'urgente' => 'URGENTE', 'bg-danger' => 'URGENTE', 'rojo' => 'URGENTE',
                      'prioritario' => 'PRIORITARIO', 'bg-warning' => 'PRIORITARIO', 'amarillo' => 'PRIORITARIO',
                      'importante' => 'IMPORTANTE', 'bg-primary' => 'IMPORTANTE', 'azul' => 'IMPORTANTE',
                      'desfasado' => 'DESFASADO', 'bg-secondary' => 'DESFASADO', 'gris' => 'DESFASADO',
                      'seguimiento' => 'SEGUIMIENTO', 'bg-success' => 'SEGUIMIENTO', 'verde' => 'SEGUIMIENTO',
                    ];
                    $classMap = [
                      'urgente' => 'bg-danger', 'bg-danger' => 'bg-danger', 'rojo' => 'bg-danger',
                      'prioritario' => 'bg-warning', 'bg-warning' => 'bg-warning', 'amarillo' => 'bg-warning',
                      'importante' => 'bg-primary', 'bg-primary' => 'bg-primary', 'azul' => 'bg-primary',
                      'desfasado' => 'bg-secondary', 'bg-secondary' => 'bg-secondary', 'gris' => 'bg-secondary',
                      'seguimiento' => 'bg-success', 'bg-success' => 'bg-success', 'verde' => 'bg-success',
                    ];
                    $tagLabel = $labelMap[$raw] ?? '';
                    $tagClass = $classMap[$raw] ?? 'bg-success';
                  ?>
                  <?php if ($tagLabel): ?>
                    <span class="badge <?= h($tagClass) ?> ms-1"><?= h($tagLabel) ?></span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small text-end">
                  <?= h(date('d/m/Y H:i', strtotime($c['fecha'] ?? 'now'))) ?><br><?= h($c['usuario'] ?? '') ?>
                </div>
              </div>
              <div class="mt-2 text-secondary"><?= nl2br(h($c['comentario'] ?? '')) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div></div>

<script>
  // Inicializa lo de UI (counters, pill de etiqueta, guardar por fetch, filtro del stepper, etc.)
  if (window.initComentariosModal) {
    window.initComentariosModal(document.currentScript.closest('.modal-content')?.parentElement || document);
  } else {
    // Fallback mínimo para el filtro si aún no cargó comentarios_ui.js
    (function(){
      const bar  = document.getElementById('barEtapas');
      const list = document.getElementById('listaComentarios');
      if (!bar || !list) return;
      document.getElementById('btnAll')?.addEventListener('click', ()=>{
        list.querySelectorAll('.tl-item').forEach(it=> it.style.display = '');
      });
      bar.addEventListener('click', (e)=>{
        const li = e.target.closest('li.step[data-id]');
        if (!li) return;
        const id = String(li.dataset.id);
        list.querySelectorAll('.tl-item').forEach(it=>{
          it.style.display = (String(it.dataset.etapaId || '') === id) ? '' : 'none';
        });
        bar.querySelectorAll('li.step').forEach(s=>s.classList.remove('current'));
        li.classList.add('current');
      });
    })();
  }
</script>
