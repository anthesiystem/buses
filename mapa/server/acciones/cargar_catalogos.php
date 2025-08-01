<?php
require_once '../../server/config.php';
header('Content-Type: application/json');

function obtenerCatalogo($pdo, $tabla) {
  $stmt = $pdo->prepare("SELECT ID, descripcion FROM $tabla WHERE Activo = 1 ORDER BY descripcion");
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
try {
echo json_encode([
  'dependencias' => $pdo->query("SELECT ID, descripcion FROM dependencia WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'entidades'    => $pdo->query("SELECT ID, descripcion FROM entidad WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'buses'        => $pdo->query("SELECT ID, descripcion FROM bus WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'engines'      => $pdo->query("SELECT ID, descripcion FROM engine WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'versiones'    => $pdo->query("SELECT ID, descripcion FROM version WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'categorias'   => $pdo->query("SELECT ID, descripcion FROM categoria WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC),
  'estatuses'    => $pdo->query("SELECT ID, descripcion FROM estado_bus WHERE Activo = 1")->fetchAll(PDO::FETCH_ASSOC)
]);
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
