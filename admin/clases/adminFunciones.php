<?php

/**
 * Funciones de utilidad para administración
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

function esNulo($parametos)
{
    foreach ($parametos as $parameto) {
        if (strlen(trim($parameto)) < 1) {
            return true;
        }
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

function usuarioExiste($usuario, $con)
{
    $sql = $con->prepare("SELECT id FROM usuarios WHERE usuario LIKE ? LIMIT 1");
    $sql->execute([$usuario]);
    if ($sql->fetchColumn() > 0) {
        return true;
    }
    return false;
}

function emailExiste($email, $con)
{
    $sql = $con->prepare("SELECT id FROM clientes WHERE email LIKE ? LIMIT 1");
    $sql->execute([$email]);
    if ($sql->fetchColumn() > 0) {
        return true;
    }
    return false;
}

function mostrarMensajes($errors = [])
{
    if (!empty($errors)) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '<ul>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

function validaToken($id, $token, $con)
{
    $msg = "";
    $sql = $con->prepare("SELECT id FROM usuarios WHERE id = ? AND token LIKE ? LIMIT 1");
    $sql->execute([$id, $token]);
    if ($sql->fetchColumn() > 0) {
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
    $sql = $con->prepare("UPDATE usuarios SET activacion = 1, token = '' WHERE id = ?");
    return $sql->execute([$id]);
}

function login($usuario, $password, $con)
{
    global $db;
    
    try {
        // Limpiar el nombre de usuario
        $usuario = trim($usuario);
        
        // Primero intentar buscar con filtro de activo
        $admin = $db->selectOne('admin', '*', ['usuario' => $usuario, 'activo' => 1]);
        
        // Si no se encuentra, buscar sin el filtro de activo (por si el campo no existe o está en 0)
        if (!$admin) {
            $admin = $db->selectOne('admin', '*', ['usuario' => $usuario]);
        }
        
        if (!$admin) {
            error_log("Login fallido: Usuario '$usuario' no encontrado");
            return 'El usuario y/o contraseña son incorrectos';
        }
        
        if (!isset($admin['password']) || empty($admin['password'])) {
            error_log("Login fallido: Usuario '$usuario' no tiene contraseña");
            return 'El usuario y/o contraseña son incorrectos';
        }
        
        $storedPassword = $admin['password'];
        
        // Verificar si es un hash válido (los hashes bcrypt tienen 60 caracteres y empiezan con $2y$)
        $isHash = (strlen($storedPassword) === 60 && strpos($storedPassword, '$2y$') === 0);
        
        if ($isHash) {
            // Es un hash, usar password_verify
            if (password_verify($password, $storedPassword)) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['nombre'];
                $_SESSION['user_type'] = 'admin';
                error_log("Login exitoso: Usuario '{$admin['usuario']}' (ID: {$admin['id']})");
                header("Location: inicio.php");
                exit;
            } else {
                error_log("Login fallido: Contraseña incorrecta para usuario '$usuario'");
            }
        } else {
            // Es texto plano (no recomendado, pero necesario para compatibilidad)
            if ($password === $storedPassword) {
                // Si la contraseña coincide, hashearla y actualizarla
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $db->update('admin', ['password' => $hashedPassword], 'id', $admin['id']);
                    error_log("Contraseña actualizada a hash para usuario '{$admin['usuario']}'");
                } catch (\Throwable $e) {
                    error_log("Error al actualizar contraseña: " . $e->getMessage());
                }
                
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['nombre'];
                $_SESSION['user_type'] = 'admin';
                error_log("Login exitoso: Usuario '{$admin['usuario']}' (ID: {$admin['id']}) - contraseña migrada a hash");
                header("Location: inicio.php");
                exit;
            } else {
                error_log("Login fallido: Contraseña en texto plano incorrecta para usuario '$usuario'");
            }
        }
    } catch (\Throwable $e) {
        error_log('Error en login admin: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
    }
    
    return 'El usuario y/o contraseña son incorrectos';
}

function solicitaPassword($userId, $con)
{
    $token = generarToken();

    $sql = $con->prepare("UPDATE usuarios SET token_password=?, password_request=1 WHERE id = ?");
    if ($sql->execute([$token, $userId])) {
        return $token;
    }
    return null;
}

function verificaTokenRequest($userId, $token, $con)
{
    $sql = $con->prepare("SELECT id FROM usuarios WHERE id = ? AND token_password LIKE ? AND password_request=1 LIMIT 1");
    $sql->execute([$userId, $token]);
    if ($sql->fetchColumn() > 0) {
        return true;
    }
    return false;
}

function actualizaPassword($userId, $password, $con)
{
    $sql = $con->prepare("UPDATE usuarios SET password=?, token_password = '', password_request = 0 WHERE id = ?");
    if ($sql->execute([$password, $userId])) {
        return true;
    }
    return false;
}

function actualizaPasswordAdmin($userId, $password, $con = null)
{
    global $db;
    
    try {
        // Usar Supabase si está disponible
        if ($db) {
            $result = $db->update('admin', ['password' => $password], 'id', $userId);
            return $result !== null;
        }
        
        // Fallback a PDO si se proporciona conexión
        if ($con) {
            $sql = $con->prepare("UPDATE admin SET password=? WHERE id = ?");
            if ($sql->execute([$password, $userId])) {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log('Error en actualizaPasswordAdmin: ' . $e->getMessage());
    }
    
    return false;
}

function crearTituloURL($cadena) {
    // Convertir la cadena a minúsculas y reemplazar caracteres especiales y espacios con guiones
    $url = strtolower($cadena);
    $url = preg_replace('/[^a-z0-9\-]/', '-', $url);
    $url = preg_replace('/-+/', "-", $url); // Reemplazar múltiples guiones con uno solo
    $url = trim($url, '-'); // Eliminar guiones al principio y al final
    
    return $url;
}
