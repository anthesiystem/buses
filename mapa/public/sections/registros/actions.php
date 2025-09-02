<?php
// actions.php - Manejo de acciones POST
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
  
  // Manejar acción de desactivar múltiple
  if (isset($_POST['action']) && $_POST['action'] === 'desactivar_multiple') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    
    if (!is_array($ids) || empty($ids)) {
      throw new Exception('No se proporcionaron IDs válidos');
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE registro SET activo = b'0' WHERE ID IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    
    $affected = $stmt->rowCount();
    
    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
      echo json_encode(['ok' => true, 'affected' => $affected, 'message' => "$affected registro(s) desactivado(s)"]);
      exit;
    }
    
    header('Location: index.php?msg=desactivados');
    exit;
  }
  
  // 1) Tomar y sanear datos
  $ID              = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
  $Fk_dependencia  = ($_POST['Fk_dependencia'] ?? '') !== '' ? (int)$_POST['Fk_dependencia'] : null;
  $Fk_entidad      = (int)($_POST['Fk_entidad'] ?? 0);
  $Fk_bus          = ($_POST['Fk_bus'] ?? '') !== '' ? (int)$_POST['Fk_bus'] : null;
  $Fk_motor_base   = (int)($_POST['Fk_motor_base'] ?? 0);
  $Fk_tecnologia   = (int)($_POST['Fk_tecnologia'] ?? 0);
  $Fk_estado_bus   = (int)($_POST['Fk_estado_bus'] ?? 0);
  $Fk_categoria    = (int)($_POST['Fk_categoria'] ?? 0);
  $Fk_etapa        = ($_POST['Fk_etapa'] ?? '') !== '' ? (int)$_POST['Fk_etapa'] : null;
  $fecha_inicio    = ($_POST['fecha_inicio'] ?? '') ?: null;
  $fecha_migracion = ($_POST['fecha_migracion'] ?? '') ?: null;

  // === Helpers ===
  $getEstadoTexto = function(int $id) use ($pdo): string {
    $st = $pdo->prepare("SELECT LOWER(TRIM(descripcion)) FROM estado_bus WHERE ID = ? AND activo = 1 LIMIT 1");
    $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '');
  };
  $getEtapaPct = function($id) use ($pdo): int {
    if (empty($id)) return 0;
    $st = $pdo->prepare("SELECT avance FROM etapa WHERE ID = ? AND activo = 1 LIMIT 1");
    $st->execute([$id]);
    return (int)($st->fetchColumn() ?: 0);
  };
  $getEtapaImplId = function() use ($pdo) {
    $id = $pdo->query("SELECT ID FROM etapa WHERE activo=1 AND avance=100 ORDER BY ID LIMIT 1")->fetchColumn();
    if ($id) return (int)$id;
    $id = $pdo->query("SELECT ID FROM etapa WHERE activo=1 ORDER BY avance DESC, ID ASC LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };
  $getEstadoImplId = function() use ($pdo) {
    $id = $pdo->query("SELECT ID FROM estado_bus WHERE activo=1 AND LOWER(descripcion) LIKE 'implementado%' LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };
  $getCatProdId = function() use ($pdo) {
    $id = $pdo->query("SELECT ID FROM categoria WHERE activo=1 AND LOWER(descripcion) LIKE 'productiv%' LIMIT 1")->fetchColumn();
    return $id ? (int)$id : null;
  };

  // 2) Reglas por ESTATUS
  $estadoTxt = $getEstadoTexto($Fk_estado_bus);

  if (preg_match('/sin\s*implement/i', $estadoTxt)) {
    $Fk_etapa = null;
    $fecha_migracion = null;
  } elseif (preg_match('/prueba/i', $estadoTxt)) {
    if ($getEtapaPct($Fk_etapa) === 100) {
      $Fk_etapa = null;
    }
  } elseif (preg_match('/implementado/i', $estadoTxt)) {
    $etapaImplId = $getEtapaImplId();
    if (!empty($etapaImplId)) {
      $Fk_etapa = $etapaImplId;
    }
  }

  // 3) Coherencia final
  if ($getEtapaPct($Fk_etapa) === 100) {
    $implId = $getEstadoImplId();
    if ($implId) $Fk_estado_bus = $implId;

    $prodId = $getCatProdId();
    if ($prodId) $Fk_categoria = $prodId;
  }

  // 4) Guardar
  $usuario_info = obtenerUsuarioSession();
  $user_id = is_array($usuario_info) ? $usuario_info['user_id'] : $usuario_info;
  
  if ($ID > 0) {
    // Obtener datos anteriores para el log
    $stmt_anterior = $pdo->prepare("
      SELECT r.*, 
             e.descripcion AS entidad_nombre,
             d.descripcion AS dependencia_nombre,
             b.descripcion AS bus_nombre,
             t.descripcion AS tecnologia_nombre
      FROM registro r
      LEFT JOIN entidad e ON e.ID = r.Fk_entidad
      LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia  
      LEFT JOIN bus b ON b.ID = r.Fk_bus
      LEFT JOIN tecnologia t ON t.ID = r.Fk_tecnologia
      WHERE r.ID = ?
    ");
    $stmt_anterior->execute([$ID]);
    $datos_anteriores = $stmt_anterior->fetch(PDO::FETCH_ASSOC);
    
    // UPDATE
    $stm = $pdo->prepare("
      UPDATE registro SET
        Fk_dependencia = ?, 
        Fk_entidad     = ?, 
        Fk_bus         = ?, 
        Fk_motor_base  = ?, 
        Fk_tecnologia  = ?,
        Fk_estado_bus  = ?, 
        Fk_categoria   = ?, 
        Fk_etapa       = ?, 
        fecha_inicio   = ?, 
        fecha_migracion= ?,
        fecha_modificacion = NOW()
      WHERE ID = ?
    ");
    $stm->execute([
      $Fk_dependencia, $Fk_entidad, $Fk_bus,
      $Fk_motor_base, $Fk_tecnologia, $Fk_estado_bus,
      $Fk_categoria, $Fk_etapa,
      $fecha_inicio, $fecha_migracion,
      $ID
    ]);
    
    // Registrar en bitácora
    $descripcion_bitacora = "Registro ID $ID actualizado";
    if ($datos_anteriores) {
      $descripcion_bitacora .= " - Entidad: " . ($datos_anteriores['entidad_nombre'] ?? 'N/A');
      if ($datos_anteriores['dependencia_nombre']) {
        $descripcion_bitacora .= ", Dependencia: " . $datos_anteriores['dependencia_nombre'];
      }
      if ($datos_anteriores['bus_nombre']) {
        $descripcion_bitacora .= ", Bus: " . $datos_anteriores['bus_nombre'];
      }
    }
    
    registrarBitacora($pdo, $user_id, 'registro', 'UPDATE', $descripcion_bitacora, $ID);
    
  } else {
    // INSERT
    $stmt = $pdo->prepare("
      INSERT INTO registro
        (Fk_dependencia, Fk_entidad, Fk_bus, Fk_motor_base, Fk_tecnologia,
         Fk_estado_bus, Fk_categoria, Fk_etapa, fecha_inicio, fecha_migracion, fecha_creacion)
      VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
    ");
    $stmt->execute([
      $Fk_dependencia, $Fk_entidad, $Fk_bus,
      $Fk_motor_base, $Fk_tecnologia, $Fk_estado_bus,
      $Fk_categoria, $Fk_etapa,
      $fecha_inicio, $fecha_migracion
    ]);
    $ID = $pdo->lastInsertId();
    
    // Obtener nombres para el log
    $stmt_nombres = $pdo->prepare("
      SELECT e.descripcion AS entidad_nombre,
             d.descripcion AS dependencia_nombre,
             b.descripcion AS bus_nombre,
             t.descripcion AS tecnologia_nombre
      FROM registro r
      LEFT JOIN entidad e ON e.ID = r.Fk_entidad
      LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia  
      LEFT JOIN bus b ON b.ID = r.Fk_bus
      LEFT JOIN tecnologia t ON t.ID = r.Fk_tecnologia
      WHERE r.ID = ?
    ");
    $stmt_nombres->execute([$ID]);
    $nombres = $stmt_nombres->fetch(PDO::FETCH_ASSOC);
    
    // Registrar en bitácora
    $descripcion_bitacora = "Nuevo registro ID $ID creado";
    if ($nombres) {
      $descripcion_bitacora .= " - Entidad: " . ($nombres['entidad_nombre'] ?? 'N/A');
      if ($nombres['dependencia_nombre']) {
        $descripcion_bitacora .= ", Dependencia: " . $nombres['dependencia_nombre'];
      }
      if ($nombres['bus_nombre']) {
        $descripcion_bitacora .= ", Bus: " . $nombres['bus_nombre'];
      }
      if ($nombres['tecnologia_nombre']) {
        $descripcion_bitacora .= ", Tecnología: " . $nombres['tecnologia_nombre'];
      }
    }
    
    registrarBitacora($pdo, $user_id, 'registro', 'INSERT', $descripcion_bitacora, $ID);
  }

  // 5) Responder
  $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

  if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'ok'     => true,
      'status' => ($ID > 0 ? 'updated' : 'created'),
      'id'     => $ID
    ]);
    exit;
  }

  header("Location: index.php?ok=" . ($ID > 0 ? 'updated' : 'created'));
  exit;

  } catch (Exception $e) {
    error_log("Error en actions.php: " . $e->getMessage());
    
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'ok'  => false,
        'msg' => 'Error interno del servidor: ' . $e->getMessage()
      ]);
      exit;
    } else {
      header("Location: index.php?error=" . urlencode($e->getMessage()));
      exit;
    }
  }
}
?>
