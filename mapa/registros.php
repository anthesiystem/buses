<?php
session_start();
require_once '../server/config.php';

// Obtener catálogos activos
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

// Si se está editando
$modo_edicion = false;
$registro = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM registro WHERE Id = ?");
    $stmt->execute([$_GET['edit']]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    $modo_edicion = true;
}

// Obtener registros activos
$where = "r.Activo = 1";
$params = [];

if (!empty($_GET['f_entidad'])) {
    $where .= " AND r.Fk_Id_Entidad = " . intval($_GET['f_entidad']);
}
if (!empty($_GET['f_bus'])) {
    $where .= " AND r.Fk_Id_Bus = " . intval($_GET['f_bus']);
}
if (!empty($_GET['f_estatus'])) {
    $where .= " AND r.Fk_Id_Estatus = " . intval($_GET['f_estatus']);
}
if (!empty($_GET['f_tecnologia'])) {
    $where .= " AND r.Fk_Id_Tecnologia = " . intval($_GET['f_tecnologia']);
}
if (!empty($_GET['f_engine'])) {
    $where .= " AND r.Fk_Id_Engine = " . intval($_GET['f_engine']);
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
    <meta charset="UTF-8">
    <title>Gestión de Registros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    function validarAvance() {
        const estatus = document.getElementById('Fk_Id_Estatus');
        const avance = document.getElementById('Avance');
        if (estatus.value == 3) {
            avance.value = 100;
            avance.setAttribute('readonly', 'readonly');
        } else {
            avance.removeAttribute('readonly');
            if (avance.value > 99) avance.value = 99;
        }
    }
    window.onload = () => {
        document.getElementById('Fk_Id_Estatus').addEventListener('change', validarAvance);
        validarAvance();

        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('Fecha_Inicio').setAttribute('max', hoy);
        document.getElementById('Migracion').setAttribute('max', hoy);
    };
    </script>
</head>
<body class="container mt-4">
    <h2><?= $modo_edicion ? 'Editar Registro' : 'Agregar Registro' ?></h2>
    <form method="POST" action="<?= $modo_edicion ? 'editar_registro.php' : 'guardar_registro.php' ?>">
        <?php if ($modo_edicion): ?>
            <input type="hidden" name="Id" value="<?= $registro['Id'] ?>">
        <?php endif; ?>

        <?php
        function generarSelect($label, $name, $datos, $selectedId = null) {
            echo "<div class='mb-2'><label>$label</label><select name='$name' class='form-select' required>";
            echo "<option value=''>Selecciona</option>";
            foreach ($datos as $item) {
                $selected = ($item['Id'] == $selectedId) ? 'selected' : '';
                echo "<option value='{$item['Id']}' $selected>{$item['Nombre']}</option>";
            }
            echo "</select></div>";
        }

        generarSelect('Engine', 'Fk_Id_Engine', $engines, $registro['Fk_Id_Engine'] ?? null);
        generarSelect('Tecnología', 'Fk_Id_Tecnologia', $tecnologias, $registro['Fk_Id_Tecnologia'] ?? null);
        generarSelect('Dependencia', 'Fk_Id_Dependencia', $dependencias, $registro['Fk_Id_Dependencia'] ?? null);
        generarSelect('Entidad', 'Fk_Id_Entidad', $entidades, $registro['Fk_Id_Entidad'] ?? null);
        generarSelect('Bus', 'Fk_Id_Bus', $buses, $registro['Fk_Id_Bus'] ?? null);
        generarSelect('Estatus', 'Fk_Id_Estatus', $estatuses, $registro['Fk_Id_Estatus'] ?? null);
        ?>

        <div class="mb-2">
            <label>Versión</label>
            <input type="text" name="Version" class="form-control" required value="<?= $registro['Version'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <label>Fecha de Inicio</label>
            <input type="date" name="Fecha_Inicio" id="Fecha_Inicio" class="form-control" required value="<?= $registro['Fecha_Inicio'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <label>Migración</label>
            <input type="date" name="Migracion" id="Migracion" class="form-control" value="<?= $registro['Migracion'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <label>Avance (%)</label>
            <input type="number" name="Avance" id="Avance" class="form-control" min="0" max="100" value="<?= $registro['Avance'] ?? 0 ?>">
        </div>

        <button class="btn btn-primary" type="submit"><?= $modo_edicion ? 'Actualizar' : 'Agregar' ?></button>
        <?php if ($modo_edicion): ?>
            <a href="registros.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>
    


    <h4>Filtrar registros</h4>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-2">
    <select name="f_entidad" class="form-select">
      <option value="">Entidad</option>
      <?php foreach ($entidades as $e): ?>
        <option value="<?= $e['Id'] ?>" <?= (isset($_GET['f_entidad']) && $_GET['f_entidad'] == $e['Id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_bus" class="form-select">
      <option value="">Bus</option>
      <?php foreach ($buses as $b): ?>
        <option value="<?= $b['Id'] ?>" <?= (isset($_GET['f_bus']) && $_GET['f_bus'] == $b['Id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_estatus" class="form-select">
      <option value="">Estatus</option>
      <?php foreach ($estatuses as $e): ?>
        <option value="<?= $e['Id'] ?>" <?= (isset($_GET['f_estatus']) && $_GET['f_estatus'] == $e['Id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_tecnologia" class="form-select">
      <option value="">Tecnología</option>
      <?php foreach ($tecnologias as $t): ?>
        <option value="<?= $t['Id'] ?>" <?= (isset($_GET['f_tecnologia']) && $_GET['f_tecnologia'] == $t['Id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($t['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_engine" class="form-select">
      <option value="">Engine</option>
      <?php foreach ($engines as $e): ?>
        <option value="<?= $e['Id'] ?>" <?= (isset($_GET['f_engine']) && $_GET['f_engine'] == $e['Id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['Nombre']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="f_categoria" class="form-select">
      <option value="">Categoría</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c['Id'] ?>" <?= (isset($_GET['f_categoria']) && $_GET['f_categoria'] == $c['Id']) ? 'selected' : '' ?>>
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




    <table class="table table-bordered table-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Bus</th>
                <th>Versión</th>
                <th>Estatus</th>
                <th>Avance</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= $r['Id'] ?></td>
                <td><?= $r['Fk_Id_Bus'] ?></td>
                <td><?= $r['Version'] ?></td>
                <td><?= $r['EstatusNombre'] ?></td>
                <td><?= $r['Avance'] ?>%</td>
                <td>
                    <a href="?edit=<?= $r['Id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="desactivar_registro.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-danger">Desactivar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
