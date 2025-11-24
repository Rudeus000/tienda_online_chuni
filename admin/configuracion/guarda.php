<?php

/**
 * Garda las configuraciones
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}


// Cargar primero supabase_config.php que maneja la sesión
require_once $basePath . '/config/supabase_config.php';
require_once $basePath . '/clases/cifrado.php';

// Debug temporal: verificar estado de la sesión
// error_log('Session ID: ' . session_id());
// error_log('Session name: ' . session_name());
// error_log('Session status: ' . session_status());
// error_log('Session data: ' . print_r($_SESSION, true));

// Verificar autenticación - la sesión ya debería estar iniciada en supabase_config.php
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    // Guardar en log para depuración
    error_log('Sesión no válida en guarda.php. Session ID: ' . session_id() . ', User type: ' . ($_SESSION['user_type'] ?? 'no definido'));
    header('Location: ' . ADMIN_URL . 'index.php?error=sesion_expirada');
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . 'configuracion/index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
$nombre = $_POST['nombre'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$moneda = $_POST['moneda'] ?? '';

$smtp = $_POST['smtp'] ?? '';
$puerto = $_POST['puerto'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$paypal_cliente = $_POST['paypal_cliente'] ?? '';
$paypal_moneda = $_POST['paypal_moneda'] ?? '';

$mp_token = $_POST['mp_token'] ?? '';
$mp_clave = $_POST['mp_clave'] ?? '';

// Obtener password actual
$configPassword = $db->selectOne('configuracion', '*', ['nombre' => 'correo_password']);
$passwordBd = $configPassword['valor'] ?? '';

// Actualizar configuraciones
$configuraciones = [
    'tienda_nombre' => $nombre,
    'tienda_telefono' => $telefono,
    'tienda_moneda' => $moneda,
    'correo_smtp' => $smtp,
    'correo_puerto' => $puerto,
    'correo_email' => $email,
    'paypal_cliente' => $paypal_cliente,
    'paypal_moneda' => $paypal_moneda,
    'mp_token' => $mp_token,
    'mp_clave' => $mp_clave
];

foreach ($configuraciones as $nombreConfig => $valor) {
    $configItem = $db->selectOne('configuracion', '*', ['nombre' => $nombreConfig]);
    if ($configItem) {
        $db->update('configuracion', ['valor' => $valor], 'id', $configItem['id']);
    }
}

// Manejar la contraseña del correo
// Si el password recibido es diferente al de la BD, significa que cambió
// Si es igual, no hacer nada (mantener el cifrado actual)
if (!empty($password)) {
    // Intentar descifrar el password de la BD para comparar
    // Si no puede descifrarse, significa que es texto plano o está cifrado diferente
    try {
        $passwordDescifrado = descifrar($passwordBd, ['key' => KEY_CIFRADO, 'method' => METODO_CIFRADO]);
    } catch (Exception $e) {
        $passwordDescifrado = '';
    }
    
    // Si el password recibido es diferente al descifrado, o si no se pudo descifrar
    // significa que se ingresó un nuevo password
    if ($passwordDescifrado !== $password && $passwordBd !== $password) {
        // Cifrar y actualizar solo si es diferente
        $passwordCifrado = cifrado($password, ['key' => KEY_CIFRADO, 'method' => METODO_CIFRADO]);
        $configPasswordItem = $db->selectOne('configuracion', '*', ['nombre' => 'correo_password']);
        if ($configPasswordItem) {
            $db->update('configuracion', ['valor' => $passwordCifrado], 'id', $configPasswordItem['id']);
        }
    }
}

// Redirigir con mensaje de éxito
header('Location: index.php?success=1');
exit;