<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../session_acl.php';

function getEstadosPermitidos() {
    global $pdo;
    
    $userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
    $nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);
    
    // Si es admin, retorna todos los estados
    if ($nivel >= 3) {
        $stmt = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener el ID del módulo mapa_general
    $modId = 10;
    try {
        $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
        if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
            $modId = (int)$row['ID'];
        }
    } catch (\Throwable $e) {
        error_log("Error obteniendo módulo: " . $e->getMessage());
    }
    
    // Obtener estados permitidos para el usuario
    $sql = "
        SELECT DISTINCT e.ID, e.descripcion
        FROM entidad e
        INNER JOIN permiso_usuario pu ON (
            pu.FK_entidad = e.ID OR pu.FK_entidad IS NULL
        )
        WHERE pu.Fk_usuario = :userId
        AND pu.Fk_modulo = :modId
        AND pu.activo = 1
        AND e.activo = 1
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':userId' => $userId,
            ':modId' => $modId
        ]);
        error_log("SQL ejecutado para permisos: " . $sql);
        error_log("Parámetros: userId=$userId, modId=$modId");
    } catch (\PDOException $e) {
        error_log("Error en consulta de permisos: " . $e->getMessage());
        return [];
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
