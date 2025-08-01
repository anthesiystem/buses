<?php
require_once __DIR__ . '/../config.php';

$sql = "
    SELECT 
        e.descripcion AS entidad_nombre,
        GROUP_CONCAT(DISTINCT UPPER(eb.descripcion)) AS estatuses
    FROM REGISTRO r
    INNER JOIN ENTIDAD e ON e.ID = r.Fk_entidad
    INNER JOIN ESTADO_BUS eb ON eb.ID = r.Fk_estado_bus
    GROUP BY e.descripcion
";

$stmt = $pdo->query($sql);

$datos = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estado = trim($row['entidad_nombre']);
    $estatuses = array_map('trim', explode(",", $row['estatuses']));
    $estatusesUnicos = array_unique($estatuses);

    if (count($estatusesUnicos) === 1) {
        $datos[$estado] = $estatusesUnicos[0]; // IMPLEMENTADO o SIN IMPLEMENTAR
    } else {
        if (in_array('PRUEBAS', $estatusesUnicos)) {
            $datos[$estado] = 'PRUEBAS';
        } else {
            $datos[$estado] = 'MIXTO';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($datos);
