<?php

/**
 * Elimina imagen del producto
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

$urlImagen = $_POST['urlImagen'] ?? '';

if ($urlImagen !== '' && file_exists($urlImagen)) {
    unlink($urlImagen);
}
