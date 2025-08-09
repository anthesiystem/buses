<?php
require_once __DIR__ . '/../config.php';

$estado = $_GET['estado'] ?? '';
$busID  = $_GET['bus_id'] ?? null;

if (!$busID || !is_numeric($busID)) {
  echo "<div class='alert alert-danger'>Bus inválido</div>";
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$sql = "
    SELECT 
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

$agrupados = [];
$totalBuses = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categoria = mb_strtoupper($row['Categoria'] ?? 'SIN CATEGORÍA', 'UTF-8');
    $agrupados[$categoria][] = $row;
    $totalBuses++;
}

echo "<h2>" . h(mb_strtoupper($estado, 'UTF-8')) . "</h2>";
echo "<h2><p><strong>CANTIDAD DE BUSES:</strong> " . (int)$totalBuses . "</p></h2>";
echo '<button class="btn btn-outline-dark btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalDetalles">Ver detalles</button>';

foreach ($agrupados as $categoria => $registros) {
    $count = count($registros);
    echo "<h4>" . h($categoria) . " (" . $count . ")</h4>";
    echo "<div class='table-responsive'>
        <table class='table table-bordered table-striped table-hover align-middle'>
            <thead class='table-dark'>
            <tr>
                <th>BUS</th>
                <th>TECNOLOGÍA</th>
                <th>MOTOR BASE</th>
                <th>ETAPA</th>
            </tr>
            </thead>
            <tbody>";

    foreach ($registros as $fila) {
        echo "<tr>
                <td>".h($fila['bus_nombre'])."</td>
                <td>".h($fila['Tecnologia'])."</td>
                <td>".h($fila['Motor_Base'])."</td>
                <td>".h($fila['Etapa'])."</td>
              </tr>";
    }
    echo "</tbody></table><br>";
}
?>

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
