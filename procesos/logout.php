<?php
session_start();

// Elimina los datos de sesión guardados en el servidor.
// Se destruye la sesión para cerrar toda la identidad de usuario actual.
session_destroy();

// Devuelve al usuario al formulario de acceso.
header("Location: ../view/login.php");
exit();