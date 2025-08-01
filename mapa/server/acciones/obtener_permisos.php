<?php
require_once '../config.php';
require_once '../permiso.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
  echo json_encode([]);
  exit;
}

$idUsuario = intval($_GET['id']);
$permisos = obtenerPermisosPorUsuario($idUsuario, $pdo);
echo json_encode($permisos);
