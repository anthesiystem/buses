<?php
require_once '../../../server/config.php';
$registros = $pdo->query("
  SELECT r.*, 
         e.descripcion AS Entidad, 
         d.descripcion AS Dependencia, 
         b.descripcion AS Bus, 
         mb.descripcion AS Motor_Base, 
         v.descripcion AS Version, 
         eb.descripcion AS Estado
  FROM registro r
  LEFT JOIN entidad e ON e.ID = r.Fk_entidad
  LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia
  LEFT JOIN bus b ON b.ID = r.Fk_bus
  LEFT JOIN motor_base mb ON mb.ID = r.Fk_motor_base
  LEFT JOIN version v ON v.ID = r.Fk_version
  LEFT JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
  WHERE r.activo = 1
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../public/css/estilo.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">

<main class="flex-shrink-0">
  <div class="container">
    <h3 class="my-3" id="titulo">Registros</h3>

    <a href="nuevo.php" class="btn btn-success">Agregar</a>

    <table class="table table-bordered table-hover my-3">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Entidad</th>
          <th>Dependencia</th>
          <th>Bus</th>
          <th>Engine</th>
          <th>Versión</th>
          <th>Estado</th>
          <th>Inicio</th>
          <th>Migración</th>
          <th>Avance</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registros as $r): ?>
          <tr>
            <td><?= $r['ID'] ?></td>
            <td><?= $r['Entidad'] ?></td>
            <td><?= $r['Dependencia'] ?></td>
            <td><?= $r['Bus'] ?></td>
            <td><?= $r['Motor_Base'] ?></td>
            <td><?= $r['Version'] ?></td>
            <td><?= $r['Estado'] ?></td>
            <td><?= $r['fecha_inicio'] ?></td>
            <td><?= $r['fecha_migracion'] ?></td>
            <td><?= $r['avance'] ?>%</td>
            <td>
              <a href="edita.php?id=<?= $r['ID'] ?>" class="btn btn-warning btn-sm me-2">Editar</a>
              <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                      data-bs-target="#eliminaModal" data-bs-id="<?= $r['ID'] ?>">Eliminar</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<footer class="footer mt-auto py-3 bg-body-tertiary">
  <div class="container">
    <span class="text-body-secondary">2025 | Sistema de Seguimiento</span>
  </div>
</footer>

<!-- Modal de eliminación -->
<div class="modal fade" id="eliminaModal" tabindex="-1" aria-labelledby="eliminaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="elimina.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eliminaModalLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que deseas eliminar este registro?
      </div>
      <div class="modal-footer">
        <input type="hidden" name="id" id="idEliminar">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const eliminaModal = document.getElementById('eliminaModal');
  eliminaModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-bs-id');
    document.getElementById('idEliminar').value = id;
  });
</script>

</body>
</html>
