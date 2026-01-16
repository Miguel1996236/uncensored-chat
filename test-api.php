<?php
declare(strict_types=1);

/**
 * Script de prueba para verificar la conexión con la API
 * Accede a: http://localhost/uncensored-chat/test-api.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test de Conexión API</h1>";
echo "<pre>";

$url = 'http://46.4.122.18:11434/api/generate';

$data = [
    'model' => 'llama2-uncensored',
    'stream' => false,
    'prompt' => 'Hola, responde con un saludo corto en español',
    'options' => [
        'temperature' => 0.3
    ]
];

echo "URL: $url\n\n";
echo "Datos enviados:\n";
print_r($data);
echo "\n";

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
$errorNo = curl_errno($ch);

echo "=== Información de la petición ===\n";
echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Error cURL: " . ($error ?: 'Ninguno') . "\n";
echo "Error No: " . ($errorNo ?: 'Ninguno') . "\n";
echo "\n";

if ($error) {
    echo "❌ ERROR cURL: $error\n";
    curl_close($ch);
    exit;
}

if ($httpCode !== 200) {
    echo "❌ ERROR HTTP: $httpCode\n";
    echo "Respuesta recibida:\n";
    echo substr($response, 0, 1000) . "\n";
    curl_close($ch);
    exit;
}

echo "=== Respuesta recibida ===\n";
echo "Longitud: " . strlen($response) . " bytes\n";
echo "Primeros 500 caracteres:\n";
echo substr($response, 0, 500) . "\n";
echo "\n";

// Intentar decodificar JSON
$decoded = json_decode($response, true);
$jsonError = json_last_error();

echo "=== Análisis JSON ===\n";
if ($jsonError === JSON_ERROR_NONE) {
    echo "✅ JSON válido\n";
    echo "Estructura:\n";
    print_r($decoded);
} else {
    echo "❌ Error al decodificar JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
    echo "Código: $jsonError\n";
    echo "\nRespuesta completa (primeros 2000 caracteres):\n";
    echo htmlspecialchars(substr($response, 0, 2000)) . "\n";
    
    // Verificar si hay caracteres no imprimibles
    echo "\n=== Análisis de caracteres ===\n";
    $nonPrintable = [];
    for ($i = 0; $i < min(500, strlen($response)); $i++) {
        $char = $response[$i];
        $ord = ord($char);
        if ($ord < 32 && !in_array($ord, [9, 10, 13])) {
            $nonPrintable[] = "Posición $i: ASCII $ord";
        }
    }
    if (!empty($nonPrintable)) {
        echo "Caracteres no imprimibles encontrados:\n";
        print_r($nonPrintable);
    } else {
        echo "No se encontraron caracteres no imprimibles en los primeros 500 caracteres\n";
    }
}

curl_close($ch);
echo "</pre>";
