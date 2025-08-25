<?php
// /mapa/public/sections/lineadetiempo/guardar_comentario.php
declare(strict_types=1);
session_start();
require_once '../../../server/config.php';
require_once __DIR__ . '/helpers.php';

// --- cabecera JSON coherente en todos los casos ---
header('Content-Type: application/json; charset=utf-8');

// 1) Sesión
$usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['ID'] ?? 0);
if ($usuarioId <= 0) {
  respond_json(['success' => false, 'message' => 'No autenticado'], 401);
}

// 2) Método
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond_json(['success' => false, 'message' => 'Método no permitido'], 405);
}

// 3) Inputs (recorte a longitudes de la DB)
$fkRegistro = isset($_POST['Fk_registro']) ? (int)$_POST['Fk_registro'] : 0;
$encabezado = mb_substr(trim((string)($_POST['encabezado'] ?? '')), 0, 45, 'UTF-8');
$comentario = mb_substr(trim((string)($_POST['comentario'] ?? '')), 0, 500, 'UTF-8');

// Preferido: FK_etapa (int). Compat: 'fase' (texto) -> buscar en etapa.descripcion
$fkEtapa     = isset($_POST['FK_etapa']) ? (int)$_POST['FK_etapa'] : 0;
$faseNombre  = trim((string)($_POST['fase'] ?? ''));

// Color/etiqueta: acepta etiquetas y clases antiguas; normaliza a bg-*
$raw = strtolower(trim((string)($_POST['color'] ?? '')));
$map = [
  // etiquetas
  'urgente'     => 'bg-danger',
  'prioritario' => 'bg-warning',
  'importante'  => 'bg-primary',
  'desfasado'   => 'bg-secondary',
  'seguimiento' => 'bg-success',
  // clases directas
  'bg-danger'   => 'bg-danger',
  'bg-warning'  => 'bg-warning',
  'bg-primary'  => 'bg-primary',
  'bg-secondary'=> 'bg-secondary',
  'bg-success'  => 'bg-success',
  // valores antiguos
  'rojo'        => 'bg-danger',
  'amarillo'    => 'bg-warning',
  'azul'        => 'bg-primary',
  'verde'       => 'bg-success',
  'gris'        => 'bg-secondary',
];
$colorInput = $map[$raw] ?? 'bg-success';

// Campos obligatorios
if ($fkRegistro <= 0 || $encabezado === '' || $comentario === '') {
  respond_json(['success' => false, 'message' => 'Datos incompletos.'], 422);
}

try {
  // 4) Resolver FK_etapa si vino como texto 'fase'
  if ($fkEtapa <= 0 && $faseNombre !== '') {
    $q = $pdo->prepare("SELECT ID FROM etapa WHERE descripcion = ? LIMIT 1");
    $q->execute([$faseNombre]);
    $fkEtapa = (int)($q->fetchColumn() ?: 0);
  }

  // 5) Etapa actual del registro (debe existir y estar activa)
  $q = $pdo->prepare("SELECT Fk_etapa FROM registro WHERE ID = ? AND activo = b'1'");
  $q->execute([$fkRegistro]);
  $etapaActual = $q->fetchColumn();

  if ($etapaActual === false || $etapaActual === null) {
    respond_json(['success' => false, 'message' => 'El registro no tiene etapa actual asignada.'], 409);
  }
  $etapaActual = (int)$etapaActual;

  if ($fkEtapa <= 0) {
    respond_json(['success' => false, 'message' => 'Etapa no válida.'], 422);
  }

  // 6) Regla: SOLO se puede comentar en la etapa ACTUAL (ni anterior ni posterior)
  if ($fkEtapa !== $etapaActual) {
    respond_json(['success' => false, 'message' => 'Solo se puede comentar en la etapa actual del registro.'], 403);
  }

  // 7) Insertar (transacción): comentario -> registro_comentario
  $pdo->beginTransaction();

  $sqlC = "INSERT INTO comentario (comentario, color, encabezado, FK_usuario, fecha_creacion, activo)
           VALUES (?, ?, ?, ?, NOW(), b'1')";
  $stC = $pdo->prepare($sqlC);
  $stC->execute([$comentario, $colorInput, $encabezado, $usuarioId]);
  $comentarioId = (int)$pdo->lastInsertId();

  $sqlRC = "INSERT INTO registro_comentario (FK_registro, FK_comentario, FK_etapa, fecha_enlace, Activo)
            VALUES (?, ?, ?, NOW(), b'1')";
  $stRC = $pdo->prepare($sqlRC);
  $stRC->execute([$fkRegistro, $comentarioId, $fkEtapa]);

  $pdo->commit();

  respond_json(['success' => true, 'id' => $comentarioId, 'message' => 'Comentario guardado']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // Puedes loggear $e->getMessage() en un fichero si lo necesitas.
  respond_json(['success' => false, 'message' => 'Error al guardar'], 500);
}
