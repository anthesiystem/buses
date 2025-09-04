<?php
// Debug específico para toggle
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug Toggle Directo</h3>";

// Hacer una petición POST directa como lo haría JavaScript
$url = 'http://localhost/final/mapa/public/sections/catalogos_admin.php?api=toggle';
$postData = 'tabla=categoria&id=1';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'Content-Length: ' . strlen($postData)
        ],
        'content' => $postData
    ]
]);

echo "<h4>Parámetros de la petición:</h4>";
echo "<p>URL: $url</p>";
echo "<p>POST Data: $postData</p>";

echo "<h4>Ejecutando petición...</h4>";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Error: No se pudo obtener respuesta</p>";
    $error = error_get_last();
    if ($error) {
        echo "<p>Error details: " . htmlspecialchars($error['message']) . "</p>";
    }
} else {
    echo "<h4>Respuesta RAW:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    echo "<h4>Headers de respuesta:</h4>";
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            echo "<p>" . htmlspecialchars($header) . "</p>";
        }
    }
    
    echo "<h4>Análisis JSON:</h4>";
    $json = json_decode($response, true);
    $jsonError = json_last_error();
    
    if ($jsonError === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON válido</p>";
        echo "<pre>" . print_r($json, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ JSON inválido</p>";
        echo "<p>Error: " . json_last_error_msg() . "</p>";
        echo "<p>Código de error: $jsonError</p>";
        
        // Mostrar caracteres problemáticos
        echo "<h5>Análisis de caracteres:</h5>";
        $chars = str_split($response);
        foreach ($chars as $i => $char) {
            $ord = ord($char);
            if ($ord < 32 || $ord > 126) {
                echo "<p>Posición $i: Carácter no imprimible (ASCII $ord)</p>";
            }
        }
        
        // Mostrar los primeros caracteres
        echo "<h5>Primeros 100 caracteres:</h5>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 100)) . "</pre>";
    }
}
?>
