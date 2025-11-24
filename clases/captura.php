<?php

/**
 * Script para capturar detalles de pago de Paypal
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/config/supabase_config.php';

// $db y $con ya están disponibles desde supabase_config.php

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (is_array($datos)) {
    try {
        $idCliente = $_SESSION['user_cliente'] ?? null;
    
    if (!$idCliente) {
        http_response_code(400);
        exit;
    }
    
    $cliente = $db->selectOne('clientes', 'email', ['id' => $idCliente, 'estatus' => 1]);
    
    if (!$cliente || empty($cliente['email'])) {
        http_response_code(400);
        exit;
    }
    
    $status = $datos['details']['status'] ?? 'pending';
    $fechaPayPal = $datos['details']['update_time'] ?? date('c');
    
    // Convertir fecha de PayPal a zona horaria de Perú
    // PayPal devuelve fechas en formato ISO 8601 (UTC)
    $fechaObj = new DateTime($fechaPayPal, new DateTimeZone('UTC'));
    $fechaObj->setTimezone(new DateTimeZone('America/Lima'));
    $time = $fechaObj->format('Y-m-d H:i:s');
    
    $email = $cliente['email'];
    $monto = floatval($datos['details']['purchase_units'][0]['amount']['value'] ?? 0);
    $idTransaccion = $datos['details']['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

    // Registrar la compra usando Supabase
    $datosCompra = [
        'fecha' => $time,
        'status' => $status,
        'email' => $email,
        'id_cliente' => intval($idCliente),
        'total' => $monto,
        'id_transaccion' => $idTransaccion,
        'medio_pago' => 'paypal'
    ];
    
    $compraInsertada = $db->insert('compra', $datosCompra);
    
    if (!$compraInsertada || !isset($compraInsertada['id'])) {
        http_response_code(500);
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
                    error_log('Error al procesar producto en captura: ' . $e->getMessage());
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
    } catch (Exception $e) {
        error_log('Error en captura: ' . $e->getMessage());
        http_response_code(500);
    }
}
