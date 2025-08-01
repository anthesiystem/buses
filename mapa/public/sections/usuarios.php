<?php
session_start();
require_once __DIR__ . '/../../server/config.php';

if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 3) {
    header("Location: ../public/acceso_denegado.php");
    exit();
}

// Obtener usuarios activos
$stmt = $pdo->query("
    SELECT u.ID, u.cuenta, u.nivel, p.nombres, p.apaterno, p.amaterno, p.correo 
    FROM usuario u
    JOIN persona p ON u.Fk_persona = p.ID 
    WHERE u.activo = 1
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
  <h2>Gestión de Usuarios</h2>
  <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalUsuario">+ Nuevo Usuario</button>

  <table class="table table-striped table-sm">
    <thead class="table-dark">
      <tr>
        <th>Cuenta</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Nivel</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $usuario): ?>
      <tr>
        <td><?= htmlspecialchars($usuario['cuenta']) ?></td>
        <td><?= htmlspecialchars("{$usuario['nombres']} {$usuario['apaterno']} {$usuario['amaterno']}") ?></td>
        <td><?= htmlspecialchars($usuario['correo']) ?></td>
        <td><?= htmlspecialchars($usuario['nivel']) ?></td>
        <td>
          <button class="btn btn-warning btn-sm" onclick="editarUsuario(<?= $usuario['ID'] ?>)">Editar</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- MODAL USUARIO -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formUsuario" action="../../server/usuarios_acciones.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="idUsuario">

        <div class="mb-3">
          <label for="cuenta" class="form-label">Cuenta</label>
          <input type="text" name="cuenta" id="cuenta" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="contrasena" class="form-label">Contraseña</label>
          <input type="text" name="contrasena" id="contrasena" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="nivel" class="form-label">Nivel</label>
          <select name="nivel" id="nivel" class="form-select" required>
            <option value="0">Enlace Externo</option>
            <option value="1">Enlace Local</option>
            <option value="2">General</option>
            <option value="3">Admin</option>
            <option value="4">SuperSU</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- SCRIPTS -->
<script>
function editarUsuario(id) {
  fetch(`../../server/usuario_datos.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data && data.success !== false) {
        document.getElementById('idUsuario').value = data.ID;
        document.getElementById('cuenta').value = data.cuenta;
        document.getElementById('contrasena').value = data.contrasena;
        document.getElementById('nivel').value = data.nivel;
        const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
        modal.show();
      } else {
        alert('Error al obtener datos del usuario.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error en la solicitud');
    });
}
</script>
