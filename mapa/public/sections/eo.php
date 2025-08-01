<?php include '../../server/auth.php'; ?>

<div class="contenedor">
  <!-- Mapa -->
  <div id="mapa" style="padding-top: 70px;">
    <?php echo file_get_contents("../../public/mapa.svg"); ?>
  </div>

  <!-- Script del mapa -->
<script
  id="mapaScript"
  src="/mapa/server/mapabus/mapa.js"
  data-bus="RNAE(EQUIPO)"
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
  <div id="info" style="padding-top: 80px;">
    <center>
      <img src="../public/icons/eo.png" width="20%" height="20%" />
      <h3>EQUIPO OFICIAL</h3>
      <div id="detalle"></div>
    </center>
  </div>
</div>
