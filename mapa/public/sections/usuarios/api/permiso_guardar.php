<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

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
    
    if ($ok) {
      $new_id = $pdo->lastInsertId();
      $usuario_session = obtenerUsuarioSession();
      $descripcion = "Nuevo permiso creado - usuario_id: '$Fk_usuario', módulo_id: '$Fk_modulo'";
      if (!is_null($FK_entidad)) $descripcion .= ", entidad: '$FK_entidad'";
      if (!is_null($FK_bus)) $descripcion .= ", bus: '$FK_bus'";
      if (!is_null($accion)) $descripcion .= ", acción: '$accion'";
      registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_crear', $descripcion, $new_id);
    }
    
  } else {
    // UPDATE - Obtener datos actuales para el log de cambios
    $stmt_prev = $pdo->prepare("SELECT Fk_usuario, Fk_modulo, FK_entidad, FK_bus, accion, activo FROM permiso_usuario WHERE ID = ?");
    $stmt_prev->execute([$id]);
    $datos_anteriores = $stmt_prev->fetch(PDO::FETCH_ASSOC);
    
    if ($datos_anteriores) {
      $sql = "UPDATE permiso_usuario
              SET Fk_usuario=:u, Fk_modulo=:m, FK_entidad=:e, FK_bus=:b, accion=:a, activo=:act
              WHERE ID=:id";
      $st = $pdo->prepare($sql);
      $st->bindValue(':id', $id, PDO::PARAM_INT);
      
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
      
      if ($ok) {
        // Registrar cambios en bitácora
        $cambios = [];
        if ($datos_anteriores['Fk_usuario'] != $Fk_usuario) {
          $cambios[] = "usuario_id: '{$datos_anteriores['Fk_usuario']}' → '$Fk_usuario'";
        }
        if ($datos_anteriores['Fk_modulo'] != $Fk_modulo) {
          $cambios[] = "módulo_id: '{$datos_anteriores['Fk_modulo']}' → '$Fk_modulo'";
        }
        if ($datos_anteriores['FK_entidad'] != $FK_entidad) {
          $ant_ent = $datos_anteriores['FK_entidad'] ?? 'NULL';
          $new_ent = $FK_entidad ?? 'NULL';
          $cambios[] = "entidad: '$ant_ent' → '$new_ent'";
        }
        if ($datos_anteriores['FK_bus'] != $FK_bus) {
          $ant_bus = $datos_anteriores['FK_bus'] ?? 'NULL';
          $new_bus = $FK_bus ?? 'NULL';
          $cambios[] = "bus: '$ant_bus' → '$new_bus'";
        }
        if ($datos_anteriores['accion'] != $accion) {
          $ant_acc = $datos_anteriores['accion'] ?? 'NULL';
          $new_acc = $accion ?? 'NULL';
          $cambios[] = "acción: '$ant_acc' → '$new_acc'";
        }
        if ($datos_anteriores['activo'] != $activo) {
          $estado_anterior = $datos_anteriores['activo'] ? 'Activo' : 'Inactivo';
          $estado_nuevo = $activo ? 'Activo' : 'Inactivo';
          $cambios[] = "estado: '$estado_anterior' → '$estado_nuevo'";
        }

        if (!empty($cambios)) {
          $usuario_session = obtenerUsuarioSession();
          $descripcion = "Permiso actualizado (ID: $id) - " . implode(', ', $cambios);
          registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_editar', $descripcion, $id);
        }
      }
    }
  }
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
