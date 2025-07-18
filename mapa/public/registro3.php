<?php
session_start();
if ($_SESSION['nivel'] != 2 && $_SESSION['nivel'] != 3) {
    // Si el nivel NO es 2 o 3, sacarlo
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

      body {
  font-family: Arial, sans-serif;
  
}
html, body {
  height: 100%;
  margin: 0;
  background-color: #6e7e8a !important;
}


.contenedor {
  margin-top: 56px; /* altura del navbar fijo */
  height: calc(100vh - 56px);
  display: flex;
}

#mapa {
  flex: 2;    /* toma 2 partes */
  background: steelblue;
  display: flex;
  justify-content: center;
  align-items: center;

}

#info {
  flex: 1;    /* toma 1 parte */
  padding: 10px;
  border-left: 1px solid #ccc;
  background: slategrey;
}


#mapa, #info {
  height: 100%;
}

#tooltip {
  position: absolute;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  display: none;
  pointer-events: none;
  font-size: 14px;
}


.navbar {
  background-color: #222;
  overflow: hidden;
  padding: 10px 20px;
}
.navbar a {
  float: left;
  display: block;
  color: #fff;
  text-align: center;
  padding: 10px 16px;
  text-decoration: none;
  font-weight: bold;
}
.navbar a:hover {
  background-color: #555;
}

.tabla-responsiva {
  overflow-x: auto;
}

.tabla-responsiva table {
  width: 100%;
  border-collapse: collapse;
}
* --- TABLERO2.PHP ESTILOS --- */

/* Reajuste de #info solo cuando es necesario */
#info.tablero-ajuste {
  width: auto !important;
  padding: 0 !important;
  border: none !important;
}

#info-tablero {
  width: 100%;
  padding: 30px;
  margin: 0 auto;
}

#info-tablero h1 {
  text-align: center;
  font-size: 28px;
  margin-bottom: 30px;
  text-transform: uppercase;
}

.zona-tablero * {
  /*all: unset;*/
  box-sizing: border-box;
}

.zona-tablero {
  display: flex;
  justify-content: center;
  width: 100%;
  margin-top: 40px;
  font-family: Arial, sans-serif;
}

.contenedor-dashboard {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: flex-start;
  gap: 60px;
  padding: 30px;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
}

.panel-produccion {
  min-width: 250px;
  background-color: #f1f1f1;
  padding: 30px 20px;
  text-align: center;
  border-radius: 12px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.panel-produccion h1 {
  font-size: 28px;
  margin-bottom: 20px;
  font-weight: bold;
  text-align: center;
}

.panel-produccion p {
  font-size: 24px;
  margin: 0;
}

.cantidad-total {
  display: block;
  font-size: 86px !important;
  font-weight: bold !important;
  color: #222;
  margin: 0;
  text-align: center;
}

.tabla-articulos {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 30px;
  justify-items: center;
}

.articulo img {
  width: 70px;
  height: 70px;
}

.nombre-bus {
  font-size: 16px;
  margin-top: 10px;
  color: #555;
}

.cantidad-bus {
  font-size: 32px;
  font-weight: bold;
  color: #222;
  margin-top: 8px;
}

.articulo {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

#info.tablero-ajuste {
  width: 100% !important;
  padding: 0 !important;
  border: none !important;
}
    </style>
</head>
<body>
  <?php include 'navbar.php'; ?>
<div class="container">
  <h3 class="mb-4">Gestión de Registro</h3>

  <form method="post" action="../server/acciones.php" class="row g-3 mb-4">
    <input type="hidden" name="id" id="id">

    <div class="col-md-3">
      <label class="form-label">Tecnología</label>
      <select name="fk_tecnologia" class="form-select" required>
        <?php foreach ($tecnologias as $row): ?>
            <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Dependencia</label>
      <select name="fk_dependencia" class="form-select" required>
        <?php foreach ($dependencias as $row): ?>
            <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Entidad</label>
      <select name="fk_entidad" class="form-select" required>
        <?php foreach ($entidades as $row): ?>
            <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Bus</label>
      <select name="fk_bus" class="form-select" required>
        <?php foreach ($buses as $row): ?>
            <option value="<?= $row['Id'] ?>"><?= $row['Nombre'] ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Estatus</label>
      <select name="fk_estatus" class="form-select" required>
        <?php foreach ($estatuses as $row): ?>
            <option value="<?= $row['Id'] ?>"><?= $row['Valor'] ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Avance (%)</label>
      <input type="number" name="avance" class="form-control" min="0" max="100" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Versión</label>
      <input type="text" name="version" class="form-control" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Fecha Inicio</label>
      <input type="date" name="fecha_inicio" class="form-control" max="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Migración</label>
      <input type="date" name="migracion" class="form-control" max="<?= date('Y-m-d') ?>">
    </div>

    <div class="col-12 d-flex gap-2">
      <button type="submit" name="agregar" class="btn btn-success">Agregar</button>
      <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
      <?php if ($_SESSION['nivel'] == 3) { echo '<button type="submit" name="eliminar" class="btn btn-danger">Eliminar</button>'; } ?>
    </div>
  </form>

  <hr>
  <h3>Registros existentes</h3>

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
        <tr>
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
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

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
</script>
</body>
</html>
