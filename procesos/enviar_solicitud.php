<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/login.php");
    exit();
}

// Carga la conexión y prepara quién envía y quién recibe la solicitud.
require_once '../includes/conexion.php';

$id_emisor = (int) $_SESSION['id_usuario'];

$id_receptor = 0;
if (isset($_POST['id_receptor']) && is_numeric($_POST['id_receptor'])) {
    $id_receptor = (int) $_POST['id_receptor'];
}

// Conserva el texto buscado para volver a mostrar los mismos resultados.
$q = "";
if (isset($_POST['q']) && is_string($_POST['q'])) {
    $q = trim($_POST['q']);
}

$error = "";
$ok = "";

// Comprueba que el destinatario sea válido y distinto del remitente.
// La validación previa evita crear relaciones con usuarios inexistentes o el propio perfil.
if ($id_receptor <= 0) {
    $error = "Usuario no válido.";
} elseif ($id_receptor === $id_emisor) {
    $error = "No puedes enviarte una solicitud a ti mismo.";
} else {
    try {
        // Verifica que la cuenta destinataria exista.
        $stmt = $pdo->prepare("SELECT id_usuario FROM tbl_usuario WHERE id_usuario = :id");
        $stmt->bindParam(':id', $id_receptor, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            $error = "El usuario no existe.";
        } else {
            // Evita duplicar una solicitud o una amistad en cualquier sentido.
            $stmt = $pdo->prepare("SELECT id_soli_amistad FROM tbl_soli_amistad WHERE (id_emisor = :e1 AND id_receptor = :r1) OR (id_emisor = :r2 AND id_receptor = :e2)");
            $stmt->bindParam(':e1', $id_emisor, PDO::PARAM_INT);
            $stmt->bindParam(':r1', $id_receptor, PDO::PARAM_INT);
            $stmt->bindParam(':r2', $id_receptor, PDO::PARAM_INT);
            $stmt->bindParam(':e2', $id_emisor, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetch()) {
                $error = "Ya existe una solicitud o una amistad con este usuario.";
            } else {
                // Crea la solicitud; la base de datos la deja pendiente por defecto.
                $stmt = $pdo->prepare("INSERT INTO tbl_soli_amistad (id_emisor, id_receptor) VALUES (:emisor, :receptor)");
                $stmt->bindParam(':emisor', $id_emisor, PDO::PARAM_INT);
                $stmt->bindParam(':receptor', $id_receptor, PDO::PARAM_INT);
                $stmt->execute();

                $ok = "Solicitud enviada correctamente.";
            }
        }
    } catch (PDOException $e) {
        $error = "No se ha podido enviar la solicitud, inténtalo de nuevo.";
    }
}

// Guarda el resultado en la sesión para mostrarlo al volver a la búsqueda.
if ($error !== "") {
    $_SESSION['error_amistad'] = $error;
} else {
    $_SESSION['ok_amistad'] = $ok;
}

// Vuelve a la búsqueda conservando el texto aunque tenga espacios o símbolos.
header("Location: ../view/buscar_usuarios.php?q=" . urlencode($q));
exit();
