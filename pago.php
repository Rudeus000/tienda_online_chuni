<?php

/**
 * Pantalla para realizar pago
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';

// Forzar validación y corrección del código de moneda PayPal si es necesario
$paypal_currency_valid = 'USD'; // Valor por defecto seguro
if (defined('CURRENCY')) {
    $monedas_validas_paypal = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'SEK', 'NZD',
        'MXN', 'SGD', 'HKD', 'NOK', 'TRY', 'RUB', 'INR', 'BRL', 'ZAR', 'DKK',
        'PLN', 'TWD', 'THB', 'MYR', 'PHP', 'CZK', 'HUF', 'ILS', 'CLP', 'PKR',
        'BGN', 'RON', 'COP', 'ARS', 'VND', 'UAH', 'AED', 'SAR', 'PEN', 'EGP'
    ];
    $currency_check = strtoupper(trim(CURRENCY));
    if (preg_match('/^[A-Z]{3}$/', $currency_check) && in_array($currency_check, $monedas_validas_paypal)) {
        $paypal_currency_valid = $currency_check;
    } else {
        error_log('Código de moneda PayPal inválido detectado en pago.php: "' . CURRENCY . '". Usando USD por defecto.');
    }
}

// SDK de Mercado Pago - NO inicializar aquí para evitar errores SSL
// Se inicializará más adelante cuando realmente se necesite
$mercadopago_ok = false;
$preference = null;
$productos_mp = array();

// Verificar si TOKEN_MP está definido y no está vacío
$token_mp = defined('TOKEN_MP') ? TOKEN_MP : '';
if (empty($token_mp)) {
    $token_mp = $config['mp_token'] ?? '';
}

$productos = isset($_SESSION['carrito']['productos']) ? $_SESSION['carrito']['productos'] : null;

// La conexión ya está inicializada en supabase_config.php como $db y $con

$lista_carrito = array();
$total = 0;

if ($productos != null) {
    foreach ($productos as $clave => $producto) {
        try {
            $result = $con->from('productos')
                ->select('id, nombre, precio, descuento')
                ->eq('id', $clave)
                ->eq('activo', 1)
                ->single()
                ->execute();
            
            $data = extractSupabaseData($result);
            if ($data) {
                $data['cantidad'] = $producto;
                $lista_carrito[] = $data;
            }
        } catch (Throwable $e) {
            error_log('Error al obtener producto: ' . $e->getMessage());
        }
    }
    
    // Calcular el total antes de mostrar el HTML
    foreach ($lista_carrito as $producto) {
        $descuento = $producto['descuento'];
        $precio = $producto['precio'];
        $cantidad = $producto['cantidad'];
        $precio_desc = $precio - (($precio * $descuento) / 100);
        $subtotal = $cantidad * $precio_desc;
        $total += $subtotal;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda en linea</title>

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">

    <?php
    // Usar la variable ya validada al inicio del archivo
    $currency_valid = $paypal_currency_valid ?? 'USD';
    $client_id_valid = defined('CLIENT_ID') && !empty(CLIENT_ID) ? CLIENT_ID : '';
    ?>
    <?php if (!empty($client_id_valid)): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($client_id_valid, ENT_QUOTES); ?>&currency=<?php echo htmlspecialchars($currency_valid, ENT_QUOTES); ?>"></script>
    <?php endif; ?>
    <script src="https://sdk.mercadopago.com/js/v2"></script>

</head>

<body class="d-flex flex-column h-100">

    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">

            <div class="row">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h4>Detalles de pago</h4>
                    <div lcass="row">
                        <div class="col-10">
                            <div id="paypal-button-container"></div>
                        </div>
                    </div>

                    <div lcass="row">
                        <div class="col-10 text-center">
                            <div class="checkout-btn"></div>
                        </div>
                    </div>

                    <!-- Botón para abrir modal de Yape -->
                    <div class="row mt-3">
                        <div class="col-10">
                            <button type="button" class="btn btn-lg w-100" data-bs-toggle="modal" data-bs-target="#modalYape" 
                                    style="background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%); color: white; border: none; border-radius: 10px; font-weight: bold; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <span style="color: #00d4aa; font-size: 16px; font-weight: bold;">S/</span><span style="font-size: 14px;">yape</span> - Pagar con Yape
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 col-md-7 col-sm-12">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($lista_carrito == null) {
                                    echo '<tr><td colspan="5" class="text-center"><b>Lista vacia</b></td></tr>';
                                } else {
                                    foreach ($lista_carrito as $producto) {
                                        $descuento = $producto['descuento'];
                                        $precio = $producto['precio'];
                                        $cantidad = $producto['cantidad'];
                                        $precio_desc = $precio - (($precio * $descuento) / 100);
                                        $subtotal = $cantidad * $precio_desc;

                                        // Almacenar datos para Mercado Pago (se crearán los objetos Item más tarde)
                                        // Esto evita inicializar el SDK de Mercado Pago antes de tiempo
                                        $productos_mp[] = [
                                            'id' => $producto['id'],
                                            'title' => $producto['nombre'],
                                            'quantity' => $cantidad,
                                            'unit_price' => $precio_desc,
                                            'currency_id' => defined('CURRENCY') ? CURRENCY : 'USD'
                                        ];
                                ?>
                                        <tr>
                                            <td><?php echo $producto['nombre']; ?></td>
                                            <td><?php echo $cantidad . ' x ' . MONEDA . '<b>' . number_format($subtotal, 2, '.', ',') . '</b>'; ?></td>
                                        </tr>
                                    <?php } ?>

                                    <tr>
                                        <td colspan="2">
                                            <p class="h3 text-end" id="total"><?php echo MONEDA . number_format($total, 2, '.', ','); ?></p>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Modal de Yape -->
    <div class="modal fade" id="modalYape" tabindex="-1" aria-labelledby="modalYapeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden; border: none;">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);">
                    <h5 class="modal-title text-white" id="modalYapeLabel" style="font-weight: bold;">
                        <span style="color: #00d4aa; font-size: 24px;">S/</span><span style="font-size: 22px;">yape</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" style="background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%); padding: 30px;">
                    <!-- QR Code -->
                    <div class="mb-4 d-flex justify-content-center">
                        <div style="background: white; padding: 20px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                            <img src="<?php echo SITE_URL; ?>images/yape/yape_qr.png" 
                                 alt="QR Code Yape" 
                                 style="width: 250px; height: 250px; border-radius: 5px; display: block;">
                        </div>
                    </div>

                    <!-- Información del destinatario -->
                    <div class="mb-4">
                        <p class="text-white mb-1" style="font-size: 14px; opacity: 0.9;">Nicol Corayma Munoz Espinoza</p>
                        <p class="text-white mb-0" style="font-size: 24px; font-weight: bold;"><?php echo MONEDA . number_format($total, 2, '.', ','); ?></p>
                    </div>

                    <!-- Botón Paga aquí con Yape -->
                    <div class="mb-3">
                        <div style="background-color: #00d4aa; color: white; border: none; border-radius: 25px; font-weight: bold; padding: 14px 20px; text-align: center; font-size: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            Paga aquí con Yape
                        </div>
                    </div>

                    <!-- Botón para enviar comprobante a WhatsApp -->
                    <div class="mb-3">
                        <a href="https://wa.me/51986753486?text=Hola,%20quiero%20enviar%20el%20comprobante%20de%20pago%20de%20mi%20compra%20por%20<?php echo urlencode(MONEDA . number_format($total, 2, '.', ',')); ?>" 
                           target="_blank" 
                           class="btn btn-lg w-100" 
                           style="background-color: rgb(211, 130, 37); color: white; border: none; border-radius: 25px; font-weight: bold; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            <i class="fab fa-whatsapp me-2"></i> Enviar comprobante por WhatsApp
                        </a>
                    </div>

                    <!-- Botón para confirmar pago -->
                    <div>
                        <button onclick="confirmarPagoYape()" 
                                class="btn btn-lg w-100" 
                                style="background-color: rgb(211, 130, 37); color: white; border: none; border-radius: 25px; font-weight: bold; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            Si ya pagaste, presiona aquí
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php

    $_SESSION['carrito']['total'] = $total;

    // Solo guardar preferencia de Mercado Pago si está configurado
    // Intentar inicializar Mercado Pago solo cuando realmente se necesite
    $preference_id = null;
    $public_key_mp = defined('PUBLIC_KEY_MP') ? PUBLIC_KEY_MP : ($config['mp_clave'] ?? '');
    
    if (!empty($token_mp) && !empty($public_key_mp) && !empty($productos_mp)) {
        try {
            // Si no se inicializó antes, intentar inicializar ahora
            if (!$mercadopago_ok || $preference === null) {
                require __DIR__ .  '/vendor/autoload.php';
                
                // Deshabilitar verificación SSL en desarrollo local (SOLO PARA DESARROLLO)
                // Usar un enfoque temporal para desarrollo
                if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
                    // Guardar el valor original de CURLOPT_SSL_VERIFYPEER
                    $original_verify_peer = curl_version() ? true : false;
                }
                
                // Configurar curl para deshabilitar verificación SSL solo en desarrollo local
                // NOTA: Esto es SOLO para desarrollo. En producción NO deshabilitar SSL.
                if ((strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) &&
                    function_exists('curl_setopt_array')) {
                    // Crear un archivo de configuración temporal para curl
                    // O mejor, simplemente hacer que Mercado Pago sea opcional si falla
                }
                
                // Intentar inicializar Mercado Pago - si falla, continuar sin él
                try {
                    MercadoPago\SDK::setAccessToken($token_mp);
                    $preference = new MercadoPago\Preference();
                    $mercadopago_ok = true;
                } catch (\Throwable $e) {
                    // Si falla (por SSL u otra razón), simplemente continuar sin Mercado Pago
                    error_log('Mercado Pago no disponible (error al inicializar): ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    $mercadopago_ok = false;
                    $preference = null;
                    // NO re-lanzar la excepción, simplemente continuar sin Mercado Pago
                }
            }
            
            // Solo continuar con Mercado Pago si se inicializó correctamente
            if ($mercadopago_ok && $preference !== null) {
                // Convertir arrays a objetos Item de Mercado Pago
                $items_mp = [];
                foreach ($productos_mp as $prod_data) {
                    $item = new MercadoPago\Item();
                    $item->id = $prod_data['id'];
                    $item->title = $prod_data['title'];
                    $item->quantity = $prod_data['quantity'];
                    $item->unit_price = $prod_data['unit_price'];
                    $item->currency_id = $prod_data['currency_id'];
                    $items_mp[] = $item;
                }
                
                $preference->items = $items_mp;

                $preference->back_urls = array(
                    "success" => SITE_URL . "/clases/captura_mp.php",
                    "failure" => SITE_URL . "/clases/fallo.php"
                );
                $preference->auto_return = "approved";
                $preference->binary_mode = true;
                $preference->statement_descriptor = "STORE CDP";
                $preference->external_reference = "Reference_1234";
                $preference->save();
                $preference_id = $preference->id ?? null;
            }
        } catch (Exception $e) {
            error_log('Error al crear preferencia de Mercado Pago: ' . $e->getMessage());
            error_log('Error completo: ' . print_r($e, true));
            $mercadopago_ok = false;
            $preference_id = null;
        } catch (\Throwable $e) {
            error_log('Error al crear preferencia de Mercado Pago (Throwable): ' . $e->getMessage());
            error_log('Error completo: ' . print_r($e, true));
            $mercadopago_ok = false;
            $preference_id = null;
        }
    }

    ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

    <script>
        // Validar que PayPal se haya cargado correctamente
        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK no se pudo cargar. Verifica el CLIENT_ID y el código de moneda (CURRENCY) en la configuración.');
            document.getElementById('paypal-button-container').innerHTML = '<div class="alert alert-warning">PayPal no está disponible en este momento. Por favor, usa otro método de pago.</div>';
        } else {
        paypal.Buttons({

            style: {
                color: 'blue',
                shape: 'pill',
                label: 'pay'
            },

            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: <?php echo $total; ?>
                        },
                        description: 'Compra tienda CDP'
                    }]
                });
            },

            onApprove: function(data, actions) {

                let url = 'clases/captura.php';
                actions.order.capture().then(function(details) {

                    let trans = details.purchase_units[0].payments.captures[0].id;
                    return fetch(url, {
                        method: 'post',
                        mode: 'cors',
                        headers: {
                            'content-type': 'application/json'
                        },
                        body: JSON.stringify({
                            details: details
                        })
                    }).then(function(response) {
                        window.location.href = "completado.php?key=" + trans;
                    });
                });
            },

            onCancel: function(data) {
                alert("Cancelo :(");
            }
        }).render('#paypal-button-container');
        } // Fin de la validación de PayPal


        <?php 
        $public_key_mp_display = defined('PUBLIC_KEY_MP') ? PUBLIC_KEY_MP : ($config['mp_clave'] ?? '');
        $locale_mp_display = defined('LOCALE_MP') ? LOCALE_MP : 'es-MX';
        if ($mercadopago_ok && $preference_id !== null && !empty($public_key_mp_display)): 
        ?>
        const mp = new MercadoPago('<?php echo htmlspecialchars($public_key_mp_display, ENT_QUOTES); ?>', {
            locale: '<?php echo htmlspecialchars($locale_mp_display, ENT_QUOTES); ?>'
        });

        // Inicializa el checkout Mercado Pago
        mp.checkout({
            preference: {
                id: '<?php echo $preference_id; ?>'
            },
            render: {
                container: '.checkout-btn', // Indica el nombre de la clase donde se mostrará el botón de pago
                type: 'wallet', // Muestra un botón de pago con la marca Mercado Pago
                label: 'Pagar con Mercado Pago', // Cambia el texto del botón de pago (opcional)
            }
        });
        <?php else: ?>
        // Mercado Pago no está configurado o no está disponible
        if (document.querySelector('.checkout-btn')) {
            document.querySelector('.checkout-btn').innerHTML = '<p class="text-muted">Mercado Pago no está disponible en este momento.</p>';
        }
        <?php endif; ?>

        // Función para confirmar pago con Yape
        function confirmarPagoYape() {
            if (confirm('¿Confirmas que ya realizaste el pago con Yape?')) {
                // Remover el foco del botón activo para evitar problemas de accesibilidad
                if (document.activeElement) {
                    document.activeElement.blur();
                }
                
                // Redirigir directamente - la redirección cierra el modal automáticamente
                // y evita problemas de foco con aria-hidden
                window.location.href = 'clases/captura_yape.php';
            }
        }
        
        // Agregar listener para manejar el cierre del modal y asegurar que el foco se maneje correctamente
        document.addEventListener('DOMContentLoaded', function() {
            var modalYape = document.getElementById('modalYape');
            if (modalYape) {
                // Cuando el modal se está ocultando, remover el foco de cualquier elemento interno
                modalYape.addEventListener('hide.bs.modal', function(event) {
                    var activeElement = document.activeElement;
                    if (activeElement && modalYape.contains(activeElement)) {
                        activeElement.blur();
                    }
                });
                
                // Cuando el modal está completamente oculto, asegurar que no haya elementos con foco
                modalYape.addEventListener('hidden.bs.modal', function(event) {
                    var activeElement = document.activeElement;
                    if (activeElement && modalYape.contains(activeElement)) {
                        // Si algún elemento dentro del modal aún tiene foco, moverlo al body
                        document.body.focus();
                        document.body.blur(); // Remover el foco completamente
                    }
                });
            }
        });
    </script>

</body>

</html>