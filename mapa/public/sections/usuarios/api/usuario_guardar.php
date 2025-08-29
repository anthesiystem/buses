<?php
// /final/mapa/public/sections/usuarios/api/usuario_guardar.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

function jerr($m){ echo json_encode(['ok'=>false,'msg'=>$m], JSON_UNESCAPED_UNICODE); exit; }
function val($k){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function to01($v){ $v = strtolower(trim((string)$v)); return in_array($v, ['1','true','on','si','sí','yes']) ? 1 : 0; }

$id         = val('ID');                          // vacío => INSERT
$Fk_persona = (int) val('Fk_persona');
$cuenta     = val('cuenta');
$nivel      = val('nivel');                       // en tu esquema es VARCHAR(45)
$pass       = val('contrasenia');                 // puede venir vacío en UPDATE
$activo     = to01($_POST['activo'] ?? '1');      // <-- normaliza a 0/1

// Validaciones básicas
if ($Fk_persona <= 0)            jerr('Selecciona una persona válida.');
if ($cuenta === '')              jerr('La cuenta es obligatoria.');
if ($nivel === '')               jerr('El nivel es obligatorio.');
if ($id === '' && $pass === '')  jerr('La contraseña es obligatoria.');

// (Opcional) verificar que la persona existe
try {
  $st = $pdo->prepare("SELECT 1 FROM persona WHERE ID=?");
  $st->execute([$Fk_persona]);
  if (!$st->fetch()) jerr('La persona seleccionada no existe.');
} catch (Throwable $e) { /* continua */ }

try {
  if ($id === '') {
    // INSERT
    $hash = password_hash($pass, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuario (Fk_persona, cuenta, contrasenia, nivel, activo)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $Fk_persona, PDO::PARAM_INT);
    $stmt->bindValue(2, $cuenta);
    $stmt->bindValue(3, $hash);
    $stmt->bindValue(4, $nivel);
    $stmt->bindValue(5, (int)$activo, PDO::PARAM_INT);  // <-- clave
    $ok = $stmt->execute();

    if ($ok) {
      $new_id = $pdo->lastInsertId();
      $usuario_session = obtenerUsuarioSession();
      $descripcion = "Nuevo usuario creado - cuenta: '$cuenta', nivel: '$nivel', activo: " . ($activo ? 'Sí' : 'No');
      registrarBitacora($pdo, $usuario_session, 'usuario', 'usuario_crear', $descripcion, $new_id);
    }

  } else {
    // UPDATE (con o sin cambio de contraseña)
    
    // Obtener datos actuales para el log de cambios
    $stmt_prev = $pdo->prepare("SELECT Fk_persona, cuenta, nivel, activo FROM usuario WHERE ID = ?");
    $stmt_prev->execute([(int)$id]);
    $datos_anteriores = $stmt_prev->fetch(PDO::FETCH_ASSOC);
    
    if ($datos_anteriores) {
      if ($pass !== '') {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $sql = "UPDATE usuario SET Fk_persona=?, cuenta=?, contrasenia=?, nivel=?, activo=?, fecha_modificacion=NOW()
                WHERE ID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $Fk_persona, PDO::PARAM_INT);
        $stmt->bindValue(2, $cuenta);
        $stmt->bindValue(3, $hash);
        $stmt->bindValue(4, $nivel);
        $stmt->bindValue(5, (int)$activo, PDO::PARAM_INT);  // <-- clave
        $stmt->bindValue(6, (int)$id, PDO::PARAM_INT);
        $ok = $stmt->execute();
      } else {
        $sql = "UPDATE usuario SET Fk_persona=?, cuenta=?, nivel=?, activo=?, fecha_modificacion=NOW()
                WHERE ID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $Fk_persona, PDO::PARAM_INT);
        $stmt->bindValue(2, $cuenta);
        $stmt->bindValue(3, $nivel);
        $stmt->bindValue(4, (int)$activo, PDO::PARAM_INT);  // <-- clave
        $stmt->bindValue(5, (int)$id, PDO::PARAM_INT);
        $ok = $stmt->execute();
      }

      if ($ok) {
        // Registrar cambios en bitácora
        $cambios = [];
        if ($datos_anteriores['Fk_persona'] != $Fk_persona) {
          $cambios[] = "persona_id: '{$datos_anteriores['Fk_persona']}' → '$Fk_persona'";
        }
        if ($datos_anteriores['cuenta'] !== $cuenta) {
          $cambios[] = "cuenta: '{$datos_anteriores['cuenta']}' → '$cuenta'";
        }
        if ($datos_anteriores['nivel'] !== $nivel) {
          $cambios[] = "nivel: '{$datos_anteriores['nivel']}' → '$nivel'";
        }
        if ($datos_anteriores['activo'] != $activo) {
          $estado_anterior = $datos_anteriores['activo'] ? 'Activo' : 'Inactivo';
          $estado_nuevo = $activo ? 'Activo' : 'Inactivo';
          $cambios[] = "estado: '$estado_anterior' → '$estado_nuevo'";
        }
        if (!empty($pass)) {
          $cambios[] = "contraseña actualizada";
        }

        if (!empty($cambios)) {
          $usuario_session = obtenerUsuarioSession();
          $descripcion = "Usuario actualizado - " . implode(', ', $cambios);
          registrarBitacora($pdo, $usuario_session, 'usuario', 'usuario_editar', $descripcion, (int)$id);
        }
      }
    }
  }

  echo json_encode(['ok'=>(bool)$ok], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  $msg = $e->getMessage();
  if (strpos($msg,'1062') !== false) {
    jerr('La cuenta ya existe (duplicado).');
  }
  echo json_encode(['ok'=>false,'msg'=>$msg], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
