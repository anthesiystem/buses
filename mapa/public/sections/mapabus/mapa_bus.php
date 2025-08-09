<?php
require_once '../../../server/config.php';
include '../../../server/auth.php';

$busID = $_GET['bus'] ?? null;
if (!$busID || !is_numeric($busID)) {
  echo "<div class='alert alert-danger'>Bus no válido</div>";
  exit;
}

// Obtener datos del bus desde la base
$stmt = $pdo->prepare("SELECT * FROM bus WHERE ID = ? AND activo = 1 LIMIT 1");
$stmt->execute([$busID]);
$bus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bus) {
  echo "<div class='alert alert-warning'>No se encontró el bus especificado.</div>";
  exit;
}

// Valores desde la tabla `bus`
$busNombre            = $bus['descripcion'];
$colorImplementado    = $bus['color_implementado'] ?? '#4CAF50';
$colorSinImplementar  = $bus['color_sin_implementar'] ?? '#9E9E9E';
$colorPruebas         = $bus['pruebas'] ?? '#FFC107';
$iconoPath            = "../public/icons/" . $bus['imagen'];
?>

<div class="contenedor-mapa">
  <!-- SVG -->
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <!-- Script del mapa (con datos del bus) -->
<script
  id="mapaScript"
  src="../../../server/mapabus/mapa.js?v=<?= time() ?>"
  data-bus="<?= htmlspecialchars($busNombre) ?>"
  data-bus-id="<?= $busID ?>"
  data-color-concluido="<?= $colorImplementado ?>"
  data-color-sin-ejecutar="<?= $colorSinImplementar ?>"
  data-color-otro="<?= $colorPruebas ?>">
</script>



  <!-- Leyenda de colores -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const interval = setInterval(() => {
        const rectConcluido = document.getElementById("legendConcluido");
        const rectPruebas = document.getElementById("legendPruebas");
        const rectSinEjecutar = document.getElementById("legendSinEjecutar");

        if (rectConcluido && rectPruebas && rectSinEjecutar) {
          rectConcluido.setAttribute("fill", "<?= $colorImplementado ?>");
          rectPruebas.setAttribute("fill", "<?= $colorPruebas ?>");
          rectSinEjecutar.setAttribute("fill", "<?= $colorSinImplementar ?>");
          clearInterval(interval);
        }
      }, 100);
    });
  </script>

  <!-- Información del bus -->
  <div id="info">
    <center>
      <img src="<?= $iconoPath ?>" width="20%" height="20%" />
      <h3><?= strtoupper($busNombre) ?></h3>
      <div id="detalle"></div>
    </center>
  </div>
</div>


