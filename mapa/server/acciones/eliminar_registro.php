<?php
require_once '../../server/config.php';
session_start();

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['success' => false, 'error' => 'No autorizado']);
  exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
  echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
  exit;
}

// Desactivar registro
$stmt = $pdo->prepare("UPDATE registro SET activo = 0 WHERE ID = ?");
$stmt->execute([$id]);

// Registrar en bitácora
$usuario = $_SESSION['usuario_nombre'] ?? 'Desconocido';
$descripcion = "El usuario {$usuario} desactivó el registro con ID $id";

$bitacora = $pdo->prepare("INSERT INTO bitacora (Fk_Usuarios, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion)
                           VALUES (?, 'registro', ?, 'DESACTIVAR', ?)");
$bitacora->execute([$_SESSION['usuario_id'], $id, $descripcion]);

echo json_encode(['success' => true]);
