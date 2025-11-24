<?php

/**
 * Pantalla principal para mostrar el listado de productos
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}


// Cargar primero supabase_config.php que maneja la sesión
require_once $basePath . '/config/supabase_config.php';

// Verificar autenticación - la sesión ya debería estar iniciada en supabase_config.php
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php?error=sesion_expirada');
    exit;
}

// $db ya está disponible desde supabase_config.php
$productos = $db->select('productos', '*', ['activo' => 1]);

require '../header.php';

?>
<main>
    <div class="container-fluid px-3">
        <h3 class="mt-2">Productos</h3>

        <a class="btn btn-primary" href="<?php echo ADMIN_URL; ?>productos/nuevo.php">Agregar</a>

        <hr>

        <table id="datatablesSimple" class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th data-sortable="false" style="width: 5%"></th>
                    <th data-sortable="false" style="width: 5%"></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($productos as $producto) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?></td>
                        <td><?php echo $producto['precio']; ?></td>
                        <td><?php echo $producto['stock']; ?></td>
                        <td>
                            <a href="<?php echo ADMIN_URL; ?>productos/edita.php?id=<?php echo $producto['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-pen"></i> Editar
                            </a>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminaModal" data-bs-id="<?php echo $producto['id']; ?>">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal Body -->
<div class="modal fade" id="eliminaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitleId">Alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Desea eliminar este registro?
            </div>
            <div class="modal-footer">
                <form action="<?php echo ADMIN_URL; ?>productos/elimina.php" method="post">
                    <input type="hidden" name="id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let eliminaModal = document.getElementById('eliminaModal')
    eliminaModal.addEventListener('show.bs.modal', function(event) {

        let button = event.relatedTarget
        let id = button.getAttribute('data-bs-id')

        let modalInputId = eliminaModal.querySelector('.modal-footer input')
        modalInputId.value = id
    })
</script>

<?php require '../footer.php'; ?>