<?php

/**
 * Pantalla para mostrar el formulario de configuraciones
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';
require_once $basePath . '/clases/cifrado.php';
require_once __DIR__ . '/../clases/adminFunciones.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
try {
    $datos = $db->select('configuracion', '*', []);
    
    $config = [];
    
    if (is_array($datos) && !empty($datos)) {
        foreach ($datos as $dato) {
            if (isset($dato['nombre']) && isset($dato['valor'])) {
                $config[$dato['nombre']] = $dato['valor'];
            }
        }
    }
} catch (Exception $e) {
    error_log('Error al cargar configuración: ' . $e->getMessage());
    $config = [];
} catch (\Throwable $e) {
    error_log('Error al cargar configuración (Throwable): ' . $e->getMessage());
    $config = [];
}

// Valores por defecto para evitar errores
$configDefaults = [
    'tienda_nombre' => '',
    'tienda_telefono' => '',
    'tienda_moneda' => 'S/',
    'correo_smtp' => '',
    'correo_puerto' => '',
    'correo_email' => '',
    'correo_password' => '',
    'paypal_cliente' => '',
    'paypal_moneda' => '',
    'mp_token' => '',
    'mp_clave' => ''
];

// Combinar con valores por defecto
$config = array_merge($configDefaults, $config);

require '../header.php';

// Mostrar mensaje de éxito si se guardó correctamente
$success = isset($_GET['success']) && $_GET['success'] == '1';
$errors = [];

if ($success) {
    $errors = []; // No hay errores, pero usamos la función para mostrar mensaje de éxito
}

?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Configuración</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>¡Éxito!</strong> La configuración se ha guardado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php mostrarMensajes($errors); ?>

        <form action="<?php echo ADMIN_URL; ?>configuracion/guarda.php" method="post">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-pane" type="button" role="tab" aria-controls="general-tab-pane" aria-selected="true">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-tab-pane" type="button" role="tab" aria-controls="email-tab-pane" aria-selected="false">Correo electrónico</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="paypal-tab" data-bs-toggle="tab" data-bs-target="#paypal-tab-pane" type="button" role="tab" aria-controls="paypal-tab-pane" aria-selected="false">Paypal</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mp-tab" data-bs-toggle="tab" data-bs-target="#mp-tab-pane" type="button" role="tab" aria-controls="mp-tab-pane" aria-selected="false">Mercado Pago</button>
                </li>
            </ul>

            <div class="tab-content mt-4" id="myTabContent">

                <!-- Tab General -->
                <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="nombre">Nombre</label>
                            <input class="form-control" type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($config['tienda_nombre'] ?? ''); ?>">
                        </div>

                        <div class="col-6">
                            <label for="telefono">Teléfono</label>
                            <input class="form-control" type="text" name="telefono" id="telefono" value="<?php echo htmlspecialchars($config['tienda_telefono'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="moneda">Moneda</label>
                            <input class="form-control" type="text" name="moneda" id="moneda" value="<?php echo htmlspecialchars($config['tienda_moneda'] ?? 'S/'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab Email -->
                <div class="tab-pane fade" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="smtp">SMTP</label>
                            <input class="form-control" type="text" name="smtp" id="smtp" value="<?php echo htmlspecialchars($config['correo_smtp'] ?? ''); ?>">
                        </div>

                        <div class="col-6">
                            <label for="puerto">Puerto</label>
                            <input class="form-control" type="text" name="puerto" id="puerto" value="<?php echo htmlspecialchars($config['correo_puerto'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="email">Correo</label>
                            <input class="form-control" type="email" name="email" id="email" value="<?php echo htmlspecialchars($config['correo_email'] ?? ''); ?>">
                        </div>

                        <div class="col-6">
                            <label for="password">Contraseña</label>
                            <input class="form-control" type="password" name="password" id="password" placeholder="Dejar vacío para mantener la contraseña actual">
                            <small class="form-text text-muted">Dejar vacío si no deseas cambiar la contraseña</small>
                        </div>
                    </div>
                </div>

                <!-- Tab Paypal -->
                <div class="tab-pane fade" id="paypal-tab-pane" role="tabpanel" aria-labelledby="paypal-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-9">
                            <label for="paypal_cliente">Cliente ID</label>
                            <input class="form-control" type="text" name="paypal_cliente" id="paypal_cliente" value="<?php echo htmlspecialchars($config['paypal_cliente'] ?? ''); ?>">
                        </div>

                        <div class="col-3">
                            <label for="paypal_moneda">Moneda</label>
                            <input class="form-control" type="text" name="paypal_moneda" id="paypal_moneda" value="<?php echo htmlspecialchars($config['paypal_moneda'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab MercadoPago -->
                <div class="tab-pane fade" id="mp-tab-pane" role="tabpanel" aria-labelledby="mp-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="mp_token">Token</label>
                            <input class="form-control" type="text" name="mp_token" id="mp_token" value="<?php echo htmlspecialchars($config['mp_token'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="mp_clave">Clave pública</label>
                            <input class="form-control" type="text" name="mp_clave" id="mp_clave" value="<?php echo htmlspecialchars($config['mp_clave'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <div class="col-6">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </div>

        </form>

    </div>
</main>


<?php require '../footer.php'; ?>