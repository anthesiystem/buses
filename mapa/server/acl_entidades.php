<?php
// server/acl_entidades.php
declare(strict_types=1);

/**
 * Intenta resolver un "módulo" a su ID.
 * - Si $key es numérico, se usa como ID.
 * - Si es string, probamos columnas típicas en este orden: slug, nombre, descripcion.
 *   Si alguna columna no existe, se ignora sin romper.
 * - Si no se encuentra, retorna null y el filtro por módulo se omite.
 */
function moduloIdPorClave(PDO $pdo, $key): ?int {
    if ($key === null || $key === '') return null;
    if (is_numeric($key)) return (int)$key;

    $colCandidates = ['slug','nombre','descripcion'];
    foreach ($colCandidates as $col) {
        try {
            $sql = "SELECT ID FROM modulo WHERE $col = :k LIMIT 1";
            $st  = $pdo->prepare($sql);
            $st->execute([':k' => (string)$key]);
            $id = $st->fetchColumn();
            if ($id !== false) return (int)$id;
        } catch (PDOException $e) {
            // Columna no existe (42S22) u otro error → probamos siguiente
            // Puedes loguear si lo deseas.
            continue;
        }
    }
    return null;
}

/**
 * Devuelve IDs de entidades que el usuario puede VER (READ) para un bus dado.
 * - Bypass para niveles >= 3 (Admin/Supersu): todas las entidades activas.
 * - Respeta permisos con Fk_entidad NULL (comodín) y/o Fk_bus NULL (comodín).
 * - Si $modKey es string o ID, se intenta acotar por módulo; si no se resuelve, se omite.
 */
function entidadesPermitidasPorUsuario(PDO $pdo, int $usuarioId, ?int $busId = null, $modKey = null): array
{
    // Bypass por nivel
    $nivel = (int)($_SESSION['nivel'] ?? 0);
    if ($nivel >= 3) {
        $stAll = $pdo->query("SELECT ID FROM entidad WHERE activo = 1");
        return array_map('intval', array_column($stAll->fetchAll(PDO::FETCH_ASSOC), 'ID'));
    }

    // Resuelve módulo a ID (si aplica)
    $modId = moduloIdPorClave($pdo, $modKey);

    // -------- Permiso GLOBAL (Fk_entidad IS NULL) --------
    $where = ["pu.Fk_usuario = :u", "pu.accion = :accion", "pu.activo = 1", "pu.Fk_entidad IS NULL"];
    $params = [':u' => $usuarioId, ':accion' => 'READ'];

    if ($busId !== null) {
        $where[] = "(pu.Fk_bus IS NULL OR pu.Fk_bus = :busId)";
        $params[':busId'] = $busId;
    }
    if ($modId !== null) {
        $where[] = "pu.Fk_modulo = :modId";
        $params[':modId'] = $modId;
    }

    $sqlGlobal = "SELECT 1 FROM permiso_usuario pu WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $stG = $pdo->prepare($sqlGlobal);
    $stG->execute($params);
    $tieneGlobal = (bool)$stG->fetchColumn();

    if ($tieneGlobal) {
        $stAll = $pdo->query("SELECT ID FROM entidad WHERE activo = 1");
        return array_map('intval', array_column($stAll->fetchAll(PDO::FETCH_ASSOC), 'ID'));
    }

    // -------- Permisos por entidad específica --------
    $where = ["pu.Fk_usuario = :u", "pu.accion = :accion", "pu.activo = 1", "pu.Fk_entidad IS NOT NULL"];
    $params = [':u' => $usuarioId, ':accion' => 'READ'];

    if ($busId !== null) {
        $where[] = "(pu.Fk_bus IS NULL OR pu.Fk_bus = :busId)";
        $params[':busId'] = $busId;
    }
    if ($modId !== null) {
        $where[] = "pu.Fk_modulo = :modId";
        $params[':modId'] = $modId;
    }

    $sql = "SELECT DISTINCT pu.Fk_entidad AS entidad_id
            FROM permiso_usuario pu
            WHERE " . implode(' AND ', $where);

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $ids = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'entidad_id'));

    return $ids ?: [];
}

/** Placeholders para IN dinámico */
function inPlaceholders(array $vals): array
{
    if (empty($vals)) return ['(?)', [-1]]; // evita IN () vacío
    return [implode(',', array_fill(0, count($vals), '?')), array_values($vals)];
}
