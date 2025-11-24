<?php

/**
 * Funciones de utilidad para usuarios
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

function esNulo(array $parametos)
{
    foreach ($parametos as $parameto) {
        if (strlen(trim($parameto)) < 1) {
            return true;
        }
    }
    return false;
}

function esEmail($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    return false;
}

function validaPassword($password, $repassword)
{
    if (strcmp($password, $repassword) === 0) {
        return true;
    }
    return false;
}

function generarToken()
{
    return md5(uniqid(mt_rand(), false));
}

function registraCliente(array $datos, $con)
{
    global $db;
    
    try {
        // Adaptar para Supabase
        $datosInsert = [
            'nombres' => $datos[0],
            'apellidos' => $datos[1],
            'email' => $datos[2],
            'telefono' => $datos[3],
            'dni' => $datos[4],
            'estatus' => 1,
            'fecha_alta' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('clientes', $datosInsert);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        error_log('Error al registrar cliente: Insert retornó ' . print_r($result, true));
        return 0;
    } catch (Exception $e) {
        error_log('Error en registraCliente: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return 0;
    } catch (\Throwable $e) {
        error_log('Error en registraCliente (Throwable): ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return 0;
    }
}

function registraUsuario(array $datos, $con)
{
    global $db;
    
    try {
        // Adaptar para Supabase
        $datosInsert = [
            'usuario' => $datos[0],
            'password' => $datos[1],
            'token' => $datos[2],
            'id_cliente' => intval($datos[3]),
            'activacion' => 0  // Inactivo hasta que se active mediante el email
        ];
        
        $result = $db->insert('usuarios', $datosInsert);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        error_log('Error al registrar usuario: Insert retornó ' . print_r($result, true));
        return 0;
    } catch (Exception $e) {
        error_log('Error en registraUsuario: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return 0;
    } catch (\Throwable $e) {
        error_log('Error en registraUsuario (Throwable): ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return 0;
    }
}

function usuarioExiste($usuario, $con)
{
    global $db;
    
    $result = $db->selectOne('usuarios', 'id', ['usuario' => $usuario]);
    return $result !== null && isset($result['id']);
}

function emailExiste($email, $con)
{
    global $db;
    
    $result = $db->selectOne('clientes', 'id', ['email' => $email]);
    return $result !== null && isset($result['id']);
}

function mostrarMensajes($errors = [])
{
    if (!empty($errors)) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '<ul>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
    }
}

function validaToken($id, $token, $con)
{
    global $db;
    
    $msg = "";
    $result = $db->selectOne('usuarios', 'id', ['id' => $id, 'token' => $token]);
    
    if ($result !== null && isset($result['id'])) {
        if (activarUsuario($id, $con)) {
            $msg = "Cuenta activada.";
        } else {
            $msg = "Error al activar cuenta.";
        }
    } else {
        $msg = "No existe el registro del cliente.";
    }

    return $msg;
}

function activarUsuario($id, $con)
{
    global $db;
    
    $result = $db->update('usuarios', ['activacion' => 1, 'token' => ''], 'id', $id);
    return $result !== null;
}

function login($usuario, $password, $con, $proceso)
{
    global $db;
    
    $result = $db->selectOne('usuarios', 'id, usuario, password, id_cliente', ['usuario' => $usuario]);
    
    if ($result && isset($result['id'])) {
        if (esActivo($usuario, $con)) {
            if (password_verify($password, $result['password'])) {
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_name'] = $result['usuario'];
                $_SESSION['user_cliente'] = $result['id_cliente'];
                if ($proceso == 'pago') {
                    header("Location: checkout.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        } else {
            return 'El usuario no ha sido activado';
        }
    }
    return 'El usuario y/o contraseña son incorrectos';
}

function esActivo($usuario, $con)
{
    global $db;
    
    $result = $db->selectOne('usuarios', 'activacion', ['usuario' => $usuario]);
    if ($result && isset($result['activacion']) && $result['activacion'] == 1) {
        return true;
    }
    return false;
}

function solicitaPassword($user_id, $con)
{
    global $db;
    
    $token = generarToken();
    $result = $db->update('usuarios', ['token_password' => $token, 'password_request' => 1], 'id', $user_id);
    
    if ($result !== null) {
        return $token;
    }
    return null;
}

function verificaTokenRequest($user_id, $token, $con)
{
    global $db;
    
    $result = $db->selectOne('usuarios', 'id', [
        'id' => $user_id, 
        'token_password' => $token, 
        'password_request' => 1
    ]);
    
    return $result !== null && isset($result['id']);
}

function actualizaPassword($user_id, $password, $con)
{
    global $db;
    
    $result = $db->update('usuarios', [
        'password' => $password,
        'token_password' => '',
        'password_request' => 0
    ], 'id', $user_id);
    
    return $result !== null;
}
