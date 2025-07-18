<?php
session_start();
if (!isset($_SESSION['fk_id_perfiles']) || $_SESSION['fk_id_perfiles'] < 4) {
    header("Location: login.php");
    exit;
}

require_once '../server/config.php';

// obtener tabla seleccionada
$tabla = isset($_GET['tabla']) ? $_GET['tabla'] : 'bus';

// validar tabla segura
$tablas_permitidas = ['bus', 'dependencia', 'entidad', 'tecnologia', 'engine', 'categoria'];
if (!in_array($tabla, $tablas_permitidas)) {
    $tabla = 'bus';
}

// obtener registros de esa tabla
$stmt = $pdo->prepare("SELECT * FROM $tabla WHERE Activo = 1");
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <link rel="stylesheet" href="../server/style.css">
  <meta charset="UTF-8">
  <title>Gestión de Catálogos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <style>
      .html, body {
          height: 100%;
          margin: 0;
          background-color: #6e7e8a !important;
          overflow-x: hidden;
      }
    .container{
      padding-top: 40px;
    }
  
  </style>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>
  <div class="container">
    <div class="container mt-4">
      <h3 class="mb-4">Gestión de Catálogos</h3>

      <form method="get" class="mb-3">
        <label>Seleccione catálogo</label>
        <select name="tabla" class="form-select" onchange="this.form.submit()">
          <?php foreach ($tablas_permitidas as $t): ?>
            <option value="<?= $t ?>" <?= $t == $tabla ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <form method="post" action="catalogos_acciones.php" class="row g-2 mb-4">
        <input type="hidden" name="tabla" value="<?= $tabla ?>">
        <input type="hidden" name="id" id="id">
        <div class="col-md-6">
          <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Nombre" required>
        </div>
        <div class="col">
          <button type="submit" name="agregar" class="btn btn-success">Agregar</button>
          <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Activo</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($registros as $r): ?>
            <tr class="<?= $r['Activo'] ? '' : 'table-secondary' ?>">
              <td><?= $r['Id'] ?></td>
              <td><?= $r['Nombre'] ?></td>
              <td><?= $r['Activo'] ? 'Activo' : 'Inactivo' ?></td>
              <td>
                <button class="btn btn-warning btn-sm"
                  onclick="editar(<?= $r['Id'] ?>, '<?= $r['Nombre'] ?>')">Editar</button>
                <button class="btn <?= $r['Activo'] ? 'btn-danger' : 'btn-success' ?> btn-sm"
                  onclick="cambiarEstado(<?= $r['Id'] ?>, <?= $r['Activo'] ? 0 : 1 ?>)"> 
                  <?= $r['Activo'] ? 'Desactivar' : 'Activar' ?>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    </div>
<script>
function editar(id, nombre) {
  document.getElementById('id').value = id;
  document.getElementById('nombre').value = nombre;
}

function cambiarEstado(id, nuevoEstado) {
  if (confirm('¿Seguro de cambiar el estado?')) {
    fetch(`catalogos_acciones.php?cambiar=1&id=${id}&estado=${nuevoEstado}&tabla=<?= $tabla ?>`)
      .then(r => r.json())
      .then(data => {
        if (data.success) location.reload();
        else alert('Error');
      });
  }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
