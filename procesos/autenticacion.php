<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/login.php");
    exit();
}

// Carga la conexión para comprobar las credenciales guardadas.
require_once '../includes/conexion.php';

// Lee los campos como texto y limpia espacios del nombre de usuario.
$username = "";
if (isset($_POST['username']) && is_string($_POST['username'])) {
    $username = trim($_POST['username']);
}

$contrasena = "";
if (isset($_POST['contrasena']) && is_string($_POST['contrasena'])) {
    $contrasena = $_POST['contrasena'];
}

// Devuelve al formulario si falta algún dato o el tamaño no es válido.
if ($username === "" || $contrasena === "") {
    $_SESSION['error_login'] = "Debes rellenar todos los campos.";
    header("Location: ../view/login.php");
    exit();
}

if (strlen($username) > 45 || strlen($contrasena) > 72) {
    $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";
    header("Location: ../view/login.php");
    exit();
}

// Busca la cuenta y verifica la contraseña contra su hash.
try {
    $sql = "SELECT id_usuario, username, contrasena FROM tbl_usuario WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Guarda la identidad del usuario y abre la página principal si coincide.
    // La comparación con password_verify evita guardar la contraseña en texto plano.
    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['username'];

        header("Location: ../view/principal.php");
        exit();
    } else {
        // Muestra un mensaje genérico si el usuario o la contraseña no coinciden.
        $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";
        header("Location: ../view/login.php");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error_login'] = "Error en el servidor al iniciar sesión.";
    header("Location: ../view/login.php");
    exit();
}