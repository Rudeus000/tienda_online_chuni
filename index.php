<?php

/**
 * Pantalla principal para mostrar el listado de productos
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';

$idCategoria = $_GET['cat'] ?? '';
$orden = $_GET['orden'] ?? '';
$buscar = $_GET['q'] ?? '';

// Mapeo de órdenes
$orders = [
    'asc' => 'nombre',
    'desc' => 'nombre',
    'precio_alto' => 'precio',
    'precio_bajo' => 'precio'
];

$orderField = $orders[$orden] ?? '';
$orderDirection = ($orden === 'precio_alto') ? 'desc' : 'asc';

// Construir consulta para productos
$query = $con->from('productos')
    ->select('id, slug, nombre, precio, descuento')
    ->eq('activo', 1);

// Aplicar búsqueda
if (!empty($buscar)) {
    $query->or()
        ->ilike('nombre', "%$buscar%")
        ->ilike('descripcion', "%$buscar%");
}

// Filtrar por categoría
if (!empty($idCategoria)) {
    $query->eq('id_categoria', $idCategoria);
}

// Aplicar ordenamiento
if (!empty($orderField)) {
    $query->order($orderField, ['asc' => $orderDirection === 'asc']);
}

try {
    $result = $query->execute();
    $data = $result->getData();
    
    // Debug: Log para ver qué está retornando Supabase
    if (empty($data)) {
        error_log('DEBUG: La consulta de productos retornó vacío. Verificar: 1) Hay productos en la BD, 2) Los productos tienen activo=1, 3) La conexión a Supabase funciona');
    }
    
    // Verificar si hay error en la respuesta de Supabase
    if (is_array($data) && isset($data['code']) && isset($data['message'])) {
        // Error de Supabase (ej: columna no existe)
        error_log('Error en consulta de productos: ' . print_r($data, true));
        $resultado = [];
    } elseif (is_array($data) && !empty($data)) {
        // Filtrar solo productos válidos (que tengan id y nombre)
        $resultado = array_filter($data, function($item) {
            return is_array($item) && isset($item['id']) && isset($item['nombre']);
        });
        // Reindexar el array después de filtrar
        $resultado = array_values($resultado);
    } else {
        $resultado = [];
    }
    
    $totalRegistros = count($resultado);
} catch (Throwable $e) {
    error_log('Error al obtener productos: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    $resultado = [];
    $totalRegistros = 0;
}

// Obtener categorías
try {
    $categorias = $db->select('categorias', 'id, nombre', ['activo' => 1]);
} catch (Throwable $e) {
    error_log('Error al obtener categorías: ' . $e->getMessage());
    $categorias = [];
}

?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The mystical Star</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container p-3">
            <?php if (isset($_GET['pago_yape']) && $_GET['pago_yape'] == 1) { ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>¡Pago registrado!</strong> Tu pago con Yape ha sido registrado. Por favor, envía el comprobante de pago por WhatsApp para confirmar tu pedido.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>
            <div class="row">
                <div class="col-12 col-md-3 col-lg-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            Categorías
                        </div>

                        <div class="list-group">
                            <a href="index.php" class="list-group-item list-group-item-action">TODO</a>
                            <?php foreach ($categorias as $categoria) { ?>
                                <a href="index.php?cat=<?php echo $categoria['id']; ?>" class="list-group-item list-group-item-action <?php echo ($categoria['id'] == $idCategoria) ? 'active' : ''; ?>">
                                    <?php echo $categoria['nombre']; ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-9 col-lg-9">
                    <header class="d-sm-flex align-items-center border-bottom mb-4 pb-3">
                        <strong class="d-block py-2"><?php echo $totalRegistros; ?> Artículos encontrados </strong>
                        <div class="ms-auto">
                            <form action="index.php" id="ordenForm" method="get" onchange="submitForm()">
                                <input type="hidden" id="cat" name="cat" value="<?php echo $idCategoria; ?>">
                                <label for="orden" class="form-label">Ordena por</label>

                                <select class="form-select d-inline-block w-auto pt-1 form-select-sm" name="orden" id="orden">
                                    <option value="precio_alto" <?php echo ($orden === 'precio_alto') ? 'selected' : ''; ?>>Pecios más altos</option>
                                    <option value="precio_bajo" <?php echo ($orden === 'precio_bajo') ? 'selected' : ''; ?>>Pecios más bajos</option>
                                    <option value="asc" <?php echo ($orden === 'asc') ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="desc" <?php echo ($orden === 'desc') ? 'selected' : ''; ?>>Nombre Z-A</option>
                                </select>
                            </form>
                        </div>
                    </header>

                    <div class="row">
                        <?php 
                        if (empty($resultado)) {
                            echo '<div class="col-12"><p class="alert alert-warning">No se encontraron productos para mostrar.</p></div>';
                        }
                        foreach ($resultado as $row) { 
                            if (!isset($row['id']) || !isset($row['nombre'])) {
                                continue; // Saltar productos incompletos
                            }
                        ?>
                            <div class="col-lg-4 col-md-6 col-sm-6 d-flex">
                                <div class="card w-100 my-2 shadow-2-strong">

                                    <?php
                                    $id = $row['id'];
                                    $imagen = "images/productos/$id/principal.jpg";

                                    if (!file_exists($imagen)) {
                                        $imagen = "images/no-photo.jpg";
                                    }
                                    ?>
                                    <a href="details.php?slug=<?php echo $row['slug']; ?>">
                                        <img src="<?php echo $imagen; ?>" class="img-thumbnail" style="max-height: 300px">
                                    </a>

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex flex-row">
                                            <h5 class="mb-1 me-1"><?php echo MONEDA . ' ' . number_format($row['precio'], 2, '.', ','); ?></h5>
                                        </div>
                                        <p class="card-text"><?php echo $row['nombre']; ?></p>
                                    </div>

                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a class="btn btn-success" onClick="addProducto(<?php echo $row['id']; ?>)">Agregar</a>
                                            <div class="btn-group">
                                                <a href="details.php?slug=<?php echo $row['slug']; ?>" class="btn btn-primary">Detalles</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>
    <script>
        function addProducto(id) {
            var url = 'clases/carrito.php';
            var formData = new FormData();
            formData.append('id', id);

            fetch(url, {
                    method: 'POST',
                    body: formData,
                    mode: 'cors',
                }).then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        let elemento = document.getElementById("num_cart")
                        elemento.innerHTML = data.numero;
                    } else {
                        alert("No ay suficientes productos en el stock")
                    }
                })
        }

        function submitForm() {
            document.getElementById("ordenForm").submit();
        }
    </script>
</body>

</html>