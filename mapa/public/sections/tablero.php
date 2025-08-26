<?php
require_once '../../server/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tablero</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../server/style.css">
  <!-- RUTA BASE GLOBAL -->
  <base href="/final/mapa/public/">
</head>
<body>

<div class="contenedor">
  <div id="info" class="tablero-ajuste">
    <div id="info-tablero">
      <h1>DETALLE DE BUSES IMPLEMENTADOS</h1>

      <?php
      // Conexión directa (si no usas $pdo aquí)
      $conexion = new mysqli("localhost", "admin", "admin1234", "buses1");
      if ($conexion->connect_error) {
          die("Error de conexión: " . $conexion->connect_error);
      }

      // Buses activos
      $sql_buses = "SELECT ID, descripcion, imagen FROM bus WHERE activo = 1 ORDER BY descripcion";
      $res_buses = $conexion->query($sql_buses);

      // Conteo por bus
      $sql_registros = "SELECT Fk_bus, COUNT(*) AS total FROM registro WHERE activo = 1 and Fk_bus IS NOT NULL GROUP BY Fk_bus";
      $res_registros = $conexion->query($sql_registros);

      // Mapeo de conteo
      $conteos = [];
      while ($row = $res_registros->fetch_assoc()) {
          $conteos[$row['Fk_bus']] = $row['total'];
      }

      // Total general
      $sql_total = "SELECT COUNT(*) AS total FROM registro WHERE activo = 1 and Fk_bus IS NOT NULL";
      $res_total = $conexion->query($sql_total);
      $total = ($res_total && $fila = $res_total->fetch_assoc()) ? $fila['total'] : 0;
      ?>

      <section class="zona-tablero">
        <div class="contenedor-dashboard">

          <!-- Producción -->
          <div class="panel-produccion">
            <h1><strong>PRODUCCIÓN</strong></h1>
            <p class="cantidad-total"><?= $total ?></p>
          </div>

          <!-- Buses -->
          <div class="tabla-articulos">
           <?php
if ($res_buses) {
  while ($bus = $res_buses->fetch_assoc()) {
    $id      = (int)$bus['ID'];
    $nombre  = strtoupper($bus['descripcion'] ?? '');

    // Normaliza la imagen (soporta rutas con \ o /) y usa default si viene vacía
    $imagenBD   = $bus['imagen'] ?? '';
    $soloNombre = basename(str_replace('\\', '/', $imagenBD));
    if ($soloNombre === '') $soloNombre = 'default.png';
    $imagen     = "icons/{$soloNombre}";

    $cantidad = $conteos[$id] ?? 0;

    // Ruta correcta a la vista del bus
    $ruta = 'sections/mapabus/mapa_bus.php?bus=' . $id;

    echo '
      <a href="javascript:void(0)" class="articulo" onclick="cargarSeccion(\'' . $ruta . '\')">
        <img src="' . $imagen . '" alt="' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '"
             onerror="this.onerror=null;this.src=\'icons/default.png\'">
        <p class="cantidad-bus">' . $cantidad . '</p>
      </a>';
  }
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
