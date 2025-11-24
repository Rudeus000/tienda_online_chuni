<?php

/**
 * Solicitud para consultar los datos de la compra
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    exit;
}

$orden = $_POST['orden'] ?? null;

if ($orden == null) {
    exit;
}

// $db ya está disponible desde supabase_config.php
$compra = $db->selectOne('compra', '*', ['id_transaccion' => $orden]);

if (!$compra) {
    exit;
}

$idCompra = $compra['id'];

// Obtener cliente
$cliente = $db->selectOne('clientes', '*', ['id' => $compra['id_cliente']]);
$nombreCliente = $cliente ? trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) : 'Sin nombre';

// Convertir fecha a zona horaria de Perú
$fechaObj = new DateTime($compra['fecha'], new DateTimeZone('UTC'));
$fechaObj->setTimezone(new DateTimeZone('America/Lima'));
$fecha = $fechaObj->format('d-m-Y H:i');

// Obtener detalles
$detalles = $db->select('detalle_compra', '*', ['id_compra' => $idCompra]);

$html = '<p><strong>Cliente: </strong>' . htmlspecialchars($nombreCliente) . '</p>';
$html .= '<p><strong>Fecha: </strong>' . $fecha . '</p>';
$html .= '<p><strong>Orden: </strong>' . htmlspecialchars($compra['id_transaccion']) . '</p>';
$html .= '<p><strong>Total: </strong>' . number_format($compra['total'], 2, '.', ',') . '</p>';

$html .= '<table class="table">
<thead>
<tr>
<th>Producto</th>
<th>Precio</th>
<th>Cantidad</th>
<th>Subtotal</th>
</tr>
</thead>';

$html .= '<tbody>';

foreach ($detalles as $row) {
    $precio = floatval($row['precio'] ?? 0);
    $cantidad = intval($row['cantidad'] ?? 0);
    $subtotal = $precio * $cantidad;
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($row['nombre'] ?? '') . '</td>';
    $html .= '<td>' . number_format($precio, 2, '.', ',') . '</td>';
    $html .= '<td>' . $cantidad . '</td>';
    $html .= '<td>' . number_format($subtotal, 2, '.', ',') . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

echo json_encode($html, JSON_UNESCAPED_UNICODE);
