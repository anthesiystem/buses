<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['nivel'] != 2 && $_SESSION['nivel'] != 4)) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db = 'seguimientobus';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $bus = $conn->real_escape_string($_POST['bus']);
    $version = $conn->real_escape_string($_POST['version']);
    $estatus = $conn->real_escape_string($_POST['estatus']);
    $avance = $conn->real_escape_string($_POST['avance']);
    $sql = "UPDATE segbus SET bus='$bus', version='$version', estatus='$estatus', avance='$avance' WHERE id=$id";
    if ($conn->query($sql)) {
        $mensaje = "<span style='color:green;'>Registro actualizado correctamente.</span>";
    } else {
        $mensaje = "<span style='color:red;'>Error al actualizar registro.</span>";
    }
}

$registros = [];
$sql = "SELECT id, entidad, bus, version, estatus, avance FROM segbus ORDER BY entidad, id LIMIT 100";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $registros[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Registros</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .edit-btn, .save-btn, .cancel-btn { cursor: pointer; color: #c00; font-weight: bold; background: none; border: none; }
    .save-btn { color: #080; }
    .cancel-btn { color: #888; }
    table { border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 4px 8px; }
    th { background: #333; color: #fff; }
    tr.editing { background: #ffffe0; }
  </style>
</head>
<body>
  <div class="navbar">
    <a href="index.php">Bus Prod</a>
    <a href="editar.php">Editar Registros</a>
    <a href="logout.php">Salir</a>
  </div>
  <div class="contenedor">
    <h2>Edición de Registros</h2>
    <?php if ($mensaje) echo "<p>$mensaje</p>"; ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Entidad</th>
          <th>Bus</th>
          <th>Version</th>
          <th>Estatus</th>
          <th>Avance</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($registros as $reg): ?>
        <tr data-id="<?= $reg['id'] ?>">
          <td><?= $reg['id'] ?></td>
          <td><?= htmlspecialchars($reg['entidad']) ?></td>
          <td class="bus"><?= htmlspecialchars($reg['bus']) ?></td>
          <td class="version"><?= htmlspecialchars($reg['version']) ?></td>
          <td class="estatus"><?= htmlspecialchars($reg['estatus']) ?></td>
          <td class="avance"><?= htmlspecialchars($reg['avance']) ?></td>
          <td>
            <button class="edit-btn">Editar</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script>
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
      const tr = this.closest('tr');
      if (tr.classList.contains('editing')) return;
      tr.classList.add('editing');
      const bus = tr.querySelector('.bus').textContent;
      const version = tr.querySelector('.version').textContent;
      const estatus = tr.querySelector('.estatus').textContent;
      const avance = tr.querySelector('.avance').textContent;
      tr.querySelector('.bus').innerHTML = `<input name="bus" value="${bus}" style="width:90%;">`;
      tr.querySelector('.version').innerHTML = `<input name="version" value="${version}" style="width:90%;">`;
      tr.querySelector('.estatus').innerHTML = `<input name="estatus" value="${estatus}" style="width:90%;">`;
      tr.querySelector('.avance').innerHTML = `<input name="avance" value="${avance}" style="width:90%;">`;
      this.style.display = "none";
      let td = tr.querySelector('td:last-child');
      td.innerHTML += ` <button class="save-btn">Guardar</button> <button class="cancel-btn">Cancelar</button>`;
      td.querySelector('.save-btn').onclick = function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `<input name="id" value="${tr.dataset.id}">
          <input name="bus" value="${tr.querySelector('input[name=bus]').value}">
          <input name="version" value="${tr.querySelector('input[name=version]').value}">
          <input name="estatus" value="${tr.querySelector('input[name=estatus]').value}">
          <input name="avance" value="${tr.querySelector('input[name=avance]').value}">`;
        document.body.appendChild(form);
        form.submit();
      };
      td.querySelector('.cancel-btn').onclick = function() {
        window.location.reload();
      };
    }
  });
  </script>
</body>
</html>
<?php $conn->close(); ?>