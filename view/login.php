<?php
session_start();

// Si ya hay sesión iniciada, redirigimos a principal.php
if (isset($_SESSION['id_usuario'])) {
    header("Location: principal.php");
    exit();
}

// Recupera y borra el aviso de error del último intento de acceso.
// La sesión guarda el mensaje y se limpia al mostrarse para no repetirlo al recargar.
$error = "";
if (isset($_SESSION['error_login'])) {
    $error = $_SESSION['error_login'];
    unset($_SESSION['error_login']); // se borra para que no salga otra vez al recargar
}

// Recupera y borra la confirmación de un registro completado.
$exitoso = "";
if (isset($_SESSION['registro_exitoso'])) {
    $exitoso = $_SESSION['registro_exitoso'];
    unset($_SESSION['registro_exitoso']); // se borra con unset tras mostrarlo
}
?>
<!-- Muestra el formulario de acceso y los mensajes pendientes. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsUp - Login</title>
    <link rel="stylesheet" href="../css/login_registro.css">
</head>
<body>

    <!-- Centra la tarjeta con los datos para iniciar sesión. -->
    <div class="mid">
        <div class="card">
            <span class="brand">WhatsUp</span>
            <h2>Iniciar sesión</h2>
            <p class="sub">Entra para hablar con tus amigos</p>

            <!-- Presenta los avisos una sola vez, después de volver del proceso. -->
            <?php if ($error != "") { ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>

            <?php if ($exitoso != "") { ?>
                <p class="exitoso">
                    <?php echo htmlspecialchars($exitoso); ?>
                </p>
            <?php } ?>

            <!-- Envía el nombre de usuario y la contraseña al proceso de autenticación. -->
            <form action="../procesos/autenticacion.php" method="POST">

                <!-- Identificador de la cuenta que se quiere abrir. -->
                <div class="fld">
                    <input type="text" name="username" placeholder="Nombre de usuario" maxlength="45" required>
                </div>

                <!-- Contraseña asociada a la cuenta. -->
                <div class="fld">
                    <input type="password" name="contrasena" placeholder="Contraseña" required>
                </div>

                <!-- Botón que envía las credenciales. -->
                <div>
                    <input type="submit" name="login" value="Entrar" class="full">
                </div>
            </form>

            <p class="swap">¿No tienes cuenta? <a href="registro_usuarios.php">Regístrate aquí.</a></p>
        </div>
    </div>

</body>
</html>