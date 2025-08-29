<?php
session_start();
require_once dirname(__FILE__) . '/../../server/config.php';

if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 4) {
    die('No tiene permiso para acceder a esta sección');
}

// Aplicar los mismos filtros que en la vista principal
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

// Configurar headers para descarga CSV
$filename = 'bitacora_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear el archivo CSV
$output = fopen('php://output', 'w');

// Agregar BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers del CSV
fputcsv($output, [
    'ID',
    'Usuario',
    'Tabla Afectada',
    'ID Registro',
    'Tipo Acción',
    'Descripción',
    'Fecha y Hora'
], ';');

// Datos
foreach ($result as $row) {
    fputcsv($output, [
        $row['ID'],
        $row['Usuario'],
        $row['Tabla_Afectada'],
        $row['Id_Registro_Afectado'] ?? '',
        $row['Tipo_Accion'],
        $row['Descripcion'],
        $row['Fecha_Accion']
    ], ';');
}

fclose($output);
exit;
?>
