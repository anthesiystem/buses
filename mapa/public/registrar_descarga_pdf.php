<?php
session_start();
require_once '../server/config.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "No autorizado";
    exit;
}

$estado = $_POST['estado'] ?? 'Desconocido';
$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario'] ?? 'Desconocido';
$descripcion = "El usuario $usuario_nombre descargó el PDF del estado $estado";

$stmt = $pdo->prepare("
    INSERT INTO bitacora (Fk_Id_Usuarios, Tabla_Afectada, Tipo_Accion, Descripcion, Fecha_Accion)
    VALUES (?, 'pdf', 'DESCARGA', ?, NOW())
");
$stmt->execute([$usuario_id, $descripcion]);

echo "OK";
?>
