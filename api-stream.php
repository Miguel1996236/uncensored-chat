<?php
declare(strict_types=1);

require_once __DIR__ . '/controlador/ChatController.php';

/**
 * Endpoint para streaming con Server-Sent Events (SSE)
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Deshabilitar buffering en Nginx

// Permitir CORS si es necesario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "data: " . json_encode(['error' => 'Método no permitido']) . "\n\n";
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "data: " . json_encode(['error' => 'JSON inválido']) . "\n\n";
        exit;
    }
    
    // Forzar stream a true
    $input['stream'] = true;
    
    // Validar prompt
    if (!isset($input['prompt']) || empty(trim($input['prompt']))) {
        echo "data: " . json_encode(['error' => 'El prompt es requerido']) . "\n\n";
        exit;
    }
    
    require_once __DIR__ . '/modelo/Validator.php';
    require_once __DIR__ . '/modelo/ApiService.php';
    require_once __DIR__ . '/config.php';
    
    $prompt = Validator::validatePrompt($input['prompt']);
    $model = Validator::validateModel($input['model'] ?? Config::DEFAULT_MODEL());
    $system = Validator::validateSystem($input['system'] ?? null);
    
    $params = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => true,
    ];
    
    if ($system !== null) {
        $params['system'] = $system;
    }
    
    $options = Validator::buildOptions($input['options'] ?? []);
    if (!empty($options)) {
        $params['options'] = $options;
    }
    
    // Realizar petición a la API
    $apiService = new ApiService();
    $url = Config::API_URL();
    
    $ch = curl_init();
    $postData = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function($ch, $data) {
            // Procesar cada línea de NDJSON
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $decoded = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['response'])) {
                    // Enviar como SSE
                    echo "data: " . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                    ob_flush();
                    flush();
                }
            }
            return strlen($data);
        },
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 300,
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "data: " . json_encode(['error' => 'Error cURL: ' . $error]) . "\n\n";
    } elseif ($httpCode !== 200) {
        echo "data: " . json_encode(['error' => 'Error HTTP: ' . $httpCode]) . "\n\n";
    }
    
    // Enviar evento de finalización
    echo "data: " . json_encode(['done' => true]) . "\n\n";
    ob_flush();
    flush();
    
} catch (Exception $e) {
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    ob_flush();
    flush();
}
