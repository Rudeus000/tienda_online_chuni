<?php

/**
 * Script para capturar detalles de pago de Mercado Pago
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/config/supabase_config.php';

// $db y $con ya están disponibles desde supabase_config.php

$idTransaccion = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

if ($idTransaccion != '') {
    try {
        // Obtener fecha actual en zona horaria de Perú (ya configurada en supabase_config.php)
        $fecha = date("Y-m-d H:i:s");
        $monto = isset($_SESSION['carrito']['total']) ? $_SESSION['carrito']['total'] : 0;
        $idCliente = $_SESSION['user_cliente'] ?? null;
        
        if (!$idCliente) {
            header("Location: " . SITE_URL . "login.php");
            exit;
        }
        
        $cliente = $db->selectOne('clientes', 'email', ['id' => $idCliente, 'estatus' => 1]);
        
        if (!$cliente || empty($cliente['email'])) {
            header("Location: " . SITE_URL . "login.php");
            exit;
        }
        
        $email = $cliente['email'];
        
        // Registrar la compra usando Supabase
        $datosCompra = [
            'fecha' => $fecha,
            'status' => $status,
            'email' => $email,
            'id_cliente' => intval($idCliente),
            'total' => floatval($monto),
            'id_transaccion' => $idTransaccion,
            'medio_pago' => 'MP'
        ];
        
        $compraInsertada = $db->insert('compra', $datosCompra);
        
        if (!$compraInsertada || !isset($compraInsertada['id'])) {
            header("Location: " . SITE_URL . "pago.php?error=1");
            exit;
        }
        
        $id = $compraInsertada['id'];

        $productos = isset($_SESSION['carrito']['productos']) ? $_SESSION['carrito']['productos'] : null;

        if ($productos != null) {
            foreach ($productos as $clave => $cantidad) {
                try {
                    $producto = $db->selectOne('productos', 'id, nombre, precio, descuento', ['id' => $clave, 'activo' => 1]);
                    
                    if ($producto) {
                        $precio = floatval($producto['precio']);
                        $descuento = floatval($producto['descuento']);
                        $precio_desc = $precio - (($precio * $descuento) / 100);
                        
                        // Insertar detalle de compra
                        $detalleCompra = [
                            'id_compra' => $id,
                            'id_producto' => intval($producto['id']),
                            'nombre' => $producto['nombre'],
                            'cantidad' => intval($cantidad),
                            'precio' => $precio_desc
                        ];
                        
                        $db->insert('detalle_compra', $detalleCompra);
                        
                        // Restar stock
                        $productoActual = $db->selectOne('productos', 'stock', ['id' => $producto['id']]);
                        if ($productoActual && isset($productoActual['stock'])) {
                            $nuevoStock = max(0, intval($productoActual['stock']) - intval($cantidad));
                            $db->update('productos', ['stock' => $nuevoStock], 'id', $producto['id']);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error al procesar producto en captura_mp: ' . $e->getMessage());
                }
            }

            $asunto = "Detalles de su pedido - Tienda online";
            $cuerpo = "<h4>Gracias por su compra</h4>";
            $cuerpo .= '<p>El ID de su compra es: <b>' . $idTransaccion . '</b></p>';

            require 'Mailer.php';
            $mailer = new Mailer();
            $mailer->enviarEmail($email, $asunto, $cuerpo);
        }

        unset($_SESSION['carrito']);
        header("Location: " . SITE_URL . "completado.php?key=" . $idTransaccion);
        exit;
    } catch (Exception $e) {
        error_log('Error en captura_mp: ' . $e->getMessage());
        header("Location: " . SITE_URL . "pago.php?error=1");
        exit;
    }
}
