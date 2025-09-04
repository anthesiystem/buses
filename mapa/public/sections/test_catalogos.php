<?php
// Archivo de prueba para verificar las respuestas JSON de catalogos_admin.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test básico de la API
$testUrl = 'http://localhost/final/mapa/public/sections/catalogos_admin.php?api=list&tabla=categoria';

echo "<h3>Prueba de API de Catálogos</h3>";
echo "<p>URL de prueba: $testUrl</p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Accept: application/json'
    ]
]);

$response = file_get_contents($testUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Error: No se pudo obtener respuesta</p>";
} else {
    echo "<h4>Respuesta recibida:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON válido</p>";
        echo "<h4>Datos decodificados:</h4>";
        echo "<pre>" . print_r($json, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ JSON inválido: " . json_last_error_msg() . "</p>";
    }
}
?>
