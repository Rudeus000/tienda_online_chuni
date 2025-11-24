<?php

/**
 * Pantalla historial de compras
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';
require 'clases/clienteFunciones.php';

$token = generarToken();
$_SESSION['token'] = $token;

if (!isset($_SESSION['user_cliente'])) {
    header("Location: login.php");
    exit;
}

$idCliente = $_SESSION['user_cliente'];

$compras = [];
try {
    $response = $con->from('compra')
        ->select('id_transaccion, fecha, status, total, medio_pago')
        ->eq('id_cliente', $idCliente)
        ->order('fecha', ['ascending' => false])
        ->execute();

    $compras = $response->getData() ?? [];
} catch (Throwable $e) {
    error_log('Error al obtener compras: ' . $e->getMessage());
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
            <h4>Mis compras</h4>

            <hr>

            <?php if (empty($compras)) { ?>
                <p>No hay compras registradas.</p>
            <?php } ?>

            <?php foreach ($compras as $row) { 
                // Formatear fecha con zona horaria de Perú
                $fechaFormateada = '';
                if (!empty($row['fecha'])) {
                    try {
                        $fechaObj = new DateTime($row['fecha'], new DateTimeZone('UTC'));
                        $fechaObj->setTimezone(new DateTimeZone('America/Lima'));
                        $fechaFormateada = $fechaObj->format('d/m/Y H:i');
                    } catch (Exception $e) {
                        $fechaFormateada = $row['fecha'] ?? '';
                    }
                }
            ?>

                <div class="card mb-2">
                    <div class="card-header">
                        <?php echo $fechaFormateada; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Folio: <?php echo $row['id_transaccion']; ?></h5>
                        <p class="card-text">Total: <?php echo $row['total']; ?></p>
                        <a href="compra_detalle.php?orden=<?php echo $row['id_transaccion']; ?>&token=<?php echo $token; ?>" class="btn btn-primary">Ver compra</a>
                    </div>
                </div>

            <?php } ?>


        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>
</body>

</html>