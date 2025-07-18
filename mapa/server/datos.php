<?php
$host = 'localhost';
$db = 'busmap';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$bus = $_GET['bus'] ?? '';
$bus = $conn->real_escape_string($bus);

$sql = "
    SELECT e.Nombre AS entidad_nombre,
           GROUP_CONCAT(DISTINCT UPPER(es.Valor)) AS estatuses
    FROM registro r
    INNER JOIN entidad e ON e.Id = r.Fk_Id_Entidad
    INNER JOIN estatus es ON es.Id = r.Fk_Id_Estatus
    INNER JOIN bus b ON b.Id = r.Fk_Id_Bus
    WHERE b.Nombre = ?
    GROUP BY e.Nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $bus);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $estado = $row['entidad_nombre'];
    $estatuses = array_map('trim', explode(",", $row['estatuses']));
    $estatusesUnicos = array_unique($estatuses);

    if (count($estatusesUnicos) === 1) {
        $datos[$estado] = $estatusesUnicos[0]; // IMPLEMENTADO o SIN IMPLEMENTAR
    } else {
        // Si hay mezcla, tratamos como PRUEBAS o MIXTO
        if (in_array('PRUEBAS', $estatusesUnicos)) {
            $datos[$estado] = 'PRUEBAS';
        } else {
            $datos[$estado] = 'MIXTO';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($datos);
$conn->close();
?>
