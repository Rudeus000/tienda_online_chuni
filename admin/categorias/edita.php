<?php

/**
 * Pantalla para mostrar el formulario para modificar
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
if (!file_exists($basePath . '/config/supabase_config.php')) {
    $basePath = dirname(__DIR__);
}

require_once $basePath . '/config/supabase_config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'categorias/index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
$id = $_GET['id'];

$categoria = $db->selectOne('categorias', '*', ['id' => $id]);

require '../header.php';

?>
<main>
    <div class="container-fluid px-3">
        <h3 class="mt-2">Modifica categoría</h3>

        <form action="<?php echo ADMIN_URL; ?>categorias/actualiza.php" method="post" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $categoria['nombre']; ?>" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>

    </div>
</main>

<?php require '../footer.php'; ?>