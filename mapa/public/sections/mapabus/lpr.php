<?php include '../../../server/auth.php'; ?>

<div class="contenedor-mapa">
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>
          <script 
            id="mapaScript"
            src="/mapa/server/mapabus/mapa.js"
            data-bus="LPR(HITS-ALERT)"
            data-color-concluido="#504f50 "
            data-color-sin-ejecutar="#c7c7c7"
            data-color-otro="#939393">
          </script>

<script>
            document.addEventListener("DOMContentLoaded", function () {
              // Esperar que el SVG esté cargado en el DOM
              const interval = setInterval(() => {
                const rectConcluido = document.getElementById("legendConcluido");
                const rectPruebas = document.getElementById("legendPruebas");
                const rectSinEjecutar = document.getElementById("legendSinEjecutar");

                if (rectConcluido && rectPruebas && rectSinEjecutar) {
                  // Colores personalizados para este BUS
                  const colorIMPLEMENTADO = "#504f50";
                  const colorOtro = "#939393";
                  const colorSinEjecutar = "gray";

                  rectConcluido.setAttribute("fill", colorIMPLEMENTADO);
                  rectPruebas.setAttribute("fill", colorOtro);
                  rectSinEjecutar.setAttribute("fill", colorSinEjecutar);

                  console.log("🎨 Leyenda pintada desde script local de vista");
                  clearInterval(interval);
                }
              }, 200); // verifica cada 200ms
            });
          </script>

    <div id="info">
      <center><img src="../public/icons/lpr.png" width="20%" height="20%"/>
      <h3>HITS ALERT</h3>
      <div id="detalle"></div>
    </div>
  </div>
</body>
</html>
