<?php

/**
 * Script para capturar confirmación de pago con Yape
 * Limpia el carrito y redirige a la página de productos
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/config/supabase_config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_cliente'])) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

// $db y $con ya están disponibles desde supabase_config.php

// Obtener información del cliente
$idCliente = $_SESSION['user_cliente'] ?? null;

if (!$idCliente) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

try {
    $cliente = $db->selectOne('clientes', 'email', ['id' => $idCliente, 'estatus' => 1]);
    
    if ($cliente && !empty($cliente['email'])) {
        $email = $cliente['email'];
        // Obtener fecha actual en zona horaria de Perú (ya configurada en supabase_config.php)
        $fecha = date("Y-m-d H:i:s");
        $monto = isset($_SESSION['carrito']['total']) ? $_SESSION['carrito']['total'] : 0;
        
        // Generar un ID de transacción único para Yape (máximo 20 caracteres)
        // Formato: YYYYMMDDHHmmssXXX (sin guiones para ahorrar espacio)
        // Ejemplo: Y231123153024456 = 16 caracteres
        $timestamp = date('ymdHis'); // Año de 2 dígitos, mes, día, hora, min, seg
        $random = rand(100, 999); // 3 dígitos aleatorios
        $idTransaccion = 'Y' . $timestamp . $random; // Total: 16 caracteres (Y + 12 dígitos + 3 dígitos)
        $status = 'pending'; // Estado pendiente hasta confirmación manual
        
        // Registrar la compra en la base de datos usando Supabase
        $datosCompra = [
            'fecha' => $fecha,
            'status' => $status,
            'email' => $email,
            'id_cliente' => intval($idCliente),
            'total' => floatval($monto),
            'id_transaccion' => $idTransaccion,
            'medio_pago' => 'YAPE'
        ];
        
        $compraInsertada = $db->insert('compra', $datosCompra);
        
        if ($compraInsertada && isset($compraInsertada['id'])) {
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
                        error_log('Error al procesar producto en captura_yape: ' . $e->getMessage());
                    }
                }
                
                // Enviar email de confirmación
                $asunto = "Confirmación de pago con Yape - Tienda online";
                $cuerpo = "<h4>Gracias por su compra</h4>";
                $cuerpo .= '<p>Su pago con Yape ha sido registrado. El ID de su compra es: <b>' . $idTransaccion . '</b></p>';
                $cuerpo .= '<p>Por favor, envíe el comprobante de pago para confirmar su pedido.</p>';
                
                require 'Mailer.php';
                $mailer = new Mailer();
                $mailer->enviarEmail($email, $asunto, $cuerpo);
            }
            
            // Limpiar el carrito
            unset($_SESSION['carrito']);
            
            // Redirigir a la página principal
            header("Location: " . SITE_URL . "index.php?pago_yape=1");
            exit;
        } else {
            header("Location: " . SITE_URL . "pago.php?error=1");
            exit;
        }
    } else {
        header("Location: " . SITE_URL . "login.php");
        exit;
    }
} catch (Exception $e) {
    error_log('Error en captura_yape: ' . $e->getMessage());
    header("Location: " . SITE_URL . "pago.php?error=1");
    exit;
}

exit;

