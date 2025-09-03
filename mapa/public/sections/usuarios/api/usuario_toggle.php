<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
require_once __DIR__ . '/../../../../server/bitacora_helper.php';

$id = (int)($_POST['ID'] ?? 0); 
if ($id <= 0) { 
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']); 
    exit; 
}

try {
    // Obtener datos del usuario antes del cambio
    $stmt_prev = $pdo->prepare("SELECT cuenta, activo FROM usuario WHERE ID = ?");
    $stmt_prev->execute([$id]);
    $usuario_data = $stmt_prev->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_data) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado']); 
        exit;
    }
    
    // Verificar si el usuario intenta desactivarse a sí mismo
    $usuario_session = $_SESSION['usuario']['ID'] ?? 0;
    if ($id == $usuario_session && $usuario_data['activo'] == 1) {
        echo json_encode(['ok' => false, 'msg' => 'No puede desactivarse a sí mismo']);
        exit;
    }
    
    // Cambiar el estado (toggle) - Para campo BIT usar b'0' y b'1' directamente en la consulta
    $nuevo_estado = ($usuario_data['activo'] == 1 || $usuario_data['activo'] == '1') ? 0 : 1;
    
    if ($nuevo_estado == 1) {
        $stmt = $pdo->prepare("UPDATE usuario SET activo = b'1', fecha_modificacion = NOW() WHERE ID = ?");
        $ok = $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuario SET activo = b'0', fecha_modificacion = NOW() WHERE ID = ?");
        $ok = $stmt->execute([$id]);
    }
    
    if ($ok) {
        // Registrar en bitácora
        $usuario_session = obtenerUsuarioSession();
        $estado_texto = $nuevo_estado == 1 ? 'Activado' : 'Desactivado';
        $descripcion = "Usuario {$estado_texto}: {$usuario_data['cuenta']}";
        registrarBitacora($pdo, $usuario_session, 'usuario', 'usuario_toggle', $descripcion, $id);
    }
    
    echo json_encode(['ok' => (bool)$ok, 'nuevo_estado' => $nuevo_estado]);
    
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
