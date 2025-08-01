<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h2 class="mb-4">📋 Información de Sesión</h2>

    <table class="table table-bordered">
      <tbody>
        <tr>
          <th>Usuario</th>
          <td><?= $_SESSION['usuario'] ?? '---' ?></td>
        </tr>
        <tr>
          <th>ID de Usuario</th>
          <td><?= $_SESSION['usuario_id'] ?? '---' ?></td>
        </tr>
        <tr>
          <th>Nivel de Perfil</th>
          <td><?= $_SESSION['fk_perfiles'] ?? '---' ?></td>
        </tr>
        <tr>
          <th>Última Actividad</th>
          <td><?= isset($_SESSION['ultima_actividad']) ? date('Y-m-d H:i:s', $_SESSION['ultima_actividad']) : '---' ?></td>
        </tr>
      </tbody>
    </table>

    <h4 class="mt-5">🔐 Permisos Cargados</h4>
    <?php if (!empty($_SESSION['permisos'])): ?>
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>Módulo</th>
            <th>Acción</th>
            <th>Entidad</th>
            <th>Bus</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($_SESSION['permisos'] as $permiso): ?>
            <tr>
              <td><?= htmlspecialchars($permiso['modulo']) ?></td>
              <td><?= htmlspecialchars($permiso['accion']) ?></td>
              <td><?= $permiso['Fk_entidad'] ?? 'Todos' ?></td>
              <td><?= $permiso['Fk_bus'] ?? 'Todos' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted">Sin permisos cargados.</p>
    <?php endif; ?>

  </div>
</body>
</html>
