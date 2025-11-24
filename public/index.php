<?php
/**
 * Punto de entrada principal para Render
 * Redirige todas las peticiones al index.php principal
 */

// Obtener la ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Remover el directorio base si existe
$basePath = dirname($scriptName);
if ($basePath !== '/' && $basePath !== '\\') {
    $requestUri = str_replace($basePath, '', $requestUri);
}

// Si la ruta es solo "/" o está vacía, redirigir al index.php principal
if ($requestUri === '/' || $requestUri === '' || $requestUri === '/index.php') {
    // Obtener la ruta absoluta del index.php principal
    $rootPath = dirname(__DIR__);
    require_once $rootPath . '/index.php';
    exit;
}

// Para otras rutas, intentar servir el archivo directamente
$filePath = __DIR__ . '/..' . $requestUri;

// Si el archivo existe y es un archivo PHP, incluirlo
if (file_exists($filePath) && is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    require_once $filePath;
    exit;
}

// Si es un archivo estático (CSS, JS, imágenes), servirlo
if (file_exists($filePath) && is_file($filePath)) {
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'json' => 'application/json',
    ];
    
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mimeType);
    readfile($filePath);
    exit;
}

// Si no se encuentra, redirigir al index.php principal
require_once dirname(__DIR__) . '/index.php';

