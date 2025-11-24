<?php

/**
 * Pantalla para modificar contraseña de administrador
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'config/supabase_config.php';
require 'clases/adminFunciones.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: index.php');
    exit;
}

$userId = $_GET['id'] ?? $_POST['id'] ?? '';

if ($userId == '' || $userId != $_SESSION['user_id']) {
    header("Location: index.php");
    exit;
}

// $db ya está disponible desde supabase_config.php
$errors = [];

if (!empty($_POST)) {
    $password = trim($_POST['password']);
    $repassword = trim($_POST['repassword']);

    if (esNulo([$userId, $password, $repassword])) {
        $errors[] = "Debe llenar todos los campos";
    }

    if (!validaPassword($password, $repassword)) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (empty($errors)) {
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            // Usar el método update de Supabase directamente
            $result = $db->update('admin', ['password' => $pass_hash], 'id', $userId);
            if ($result !== null) {
                // Redirigir con mensaje de éxito
                header('Location: cambiar_password.php?id=' . $userId . '&success=1');
                exit;
            } else {
                $errors[] = "Error al modificar contraseña. Intentalo nuevamente.";
            }
        } catch (Exception $e) {
            error_log('Error al actualizar contraseña de admin: ' . $e->getMessage());
            $errors[] = "Error al modificar contraseña. Intentalo nuevamente.";
        }
    }
}

// Obtener datos del usuario usando Supabase
try {
    $usuario = $db->selectOne('admin', 'id, usuario', ['id' => $userId]);
    if (!$usuario) {
        header("Location: index.php");
        exit;
    }
} catch (Exception $e) {
    error_log('Error al obtener datos de admin: ' . $e->getMessage());
    header("Location: index.php");
    exit;
}

require 'header.php';

?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Cambiar contraseña</h1>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>¡Éxito!</strong> La contraseña se ha actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php mostrarMensajes($errors); ?>

        <form action="cambiar_password.php" method="post" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" value="<?php echo htmlspecialchars($usuario['usuario'] ?? ''); ?>" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nueva contraseña" required autofocus>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="repassword" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="repassword" name="repassword" placeholder="Confirmar contraseña" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                    <a href="<?php echo ADMIN_URL; ?>inicio.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>