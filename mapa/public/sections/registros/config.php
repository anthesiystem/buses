<?php
// config.php - Configuración y inicialización
session_start();
require_once '../../../server/config.php';
require_once '../../../server/bitacora_helper.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../../login.php");
  exit;
}

// Función helper para escapar HTML
function h($s){ 
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

// Función para obtener catálogos
function catalogo($pdo, $tabla) {
  return $pdo->query("SELECT ID, descripcion FROM $tabla WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
}
?>
