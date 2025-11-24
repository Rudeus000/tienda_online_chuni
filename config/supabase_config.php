<?php

/**
 * Configuración para Supabase
 */

// Importaciones deben ir antes de cualquier código ejecutable
use App\Database;
use App\StorageManager;

// Configurar zona horaria de Perú ANTES de cualquier otra operación
date_default_timezone_set('America/Lima');

// Suprimir warnings de deprecación (PHP 8.4)
// Estos warnings son de librerías externas (Guzzle, Dotenv) que aún no están completamente actualizadas para PHP 8.4
// No afectan la funcionalidad, pero generan mucho ruido en los logs
// E_STRICT está deprecado en PHP 8.4, usar solo E_DEPRECATED
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0'); // No mostrar errores en producción
ini_set('log_errors', '1'); // Pero sí loggearlos

// Cargar el autoload de Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Cargar variables de entorno (solo si el archivo .env existe, si no usar variables del sistema)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Inicializar la base de datos

// Configuración del sistema
// Detectar automáticamente la URL base si no está en .env
if (isset($_ENV['SITE_URL'])) {
    define('SITE_URL', $_ENV['SITE_URL']);
} else {
    // Detectar automáticamente desde el servidor
    // Mejorar detección de HTTPS para servicios en la nube (Railway, Render, etc.)
    $protocol = 'http';
    
    // Verificar múltiples indicadores de HTTPS
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        // Detectar Railway, Render y otros servicios en la nube
        (isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'railway.app') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'render.com') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'herokuapp.com') !== false
        ))
    ) {
        $protocol = 'https';
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = str_replace('\\', '/', $scriptPath);
    if ($basePath !== '/' && $basePath !== '') {
        $basePath = rtrim($basePath, '/') . '/';
    } else {
        $basePath = '/';
    }
    define('SITE_URL', $protocol . '://' . $host . $basePath);
}
define('ADMIN_URL', SITE_URL . 'admin/');
define('KEY_CIFRADO', $_ENV['KEY_CIFRADO'] ?? 'ABCD.1234-');
define('METODO_CIFRADO', 'aes-128-cbc');

// Inicializar la conexión a Supabase
$db = Database::getInstance();
$con = $db->conectar();

// Obtener configuración de la base de datos
$config = [];
try {
    $configData = $db->select('configuracion');
    foreach ($configData as $item) {
        $config[$item['nombre']] = $item['valor'];
    }
} catch (Exception $e) {
    error_log('Error al cargar configuración: ' . $e->getMessage());
}

// Definir constantes de configuración
if (!defined('MONEDA')) {
    define('MONEDA', $config['tienda_moneda'] ?? 'S/');
}

// Definir constantes de PayPal
if (!defined('CLIENT_ID')) {
    define('CLIENT_ID', $config['paypal_cliente'] ?? '');
}
if (!defined('CURRENCY')) {
    // Validar código de moneda (debe ser de 3 letras ISO 4217)
    $paypal_moneda = strtoupper(trim($config['paypal_moneda'] ?? 'USD'));
    
    // Lista de códigos de moneda válidos más comunes para PayPal
    $monedas_validas = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'SEK', 'NZD',
        'MXN', 'SGD', 'HKD', 'NOK', 'TRY', 'RUB', 'INR', 'BRL', 'ZAR', 'DKK',
        'PLN', 'TWD', 'THB', 'MYR', 'PHP', 'CZK', 'HUF', 'ILS', 'CLP', 'PKR',
        'BGN', 'RON', 'COP', 'ARS', 'VND', 'UAH', 'AED', 'SAR', 'PEN', 'EGP'
    ];
    
    // Si no es válido (no es de 3 letras o no está en la lista de monedas válidas), usar USD por defecto
    if (!preg_match('/^[A-Z]{3}$/', $paypal_moneda) || !in_array($paypal_moneda, $monedas_validas)) {
        error_log('Código de moneda PayPal inválido: "' . ($config['paypal_moneda'] ?? '') . '". Usando USD por defecto.');
        $paypal_moneda = 'USD';
    }
    define('CURRENCY', $paypal_moneda);
}

// Definir constantes de Mercado Pago
if (!defined('TOKEN_MP')) {
    define('TOKEN_MP', $config['mp_token'] ?? '');
}
if (!defined('PUBLIC_KEY_MP')) {
    define('PUBLIC_KEY_MP', $config['mp_clave'] ?? '');
}
if (!defined('LOCALE_MP')) {
    define('LOCALE_MP', 'es-MX');
}

// Definir constantes para envío de correo electrónico
if (!defined('MAIL_HOST')) {
    if (!function_exists('descifrar')) {
        require_once dirname(__DIR__) . '/clases/cifrado.php';
    }
    $passwordEmail = $config['correo_password'] ?? '';
    $passwordDescifrado = '';
    if (!empty($passwordEmail)) {
        try {
            $passwordDescifrado = descifrar($passwordEmail, ['key' => KEY_CIFRADO, 'method' => METODO_CIFRADO]);
        } catch (Exception $e) {
            error_log('Error al descifrar contraseña de correo: ' . $e->getMessage());
            $passwordDescifrado = $passwordEmail; // Si no se puede descifrar, usar el valor original
        } catch (\Throwable $e) {
            error_log('Error al descifrar contraseña de correo (Throwable): ' . $e->getMessage());
            $passwordDescifrado = $passwordEmail;
        }
    }
    
    define('MAIL_HOST', $config['correo_smtp'] ?? '');
    define('MAIL_USER', $config['correo_email'] ?? '');
    define('MAIL_PASS', $passwordDescifrado);
    define('MAIL_PORT', $config['correo_puerto'] ?? 465);
}

// Inicializar el manejador de almacenamiento (solo si existe la clase)
try {
    $storage = new StorageManager();
} catch (\Throwable $e) {
    error_log('Error al inicializar StorageManager: ' . $e->getMessage());
    $storage = null;
}

// Función para mantener compatibilidad con el código existente
function getDbConnection() {
    return Database::getInstance()->conectar();
}

// Función para obtener la configuración
function getConfig($key, $default = '') {
    global $config;
    return $config[$key] ?? $default;
}

// Inicializar sesión para tienda
if (session_status() === PHP_SESSION_NONE) {
    session_name('ecommerce_session');
    
    // Configurar parámetros de la cookie de sesión para mejor compatibilidad
    $cookieParams = session_get_cookie_params();
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    // PHP 7.3+ soporta array asociativo con 'samesite'
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax' // Permite que las cookies se envíen con POST desde el mismo sitio
        ]);
    } else {
        // Para versiones anteriores de PHP
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            $isSecure,
            true // httponly
        );
    }
    
    session_start();
}

// Inicializar contador de carrito
$num_cart = 0;
if (isset($_SESSION['carrito']['productos'])) {
    $num_cart = count($_SESSION['carrito']['productos']);
}