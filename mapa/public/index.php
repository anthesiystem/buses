<?php
require_once __DIR__ . '/../server/auth.php';   // ajusta el path relativo
require_login_or_redirect();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Sistema de Buses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../server/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://unpkg.com/akar-icons-fonts"></script>
  <!-- Configuración global -->
  <script>
    window.APP_CONFIG = {
      baseUrl: '/final/mapa/public',
      serverUrl: '/final/mapa/server',
      mapagUrl: '/final/mapa/server/mapag'
    };
  </script>
  <!-- Script principal mejorado -->
  <script defer src="assets/script-combined.js"></script>
</head>

<script>
  window.MAPA_BUS = {
    busID: 1, // o el ID que tengas seleccionado en el dashboard
    endpointConteos:  "/final/mapa/public/server/mapabus/conteos.php",
    endpointEnts:     "/final/mapa/public/sections/mapabus/entidades_permitidas.php",
    endpointDetalle:  "/final/mapa/public/server/mapabus/detalle.php",
    colors: {
      concluido: "#4CAF50",
      sin: "#9E9E9E",
      otro: "#FFC107"
    }
  };
</script>

<body class="sidebar-expanded">
  
  <?php include 'layout.php'; ?>
  


  <main id="main-content">
    <!-- Aquí se cargan las secciones dinámicamente -->
    <div class="text-center p-4">Cargando...</div>
  </main>
</body>
</html>
