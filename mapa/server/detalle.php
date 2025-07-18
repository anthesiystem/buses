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
$estado = $conn->real_escape_string($estado);

$sql = "
    SELECT 
        r.Fk_Id_Engine,
        r.Fk_Id_Tecnologia,
        r.Fk_Id_Dependencia,
        r.Fk_Id_Entidad,
        r.Fk_Id_Bus,
        r.Fk_Id_Estatus,
        r.Fk_Id_Categoria,
        r.Version,
        r.Fecha_Inicio,
        r.Migracion,
        r.Avance,
        r.Fecha_Creacion,
        b.Nombre AS bus_nombre,
        es.Valor AS estatus_nombre,
        t.Nombre AS Tecnologia,
        d.Nombre AS Dependencia,
        e.Nombre AS Entidad,
        c.Nombre AS Categoria,
        en.Nombre AS Engine
    FROM registro r
    INNER JOIN entidad e ON e.Id = r.Fk_Id_Entidad
    INNER JOIN estatus es ON es.Id = r.Fk_Id_Estatus
    INNER JOIN bus b ON b.Id = r.Fk_Id_Bus
    LEFT JOIN tecnologia t ON t.Id = r.Fk_Id_Tecnologia
    LEFT JOIN dependencia d ON d.Id = r.Fk_Id_Dependencia
    LEFT JOIN categoria c ON c.Id = r.Fk_Id_Categoria
    LEFT JOIN engine en ON en.Id = r.Fk_Id_Engine
    WHERE e.Nombre = ?
    ORDER BY FIELD(c.Nombre, 'Productivos', 'Centrales', 'Migraciones', 'Pruebas', 'PRUEBAS-MIGRADOS'), c.Nombre, b.Nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $estado);
$stmt->execute();
$result = $stmt->get_result();

$registros = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $categoria = ucfirst(strtolower($row['Categoria'] ?? 'Otro'));
    $registros[$categoria][] = $row;
    $total++;
}

// Mostrar resumen encabezado
echo '<button class="btn btn-outline-dark btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalDetalles">VER DETALLES</button>';
echo "<h3><strong>TOTAL DE REGISTROS:</strong> $total</h3>";

$colores = [
    'Productivos' => '#d4edda',
    'Centrales' => '#d1ecf1',
    'Migraciones' => '#fff3cd',
    'Pruebas' => '#f8d7da',
    'Otro' => '#f5f5f5'
];

$orden = ['Productivos', 'Centrales', 'Migraciones', 'Pruebas'];

echo "<div class='table-responsive mt-4'>";
echo "<table class='table table-bordered table-sm align-middle text-center'>";
echo "<thead class='table-dark'><tr><th>CATEGORÍA</th><th>BUS</th><th>VERSIÓN</th><th>ESTATUS</th><th>AVANCE</th></tr></thead><tbody>";

foreach ($orden as $cat) {
    if (!isset($registros[$cat])) continue;
    foreach ($registros[$cat] as $row) {
        $bg = $colores[$cat] ?? '#ffffff';
        echo "<tr style='background-color: $bg'>
            <td><strong>$cat</strong></td>
            <td>{$row['bus_nombre']}</td>
            <td>{$row['Version']}</td>
            <td>{$row['estatus_nombre']}</td>
            <td>{$row['Avance']}%</td>
        </tr>";
    }
}
foreach ($registros as $cat => $filas) {
    if (in_array($cat, $orden)) continue;
    foreach ($filas as $row) {
        $bg = $colores['Otro'];
        echo "<tr style='background-color: $bg'>
            <td><strong>$cat</strong></td>
            <td>{$row['bus_nombre']}</td>
            <td>{$row['Version']}</td>
            <td>{$row['estatus_nombre']}</td>
            <td>{$row['Avance']}%</td>
        </tr>";
    }
}
echo "</tbody></table></div>";
?>

<!-- MODAL DETALLE DE REGISTROS -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Registros</h5>
        <center><button type="button" class="btn btn-danger btn-sm ms-2" onclick="generarPDF()">Descargar PDF</button></center>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-striped align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>CATEGORÍA</th>
                <th>ENGINE</th>
                <th>TECNOLOGÍA</th>
                <th>DEPENDENCIA</th>
                <th>ENTIDAD</th>
                <th>BUS</th>
                <th>VERSIÓN</th>
                <th>FECHA INICIO</th>
                <th>MIGRACIÓN</th>
                <th>ESTATUS</th>
                <th>AVANCE</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($registros as $grupo): ?>
                <?php foreach ($grupo as $row): ?>
                  <tr>
                    <td><?= $row['Categoria'] ?></td>
                    <td><?= $row['Engine'] ?></td>
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
                <?php endforeach ?>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $conn->close(); ?>
