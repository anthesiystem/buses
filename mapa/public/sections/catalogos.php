<?php
session_start();

if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 3) {
    header("Location: acceso_denegado.php");
    exit;
}

require_once '../../server/config.php';

$tabla = $_GET['tabla'] ?? 'bus';
$tablas_permitidas = ['bus', 'dependencia', 'entidad', 'tecnologia', 'engine', 'categoria'];

if (!in_array($tabla, $tablas_permitidas)) {
    $tabla = 'bus';
}

$stmt = $pdo->prepare("SELECT * FROM $tabla ORDER BY ID");
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<head>
  <meta charset="UTF-8">
  <title>Gestión de Catálogos</title>
  <link rel="stylesheet" href="../server/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-4">
    <h3 class="mb-4">Gestión de Catálogos</h3>

    <form method="get" class="mb-3">
      <label>Seleccione catálogo</label>
      <select name="tabla" class="form-select" onchange="cargarSeccionTabla(this.value)">
        <?php foreach ($tablas_permitidas as $t): ?>
          <option value="<?= $t ?>" <?= $t == $tabla ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <form id="formCatalogo" class="row g-2 mb-4">
      <input type="hidden" name="tabla" value="<?= $tabla ?>">
      <input type="hidden" name="ID" id="ID">
      <div class="col-md-6">
        <input type="text" name="descripcion" id="descripcion" class="form-control" placeholder="Descripción" required>
      </div>
      <div class="col">
        <button type="submit" id="btnAgregar" class="btn btn-success">Agregar</button>
        <button type="submit" id="btnActualizar" class="btn btn-primary d-none">Actualizar</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Descripción</th>
            <th>Activo</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r): ?>
          <tr class="<?= $r['activo'] ? '' : 'table-secondary' ?>">
            <td><?= $r['ID'] ?></td>
            <td><?= $r['descripcion'] ?></td>
            <td><?= $r['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
              <button class="btn btn-warning btn-sm"
                onclick="editar(<?= $r['ID'] ?>, '<?= htmlspecialchars($r['descripcion'], ENT_QUOTES) ?>')">Editar</button>
              <button class="btn <?= $r['activo'] ? 'btn-danger' : 'btn-success' ?> btn-sm"
                onclick="cambiarEstado(<?= $r['ID'] ?>, <?= $r['activo'] ? 0 : 1 ?>)"> 
                <?= $r['activo'] ? 'Desactivar' : 'Activar' ?>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
function cargarSeccionTabla(tabla) {
  cargarSeccion(`sections/catalogos.php?tabla=${tabla}`);
}

function editar(id, descripcion) {
  document.getElementById('ID').value = id;
  document.getElementById('descripcion').value = descripcion;

  document.getElementById('btnAgregar').classList.add('d-none');
  document.getElementById('btnActualizar').classList.remove('d-none');
}

function resetFormulario() {
  document.getElementById('formCatalogo').reset();
  document.getElementById('ID').value = '';
  document.getElementById('btnAgregar').classList.remove('d-none');
  document.getElementById('btnActualizar').classList.add('d-none');
}

function cambiarEstado(id, nuevoEstado) {
  if (confirm('¿Seguro de cambiar el estado?')) {
    fetch(`/mapa/server/acciones/catalogos_acciones.php?cambiar=1&id=${id}&estado=${nuevoEstado}&tabla=<?= $tabla ?>`)
      .then(r => r.json())
      .then(data => {
        if (data.success) location.reload();
        else alert('Error al cambiar estado');
      });
  }
}

document.getElementById('formCatalogo').addEventListener('submit', function(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const esActualizar = document.getElementById('btnActualizar').classList.contains('d-none') === false;

  if (esActualizar) {
    formData.append('actualizar', 1);
  } else {
    formData.append('agregar', 1);
  }

  fetch('/mapa/server/acciones/catalogos_acciones.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.ok ? location.reload() : alert('Error al guardar'))
  .catch(() => alert('Error en conexión'));
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
