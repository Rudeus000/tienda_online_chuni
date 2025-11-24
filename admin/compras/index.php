<?php



$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
// Obtener compras y clientes por separado y combinarlos
$compras = $db->select('compra');
$clientes = $db->select('clientes');

// Crear array asociativo de clientes por ID
$clientesById = [];
foreach ($clientes as $cliente) {
    $clientesById[$cliente['id']] = $cliente;
}

// Combinar datos y ordenar por fecha
$comprasData = [];
foreach ($compras as $compra) {
    $clienteId = $compra['id_cliente'] ?? null;
    $cliente = $clientesById[$clienteId] ?? null;
    
    // Formatear fecha con zona horaria de Perú
    $fechaFormateada = '';
    if (!empty($compra['fecha'])) {
        try {
            $fechaObj = new DateTime($compra['fecha'], new DateTimeZone('UTC'));
            $fechaObj->setTimezone(new DateTimeZone('America/Lima'));
            $fechaFormateada = $fechaObj->format('d/m/Y H:i');
        } catch (Exception $e) {
            $fechaFormateada = $compra['fecha'] ?? '';
        }
    }
    
    $comprasData[] = [
        'id_transaccion' => $compra['id_transaccion'] ?? '',
        'fecha' => $fechaFormateada,
        'status' => $compra['status'] ?? '',
        'total' => $compra['total'] ?? 0,
        'medio_pago' => $compra['medio_pago'] ?? '',
        'cliente' => $cliente ? trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) : 'Sin nombre'
    ];
}

// Ordenar por fecha descendente
usort($comprasData, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

require '../header.php';

?>
<!-- Contenido -->
<main class="flex-shrink-0">
    <div class="container mt-3">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3>Compras</h3>
            <a class="btn btn-success" href="<?php echo ADMIN_URL; ?>compras/genera_reporte_compras.php">
                Reporte de compras
            </a>
        </div>

        <hr>

        <table id="datatablesSimple" class="table table-bordered table-sm">

            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Medio de Pago</th>
                    <th style="width: 10%" data-sortable="false">Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php 
                foreach ($comprasData as $row) { 
                    $status = $row['status'];
                    $medio_pago = $row['medio_pago'];
                    
                    // Traducir estado
                    $estado_texto = '';
                    $estado_badge = '';
                    switch($status) {
                        case 'COMPLETED':
                        case 'approved':
                            $estado_texto = 'Completado';
                            $estado_badge = 'success';
                            break;
                        case 'pending':
                            $estado_texto = 'Pendiente';
                            $estado_badge = 'warning';
                            break;
                        case 'failed':
                            $estado_texto = 'Fallido';
                            $estado_badge = 'danger';
                            break;
                        case 'cancelled':
                            $estado_texto = 'Cancelado';
                            $estado_badge = 'secondary';
                            break;
                        default:
                            $estado_texto = $status;
                            $estado_badge = 'info';
                    }
                ?>
                    <tr>
                        <td><?php echo $row['id_transaccion']; ?></td>
                        <td><?php echo $row['cliente']; ?></td>
                        <td>S/ <?php echo number_format($row['total'], 2, '.', ','); ?></td>
                        <td><?php echo $row['fecha']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $estado_badge; ?>"><?php echo $estado_texto; ?></span>
                        </td>
                        <td><?php echo $medio_pago == 'YAPE' ? 'Yape' : ($medio_pago == 'MP' ? 'Mercado Pago' : 'PayPal'); ?></td>
                        <td>
                            <div class="btn-group-vertical btn-group-sm" role="group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#detalleModal" data-bs-orden="<?php echo $row['id_transaccion']; ?>">
                                    <i class="fas fa-shopping-basket"></i> Ver
                                </button>
                                <?php if ($medio_pago == 'YAPE' && $status == 'pending') { ?>
                                    <button type="button" class="btn btn-success" onclick="confirmarPagoYape('<?php echo $row['id_transaccion']; ?>')">
                                        <i class="fas fa-check"></i> Completar
                                    </button>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>
        </table>
    </div>
</main>

<!-- Modal -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="detalleModalLabel">Detalles de compra</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const detalleModal = document.getElementById('detalleModal')
    detalleModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const orden = button.getAttribute('data-bs-orden')
        const modalBody = detalleModal.querySelector('.modal-body')

        const url = '<?php echo ADMIN_URL; ?>compras/getCompra.php'

        let formData = new FormData()
        formData.append('orden', orden)

        fetch(url, {
                method: 'post',
                body: formData,
            })
            .then((resp) => resp.json())
            .then(function(data) {
                modalBody.innerHTML = data
            })
    })

    detalleModal.addEventListener('hide.bs.modal', event => {
        const modalBody = detalleModal.querySelector('.modal-body')
        modalBody.innerHTML = ''
    })

    // Función para confirmar pago de Yape
    function confirmarPagoYape(idTransaccion) {
        if (confirm('¿Confirmas que el pago con Yape ha sido completado?')) {
            const url = '<?php echo ADMIN_URL; ?>compras/actualizar_estado_yape.php'
            
            let formData = new FormData()
            formData.append('id_transaccion', idTransaccion)
            
            fetch(url, {
                    method: 'post',
                    body: formData,
                })
                .then((resp) => resp.json())
                .then(function(data) {
                    if (data.success) {
                        alert('Estado actualizado correctamente')
                        location.reload()
                    } else {
                        alert('Error al actualizar el estado: ' + (data.message || 'Error desconocido'))
                    }
                })
                .catch(function(error) {
                    alert('Error al procesar la solicitud')
                    console.error('Error:', error)
                })
        }
    }
</script>

<?php include '../footer.php'; ?>