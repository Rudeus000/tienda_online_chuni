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


// Cargar primero supabase_config.php que maneja la sesión
require_once $basePath . '/config/supabase_config.php';

// Verificar autenticación - la sesión ya debería estar iniciada en supabase_config.php
// Asegurarse de que $_SESSION esté disponible
if (!isset($_SESSION) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    error_log('Sesión no válida en nuevo.php. Session ID: ' . (session_id() ?? 'no iniciada'));
    header('Location: ' . ADMIN_URL . 'index.php?error=sesion_expirada');
    exit;
}

// $db ya está disponible desde supabase_config.php
try {
    // Obtener categorías usando Supabase
    $categorias = $db->select('categorias', 'id, nombre', ['activo' => 1]);
    if (!is_array($categorias)) {
        $categorias = [];
    }
} catch (Exception $e) {
    error_log('Error al cargar categorías en nuevo.php: ' . $e->getMessage());
    $categorias = [];
} catch (\Throwable $e) {
    error_log('Error al cargar categorías en nuevo.php (Throwable): ' . $e->getMessage());
    $categorias = [];
}

require '../header.php';

?>

<style>
    .ck-editor__editable[role="textbox"] {
        /* editing area */
        min-height: 200px;
    }
</style>

<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<main>
    <div class="container-fluid px-3">
        <h3 class="mt-2">Nuevo producto</h3>

        <form action="<?php echo ADMIN_URL; ?>productos/guarda.php" method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" class="form-control" name="nombre" id="nombre" required autofocus>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción:</label>
                <textarea class="form-control" name="descripcion" id="editor"></textarea>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <label for="imagen_principal" class="form-label">Imagen principal:</label>
                    <input type="file" class="form-control" name="imagen_principal" id="imagen_principal" accept="image/jpeg" required>
                </div>
                <div class="col-12 col-md-6">
                    <label for="otras_imagenes" class="form-label">Otras imagenes:</label>
                    <input type="file" class="form-control" name="otras_imagenes[]" id="otras_imagenes" accept="image/jpeg" multiple>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <label for="precio" class="form-label">Precio:</label>
                    <input type="number" class="form-control" name="precio" id="precio" required>
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="descuento" class="form-label">Descuento:</label>
                    <input type="number" class="form-control" name="descuento" id="descuento" required>
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="stock" class="form-label">Stock:</label>
                    <input type="number" class="form-control" name="stock" id="stock" required>
                </div>
            </div>

            <div class="row">
                <div class="col-4 mb-3">
                    <label for="categoria" class="form-label">Categoría:</label>
                    <select class="form-select" name="categoria" id="categoria" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($categorias as $categoria) { ?>
                            <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <a href="<?php echo ADMIN_URL; ?>productos/index.php" class="btn btn-secondary my-3">Regresar</a>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>

    </div>
</main>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'))
        .catch(error => {
            console.error(error);
        });
</script>


<?php require '../footer.php'; ?>