<?php
require_once __DIR__ . '/../config.php';

$estado = $_GET['estado'] ?? '';
$estado = trim($estado);

$sql = "
SELECT 
    r.Fk_motor_base,
    r.Fk_dependencia,
    r.Fk_entidad,
    r.Fk_bus,
    r.Fk_estado_bus,
    r.Fk_categoria,
    r.Fk_version,
    r.fecha_inicio,
    r.fecha_migracion,
    r.avance,
    r.fecha_creacion,

    b.descripcion AS bus_nombre,
    eb.descripcion AS estado_nombre,
    v.descripcion AS version,
    t.descripcion AS tecnologia,
    d.descripcion AS dependencia,
    e.descripcion AS entidad,
    c.descripcion AS categoria,
    en.descripcion AS motor_base_nombre

FROM registro r
INNER JOIN entidad e       ON e.ID = r.Fk_entidad
INNER JOIN estado_bus eb   ON eb.ID = r.Fk_estado_bus
INNER JOIN bus b           ON b.ID = r.Fk_bus
LEFT JOIN version v        ON v.ID = r.Fk_version
LEFT JOIN tecnologia t     ON v.Fk_tecnologia = t.ID
LEFT JOIN dependencia d    ON r.Fk_dependencia = d.ID
LEFT JOIN categoria c      ON c.ID = r.Fk_categoria
LEFT JOIN motor_base en    ON en.ID = r.Fk_motor_base

WHERE e.descripcion = :estado

ORDER BY FIELD(c.descripcion, 'Productivos', 'Centrales', 'Migraciones', 'Pruebas', 'PRUEBAS-MIGRADOS'),
         c.descripcion,
         b.descripcion;

";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$registros = [];
$total = 0;

foreach ($rows as $row) {
    $categoria = ucfirst(strtolower($row['categoria'] ?? 'Otro'));
    $registros[$categoria][] = $row;
    $total++;
}

// Encabezado
echo '<button class="btn btn-outline-dark btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalDetalles">VER DETALLES</button>';
echo "<h3><strong>TOTAL DE BUSES:</strong> $total</h3>";

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
            <td>{$row['version']}</td>
            <td>{$row['estado_nombre']}</td>
            <td>{$row['avance']}%</td>
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
            <td>{$row['version']}</td>
            <td>{$row['estado_nombre']}</td>
            <td>{$row['avance']}%</td>
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
                <th>FECHA MIGRACION</th>
                <th>ESTATUS</th>
                <th>AVANCE</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($registros as $grupo): ?>
                <?php foreach ($grupo as $row): ?>
                  <tr>
                    <td><?= $row['categoria'] ?></td>
                    <td><?= $row['motor_base_nombre'] ?></td>
                    <td><?= $row['tecnologia'] ?></td>
                    <td><?= $row['dependencia'] ?></td>
                    <td><?= $row['entidad'] ?></td>
                    <td><?= $row['bus_nombre'] ?></td>
                    <td><?= $row['version'] ?></td>
                    <td><?= $row['fecha_inicio'] ?></td>
                    <td><?= $row['fecha_migracion'] ?></td>
                    <td><?= $row['estado_nombre'] ?></td>
                    <td><?= $row['avance'] ?>%</td>
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
