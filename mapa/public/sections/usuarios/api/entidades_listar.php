<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

try {
    $sql = "SELECT ID, descripcion FROM entidad WHERE activo = 1 ORDER BY descripcion";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $entidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($entidades, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
