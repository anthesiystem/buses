<?php
include '../server/auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>LC</title>
  <link rel="stylesheet" href="../server/style.css">
</head>
<body>
  <?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

  <div class="contenedor">
    <div id="mapa">
      <?php echo file_get_contents("mapa.svg"); ?>
    </div>
    		  <script 
            id="mapaScript"
            src="../server/mapa.js"
            data-bus="RNL (LICENCIAS)"
            data-color-concluido="#fba829"
            data-color-sin-ejecutar="gray"
            data-color-otro="#fac878">
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
                  const colorIMPLEMENTADO = "#fba829";
                  const colorOtro = "#fac878";
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
      <center><img src="../icons/rnl.png" width="20%" height="20%"/>
      <h3>Renovacion de Nuevas Licencias</h3>
      <div id="detalle"></div>
    </div>
  </div>

</body>
</html>
