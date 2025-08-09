<?php
$idRegistro = isset($_GET['id']) ? (int)$_GET['id'] : 0;
require_once '../../../server/config.php';

$sql = "SELECT cr.encabezado, cr.comentario, cr.fecha_creacion, u.cuenta AS usuario
        FROM comentario_registro cr
        INNER JOIN usuario u ON cr.Fk_usuario = u.ID
        WHERE cr.Fk_registro = ? AND cr.activo = 1
        ORDER BY cr.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idRegistro]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
