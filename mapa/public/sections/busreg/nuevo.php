<?php
require_once '../../server/config.php';

function obtenerCatalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}

$entidades    = obtenerCatalogo($pdo, 'entidad');
$dependencias = obtenerCatalogo($pdo, 'dependencia');
$buses        = obtenerCatalogo($pdo, 'bus');
$engines      = obtenerCatalogo($pdo, 'engine');
$versiones    = $pdo->query("SELECT v.ID, CONCAT(v.descripcion, ' - ', t.descripcion) AS descripcion 
                             FROM version v 
                             JOIN tecnologia t ON v.Fk_ID_tecnologia = t.ID 
                             WHERE v.activo = 1 
                             ORDER BY v.descripcion")->fetchAll(PDO::FETCH_ASSOC);
$estatuses    = obtenerCatalogo($pdo, 'estado_bus');
$categorias   = obtenerCatalogo($pdo, 'categoria');
?>

<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
  <meta charset="UTF-8">
  <title>Nuevo registro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../public/css/estilo.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">

<main class="flex-shrink-0">
  <div class="container">
    <h3 class="my-3">Nuevo registro</h3>

    <form action="guardar.php" method="post" class="row g-3" autocomplete="off">

      <div class="col-md-6">
        <label for="Fk_ID_entidad" class="form-label">Entidad</label>
        <select name="Fk_ID_entidad" id="Fk_ID_entidad" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($entidades as $e): ?>
            <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_ID_dependencia" class="form-label">Dependencia</label>
        <select name="Fk_ID_dependencia" id="Fk_ID_dependencia" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($dependencias as $d): ?>
            <option value="<?= $d['ID'] ?>"><?= $d['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_ID_bus" class="form-label">Bus</label>
        <select name="Fk_ID_bus" id="Fk_ID_bus" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($buses as $b): ?>
            <option value="<?= $b['ID'] ?>"><?= $b['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_ID_engine" class="form-label">Engine</label>
        <select name="Fk_ID_engine" id="Fk_ID_engine" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($engines as $en): ?>
            <option value="<?= $en['ID'] ?>"><?= $en['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_ID_version" class="form-label">Versión</label>
        <select name="Fk_ID_version" id="Fk_ID_version" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($versiones as $v): ?>
            <option value="<?= $v['ID'] ?>"><?= $v['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_ID_estado_bus" class="form-label">Estatus</label>
        <select name="Fk_ID_estado_bus" id="Fk_ID_estado_bus" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($estatuses as $e): ?>
            <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_ID_categoria" class="form-label">Categoría</label>
        <select name="Fk_ID_categoria" id="Fk_ID_categoria" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['ID'] ?>"><?= $c['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="fecha_inicio" class="form-label">Fecha inicio</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
      </div>

      <div class="col-md-6">
        <label for="fecha_migracion" class="form-label">Fecha migración</label>
        <input type="date" name="fecha_migracion" id="fecha_migracion" class="form-control">
      </div>

      <div class="col-md-4">
        <label for="avance" class="form-label">Avance (%)</label>
        <input type="number" name="avance" id="avance" class="form-control" min="0" max="100" step="1" required>
      </div>

      <div class="col-12">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>

    </form>
  </div>
</main>

<footer class="footer mt-auto py-3 bg-body-tertiary">
  <div class="container">
    <span class="text-body-secondary">2025 | Sistema de Seguimiento</span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
