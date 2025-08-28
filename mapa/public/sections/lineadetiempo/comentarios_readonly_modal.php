<?php
session_start();
require_once '../../../server/config.php';
require_once __DIR__ . '/helpers.php';

$idRegistro = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idRegistro <= 0) {
  echo '<div class="modal-content"><div class="modal-body">ID inválido.</div></div>'; 
  exit;
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
  echo '<div class="modal-content"><div class="modal-body">Registro no encontrado.</div></div>'; 
  exit;
}

/* ===== Etapas (para stepper y cálculo de avance) ===== */
$etapas = $pdo->query("SELECT ID, descripcion, orden, avance FROM etapa ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
$totalEtapas   = max(1, count($etapas));
$etapaActualId = (int)($reg['Fk_etapa'] ?? 0);
$etapaActualTx = (string)($reg['EtapaActual'] ?? '');
$ordenActual   = 0;
$porc          = (int)($reg['AvanceActual'] ?? 0);

foreach ($etapas as $e) {
  if ((int)$e['ID'] === $etapaActualId) {
    $ordenActual = (int)($e['orden'] ?? 0);
    if (!$porc && isset($e['avance'])) $porc = (int)$e['avance'];
    break;
  }
}
if (!$porc) {
  $porc = ($totalEtapas > 1 && $ordenActual > 0)
    ? round((($ordenActual - 1) / ($totalEtapas - 1)) * 100)
    : 0;
}

$barFill = etapa_color_hex($etapaActualTx);
$barTxt  = etapa_text_color($barFill);

/* ===== Comentarios del registro ===== */
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

/* ===== Rutas base ===== */
$ltBase      = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$defaultIcon = lt_default_icon_url();
$publicIcons = lt_public_base_url() . '/icons';
?>

<div class="modal-content">
  <!-- Encabezado -->
  <div class="header p-3 border-bottom">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="m-0">
          <?= h($reg['Entidad'] ?? '') ?> ·
          <?= h($reg['Dependencia'] ?? '') ?> ·
          <?= h($reg['Bus'] ?? '') ?> ·
          TEC: <?= h($reg['Tecnologia'] ?? '') ?>
        </h5>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <!-- Barra de progreso -->
    <div class="stagebar mt-2" style="--value: <?= (int)$porc ?>%; --bar-fill: <?= h($barFill) ?>; --bar-text: <?= h($barTxt) ?>;">
      <div class="fill"><?= (int)$porc ?>%</div>
    </div>
    <?php if ($etapaActualTx): ?>
      <div class="mt-1">
        <span class="badge" style="background:<?= h($barFill) ?>; color:<?= h($barTxt) ?>; border-radius:10px;">
          <?= h($etapaActualTx) ?>
        </span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Contenido: Stepper + Timeline (Solo lectura) -->
  <div class="layout" style="padding:18px; display:flex; flex-direction:column; gap:12px; height: calc(100vh - 150px); overflow: hidden;">
    <!-- Stepper de etapas -->
    <div class="card-soft p-3 mb-2">
      <div class="stepper-wrap d-flex align-items-center gap-2">
        <button id="btnAll" type="button" class="btn btn-sm btn-outline-secondary" title="Ver todas las etapas">Todos</button>
        <ol id="barEtapas" class="stepper m-0">
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

    <!-- Timeline de comentarios -->
    <div class="timeline-wrap" id="listaComentarios" style="position:relative; padding-left:11px; overflow:auto;">
      <div style="content:''; position:absolute; left:16px; top:0; bottom:0; width:3px; background:#eef1f4; border-radius:2px;"></div>

      <?php if (!$comentarios): ?>
        <div class="text-muted text-center p-4">Aún no hay comentarios.</div>
      <?php else: ?>
        <?php foreach ($comentarios as $c): 
          $dot = dotClassFromColor($c['color'] ?? ''); 
          
          // Normalización de etiqueta/color
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
          <div class="t-item tl-item" data-etapa-id="<?= (int)($c['etapa_id'] ?? 0) ?>" style="position:relative; margin-bottom:14px;">
            <span class="t-dot <?= $dot ?>" style="position:absolute; top:50%; width:14px; height:14px; border-radius:50%; box-shadow:0 0 0 4px #fff;"></span>
            <div class="t-card" style="margin-left:24px; border:1px solid #edf0f3; padding:12px 14px; border-radius:12px; background:#fff; box-shadow:0 8px 16px rgba(0,0,0,.04);">
              <div class="d-flex justify-content-between gap-2 fw-bold">
                <div>
                  <?= h($c['encabezado'] ?? '') ?>
                  <?php if (!empty($c['etapa'])):
                    $cHex = etapa_color_hex($c['etapa']); 
                    $cTx = etapa_text_color($cHex); 
                  ?>
                    <span class="badge ms-1" style="background:<?= h($cHex) ?>; color:<?= h($cTx) ?>; border-radius:10px;">
                      <?= h($c['etapa']) ?>
                    </span>
                  <?php endif; ?>

                  <?php if ($tagLabel): ?>
                    <span class="badge <?= h($tagClass) ?> ms-1"><?= h($tagLabel) ?></span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small text-end">
                  <?= h(date('d/m/Y H:i', strtotime($c['fecha'] ?? 'now'))) ?><br>
                  <?= h($c['usuario'] ?? '') ?>
                </div>
              </div>
              <div class="mt-2 text-secondary"><?= nl2br(h($c['comentario'] ?? '')) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // Filtro por etapa desde el stepper
  (function(){
    const bar  = document.getElementById('barEtapas');
    const list = document.getElementById('listaComentarios');
    if (!bar || !list) return;

    const applyFilter = (id) => {
      const target = String(id ?? '');
      list.querySelectorAll('.tl-item').forEach(it=>{
        const x = String(it.dataset.etapaId || '');
        it.style.display = (target && x !== target) ? 'none' : '';
      });
      bar.querySelectorAll('li.step').forEach(s=>s.classList.remove('current'));
      if (target) {
        const li = bar.querySelector(`li.step[data-id="${target}"]`);
        li && li.classList.add('current');
      }
    };

    document.getElementById('btnAll')?.addEventListener('click', ()=>{
      applyFilter('');
    });

    bar.addEventListener('click', (e)=>{
      const li = e.target.closest('li.step[data-id]');
      if (!li) return;
      applyFilter(li.dataset.id);
    });

    // Auto-filtrar a la etapa actual si existe (y hay comentarios)
    const etapaActual = <?= (int)$etapaActualId ?>;
    const hayComentarios = !!list.querySelector('.tl-item');
    if (etapaActual && hayComentarios) {
      const hayDeActual = list.querySelector(`.tl-item[data-etapa-id="${etapaActual}"]`);
      if (hayDeActual) applyFilter(etapaActual);
    }
  })();
</script>
