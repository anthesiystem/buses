<?php
require_once __DIR__ . '/server/config.php';

try {
    // Verificar estructura del campo activo
    $stmt = $pdo->prepare("DESCRIBE usuario");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estructura del campo 'activo':</h3>";
    foreach($columns as $column) {
        if($column['Field'] === 'activo') {
            echo "<pre>";
            print_r($column);
            echo "</pre>";
            break;
        }
    }
    
    // Verificar algunos registros actuales
    echo "<h3>Valores actuales del campo activo:</h3>";
    $stmt = $pdo->prepare("SELECT ID, cuenta, activo, HEX(activo) as activo_hex FROM usuario LIMIT 5");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Cuenta</th><th>Activo</th><th>Activo (HEX)</th></tr>";
    foreach($usuarios as $user) {
        echo "<tr>";
        echo "<td>" . $user['ID'] . "</td>";
        echo "<td>" . $user['cuenta'] . "</td>";
        echo "<td>" . $user['activo'] . "</td>";
        echo "<td>" . $user['activo_hex'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
