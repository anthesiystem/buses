<?php
// Ruta: /final/mapa/public/sections/usuarios/api/persona_guardar.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

function jerr($msg){ echo json_encode(['ok'=>false,'msg'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function val($k){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }

$id               = val('ID'); // vacío => insert
$nombre           = val('nombre');
$apaterno         = val('apaterno');
$amaterno         = val('amaterno');
$numero_empleado  = val('numero_empleado');
$correo           = val('correo');
$Fk_dependencia   = (int)val('Fk_dependencia');
$Fk_entidad       = (int)val('Fk_entidad');
$activo           = (val('activo') === '1') ? 1 : 0;

// --------- Validaciones básicas ---------
if ($nombre === '')          jerr('El nombre es obligatorio.');
if ($apaterno === '')        jerr('El apellido paterno es obligatorio.');
if ($amaterno === '')        jerr('El apellido materno es obligatorio.');
if ($numero_empleado === '') jerr('El número de empleado es obligatorio.');
if ($correo === '')          jerr('El correo es obligatorio.');
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) jerr('El correo no es válido.');
if ($Fk_dependencia <= 0)    jerr('Selecciona una dependencia válida.');
if ($Fk_entidad <= 0)        jerr('Selecciona una entidad válida.');

// --------- Comprobar existencia de FK (por si las FK están desactivadas) ---------
try {
  $chkDep = $pdo->prepare("SELECT 1 FROM dependencia WHERE ID=? AND activo=1");
  $chkDep->execute([$Fk_dependencia]);
  if (!$chkDep->fetch()) jerr('La dependencia seleccionada no existe o está inactiva.');

  $chkEnt = $pdo->prepare("SELECT 1 FROM entidad WHERE ID=? AND activo=1");
  $chkEnt->execute([$Fk_entidad]);
  if (!$chkEnt->fetch()) jerr('La entidad seleccionada no existe o está inactiva.');
} catch (Throwable $e) {
  // Si estas tablas no tienen columna "activo", ignora el chequeo anterior
}

// --------- Validación de unicidad previa (correo y número de empleado) ---------
try {
  // Correo único
  $sqlC = "SELECT ID FROM persona WHERE correo=?".($id!=='' ? " AND ID<>?" : "");
  $stC  = $pdo->prepare($sqlC);
  $stC->execute($id!=='' ? [$correo, (int)$id] : [$correo]);
  if ($stC->fetch()) jerr('El correo ya está registrado.');

  // Número de empleado único
  $sqlN = "SELECT ID FROM persona WHERE numero_empleado=?".($id!=='' ? " AND ID<>?" : "");
  $stN  = $pdo->prepare($sqlN);
  $stN->execute($id!=='' ? [$numero_empleado, (int)$id] : [$numero_empleado]);
  if ($stN->fetch()) jerr('El número de empleado ya está registrado.');
} catch (Throwable $e) {
  // seguimos; la validación DB final atrapará cualquier cosa
}

$rawActivo = $_POST['activo'] ?? 0;
function to01($v){
  $v = strtolower(trim((string)$v));
  return in_array($v, ['1','true','on','si','sí','yes']) ? 1 : 0;
}
$activo = to01($rawActivo);


try {
  if ($id === '') {
    // INSERT
   $sql = "INSERT INTO persona
  (nombre, apaterno, amaterno, numero_empleado, correo, Fk_dependencia, Fk_entidad, activo)
  VALUES (?,?,?,?,?,?,?,?)";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $nombre);
$stmt->bindValue(2, $apaterno);
$stmt->bindValue(3, $amaterno);
$stmt->bindValue(4, $numero_empleado);
$stmt->bindValue(5, $correo);
$stmt->bindValue(6, (int)$Fk_dependencia, PDO::PARAM_INT);
$stmt->bindValue(7, (int)$Fk_entidad, PDO::PARAM_INT);
$stmt->bindValue(8, (int)$activo, PDO::PARAM_INT);   // <---- clave
$ok = $stmt->execute();

  } else {
    // UPDATE
    $sql = "UPDATE persona SET
  nombre=?, apaterno=?, amaterno=?, numero_empleado=?, correo=?,
  Fk_dependencia=?, Fk_entidad=?, activo=?, fecha_modificacion=NOW()
  WHERE ID=?";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $nombre);
$stmt->bindValue(2, $apaterno);
$stmt->bindValue(3, $amaterno);
$stmt->bindValue(4, $numero_empleado);
$stmt->bindValue(5, $correo);
$stmt->bindValue(6, (int)$Fk_dependencia, PDO::PARAM_INT);
$stmt->bindValue(7, (int)$Fk_entidad, PDO::PARAM_INT);
$stmt->bindValue(8, (int)$activo, PDO::PARAM_INT);   // <---- clave
$stmt->bindValue(9, (int)$id, PDO::PARAM_INT);
$ok = $stmt->execute();

  }

  echo json_encode(['ok' => (bool)$ok], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  // Mensajes amigables por errores típicos
  $msg = $e->getMessage();
  if (strpos($msg, '1062') !== false) {
    // Duplicate entry
    if (strpos($msg, 'correo') !== false)           jerr('El correo ya está registrado.');
    if (strpos($msg, 'numero_empleado') !== false)  jerr('El número de empleado ya está registrado.');
    jerr('Registro duplicado.');
  }
  if (strpos($msg, '1452') !== false) {
    jerr('Dependencia o Entidad inválida (violación de clave foránea).');
  }
  echo json_encode(['ok'=>false,'msg'=>$msg], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
