<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing index.php access...\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "\n";

// Verificar que los archivos existan
$files = [
    'config.php',
    'modelo/EnvLoader.php',
    'modelo/RateLimiter.php',
    'modelo/Logger.php',
    'modelo/ResponseCache.php',
    'modelo/ApiService.php',
    'modelo/Validator.php',
    'controlador/ChatController.php',
    'js/chat.js',
    'js/features.js',
    'css/styles.css'
];

echo "Checking files:\n";
foreach ($files as $file) {
    $exists = file_exists($file);
    echo ($exists ? "✓" : "✗") . " $file\n";
    if (!$exists) {
        echo "  ERROR: File not found!\n";
    }
}

echo "\nTesting includes:\n";
try {
    require_once 'config.php';
    echo "✓ config.php loaded\n";
} catch (Exception $e) {
    echo "✗ Error loading config.php: " . $e->getMessage() . "\n";
}

echo "\nTest complete!\n";
