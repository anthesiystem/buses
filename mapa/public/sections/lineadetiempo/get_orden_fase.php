<?php
require_once '../../../server/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$orden = null;

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT orden FROM fase WHERE ID = ?");
  $stmt->execute([$id]);
  $orden = $stmt->fetchColumn();
}

header('Content-Type: application/json');
echo json_encode([
  'ok' => $orden !== false,
  'orden' => $orden !== false ? (int)$orden : null
]);
