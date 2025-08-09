<?php
$isRespuesta = $esRespuesta ?? false;
$bg = bgClassFromDb($c['color'] ?? null);
?>

<div class="vertical-timeline-item vertical-timeline-element tl-item <?= $isRespuesta ? 'respuesta' : '' ?>" data-fase="<?= htmlspecialchars($c['fase'] ?? '') ?>">
  <div>
    <span class="vertical-timeline-element-icon bounce-in">
      <span class="tl-dot <?= $bg ?>"></span>
    </span>
    <div class="vertical-timeline-element-content bounce-in">
      <h6 class="timeline-title mb-1">
        <?= htmlspecialchars($c['encabezado']) ?>
        <?php if (!empty($c['fase'])): ?>
          <span class="badge <?= $bg ?> text-light ms-2"><?= htmlspecialchars($c['fase']) ?></span>
        <?php endif; ?>
      </h6>
      <p class="mb-1"><?= nl2br(htmlspecialchars($c['comentario'])) ?></p>
      <small class="vertical-timeline-element-date">
        <?= date('d/m/Y H:i', strtotime($c['fecha_creacion'])) ?><br>
        <?= htmlspecialchars($c['usuario']) ?>
      </small>
      <div class="mt-2">
        <button class="btn btn-sm btn-outline-primary btnResponder"
                data-id="<?= $c['ID'] ?>"
                data-registro="<?= $idRegistro ?>"
                data-encabezado="<?= htmlspecialchars($c['encabezado']) ?>"
                data-comentario="<?= htmlspecialchars($c['comentario']) ?>">
          Responder
        </button>
      </div>
    </div>
  </div>
</div>
