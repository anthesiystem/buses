<?php include '../../../server/auth.php'; ?>

<div class="contenedor-mapa">
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  
        <script 
            id="mapaScript"
            src="/mapa/server/mapabus/mapa.js"
            data-bus="RNIP (PENITENCIARIA)"
            data-color-concluido="#2e7fb2"
            data-color-sin-ejecutar="gray"
            data-color-otro="#389fe0">
        </script>



    <div id="info">
      <center><img src="../public/icons/rnip.png" width="20%" height="20%"/>
      <h3>INDICIADOS Y PROCESADOS</h3>
      <div id="detalle"></div>
    </div>
  </div>

  
</body>
</html>
