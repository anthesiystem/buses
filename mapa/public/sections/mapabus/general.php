<?php
include '../../../server/auth.php';
require_once '../../../server/config.php';
?>

<head>
  <style>
    @media (max-width: 768px) {
  .table thead {
    display: none;
  }

  .tabla-responsive-fila {
    display: block;
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 0.5rem;
  }

  .tabla-responsive-fila td {
    display: flex;
    justify-content: space-between;
    padding: 6px 12px;
    position: relative;
    border: none;
    border-bottom: 1px solid #ddd;
  }

  .tabla-responsive-fila td::before {
    content: attr(data-label);
    font-weight: bold;
    flex-basis: 40%;
    color: #333;
  }

  .tabla-responsive-fila td:last-child {
    border-bottom: none;
  }
}

  </style>
</head>


<div class="contenedor-mapa-general">
  <!-- Mapa -->
  <div id="mapa"">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <!-- Información del Estado -->
  <div id="info">
    <center>
      <h2 id="estadoNombre">Información del Estado</h2>
      <div id="detalle" data-estado=""></div>
    </center>
  </div>
</div>

<?php
// Obtener todos los buses desde la BD, excepto VACIA
$catalogoBuses = [];
$stmt = $pdo->query("SELECT descripcion  FROM bus WHERE descripcion  != 'VACIA'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $catalogoBuses[] = $row['descripcion'];
}
?>

<!-- Script del mapa con configuración -->
<script
  id="mapaScript"
  src="/mapa/server/mapag/mapageneral.js"
  data-color-concluido="#95e039"
  data-color-sin-ejecutar="gray"
  data-color-otro="#de4f33"
  data-catalogo-buses='<?php echo json_encode($catalogoBuses); ?>'>
</script>

<!-- Scripts de apoyo -->
<script src="/mapa/server/mapag/estadoMap.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const interval = setInterval(() => {
    const rectConcluido = document.getElementById("legendConcluido");
    const rectPruebas = document.getElementById("legendPruebas");
    const rectSinEjecutar = document.getElementById("legendSinEjecutar");

    if (rectConcluido && rectPruebas && rectSinEjecutar) {
      rectConcluido.setAttribute("fill", "#95e039");
      rectPruebas.setAttribute("fill", "#de4f33");
      rectSinEjecutar.setAttribute("fill", "gray");

      clearInterval(interval);
    }
  }, 200);
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const a = document.getElementById("legendConcluido");
    const b = document.getElementById("legendPruebas");
    const c = document.getElementById("legendSinEjecutar");

    console.log("🧪 Verificando leyendas:");
    console.log("legendConcluido:", a);
    console.log("legendPruebas:", b);
    console.log("legendSinEjecutar:", c);

    if (a) a.setAttribute("fill", "#95e039");
    if (b) b.setAttribute("fill", "#de4f33");
    if (c) c.setAttribute("fill", "gray");
  }, 500); // espera medio segundo para asegurar que el mapa esté
});
</script>

<!-- PDF generación -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
  window.jsPDF = window.jspdf.jsPDF; // 🔁 Esto es lo que te falta
</script>
<script src="/mapa/server/generar_pdf.js"></script>
