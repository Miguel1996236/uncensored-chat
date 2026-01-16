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
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Error cURL: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Error HTTP: ' . $httpCode);
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar respuesta: ' . json_last_error_msg());
        }
        
        return $decoded;
    }
}
