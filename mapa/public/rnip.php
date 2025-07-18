<?php
include '../server/auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>RNIP</title>
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
            data-bus="RNIP (PENITENCIARIA)"
            data-color-concluido="#2e7fb2"
            data-color-sin-ejecutar="gray"
            data-color-otro="#389fe0">
        </script>



    <div id="info" style="padding-top: 80px;">
      <center><img src="../icons/rnip.png" width="20%" height="20%"/>
      <h3>Indiciados Y Procesados</h3>
      <div id="detalle"></div>
    </div>
  </div>

  
</body>
</html>
