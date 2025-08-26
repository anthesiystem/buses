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

<!-- MODAL DETALLE DE REGISTROS --><!-- MODAL DETALLE DE REGISTROS - ESTILOS + UI MEJORADA -->
<!-- ESTILOS BÁSICOS (solo diseño) -->
<style>
  /* Modal */
  .modal-content.detalles {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 20px 45px rgba(0,0,0,.15);
    overflow: hidden;
  }
  .modal-header.detalles {
    background: linear-gradient(135deg, #911f1f, #fd0d39);
    color: #fff;
    border: 0;
  }
  .modal-header.detalles .btn-close {
    filter: invert(1) grayscale(100%);
  }

  /* Encabezado de categoría */
  .detalles-cat {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: 1.25rem;
    margin-bottom: .5rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    border-bottom: 1px dashed var(--bs-border-color);
    padding-bottom: .25rem;
  }
  .detalles-cat .chip {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .25rem .6rem;
    border-radius: 999px;
    background: rgba(253, 13, 41, 0.08);
    color: #fd0d45;
    font-weight: 600;
    font-size: .9rem;
  }

  /* Tabla */
  .detalles-table {
    --row-pad-y: .6rem;
    --row-pad-x: .75rem;
    --font-size: .95rem;
    --radius: .75rem;
    --thead-bg: #ffcfcf;
    --thead-color: #fd0d59;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    font-size: var(--font-size);
    overflow: hidden;
    border-radius: var(--radius);
  }
  .detalles-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--thead-bg);
    color: var(--thead-color);
    font-weight: 700;
    border-bottom: 1px solid var(--bs-border-color);
    padding: .7rem var(--row-pad-x);
    white-space: nowrap;
  }
  .detalles-table tbody td {
    padding: var(--row-pad-y) var(--row-pad-x);
    vertical-align: middle;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
  }
  .detalles-table tbody tr:hover td {
    background: #f6e4e492;
  }
  .detalles-table tr:nth-child(even) td {
    background: #f7f4f499;
  }
  .detalles-table .badge-soft {
    background: rgba(125, 108, 111, 0.12);
    color: #6c757d;
    font-weight: 600;
    padding: .35rem .55rem;
    border-radius: .5rem;
  }
  .detalles-table .text-strong {
    font-weight: 700;
    color: var(--bs-emphasis-color);
  }
  .detalles-table .text-accent {
    font-weight: 600;
    color: #e90e45;
  }
</style>

<!-- MODAL SOLO CON TABLAS -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content detalles">
      <div class="modal-header detalles">
        <h5 class="modal-title">Detalle de Registros</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <?php foreach ($agrupados as $categoria => $grupo): ?>
          <div class="detalles-bloque mb-4">
            <div class="detalles-cat">
              <span><?= h($categoria) ?></span>
              <span class="chip" title="Registros en esta categoría"><?= count($grupo) ?> registros</span>
            </div>

            <div class="table-responsive">
              <table class="detalles-table table w-100">
                <thead>
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
                      <td><span class="badge-soft"><?= h($row['Categoria']) ?></span></td>
                      <td><?= h($row['Motor_Base']) ?></td>
                      <td class="text-accent"><?= h($row['Tecnologia']) ?></td>
                      <td><?= h($row['Dependencia']) ?></td>
                      <td><?= h($row['Entidad']) ?></td>
                      <td class="text-strong"><?= h($row['bus_nombre']) ?></td>
                      <td><?= h($row['Etapa']) ?></td>
                      <td><span class="text-body-secondary small"><?= h($row['Fecha_Inicio']) ?></span></td>
                      <td><span class="text-body-secondary small"><?= h($row['Fecha_Migracion']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
