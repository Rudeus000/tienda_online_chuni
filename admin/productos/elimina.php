<?php

/**
 * Elimina el registro de producto (Dar de baja)
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
$id = $_POST['id'] ?? '';

if (empty($id)) {
    header('Location: index.php');
    exit;
}

try {
    // Actualizar producto a inactivo usando Supabase
    $db->update('productos', ['activo' => 0], 'id', $id);
    header('Location: index.php?success=eliminado');
} catch (Exception $e) {
    error_log('Error al eliminar producto: ' . $e->getMessage());
    header('Location: index.php?error=eliminar');
}
exit;
