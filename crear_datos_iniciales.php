<?php
/**
 * Script para crear datos iniciales en Supabase
 * Ejecuta este script UNA SOLA VEZ para poblar la base de datos
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

echo "<h1>Crear Datos Iniciales</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

try {
    require 'config/supabase_config.php';
    echo "<p class='ok'>✓ Configuración cargada</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    exit;
}

// 1. Crear configuración inicial
echo "<h2>1. Crear Configuración Inicial</h2>";
$configuraciones = [
    ['nombre' => 'tienda_nombre', 'valor' => 'The mystical Star'],
    ['nombre' => 'tienda_moneda', 'valor' => 'S/'],
    ['nombre' => 'correo_smtp', 'valor' => 'smtp.gmail.com'],
    ['nombre' => 'correo_email', 'valor' => 'tu-email@gmail.com'],
    ['nombre' => 'correo_password', 'valor' => ''],
    ['nombre' => 'correo_puerto', 'valor' => '587'],
    ['nombre' => 'paypal_cliente', 'valor' => ''],
    ['nombre' => 'paypal_moneda', 'valor' => 'USD'],
    ['nombre' => 'mp_token', 'valor' => ''],
    ['nombre' => 'mp_clave', 'valor' => ''],
];

$configCreadas = 0;
foreach ($configuraciones as $config) {
    try {
        // Verificar si ya existe
        $existe = $db->selectOne('configuracion', 'id', ['nombre' => $config['nombre']]);
        
        if (!$existe) {
            $result = $db->insert('configuracion', $config);
            if ($result && isset($result['id'])) {
                $configCreadas++;
                echo "<p class='ok'>✓ Creado: {$config['nombre']}</p>";
            } else {
                echo "<p class='warning'>⚠ No se pudo crear: {$config['nombre']}</p>";
            }
        } else {
            echo "<p>Ya existe: {$config['nombre']}</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error al crear {$config['nombre']}: " . $e->getMessage() . "</p>";
    }
}
echo "<p><strong>Configuraciones creadas: {$configCreadas}</strong></p>";

// 2. Crear categorías de ejemplo
echo "<h2>2. Crear Categorías</h2>";
$categorias = [
    ['nombre' => 'Electrónica', 'activo' => 1],
    ['nombre' => 'Ropa', 'activo' => 1],
    ['nombre' => 'Hogar', 'activo' => 1],
];

$categoriasCreadas = 0;
foreach ($categorias as $cat) {
    try {
        $existe = $db->selectOne('categorias', 'id', ['nombre' => $cat['nombre']]);
        
        if (!$existe) {
            $result = $db->insert('categorias', $cat);
            if ($result && isset($result['id'])) {
                $categoriasCreadas++;
                echo "<p class='ok'>✓ Creada categoría: {$cat['nombre']} (ID: {$result['id']})</p>";
            }
        } else {
            echo "<p>Ya existe categoría: {$cat['nombre']}</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
}
echo "<p><strong>Categorías creadas: {$categoriasCreadas}</strong></p>";

// 3. Obtener primera categoría para productos
$primeraCategoria = $db->selectOne('categorias', 'id', ['activo' => 1]);
$categoriaId = $primeraCategoria['id'] ?? null;

if (!$categoriaId) {
    echo "<p class='error'>✗ No hay categorías. Crea categorías primero.</p>";
} else {
    // 4. Crear productos de ejemplo
    echo "<h2>3. Crear Productos de Ejemplo</h2>";
    $productos = [
        [
            'nombre' => 'Producto de Prueba 1',
            'slug' => 'producto-de-prueba-1',
            'descripcion' => '<p>Este es un producto de prueba para verificar que todo funcione correctamente.</p>',
            'precio' => 99.99,
            'descuento' => 10,
            'stock' => 50,
            'id_categoria' => $categoriaId,
            'activo' => 1
        ],
        [
            'nombre' => 'Producto de Prueba 2',
            'slug' => 'producto-de-prueba-2',
            'descripcion' => '<p>Segundo producto de prueba.</p>',
            'precio' => 149.99,
            'descuento' => 0,
            'stock' => 30,
            'id_categoria' => $categoriaId,
            'activo' => 1
        ],
        [
            'nombre' => 'Producto de Prueba 3',
            'slug' => 'producto-de-prueba-3',
            'descripcion' => '<p>Tercer producto de prueba con descuento.</p>',
            'precio' => 199.99,
            'descuento' => 15,
            'stock' => 20,
            'id_categoria' => $categoriaId,
            'activo' => 1
        ],
    ];

    $productosCreados = 0;
    foreach ($productos as $prod) {
        try {
            $existe = $db->selectOne('productos', 'id', ['slug' => $prod['slug']]);
            
            if (!$existe) {
                $result = $db->insert('productos', $prod);
                if ($result && isset($result['id'])) {
                    $productosCreados++;
                    echo "<p class='ok'>✓ Creado producto: {$prod['nombre']} (ID: {$result['id']})</p>";
                }
            } else {
                echo "<p>Ya existe producto: {$prod['nombre']}</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error al crear producto: " . $e->getMessage() . "</p>";
        }
    }
    echo "<p><strong>Productos creados: {$productosCreados}</strong></p>";
}

// 5. Resumen final
echo "<hr><h2>Resumen</h2>";
$totalConfig = count($db->select('configuracion', '*', []));
$totalCategorias = count($db->select('categorias', '*', ['activo' => 1]));
$totalProductos = count($db->select('productos', '*', ['activo' => 1]));

echo "<p>Configuraciones: {$totalConfig}</p>";
echo "<p>Categorías activas: {$totalCategorias}</p>";
echo "<p>Productos activos: {$totalProductos}</p>";

if ($totalProductos > 0) {
    echo "<p class='ok'><strong>✓ ¡Datos creados exitosamente! Ahora puedes ver productos en la tienda.</strong></p>";
    echo "<p><a href='index.php'>Ir a la tienda</a></p>";
} else {
    echo "<p class='warning'><strong>⚠ Aún no hay productos. Revisa los errores arriba.</strong></p>";
}

echo "<hr>";
echo "<p><small>IMPORTANTE: Elimina este archivo después de usarlo por seguridad.</small></p>";

