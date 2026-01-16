<?php
declare(strict_types=1);

/**
 * Rate Limiter para proteger la API contra abuso
 */
class RateLimiter {
    private string $storageDir;
    private int $maxRequests;
    private int $timeWindow; // en segundos
    
    public function __construct(int $maxRequests = 60, int $timeWindow = 60) {
        $this->storageDir = __DIR__ . '/../storage/rate_limit/';
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        
        // Crear directorio si no existe
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Verifica si una IP puede hacer una petición
     * 
     * @param string $ip Dirección IP del cliente
     * @return bool true si puede hacer la petición, false si excedió el límite
     */
    public function checkLimit(string $ip): bool {
        $ip = $this->sanitizeIp($ip);
        $file = $this->storageDir . md5($ip) . '.json';
        
        $now = time();
        $requests = [];
        
        // Cargar requests existentes
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['requests'])) {
                $requests = $data['requests'];
            }
        }
        
        // Filtrar requests fuera de la ventana de tiempo
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });
        
        // Verificar límite
        if (count($requests) >= $this->maxRequests) {
            return false;
        }
        
        // Agregar nueva request
        $requests[] = $now;
        
        // Guardar
        file_put_contents($file, json_encode([
            'ip' => $ip,
            'requests' => array_values($requests),
            'last_update' => $now
        ]));
        
        return true;
    }
    
    /**
     * Obtiene información sobre el rate limit de una IP
     */
    public function getRemainingRequests(string $ip): array {
        $ip = $this->sanitizeIp($ip);
        $file = $this->storageDir . md5($ip) . '.json';
        
        $now = time();
        $requests = [];
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['requests'])) {
                $requests = array_filter($data['requests'], function($timestamp) use ($now) {
                    return ($now - $timestamp) < $this->timeWindow;
                });
            }
        }
        
        return [
            'remaining' => max(0, $this->maxRequests - count($requests)),
            'used' => count($requests),
            'limit' => $this->maxRequests,
            'reset_in' => $this->timeWindow - (count($requests) > 0 ? ($now - min($requests)) : 0)
        ];
    }
    
    /**
     * Limpia archivos antiguos
     */
    public function cleanup(): void {
        $files = glob($this->storageDir . '*.json');
        $now = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['last_update'])) {
                // Eliminar archivos más antiguos que 1 hora
                if (($now - $data['last_update']) > 3600) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Sanitiza la IP
     */
    private function sanitizeIp(string $ip): string {
        // Obtener IP real si está detrás de proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ?: 'unknown';
    }
}
