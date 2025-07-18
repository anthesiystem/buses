<?php

session_start();
require_once '../server/config.php';

function obtenerCatalogo($pdo, $tabla) {
    $columna = ($tabla === 'estatus') ? 'Valor' : 'Nombre';
    return $pdo->query("SELECT Id, $columna AS Nombre FROM $tabla WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC);
}

$engines = obtenerCatalogo($pdo, 'engine');
$tecnologias = obtenerCatalogo($pdo, 'tecnologia');
$dependencias = obtenerCatalogo($pdo, 'dependencia');
$entidades = obtenerCatalogo($pdo, 'entidad');
$buses = obtenerCatalogo($pdo, 'bus');
$estatuses = obtenerCatalogo($pdo, 'estatus');
$categorias = obtenerCatalogo($pdo, 'categoria');


// Filtros de búsqueda
$where = "r.Activo = 1";

if (!empty($_GET['f_entidad'])) {
    $where .= " AND r.Fk_Id_Entidad = " . intval($_GET['f_entidad']);
}
if (!empty($_GET['f_bus'])) {
    $where .= " AND r.Fk_Id_Bus = " . intval($_GET['f_bus']);
}
if (!empty($_GET['f_estatus'])) {
    $where .= " AND r.Fk_Id_Estatus = " . intval($_GET['f_estatus']);
}
if (!empty($_GET['f_engine'])) {
    $where .= " AND r.Fk_Id_Engine = " . intval($_GET['f_engine']);
}
if (!empty($_GET['f_tecnologia'])) {
    $where .= " AND r.Fk_Id_Tecnologia = " . intval($_GET['f_tecnologia']);
}
if (!empty($_GET['f_categoria'])) {
    $where .= " AND r.Fk_Id_Categoria = " . intval($_GET['f_categoria']);
}

$registros = $pdo->query("
    SELECT r.*, 
        t.Nombre AS Tecnologia,
        d.Nombre AS Dependencia,
        e.Nombre AS Entidad,
        b.Nombre AS Bus,
        es.Valor AS Estatus,
        en.Nombre AS Engine,
        c.Nombre AS Categoria
    FROM registro r
    LEFT JOIN tecnologia t ON r.Fk_Id_Tecnologia = t.Id
    LEFT JOIN dependencia d ON r.Fk_Id_Dependencia = d.Id
    LEFT JOIN entidad e ON r.Fk_Id_Entidad = e.Id
    LEFT JOIN bus b ON r.Fk_Id_Bus = b.Id
    LEFT JOIN estatus es ON r.Fk_Id_Estatus = es.Id
    LEFT JOIN engine en ON r.Fk_Id_Engine = en.Id
    LEFT JOIN categoria c ON r.Fk_Id_Categoria = c.Id
    WHERE $where
    ORDER BY r.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../server/style.css">
  <meta charset="UTF-8">
  <title>Gestión de Registros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .mt-4 {
    margin-top: 4.5rem !important;
    padding-top: 56px !important;
    }
  </style>
</head>


<body>

  <?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="container mt-4">
 


<h3>Registros existentes</h3>

<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalRegistro">
  + Nuevo Registro
</button>

<!-- MODAL -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="../server/acciones.php" class="row g-3 p-3">
        <input type="hidden" name="id" id="id">
        <div class="modal-header">
          <h5 class="modal-title">Registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">

          <div class="col-md-6">
            <label class="form-label">Engine</label>
            <select name="fk_engine" id="fk_engine" class="form-select" required>
              <?php foreach ($engines as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Tecnología</label>
            <select name="fk_tecnologia" id="fk_tecnologia" class="form-select" required>
              <?php foreach ($tecnologias as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Dependencia</label>
            <select name="fk_dependencia" id="fk_dependencia" class="form-select" required>
              <?php foreach ($dependencias as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Entidad</label>
            <select name="fk_entidad" id="fk_entidad" class="form-select" required>
              <?php foreach ($entidades as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Bus</label>
            <select name="fk_bus" id="fk_bus" class="form-select" required>
              <?php foreach ($buses as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Estatus</label>
            <select name="fk_estatus" id="fk_estatus" class="form-select" required>
              <?php foreach ($estatuses as $row): ?>
                <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Categoría</label>
            <select name="fk_categoria" id="fk_categoria" class="form-select" required>
              <?php foreach ($categorias as $row): ?>
                  <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
              <?php endforeach ?>
            </select>
          </div>


          <div class="col-md-6">
            <label class="form-label">Avance (%)</label>
            <input type="number" name="avance" id="Avance" class="form-control" min="0" max="100" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Versión</label>
            <input type="text" name="version" id="version" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" id="Fecha_Inicio" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Migración</label>
            <input type="date" name="migracion" id="Migracion" class="form-control">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="agregar" id="btnAgregar" class="btn btn-success">Agregar</button>
          <button type="submit" name="actualizar" id="btnActualizar" class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<hr>

<h4>Filtrar registros</h4>
<form method="get" class="row g-2 mb-3">

  <div class="col-md-2">
    <select name="f_entidad" class="form-select">
      <option value="">Entidad</option>
      <?php foreach ($entidades as $e): ?>
        <option value="<?= $e['Id'] ?>" <?= ($_GET['f_entidad'] ?? '') == $e['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_bus" class="form-select">
      <option value="">Bus</option>
      <?php foreach ($buses as $b): ?>
        <option value="<?= $b['Id'] ?>" <?= ($_GET['f_bus'] ?? '') == $b['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_estatus" class="form-select">
      <option value="">Estatus</option>
      <?php foreach ($estatuses as $es): ?>
        <option value="<?= $es['Id'] ?>" <?= ($_GET['f_estatus'] ?? '') == $es['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($es['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_engine" class="form-select">
      <option value="">Engine</option>
      <?php foreach ($engines as $en): ?>
        <option value="<?= $en['Id'] ?>" <?= ($_GET['f_engine'] ?? '') == $en['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($en['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_tecnologia" class="form-select">
      <option value="">Tecnología</option>
      <?php foreach ($tecnologias as $t): ?>
        <option value="<?= $t['Id'] ?>" <?= ($_GET['f_tecnologia'] ?? '') == $t['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($t['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_categoria" class="form-select">
      <option value="">Categoría</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c['Id'] ?>" <?= ($_GET['f_categoria'] ?? '') == $c['Id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-12 text-end">
    <button type="submit" class="btn btn-primary">Filtrar</button>
    <a href="registros.php" class="btn btn-secondary">Limpiar</a>
  </div>
</form>



<h4>Registros Activos</h4>
<table class="table table-bordered table-sm">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Engine</th>
        <th>Tecnología</th>
        <th>Dependencia</th>
        <th>Entidad</th>
        <th>Bus</th>
        <th>Estatus</th>
        <th>Categoría</th>
        <th>Versión</th>
        <th>Fecha Inicio</th>
        <th>Migración</th>
        <th>Avance</th>
        <th>Acciones</th>
      </tr>
    </thead>
  <tbody>
    <?php foreach ($registros as $r): ?>
    <tr>
      <td><?= $r['Id'] ?></td>
      <td><?= $r['Engine'] ?></td>
      <td><?= $r['Tecnologia'] ?></td>
      <td><?= $r['Dependencia'] ?></td>
      <td><?= $r['Entidad'] ?></td>
      <td><?= $r['Bus'] ?></td>
      <td><?= $r['Estatus'] ?></td>
      <td><?= $r['Categoria'] ?></td>
      <td><?= $r['Version'] ?></td>
      <td><?= $r['Fecha_Inicio'] ?></td>
      <td><?= $r['Migracion'] ?></td>
      <td><?= $r['Avance'] ?>%</td>
      <td>
        

    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRegistro"
      onclick="cargarEnModal(
        <?= (int) $r['Id'] ?>,
        <?= (int) $r['Fk_Id_Engine'] ?>,
        <?= (int) $r['Fk_Id_Tecnologia'] ?>,
        <?= (int) $r['Fk_Id_Dependencia'] ?>,
        <?= (int) $r['Fk_Id_Entidad'] ?>,
        <?= (int) $r['Fk_Id_Bus'] ?>,
        <?= (int) $r['Fk_Id_Estatus'] ?>,
        <?= (int) $r['Fk_Id_Categoria'] ?>,
        <?= (int) $r['Avance'] ?>,
        '<?= htmlspecialchars($r['Version'], ENT_QUOTES) ?>',
        '<?= $r['Fecha_Inicio'] ?? '' ?>',
        '<?= $r['Migracion'] ?? '' ?>'
      )">
      Editar
    </button>




        <a href="desactivar_registro.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-danger">Desactivar</a>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cargarEnModal(id, engine, tecnologia, dependencia, entidad, bus, estatus, categoria, avance, version, fechaInicio, migracion) {
  document.getElementById('id').value = id;
  document.getElementById('fk_engine').value = engine;
  document.getElementById('fk_tecnologia').value = tecnologia;
  document.getElementById('fk_dependencia').value = dependencia;
  document.getElementById('fk_entidad').value = entidad;
  document.getElementById('fk_bus').value = bus;
  document.getElementById('fk_estatus').value = estatus;
  document.getElementById('fk_categoria').value = categoria;
  document.getElementById('Avance').value = avance;
  document.getElementById('version').value = version;
  document.getElementById('Fecha_Inicio').value = fechaInicio;
  document.getElementById('Migracion').value = migracion;

  actualizarColorAvance(avance);
  document.getElementById('btnAgregar').style.display = 'none';
  document.getElementById('btnActualizar').style.display = 'inline-block';

  if (estatus == 2) {
    document.getElementById('Avance').value = 100;
    document.getElementById('Avance').setAttribute('readonly', 'readonly');
    actualizarColorAvance(100);
  } else {
    document.getElementById('Avance').removeAttribute('readonly');
  }
}

document.querySelector('[data-bs-target="#modalRegistro"]').addEventListener('click', () => {
  document.getElementById('id').value = '';
  document.getElementById('fk_engine').value = '';
  document.getElementById('fk_tecnologia').value = '';
  document.getElementById('fk_dependencia').value = '';
  document.getElementById('fk_entidad').value = '';
  document.getElementById('fk_bus').value = '';
  document.getElementById('fk_id_estatus').value = '';
  document.getElementById('fk_categoria').value = '';
  document.getElementById('avance').value = 0;
  document.getElementById('version').value = '';
  document.getElementById('fecha_inicio').value = '';
  document.getElementById('migracion').value = '';
  actualizarColorAvance(0);

  document.getElementById('btnAgregar').style.display = 'inline-block';
  document.getElementById('btnActualizar').style.display = 'none';
});

function actualizarColorAvance(valor) {
  const avanceInput = document.getElementById('Avance');
  if (valor == 100) {
    avanceInput.style.backgroundColor = '#d4edda'; // verde claro
    avanceInput.style.borderColor = '#28a745';     // borde verde
  } else {
    avanceInput.style.backgroundColor = '';
    avanceInput.style.borderColor = '';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const estatus = document.getElementById('fk_estatus');
  const avance = document.getElementById('Avance');
  const fechaInicio = document.getElementById('Fecha_Inicio');
  const migracion = document.getElementById('Migracion');
  const hoy = new Date().toISOString().split('T')[0];

  if (fechaInicio) fechaInicio.setAttribute('max', hoy);
  if (migracion) migracion.setAttribute('max', hoy);

  if (estatus && avance) {
    estatus.addEventListener('change', function () {
      if (estatus.value == 2) {
        if (!confirm("⚠️ ¿Estás seguro de guardar este registro como IMPLEMENTADO?")) {
          estatus.value = '';
          return;
        }
        avance.value = 100;
        avance.setAttribute('readonly', 'readonly');
        actualizarColorAvance(100);
      } else {
        avance.removeAttribute('readonly');
        if (avance.value > 100) avance.value = 100;
        actualizarColorAvance(avance.value);
      }
    });

    avance.addEventListener('input', function () {
      actualizarColorAvance(avance.value);
    });
  }
});
</script>

</div>
</body>
</html>
