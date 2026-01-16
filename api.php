<?php
declare(strict_types=1);

// Deshabilitar warnings que puedan interferir con JSON
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

// Iniciar buffer de salida para capturar cualquier output inesperado
ob_start();

try {
    require_once __DIR__ . '/controlador/ChatController.php';
    
    $controller = new ChatController();
    $controller->handleRequest();
} catch (Throwable $e) {
    // Limpiar cualquier output previo
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    
    $errorMessage = 'Error fatal: ' . $e->getMessage();
    if (defined('DEBUG') && DEBUG) {
        $errorMessage .= ' | Archivo: ' . $e->getFile() . ' | Línea: ' . $e->getLine();
    }
    
    echo json_encode(['error' => $errorMessage], JSON_UNESCAPED_UNICODE);
    exit;
}
