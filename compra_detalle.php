<?php

/**
 * Pantalla para detalles de compra
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';
require 'clases/clienteFunciones.php';

if (!isset($_SESSION['token'])) {
    header("Location: compras.php");
    exit;
}

$token_session = $_SESSION['token'];
$orden = $_GET['orden'] ?? null;
$token = $_GET['token'] ?? null;

if (empty($orden) || empty($token) || $token != $token_session) {
    header("Location: compras.php");
    exit;
}

// La conexión ya está inicializada en supabase_config.php como $db y $con

try {
    $resultCompra = $con->from('compra')
        ->select('id, id_transaccion, fecha, total')
        ->eq('id_transaccion', $orden)
        ->single()
        ->execute();
    
    $rowCompra = $resultCompra->getData();
    if (!$rowCompra) {
        header("Location: compras.php");
        exit;
    }
    
    $idCompra = $rowCompra['id'];
    // Convertir fecha a zona horaria de Perú
    $fechaObj = new DateTime($rowCompra['fecha'], new DateTimeZone('UTC'));
    $fechaObj->setTimezone(new DateTimeZone('America/Lima'));
    $fecha = $fechaObj->format('d-m-Y H:i');
    
    // Obtener detalles
    $resultDetalle = $con->from('detalle_compra')
        ->select('id, nombre, precio, cantidad')
        ->eq('id_compra', $idCompra)
        ->execute();
    
    $detalles = $resultDetalle->getData() ?? [];
} catch (Throwable $e) {
    error_log('Error al obtener compra: ' . $e->getMessage());
    header("Location: compras.php");
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
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">

            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>Detalle de la compra</strong>
                        </div>
                        <div class="card-body">
                            <p><strong>Fecha: </strong><?php echo $fecha; ?></p>
                            <p><strong>Orden: </strong><?php echo $rowCompra['id_transaccion']; ?></p>
                            <p><strong>Total: </strong><?php echo MONEDA . ' ' . number_format($rowCompra['total'], 2, '.', ','); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                if (empty($detalles)) {
                                    echo '<tr><td colspan="4" class="text-center">No hay detalles disponibles</td></tr>';
                                } else {
                                    foreach ($detalles as $row) {
                                        $precio = $row['precio'];
                                        $cantidad = $row['cantidad'];
                                        $subtotal = $precio * $cantidad;
                                ?>
                                    <tr>
                                        <td><?php echo $row['nombre']; ?></td>
                                        <td><?php echo MONEDA . ' ' . number_format($precio, 2, '.', ','); ?></td>
                                        <td><?php echo $cantidad; ?></td>
                                        <td><?php echo MONEDA . ' ' . number_format($subtotal, 2, '.', ','); ?></td>
                                    </tr>
                                <?php 
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>
</body>

</html>