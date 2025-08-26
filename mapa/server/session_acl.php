<?php
// /final/mapa/server/session_acl.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Función para verificar permisos específicos
function tienePermiso($accion, $entidad = null, $bus = null) {
    // Verificar que el usuario esté en sesión
    if (!isset($_SESSION['usuario'])) {
        return false;
    }

    // Obtener el nivel del usuario
    $nivel = isset($_SESSION['usuario']['nivel']) ? (int)$_SESSION['usuario']['nivel'] : 1;

    // Nivel 3 y 4 tienen acceso total
    if ($nivel >= 3) {
        return true;
    }

    // Nivel 2 solo tiene permiso de lectura
    if ($nivel === 2) {
        return $accion === 'read';
    }

    // Para nivel 1, verificar permisos específicos
    if (!isset($_SESSION['permisos']) || !is_array($_SESSION['permisos'])) {
        return false;
    }

    // Buscar un permiso que coincida con los criterios
    foreach ($_SESSION['permisos'] as $permiso) {
        if (!isset($permiso['modulo']) || $permiso['modulo'] !== 'mapa_general') {
            continue;
        }

        // Verificar entidad si se especificó
        if ($entidad !== null && 
            isset($permiso['entidad']) && 
            $permiso['entidad'] !== '*' && 
            $permiso['entidad'] !== $entidad) {
            continue;
        }

        // Verificar bus si se especificó
        if ($bus !== null && 
            isset($permiso['bus']) && 
            $permiso['bus'] !== '*' && 
            $permiso['bus'] !== $bus) {
            continue;
        }

        // Verificar la acción
        if (isset($permiso['acciones']) && is_array($permiso['acciones'])) {
            if (in_array($accion, $permiso['acciones'])) {
                return true;
            }
        }
    }

    return false;
}

// Si se solicita como API, devolver el ACL en formato JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($_SESSION['acl'] ?? ['all'=>false,'mods'=>[]], JSON_UNESCAPED_UNICODE);
    exit;
}
