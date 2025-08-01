<?php include '../../../server/auth.php'; ?>

<div class="contenedor-mapa">
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <!-- Script del mapa -->
<script
  id="mapaScript"
  src="../../../server/mapabus/mapa.js"
  data-bus="911 (EMERGENCIAS)"
  data-color-concluido="#8d2f2c"
  data-color-sin-ejecutar="gray"
  data-color-otro="#be3f3b">
</script>




  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const interval = setInterval(() => {
        const rectConcluido = document.getElementById("legendConcluido");
        const rectPruebas = document.getElementById("legendPruebas");
        const rectSinEjecutar = document.getElementById("legendSinEjecutar");

        if (rectConcluido && rectPruebas && rectSinEjecutar) {
          rectConcluido.setAttribute("fill", "#8d2f2c");
          rectPruebas.setAttribute("fill", "#be3f3b");
          rectSinEjecutar.setAttribute("fill", "gray");

          console.log("🎨 Leyenda pintada desde script local de vista");
          clearInterval(interval);
        }
      }, 200);
    });
  </script>

  <!-- Información del bus -->
  <div id="info">
    <center>
      <img src="../public/icons/911.png" width="20%" height="20%" />
      <h3>EQUIPO OFICIAL</h3>
      <div id="detalle"></div>
    </center>
  </div>
</div>
