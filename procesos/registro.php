<?php
session_start();

// Evita procesar la página si no se envió desde el formulario de registro
if (!isset($_POST['registro'])) {
    header("Location: ../view/registro_usuarios.php");
    exit();
}

// Recupera los campos de texto y limpia espacios innecesarios.
// El trim ayuda a evitar entradas con espacios sueltos antes de validarlas.
$username = "";
if (isset($_POST['username']) && is_string($_POST['username'])) {
    $username = trim($_POST['username']);
}

$nombre_real = "";
if (isset($_POST['nombre_real']) && is_string($_POST['nombre_real'])) {
    $nombre_real = trim($_POST['nombre_real']);
}

// No recorta las contraseñas: sus espacios también forman parte del valor
$contrasena = "";
if (isset($_POST['contrasena']) && is_string($_POST['contrasena'])) {
    $contrasena = $_POST['contrasena'];
}

$repetir_contrasena = "";
if (isset($_POST['repetir_contrasena']) && is_string($_POST['repetir_contrasena'])) {
    $repetir_contrasena = $_POST['repetir_contrasena'];
}

// Guarda el primer problema encontrado para explicárselo al usuario.
$error = "";

// Comprueba que los campos estén completos y cumplan las reglas acordadas.
if ($username === "" || $nombre_real === "" || $contrasena === "" || $repetir_contrasena === "") {
    $error = "Rellena todos los campos.";
} elseif (strlen($username) < 3 || strlen($username) > 45 || !preg_match('/^[A-Za-z0-9_]+$/', $username)) {

    // Solo letras sin tilde, números y _ de principio a fin
    $error = "El username debe tener entre 3 y 45 caracteres: letras, números o _.";
} elseif (strlen($nombre_real) > 100 || preg_match('/[0-9<>&;"\']/', $nombre_real)) {

    // Error si aparece algún número o símbolo prohibido
    $error = "El nombre real no puede tener números ni símbolos (máximo 100 caracteres).";

} elseif (strlen($contrasena) < 8 || strlen($contrasena) > 72) {

    // bcrypt solo usa los primeros 72 caracteres
    $error = "La contraseña debe tener entre 8 y 72 caracteres.";
} elseif (!preg_match('/[A-Z]/', $contrasena) || !preg_match('/[0-9]/', $contrasena)) {

    // Debe aparecer al menos una mayúscula y un número
    $error = "La contraseña debe tener al menos una mayúscula y un número.";
} elseif ($contrasena !== $repetir_contrasena) {
    $error = "Las contraseñas no coinciden.";
}

if ($error !== "") {
    // Devuelve el formulario con los datos no sensibles para facilitar la corrección.
    $_SESSION['error_registro'] = $error;
    $_SESSION['datos_registro'] = array(
        'username' => $username,
        'nombre_real' => $nombre_real
    );
    header("Location: ../view/registro_usuarios.php");
    exit();
}

// Conecta a la base de datos para comprobar e insertar la nueva cuenta.
require_once '../includes/conexion.php';

try {
    // Impide registrar un nombre de usuario que ya esté en uso.
    $stmt = $pdo->prepare("SELECT id_usuario FROM tbl_usuario WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->fetch()) {
        $_SESSION['error_registro'] = "Ese nombre de usuario ya está registrado.";
        $_SESSION['datos_registro'] = array(
            'username' => $username,
            'nombre_real' => $nombre_real
        );
        header("Location: ../view/registro_usuarios.php");
        exit();
    }

    // Guarda la contraseña como hash y crea el registro del nuevo usuario.
    $hash = password_hash($contrasena, PASSWORD_BCRYPT);

    $pdo->beginTransaction();
    $insert = $pdo->prepare("INSERT INTO tbl_usuario (username, nombre_real, contrasena) VALUES (:username, :nombre_real, :contrasena)");
    $insert->execute([
        ':username' => $username,
        ':nombre_real' => $nombre_real,
        ':contrasena' => $hash
    ]);

    $id_usuario = (int) $pdo->lastInsertId();
    $asignar_rol = $pdo->prepare(
        "INSERT INTO tbl_usuario_roles (id_usuario, id_rol)
         SELECT :id_usuario, id_rol FROM tbl_roles WHERE nombre = 'usuario'"
    );
    $asignar_rol->execute([':id_usuario' => $id_usuario]);
    if ($asignar_rol->rowCount() !== 1) {
        $pdo->rollBack();
        $_SESSION['error_registro'] = "No se pudo asignar el rol inicial a la cuenta.";
        header("Location: ../view/registro_usuarios.php");
        exit();
    }
    $pdo->commit();

    // Confirma el alta y permite iniciar sesión.
    $_SESSION['registro_exitoso'] = "Usuario registrado correctamente. Ya puedes iniciar sesión.";
    header("Location: ../view/login.php");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Informa del fallo sin mostrar detalles internos de la base de datos.
    $_SESSION['error_registro'] = "Error al registrar el usuario en la base de datos.";
    header("Location: ../view/registro_usuarios.php");
    exit();
}