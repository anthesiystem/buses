<?php
require_once __DIR__ . '/../server/auth.php';
require_login_or_redirect();

require_once __DIR__ . '/../server/acl.php';

// Nivel desde la nueva sesión o la anterior (compat)
$nivel = $_SESSION['usuario']['nivel'] ?? ($_SESSION['fk_perfiles'] ?? 0);

// Asegura ACL en sesión (por si se llegó aquí sin pasar por login)
if (!isset($_SESSION['acl'])) {
  $uid   = $_SESSION['usuario']['ID'] ?? ($_SESSION['usuario_id'] ?? null);
  $nivel = (int)($nivel ?? 0);
  if ($uid) {
    $_SESSION['acl'] = acl_build_from_db($uid, $nivel);
  } else {
    // sin usuario, manda a login
    header("Location: /final/mapa/public/login.php"); exit;
  }
}

// Helper corto para no repetir
function can($mod, $acc='READ'){ return acl_can($mod, $acc); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Sistema de Buses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../server/styles_layout.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://unpkg.com/akar-icons-fonts"></script>
  <style>
    .navbar { z-index: 2000; }
    .navbar .navbar-brand img { height: 40px; }
  </style>
</head>
<body class="sidebar-expanded">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center">
        <img src="icons/logotipo.png" alt="Logo">
      </a>
      <button id="toggleSidebar" class="btn btn-dark d-lg-none ms-2">☰</button>
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

      <?php if ($nivel >= 3 || can('registro','READ')): ?>
      <button onclick="cargarSeccion('sections/busreg/buses.php')" class="btn-icon">
        <img src="icons/reg.png" class="imgsdb" />
        <span class="icon-label">Registros</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel >= 3 || can('catalogo','READ')): ?>
      <button onclick="cargarSeccion('sections/catalogos.php')" class="btn-icon">
        <img src="icons/cat.png" class="imgsdb" />
        <span class="icon-label">Catálogos</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel >= 3 || can('bitacora','READ')): ?>
      <button onclick="cargarSeccion('sections/bitacora.php')" class="btn-icon">
        <img src="icons/bit.png" class="imgsdb" />
        <span class="icon-label">Bitácora</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel >= 3 || can('usuarios','READ')): ?>
      <button onclick="cargarSeccion('sections/usuarios/index.php')" class="btn-icon">
        <img src="icons/usuarios.png" class="imgsdb" />
        <span class="icon-label">Usuarios</span>
      </button>
      <?php endif; ?>

      <?php if ($nivel >= 3 || can('buses','READ')): ?>
      <button onclick="cargarSeccion('sections/buses/buses.php')" class="btn-icon">
        <img src="icons/usuarios.png" class="imgsdb" />
        <span class="icon-label">Buses</span>
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
        // Trae buses activos (auth.php ya cargó config y $pdo)
        $stmt = $pdo->query("SELECT ID, descripcion, imagen FROM bus WHERE activo = 1 ORDER BY descripcion");
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // IMPORTANTE: usamos módulo fijo 'mapa_bus' y chequeamos por BUS ID (no por nombre)
        foreach ($buses as $bus):
          $imagen = 'icons/' . (basename($bus['imagen']) ?: 'default.png');

          $puedeVerEsteBus = ($nivel >= 3) || acl_can('mapa_bus', 'READ', null, (int)$bus['ID']);
          if (!$puedeVerEsteBus) continue;
        ?>
          <button onclick="cargarSeccion('sections/mapabus/mapa_bus.php?bus=<?= (int)$bus['ID'] ?>')">
            <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>" class="imgsdb" onerror="this.src='icons/default.png'" />
          </button>
        <?php endforeach; ?>
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
      if (window.innerWidth >= 768) document.body.classList.remove("sidebar-mobile-open");
    });
  </script>

  <meta name="base-path" content="/final/mapa/public">
  <?php $publicBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/'; ?>
  <base href="<?= htmlspecialchars($publicBase, ENT_QUOTES, 'UTF-8') ?>">
</body>
</html>
