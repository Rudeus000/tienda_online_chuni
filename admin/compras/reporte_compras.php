<?php

/**
 * Genera reporte de compra en PDF usando la biblioteca FPDF
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}

require_once $basePath . '/config/supabase_config.php';
require_once $basePath . '/admin/fpdf/plantilla_reporte_compra.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php

function ordenarFecha($fecha)
{
    $arreglo = explode("-", $fecha);
    return $arreglo[2] . '/' . $arreglo[1] . '/' . $arreglo[0];
}

function traducirStatus($status)
{
    $traducciones = [
        'COMPLETED' => 'Completado',
        'approved' => 'Completado',
        'pending' => 'Pendiente',
        'failed' => 'Fallido',
        'cancelled' => 'Cancelado'
    ];
    return $traducciones[$status] ?? $status;
}

function traducirMedioPago($medio)
{
    $traducciones = [
        'paypal' => 'PayPal',
        'MP' => 'Mercado Pago',
        'YAPE' => 'Yape'
    ];
    return $traducciones[$medio] ?? $medio;
}

$fechaIni = $_POST['fecha_ini'] ?? '';
$fechaFin = $_POST['fecha_fin'] ?? '';

if (empty($fechaIni) || empty($fechaFin)) {
    header('Location: ' . ADMIN_URL . 'compras/index.php?error=fechas_requeridas');
    exit;
}

try {
    // Obtener todas las compras y filtrarlas por fecha
    $todasCompras = $db->select('compra');
    $clientes = $db->select('clientes');
    
    // Crear array asociativo de clientes por ID
    $clientesById = [];
    foreach ($clientes as $cliente) {
        $clientesById[$cliente['id']] = $cliente;
    }
    
    $compras = [];
    foreach ($todasCompras as $row) {
        $fechaCompra = date('Y-m-d', strtotime($row['fecha']));
        
        // Filtrar por rango de fechas
        if ($fechaCompra >= $fechaIni && $fechaCompra <= $fechaFin) {
            // Obtener cliente
            $clienteId = $row['id_cliente'] ?? null;
            $cliente = $clientesById[$clienteId] ?? null;
            $nombreCliente = $cliente ? trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) : 'Sin nombre';
            
            // Formatear fecha y hora en zona horaria de Perú
            $fechaObj = new DateTime($row['fecha'], new DateTimeZone('UTC'));
            $fechaObj->setTimezone(new DateTimeZone('America/Lima'));
            $fechaHora = $fechaObj->format('d/m/Y H:i');
            
            // Obtener detalles de productos de esta compra
            $detalles = $db->select('detalle_compra', '*', ['id_compra' => $row['id']]);
            $productos = [];
            
            foreach ($detalles as $detalle) {
                $productoId = $detalle['id_producto'] ?? null;
                $producto = null;
                if ($productoId) {
                    $producto = $db->selectOne('productos', 'precio, descuento', ['id' => $productoId]);
                }
                
                $productos[] = [
                    'nombre' => $detalle['nombre'] ?? '',
                    'cantidad' => $detalle['cantidad'] ?? 0,
                    'precio' => $detalle['precio'] ?? 0,
                    'precio_original' => $producto['precio'] ?? $detalle['precio'] ?? 0,
                    'descuento' => $producto['descuento'] ?? 0
                ];
            }
            
            $compras[] = [
                'fechaHora' => $fechaHora,
                'id_transaccion' => $row['id_transaccion'] ?? '',
                'status' => traducirStatus($row['status'] ?? ''),
                'cliente' => $nombreCliente,
                'total' => number_format($row['total'] ?? 0, 2, '.', ','),
                'medio_pago' => traducirMedioPago($row['medio_pago'] ?? ''),
                'productos' => $productos
            ];
        }
    }
    
    // Ordenar por fecha
    usort($compras, function($a, $b) {
        return strcmp($a['fechaHora'], $b['fechaHora']);
    });
} catch (Exception $e) {
    error_log('Error al generar reporte: ' . $e->getMessage());
    header('Location: ' . ADMIN_URL . 'compras/index.php?error=generar_reporte');
    exit;
}

$datos = [
    'fechaIni' => ordenarFecha($fechaIni),
    'fechaFin' => ordenarFecha($fechaFin),
    'compras' => $compras
];

// Creación del objeto de la clase heredada
$pdf = new PDF('P', 'mm', 'Letter', $datos);
$pdf->AliasNbPages();
$pdf->AddPage();

foreach ($compras as $compra) {
    // Verificar si hay espacio para una nueva compra, si no, nueva página
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
    }
    
    // Información de la compra - Header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(0, 7, mb_convert_encoding('Compra #' . $compra['id_transaccion'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', true);
    
    // Información de la compra - Detalles (ligeramente más grande)
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell(36, 6, mb_convert_encoding('Fecha: ' . $compra['fechaHora'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
    $pdf->Cell(48, 6, mb_convert_encoding('Cliente: ' . $compra['cliente'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
    $pdf->Cell(24, 6, mb_convert_encoding('Estado: ' . $compra['status'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(32, 6, mb_convert_encoding('Total: S/ ' . $compra['total'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
    $pdf->Cell(36, 6, mb_convert_encoding('Pago: ' . $compra['medio_pago'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
    
    // Encabezado de productos (ligeramente más grande - total 176mm)
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 6, mb_convert_encoding('Producto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(16, 6, mb_convert_encoding('Cant.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(26, 6, mb_convert_encoding('P. Unit.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(20, 6, mb_convert_encoding('Desc.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(28, 6, mb_convert_encoding('Subtotal', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(26, 6, mb_convert_encoding('Total', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
    
    // Productos
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $total_compra_mostrado = false;
    foreach ($compra['productos'] as $index => $producto) {
        $descuento = $producto['descuento'];
        $precio_original = $producto['precio_original'];
        $precio_final = $producto['precio'];
        $cantidad = $producto['cantidad'];
        $subtotal = $precio_final * $cantidad;
        
        $descuento_texto = $descuento > 0 ? $descuento . '%' : mb_convert_encoding('Sin', 'ISO-8859-1', 'UTF-8');
        
        // Truncar nombre del producto si es muy largo
        $nombre_producto = mb_convert_encoding($producto['nombre'], 'ISO-8859-1', 'UTF-8');
        if (strlen($nombre_producto) > 30) {
            $nombre_producto = substr($nombre_producto, 0, 27) . '...';
        }
        
        // Mostrar total solo en la última fila de productos
        $total_celda = '';
        if ($index === count($compra['productos']) - 1) {
            $total_celda = 'S/ ' . $compra['total'];
            $total_compra_mostrado = true;
        }
        
        $pdf->Cell(60, 6, $nombre_producto, 1, 0, 'L', true);
        $pdf->Cell(16, 6, $cantidad, 1, 0, 'C', true);
        $pdf->Cell(26, 6, 'S/ ' . number_format($precio_final, 2, '.', ','), 1, 0, 'R', true);
        $pdf->Cell(20, 6, $descuento_texto, 1, 0, 'C', true);
        $pdf->Cell(28, 6, 'S/ ' . number_format($subtotal, 2, '.', ','), 1, 0, 'R', true);
        $pdf->Cell(26, 6, $total_celda, 1, 1, 'R', true);
    }
    
    // Si no se mostró el total (solo un producto), mostrarlo en una fila adicional
    if (!$total_compra_mostrado && count($compra['productos']) > 0) {
        $pdf->Cell(60, 6, '', 1, 0, 'L', true);
        $pdf->Cell(16, 6, '', 1, 0, 'C', true);
        $pdf->Cell(26, 6, '', 1, 0, 'R', true);
        $pdf->Cell(20, 6, '', 1, 0, 'C', true);
        $pdf->Cell(28, 6, '', 1, 0, 'R', true);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(26, 6, 'S/ ' . $compra['total'], 1, 1, 'R', true);
        $pdf->SetFont('Arial', '', 7);
    }
    
    $pdf->Ln(5); // Espacio entre compras
}

$pdf->Output();
