<?php
declare(strict_types=1);

/**
 * Configuración del sistema
 */
class Config {
    // API Configuration
    public const API_URL = 'http://46.4.122.18:11434/api/generate';
    public const DEFAULT_MODEL = 'llama2-uncensored';
    public const DEFAULT_TEMPERATURE = 0.3;
    public const DEFAULT_STREAM = false;
    
    // Security
    public const MAX_PROMPT_LENGTH = 5000;
    public const MAX_SYSTEM_LENGTH = 1000;
    public const MAX_INPUT_LENGTH = 10000;
    
    // Timeouts
    public const CURL_TIMEOUT = 120; // 2 minutos
    
    // Allowed HTML tags for markdown rendering (sanitized)
    public const ALLOWED_HTML_TAGS = '<p><br><strong><em><code><pre><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a>';
    
    // Debug mode (cambiar a false en producción)
    public const DEBUG = true;
}

// Definir constante global para compatibilidad
if (!defined('DEBUG')) {
    define('DEBUG', Config::DEBUG);
}
