<?php include '../../server/auth.php'; ?>

<div class="contenedor">
  <div id="mapa" style="padding-top: 70px;">
        <?php echo file_get_contents("../../public/mapa.svg"); ?>
      </div>
            <script
              id="mapaScript"
              src="/mapa/server/mapabus/mapa.js"
              data-bus="CUP (CERTIFICADO)"
              data-color-concluido="#21626e"
              data-color-sin-ejecutar="gray"
              data-color-otro="#2a7f8f">
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
                  const colorIMPLEMENTADO = "#21626e";
                  const colorOtro = "#2a7f8f";
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


        <div id="info" style="padding-top: 80px;">
          <center><img src="../public/icons/cup.png" width="20%" height="20%"/>
          <h3>CERTIFICADO UNICO POLICIAL</h3>
          <div id="detalle">
          </div>
        </div>
    </div>
