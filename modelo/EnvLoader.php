<?php
declare(strict_types=1);

/**
 * Cargador de variables de entorno desde archivo .env
 */
class EnvLoader {
    private static bool $loaded = false;
    
    /**
     * Carga las variables de entorno desde .env
     */
    public static function load(string $envFile = null): void {
        if (self::$loaded) {
            return;
        }
        
        $envFile = $envFile ?? __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            return; // No hay archivo .env, usar valores por defecto
        }
        
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return; // Error al leer el archivo
        }
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parsear línea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Solo definir si no existe
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtiene una variable de entorno
     */
    public static function get(string $key, $default = null) {
        self::load();
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? null;
        }
        // Si el valor es null o string vacío, devolver el default
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}
