<?php
session_start();
require_once '../../../server/config.php';

$idRegistro = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idRegistro <= 0) {
  echo '<div class="modal-content"><div class="modal-body">ID inválido.</div></div>';
  exit;
}

// Datos del registro
$reg = $pdo->prepare("
  SELECT r.ID, r.Fk_fase_actual, e.descripcion AS Entidad, d.descripcion AS Dependencia, b.descripcion AS Bus,
         v.descripcion AS Version, r.avance, r.fecha_inicio, r.fecha_migracion
  FROM registro r
  LEFT JOIN entidad e ON e.ID = r.Fk_entidad
  LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia
  LEFT JOIN bus b ON b.ID = r.Fk_bus
  LEFT JOIN version v ON v.ID = r.Fk_version
  WHERE r.ID = ?
");
$reg->execute([$idRegistro]);
$registro = $reg->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
  echo '<div class="modal-content"><div class="modal-body">Registro no encontrado.</div></div>';
  exit;
}

// Fases desde tabla fase
$fases = $pdo->query("SELECT ID, nombre, orden FROM fase ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
$totFases = count($fases);
$ordenActual = 0;
if (!empty($registro['Fk_fase_actual'])) {
  $ordenActual = (int)$pdo->query("SELECT orden FROM fase WHERE ID = ".(int)$registro['Fk_fase_actual'])->fetchColumn();
}
$porc = $totFases > 0 ? round(($ordenActual / $totFases) * 100) : 0;

// Comentarios
$stmt = $pdo->prepare("
  SELECT cr.ID, cr.encabezado, cr.comentario, cr.fecha_creacion,
         u.cuenta AS usuario, cr.color, cr.fase
  FROM comentario_registro cr
  INNER JOIN usuario u ON u.ID = cr.Fk_usuario
  WHERE cr.Fk_registro = ? AND cr.activo = 1
  ORDER BY cr.fecha_creacion DESC
");
$stmt->execute([$idRegistro]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir color a clase de Bootstrap
function bgClassFromDb(?string $color): string {
  $c = strtolower(trim((string)$color));
  if (str_starts_with($c, 'badge-')) $c = str_replace('badge-', 'bg-', $c);
  return match ($c) {
    'rojo', 'bg-danger'      => 'bg-danger',
    'amarillo', 'bg-warning' => 'bg-warning',
    'verde', 'bg-success'    => 'bg-success',
    'azul', 'bg-primary'     => 'bg-primary',
    default                  => 'bg-primary',
  };
}
?>

<style>
  #faseBar button.active { box-shadow: 0 0 0 .2rem rgba(178, 205, 193, 0.25); }
  #faseBar img { filter: saturate(0.6); }
  #faseBar button.active img { filter: saturate(1); }


.progress-bar {
  transition: width 0.6s ease !important;
  height: 12px !important;
}

.progress my-2{
height: 12px !important;
overflow: visible !important;
}

.my-2 {
    padding-bottom: 10px !important;
}
  .btn-outline-success {
    --bs-btn-color: #73eeb5;
    --bs-btn-border-color: #9ae6c3;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #b0fad8 !important;
    --bs-btn-hover-border-color: #198754;
    --bs-btn-focus-shadow-rgb: 25, 135, 84;
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg: #17fe92ff !important;
    --bs-btn-active-border-color: #17fe92ff;
    --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
    --bs-btn-disabled-color: #198754;
    --bs-btn-disabled-bg: transparent;
    --bs-btn-disabled-border-color: #198754;
    --bs-gradient: none;
  }
</style>

<div class="modal-header">
  <h5 class="modal-title">
    Comentarios del registro #<?= htmlspecialchars($idRegistro) ?>
    <small class="text-muted d-block">
      <?= htmlspecialchars($registro['Entidad'] ?? '') ?> •
      <?= htmlspecialchars($registro['Dependencia'] ?? '') ?> •
      <?= htmlspecialchars($registro['Bus'] ?? '') ?> •
      Versión: <?= htmlspecialchars($registro['Version'] ?? '') ?> •
      Avance: <?= (int)($registro['avance'] ?? 0) ?>%
    </small>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="progress my-2" style="height: 10px;">
  <div class="progress-bar" role="progressbar"
       style="width: <?= $porc ?>%;" aria-valuenow="<?= $porc ?>"
       aria-valuemin="0" aria-valuemax="100"></div>
</div>

<div class="bg-white border rounded p-3 mb-3">
  <div class="d-flex gap-3 flex-wrap align-items-center" id="faseBar" style="justify-content: center;">
    <button type="button" class="btn btn-sm btn-outline-secondary active" data-fase="__ALL__">Todos</button>
   
<?php foreach ($fases as $fase): 
  $nombre = $fase['nombre'];
  $activo = ($fase['ID'] == $registro['Fk_fase_actual']) ? 'active' : '';
?>
  <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-2 <?= $activo ?>" data-fase="<?= htmlspecialchars($nombre) ?>">
    <img src="/mapa/public/icons/<?= strtolower(str_replace(' ', '_', $nombre)) ?>.png" alt="<?= htmlspecialchars($nombre) ?>" width="36" height="36">
  </button>
<?php endforeach; ?>

  </div>
</div>


<small class="text-muted" id="textoAvance">Avance de fases: <?= $porc ?>%</small>


<div class="modal-body">
  <form id="formComentario" method="post" action="/mapa/public/sections/lineadetiempo/guardar_comentario.php" class="border rounded p-3 mb-3 bg-light">
    <input type="hidden" name="Fk_registro" value="<?= (int)$idRegistro ?>">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Encabezado</label>
        <input type="text" name="encabezado" class="form-control" maxlength="500" required>
      </div>
      <div class="col-md-5">
        <label class="form-label">Comentario</label>
        <textarea name="comentario" class="form-control" rows="2" maxlength="500" required></textarea>
      </div>
      <div class="col-md-3">
        <label class="form-label">Fase</label>
        <select name="fase" class="form-select" required>
          <option value="">Seleccione...</option>
          <?php foreach ($fases as $f): ?>
            <option value="<?= htmlspecialchars($f['nombre']) ?>"><?= htmlspecialchars($f['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Color del marcador</label>
        <select name="color" class="form-select" required>
          <option value="rojo">🔴 Rojo</option>
          <option value="amarillo">🟡 Amarillo</option>
          <option value="azul" selected>🔵 Azul</option>
          <option value="verde">🟢 Verde</option>
        </select>
      </div>
    </div>
    <div class="text-end mt-2">
      <button class="btn btn-success" type="submit">Guardar</button>
    </div>
  </form>

  <div class="vertical-timeline vertical-timeline--animate vertical-timeline--one-column">
    <?php if (!$comentarios): ?>
      <div class="text-center text-muted py-4">Aún no hay comentarios.</div>
    <?php else: ?>
      <?php foreach ($comentarios as $c): ?>
        <?php $bg = bgClassFromDb($c['color'] ?? null); ?>
        <div class="vertical-timeline-item vertical-timeline-element tl-item" data-fase="<?= htmlspecialchars($c['fase'] ?? '') ?>">
          <div>
            <span class="vertical-timeline-element-icon bounce-in">
              <span class="tl-dot <?= $bg ?>"></span>
            </span>
            <div class="vertical-timeline-element-content bounce-in">
              <h6 class="timeline-title mb-1">
                <?= htmlspecialchars($c['encabezado']) ?>
                <?php if (!empty($c['fase'])): ?>
                  <?php $badgeColor = bgClassFromDb($c['color'] ?? null); ?>
                  <span class="badge <?= $badgeColor ?> text-light ms-2"><?= htmlspecialchars($c['fase']) ?></span>
                <?php endif; ?>

              </h6>
              <p class="mb-1"><?= nl2br(htmlspecialchars($c['comentario'])) ?></p>
              <small class="vertical-timeline-element-date">
                <?= date('d/m/Y H:i', strtotime($c['fecha_creacion'])) ?><br>
                <?= htmlspecialchars($c['usuario']) ?>
              </small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <style>
    .vertical-timeline { width: 100%; position: relative; padding: 1.5rem 0 1rem; }
    .vertical-timeline::before { content: ''; position: absolute; top: 0; left: 67px; height: 100%; width: 4px; background: #e9ecef; border-radius: .25rem; }
    .vertical-timeline-element { position: relative; margin: 0 0 1rem; }
    .vertical-timeline--animate .vertical-timeline-element-icon.bounce-in { visibility: visible; animation: cd-bounce-1 .8s; }
    .vertical-timeline-element-icon { position: absolute; top: 0; left: 60px; }
    .tl-dot { width: 18px; height: 18px; display: inline-block; border-radius: 50%; box-shadow: 0 0 0 5px #fff; }
    .vertical-timeline-element-content {
      position: relative;
      margin-left: 90px;
      font-size: .9rem;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: .5rem;
      padding: .75rem .9rem;
    }
    .timeline-title { font-size: .9rem; font-weight: 600; }
    .vertical-timeline-element-date { display: block; top: 0; padding-right: 10px; text-align: right; color: #adb5bd; font-size: .8rem; white-space: nowrap; }
    @keyframes cd-bounce-1 { 0% { opacity: 0; transform: translateY(-20px); } 60% { opacity: 1; transform: translateY(0); } 100% { opacity: 1; } }
  </style>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
<script>
(() => {
  const faseActualId = <?= json_encode($registro['Fk_fase_actual'] ?? null) ?>;
  const totalFases = <?= count($fases) ?>;

  if (faseActualId && totalFases > 0) {
    fetch('/mapa/public/sections/lineadetiempo/get_orden_fase.php?id=' + faseActualId)
      .then(res => res.json())
      .then(data => {
        if (data.ok && data.orden !== null) {
          const porcentaje = Math.round((data.orden / totalFases) * 100);
          const barra = document.querySelector('.progress-bar');
          const texto = document.querySelector('#textoAvance');

          if (barra) {
            barra.style.width = porcentaje + '%';
            barra.setAttribute('aria-valuenow', porcentaje);
          }

          if (texto) texto.textContent = 'Avance de fases: ' + porcentaje + '%';
        }
      });
  }
})();

</script>
<script>
(function() {
  const form = document.getElementById('formComentario');
  if (!form) return;

  const idRegistro = <?= (int)$idRegistro ?>;

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = 'Guardando...';

    try {
      const fd = new FormData(form);
      const res = await fetch(form.action, { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.ok) throw new Error(data.msg || 'No se pudo guardar');

      const modalDialog = form.closest('.modal-dialog');
      const url = '/mapa/public/sections/lineadetiempo/comentarios_modal.php?id=' + encodeURIComponent(idRegistro);
      const html = await (await fetch(url, { cache: 'no-store' })).text();
      modalDialog.innerHTML = html;
    } catch (err) {
      alert('Error: ' + (err.message || err));
    } finally {
      btn.disabled = false; btn.innerHTML = original;
    }
  });
})();

</script>

