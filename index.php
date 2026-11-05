<?php
session_start();

// Envía a cada usuario a la página que corresponde según su sesión.
// La comprobación es mínima, pero evita que alguien entre en una ruta sin estar autenticado.
if (isset($_SESSION['id_usuario'])) {
    header('Location: view/principal.php');
    exit();
} else {
    header('Location: view/login.php');
    exit();
}