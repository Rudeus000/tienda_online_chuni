<?php

/**
 * Elimina imagen del producto
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}

require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

$productoId = $_POST['productoId'] ?? '';
$nombreArchivo = $_POST['nombreArchivo'] ?? '';

if (empty($productoId) || empty($nombreArchivo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros requeridos']);
    exit;
}

$eliminado = false;

// Intentar eliminar desde Supabase Storage
try {
    global $storage;
    if ($storage !== null) {
        $eliminado = $storage->eliminarImagenProducto($productoId, $nombreArchivo);
    }
} catch (\Throwable $e) {
    error_log('Error al eliminar imagen de Storage: ' . $e->getMessage());
}

// Fallback: eliminar del sistema local si existe
if (!$eliminado) {
    $basePath = dirname(__DIR__, 2);
    if (!file_exists($basePath . '/config/supabase_config.php')) {
        $basePath = dirname(__DIR__);
    }
    $rutaLocal = $basePath . '/images/productos/' . $productoId . '/' . $nombreArchivo;
    if (file_exists($rutaLocal)) {
        $eliminado = unlink($rutaLocal);
    }
}

if ($eliminado) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo eliminar la imagen']);
}
exit;
