<?php
// Archivo de prueba para verificar la función toggle de catalogos_admin.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Prueba de Toggle API</h3>";

// Primero obtener un registro para toggle
$testUrl = 'http://localhost/final/mapa/public/sections/catalogos_admin.php?api=list&tabla=categoria';

echo "<h4>1. Obteniendo lista de categorías:</h4>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Accept: application/json'
    ]
]);

$response = file_get_contents($testUrl, false, $context);
if ($response) {
    $json = json_decode($response, true);
    if (isset($json['rows']) && count($json['rows']) > 0) {
        $firstRow = $json['rows'][0];
        echo "<p>✅ Primer registro encontrado: ID {$firstRow['ID']}, Estado actual: " . ($firstRow['activo'] ? 'ACTIVO' : 'INACTIVO') . "</p>";
        
        echo "<h4>2. Probando toggle para ID {$firstRow['ID']}:</h4>";
        
        // Simular el toggle
        $postData = http_build_query([
            'tabla' => 'categoria',
            'id' => $firstRow['ID']
        ]);
        
        $toggleContext = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json",
                'content' => $postData
            ]
        ]);
        
        $toggleUrl = 'http://localhost/final/mapa/public/sections/catalogos_admin.php?api=toggle';
        $toggleResponse = file_get_contents($toggleUrl, false, $toggleContext);
        
        if ($toggleResponse) {
            echo "<h5>Respuesta del toggle:</h5>";
            echo "<pre>" . htmlspecialchars($toggleResponse) . "</pre>";
            
            $toggleJson = json_decode($toggleResponse, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($toggleJson['ok']) {
                    echo "<p style='color: green;'>✅ Toggle exitoso</p>";
                } else {
                    echo "<p style='color: red;'>❌ Error en toggle: " . htmlspecialchars($toggleJson['msg']) . "</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Respuesta no es JSON válido</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ No se pudo ejecutar toggle</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No hay registros para probar</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No se pudo obtener lista</p>";
}
?>
