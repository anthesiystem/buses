<?php
require_once __DIR__ . '/../config.php';

$estado = $_GET['estado'] ?? '';
$busID  = $_GET['bus_id'] ?? null;

if (!$busID || !is_numeric($busID)) {
  echo "<div class='alert alert-danger'>Bus inválido</div>";
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* =========================
   CONSULTA PRINCIPAL
========================= */
$sql = "
  SELECT 
      r.ID                   AS ID,
      b.descripcion          AS bus_nombre,
      eb.descripcion         AS estatus_nombre,
      r.fecha_inicio         AS Fecha_Inicio,
      r.fecha_migracion      AS Fecha_Migracion,
      r.fecha_creacion       AS Fecha_Creacion,
      t.descripcion          AS Tecnologia,
      d.descripcion          AS Dependencia,
      e.descripcion          AS Entidad,
      c.descripcion          AS Categoria,
      mb.descripcion         AS Motor_Base,
      et.descripcion         AS Etapa
  FROM registro r
  INNER JOIN entidad     e  ON e.Id = r.Fk_entidad
  INNER JOIN estado_bus  eb ON eb.Id = r.Fk_estado_bus
  INNER JOIN bus         b  ON b.Id = r.Fk_bus
  INNER JOIN tecnologia  t  ON t.Id = r.Fk_tecnologia
  INNER JOIN dependencia d  ON d.Id = r.Fk_dependencia
  INNER JOIN categoria   c  ON c.Id = r.Fk_categoria
  INNER JOIN motor_base  mb ON mb.Id = r.Fk_motor_base
  LEFT  JOIN etapa       et ON et.Id = r.Fk_etapa
  WHERE UPPER(e.descripcion) = :estado AND b.Id = :bus_id
  ORDER BY c.descripcion, b.descripcion
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':estado' => mb_strtoupper($estado, 'UTF-8'),
  ':bus_id' => $busID
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AGRUPACIÓN Y DATOS CABECERA
========================= */
$agrupados = [];
$totalBuses = 0;
$busNombreHeader = '';
foreach ($rows as $row) {
  $categoria = mb_strtoupper($row['Categoria'] ?? 'SIN CATEGORÍA', 'UTF-8');
  $agrupados[$categoria][] = $row;
  $totalBuses++;
  if ($busNombreHeader === '' && !empty($row['bus_nombre'])) {
    $busNombreHeader = $row['bus_nombre'];
  }
}

/* Icono del bus (si existe columna imagen en tabla bus) */
$icono = null;
try {
  $qIcon = $pdo->prepare("SELECT imagen FROM bus WHERE Id = :id LIMIT 1");
  $qIcon->execute([':id' => $busID]);
  $iconoRel = $qIcon->fetchColumn();
  if ($iconoRel) {
    $solo = basename($iconoRel);
    $icono = "icons/{$solo}";
  }
} catch (\Throwable $th) { /* opcional: log */ }

/* =========================
   ESTILOS (card + tabla moderna)
========================= */
?>
<style>
  .card-estado{border-radius:18px;background:;box-shadow:0 10px 28px rgba(0,0,0,.06);padding:18px 18px 8px}
  .estado-header{display:flex;align-items:center;gap:16px;margin-bottom:8px}
  .estado-icon{width:86px;height:86px;border-radius:18px;display:grid;place-items:center;color:#fff;font-weight:800;font-size:22px;overflow:hidden;background:#f59e0b}
  .estado-icon img{width:100%;height:100%;object-fit:cover}
  .estado-info h3{font-size:22px;font-weight:800;margin:0;color:#111827}
  .estado-info h5{font-size:15px;font-weight:700;margin:.2rem 0 0;color:#374151}
  .estado-kv{font-weight:800;margin-top:8px;color:#111827}

  .btn-detalles{border:1px solid #d1d5db;border-radius:12px;padding:6px 14px;font-weight:600;background:#f9fafb;transition:.2s}
  .btn-detalles:hover{background:#e5e7eb}

  .section-title{font-weight:800;color:#111827;letter-spacing:.2px;margin:.9rem 0 .35rem}

  .table-modern{border-collapse:separate;border-spacing:0;width:100%;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.06);margin-bottom:16px}
  .table-modern thead{background:#111827;color:#fff}
  .table-modern th,.table-modern td{padding:10px 12px;text-align:center}
  .table-modern tbody tr{transition:background .2s}
  .table-modern tbody tr:hover{background:#f9fafb}

  .comment-btn{border:0;background:#962a2a; color:#fff;width:32px;height:32px;border-radius:50%;font-weight:800;display:inline-grid;place-items:center}
  .comment-btn:hover{background:#db2777}
</style>

<!-- CABECERA -->
<div class="card-estado mb-3">
  <div class="estado-header">
    <div class="estado-icon">
      <?php if ($icono): ?>
        <img src="<?= h($icono) ?>" alt="icono">
      <?php else: ?>
        <?= strtoupper(substr($busNombreHeader !== '' ? $busNombreHeader : 'BUS', 0, 2)) ?>
      <?php endif; ?>
    </div>
    <div class="estado-info flex-grow-1">
      <h3><?= h(mb_strtoupper($busNombreHeader ?: 'BUS', 'UTF-8')) ?></h3>
      <h5><?= h(mb_strtoupper($estado, 'UTF-8')) ?></h5>
      <div class="estado-kv">CANTIDAD DE BUSES: <?= (int)$totalBuses ?></div>
    </div>
    <div><button class="btn-detalles" data-bs-toggle="modal" data-bs-target="#modalDetalles">Ver detalles</button></div>
  </div>
</div>

<?php
/* =========================
   TABLAS POR CATEGORÍA
========================= */
foreach ($agrupados as $categoria => $registros):
  $count = count($registros);
?>
  <h6 class="section-title"><?= h($categoria) ?> (<?= $count ?>)</h6>
  <div class="table-responsive">
    <table class="table-modern align-middle">
      <thead>
        <tr>
          <th>BUS</th>
          <th>TECNOLOGÍA</th>
          <th>MOTOR BASE</th>
          <th>ETAPA</th>
          <th>COMENTARIOS</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registros as $fila): 
          $id    = isset($fila['ID']) ? (int)$fila['ID'] : 0;
          $bus   = h($fila['bus_nombre']   ?? '');
          $tec   = h($fila['Tecnologia']   ?? '');
          $motor = h($fila['Motor_Base']   ?? '');
          $etapa = h($fila['Etapa']        ?? '');
        ?>
          <tr>
            <td><?= $bus ?></td>
            <td><span class="badge-tec"><?= $tec ?></span></td>
            <td><span class="badge-motor"><?= $motor ?></span></td>
            <td><span class="badge-etapa"><?= $etapa ?></span></td>
            <td>
              <button type="button" class="comment-btn modalbitacora"
                      title="Comentarios"
                      data-bs-toggle="modal"
                      data-bs-target="#modalComentarios"
                      data-bs-id="<?= $id ?>">…</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>

<!-- MODAL DETALLE DE REGISTROS -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Registros</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php foreach ($agrupados as $categoria => $grupo): ?>
          <h5 class="mt-3"><?= h($categoria) ?> (<?= count($grupo) ?>)</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-dark">
                <tr>
                  <th>Categoria</th>
                  <th>Engine</th>
                  <th>Tecnologia</th>
                  <th>Dependencia</th>
                  <th>Entidad</th>
                  <th>Bus</th>
                  <th>Etapa</th>
                  <th>Fecha Inicio</th>
                  <th>Fecha Migración</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($grupo as $row): ?>
                <tr>
                  <td><?= h($row['Categoria']) ?></td>
                  <td><?= h($row['Motor_Base']) ?></td>
                  <td><?= h($row['Tecnologia']) ?></td>
                  <td><?= h($row['Dependencia']) ?></td>
                  <td><?= h($row['Entidad']) ?></td>
                  <td><?= h($row['bus_nombre']) ?></td>
                  <td><?= h($row['Etapa']) ?></td>
                  <td><?= h($row['Fecha_Inicio']) ?></td>
                  <td><?= h($row['Fecha_Migracion']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>




