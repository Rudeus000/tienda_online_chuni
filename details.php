<?php

/**
 * Pantalla individual para mostrar el producto
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if ($slug == '') {
    echo 'Error al procesar la petición';
    exit;
}

try {
    $response = $con->from('productos')
        ->select('id, nombre, descripcion, precio, descuento, slug')
        ->eq('slug', $slug)
        ->eq('activo', 1)
        ->limit(1)
        ->execute();

    $data = extractSupabaseData($response);
    
    // Verificar si hay error en la respuesta
    if (is_array($data) && isset($data['code']) && isset($data['message'])) {
        // Error de Supabase
        error_log('Error en consulta de producto: ' . $data['message']);
        $row = null;
    } elseif (is_array($data) && !empty($data) && isset($data[0])) {
        // Datos válidos - tomar el primer elemento
        $row = $data[0];
    } elseif (is_array($data) && !empty($data) && !isset($data[0]) && isset($data['id'])) {
        // Si es un objeto asociativo directamente
        $row = $data;
    } else {
        $row = null;
    }
} catch (Throwable $e) {
    error_log('Error al obtener el producto: ' . $e->getMessage());
    $row = null;
}

if (!$row || !isset($row['id'])) {
    echo '<h1>Producto no disponible</h1>';
    echo '<p>El producto que buscas no existe o no está disponible.</p>';
    echo '<a href="index.php">Volver al catálogo</a>';
    exit;
}

$id = $row['id'];
$descuento = $row['descuento'];
$precio = $row['precio'];
$precio_desc = $precio - (($precio * $descuento) / 100);

// Usar funciones helper para obtener URLs de imágenes
$rutaImg = getImagenProducto($id, 'principal.jpg');
$imagenes = getImagenesAdicionalesProducto($id);
?>

<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda en linea</title>

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <base href="<?php echo SITE_URL; ?>">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">
            <div class="row">
                <div class="col-md-5 order-md-1">
                    <!--Carrusel-->
                    <div id="carouselImages" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <!--Imagenes-->
                            <div class="carousel-item active">
                                <img src="<?php echo $rutaImg; ?>" class="d-block w-100">
                            </div>

                            <?php foreach ($imagenes as $img) { ?>
                                <div class="carousel-item">
                                    <img src="<?php echo $img; ?>" class="d-block w-100">
                                </div>
                            <?php } ?>

                            <!--Imagenes-->
                        </div>

                        <!--Controles-->
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselImages" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselImages" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                        <!--Controles carrusel-->
                    </div>
                    <!--Carrusel-->
                </div>

                <div class="col-md-7 order-md-2">
                    <h2><?php echo htmlspecialchars($row['nombre'] ?? 'Sin nombre', ENT_QUOTES); ?></h2>

                    <?php 
                    $descuento = isset($row['descuento']) ? floatval($row['descuento']) : 0;
                    $precio = isset($row['precio']) ? floatval($row['precio']) : 0;
                    $precio_desc = $precio - (($precio * $descuento) / 100);
                    
                    if ($descuento > 0) { 
                    ?>

                        <p><del><?php echo MONEDA; ?> <?php echo number_format($precio, 2, '.', ','); ?></del></p>
                        <h2><?php echo MONEDA; ?> <?php echo number_format($precio_desc, 2, '.', ','); ?> <small class="text-success"><?php echo $descuento; ?>% descuento</small></h2>

                    <?php } else { ?>

                        <h2><?php echo MONEDA . ' ' . number_format($precio, 2, '.', ','); ?></h2>

                    <?php } ?>

                    <div class="lead"><?php echo $row['descripcion'] ?? 'Sin descripción'; ?></div>

                    <div class="col-3 my-3">
                        <input class="form-control" id="cantidad" name="cantidad" type="number" min="1" max="10" value="1">
                    </div>

                    <div class="d-grid gap-3 col-7">
                        <button class="btn btn-primary" type="button" onClick="comprarAhora(<?php echo $id; ?>, cantidad.value)">Comprar ahora</button>
                        <button class="btn btn-outline-primary" type="button" onClick="addProducto(<?php echo $id; ?>, cantidad.value)">Agregar al carrito</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

    <script>
        function addProducto(id, cantidad) {
            var url = 'clases/carrito.php';
            var formData = new FormData();
            formData.append('id', id);
            formData.append('cantidad', cantidad);

            fetch(url, {
                    method: 'POST',
                    body: formData,
                    mode: 'cors',
                }).then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        let elemento = document.getElementById("num_cart")
                        elemento.innerHTML = data.numero;
                    }
                })
        }

        function comprarAhora(id, cantidad) {
            var url = 'clases/carrito.php';
            var formData = new FormData();
            formData.append('id', id);
            formData.append('cantidad', cantidad);

            fetch(url, {
                    method: 'POST',
                    body: formData,
                    mode: 'cors',
                }).then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        let elemento = document.getElementById("num_cart")
                        elemento.innerHTML = data.numero;
                        location.href ='checkout.php';
                    }
                })
        }
    </script>
</body>

</html>