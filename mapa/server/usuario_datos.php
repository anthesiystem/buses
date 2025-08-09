<?php
session_start();
require_once __DIR__ . '/config.php';

// Solo usuarios con nivel 3 (Admin) o 4 (SuperSU) pueden usar esta acción
if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

// Validar que se envió un ID numérico
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$id = (int) $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.ID, u.cuenta, u.contrasenia, u.nivel 
        FROM usuario u 
        WHERE u.ID = ? AND u.activo = 1
    ");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode($usuario);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos']);
}
