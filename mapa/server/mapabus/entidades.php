<?php
// server/mapabus/entidades.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_login_or_redirect();
require_once __DIR__ . '/../acl_entidades.php';

header('Content-Type: application/json; charset=utf-8');

$busId = (int)($_GET['bus'] ?? 0);
if ($busId <= 0) { http_response_code(400); echo json_encode(['error'=>'Bus inválido']); exit; }

$usuarioId  = (int)($_SESSION['user_id'] ?? 0);
$permitidas = entidadesPermitidasPorUsuario($pdo, $usuarioId, $busId, 'mapa_bus');

if (!$permitidas) { echo json_encode([]); exit; }

$in  = implode(',', array_fill(0, count($permitidas), '?'));
$sql = "SELECT ID, UPPER(descripcion) AS nombre FROM entidad WHERE activo = 1 AND ID IN ($in) ORDER BY nombre";
$st  = $pdo->prepare($sql);
$st->execute($permitidas);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/*
  Respuesta: [ { ID: 1, nombre: "AGUASCALIENTES" }, ... ]
  En JS crearemos un mapa nombre→ID para cruzarlo con tu estadoMap.
*/
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
