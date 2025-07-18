<?php
require_once '../server/config.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE registro SET Activo = 0 WHERE Id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: registros.php");
}
?>