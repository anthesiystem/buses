<?php
header('Content-Type: application/json');

require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$activo = isset($_GET['activo']) ? intval($_GET['activo']) : null;

if ($id !== null && $activo !== null) {
    try {
        $stmt = $pdo->prepare("UPDATE registro SET Activo = :activo WHERE Id = :id");
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo actualizar"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Parámetros incompletos"]);
}
