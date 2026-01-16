<?php
declare(strict_types=1);

require_once __DIR__ . '/../modelo/ApiService.php';
require_once __DIR__ . '/../modelo/Validator.php';
require_once __DIR__ . '/../modelo/RateLimiter.php';
require_once __DIR__ . '/../modelo/Logger.php';
require_once __DIR__ . '/../modelo/ResponseCache.php';
require_once __DIR__ . '/../config.php';

/**
 * Controlador para manejar las peticiones del chat
 */
class ChatController {
    private ApiService $apiService;
    private RateLimiter $rateLimiter;
    private Logger $logger;
    private ResponseCache $cache;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->rateLimiter = new RateLimiter(60, 60); // 60 requests por minuto
        $this->logger = new Logger();
        $this->cache = new ResponseCache(3600); // 1 hora de caché
    }
    
    /**
     * Procesa una petición de chat
     */
    public function handleRequest(): void {
        // Limpiar cualquier output previo
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Establecer headers antes de cualquier output
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Solo permitir POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->rateLimiter->checkLimit($ip)) {
            $remaining = $this->rateLimiter->getRemainingRequests($ip);
            http_response_code(429);
            header('Retry-After: ' . $remaining['reset_in']);
            echo json_encode([
                'error' => 'Demasiadas peticiones. Intenta de nuevo en ' . $remaining['reset_in'] . ' segundos.',
                'rate_limit' => $remaining
            ], JSON_UNESCAPED_UNICODE);
            $this->logger->warning('Rate limit exceeded', ['ip' => $ip]);
            return;
        }
        
        // Limpiar rate limit antiguo periódicamente (10% de probabilidad)
        if (rand(1, 10) === 1) {
            $this->rateLimiter->cleanup();
        }
        
        try {
            $startTime = microtime(true);
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
            $model = Validator::validateModel($input['model'] ?? Config::DEFAULT_MODEL());
            $stream = Validator::validateStream($input['stream'] ?? Config::DEFAULT_STREAM());
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
            
            // Verificar caché (solo si stream es false)
            $cacheKey = null;
            $fromCache = false;
            if (!($params['stream'] ?? false)) {
                $cacheKey = ResponseCache::generateKey($params);
                $cachedResponse = $this->cache->get($cacheKey);
                
                if ($cachedResponse !== null) {
                    $response = $cachedResponse;
                    $fromCache = true;
                    $this->logger->info('Response served from cache', ['key' => $cacheKey]);
                }
            }
            
            // Si no está en caché, llamar a la API
            if (!$fromCache) {
                $response = $this->apiService->generate($params);
                
                // Guardar en caché (solo si stream es false y la respuesta es exitosa)
                if ($cacheKey !== null && isset($response['response'])) {
                    $this->cache->set($cacheKey, $response);
                }
            }
            
            // Sanitizar respuesta si contiene HTML
            if (isset($response['response'])) {
                $response['response'] = Validator::sanitizeHtml($response['response']);
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Agregar metadata
            $response['_meta'] = [
                'duration_ms' => $duration,
                'timestamp' => date('c'),
                'model' => $model
            ];
            
            // Log exitoso
            $this->logger->info('Request successful', [
                'model' => $model,
                'duration_ms' => $duration,
                'prompt_length' => strlen($prompt),
                'response_length' => isset($response['response']) ? strlen($response['response']) : 0
            ]);
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
            
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            $this->logger->warning('Validation error', ['error' => $e->getMessage()]);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0;
            
            // Log error
            $this->logger->error('Request failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => $duration
            ]);
            
            // En desarrollo, mostrar más detalles del error
            $errorMessage = $e->getMessage();
            if (defined('DEBUG') && DEBUG) {
                $errorMessage .= ' | Archivo: ' . $e->getFile() . ' | Línea: ' . $e->getLine();
            }
            echo json_encode(['error' => 'Error interno: ' . $errorMessage], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
