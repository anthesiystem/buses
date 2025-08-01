<?php
require_once '../../server/config.php';
session_start();

header('Content-Type: application/json');

try {
  // Validaciones básicas
  $id = $_POST['ID'] ?? null;
  $dependencia = $_POST['Fk_dependencia'];
  $entidad     = $_POST['Fk_entidad'];
  $bus         = $_POST['Fk_bus'];
  $engine      = $_POST['Fk_engine'];
  $version     = $_POST['Fk_version'];
  $estatus     = $_POST['Fk_estado_bus'];
  $categoria   = $_POST['Fk_categoria'];
  $inicio      = $_POST['fecha_inicio'] ?: null;
  $migracion   = $_POST['fecha_migracion'] ?: null;
  $avance      = (int)$_POST['avance'];



  // Evitar duplicado (único por entidad + bus + versión)
  if (!$id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registro WHERE Fk_entidad = ? AND Fk_bus = ? AND Fk_version = ? AND activo = 1");
    $stmt->execute([$entidad, $bus, $version]);
    if ($stmt->fetchColumn() > 0) {
      echo json_encode(['success' => false, 'error' => 'Ya existe un registro activo con el mismo Bus, Entidad y Versión.']);
      exit;
    }
  }

  // Insertar o actualizar
  if ($id) {
    $stmt = $pdo->prepare("UPDATE registro SET Fk_dependencia=?, Fk_entidad=?, Fk_bus=?, Fk_engine=?, Fk_version=?, Fk_estado_bus=?, Fk_categoria=?, fecha_inicio=?, fecha_migracion=?, avance=?, fecha_modificacion=NOW() WHERE ID=?");
    $stmt->execute([$dependencia, $entidad, $bus, $engine, $version, $estatus, $categoria, $inicio, $migracion, $avance, $id]);
    $accion = 'UPDATE';
  } else {
    $stmt = $pdo->prepare("INSERT INTO registro (Fk_dependencia, Fk_entidad, Fk_bus, Fk_engine, Fk_version, Fk_estado_bus, Fk_categoria, fecha_inicio, fecha_migracion, avance, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$dependencia, $entidad, $bus, $engine, $version, $estatus, $categoria, $inicio, $migracion, $avance]);
    $id = $pdo->lastInsertId();
    $accion = 'INSERT';
  }

  

  // Bitácora
// Bitácora
$usuario_id = $_SESSION['usuario_id'] ?? null;
if ($usuario_id) {
  $desc = $accion . ' en registro ID ' . $id;
  $bitacora = $pdo->prepare("INSERT INTO bitacora (Fk_usuario, tabla_afectada, ID_registro_afectado, tipo_accion, fecha_accion, descripcion) VALUES (?, ?, ?, ?, NOW(), ?)");
  $bitacora->execute([$usuario_id, 'registro', $id, $accion, $desc]);
}


  echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
