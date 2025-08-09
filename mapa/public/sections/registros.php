<?php
session_start();
require_once '../../server/config.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.php");
  exit;
}

// Catálogos para selects
function obtenerCatalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}

$dependencias = obtenerCatalogo($pdo, 'dependencia');
$entidades    = obtenerCatalogo($pdo, 'entidad');
$buses        = obtenerCatalogo($pdo, 'bus');
$motor_bases  = obtenerCatalogo($pdo, 'motor_base');
$versiones    = $pdo->query("SELECT v.ID, CONCAT(v.descripcion, ' - ', t.descripcion) AS descripcion FROM version v JOIN tecnologia t ON v.Fk_tecnologia = t.ID WHERE v.activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$estatuses    = obtenerCatalogo($pdo, 'estado_bus');
$categorias   = obtenerCatalogo($pdo, 'categoria');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">



<!-- En tu HTML -->




</head>
<body class="container mt-4">

<h3>Gestor de Registros</h3>

<div class="mb-3 d-flex justify-content-between">
  <button class="btn btn-success" onclick="abrirModal()">+ Nuevo Registro</button>
  <a></a>
    <a></a>
</div>

<?php include 'registros/filtros.php'; ?>

<div id="contenedorTabla">
  <div class="text-center my-4" id="spinnerTabla" style="display:none;">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Cargando...</span>
    </div>
  </div>
  <?php include 'registros/tabla.php'; ?>
</div>

<?php include 'registros/modal_registro.php'; ?>

<div id="loader" class="text-center mt-3" style="display:none">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Cargando...</span>
  </div>
</div>




<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toast dinámico -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="toastExito" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="mensajeToast">
        <!-- Mensaje dinámico -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<!-- Cargando centrado -->
<div id="cargando" style="
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2000;
  background: rgba(255, 255, 255, 0.9);
  padding: 20px;
  border-radius: 8px;
  text-align: center;">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Cargando...</span>
  </div>
  <div>Espere un momento...</div>
</div>

<div id="guardadoExitoAnimado" style="
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1055;
  background: rgba(255, 255, 255, 0.95);
  padding: 30px 40px;
  border-radius: 20px;
  box-shadow: 0 0 20px rgba(0,0,0,0.2);
  text-align: center;
">
  <div style="font-size: 60px; color: green;">
    <img src="/mapa/public/img/escudospiner.gif" style="height: 250px; wight: 250px;" alt="">
  </div>
  <div style="font-size: 18px; margin-top: 10px;">
    Guardado exitosamente
  </div>
</div>

<!-- Catálogos disponibles para JS -->
<!-- Catálogos disponibles para JS -->
<script>
  const catalogos = {
    dependencia: <?= json_encode($dependencias) ?>,
    entidad: <?= json_encode($entidades) ?>,
    bus: <?= json_encode($buses) ?>,
    motor_base: <?= json_encode($motor_bases) ?>,
    version: <?= json_encode($versiones) ?>,
    estado: <?= json_encode($estatuses) ?>,
    categoria: <?= json_encode($categorias) ?>
  };
</script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Tu JS principal (al final) -->
<script src="../assets/js/registros/main.js"></script>


<!-- Scripts JS modularizados -->

</body>
</html>
