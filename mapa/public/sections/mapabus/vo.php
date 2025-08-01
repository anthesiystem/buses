<?php include '../../../server/auth.php'; ?>

<div class="contenedor-mapa">
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

    		  <script 
            id="mapaScript"
            src="/mapa/server/mapabus/mapa.js"
            data-bus="VEH(OFICIALES)"
            data-color-concluido="#e1493a"
            data-color-sin-ejecutar="gray"
            data-color-otro="#e06053">
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
                  const colorIMPLEMENTADO = "#e1493a";
                  const colorOtro = "#e06053";
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
      <center><img src="../public/icons/vo.png" width="20%" height="20%"/>
      <h3>VEHICULOS OFICIALES</h3>
      <div id="detalle"></div>
    </div>
  </div>

</body>
</html>
