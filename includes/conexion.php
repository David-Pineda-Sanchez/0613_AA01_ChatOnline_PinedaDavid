<?php
include "config.php";

try {
    // Crea la conexión PDO y activa los errores como excepciones.
    // Así cada consulta puede manejar fallos con un try/catch claro.
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detiene la página si no es posible conectar con la base de datos.
    die("Error de conexión: " . $e->getMessage());
}