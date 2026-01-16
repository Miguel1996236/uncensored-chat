<?php
declare(strict_types=1);

require_once __DIR__ . '/controlador/ChatController.php';

$controller = new ChatController();
$controller->handleRequest();
