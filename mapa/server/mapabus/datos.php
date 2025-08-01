<?php
require_once __DIR__ . '/../config.php';

$bus = $_GET['bus'] ?? '';
$bus = trim($bus);

$sql = "
    SELECT 
        e.descripcion AS entidad_descripcion,
        GROUP_CONCAT(DISTINCT UPPER(eb.descripcion)) AS estatuses,
        ROUND(AVG(r.avance)) AS avance_promedio
    FROM registro r
    INNER JOIN entidad e ON e.Id = r.Fk_entidad
    INNER JOIN estado_bus eb ON eb.Id = r.Fk_estado_bus
    INNER JOIN bus b ON b.Id = r.Fk_bus
    WHERE b.descripcion = :bus
    GROUP BY e.descripcion
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':bus' => $bus]);

$datos = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estado = $row['entidad_descripcion'];
    $estatuses = array_map('trim', explode(",", $row['estatuses']));
    $estatusesUnicos = array_unique($estatuses);
    $avance = (int) $row['avance_promedio'];

    if (count($estatusesUnicos) === 1) {
        $estatusFinal = $estatusesUnicos[0];
    } else {
        $estatusFinal = in_array('PRUEBAS', $estatusesUnicos) ? 'PRUEBAS' : 'MIXTO';
    }

    $datos[$estado] = [
        'estatus' => $estatusFinal,
        'avance' => $avance
    ];
}

header('Content-Type: application/json');
echo json_encode($datos);
