<?php
/**
 * Página de diagnóstico para verificar conexión y datos
 * Úsala temporalmente para diagnosticar problemas
 */

// Suprimir warnings de deprecación
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

echo "<h1>Diagnóstico de Tienda Online</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// 1. Verificar variables de entorno
echo "<h2>1. Variables de Entorno</h2>";
$supabase_url = $_ENV['SUPABASE_URL'] ?? 'NO CONFIGURADO';
$supabase_key = $_ENV['SUPABASE_ANON_KEY'] ?? 'NO CONFIGURADO';

echo "<p>SUPABASE_URL: " . ($supabase_url !== 'NO CONFIGURADO' ? '<span class="ok">✓ Configurado</span>' : '<span class="error">✗ No configurado</span>') . "</p>";
echo "<p>SUPABASE_ANON_KEY: " . ($supabase_key !== 'NO CONFIGURADO' ? '<span class="ok">✓ Configurado</span>' : '<span class="error">✗ No configurado</span>') . "</p>";

// 2. Intentar cargar configuración
echo "<h2>2. Carga de Configuración</h2>";
try {
    require 'config/supabase_config.php';
    echo "<p class='ok'>✓ Configuración cargada correctamente</p>";
    echo "<p>SITE_URL: " . (defined('SITE_URL') ? SITE_URL : '<span class="error">No definido</span>') . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al cargar configuración: " . $e->getMessage() . "</p>";
    exit;
}

// 3. Verificar conexión a Supabase
echo "<h2>3. Conexión a Supabase</h2>";
try {
    $test = $db->select('configuracion', '*', []);
    if (is_array($test)) {
        echo "<p class='ok'>✓ Conexión a Supabase exitosa</p>";
        echo "<p>Registros en 'configuracion': " . count($test) . "</p>";
    } else {
        echo "<p class='warning'>⚠ Conexión establecida pero respuesta inesperada</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
}

// 4. Verificar productos
echo "<h2>4. Productos en la Base de Datos</h2>";
try {
    // Obtener todos los productos (sin filtro de activo)
    $todosProductos = $db->select('productos', '*', []);
    echo "<p>Total de productos (sin filtro): " . count($todosProductos) . "</p>";
    
    // Obtener productos activos
    $productosActivos = $db->select('productos', '*', ['activo' => 1]);
    echo "<p>Productos activos (activo=1): " . count($productosActivos) . "</p>";
    
    if (count($productosActivos) > 0) {
        echo "<p class='ok'>✓ Hay productos activos en la base de datos</p>";
        echo "<h3>Primeros 5 productos activos:</h3>";
        echo "<ul>";
        foreach (array_slice($productosActivos, 0, 5) as $prod) {
            echo "<li>ID: {$prod['id']}, Nombre: {$prod['nombre']}, Activo: {$prod['activo']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠ No hay productos con activo=1</p>";
        if (count($todosProductos) > 0) {
            echo "<p>Productos encontrados (puede que tengan activo=0 o NULL):</p>";
            echo "<ul>";
            foreach (array_slice($todosProductos, 0, 5) as $prod) {
                $activo = $prod['activo'] ?? 'NULL';
                echo "<li>ID: {$prod['id']}, Nombre: {$prod['nombre']}, Activo: {$activo}</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al obtener productos: " . $e->getMessage() . "</p>";
}

// 5. Probar consulta directa con Supabase
echo "<h2>5. Consulta Directa con Supabase</h2>";
try {
    $query = $con->from('productos')
        ->select('id, nombre, precio, activo')
        ->eq('activo', 1)
        ->limit(5);
    
    $result = $query->execute();
    $data = $result->getData();
    
    echo "<p>Resultado de consulta directa: " . count($data) . " productos</p>";
    
    if (is_array($data) && isset($data['code'])) {
        echo "<p class='error'>✗ Error de Supabase: " . print_r($data, true) . "</p>";
    } elseif (!empty($data)) {
        echo "<p class='ok'>✓ Consulta directa funcionó correctamente</p>";
        echo "<pre>" . print_r(array_slice($data, 0, 3), true) . "</pre>";
    } else {
        echo "<p class='warning'>⚠ Consulta retornó vacío</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error en consulta directa: " . $e->getMessage() . "</p>";
}

// 6. Verificar categorías
echo "<h2>6. Categorías</h2>";
try {
    $categorias = $db->select('categorias', '*', ['activo' => 1]);
    echo "<p>Categorías activas: " . count($categorias) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al obtener categorías: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<p><small>Elimina este archivo después de diagnosticar el problema</small></p>";

