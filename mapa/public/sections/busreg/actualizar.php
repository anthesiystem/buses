<?php
// sections/actualizar.php
require_once '../../../server/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: sections/busreg//buses.php?msg=Metodo+no+permitido&type=warning');
  exit;
}

// ---- Helpers ----
function valInt($v, $nullable = false) {
  if ($nullable && ($v === '' || $v === null)) return null;
  if ($v === '' || $v === null) return null;
  if (!ctype_digit(strval($v))) return null;
  return (int)$v;
}

function valDate($v, $nullable = false) {
  if ($nullable && ($v === '' || $v === null)) return null;
  if ($v === '' || $v === null) return null;
  $d = DateTime::createFromFormat('Y-m-d', $v);
  return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}

function clampAvance($v) {
  if ($v === '' || $v === null) return null;
  if (!is_numeric($v)) return null;
  $n = (int)$v;
  if ($n < 0) $n = 0;
  if ($n > 100) $n = 100;
  return $n;
}

// ---- Inputs (names iguales a columnas) ----
$id             = valInt($_POST['ID'] ?? null);
$Fk_entidad     = valInt($_POST['Fk_entidad'] ?? null);
$Fk_dependencia = valInt($_POST['Fk_dependencia'] ?? null);
$Fk_bus         = valInt($_POST['Fk_bus'] ?? null, true); // puede ser NULL
$Fk_motor_base  = valInt($_POST['Fk_motor_base'] ?? null);
$Fk_version     = valInt($_POST['Fk_version'] ?? null);
$Fk_estado_bus  = valInt($_POST['Fk_estado_bus'] ?? null);
$Fk_categoria   = valInt($_POST['Fk_categoria'] ?? null);
$fecha_inicio   = valDate($_POST['fecha_inicio'] ?? null, true);
$fecha_migracion= valDate($_POST['fecha_migracion'] ?? null, true);
$avance         = clampAvance($_POST['avance'] ?? null);

// Validaciones mínimas
$errores = [];
if (!$id)               $errores[] = 'ID inválido.';
if (!$Fk_entidad)       $errores[] = 'Entidad requerida.';
if (!$Fk_dependencia)   $errores[] = 'Dependencia requerida.';
if (!$Fk_motor_base)    $errores[] = 'Motor base requerido.';
if (!$Fk_version)       $errores[] = 'Versión requerida.';
if (!$Fk_estado_bus)    $errores[] = 'Estatus requerido.';
if (!$Fk_categoria)     $errores[] = 'Categoría requerida.';
if (!$fecha_inicio)     $errores[] = 'Fecha inicio inválida.';
// Validación de fechas no futuras (opcional; comenta si no aplica)
$hoy = (new DateTime('today'))->format('Y-m-d');
if ($fecha_inicio && $fecha_inicio > $hoy)       $errores[] = 'Fecha inicio no puede ser futura.';
if ($fecha_migracion && $fecha_migracion > $hoy) $errores[] = 'Fecha migración no puede ser futura.';

if ($errores) {
  $msg = urlencode(implode(' ', $errores));
  header("Location: sections/busreg//buses.php?msg=$msg&type=danger");
  exit;
}

try {
  // Verificar existencia del registro
  $chk = $pdo->prepare("SELECT ID FROM registro WHERE ID = ?");
  $chk->execute([$id]);
  if (!$chk->fetchColumn()) {
    header("Location: sections/busreg//buses.php?msg=Registro+no+encontrado&type=warning");
    exit;
  }

  $sql = "UPDATE registro SET
            Fk_dependencia   = :Fk_dependencia,
            Fk_entidad       = :Fk_entidad,
            Fk_bus           = :Fk_bus,
            Fk_motor_base    = :Fk_motor_base,
            Fk_version       = :Fk_version,
            Fk_estado_bus    = :Fk_estado_bus,
            Fk_categoria     = :Fk_categoria,
            fecha_inicio     = :fecha_inicio,
            fecha_migracion  = :fecha_migracion,
            avance           = :avance,
            fecha_modificacion = CURRENT_TIMESTAMP
          WHERE ID = :ID";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':Fk_dependencia',  $Fk_dependencia,  $Fk_dependencia===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_entidad',      $Fk_entidad,      $Fk_entidad===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_bus',          $Fk_bus,          $Fk_bus===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_motor_base',   $Fk_motor_base,   $Fk_motor_base===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_version',      $Fk_version,      $Fk_version===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_estado_bus',   $Fk_estado_bus,   $Fk_estado_bus===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':Fk_categoria',    $Fk_categoria,    $Fk_categoria===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':fecha_inicio',    $fecha_inicio,    $fecha_inicio===null?PDO::PARAM_NULL:PDO::PARAM_STR);
  $stmt->bindValue(':fecha_migracion', $fecha_migracion, $fecha_migracion===null?PDO::PARAM_NULL:PDO::PARAM_STR);
  $stmt->bindValue(':avance',          $avance,          $avance===null?PDO::PARAM_NULL:PDO::PARAM_INT);
  $stmt->bindValue(':ID',              $id,              PDO::PARAM_INT);

  $stmt->execute();

  header('Location: sections/busreg/buses.php?msg=Registro+actualizado+correctamente&type=success');
  exit;

} catch (Throwable $e) {
  // Si tu config.php no activa excepciones, considera: $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $msg = urlencode('Error al actualizar: '.$e->getMessage());
  header("Location: sections/busreg//buses.php?msg=$msg&type=danger");
  exit;
}
