<?php

/**
 * Activa el registro de usuario
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

// $db ya está disponible desde supabase_config.php
$id = $_POST['id'];

$db->update('usuarios', ['activacion' => 1], 'id', $id);

header('Location: ' . ADMIN_URL . 'usuarios/index.php');
