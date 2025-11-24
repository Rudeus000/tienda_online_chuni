<?php

/**
 * Elimina el registro para categorías (Dar de baja)
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
$id = $_POST['id'];

$db->update('categorias', ['activo' => 0], 'id', $id);

header('Location: index.php');
