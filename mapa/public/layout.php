<?php
require_once __DIR__ . '/../server/auth.php';
require_once(__DIR__ . '/../server/config.php');
require_login_or_redirect();

require_once __DIR__ . '/../server/acl.php';




// Nivel (compat con tus llaves viejas)
$nivel = $_SESSION['usuario']['nivel'] ?? ($_SESSION['fk_perfiles'] ?? 0);

// Asegura que exista ACL en sesión (por si vinieron directo)
if (!isset($_SESSION['acl'])) {
  $uid = $_SESSION['usuario']['ID'] ?? ($_SESSION['usuario_id'] ?? null);
  if ($uid) $_SESSION['acl'] = acl_build_from_db((int)$uid, (int)$nivel);
}

// 👇 Shim de compatibilidad: mapea tienePermiso() → acl_can()
if (!function_exists('tienePermiso')) {
  function tienePermiso($modulo, $accion='READ', $entidad=null, $bus=null){
    return acl_can($modulo, $accion, $entidad, $bus);
  }
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

      <?php $esAdmin = (int)($nivel ?? 0) >= 3; ?>

<button onclick="cargarSeccion('sections/tablero.php')" class="btn-icon">
  <img src="icons/tab.png" class="imgsdb" />
  <span class="icon-label">Tablero</span>
</button>

<button onclick="cargarSeccion('sections/mapabus/general.php')" class="btn-icon">
  <img src="icons/map.png" class="imgsdb" />
  <span class="icon-label">Mapa</span>
</button>

<?php if ($esAdmin || acl_can('registro','READ')): ?>
<button onclick="cargarSeccion('sections/regprueba.php')" class="btn-icon">
  <img src="icons/reg.png" class="imgsdb" />
  <span class="icon-label">Registros</span>
</button>
<?php endif; ?>

<?php if ($esAdmin || acl_can('catalogo','READ')): ?>
<button onclick="cargarSeccion('sections/catalogos_admin.php')" class="btn-icon">
  <img src="icons/cat.png" class="imgsdb" />
  <span class="icon-label">Catálogos</span>
</button>
<?php endif; ?>

<?php if ($esAdmin || acl_can('bitacora','READ')): ?>
<button onclick="cargarSeccion('sections/bitacora.php')" class="btn-icon">
  <img src="icons/bit.png" class="imgsdb" />
  <span class="icon-label">Bitácora</span>
</button>
<?php endif; ?>

<?php if ($esAdmin || acl_can('usuarios','READ')): ?>
<button onclick="cargarSeccion('sections/usuarios/index.php')" class="btn-icon">
  <img src="icons/usuarios.png" class="imgsdb" />
  <span class="icon-label">Usuarios</span>
</button>
<?php endif; ?>

<?php if ($esAdmin || acl_can('buses','READ')): ?>
<button onclick="cargarSeccion('sections/buses/buses.php')" class="btn-icon">
  <img src="icons/bd.png" class="imgsdb" />
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
$stmt  = $pdo->query("SELECT ID, descripcion, imagen FROM bus WHERE activo = 1 ORDER BY ID");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mostroAlguno = false;
foreach ($buses as $bus):
  $busId   = (int)$bus['ID'];
  $imgPath = 'icons/' . (basename($bus['imagen'] ?? '') ?: 'default.png');

  // ✅ ahora acepta permisos por entidad también (no exige FK_entidad=NULL)
  $puedeVerEsteBus = $esAdmin || acl_can_some_entity('mapa_bus', 'READ', $busId);
  if (!$puedeVerEsteBus) continue;

  $mostroAlguno = true;
?>
  <button onclick="cargarSeccion('sections/mapabus/mapa_bus.php?bus=<?= $busId ?>')">
    <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>"
         class="imgsdb"
         alt="<?= htmlspecialchars($bus['descripcion'], ENT_QUOTES, 'UTF-8') ?>"
         onerror="this.src='icons/default.png'"/>
  </button>
<?php endforeach; ?>

<?php if (!$mostroAlguno): ?>
  <div class="text-muted small mt-2">No tienes buses disponibles.</div>
<?php endif; ?>
</nav>



    </div>
  </aside>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Sistema de registro de vistas en bitácora -->
  <script src="assets/js/bitacora_tracker.js"></script>
  
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

  <meta name="base-path" content="/final/mapa/public">

<?php
  $publicBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/'; // si sirve /final/mapa/public
?>
<base href="<?= htmlspecialchars($publicBase, ENT_QUOTES, 'UTF-8') ?>">



</body>
</html>
