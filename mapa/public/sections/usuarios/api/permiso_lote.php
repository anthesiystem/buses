<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

function jerr($m){ echo json_encode(['ok'=>false,'msg'=>$m], JSON_UNESCAPED_UNICODE); exit; }
function to01($v){ 
    $v = strtolower(trim((string)$v)); 
    $result = in_array($v, ['1','true','on','si','sí','yes']) ? 1 : 0;
    // Asegurar que sea exactamente 0 o 1
    return (int)$result;
}

// Genera UUID v4
function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant bits
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Normaliza selección: convierte string/array a array limpio
function normalizarSeleccion($input) {
    if (is_string($input)) {
        $input = json_decode($input, true) ?: [$input];
    }
    if (!is_array($input)) return [];
    
    // Filtra valores válidos
    $result = array_filter($input, function($v) {
        return $v !== '' && $v !== null;
    });
    
    // Si incluye 'ALL', solo devuelve ['ALL']
    if (in_array('ALL', $result)) {
        return ['ALL'];
    }
    
    return array_values($result);
}

// Valida que las selecciones sean consistentes
function validarSelecciones($ents, $buss) {
    // Si alguna es 'ALL', ambas deben ser válidas
    $entTieneAll = in_array('ALL', $ents);
    $busTieneAll = in_array('ALL', $buss);
    
    if ($entTieneAll && count($ents) > 1) {
        jerr('Si seleccionas "Todas" las entidades, no puedes seleccionar entidades específicas.');
    }
    
    if ($busTieneAll && count($buss) > 1) {
        jerr('Si seleccionas "Todos" los buses, no puedes seleccionar buses específicos.');
    }
}

// Expande el producto cartesiano de entidades x buses
function expandirProducto($ents, $buss) {
    $combos = [];
    
    foreach ($ents as $ent) {
        foreach ($buss as $bus) {
            // Convierte 'ALL' a NULL para la BD
            $entValue = ($ent === 'ALL') ? null : $ent;
            $busValue = ($bus === 'ALL') ? null : (int)$bus;
            $combos[] = [$entValue, $busValue];
        }
    }
    
    return $combos;
}

// Obtiene combos existentes de un grupo
function fetchCombosGrupo($pdo, $group_token) {
    $stmt = $pdo->prepare("SELECT FK_entidad, FK_bus FROM permiso_usuario WHERE group_token = ?");
    $stmt->execute([$group_token]);
    
    $combos = [];
    while ($row = $stmt->fetch()) {
        $combos[] = [$row['FK_entidad'], (int)$row['FK_bus'] ?: null];
    }
    
    return $combos;
}

// Diferencia entre arrays de combos
function array_diff_combinate($array1, $array2) {
    $result = [];
    
    foreach ($array1 as $combo1) {
        $found = false;
        foreach ($array2 as $combo2) {
            if ($combo1[0] === $combo2[0] && $combo1[1] === $combo2[1]) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $result[] = $combo1;
        }
    }
    
    return $result;
}

