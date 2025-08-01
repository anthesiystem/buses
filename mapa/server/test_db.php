<?php
require_once '../server/config.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM usuario");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Conexión correcta. Usuarios encontrados: " . $row['total'];
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
