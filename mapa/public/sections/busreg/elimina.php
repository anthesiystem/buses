<?php
require_once '../../server/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id > 0) {
        // Eliminar lógicamente (activo = 0)
        $stmt = $pdo->prepare("UPDATE registro SET activo = 0 WHERE ID = ?");
        $stmt->execute([$id]);
    }
}

header("Location: index.php");
exit;
