<?php

/**
 * Pantalla para registro de cliente
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';
require 'clases/clienteFunciones.php';

// La conexión ya está inicializada en supabase_config.php como $db y $con

$errors = [];

if (!empty($_POST)) {

    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $dni = trim($_POST['dni']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $repassword = trim($_POST['repassword']);

    if (esNulo([$nombres, $apellidos, $email, $telefono, $dni, $usuario, $password, $repassword])) {
        $errors[] = "Debe llenar todos los campos";
    }

    if (!esEmail($email)) {
        $errors[] = "La dirección de correo no es válida";
    }

    if (!validaPassword($password, $repassword)) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (usuarioExiste($usuario, $con)) {
        $errors[] = "El nombre de usuario $usuario ya existe";
    }

    if (emailExiste($email, $con)) {
        $errors[] = "El correo electrónico $email ya existe";
    }

    if (empty($errors)) {

        $id = registraCliente([$nombres, $apellidos, $email, $telefono, $dni], $con);

        if ($id > 0) {
            error_log('Cliente registrado exitosamente con ID: ' . $id);

            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $token = generarToken();

            // Registrar usuario con activación automática
            $idUsuario = registraUsuario([$usuario, $pass_hash, $token, $id], $con);
            if ($idUsuario > 0) {
                // Activar automáticamente al usuario al registrarse
                global $db;
                try {
                    $db->update('usuarios', ['activacion' => 1], 'id', $idUsuario);
                    
                    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Registro Exitoso</title>";
                    echo "<link href='" . SITE_URL . "css/bootstrap.min.css' rel='stylesheet'>";
                    echo "</head><body>";
                    echo "<div style='padding: 40px; max-width: 600px; margin: 50px auto; text-align: center;'>";
                    echo "<div class='alert alert-success' role='alert'>";
                    echo "<h3>¡Registro exitoso!</h3>";
                    echo "<p>Tu cuenta ha sido creada y <strong>activada automáticamente</strong>.</p>";
                    echo "<p>Ya puedes iniciar sesión con tu usuario y contraseña.</p>";
                    echo "<a href='login.php' class='btn btn-primary mt-3'>Iniciar Sesión</a>";
                    echo "<br><br>";
                    echo "<a href='index.php'>Volver al inicio</a>";
                    echo "</div>";
                    echo "</div>";
                    echo "</body></html>";
                    exit;
                } catch (Exception $e) {
                    error_log('Error al activar usuario automáticamente: ' . $e->getMessage());
                    // Si falla la activación automática, intentar enviar correo
                    require 'clases/Mailer.php';
                    $mailer = new Mailer();
                    $url = SITE_URL . 'activa_cliente.php?id=' . $idUsuario . '&token=' . $token;
                    $asunto = "Activar cuenta - Tienda online";
                    $cuerpo = "Estimado $nombres: <br> Para continuar con el proceso de registro es indispensable de clic en la siguiente liga <a href='$url'>Activar cuenta</a>";
                    
                    if ($mailer->enviarEmail($email, $asunto, $cuerpo)) {
                        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Registro Exitoso</title>";
                        echo "<link href='" . SITE_URL . "css/bootstrap.min.css' rel='stylesheet'>";
                        echo "</head><body>";
                        echo "<div style='padding: 40px; max-width: 600px; margin: 50px auto;'>";
                        echo "<div class='alert alert-info' role='alert'>";
                        echo "<h3>¡Registro exitoso!</h3>";
                        echo "<p>Hemos enviado un correo a <strong>$email</strong> con el enlace de activación.</p>";
                        echo "<p><a href='index.php'>Volver al inicio</a></p>";
                        echo "</div>";
                        echo "</div>";
                        echo "</body></html>";
                    } else {
                        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Registro Exitoso</title>";
                        echo "<link href='" . SITE_URL . "css/bootstrap.min.css' rel='stylesheet'>";
                        echo "</head><body>";
                        echo "<div style='padding: 40px; max-width: 600px; margin: 50px auto;'>";
                        echo "<div class='alert alert-warning' role='alert'>";
                        echo "<h3>¡Usuario registrado exitosamente!</h3>";
                        echo "<p>Tu cuenta está lista. <strong>Haz clic aquí para activarla:</strong></p>";
                        echo "<p><a href='$url' class='btn btn-primary'>Activar mi cuenta</a></p>";
                        echo "<p><a href='index.php'>Volver al inicio</a></p>";
                        echo "</div>";
                        echo "</div>";
                        echo "</body></html>";
                    }
                    exit;
                }
            } else {
                error_log('Error al registrar usuario. ID Cliente: ' . $id);
                $errors[] = "Error al registrar usuario";
            }
        } else {
            error_log('Error al registrar cliente. ID retornado: ' . $id);
            $errors[] = "Error al registrar cliente. Por favor, verifica los datos e intenta nuevamente.";
        }
    }
}


?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda en linea</title>

    <link href="<?php echo SITE_URL; ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">
    
    <?php include 'menu.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">
            <h3>Datos del cliente</h3>

            <?php mostrarMensajes($errors); ?>

            <form class="row g-3" action="registro.php" method="post" autocomplete="off">
                <div class="col-md-6">
                    <label for="nombres"><span class="text-danger">*</span> Nombres</label>
                    <input type="text" name="nombres" id="nombres" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="apellidos"><span class="text-danger">*</span> Apellidos</label>
                    <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="email"><span class="text-danger">*</span> Correo electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                    <span id="validaEmail" class="text-danger"></span>
                </div>
                <div class="col-md-6">
                    <label for="telefono"><span class="text-danger">*</span> Telefono</label>
                    <input type="tel" name="telefono" id="telefono" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="dni"><span class="text-danger">*</span> DNI</label>
                    <input type="text" name="dni" id="dni" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="usuario"><span class="text-danger">*</span> Usuario</label>
                    <input type="text" name="usuario" id="usuario" class="form-control" required>
                    <span id="validaUsuario" class="text-danger"></span>
                </div>

                <div class="col-md-6">
                    <label for="password"><span class="text-danger">*</span> Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="repassword"><span class="text-danger">*</span> Repetir contraseña</label>
                    <input type="password" name="repassword" id="repassword" class="form-control" required>
                </div>

                <i><b>Nota:</b> Los campos con asterisco son obligatorios</i>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>

            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>

    <script>
        let txtUsuario = document.getElementById('usuario')
        txtUsuario.addEventListener("blur", function() {
            existeUsuario(txtUsuario.value)
        }, false)

        let txtEmail = document.getElementById('email')
        txtEmail.addEventListener("blur", function() {
            existeEmail(txtEmail.value)
        }, false)

        function existeEmail(email) {
            let url = "clases/clienteAjax.php"
            let formData = new FormData()
            formData.append("action", "existeEmail")
            formData.append("email", email)

            fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                .then(data => {

                    if (data.ok) {
                        document.getElementById('email').value = ''
                        document.getElementById('validaEmail').innerHTML = 'Email no disponible'
                    } else {
                        document.getElementById('validaEmail').innerHTML = ''
                    }

                })
        }

        function existeUsuario(usuario) {
            let url = "clases/clienteAjax.php"
            let formData = new FormData()
            formData.append("action", "existeUsuario")
            formData.append("usuario", usuario)

            fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                .then(data => {

                    if (data.ok) {
                        document.getElementById('usuario').value = ''
                        document.getElementById('validaUsuario').innerHTML = 'Usuario no disponible'
                    } else {
                        document.getElementById('validaUsuario').innerHTML = ''
                    }

                })
        }
    </script>

</body>

</html>