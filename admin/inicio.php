<?php

require '../config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// $db y $con ya están disponibles desde supabase_config.php
// No necesitamos crear nuevas instancias

function totalDia($db, $fecha)
{
    try {
        $compras = $db->select('compra', '*', []);
        $total = 0;
        
        foreach ($compras as $compra) {
            $fechaCompra = date('Y-m-d', strtotime($compra['fecha']));
            if ($fechaCompra === $fecha && 
                ($compra['status'] === 'COMPLETED' || $compra['status'] === 'approved')) {
                $total += floatval($compra['total'] ?? 0);
            }
        }
        
        return $total;
    } catch (\Throwable $e) {
        error_log('Error en totalDia: ' . $e->getMessage());
        return 0;
    }
}

function productosMasVendidos($db, $fechaInicial, $fechaFinal)
{
    try {
        $compras = $db->select('compra', '*', []);
        $productosVendidos = [];
        
        foreach ($compras as $compra) {
            $fechaCompra = date('Y-m-d', strtotime($compra['fecha']));
            if ($fechaCompra >= $fechaInicial && $fechaCompra <= $fechaFinal) {
                $detalles = $db->select('detalle_compra', '*', ['id_compra' => $compra['id']]);
                
                foreach ($detalles as $detalle) {
                    $idProducto = $detalle['id_producto'] ?? null;
                    $nombre = $detalle['nombre'] ?? 'Sin nombre';
                    $cantidad = intval($detalle['cantidad'] ?? 0);
                    
                    if ($idProducto) {
                        if (!isset($productosVendidos[$idProducto])) {
                            $productosVendidos[$idProducto] = [
                                'nombre' => $nombre,
                                'cantidad' => 0
                            ];
                        }
                        $productosVendidos[$idProducto]['cantidad'] += $cantidad;
                    }
                }
            }
        }
        
        // Ordenar por cantidad descendente y tomar los 5 primeros
        usort($productosVendidos, function($a, $b) {
            return $b['cantidad'] - $a['cantidad'];
        });
        
        return array_slice($productosVendidos, 0, 5);
    } catch (\Throwable $e) {
        error_log('Error en productosMasVendidos: ' . $e->getMessage());
        return [];
    }
}

$hoy = date('Y-m-d');
$lunes = date('Y-m-d', strtotime('monday this week', strtotime($hoy)));
$domingo = date('Y-m-d', strtotime('sunday this week', strtotime($hoy)));

$fechaInicial = new DateTime($lunes);
$fechaFinal = new DateTime($domingo);

$diasVentas = [];

for ($i = $fechaInicial; $i <= $fechaFinal; $i->modify('+1 day')) {
    $diasVentas[] = totalDia($db, $i->format('Y-m-d'));
}

$diasVentas = implode(',', $diasVentas);

$fechaIni = date('Y-m') . '-01';
$ultimoDia = date("d", (mktime(0, 0, 0, date('m') + 1, 1, date('y')) - 1));
$fechaFin = date('Y-m') . '-' . $ultimoDia;

$listaProductos = productosMasVendidos($db, $fechaIni, $fechaFin);
$nombreProductos = [];
$cantidadProductos = [];

foreach ($listaProductos as $producto) {
    $nombreProductos[] = $producto['nombre'];
    $cantidadProductos[] = $producto['cantidad'];
}

$nombreProductos = implode("','", $nombreProductos);
$cantidadProductos = implode(',', $cantidadProductos);

include 'header.php';

?>
<main>
    <div class="container-fluid px-4">
        <h1 class="my-3">Dashboard</h1>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar mr-1"></i>
                        Ventas de la semana
                    </div>
                    <div class="card-body">
                        <canvas id="myChart" width="100%" height="75"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Productos más vendidos del mes
                    </div>
                    <div class="card-body">
                        <canvas id="chart-productos" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const ctx = document.getElementById('myChart');

    let chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'],
            datasets: [{
                labels: ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'],
                data: [<?php echo $diasVentas; ?>],
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    // Pie Chart Example
    const ctxProdctos = document.getElementById('chart-productos');

    let chartProd = new Chart(ctxProdctos, {
        type: 'pie',
        data: {
            labels: ['<?php echo $nombreProductos; ?>'],
            datasets: [{
                data: [<?php echo $cantidadProductos; ?>],
                backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#697bff'],
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            }
        }
    });
</script>

<?php include 'footer.php'; ?>