<?php

/**
 * Actualiza un producto
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

$basePath = dirname(__DIR__, 2);
require_once $basePath . '/config/supabase_config.php';
require_once $basePath . '/admin/clases/adminFunciones.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// $db ya está disponible desde supabase_config.php
$id = $_POST['id'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$slug = crearTituloURL($nombre);
$descripcion = $_POST['descripcion'] ?? '';
$precio = $_POST['precio'] ?? 0;
$descuento = $_POST['descuento'] ?? 0;
$stock = $_POST['stock'] ?? 0;
$categoria = $_POST['categoria'] ?? '';

if (empty($id) || empty($nombre)) {
    header('Location: index.php?error=datos_requeridos');
    exit;
}

try {
    // Actualizar producto usando Supabase
    $datosProducto = [
        'slug' => $slug,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'precio' => floatval($precio),
        'descuento' => floatval($descuento),
        'stock' => intval($stock),
        'id_categoria' => intval($categoria)
    ];
    
    $resultado = $db->update('productos', $datosProducto, 'id', $id);
    
    if ($resultado !== null) {

    // Subir imagen principal
    if ($_FILES['imagen_principal']['error'] == UPLOAD_ERR_OK) {
        $dir = '../../images/productos/' . $id . '/';
        $permitidos = ['jpeg', 'jpg'];

        $arregloImagen = explode('.', $_FILES['imagen_principal']['name']);
        $extension = strtolower(end($arregloImagen));

        if (in_array($extension, $permitidos)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $ruta_img = $dir . 'principal.' . $extension;
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $ruta_img)) {
                echo "El archivo se cargó correctamente.";
            }
        }
    }

    // Subir otras imagenes
    if (isset($_FILES['otras_imagenes']) && !empty($_FILES['otras_imagenes']['tmp_name'][0])) {
        $dir = '../../images/productos/' . $id . '/';
        $permitidos = ['jpeg', 'jpg'];

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach ($_FILES['otras_imagenes']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) continue;
            
            $fileName = $_FILES['otras_imagenes']['name'][$key];
            $arregloImagen = explode('.', $fileName);
            $extension = strtolower(end($arregloImagen));

            $nuevoNombre = $dir . uniqid() . '.' . $extension;

            if (in_array($extension, $permitidos)) {
                move_uploaded_file($tmp_name, $nuevoNombre);
            }
        }
    }
    
    header('Location: index.php?success=1');
    exit;
} catch (Exception $e) {
    error_log('Error al actualizar producto: ' . $e->getMessage());
    header('Location: edita.php?id=' . $id . '&error=actualizar');
    exit;
}
