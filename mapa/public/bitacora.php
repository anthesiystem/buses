<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$host = 'localhost';
$db = 'busmap';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_tabla = $_GET['tabla'] ?? '';

$where = [];
$params = [];

if ($filtro_usuario !== '') {
    $where[] = 'u.Id = ?';
    $params[] = $filtro_usuario;
}
if ($filtro_accion !== '') {
    $where[] = 'b.Tipo_Accion = ?';
    $params[] = $filtro_accion;
}
if ($filtro_fecha !== '') {
    $where[] = 'DATE(b.Fecha_Accion) = ?';
    $params[] = $filtro_fecha;
}
if ($filtro_tabla !== '') {
    $where[] = 'b.Tabla_Afectada = ?';
    $params[] = $filtro_tabla;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT 
        b.Id,
        u.user AS Usuario,
        b.Tabla_Afectada,
        b.Id_Registro_Afectado,
        b.Tipo_Accion,
        b.Descripcion,
        b.Fecha_Accion
    FROM bitacora b
    INNER JOIN usuarios u ON u.Id = b.Fk_Id_Usuarios
    $where_sql
    ORDER BY b.Fecha_Accion DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener listas para filtros
$usuarios_result = $conn->query("SELECT Id, user FROM usuarios ORDER BY user");
$tablas_result = $conn->query("SELECT DISTINCT Tabla_Afectada FROM bitacora ORDER BY Tabla_Afectada");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

 <style>
.mt-4 {
    margin-top: 1.5rem !important;
    padding: 46px;
}
  </style>


</head>
<body class="bg-light">
  <?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">Bitácora de Auditoría</h2>

    <form method="get" class="row g-2 mb-4">
        <div class="col-md-3">
            <label for="usuario" class="form-label">Usuario</label>
            <select name="usuario" id="usuario" class="form-select">
                <option value="">Todos</option>
                <?php while ($u = $usuarios_result->fetch_assoc()): ?>
                    <option value="<?= $u['Id'] ?>" <?= ($filtro_usuario == $u['Id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['user']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="accion" class="form-label">Tipo de Acción</label>
            <select name="accion" id="accion" class="form-select">
                <option value="">Todas</option>
                <option value="INSERT" <?= $filtro_accion == 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                <option value="UPDATE" <?= $filtro_accion == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                <option value="DELETE" <?= $filtro_accion == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                <option value="ACTIVAR" <?= $filtro_accion == 'ACTIVAR' ? 'selected' : '' ?>>ACTIVAR</option>
                <option value="DESACTIVAR" <?= $filtro_accion == 'DESACTIVAR' ? 'selected' : '' ?>>DESACTIVAR</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="tabla" class="form-label">Tabla Afectada</label>
            <select name="tabla" id="tabla" class="form-select">
                <option value="">Todas</option>
                <?php while ($t = $tablas_result->fetch_assoc()): ?>
                    <option value="<?= $t['Tabla_Afectada'] ?>" <?= ($filtro_tabla == $t['Tabla_Afectada']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['Tabla_Afectada']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-12 mt-3">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Tabla Afectada</th>
                    <th>ID Registro</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['Id'] ?></td>
                    <td><?= htmlspecialchars($row['Usuario']) ?></td>
                    <td><?= htmlspecialchars($row['Tabla_Afectada']) ?></td>
                    <td><?= $row['Id_Registro_Afectado'] ?? '-' ?></td>
                    <td><?= $row['Tipo_Accion'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['Descripcion'])) ?></td>
                    <td><?= $row['Fecha_Accion'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
