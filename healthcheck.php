<?php
/**
 * Healthcheck endpoint para Railway
 * Verifica que la aplicación esté funcionando correctamente
 */

// Respuesta simple para el healthcheck
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
]);
exit;

