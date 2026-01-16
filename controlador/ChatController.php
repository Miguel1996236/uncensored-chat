<?php
declare(strict_types=1);

require_once __DIR__ . '/../modelo/ApiService.php';
require_once __DIR__ . '/../modelo/Validator.php';
require_once __DIR__ . '/../config.php';

/**
 * Controlador para manejar las peticiones del chat
 */
class ChatController {
    private ApiService $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    /**
     * Procesa una petición de chat
     */
    public function handleRequest(): void {
        header('Content-Type: application/json; charset=utf-8');
        
        // Solo permitir POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        try {
            // Obtener y validar datos
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('JSON inválido');
            }
            
            // Validar prompt (requerido)
            if (!isset($input['prompt']) || empty(trim($input['prompt']))) {
                throw new InvalidArgumentException('El prompt es requerido');
            }
            
            $prompt = Validator::validatePrompt($input['prompt']);
            $model = Validator::validateModel($input['model'] ?? Config::DEFAULT_MODEL);
            $stream = Validator::validateStream($input['stream'] ?? Config::DEFAULT_STREAM);
            $system = Validator::validateSystem($input['system'] ?? null);
            $format = Validator::validateFormat($input['format'] ?? null);
            
            // Construir parámetros
            $params = [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => $stream,
            ];
            
            if ($system !== null) {
                $params['system'] = $system;
            }
            
            if ($format !== null) {
                $params['format'] = $format;
            }
            
            // Agregar opciones
            $options = Validator::buildOptions($input['options'] ?? []);
            if (!empty($options)) {
                $params['options'] = $options;
            }
            
            // Agregar keep_alive si está presente
            if (isset($input['keep_alive']) && is_string($input['keep_alive'])) {
                // Validar formato (ej: "10m", "1h", "30s")
                if (preg_match('/^\d+[smhd]$/', $input['keep_alive'])) {
                    $params['keep_alive'] = $input['keep_alive'];
                }
            }
            
            // Llamar a la API
            $response = $this->apiService->generate($params);
            
            // Sanitizar respuesta si contiene HTML
            if (isset($response['response'])) {
                $response['response'] = Validator::sanitizeHtml($response['response']);
            }
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
        }
    }
}
