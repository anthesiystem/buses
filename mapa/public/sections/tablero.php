<?php
include '../../server/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tablero</title>
  <link rel="stylesheet" href="../server/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="contenedor">
  <div id="info" class="tablero-ajuste">
    <div id="info-tablero">
      <h1>DETALLE DE BUSES IMPLEMENTADOS</h1>

      <?php
      $conexion = new mysqli("localhost", "admin", "admin1234", "busmap");
      if ($conexion->connect_error) {
          die("Error de conexión: " . $conexion->connect_error);
      }

      // Consulta principal categorizada por nombre del bus (descripcion)
      $sql = "SELECT
              CASE
                WHEN b.descripcion LIKE 'RNAE%ARMAMENTO%' THEN 'rnae'
                WHEN b.descripcion LIKE 'RNAE%EQUIPO%' THEN 'eo'
                WHEN b.descripcion LIKE '911%' THEN '911'
                WHEN b.descripcion LIKE 'CUP%' THEN 'cup'
                WHEN b.descripcion LIKE 'RNL%' THEN 'rnl'
                WHEN b.descripcion LIKE 'LPR%' THEN 'lpr'
                WHEN b.descripcion LIKE 'MJ%' THEN 'mj'
                WHEN b.descripcion LIKE 'RNIP%' THEN 'rnip'
                WHEN b.descripcion LIKE 'VEH%' THEN 'vo'
                WHEN b.descripcion LIKE 'VRyR%' THEN 'vryr'
                ELSE 'otros'
              END AS categoria,
              COUNT(*) AS total
              FROM REGISTRO r
              INNER JOIN BUS b ON r.Fk_bus = b.ID
              WHERE 
                b.descripcion IS NOT NULL 
                AND b.descripcion != '' 
                AND b.descripcion NOT LIKE '%VACIA%'
              GROUP BY categoria
              HAVING categoria != 'otros'
              ORDER BY categoria;";

      // Total general (sin buses VACÍA ni los intermedios de prueba, si aplica)
      $sql_TOTAL = "
        SELECT COUNT(Fk_bus) AS total
        FROM REGISTRO
        WHERE Fk_bus IS NOT NULL
          AND Fk_bus != 1
          AND Fk_bus NOT BETWEEN 3 AND 5;
      ";

      $resultado_total = $conexion->query($sql_TOTAL);
      $total = 0;
      if ($resultado_total && $row = $resultado_total->fetch_assoc()) {
        $total = $row['total'];
      }

      $resultado = $conexion->query($sql);

      $categorias = ['vryr', 'rnl', 'rnip', 'mj', 'cup', '911', 'lpr', 'rnae', 'eo', 'vo'];
      $datos = array_fill_keys($categorias, 0);
      $totalGeneral = 0;

      while ($fila = $resultado->fetch_assoc()) {
          $cat = strtolower($fila['categoria']);
          $count = intval($fila['total']);
          if (array_key_exists($cat, $datos)) {
              $datos[$cat] = $count;
          }
          $totalGeneral += $count;
      }

      $conexion->close();
      ?>

      <!-- Tablero separado del resto -->
      <section class="zona-tablero">
        <div class="contenedor-dashboard">

          <!-- Panel izquierdo -->
          <div class="panel-produccion">
            <h1><strong>PRODUCCIÓN</strong></h1>
            <p class="cantidad-total"><?php echo $total; ?></p>
          </div>

          <!-- Panel derecho -->
          <div class="tabla-articulos">
            <?php
            foreach ($categorias as $cat) {
                $nombre = strtoupper($cat);
                $cantidad = $datos[$cat];
                echo "
                <a href='javascript:void(0)' class='articulo' onclick=\"cargarSeccion('sections/mapabus/{$cat}.php')\">
                  <img src='icons/{$cat}.png' alt='{$nombre}'>
                  <p class='cantidad-bus'>{$cantidad}</p>
                </a>";
            }
            ?>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

</body>
</html>
