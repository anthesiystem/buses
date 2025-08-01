<?php
// sections/permisos.php
session_start();
if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 3) {
  header("Location: acceso_denegado.php");
  exit;
}
require_once '../../server/config.php';

// Obtener usuarios
$usuarios = $pdo->query("SELECT u.ID, p.nombres, p.apaterno, p.amaterno FROM usuario u JOIN persona p ON u.Fk_persona = p.ID WHERE u.activo = 1")->fetchAll(PDO::FETCH_ASSOC);

// Obtener modulos, entidades y buses
$modulos = $pdo->query("SELECT ID, descripcion FROM modulo WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$entidades = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$buses = $pdo->query("SELECT ID, descripcion FROM bus WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$acciones = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
?>

<h3>Gestor de Permisos</h3>

<form action="../../server/acciones/permisos_acciones.php" method="POST" class="mb-4">
  <div class="row g-2">
    <div class="col-md-3">
      <label>Usuario</label>
      <select name="usuario" class="form-select" required>
        <option value="">Seleccione</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?= $u['ID'] ?>">
            <?= $u['nombres'] . ' ' . $u['apaterno'] . ' ' . $u['amaterno'] ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label>Módulo</label>
      <select name="modulo" class="form-select" required>
        <option value="">Seleccione</option>
        <?php foreach ($modulos as $m): ?>
          <option value="<?= $m['ID'] ?>"><?= $m['descripcion'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label>Acción</label>
      <select name="accion" class="form-select" required>
        <option value="">Seleccione</option>
        <?php foreach ($acciones as $a): ?>
          <option value="<?= $a ?>"><?= $a ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label>Entidad (opcional)</label>
      <select name="entidad" class="form-select">
        <option value="">---</option>
        <?php foreach ($entidades as $e): ?>
          <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label>Bus (opcional)</label>
      <select name="bus" class="form-select">
        <option value="">---</option>
        <?php foreach ($buses as $b): ?>
          <option value="<?= $b['ID'] ?>"><?= $b['descripcion'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary">Asignar Permiso</button>
  </div>
</form>

<!-- Aquí puedes mostrar permisos existentes si lo deseas -->
<div id="permisos_usuario" class="mt-4" style="display:none;">
  <h5>Permisos Asignados</h5>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Módulo</th>
        <th>Acción</th>
        <th>Entidad</th>
        <th>Bus</th>
      </tr>
    </thead>
    <tbody id="tabla_permisos_body">
      <!-- JS llenará esto -->
    </tbody>
  </table>
</div>


<script>
function editarUsuario(usuario) {
  document.getElementById('id_usuario').value = usuario.ID;
  document.getElementById('persona').value = usuario.Fk_persona;
  document.getElementById('cuenta').value = usuario.cuenta;
  document.getElementById('nivel').value = usuario.nivel;

  // Limpiar tabla y mostrar contenedor
  const tbody = document.getElementById("tabla_permisos_body");
  tbody.innerHTML = "";
  document.getElementById("permisos_usuario").style.display = "block";

  fetch(`../../server/acciones/obtener_permisos.php?id=${usuario.ID}`)
    .then(res => res.json())
    .then(permisos => {
      if (permisos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center">Sin permisos asignados</td></tr>`;
      } else {
        permisos.forEach(p => {
          const row = `<tr>
              <td>${p.modulo}</td>
              <td>${p.accion}</td>
              <td>${p.entidad ?? '—'}</td>
              <td>${p.bus ?? '—'}</td>
          </tr>`;
          tbody.innerHTML += row;
        });
      }
    })
    .catch(error => {
      console.error("Error al cargar permisos:", error);
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger">Error al cargar</td></tr>`;
    });
}
</script>
