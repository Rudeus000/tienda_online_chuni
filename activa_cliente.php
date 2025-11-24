<?php

require 'config/supabase_config.php';
require 'clases/clienteFunciones.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if ($id == '' || $token == '') {
    header("Location: index.php");
    exit;
}

// La conexión ya está inicializada en supabase_config.php como $db y $con

echo validaToken($id, $token, $con);
