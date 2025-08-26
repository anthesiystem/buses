<?php
// ❌ si no hay permisos, comenta la línea de auth:
# include '../../../server/auth.php';
require_once '../../../server/config.php';
?>
<head>
  <style>
    @media (max-width: 768px) {
      .table thead { display:none; }
      .tabla-responsive-fila{ display:block; margin-bottom:1rem; border:1px solid #ccc; border-radius:6px; padding:.5rem; }
      .tabla-responsive-fila td{ display:flex; justify-content:space-between; padding:6px 12px; border:none; border-bottom:1px solid #ddd; }
      .tabla-responsive-fila td::before{ content:attr(data-label); font-weight:bold; flex-basis:40%; color:#333; }
      .tabla-responsive-fila td:last-child{ border-bottom:none; }
    }
    #info {
          margin-top: 26px;
    }
  </style>
  <base href="/final/mapa/public/">
</head>

<div class="contenedor-mapa-general">
  <!-- ojo: sin comilla extra -->
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <div id="info">
    <center>
      <h2 id="estadoNombre">Información del Estado</h2>
      <div id="detalle" data-estado=""></div>
    </center>
  </div>
</div>

<?php
$catalogoBuses = [];
$stmt = $pdo->query(" SELECT UPPER(TRIM(descripcion)) AS descripcion
    FROM bus
    WHERE activo = 1
      AND descripcion <> 'VACIA'
    ORDER BY ID");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $catalogoBuses[] = $row['descripcion'];
?>

<!-- primero el mapa de claves de estados -->
<script src="/final/mapa/server/mapag/estadomap.js"></script>

<!-- script principal con endpoints bien puestos -->
<script
  id="mapaScript"
  src="/final/mapa/server/mapag/mapageneral.js?v=1"
  data-color-concluido="#04a404b6"
  data-color-sin-ejecutar="#B0B0B0"
  data-color-otro="#d7201aad"
  data-url-datos="/final/mapa/server/mapag/generalindex.php"
  data-url-detalle="/final/mapa/server/mapag/detalle.php"
  data-catalogo-buses='<?= json_encode($catalogoBuses, JSON_UNESCAPED_UNICODE) ?>'>
</script>

<!-- (opcional) repintar leyenda una vez que exista -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const iv = setInterval(() => {
    const a = document.getElementById("legendConcluido");
    const b = document.getElementById("legendPruebas");
    const c = document.getElementById("legendSinEjecutar");
    if (a) a.setAttribute("fill", "#04a404b6");
    if (b) b.setAttribute("fill", "#d7201aff");
    if (c) c.setAttribute("fill", "#B0B0B0");
    if (a && b && c) clearInterval(iv);
  }, 120);
});
</script>

<!-- PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>window.jsPDF = window.jspdf.jsPDF;</script>
<script src="/final/mapa/server/generar_pdf.js"></script>

