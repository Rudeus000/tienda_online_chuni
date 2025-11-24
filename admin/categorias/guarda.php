<?php

/**
 * Guarda el registro de categorías
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'categorias/index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
$nombre = trim($_POST['nombre']);

$db->insert('categorias', ['nombre' => $nombre, 'activo' => 1]);

header('Location: index.php');
