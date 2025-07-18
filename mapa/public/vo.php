<?php
include '../server/auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>VO</title>
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



    <div id="info" style="padding-top: 80px;">
      <center><img src="../icons/vo.png" width="20%" height="20%"/>
      <h3>Vehiculos Oficiales</h3>
      <div id="detalle"></div>
    </div>
  </div>

</body>
</html>
