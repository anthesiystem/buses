<?php
session_start();
require_once '../../server/config.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.php");
  exit;
}

// Obtener registros existentes
$stmt = $pdo->query("
SELECT 
  r.ID,
  r.fecha_inicio,
  r.fecha_migracion,
  r.avance,
  r.fecha_creacion,
  r.fecha_modificacion,
  d.descripcion AS Dependencia,
  e.descripcion AS Entidad,
  b.descripcion AS Bus,
  en.descripcion AS Engine,
  v.descripcion AS Version,
  c.descripcion AS Categoria,
  eb.descripcion AS Estado
FROM registro r
INNER JOIN dependencia d ON d.ID = r.Fk_dependencia
INNER JOIN entidad e ON e.ID = r.Fk_entidad
LEFT JOIN bus b ON b.ID = r.Fk_bus
INNER JOIN motor_base en ON en.ID = r.Fk_motor_base
INNER JOIN version v ON v.ID = r.Fk_version
INNER JOIN categoria c ON c.ID = r.Fk_categoria
INNER JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
WHERE r.activo = 1
ORDER BY r.fecha_creacion DESC;
");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catálogos
function catalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}
$dependencias = catalogo($pdo, 'dependencia');
$entidades    = catalogo($pdo, 'entidad');
$buses        = catalogo($pdo, 'bus');
$engines      = catalogo($pdo, 'motor_base');
$versiones    = $pdo->query("SELECT v.ID, CONCAT(v.descripcion, ' - ', t.descripcion) AS descripcion FROM version v JOIN tecnologia t ON v.Fk_tecnologia = t.ID WHERE v.activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$estatuses    = catalogo($pdo, 'estado_bus');
$categorias   = catalogo($pdo, 'categoria');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
  <h3>Gestión de Registros</h3>
  <button class="btn btn-success mb-3" onclick="abrirModal()">+ Nuevo</button>

  <table class="table table-bordered table-sm">
    <thead class="table-dark text-center">
      <tr>
        <th>ID</th><th>Dependencia</th><th>Entidad</th><th>Bus</th><th>Engine</th><th>Versión</th><th>Estatus</th><th>Avance</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($registros as $r): ?>
        <tr>
          <td><?= $r['ID'] ?></td>
          <td><?= $r['Dependencia'] ?></td>
          <td><?= $r['Entidad'] ?></td>
          <td><?= $r['Bus'] ?></td>
          <td><?= $r['Engine'] ?></td>
          <td><?= $r['Version'] ?></td>
          <td><?= $r['Estado'] ?></td>
          <td><?= $r['avance'] ?>%</td>
          <td>
            <?php $json = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8'); ?>
            <button class="btn btn-sm btn-primary" onclick="editar(<?= $json ?>)">Editar</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Modal -->
  <!-- Modal de Registro -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formRegistro">
  <div class="modal-header bg-primary text-white">
    <h5 class="modal-title">Registro</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <input type="hidden" name="ID" id="ID">

    <!-- Sección 1: Dependencia, Entidad, Bus -->
    <fieldset class="border p-2 mb-3">
      <legend class="w-auto fs-6">Ubicación</legend>
      <div class="row">
        <div class="col-md-4">
          <label>Dependencia</label>
          <select class="form-select" name="Fk_dependencia" required>
            <option value="">Seleccione</option>
            <?php foreach ($dependencias as $d): ?>
              <option value="<?= $d['ID'] ?>"><?= $d['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Entidad</label>
          <select class="form-select" name="Fk_entidad" required>
            <option value="">Seleccione</option>
            <?php foreach ($entidades as $e): ?>
              <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Bus</label>
          <select class="form-select" name="Fk_bus" required>
            <option value="">Seleccione</option>
            <?php foreach ($buses as $b): ?>
              <option value="<?= $b['ID'] ?>"><?= $b['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </fieldset>

    <!-- Sección 2: Engine, Versión, Categoría -->
    <fieldset class="border p-2 mb-3">
      <legend class="w-auto fs-6">Tecnología</legend>
      <div class="row">
        <div class="col-md-4">
          <label>Engine</label>
          <select class="form-select" name="Fk_motor_base" required>
            <option value="">Seleccione</option>
            <?php foreach ($engines as $en): ?>
              <option value="<?= $en['ID'] ?>"><?= $en['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Versión</label>
          <select class="form-select" name="Fk_version" required>
            <option value="">Seleccione</option>
            <?php foreach ($versiones as $v): ?>
              <option value="<?= $v['ID'] ?>"><?= $v['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Categoría</label>
          <select class="form-select" name="Fk_categoria" id="Fk_categoria" required>
            <option value="">Seleccione</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['ID'] ?>"><?= $c['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </fieldset>

    <!-- Sección 3: Estatus, Fechas, Avance -->
    <fieldset class="border p-2 mb-3">
      <legend class="w-auto fs-6">Estatus</legend>
      <div class="row">
        <div class="col-md-4">
          <label>Estatus</label>
          <select class="form-select" name="Fk_estado_bus" id="Fk_estado_bus" required>
            <option value="">Seleccione</option>
            <?php foreach ($estatuses as $e): ?>
              <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Fecha Inicio</label>
          <input type="date" class="form-control" name="fecha_inicio" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-4">
          <label>Fecha Migración</label>
          <input type="date" class="form-control" name="fecha_migracion" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-4 mt-2">
          <label>Avance (%)</label>
          <input type="number" class="form-control" name="avance" id="avance" min="0" max="100" required>
        </div>
      </div>
    </fieldset>
  </div>
  <div class="modal-footer">
    <button type="submit" class="btn btn-success">Guardar</button>
  </div>
</form>

    </div>
  </div>
</div>


<?php
// Procesar guardado (insert o update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $avance = ($_POST['Fk_estado_bus'] == 3) ? 100 : min((int)$_POST['avance'], 99);
  if (!empty($_POST['ID'])) {
    $stmt = $pdo->prepare("UPDATE registro SET Fk_dependencia=?, Fk_entidad=?, Fk_bus=?, Fk_motor_base=?, Fk_version=?, Fk_estado_bus=?, Fk_categoria=?, fecha_inicio=?, fecha_migracion=?, avance=?, fecha_modificacion=NOW() WHERE ID=?");
    $stmt->execute([
      $_POST['Fk_dependencia'], $_POST['Fk_entidad'], $_POST['Fk_bus'],
      $_POST['Fk_motor_base'], $_POST['Fk_version'], $_POST['Fk_estado_bus'],
      $_POST['Fk_categoria'], $_POST['fecha_inicio'], $_POST['fecha_migracion'],
      $avance, $_POST['ID']
    ]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO registro (Fk_dependencia, Fk_entidad, Fk_bus, Fk_motor_base, Fk_version, Fk_estado_bus, Fk_categoria, fecha_inicio, fecha_migracion, avance, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
      $_POST['Fk_dependencia'], $_POST['Fk_entidad'], $_POST['Fk_bus'],
      $_POST['Fk_motor_base'], $_POST['Fk_version'], $_POST['Fk_estado_bus'],
      $_POST['Fk_categoria'], $_POST['fecha_inicio'], $_POST['fecha_migracion'],
      $avance
    ]);
  }
  header("Location: registros.php");
  exit;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModal() {
  document.querySelector("#modalRegistro form").reset();
  document.querySelector("#ID").value = "";
  new bootstrap.Modal(document.getElementById("modalRegistro")).show();
}

function editar(datos) {
  Object.entries(datos).forEach(([k, v]) => {
    const campo = document.querySelector(`[name="${k}"]`);
    if (campo) campo.value = v;
  });
  new bootstrap.Modal(document.getElementById("modalRegistro")).show();
}
</script>


<!-- <script>
document.querySelector("#modalRegistro form").addEventListener("submit", function(e) {
  e.preventDefault();
  const form = e.target;
  const datos = new FormData(form);

  fetch("/mapa/server/acciones/guardar_registro.php", {
    method: "POST",
    body: datos
  })
  .then(res => res.json())
  .then(resp => {
    if (resp.success) {
      bootstrap.Modal.getInstance(document.getElementById("modalRegistro")).hide();
      cargarRegistros(); // función que volverá a cargar los datos sin recargar la página
    } else {
      alert("Error: " + resp.error);
    }
  });
});

function cargarRegistros() {
  location.reload(); // temporal, hasta que se haga con AJAX
}
</script> -->


<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formRegistro");

  // --- Llenado de catálogos de forma segura ---
  const dataCatalogos = {
    "Fk_ID_dependencia": <?= json_encode($dependencias) ?>,
    "Fk_ID_entidad": <?= json_encode($entidades) ?>,
    "Fk_ID_bus": <?= json_encode($buses) ?>,
    "Fk_ID_motor_base": <?= json_encode($engines) ?>,
    "Fk_ID_version": <?= json_encode($versiones) ?>,
    "Fk_ID_categoria": <?= json_encode($categorias) ?>,
    "Fk_ID_estado_bus": <?= json_encode($estatuses) ?>
  };

  for (const [campo, opciones] of Object.entries(dataCatalogos)) {
    const select = form ? form.querySelector(`[name="${campo}"]`) : document.querySelector(`[name="${campo}"]`);
    if (!select) continue;
    if (!Array.isArray(opciones)) continue; // evita forEach sobre null/undefined

    opciones.forEach(opt => {
      const o = document.createElement("option");
      o.value = opt.ID;
      o.text = opt.descripcion;
      select.appendChild(o);
    });
  }

  // --- Max de fechas = hoy (si existen) ---
  const maxDate = new Date().toISOString().split('T')[0];
  const fechaInicio = form ? form.querySelector('[name="fecha_inicio"]') : document.querySelector('[name="fecha_inicio"]');
  const fechaMigracion = form ? form.querySelector('[name="fecha_migracion"]') : document.querySelector('[name="fecha_migracion"]');
  if (fechaInicio) fechaInicio.max = maxDate;
  if (fechaMigracion) fechaMigracion.max = maxDate;

  // --- Función editar (usa form si existe) ---
  window.editar = function editar(datos) {
    const frm = document.querySelector("#formRegistro");
    if (!frm) return;
    Object.entries(datos).forEach(([k, v]) => {
      const campo = frm.querySelector(`[name="${k}"]`);
      if (campo) campo.value = v ?? "";
    });
    const modalEl = document.getElementById("modalRegistro");
    if (modalEl) new bootstrap.Modal(modalEl).show();
  };

  // --- Submit con validaciones seguras ---
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const estatus = form.querySelector('[name="Fk_ID_estado_bus"]');
      const categoria = form.querySelector('[name="Fk_ID_categoria"]');
      const avance = form.querySelector('[name="avance"]');

      // Si faltan campos, enviamos sin autoadjustes para evitar errores
      if (!estatus || !categoria || !avance) {
        this.submit();
        return;
      }

      // Accesos seguros a options / selectedIndex
      const estSel = estatus.selectedIndex >= 0 ? estatus.options[estatus.selectedIndex] : null;
      const catSel = categoria.selectedIndex >= 0 ? categoria.options[categoria.selectedIndex] : null;

      const getValueByText = (selectEl, text) => {
        const opt = Array.from(selectEl.options).find(o => o.text.trim() === text);
        return opt ? opt.value : null;
      };

      let cambio = false;
      const avanceNum = parseInt(avance.value || "0", 10);

      if (avanceNum === 100) {
        if (estSel && estSel.text !== "Implementado") {
          const v = getValueByText(estatus, "Implementado");
          if (v) { estatus.value = v; cambio = true; }
        }
        if (catSel && ["Migraciones", "Pruebas"].includes(catSel.text)) {
          const v = getValueByText(categoria, "Productivos");
          if (v) { categoria.value = v; cambio = true; }
        }
      } else if (estSel && estSel.text === "Implementado") {
        avance.value = 100;
        if (catSel && ["Migraciones", "Pruebas"].includes(catSel.text)) {
          const v = getValueByText(categoria, "Productivos");
          if (v) categoria.value = v;
        }
        cambio = true;
      } else if (
        catSel &&
        !["Migraciones", "Pruebas"].includes(catSel.text) &&
        catSel.text === "Productivos"
      ) {
        const v = getValueByText(estatus, "Implementado");
        if (v) estatus.value = v;
        avance.value = 100;
        cambio = true;
      }

      if (cambio) {
        alert("🚀 El registro fue marcado como Implementado y Productivo automáticamente.");
      }

      this.submit();
    });
  }
});
</script>

</body>
</html>
