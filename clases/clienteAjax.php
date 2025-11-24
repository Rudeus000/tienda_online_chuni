<?php

/**
 * Script para validar si existe el usuario
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require_once '../config/supabase_config.php';
require_once 'clienteFunciones.php';

$datos = [];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // La conexión ya está inicializada en supabase_config.php como $db y $con

    if ($action == 'existeUsuario') {
        $datos['ok'] = usuarioExiste($_POST['usuario'], $con);
    } elseif ($action == 'existeEmail') {
        $datos['ok'] = emailExiste($_POST['email'], $con);
    }
}

echo json_encode($datos);
