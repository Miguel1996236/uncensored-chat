<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Servicio para comunicación con la API de Ollama
 */
class ApiService {
    private string $apiUrl;
    private int $timeout;
    
    public function __construct(string $apiUrl = Config::API_URL, int $timeout = Config::CURL_TIMEOUT) {
        $this->apiUrl = $apiUrl;
        $this->timeout = $timeout;
    }
    
    /**
     * Genera una respuesta del modelo
     * 
     * @param array $params Parámetros validados
     * @return array Respuesta de la API
     * @throws Exception Si hay error en la petición
     */
    public function generate(array $params): array {
        $ch = curl_init();
        
        $postData = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData)
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Solo si es necesario para desarrollo
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Error cURL: ' . $error);
        }
        
        if ($httpCode !== 200) {
            // Intentar obtener más información del error
            $errorInfo = '';
            if ($response) {
                $errorInfo = ' - Respuesta: ' . substr(strip_tags($response), 0, 200);
            }
            throw new Exception('Error HTTP ' . $httpCode . $errorInfo);
        }
        
        // Verificar que la respuesta no esté vacía
        if (empty($response)) {
            throw new Exception('La respuesta de la API está vacía');
        }
        
        // Limpiar BOM y espacios al inicio/final
        $response = trim($response);
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response); // Remover BOM UTF-8
        
        // Detectar si es NDJSON (Newline Delimited JSON) - múltiples objetos JSON separados por saltos de línea
        $isNdJson = (substr_count($response, "\n") > 0 && preg_match('/^\{.*\}$/m', $response));
        
        if ($isNdJson) {
            // Procesar formato NDJSON (streaming)
            return $this->processNdJson($response);
        }
        
        // Verificar que comience con { o [ (JSON válido)
        $firstChar = substr(trim($response), 0, 1);
        if ($firstChar !== '{' && $firstChar !== '[') {
            // La respuesta no es JSON, podría ser HTML o texto plano
            $preview = substr($response, 0, 500);
            throw new Exception('La respuesta no es JSON válido. Tipo: ' . ($contentType ?: 'desconocido') . '. Inicio: ' . $preview);
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = json_last_error_msg();
            $errorPreview = substr($response, 0, 500);
            throw new Exception('Error al decodificar JSON: ' . $errorMsg . '. Respuesta recibida: ' . $errorPreview);
        }
        
        return $decoded;
    }
    
    /**
     * Procesa respuesta en formato NDJSON (Newline Delimited JSON)
     * Combina todas las respuestas parciales en una sola respuesta final
     * 
     * @param string $response Respuesta en formato NDJSON
     * @return array Respuesta combinada
     * @throws Exception Si hay error al procesar
     */
    private function processNdJson(string $response): array {
        $lines = explode("\n", $response);
        $combinedResponse = '';
        $finalData = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $decoded = json_decode($line, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Ignorar líneas que no son JSON válido
                continue;
            }
            
            // Acumular la respuesta
            if (isset($decoded['response'])) {
                $combinedResponse .= $decoded['response'];
            }
            
            // Guardar el último objeto completo como base
            $finalData = $decoded;
        }
        
        if ($finalData === null) {
            throw new Exception('No se pudo procesar ninguna línea válida del NDJSON');
        }
        
        // Combinar todas las respuestas parciales en una sola
        $finalData['response'] = $combinedResponse;
        $finalData['done'] = true; // Marcar como completado
        
        return $finalData;
    }
}
