<?php
// server/permiso.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function cargarPermisos($idUsuario, $pdo) {
    $stmt = $pdo->prepare("SELECT 
        m.descripcion AS modulo, 
        p.accion, 
        p.Fk_entidad, 
        p.Fk_bus
    FROM permiso_usuario p
    JOIN modulo m ON p.Fk_modulo = m.ID
    WHERE p.Fk_usuario = ? AND p.activo = 1");
    $stmt->execute([$idUsuario]);
    $_SESSION['permisos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tienePermiso($modulo, $accion, $entidad = null, $bus = null) {
    if (!isset($_SESSION['permisos'])) return false;

    // Permitir todo para SUPERSU y ADMIN
    if (isset($_SESSION['fk_perfiles']) && $_SESSION['fk_perfiles'] >= 3) {
        return true;
    }

    foreach ($_SESSION['permisos'] as $permiso) {
        if (
            $permiso['modulo'] === $modulo &&
            $permiso['accion'] === $accion &&
            (is_null($permiso['Fk__entidad']) || is_null($entidad) || $permiso['Fk_entidad'] == $entidad) &&
            (is_null($permiso['Fk_bus'])     || is_null($bus)     || $permiso['Fk_bus'] == $bus)
        ) {
            return true;
        }
    }
    return false;
}

function obtenerPermisosPorUsuario($idUsuario, $pdo) {
    $stmt = $pdo->prepare("SELECT 
        m.descripcion AS modulo,
        p.accion,
        e.descripcion AS entidad,
        b.Nombre AS bus
    FROM permiso_usuario p
    JOIN modulo m ON p.Fk_modulo = m.ID
    LEFT JOIN entidad e ON p.Fk_entidad = e.ID
    LEFT JOIN bus b ON p.Fk_bus = b.ID
    WHERE p.Fk_usuario = ? AND p.activo = 1");
    $stmt->execute([$idUsuario]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ejemplo de uso:
// if (!tienePermiso('registro', 'UPDATE', 2, 3)) {
//     header("Location: acceso_denegado.php");
//     exit;
// }
