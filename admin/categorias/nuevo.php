<?php

/**
 * Pantalla para mostrar el formulario de nuevo registro
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

require '../header.php';

?>
<main>
    <div class="container-fluid px-3">
        <h3 class="mt-2">Nueva categoría</h3>

        <form action="<?php echo ADMIN_URL; ?>categorias/guarda.php" method="post" autocomplete="off">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" id="nombre" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>

    </div>
</main>

<?php require '../footer.php'; ?>