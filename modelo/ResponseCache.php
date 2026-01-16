<?php
declare(strict_types=1);

/**
 * Caché de respuestas para mejorar rendimiento
 */
class ResponseCache {
    private string $cacheDir;
    private int $ttl; // Time to live en segundos
    
    public function __construct(int $ttl = 3600) { // 1 hora por defecto
        $this->cacheDir = __DIR__ . '/../storage/cache/';
        $this->ttl = $ttl;
        
        // Crear directorio si no existe
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Obtiene una respuesta del caché
     * 
     * @param string $key Clave única del caché
     * @return array|null Respuesta en caché o null si no existe/expirada
     */
    public function get(string $key): ?array {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data) {
            return null;
        }
        
        // Verificar si expiró
        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            unlink($file);
            return null;
        }
        
        return $data['response'] ?? null;
    }
    
    /**
     * Guarda una respuesta en el caché
     * 
     * @param string $key Clave única del caché
     * @param array $response Respuesta a guardar
     * @param int|null $ttl TTL personalizado (opcional)
     */
    public function set(string $key, array $response, ?int $ttl = null): void {
        $file = $this->getCacheFile($key);
        $ttl = $ttl ?? $this->ttl;
        
        $data = [
            'key' => $key,
            'response' => $response,
            'created_at' => time(),
            'expires_at' => time() + $ttl
        ];
        
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    
    /**
     * Genera una clave única basada en los parámetros
     */
    public static function generateKey(array $params): string {
        // Normalizar parámetros (remover campos que no afectan la respuesta)
        $normalized = $params;
        unset($normalized['stream']); // Stream no afecta el contenido
        unset($normalized['keep_alive']); // Keep alive no afecta el contenido
        
        // Ordenar para consistencia
        ksort($normalized);
        
        return md5(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Limpia el caché expirado
     */
    public function cleanup(): int {
        $files = glob($this->cacheDir . '*.json');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires_at']) && $now > $data['expires_at']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Limpia todo el caché
     */
    public function clear(): void {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Obtiene la ruta del archivo de caché
     */
    private function getCacheFile(string $key): string {
        return $this->cacheDir . md5($key) . '.json';
    }
}
