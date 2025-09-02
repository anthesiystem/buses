<?php
// Test directo de la API de lotes
$apiUrl = 'http://localhost/final/mapa/public/sections/usuarios/api/permiso_lote.php';

$postData = [
    'action' => 'crear',
    'Fk_usuario' => '1',
    'Fk_modulo' => '1', 
    'accion' => 'test',
    'activo' => '1',
    'entidades' => ['ALL'],
    'buses' => ['ALL']
];

echo "Enviando datos a la API:\n";
print_r($postData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nCódigo HTTP: $httpCode\n";
echo "Respuesta completa:\n";
echo $response;
?>
