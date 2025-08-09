<?php
require_once '../../../server/config.php';

// Validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  header("Location: ../buses.php"); // <-- ajusta a tu listado real
  exit;
}

// Obtener registro
$stmt = $pdo->prepare("SELECT * FROM registro WHERE ID = ?");
$stmt->execute([$id]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$registro) {
  header("Location: ../buses.php"); // <-- ajusta a tu listado real
  exit;
}

// Catálogos
function obtenerCatalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}

$entidades    = obtenerCatalogo($pdo, 'entidad');
$dependencias = obtenerCatalogo($pdo, 'dependencia');
$buses        = obtenerCatalogo($pdo, 'bus');
$engines      = obtenerCatalogo($pdo, 'motor_base');
$versiones    = $pdo->query("
  SELECT v.ID, CONCAT(v.descripcion, ' - ', t.descripcion) AS descripcion 
  FROM version v 
  JOIN tecnologia t ON v.Fk_tecnologia = t.ID 
  WHERE v.activo = 1 
  ORDER BY v.descripcion
")->fetchAll(PDO::FETCH_ASSOC);
$estatuses    = obtenerCatalogo($pdo, 'estado_bus');
$categorias   = obtenerCatalogo($pdo, 'categoria');
?>
<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
  <meta charset="UTF-8">
  <title>Editar registro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/estilo.css" rel="stylesheet"><!-- ruta corregida -->
</head>
<body class="d-flex flex-column h-100">

<main class="flex-shrink-0">
  <div class="container">
    <h3 class="my-3">Editar registro</h3>

    <!-- IMPORTANTE: los name del form coinciden con las columnas reales -->
    <form action="actualizar.php" method="post" class="row g-3" autocomplete="off">
      <input type="hidden" name="ID" value="<?= htmlspecialchars($registro['ID']) ?>">

      <div class="col-md-6">
        <label for="Fk_entidad" class="form-label">Entidad</label>
        <select name="Fk_entidad" id="Fk_entidad" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($entidades as $e): ?>
            <option value="<?= $e['ID'] ?>" <?= $e['ID'] == $registro['Fk_entidad'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_dependencia" class="form-label">Dependencia</label>
        <select name="Fk_dependencia" id="Fk_dependencia" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($dependencias as $d): ?>
            <option value="<?= $d['ID'] ?>" <?= $d['ID'] == $registro['Fk_dependencia'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_bus" class="form-label">Bus</label>
        <select name="Fk_bus" id="Fk_bus" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($buses as $b): ?>
            <option value="<?= $b['ID'] ?>" <?= $b['ID'] == $registro['Fk_bus'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_motor_base" class="form-label">Engine</label>
        <select name="Fk_motor_base" id="Fk_motor_base" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($engines as $en): ?>
            <option value="<?= $en['ID'] ?>" <?= $en['ID'] == $registro['Fk_motor_base'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($en['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="Fk_version" class="form-label">Versión</label>
        <select name="Fk_version" id="Fk_version" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($versiones as $v): ?>
            <option value="<?= $v['ID'] ?>" <?= $v['ID'] == $registro['Fk_version'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($v['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_estado_bus" class="form-label">Estatus</label>
        <select name="Fk_estado_bus" id="Fk_estado_bus" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($estatuses as $e): ?>
            <option value="<?= $e['ID'] ?>" <?= $e['ID'] == $registro['Fk_estado_bus'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="Fk_categoria" class="form-label">Categoría</label>
        <select name="Fk_categoria" id="Fk_categoria" class="form-select" required>
          <option value="">Seleccionar</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['ID'] ?>" <?= $c['ID'] == $registro['Fk_categoria'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="fecha_inicio" class="form-label">Fecha inicio</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
               value="<?= htmlspecialchars($registro['fecha_inicio']) ?>" required>
      </div>

      <div class="col-md-6">
        <label for="fecha_migracion" class="form-label">Fecha migración</label>
        <input type="date" name="fecha_migracion" id="fecha_migracion" class="form-control"
               value="<?= htmlspecialchars($registro['fecha_migracion']) ?>">
      </div>

      <div class="col-md-4">
        <label for="avance" class="form-label">Avance (%)</label>
        <input type="number" name="avance" id="avance" class="form-control" min="0" max="100" step="1"
               value="<?= htmlspecialchars($registro['avance']) ?>" required>
      </div>

      <div class="col-12">
        <a href="../buses.php" class="btn btn-secondary">Cancelar</a> <!-- ajusta a tu listado -->
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
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
