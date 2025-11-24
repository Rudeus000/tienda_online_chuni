<?php

/**
 * Script para actualizar el estado de compras de Yape de pendiente a completado
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$idTransaccion = $_POST['id_transaccion'] ?? null;

if ($idTransaccion == null) {
    echo json_encode(['success' => false, 'message' => 'ID de transacción no proporcionado']);
    exit;
}

// $db ya está disponible desde supabase_config.php
try {
    // Verificar que la compra existe y es de Yape con estado pendiente
    $compra = $db->selectOne('compra', 'id, status, medio_pago', ['id_transaccion' => $idTransaccion]);

    if (!$compra) {
        echo json_encode(['success' => false, 'message' => 'Compra no encontrada']);
        exit;
    }

    if (($compra['medio_pago'] ?? '') != 'YAPE') {
        echo json_encode(['success' => false, 'message' => 'Esta compra no es de Yape']);
        exit;
    }

    if (($compra['status'] ?? '') != 'pending') {
        echo json_encode(['success' => false, 'message' => 'Esta compra ya no está pendiente']);
        exit;
    }

    // Actualizar el estado a 'approved' (completado)
    $resultado = $db->update('compra', ['status' => 'approved'], 'id_transaccion', $idTransaccion);

    if ($resultado !== null) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
} catch (Exception $e) {
    error_log('Error al actualizar estado de Yape: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']);
}

