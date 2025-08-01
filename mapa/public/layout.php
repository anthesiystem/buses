<?php
session_start();
require_once(__DIR__ . '/../server/config.php');
require_once(__DIR__ . '/../server/permiso.php');


$nivel = $_SESSION['fk_perfiles'] ?? 0;

// Asegúrate de cargar permisos si no existen en la sesión
if (!isset($_SESSION['permisos']) && isset($_SESSION['usuario_id'])) {
    cargarPermisos($_SESSION['usuario_id'], $pdo);
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Sistema de Buses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Estilos -->
  <link rel="stylesheet" href="../server/styles_layout.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://unpkg.com/akar-icons-fonts"></script>

  <style>
    .navbar {
      z-index: 2000;
    }
    .navbar .navbar-brand img {
      height: 40px;
    }
  </style>
</head>

<body class="sidebar-expanded">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center">
        <img src="icons/logotipo.png" alt="Logo">
      </a>
      <!-- Botón visible solo en pantallas pequeñas -->
      <button id="toggleSidebar" class="btn btn-dark d-lg-none ms-2">
        ☰
      </button>
    </div>
  </nav>

  <!-- SIDEBAR -->
  <aside class="sidebar" style="top: 71px;">
    <div class="left">
      <img src="img/iconescudo.png" alt="Logo Sistema" class="logo-sidebar" />

      <button onclick="cargarSeccion('sections/tablero.php')" class="btn-icon">
        <img src="icons/tab.png" class="imgsdb" />
        <span class="icon-label">Tablero</span>
      </button>

      <button onclick="cargarSeccion('sections/mapabus/general.php')" class="btn-icon">
        <img src="icons/map.png" class="imgsdb" />
        <span class="icon-label">Mapa</span>
      </button>

      <?php if ($nivel >= 3 || tienePermiso('registro', 'READ')): ?>
      <button onclick="cargarSeccion('sections/busreg/buses.php')" class="btn-icon">
        <img src="icons/reg.png" class="imgsdb" />
        <span class="icon-label">Registros</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel >= 3 || tienePermiso('catalogo', 'READ')): ?>
      <button onclick="cargarSeccion('sections/catalogos.php')" class="btn-icon">
        <img src="icons/cat.png" class="imgsdb" />
        <span class="icon-label">Catálogos</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel == 4 || tienePermiso('bitacora', 'READ')): ?>
      <button onclick="cargarSeccion('sections/bitacora.php')" class="btn-icon">
        <img src="icons/bit.png" class="imgsdb" />
        <span class="icon-label">Bitácora</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel == 4 || tienePermiso('usuarios', 'READ')): ?>
      <button onclick="cargarSeccion('sections/usuarios.php')" class="btn-icon">
        <img src="icons/usuarios.png" class="imgsdb" />
        <span class="icon-label">Usuarios</span>
      </button>
      <?php endif; ?>

      <button onclick="location.href='logout.php'" class="mt-auto mb-3">
        <img src="icons/lg.png" class="imgsdb" />
      </button>
    </div>

    <div class="right">
      <h1>BUSES</h1>
      <nav class="buttons">
        <?php
        $buses = [
          'vryr' => 'VRYR', 'rnl' => 'RNL', 'rnip' => 'RNIP', 'mj' => 'MJ',
          'cup' => 'CUP', '911' => '911', 'lpr' => 'LPR', 'rnae' => 'RNAE',
          'eo' => 'EO', 'vo' => 'VO'
        ];
        foreach ($buses as $modulo => $label):
          if ($nivel >= 3 || tienePermiso($modulo, 'READ')):
        ?>
          <button onclick="cargarSeccion('sections/mapabus/<?= $modulo ?>.php')">
            <img src="icons/<?= $modulo ?>.png" class="imgsdb" />
            <span><?= $label ?></span>
          </button>
        <?php
          endif;
        endforeach;
        ?>
      </nav>
    </div>
  </aside>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const toggleBtn = document.getElementById("toggleSidebar");
    toggleBtn?.addEventListener("click", () => {
      document.body.classList.toggle("sidebar-mobile-open");
    });

    window.addEventListener("resize", () => {
      if (window.innerWidth >= 768) {
        document.body.classList.remove("sidebar-mobile-open");
      }
    });
  </script>
</body>
</html>
