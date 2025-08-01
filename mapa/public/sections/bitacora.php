<?php
session_start();
require_once '../../server/config.php';

if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 4) {
    header("Location: acceso_denegado.php");
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha  = $_GET['fecha'] ?? '';
$filtro_tabla  = $_GET['tabla'] ?? '';

$where = [];
$params = [];

if ($filtro_usuario !== '') {
    $where[] = 'u.ID = ?';
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
        b.ID,
        u.cuenta AS Usuario,
        b.Tabla_Afectada,
        b.Id_Registro_Afectado,
        b.Tipo_Accion,
        b.Descripcion,
        b.Fecha_Accion
    FROM bitacora b
    INNER JOIN usuario u ON u.ID = b.Fk_Usuario
    $where_sql
    ORDER BY b.Fecha_Accion DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas para filtros
$usuarios_result = $pdo->query("SELECT ID, cuenta FROM usuario ORDER BY cuenta");
$tablas_result = $pdo->query("SELECT DISTINCT Tabla_Afectada FROM bitacora ORDER BY Tabla_Afectada");
?>

<!-- Resto del HTML -->
<head>
    <title>Bitácora de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4">Bitácora de Auditoría</h2>

    <form method="get" class="row g-2 mb-4">
        <div class="col-md-3">
            <label for="usuario" class="form-label">Usuario</label>
            <select name="usuario" id="usuario" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($usuarios_result as $u): ?>
                    <option value="<?= $u['ID'] ?>" <?= ($filtro_usuario == $u['ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['cuenta']) ?>
                    </option>
                <?php endforeach; ?>
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
                <?php foreach ($tablas_result as $t): ?>
                    <option value="<?= $t['Tabla_Afectada'] ?>" <?= ($filtro_tabla == $t['Tabla_Afectada']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['Tabla_Afectada']) ?>
                    </option>
                <?php endforeach; ?>
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
                <?php foreach ($result as $row): ?>
                <tr>
                    <td><?= $row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Usuario']) ?></td>
                    <td><?= htmlspecialchars($row['Tabla_Afectada']) ?></td>
                    <td><?= $row['Id_Registro_Afectado'] ?? '-' ?></td>
                    <td><?= $row['Tipo_Accion'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['Descripcion'])) ?></td>
                    <td><?= $row['Fecha_Accion'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
