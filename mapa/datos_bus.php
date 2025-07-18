<?php
$host = 'localhost';
$db = 'seguimientobus';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

$bus = $_GET['bus'] ?? '';
$bus = $conn->real_escape_string($bus);

$sql = "SELECT entidad, GROUP_CONCAT(DISTINCT estatus) as estatuses
        FROM segbus
        WHERE bus = '$bus'
        GROUP BY entidad";

$result = $conn->query($sql);

$datos = [];

while ($row = $result->fetch_assoc()) {
  $estado = $row['entidad'];
  $estatuses = explode(",", $row['estatuses']);

  if (count($estatuses) === 1) {
    $val = strtolower($estatuses[0]);
    $datos[$estado] = ($val === 'IMPLEMENTADO' || $val === 'SIN IMPLEMENTAR') ? $val : 'mixto';
  } else {
    $datos[$estado] = 'mixto';
  }
}

header('Content-Type: application/json');
echo json_encode($datos);
$conn->close();
?>
