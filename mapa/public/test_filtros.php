<?php
// Archivo de prueba para verificar filtros de bitácora
session_start();
require_once dirname(__FILE__) . '/../server/config.php';

echo "<h3>Test de Filtros Bitácora</h3>";
echo "<pre>";
echo "GET Parameters:\n";
print_r($_GET);
echo "\n";

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha  = $_GET['fecha'] ?? '';
$filtro_tabla  = $_GET['tabla'] ?? '';

echo "Filtros extraídos:\n";
echo "Usuario: '$filtro_usuario'\n";
echo "Acción: '$filtro_accion'\n";
echo "Fecha: '$filtro_fecha'\n";  
echo "Tabla: '$filtro_tabla'\n";

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

echo "\nSQL WHERE: $where_sql\n";
echo "Parameters: ";
print_r($params);

echo "\nPrueba con URL: /final/mapa/public/test_filtros.php?accion=DESCARGA&usuario=1\n";
echo "</pre>";

// Formulario de prueba
?>
<form method="get">
    <h4>Formulario de Prueba</h4>
    <label>Acción: <input type="text" name="accion" value="<?= htmlspecialchars($filtro_accion) ?>"></label><br>
    <label>Usuario: <input type="text" name="usuario" value="<?= htmlspecialchars($filtro_usuario) ?>"></label><br>
    <label>Fecha: <input type="date" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>"></label><br>
    <label>Tabla: <input type="text" name="tabla" value="<?= htmlspecialchars($filtro_tabla) ?>"></label><br>
    <button type="submit">Enviar</button>
</form>
