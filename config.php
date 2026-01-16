<?php
declare(strict_types=1);

require_once __DIR__ . '/modelo/EnvLoader.php';

// Cargar variables de entorno
EnvLoader::load();

/**
 * Configuración del sistema
 */
class Config {
    // API Configuration
    public static function API_URL(): string {
        $url = EnvLoader::get('API_URL', 'http://46.4.122.18:11434/api/generate');
        // Asegurar que siempre sea string y no esté vacío
        if (!is_string($url) || empty(trim($url))) {
            return 'http://46.4.122.18:11434/api/generate';
        }
        return trim($url);
    }
    
    public static function DEFAULT_MODEL(): string {
        return EnvLoader::get('DEFAULT_MODEL', 'llama2-uncensored');
    }
    
    public static function DEFAULT_TEMPERATURE(): float {
        return (float) EnvLoader::get('DEFAULT_TEMPERATURE', 0.3);
    }
    
    public static function DEFAULT_STREAM(): bool {
        return EnvLoader::get('DEFAULT_STREAM', 'false') === 'true';
    }
    
    // Security
    public static function MAX_PROMPT_LENGTH(): int {
        return (int) EnvLoader::get('MAX_PROMPT_LENGTH', 5000);
    }
    
    public static function MAX_SYSTEM_LENGTH(): int {
        return (int) EnvLoader::get('MAX_SYSTEM_LENGTH', 1000);
    }
    
    public static function MAX_INPUT_LENGTH(): int {
        return (int) EnvLoader::get('MAX_INPUT_LENGTH', 10000);
    }
    
    // Timeouts
    public static function CURL_TIMEOUT(): int {
        $timeout = EnvLoader::get('CURL_TIMEOUT', 120);
        $timeout = is_numeric($timeout) ? (int) $timeout : 120;
        return $timeout > 0 ? $timeout : 120;
    }
    
    // Allowed HTML tags for markdown rendering (sanitized)
    public const ALLOWED_HTML_TAGS = '<p><br><strong><em><code><pre><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a>';
    
    // Debug mode
    public static function DEBUG(): bool {
        return EnvLoader::get('DEBUG', 'true') === 'true';
    }
}

// Definir constante global para compatibilidad
if (!defined('DEBUG')) {
    define('DEBUG', Config::DEBUG());
}
