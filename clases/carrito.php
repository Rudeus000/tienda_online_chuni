<?php

/**
 * Script para agregar al carrito de compras
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require '../config/supabase_config.php';

$datos['ok'] = false;

if (isset($_POST['id'])) {

    $id = $_POST['id'];
    $cantidad = isset($_POST['cantidad']) ? $_POST['cantidad'] : 1;

    if ($cantidad > 0 && is_numeric($cantidad)) {

        if (isset($_SESSION['carrito']['productos'][$id])) {
            $cantidad += $_SESSION['carrito']['productos'][$id];
        }

        // Usar Supabase en lugar de PDO
        try {
            $result = $con->from('productos')
                ->select('stock')
                ->eq('id', $id)
                ->eq('activo', 1)
                ->single()
                ->execute();
            
            $producto = $result->getData();
            $stock = $producto ? $producto['stock'] : 0;
        } catch (Throwable $e) {
            error_log('Error al obtener stock: ' . $e->getMessage());
            $stock = 0;
        }

        if ($stock >= $cantidad) {
            $datos['ok'] = true;
            $_SESSION['carrito']['productos'][$id] = $cantidad;
            $datos['numero'] = count($_SESSION['carrito']['productos']);
        }
    }
}

echo json_encode($datos);
