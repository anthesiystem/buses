<?php
session_start();
if (!isset($_SESSION['fk_id_perfiles']) || $_SESSION['fk_id_perfiles'] < 4) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../server/consultas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Registro</title>
  <link rel="stylesheet" href="../server/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="container">
  
    <hr>
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
                <label class="form-label">Tecnología</label>
                <select name="fk_tecnologia" class="form-select" required>
                  <?php foreach ($tecnologias as $row): ?>
                      <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Dependencia</label>
                <select name="fk_dependencia" class="form-select" required>
                  <?php foreach ($dependencias as $row): ?>
                      <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Entidad</label>
                <select name="fk_entidad" class="form-select" required>
                  <?php foreach ($entidades as $row): ?>
                      <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Bus</label>
                <select name="fk_bus" class="form-select" required>
                  <?php foreach ($buses as $row): ?>
                      <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Estatus</label>
                <select name="fk_estatus" class="form-select" required>
                  <?php foreach ($estatuses as $row): ?>
                      <option value="<?= $row['Id'] ?>"><?= $row['Valor'] ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Avance (%)</label>
                <input type="number" name="avance" class="form-control" min="0" max="100" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Versión</label>
                <input type="text" name="version" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" max="<?= date('Y-m-d') ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Migración</label>
                <input type="date" name="migracion" class="form-control" max="<?= date('Y-m-d') ?>">
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" name="agregar" class="btn btn-success">Agregar</button>
              <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- FIN MODAL -->

    <form method="get" class="mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Filtrar por Entidad</label>
          <select name="filtro_entidad" class="form-select" onchange="this.form.submit()">
            <option value="">-- Todas --</option>
            <?php foreach ($entidades as $row): ?>
              <option value="<?= $row['Id'] ?>" <?= ($row['Id'] == $filtro_entidad ? 'selected' : '') ?>><?= $row['Nombre'] ?></option>
            <?php endforeach ?>
          </select>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Tecnología</th>
            <th>Dependencia</th>
            <th>Entidad</th>
            <th>Bus</th>
            <th>Estatus</th>
            <th>Avance</th>
            <th>Versión</th>
            <th>Fecha Inicio</th>
            <th>Migración</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r): ?>
          <tr class="<?= $r['Activo'] ? '' : 'table-secondary' ?>">
            <td><?= $r['Tecnologia'] ?></td>
            <td><?= $r['Dependencia'] ?></td>
            <td><?= $r['Entidad'] ?></td>
            <td><?= $r['Bus'] ?></td>
            <td><?= $r['Estatus'] ?></td>
            <td><?= $r['Avance'] ?>%</td>
            <td><?= $r['Version'] ?></td>
            <td><?= $r['Fecha_Inicio'] ?></td>
            <td><?= $r['Migracion'] ?></td>
            <td>
              <button class="btn btn-warning btn-sm"
                data-bs-toggle="modal" data-bs-target="#modalRegistro"
                onclick="seleccionar(
                  <?= $r['Id'] ?>,
                  <?= $r['Fk_Id_Tecnologia'] ?>,
                  <?= $r['Fk_Id_Dependencia'] ?>,
                  <?= $r['Fk_Id_Entidad'] ?>,
                  <?= $r['Fk_Id_Bus'] ?>,
                  <?= $r['Fk_Id_Estatus'] ?>,
                  <?= $r['Avance'] ?>,
                  '<?= $r['Version'] ?>',
                  '<?= $r['Fecha_Inicio'] ?>',
                  '<?= $r['Migracion'] ?>'
                )">Editar</button>
              <button class="btn <?= $r['Activo'] ? 'btn-danger' : 'btn-success' ?> btn-sm"
                onclick="cambiarEstadoRegistro(<?= $r['Id'] ?>, <?= $r['Activo'] ? 0 : 1 ?>)">
                <?= $r['Activo'] ? 'Desactivar' : 'Activar' ?>
              </button>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
function seleccionar(id, fk_tecnologia, fk_dependencia, fk_entidad, fk_bus, fk_estatus, avance, version, fecha_inicio, migracion) {
    document.querySelector('[name="id"]').value = id;
    document.querySelector('[name="fk_tecnologia"]').value = fk_tecnologia;
    document.querySelector('[name="fk_dependencia"]').value = fk_dependencia;
    document.querySelector('[name="fk_entidad"]').value = fk_entidad;
    document.querySelector('[name="fk_bus"]').value = fk_bus;
    document.querySelector('[name="fk_estatus"]').value = fk_estatus;
    document.querySelector('[name="avance"]').value = avance;
    document.querySelector('[name="version"]').value = version;
    document.querySelector('[name="fecha_inicio"]').value = fecha_inicio;
    document.querySelector('[name="migracion"]').value = migracion;
}

function cambiarEstadoRegistro(id, nuevoEstado) {
    if (confirm("¿Estás seguro de cambiar el estado del registro?")) {
      fetch(`../server/cambiarEstado.php?id=${id}&activo=${nuevoEstado}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            console.error("Error:", data.error);
            alert("Error al cambiar estado");
          }
        })
        .catch(error => {
            console.error("Fallo en la petición:", error);
            alert("Error de red o petición fallida");
        });
    }
}
</script>
</body>
</html>

</body>
</html>
