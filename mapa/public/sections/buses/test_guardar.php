<?php
session_start();
require_once '../../../server/config.php';
require_once '../../../server/bitacora_helper.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint funcionando',
    'session_data' => [
        'usuario_id_from_session' => $_SESSION['usuario_id'] ?? null,
        'ID_from_session' => $_SESSION['ID'] ?? null,
        'usuario_id_from_function' => obtenerUsuarioSession(),
        'session_keys' => array_keys($_SESSION ?? [])
    ],
    'post_data' => $_POST,
    'files_data' => array_keys($_FILES ?? [])
]);
?>
