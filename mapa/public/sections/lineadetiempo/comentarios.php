<?php
$idRegistro = intval($_GET['id']); // ID del registro a mostrar
require_once '../../../server/config.php';

$sql = "SELECT cr.encabezado, cr.comentario, cr.fecha_creacion, u.cuenta AS usuario
        FROM comentario_registro cr
        INNER JOIN usuario u ON cr.Fk_usuario = u.ID
        WHERE cr.Fk_registro = ? AND cr.activo = 1
        ORDER BY cr.fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idRegistro]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<head>

<!-- Botón -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="card-title mb-0">Historial de comentarios</h5>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComentario">
    Agregar comentario
  </button>
</div>

<!-- Modal -->
<div class="modal fade" id="modalComentario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="guardar_comentario.php">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo comentario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Relación -->
        <input type="hidden" name="Fk_registro" value="<?= (int)$idRegistro ?>">

        <div class="mb-3">
          <label class="form-label">Encabezado</label>
          <input type="text" name="encabezado" class="form-control" maxlength="500" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Comentario</label>
          <textarea name="comentario" class="form-control" rows="4" maxlength="500" required></textarea>
          <small class="text-muted">Máximo 500 caracteres (según tu tabla).</small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>


<!-- Bootstrap y jQuery -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>

<!-- Estilos del timeline -->
<style>
<?php include 'timeline.css'; ?> /* O inserta el CSS directamente si no deseas usar archivo externo */
</style>


</head>

<div class="row d-flex justify-content-center mt-70 mb-70">
  <div class="col-md-8">
    <div class="main-card mb-3 card">
      <div class="card-body">
        <h5 class="card-title">Historial de comentarios</h5>
        <div class="vertical-timeline vertical-timeline--animate vertical-timeline--one-column">

          <?php foreach ($comentarios as $comentario): ?>
          <div class="vertical-timeline-item vertical-timeline-element">
            <div>
              <span class="vertical-timeline-element-icon bounce-in">
                <i class="badge badge-dot badge-dot-xl badge-primary"></i>
              </span>
              <div class="vertical-timeline-element-content bounce-in">
                <h4 class="timeline-title"><?= htmlspecialchars($comentario['encabezado']) ?></h4>
                <p><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                <span class="vertical-timeline-element-date">
                  <?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?><br>
                  <?= htmlspecialchars($comentario['usuario']) ?>
                </span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</div>


<?php if (isset($_GET['ok'])): ?>
  <?php if ((int)$_GET['ok'] === 1): ?>
    <div class="alert alert-success">Comentario guardado correctamente.</div>
  <?php else: ?>
    <div class="alert alert-danger"><?= isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'No se pudo guardar.' ?></div>
  <?php endif; ?>
<?php endif; ?>
