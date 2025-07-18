<?php
$host = 'localhost';
$db = 'busmap';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

$sql = "
  SELECT 
    e.Nombre AS entidad,
    GROUP_CONCAT(DISTINCT es.Valor) AS estatuses
  FROM registro r
  INNER JOIN entidad e ON e.Id = r.Fk_Id_Entidad
  INNER JOIN estatus es ON es.Id = r.Fk_Id_Estatus
  GROUP BY e.Nombre
";

$result = $conn->query($sql);

$datos = [];

while ($row = $result->fetch_assoc()) {
  $estado = $row['entidad'];
  $estatuses = array_map('trim', explode(",", $row['estatuses']));
  $estatusesUnicos = array_unique($estatuses);

  if (count($estatusesUnicos) === 1) {
    $datos[$estado] = $estatusesUnicos[0]; // CONCLUIDO o SIN EJECUTAR
  } else {
    $datos[$estado] = 'mixto';
  }
}

header('Content-Type: application/json');
echo json_encode($datos);
$conn->close();
?>
