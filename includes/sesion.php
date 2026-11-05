<?php
session_start();

// Si no hay un usuario identificado, vuelve al login y detiene la página.
// Esto evita que una vista protegida se abra sin autenticación.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php");
    exit();
}
?>