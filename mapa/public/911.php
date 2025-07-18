<?php
include '../server/auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>911</title>
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
            data-bus="911 (EMERGENCIAS)"
            data-color-concluido="#e24939"
            data-color-sin-ejecutar="gray"
            data-color-otro="#de675b">
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
                  const colorIMPLEMENTADO = "#e24939";
                  const colorOtro = "#de675b";
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
    <center>
      <img src="../icons/911.png" width="20%" height="20%"/>
      <h3>Centro de Atención Telefónica</h3>
      <div id="detalle"></div>
    </center>
  </div>
</div>




</body>
</html>