// Inserta un permiso específico
function upsertPermiso($pdo, $group_token, $usuario, $modulo, $accion, $activo, $entidad, $bus) {
    // Validar y normalizar tipos de datos
    $group_token = (string)$group_token;
    $usuario = (int)$usuario;
    $modulo = (int)$modulo;
    $entidad = $entidad === null ? null : (int)$entidad;
    $bus = $bus === null ? null : (int)$bus;
    $accion = $accion === '' ? null : (string)$accion;
    $activo = (int)$activo; // Asegurar que sea entero
    
    // Validar que activo sea 0 o 1
    if ($activo !== 0 && $activo !== 1) {
        $activo = $activo ? 1 : 0;
    }
    
    $stmt = $pdo->prepare(
        "INSERT INTO permiso_usuario 
         (group_token, Fk_usuario, Fk_modulo, FK_entidad, FK_bus, accion, activo)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->execute([
        $group_token,
        $usuario,
        $modulo,
        $entidad,
        $bus,
        $accion,
        $activo
    ]);
}

// Elimina un permiso específico del grupo
function deletePermiso($pdo, $group_token, $entidad, $bus) {
    $stmt = $pdo->prepare(
        "DELETE FROM permiso_usuario 
         WHERE group_token = ? AND FK_entidad <=> ? AND FK_bus <=> ?"
    );
    
    $stmt->execute([$group_token, $entidad, $bus]);
}

// Obtiene datos actuales de un grupo para comparar cambios
function obtenerDatosGrupo($pdo, $group_token) {
    $stmt = $pdo->prepare(
        "SELECT Fk_usuario, Fk_modulo, accion, activo 
         FROM permiso_usuario 
         WHERE group_token = ? 
         LIMIT 1"
    );
    $stmt->execute([$group_token]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar la solicitud
$action = $_POST['action'] ?? '';
$group_token = $_POST['group_token'] ?? '';

try {
    if ($action === 'crear') {
        // CREAR LOTE
        $usuario = (int)($_POST['Fk_usuario'] ?? 0);
        $modulo = (int)($_POST['Fk_modulo'] ?? 0);
        $accion = $_POST['accion'] ?? null;
        $activo = to01($_POST['activo'] ?? '1');
        
        // Debug: verificar el valor de activo
        error_log("DEBUG - Crear lote: activo recibido = " . var_export($_POST['activo'] ?? '1', true) . ", convertido = " . var_export($activo, true));
        
        if ($usuario <= 0) jerr('Selecciona un usuario válido.');
        if ($modulo <= 0) jerr('Selecciona un módulo válido.');
        
        // Validar que activo sea exactamente 0 o 1
        if (!is_int($activo) || ($activo !== 0 && $activo !== 1)) {
            jerr('Valor de activo inválido: debe ser 0 o 1');
        }
        
        $ents = normalizarSeleccion($_POST['entidades'] ?? []);
        $buss = normalizarSeleccion($_POST['buses'] ?? []);
        
        if (empty($ents)) jerr('Selecciona al menos una entidad.');
        if (empty($buss)) jerr('Selecciona al menos un bus.');
        
        validarSelecciones($ents, $buss);
        
        $combos = expandirProducto($ents, $buss);
        $group = uuidv4();
        
        $pdo->beginTransaction();
        
        foreach ($combos as [$ent, $bus]) {
            upsertPermiso($pdo, $group, $usuario, $modulo, $accion, $activo, $ent, $bus);
        }
        
        $pdo->commit();
        
        // Registrar en bitácora
        $usuario_session = obtenerUsuarioSession();
        $descripcion = "Lote de permisos creado - grupo: '$group', usuario: '$usuario', módulo: '$modulo', " . count($combos) . " combinaciones";
        registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_lote_crear', $descripcion, null);
        
        echo json_encode(['ok' => true, 'group_token' => $group]);
        
    } elseif ($action === 'editar') {
        // EDITAR LOTE
        if (!$group_token) jerr('Token de grupo requerido.');
        
        $usuario = (int)($_POST['Fk_usuario'] ?? 0);
        $modulo = (int)($_POST['Fk_modulo'] ?? 0);
        $accion = $_POST['accion'] ?? null;
        $activo = to01($_POST['activo'] ?? '1');
        
        // Debug: verificar el valor de activo
        error_log("DEBUG - Editar lote: activo recibido = " . var_export($_POST['activo'] ?? '1', true) . ", convertido = " . var_export($activo, true));
        
        if ($usuario <= 0) jerr('Selecciona un usuario válido.');
        if ($modulo <= 0) jerr('Selecciona un módulo válido.');
        
        // Validar que activo sea exactamente 0 o 1
        if (!is_int($activo) || ($activo !== 0 && $activo !== 1)) {
            jerr('Valor de activo inválido: debe ser 0 o 1');
        }
        
        // Obtener datos actuales para comparar
        $datosAnteriores = obtenerDatosGrupo($pdo, $group_token);
        if (!$datosAnteriores) jerr('Grupo no encontrado.');
        
        $entsNuevas = normalizarSeleccion($_POST['entidades'] ?? []);
        $bussNuevos = normalizarSeleccion($_POST['buses'] ?? []);
        
        if (empty($entsNuevas)) jerr('Selecciona al menos una entidad.');
        if (empty($bussNuevos)) jerr('Selecciona al menos un bus.');
        
        validarSelecciones($entsNuevas, $bussNuevos);
        
        $combosNuevos = expandirProducto($entsNuevas, $bussNuevos);
        
        $pdo->beginTransaction();
        
        $actual = fetchCombosGrupo($pdo, $group_token);
        $insertar = array_diff_combinate($combosNuevos, $actual);
        $eliminar = array_diff_combinate($actual, $combosNuevos);
        
        // Insertar nuevas combinaciones
        foreach ($insertar as [$e, $b]) {
            upsertPermiso($pdo, $group_token, $usuario, $modulo, $accion, $activo, $e, $b);
        }
        
        // Eliminar combinaciones que ya no aplican
        foreach ($eliminar as [$e, $b]) {
            deletePermiso($pdo, $group_token, $e, $b);
        }
        
        // Verificar si hay cambios en metadatos (usuario, módulo, acción, activo)
        $cambiosMeta = (
            $datosAnteriores['Fk_usuario'] != $usuario ||
            $datosAnteriores['Fk_modulo'] != $modulo ||
            $datosAnteriores['accion'] != $accion ||
            $datosAnteriores['activo'] != $activo
        );
        
        if ($cambiosMeta) {
            // Asegurar tipos de datos correctos antes del UPDATE
            $usuario = (int)$usuario;
            $modulo = (int)$modulo;
            $accion = $accion === '' ? null : (string)$accion;
            $activo = (int)$activo;
            
            // Validar que activo sea 0 o 1
            if ($activo !== 0 && $activo !== 1) {
                $activo = $activo ? 1 : 0;
            }
            
            $stmt = $pdo->prepare(
                "UPDATE permiso_usuario 
                 SET Fk_usuario = ?, Fk_modulo = ?, accion = ?, activo = ? 
                 WHERE group_token = ?"
            );
            $stmt->execute([$usuario, $modulo, $accion, $activo, $group_token]);
        }
        
        $pdo->commit();
        
        // Registrar en bitácora
        $usuario_session = obtenerUsuarioSession();
        $cambios = [];
        if (count($insertar) > 0) $cambios[] = count($insertar) . " combinaciones agregadas";
        if (count($eliminar) > 0) $cambios[] = count($eliminar) . " combinaciones eliminadas";
        if ($cambiosMeta) $cambios[] = "metadatos actualizados";
        
        $descripcion = "Lote de permisos editado - grupo: '$group_token', " . implode(', ', $cambios);
        registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_lote_editar', $descripcion, null);
        
        echo json_encode(['ok' => true]);
        
    } elseif ($action === 'eliminar') {
        // ELIMINAR GRUPO COMPLETO
        if (!$group_token) jerr('Token de grupo requerido.');
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM permiso_usuario WHERE group_token = ?");
        $stmt->execute([$group_token]);
        $total = $stmt->fetch()['total'];
        
        if ($total == 0) jerr('Grupo no encontrado.');
        
        $stmt = $pdo->prepare("DELETE FROM permiso_usuario WHERE group_token = ?");
        $stmt->execute([$group_token]);
        
        // Registrar en bitácora
        $usuario_session = obtenerUsuarioSession();
        $descripcion = "Lote de permisos eliminado - grupo: '$group_token', $total registros eliminados";
        registrarBitacora($pdo, $usuario_session, 'permiso_usuario', 'permiso_lote_eliminar', $descripcion, null);
        
        echo json_encode(['ok' => true]);
        
    } else {
        jerr('Acción no válida.');
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $msg = $e->getMessage();
    
    if (strpos($msg, '1062') !== false) {
        jerr('Ya existe un permiso con la misma combinación.');
    }
    if (strpos($msg, '1452') !== false) {
        jerr('Usuario o Módulo no existen (clave foránea).');
    }
    
    echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
