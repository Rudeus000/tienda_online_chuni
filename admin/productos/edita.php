<?php

/**
 * Pantalla para mostrar el formulario de editar producto
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
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: index.php');
    exit;
}

try {
    // Obtener producto usando Supabase
    $producto = $db->selectOne('productos', 'id, nombre, descripcion, precio, stock, descuento, id_categoria', ['id' => $id, 'activo' => 1]);
    
    if (!$producto) {
        header('Location: index.php');
        exit;
    }
    
    // Obtener categorías usando Supabase
    $categorias = $db->select('categorias', 'id, nombre', ['activo' => 1]);
} catch (Exception $e) {
    error_log('Error al cargar datos: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Usar funciones helper para obtener URLs de imágenes
$imagenPrincipal = getImagenProducto($id, 'principal.jpg');
$imagenes = getImagenesAdicionalesProducto($id);

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
        <h3 class="mt-2">Modifica producto</h3>

        <form action="<?php echo ADMIN_URL; ?>productos/actualiza.php" method="post" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción:</label>
                <textarea class="form-control" name="descripcion" id="editor"><?php echo $producto['descripcion']; ?></textarea>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <label for="imagen_principal" class="form-label">Imagen principal:</label>
                    <input type="file" class="form-control" name="imagen_principal" id="imagen_principal" accept="image/jpeg">
                </div>
                <div class="col-12 col-md-6">
                    <label for="otras_imagenes" class="form-label">Otras imagenes:</label>
                    <input type="file" class="form-control" name="otras_imagenes[]" id="otras_imagenes" accept="image/jpeg" multiple>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <?php if ($imagenPrincipal && strpos($imagenPrincipal, 'no-photo.jpg') === false) { ?>
                        <img src="<?php echo $imagenPrincipal . '?v=' . time(); ?>" class="img-thumbnail my-3" style="max-height: 300px"><br>
                        <button class="btn btn-danger btn-sm" onclick="eliminaImagen(<?php echo $id; ?>, 'principal.jpg')">Eliminar</button>
                    <?php } ?>
                </div>

                <div class="col-12 col-md-6">
                    <div class="row">
                        <?php 
                        foreach ($imagenes as $index => $imagen) {
                            // Extraer nombre de archivo de la URL (funciona tanto para URLs de Storage como locales)
                            $path = parse_url($imagen, PHP_URL_PATH);
                            // Si la URL es de Supabase Storage, el path será /storage/v1/object/public/productos/{id}/{nombre}
                            // Si es local, será images/productos/{id}/{nombre}
                            if (strpos($path, '/storage/v1/object/public/productos/') !== false) {
                                // URL de Supabase Storage
                                $parts = explode('/', $path);
                                $nombreArchivo = end($parts);
                            } else {
                                // URL local
                                $nombreArchivo = basename($path);
                            }
                            // Remover parámetros de query si existen
                            $nombreArchivo = preg_replace('/\?.*$/', '', $nombreArchivo);
                        ?>
                            <div class="col-4">
                                <img src="<?php echo $imagen . '?v=' . time(); ?>" class="img-thumbnail my-3" style="max-height: 300px"><br>
                                <button class="btn btn-danger btn-sm" onclick="eliminaImagen(<?php echo $id; ?>, '<?php echo htmlspecialchars($nombreArchivo, ENT_QUOTES); ?>')">Eliminar</button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <label for="precio" class="form-label">Precio:</label>
                    <input type="number" class="form-control" name="precio" id="precio" value="<?php echo $producto['precio']; ?>" required>
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="descuento" class="form-label">Descuento:</label>
                    <input type="number" class="form-control" name="descuento" id="descuento" value="<?php echo $producto['descuento']; ?>" required>
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="stock" class="form-label">Stock:</label>
                    <input type="number" class="form-control" name="stock" id="stock" value="<?php echo $producto['stock']; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-4 mb-3">
                    <label for="categoria" class="form-label">Categoría:</label>
                    <select class="form-select" name="categoria" id="categoria" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($categorias as $categoria) { ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php if ($categoria['id'] == $producto['id_categoria']) echo 'selected'; ?>><?php echo $categoria['nombre']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <a href="<?php echo ADMIN_URL; ?>productos/index.php" class="btn btn-secondary my-3">Regresar</a>
            <button type="submit" class="btn btn-primary my-3">Guardar</button>
        </form>

    </div>
</main>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'))
        .catch(error => {
            console.error(error);
        });

    function eliminaImagen(productoId, nombreArchivo) {
        let url = 'eliminar_imagen.php'
        let formData = new FormData()
        formData.append('productoId', productoId)
        formData.append('nombreArchivo', nombreArchivo)

        if (confirm('¿Está seguro de que desea eliminar esta imagen?')) {
            fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        location.reload()
                    } else {
                        alert('Error al eliminar la imagen: ' + (data.error || 'Error desconocido'))
                    }
                })
                .catch(err => {
                    console.log(err)
                    alert('Error al eliminar la imagen')
                });
        }
    }
</script>

<?php require '../footer.php'; ?>