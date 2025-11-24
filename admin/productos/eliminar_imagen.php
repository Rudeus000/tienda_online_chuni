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

$urlImagen = $_POST['urlImagen'] ?? '';

if ($urlImagen !== '' && file_exists($urlImagen)) {
    unlink($urlImagen);
}

// Redirigir de vuelta a la página de edición
$producto_id = $_POST['producto_id'] ?? '';
if ($producto_id) {
    header('Location: ' . ADMIN_URL . 'productos/edita.php?id=' . $producto_id);
} else {
    header('Location: ' . ADMIN_URL . 'productos/index.php');
}
exit;
