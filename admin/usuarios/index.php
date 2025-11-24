<?php

/**
 * Pantalla principal para mostrar el listado de usuarios
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}

require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
// Obtener usuarios y clientes por separado y combinarlos
$usuariosData = $db->select('usuarios');
$clientesData = $db->select('clientes');

// Crear un array asociativo de clientes por ID
$clientesById = [];
foreach ($clientesData as $cliente) {
    $clientesById[$cliente['id']] = $cliente;
}

// Combinar datos
$usuarios = [];
foreach ($usuariosData as $usuario) {
    $clienteId = $usuario['id_cliente'] ?? null;
    $cliente = $clientesById[$clienteId] ?? null;
    
    $usuarios[] = [
        'id' => $usuario['id'],
        'cliente' => $cliente ? trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) : 'Sin nombre',
        'usuario' => $usuario['usuario'] ?? '',
        'activacion' => $usuario['activacion'] ?? 0,
        'estatus' => $usuario['activacion'] == 1 ? 'Activo' : ($usuario['activacion'] == 0 ? 'No activado' : 'deshabilitado')
    ];
}

require '../header.php';

?>

<!-- Contenido -->
<main class="flex-shrink-0">
    <div class="container-fluid px-3">
        <h3 id="titulo" class="mt-2">Usuarios</h3>

        <hr>

        <table class="table table-bordered table-sm" aria-describedby="titulo">

            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>usuario</th>
                    <th>Estatus</th>
                    <th style="width: 10%" data-sortable="false"></th>
                    <th style="width: 10%" data-sortable="false"></th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($usuarios as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                        <td><?php echo htmlspecialchars($row['estatus']); ?></td>
                        <td>
                            <a href="<?php echo ADMIN_URL; ?>usuarios/cambiar_password.php?user_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-key"></i> Cambia
                            </a>
                        </td>
                        <td>

                            <?php if ($row['activacion'] == 1) { ?>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#eliminaModal" data-bs-user="<?php echo $row['id']; ?>">
                                    <i class="fas fa-arrow-down"></i> Baja
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#activaModal" data-bs-user="<?php echo $row['id']; ?>">
                                    <i class="fas fa-check-circle"></i> Activa
                                </button>
                            <?php } ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

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
                ¿Desea deshabilitar este usuario?
            </div>
            <div class="modal-footer">
                <form action="<?php echo ADMIN_URL; ?>usuarios/deshabilita.php" method="post">
                    <input type="hidden" name="id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Deshabilitar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Body -->
<div class="modal fade" id="activaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitleId">Alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Desea activar este usuario?
            </div>
            <div class="modal-footer">
                <form action="<?php echo ADMIN_URL; ?>usuarios/activa.php" method="post">
                    <input type="hidden" name="id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Activar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let eliminaModal = document.getElementById('eliminaModal')
    eliminaModal.addEventListener('show.bs.modal', function(event) {

        let button = event.relatedTarget
        let user = button.getAttribute('data-bs-user')

        let modalInputId = eliminaModal.querySelector('.modal-footer input')
        modalInputId.value = user
    })

    let activaModal = document.getElementById('activaModal')
    activaModal.addEventListener('show.bs.modal', function(event) {

        let button = event.relatedTarget
        let user = button.getAttribute('data-bs-user')

        let modalInputId = activaModal.querySelector('.modal-footer input')
        modalInputId.value = user
    })
</script>

<?php require '../footer.php'; ?>