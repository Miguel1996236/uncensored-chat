<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing configuration...\n\n";

try {
    require_once __DIR__ . '/config.php';
    echo "✓ Config loaded\n";
    
    require_once __DIR__ . '/modelo/EnvLoader.php';
    echo "✓ EnvLoader loaded\n";
    
    require_once __DIR__ . '/modelo/RateLimiter.php';
    echo "✓ RateLimiter loaded\n";
    
    require_once __DIR__ . '/modelo/Logger.php';
    echo "✓ Logger loaded\n";
    
    require_once __DIR__ . '/modelo/ResponseCache.php';
    echo "✓ ResponseCache loaded\n";
    
    require_once __DIR__ . '/modelo/ApiService.php';
    echo "✓ ApiService loaded\n";
    
    require_once __DIR__ . '/modelo/Validator.php';
    echo "✓ Validator loaded\n";
    
    require_once __DIR__ . '/controlador/ChatController.php';
    echo "✓ ChatController loaded\n";
    
    echo "\n✓ All files loaded successfully!\n";
    
    // Test Config methods
    echo "\nTesting Config methods:\n";
    echo "API_URL: " . Config::API_URL() . "\n";
    echo "DEFAULT_MODEL: " . Config::DEFAULT_MODEL() . "\n";
    echo "DEBUG: " . (Config::DEBUG() ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
