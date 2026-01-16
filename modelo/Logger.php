<?php
declare(strict_types=1);

/**
 * Logger estructurado para el sistema
 */
class Logger {
    private string $logDir;
    private string $logFile;
    
    public function __construct(string $logFile = 'app.log') {
        $this->logDir = __DIR__ . '/../storage/logs/';
        $this->logFile = $logFile;
        
        // Crear directorio si no existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Registra un mensaje de log
     * 
     * @param string $level Nivel de log (info, warning, error, debug)
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    public function log(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $ip,
            'method' => $method,
            'uri' => $uri,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        file_put_contents(
            $this->logDir . $this->logFile,
            $logLine,
            FILE_APPEND | LOCK_EX
        );
        
        // También escribir a archivo diario
        $dailyFile = $this->logDir . 'app-' . date('Y-m-d') . '.log';
        file_put_contents($dailyFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log de información
     */
    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log de advertencia
     */
    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Log de error
     */
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log de debug
     */
    public function debug(string $message, array $context = []): void {
        if (defined('DEBUG') && DEBUG) {
            $this->log('debug', $message, $context);
        }
    }
    
    /**
     * Obtiene los últimos logs
     */
    public function getRecentLogs(int $lines = 100): array {
        $file = $this->logDir . $this->logFile;
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        $allLines = explode("\n", trim($content));
        $recentLines = array_slice($allLines, -$lines);
        
        $logs = [];
        foreach ($recentLines as $line) {
            if (empty(trim($line))) continue;
            $decoded = json_decode($line, true);
            if ($decoded) {
                $logs[] = $decoded;
            }
        }
        
        return $logs;
    }
}
