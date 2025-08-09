<?php
require_once '../../../server/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$id = $_GET['id'];
$estado = $_GET['estado'] == 1 ? 0 : 1;

$stmt = $pdo->prepare("UPDATE bus SET activo=? WHERE ID=?");
if ($stmt->execute([$estado, $id])) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false]);
}
