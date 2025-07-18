<?php
include '../server/auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tablero</title>
  <link rel="stylesheet" href="../server/style.css">

  <style>
    .articulo {
  text-decoration: none;
  color: inherit;
}
.articulo:hover {
  opacity: 0.8;
}

.articulo img {
transition: 1s ease;
}

.articulo img:hover {
-webkit-transform: scale(1.2);
-ms-transform: scale(1.2);
transform: scale(1.2);
transition: 1s ease;
}


  </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="contenedor">
  <div id="info" class="tablero-ajuste">
    <div id="info-tablero">
      <h1>DETALLE DE BUSES IMPLEMENTADOS</h1>

      <?php
      // Conexión a la base de datos
      $conexion = new mysqli("localhost", "admin", "admin1234", "busmap");
      if ($conexion->connect_error) {
          die("Error de conexión: " . $conexion->connect_error);
      }

      $sql = "SELECT
              CASE
                WHEN b.Nombre LIKE 'RNAE%ARMAMENTO%' THEN 'rnae'
                WHEN b.Nombre LIKE 'RNAE%EQUIPO%' THEN 'eo'
                WHEN b.Nombre LIKE '911%' THEN '911'
                WHEN b.Nombre LIKE 'CUP%' THEN 'cup'
                WHEN b.Nombre LIKE 'RNL%' THEN 'rnl'
                WHEN b.Nombre LIKE 'LPR%' THEN 'lpr'
                WHEN b.Nombre LIKE 'MJ%' THEN 'mj'
                WHEN b.Nombre LIKE 'RNIP%' THEN 'rnip'
                WHEN b.Nombre LIKE 'VEH%' THEN 'vo'
                WHEN b.Nombre LIKE 'VRyR%' THEN 'vryr'
                ELSE 'otros'
              END AS categoria,
              COUNT(*) AS total
              FROM registro r
              INNER JOIN bus b ON r.Fk_Id_Bus = b.Id
              WHERE 
                b.Nombre IS NOT NULL 
                AND b.Nombre != '' 
                AND b.Nombre NOT LIKE '%VACIA%'
              GROUP BY categoria
              HAVING categoria != 'otros'
              ORDER BY categoria;";


      $sql_TOTAL = "
        SELECT COUNT(Fk_Id_Bus) AS total
        FROM registro
        WHERE Fk_Id_Bus != 1
        AND Fk_Id_Bus NOT BETWEEN 3 AND 5;
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
                <a href='{$cat}.php' class='articulo'>
                  <img src='../icons/{$cat}.png' alt='{$nombre}'>
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

