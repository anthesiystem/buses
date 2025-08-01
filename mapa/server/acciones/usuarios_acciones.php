<?php
require_once __DIR__ . '/../../server/config.php';
session_start();

// Verificación de acceso: solo perfiles >= 3 (Admin o Supersu)
if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

// Validar campos del formulario
$persona_id  = $_POST['persona_id'] ?? null;
$cuenta      = $_POST['cuenta'] ?? null;
$contrasena  = $_POST['contrasena'] ?? null;
$nivel       = $_POST['nivel'] ?? null;
$id          = $_POST['id'] ?? null;

if (!$persona_id || !$cuenta || !$contrasena || $nivel === null) {
    echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
    exit;
}

try {
    if ($id) {
        // Actualizar usuario
        $stmt = $pdo->prepare("
            UPDATE usuario 
            SET Fk_persona = ?, cuenta = ?, contrasena = ?, nivel = ?, fecha_modificacion = NOW() 
            WHERE ID = ?
        ");
        $stmt->execute([$persona_id, $cuenta, $contrasena, $nivel, $id]);
    } else {
        // Insertar nuevo usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuario (Fk_persona, cuenta, contrasena, nivel) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$persona_id, $cuenta, $contrasena, $nivel]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos: ' . $e->getMessage()]);
}
