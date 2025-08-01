<?php
require_once __DIR__ . '/../config.php';

$estado = $_GET['estado'] ?? '';
$bus = $_GET['bus'] ?? '';

$sql = "
    SELECT 
        b.descripcion AS bus_nombre,
        v.descripcion AS Version,
        eb.descripcion AS estatus_nombre,
        r.avance AS Avance,
        r.fecha_inicio AS Fecha_Inicio,
        r.fecha_creacion AS Fecha_Creacion,
        t.descripcion AS Tecnologia,
        d.descripcion AS Dependencia,
        e.descripcion AS Entidad,
        c.descripcion AS Categoria
    FROM registro r
    INNER JOIN entidad e ON e.Id = r.Fk_entidad
    INNER JOIN estado_bus eb ON eb.Id = r.Fk_estado_bus
    INNER JOIN bus b ON b.Id = r.Fk_bus
    LEFT JOIN version v ON v.Id = r.Fk_version
    LEFT JOIN tecnologia t ON v.Fk_tecnologia = t.Id
    LEFT JOIN dependencia d ON r.Fk_dependencia = d.Id
    LEFT JOIN categoria c ON c.Id = r.Fk_categoria
    WHERE e.descripcion = :estado AND b.descripcion = :bus
    ORDER BY c.descripcion, b.descripcion
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':estado' => $estado,
    ':bus' => $bus
]);

$agrupados = [];
$totalBuses = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categoria = mb_strtoupper($row['Categoria'] ?? 'SIN CATEGORÍA');
    $agrupados[$categoria][] = $row;
    $totalBuses++;
}

echo "<h2>" . mb_strtoupper($estado) . "</h2>";
echo "<h2><p><strong>CANTIDAD DE BUSES:</strong> $totalBuses</p></h2>";
echo '<button class="btn btn-outline-dark btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalDetalles">Ver detalles</button>';

foreach ($agrupados as $categoria => $registros) {
    $count = count($registros);
    echo "<h4>$categoria ($count)</h4>";
    echo "<div class='table-responsive'>
        <table class='table table-bordered table-striped table-hover align-middle'>
            <thead class='table-dark'>
            <tr>
                <th>BUS</th>
                <th>VERSIÓN</th>
                <th>ESTATUS</th>
                <th>AVANCE</th>
            </tr>
            </thead>
            <tbody>";

    foreach ($registros as $fila) {
        echo "<tr>
                <td>{$fila['bus_nombre']}</td>
                <td>{$fila['Version']}</td>
                <td>{$fila['estatus_nombre']}</td>
                <td>{$fila['Avance']}%</td>
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
          <h5 class="mt-3"><?= $categoria ?> (<?= count($grupo) ?>)</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-dark">
                <tr>
                  <th>Tecnología</th>
                  <th>Dependencia</th>
                  <th>Entidad</th>
                  <th>Bus</th>
                  <th>Versión</th>
                  <th>Fecha Inicio</th>
                  <th>Fecha Creación</th>
                  <th>Estatus</th>
                  <th>Avance</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($grupo as $row): ?>
                <tr>
                  <td><?= $row['Tecnologia'] ?></td>
                  <td><?= $row['Dependencia'] ?></td>
                  <td><?= $row['Entidad'] ?></td>
                  <td><?= $row['bus_nombre'] ?></td>
                  <td><?= $row['Version'] ?></td>
                  <td><?= $row['Fecha_Inicio'] ?></td>
                  <td><?= $row['Fecha_Creacion'] ?></td>
                  <td><?= $row['estatus_nombre'] ?></td>
                  <td><?= $row['Avance'] ?>%</td>
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
