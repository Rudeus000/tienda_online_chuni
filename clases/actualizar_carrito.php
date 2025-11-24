<?php

/**
 * Script para actualizar carrito de compas
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require '../config/supabase_config.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? $_POST['id'] : 0;

    if ($action == 'eliminar') {
        $datos['ok'] = eliminar($id);
    } elseif ($action == 'agregar') {
        $cantidad = isset($_POST['cantidad']) ? $_POST['cantidad'] : 0;
        $respuesta = agregar($id, $cantidad);
        if ($respuesta > 0) {
            $_SESSION['carrito']['productos'][$id] = $cantidad;
            $datos['ok'] = true;
        } else {
            $datos['ok'] = false;
            $datos['cantidadAnterior'] = $_SESSION['carrito']['productos'][$id];
        }
        $datos['sub'] = MONEDA . number_format($respuesta, 2, '.', ',');
    } else {
        $datos['ok'] = false;
    }
} else {
    $datos['ok'] = false;
}

echo json_encode($datos);

function eliminar($id)
{
    if ($id > 0) {
        if (isset($_SESSION['carrito']['productos'][$id])) {
            unset($_SESSION['carrito']['productos'][$id]);
            return true;
        }
    } else {
        return false;
    }
}

function agregar($id, $cantidad)
{
    global $con;
    
    if ($id > 0 && $cantidad > 0 && is_numeric($cantidad) && isset($_SESSION['carrito']['productos'][$id])) {

        try {
            $result = $con->from('productos')
                ->select('precio, descuento, stock')
                ->eq('id', $id)
                ->eq('activo', 1)
                ->single()
                ->execute();
            
            $producto = extractSupabaseData($result);
            
            if ($producto) {
                $descuento = $producto['descuento'];
                $precio = $producto['precio'];
                $stock = $producto['stock'];

                if ($stock >= $cantidad) {
                    $precio_desc = $precio - (($precio * $descuento) / 100);
                    return $cantidad * $precio_desc;
                }
            }
        } catch (Throwable $e) {
            error_log('Error al obtener producto: ' . $e->getMessage());
        }
    }
    return 0;
}
