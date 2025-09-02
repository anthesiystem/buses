<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug Test</h3>";

try {
    require_once '../../../server/config.php';
    echo "<p>✅ Config cargado exitosamente</p>";
    
    // Test de conexión
    $test = $pdo->query("SELECT 1")->fetchColumn();
    echo "<p>✅ Conexión a BD: OK</p>";
    
    // Verificar tabla bus
    $stmt = $pdo->query("DESCRIBE bus");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Estructura tabla bus:</h4><ul>";
    foreach($columns as $col) {
        echo "<li>{$col['Field']} ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // Test de datos
    $stmt = $pdo->query("SELECT COUNT(*) FROM bus");
    $count = $stmt->fetchColumn();
    echo "<p>Registros en bus: $count</p>";
    
    if($count > 0) {
        $stmt = $pdo->query("SELECT * FROM bus LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h4>Registro de ejemplo:</h4><pre>";
        print_r($sample);
        echo "</pre>";
    }
    
    // Test de funciones helper
    if(function_exists('obtenerUsuarioSession')) {
        echo "<p>✅ Función obtenerUsuarioSession disponible</p>";
    } else {
        echo "<p>❌ Función obtenerUsuarioSession NO disponible</p>";
    }
    
    if(function_exists('registrarBitacora')) {
        echo "<p>✅ Función registrarBitacora disponible</p>";
    } else {
        echo "<p>❌ Función registrarBitacora NO disponible</p>";
    }
    
} catch(Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
?>
