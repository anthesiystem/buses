<?php
// /mapa/public/sections/lineadetiempo/guardar_comentario.php
session_start();
require_once '../../../server/config.php';

// === 1) Validar sesión de usuario ===
$usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['ID'] ?? 0;
if (!$usuarioId) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
  exit;
}

// === 2) Validar método ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
  exit;
}

// === 3) Sanitizar y validar inputs ===
$fkRegistro = isset($_POST['Fk_registro']) ? (int)$_POST['Fk_registro'] : 0;
$encabezado = trim($_POST['encabezado'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');
$colorInput = strtolower(trim($_POST['color'] ?? 'azul'));
$faseNombre = trim($_POST['fase'] ?? '');

if ($fkRegistro <= 0 || $encabezado === '' || $comentario === '' || $faseNombre === '') {
  http_response_code(422);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
  exit;
}

// === 4) Normalizar color ===
$permitidos = ['rojo','amarillo','azul','verde','badge-danger','badge-warning','badge-primary','badge-success','bg-danger','bg-warning','bg-primary','bg-success'];
if (!in_array($colorInput, $permitidos, true)) {
  $colorInput = 'azul';
}

try {
  // === 5) Verificar fase actual del registro ===
  $faseActualQuery = $pdo->prepare("
    SELECT f.orden
    FROM registro r
    LEFT JOIN fase f ON r.Fk_fase_actual = f.ID
    WHERE r.ID = ?
  ");
  $faseActualQuery->execute([$fkRegistro]);
  $ordenActual = (int)$faseActualQuery->fetchColumn();

  // === 6) Verificar orden de la fase del comentario ===
  $faseNuevaQuery = $pdo->prepare("SELECT orden FROM fase WHERE nombre = ?");
  $faseNuevaQuery->execute([$faseNombre]);
  $ordenNueva = $faseNuevaQuery->fetchColumn();

  if ($ordenNueva === false) {
    throw new Exception("Fase seleccionada inválida.");
  }

  // === 7) No permitir comentarios en fases anteriores ===
  if ($ordenNueva < $ordenActual) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'No se puede comentar en una fase anterior a la actual.']);
    exit;
  }

  // === 8) Insertar comentario ===
$sql = "INSERT INTO comentario_registro (Fk_registro, Fk_usuario, encabezado, comentario, color, fase)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fkRegistro, $usuarioId, $encabezado, $comentario, $colorInput, $faseNombre]);


  header('Content-Type: application/json');
  echo json_encode([
    'ok' => true,
    'id' => $pdo->lastInsertId(),
    'msg' => 'Comentario guardado',
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode([
    'ok' => false,
    'msg' => 'Error al guardar',
    'error' => $e->getMessage()
  ]);
}
