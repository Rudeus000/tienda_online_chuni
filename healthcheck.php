<?php
/**
 * Healthcheck endpoint para Railway
 * Verifica que la aplicación esté funcionando correctamente
 * Este endpoint NO carga Supabase para responder rápidamente
 */

// Configurar headers antes de cualquier salida
header('Content-Type: application/json');
http_response_code(200);

// Respuesta simple para el healthcheck
// NO cargar supabase_config.php aquí para evitar errores de inicialización
echo json_encode([
    'status' => 'ok',
    'service' => 'tienda-online',
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get() ?: 'UTC',
    'php_version' => PHP_VERSION
]);
exit;

