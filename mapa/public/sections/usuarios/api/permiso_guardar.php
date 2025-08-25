<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

function jerr($m){ echo json_encode(['ok'=>false,'msg'=>$m], JSON_UNESCAPED_UNICODE); exit; }
function to01($v){ $v=strtolower(trim((string)$v)); return in_array($v,['1','true','on','si','sí','yes'])?1:0; }

// Helpers: '' -> NULL (para “Todos”)
function post_null(string $k){
  if (!array_key_exists($k, $_POST)) return null;      // campo omitido
  $v = trim((string)$_POST[$k]);
  return $v === '' ? null : $v;
}
function post_int_or_null(string $k){
  if (!array_key_exists($k, $_POST)) return null;
  $v = trim((string)$_POST[$k]);
  if ($v === '') return null;
  return (int)$v;
}

$id         = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
$Fk_usuario = isset($_POST['Fk_usuario']) ? (int)$_POST['Fk_usuario'] : 0;
$Fk_modulo  = isset($_POST['Fk_modulo'])  ? (int)$_POST['Fk_modulo']  : 0;

// “Todos” ⇒ NULL
$FK_entidad = post_null('FK_entidad');     // VARCHAR o INT, según tu esquema
$FK_bus     = post_int_or_null('FK_bus');  // INT NULL
$accion     = post_null('accion');         // VARCHAR NULL
$accion     = is_null($accion) ? null : strtoupper($accion);

$activo     = to01($_POST['activo'] ?? '1');

// Validaciones mínimas (usuario y módulo sí son obligatorios)
if ($Fk_usuario<=0) jerr('Selecciona un usuario válido.');
if ($Fk_modulo<=0)  jerr('Selecciona un módulo válido.');

// (Opcional) Si decides validar acción cuando venga: 
// if (!is_null($accion) && !in_array($accion, ['READ','CREATE','UPDATE','DELETE','COMMENT','EXPORT'])) jerr('Acción inválida.');

try{
  if ($id <= 0){
    $sql = "INSERT INTO permiso_usuario (Fk_usuario, Fk_modulo, FK_entidad, FK_bus, accion, activo)
            VALUES (:u, :m, :e, :b, :a, :act)";
    $st = $pdo->prepare($sql);
  } else {
    $sql = "UPDATE permiso_usuario
            SET Fk_usuario=:u, Fk_modulo=:m, FK_entidad=:e, FK_bus=:b, accion=:a, activo=:act
            WHERE ID=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
  }

  $st->bindValue(':u',   $Fk_usuario, PDO::PARAM_INT);
  $st->bindValue(':m',   $Fk_modulo,  PDO::PARAM_INT);

  // FK_entidad puede ser NULL o texto/número según tu columna
  if (is_null($FK_entidad)) $st->bindValue(':e', null, PDO::PARAM_NULL);
  else                      $st->bindValue(':e', $FK_entidad);

  // FK_bus INT NULL
  if (is_null($FK_bus))     $st->bindValue(':b', null, PDO::PARAM_NULL);
  else                      $st->bindValue(':b', (int)$FK_bus, PDO::PARAM_INT);

  // acción NULL o texto
  if (is_null($accion))     $st->bindValue(':a', null, PDO::PARAM_NULL);
  else                      $st->bindValue(':a', $accion);

  $st->bindValue(':act', (int)$activo, PDO::PARAM_INT);

  $ok = $st->execute();
  echo json_encode(['ok'=>(bool)$ok], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e){
  $msg = $e->getMessage();
  if (strpos($msg,'1062')!==false){
    jerr('Ya existe un permiso con la misma combinación Usuario + Módulo + Acción.');
  }
  if (strpos($msg,'1452')!==false){
    jerr('Usuario o Módulo no existen (clave foránea).');
  }
  echo json_encode(['ok'=>false,'msg'=>$msg], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
