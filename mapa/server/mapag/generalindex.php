<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../acl.php';

// Obtener información del usuario
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);

// Obtener el ID del módulo mapa_general
$modId = 10;
try {
    $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
    if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
        $modId = (int)$row['ID'];
    }
} catch (\Throwable $e) { /* ignorar */ }

// Construir la condición de permisos
$permisosWhere = "";
$permisosParams = [];

// Debug
error_log("Usuario ID: $userId, Nivel: $nivel, Módulo: $modId");

// Para administradores o si hay permiso total, no aplicar filtro
if ($nivel >= 3) {
    error_log("Es administrador - sin filtros");
    $permisosWhere = "";
    $permisosParams = [];
} else {
    // Verificar permisos
    $stmt = $pdo->prepare("
        SELECT FK_entidad 
        FROM permiso_usuario 
        WHERE Fk_usuario = ? 
        AND Fk_modulo = ? 
        AND activo = 1
    ");
    $stmt->execute([$userId, $modId]);
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $entidadesPermitidas = [];
    $tiene_permiso_total = false;
    
    foreach ($permisos as $p) {
        $entidad = $p['FK_entidad'];
        error_log("Permiso encontrado - Entidad: " . var_export($entidad, true));
        
        if ($entidad === null || $entidad === '0' || $entidad === 0) {
            $tiene_permiso_total = true;
            error_log("Tiene permiso total");
            break;
        }
        
        if (is_numeric($entidad) && $entidad > 0) {
            $entidadesPermitidas[] = (int)$entidad;
        }
    }
    
    if ($tiene_permiso_total) {
        error_log("Usando permiso total - sin filtros");
        $permisosWhere = "";
        $permisosParams = [];
    } elseif (!empty($entidadesPermitidas)) {
        error_log("Aplicando filtro de entidades: " . implode(',', $entidadesPermitidas));
        $placeholders = str_repeat('?,', count($entidadesPermitidas) - 1) . '?';
        $permisosWhere = " AND e.ID IN ($placeholders)";
        $permisosParams = $entidadesPermitidas;
    } else {
        error_log("Sin permisos - retornando array vacío");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([]);
        exit;
    }
}

$sql = "
SELECT
  e.ID as entidad_id,
  UPPER(TRIM(e.descripcion)) AS entidad_nombre,
  GROUP_CONCAT(
    DISTINCT UPPER(TRIM(eb.descripcion))
    ORDER BY eb.descripcion
    SEPARATOR ','
  ) AS estatuses
FROM registro r
INNER JOIN entidad     e  ON e.ID  = r.Fk_entidad
INNER JOIN estado_bus  eb ON eb.ID = r.Fk_estado_bus
LEFT  JOIN bus         b  ON b.ID  = r.Fk_bus
WHERE r.activo = 1
  AND e.activo = 1
  AND (r.Fk_bus IS NULL OR b.activo = 1)
GROUP BY e.ID, e.descripcion
";

// Preparar y ejecutar la consulta con los permisos
if (!empty($permisosParams)) {
    $stmt = $pdo->prepare($sql . $permisosWhere);
    $stmt->execute($permisosParams);
} else {
    $stmt = $pdo->query($sql);
}

error_log("SQL ejecutado: " . $sql . $permisosWhere);

$datos = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estado = $row['entidad_nombre'];
    $arr = array_filter(array_map('trim', explode(',', $row['estatuses'] ?? '')));
    $arr = array_unique($arr);

    if (!$arr) continue;

    // Determinar el estado final
    $estado_final = 'SIN IMPLEMENTAR';
    if (count($arr) === 1) {
        $estado_final = $arr[0];
    } else {
        if (in_array('PRUEBAS', $arr, true) || in_array('EN PRUEBAS', $arr, true)) {
            $estado_final = 'PRUEBAS';
        } elseif (in_array('IMPLEMENTADO', $arr, true)) {
            $estado_final = 'IMPLEMENTADO';
        }
    }

    $datos[$estado] = [
        'entidad' => (int)$row['entidad_id'],
        'estado' => $estado_final,
        'estados_raw' => $arr
    ];

    error_log("Estado procesado - $estado: " . json_encode($datos[$estado]));
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($datos, JSON_UNESCAPED_UNICODE);
