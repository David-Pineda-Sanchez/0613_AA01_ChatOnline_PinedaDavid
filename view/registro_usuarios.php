<?php
session_start();

// Si ya hay sesión iniciada se va a la página principal
if (isset($_SESSION['id_usuario'])) {
    header("Location: principal.php");
    exit();
}

// Recupera y borra el aviso de error del registro anterior.
$error = "";
if (isset($_SESSION['error_registro'])) {
    $error = $_SESSION['error_registro'];
    unset($_SESSION['error_registro']); // se borra para que no salga otra vez al recargar
}

// Conserva el nombre y el usuario escritos, pero nunca rellena contraseñas.
// Así se recupera el formulario sin mostrar datos sensibles de la última validación fallida.
$username = "";
$nombre_real = "";
if (isset($_SESSION['datos_registro'])) {
    $username = $_SESSION['datos_registro']['username'];
    $nombre_real = $_SESSION['datos_registro']['nombre_real'];
    unset($_SESSION['datos_registro']);
}
?>
<!-- Muestra el formulario para crear una cuenta. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsUp - Registro</title>
    <link rel="stylesheet" href="../css/login_registro.css">
</head>
<body>

    <!-- Centra el formulario y sus mensajes dentro de una tarjeta. -->
    <div class="mid">
        <div class="card">
            <span class="brand">WhatsUp</span>
            <h2>Crear cuenta</h2>
            <p class="sub">Regístrate para hablar con tus amigos</p>

            <!-- Explica qué dato debe corregirse si el registro anterior falló. -->
            <?php if ($error != "") { ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>

            <!-- Envía los datos de la nueva cuenta al proceso de registro. -->
            <form action="../procesos/registro.php" method="POST">

                <!-- Nombre corto con el que se identificará la cuenta. -->
                <div class="fld">
                    <input type="text" name="username" placeholder="Nombre de usuario" maxlength="45"
                        value="<?php echo htmlspecialchars($username); ?>" required>
                </div>

                <!-- Nombre que se mostrará en los resultados de búsqueda. -->
                <div class="fld">
                    <input type="text" name="nombre_real" placeholder="Nombre real" maxlength="100"
                        value="<?php echo htmlspecialchars($nombre_real); ?>" required>
                </div>

                <!-- Contraseña y confirmación para evitar errores al escribirla. -->
                <div class="fld">
                    <input type="password" name="contrasena" placeholder="Contraseña" required>
                </div>

                <div class="fld">
                    <input type="password" name="repetir_contrasena" placeholder="Repetir contraseña" required>
                </div>

                <div>
                    <input type="submit" name="registro" value="Registrarse" class="full">
                </div>
            </form>

            <p class="swap">¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
    </div>

</body>
</html>
