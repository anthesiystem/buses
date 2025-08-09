<?php include '../../server/auth.php'; ?>

<div class="contenedor">
  <!-- Mapa -->
  <div id="mapa" style="padding-top: 70px;">
    <?php echo file_get_contents("../../public/mapa.svg"); ?>
  </div>

  <!-- Información del Estado -->
  <div id="info" style="padding-top: 80px;">
    <center>
      <h2 id="estadoNombre">Información del Estado</h2>
      <div id="detalle" data-estado=""></div>
    </center>
  </div>
</div>

<?php
// Obtener todos los buses desde la BD, excepto VACIA
$conexion = new mysqli("localhost", "admin", "admin1234", "buses");
$catalogoBuses = [];
$result = $conexion->query("SELECT Nombre FROM bus WHERE Nombre != 'VACIA'");
while ($row = $result->fetch_assoc()) {
    $catalogoBuses[] = $row['Nombre'];
}
$conexion->close();
?>

<!-- Script del mapa con configuración -->
<script
  id="mapaScript"
  src="/mapa/server/mapag/mapageneral.js"
  data-color-concluido="#95e039"
  data-color-sin-ejecutar="gray"
  data-color-otro="#de4f33"
  data-catalogo-buses='<?php echo json_encode($catalogoBuses); ?>'>
</script>

<!-- Scripts de apoyo -->
<script src="/mapa/server/mapag/estadoMap.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const interval = setInterval(() => {
    const rectConcluido = document.getElementById("legendConcluido");
    const rectPruebas = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    if (rectConcluido && rectPruebas && rectSinEjecutar) {
      rectConcluido.setAttribute("fill", "#95e039");
      rectPruebas.setAttribute("fill", "#de4f33");
      rectSinEjecutar.setAttribute("fill", "gray");

      console.log("🎨 Leyenda pintada desde script local de vista");
      clearInterval(interval);
    }
  }, 200);
});
</script>

<!-- PDF generación -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="/mapa/server/mapag/generar_pdf.js"></script>
