<?php
$host = 'localhost';
$db = 'busmap';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$estado = $_GET['estado'] ?? '';
$bus = $_GET['bus'] ?? '';

$estado = $conn->real_escape_string($estado);
$bus = $conn->real_escape_string($bus);

$sql = "
    SELECT 
        b.Nombre AS bus_nombre,
        r.Version,
        es.Valor AS estatus_nombre,
        r.Avance,
        r.Fecha_Inicio,
        r.Migracion,
        r.Fecha_Creacion,
        t.Nombre AS Tecnologia,
        d.Nombre AS Dependencia,
        e.Nombre AS Entidad,
        c.Nombre AS Categoria
    FROM registro r
    INNER JOIN entidad e ON e.Id = r.Fk_Id_Entidad
    INNER JOIN estatus es ON es.Id = r.Fk_Id_Estatus
    INNER JOIN bus b ON b.Id = r.Fk_Id_Bus
    LEFT JOIN tecnologia t ON r.Fk_Id_Tecnologia = t.Id
    LEFT JOIN dependencia d ON r.Fk_Id_Dependencia = d.Id
    LEFT JOIN categoria c ON c.Id = r.Fk_Id_Categoria
    WHERE e.Nombre = ? AND b.Nombre = ?
    ORDER BY c.Nombre, b.Nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $estado, $bus);
$stmt->execute();
$result = $stmt->get_result();

$agrupados = [];
$totalBuses = 0;

while ($row = $result->fetch_assoc()) {
    $categoria = mb_strtoupper($row['Categoria'] ?? 'SIN CATEGORÍA');
    $agrupados[$categoria][] = $row;
    $totalBuses++;
}

echo "<h2>" . mb_strtoupper($estado) . "</h2>";
echo "<h3><p><strong>CANTIDAD DE BUSES:</strong> $totalBuses</p></h3>";
echo '<button class="btn btn-outline-dark btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalDetalles">Ver detalles</button>';

foreach ($agrupados as $categoria => $registros) {
    $count = count($registros);
    echo "<h4>$categoria ($count)</h4>";
    echo "<div class='table-responsive'>
        <table class='table table-bordered table-striped table-hover align-middle'>
            <thead class='table-dark'>
            <tr>
                <th>BUS</th>
                <th>VERSION</th>
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
                  <th>Migración</th>
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
                  <td><?= $row['Migracion'] ?></td>
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

<?php $conn->close(); ?>
