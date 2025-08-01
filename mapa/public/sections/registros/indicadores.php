<?php
// Estadísticas generales
$total = count($registros);
$concluidos = count(array_filter($registros, fn($r) => (int)$r['avance'] === 100));
$enProceso = count(array_filter($registros, fn($r) => (int)$r['avance'] > 0 && (int)$r['avance'] < 100));
$sinIniciar = $total - $concluidos - $enProceso;

// Porcentaje de avance promedio
$promedioAvance = $total > 0 ? round(array_sum(array_column($registros, 'avance')) / $total) : 0;
?>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-primary h-100">
      <div class="card-body text-center">
        <h5 class="card-title">Total Registros</h5>
        <p class="fs-4"><?= $total ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success h-100">
      <div class="card-body text-center">
        <h5 class="card-title">Concluidos</h5>
        <p class="fs-4"><?= $concluidos ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning h-100">
      <div class="card-body text-center">
        <h5 class="card-title">En Proceso</h5>
        <p class="fs-4"><?= $enProceso ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-secondary h-100">
      <div class="card-body text-center">
        <h5 class="card-title">Sin Iniciar</h5>
        <p class="fs-4"><?= $sinIniciar ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Barra de avance promedio -->
<div class="mb-4">
  <label class="form-label">Avance Promedio del Proyecto</label>
  <div class="progress" role="progressbar" aria-valuenow="<?= $promedioAvance ?
