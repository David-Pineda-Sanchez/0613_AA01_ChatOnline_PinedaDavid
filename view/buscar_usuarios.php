<?php
include "../includes/sesion.php";
include "../includes/conexion.php";
include "../includes/contador_solicitudes.php";

// Recupera el texto que se quiere buscar y prepara los resultados.
$q = "";
if (isset($_GET["q"])) {
    $q = trim($_GET["q"]);
}

$resultados = [];
$error_busqueda = "";

// Lee y borra los avisos guardados al enviar una solicitud.
$ok_amistad = "";
if (isset($_SESSION['ok_amistad'])) {
    $ok_amistad = $_SESSION['ok_amistad'];
    unset($_SESSION['ok_amistad']);
}

$error_amistad = "";
if (isset($_SESSION['error_amistad'])) {
    $error_amistad = $_SESSION['error_amistad'];
    unset($_SESSION['error_amistad']);
}

// Busca coincidencias y averigua si ya existe una relación con cada persona.
if ($q !== "") {
    $id_usuario = $_SESSION['id_usuario'];

    try {
        $busqueda = "%" . $q . "%";
        // Busca por nombre de usuario o nombre real, excluyendo al usuario actual.
        $stmt = $pdo->prepare("SELECT id_usuario, username, nombre_real FROM tbl_usuario WHERE (username LIKE :q1 OR nombre_real LIKE :q2) AND id_usuario != :id");
        $stmt->execute([
            ':q1' => $busqueda,
            ':q2' => $busqueda,
            ':id' => $id_usuario
        ]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as $clave => $usuario) {
            $id_otro = $usuario['id_usuario'];

            // Comprueba solicitudes en ambos sentidos para decidir qué acción mostrar.
            // Este estado define si debe mostrarse el botón de amistad o un texto informativo.
            $stmt = $pdo->prepare("SELECT id_emisor, estado FROM tbl_soli_amistad WHERE (id_emisor = :e1 AND id_receptor = :r1) OR (id_emisor = :r2 AND id_receptor = :e2)");
            $stmt->execute([
                ':e1' => $id_usuario,
                ':r1' => $id_otro,
                ':r2' => $id_otro,
                ':e2' => $id_usuario
            ]);
            $soli = $stmt->fetch(PDO::FETCH_ASSOC);

            // Convierte el estado de amistad en un texto comprensible para la página.
            $texto = "";
            if ($soli) {
                if ($soli['estado'] === 'aceptada') {
                    $texto = "Ya sois amigos";
                } elseif ($soli['estado'] === 'rechazada') {
                    $texto = "Solicitud rechazada";
                } elseif ($soli['id_emisor'] == $id_usuario) {
                    $texto = "Solicitud enviada";
                } else {
                    $texto = "Te ha enviado una solicitud";
                }
            }
            $resultados[$clave]['boton'] = $texto;
        }
    } catch (PDOException $e) {
        // Muestra un aviso sin exponer detalles de la consulta.
        $error_busqueda = "Error en la búsqueda.";
    }
}
?>
<!-- Define el idioma y prepara la página con sus estilos. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda de usuarios - WhatsUp</title>
    <link rel="stylesheet" href="../css/principal.css">
    <link rel="stylesheet" href="../css/buscador.css">
</head>
<body>

    <!-- Enlaces para volver a las otras secciones de la aplicación. -->
    <header class="bar">
        <a class="brand" href="principal.php">WhatsUp</a>
        <div class="row">
            <span class="user-greeting">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="principal.php" class="ghost">Inicio</a>
            <a href="amigos.php" class="ghost">Mis Amigos</a>
            <a href="solicitudes.php" class="ghost">Ver Solicitudes<?php if ($num_solicitudes > 0) { echo " (" . $num_solicitudes . ")"; } ?></a>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <!-- Formulario de búsqueda y resultados disponibles. -->
    <div class="buscador">
        <h2>Buscar usuarios</h2>

        <!-- Envía la búsqueda por GET para conservarla en la URL. -->
        <form class="buscador-form" action="buscar_usuarios.php" method="GET">
            <input class="buscador-input" type="text" name="q" placeholder="Buscar por usuario o nombre" value="<?php echo htmlspecialchars($q); ?>">
            <input class="buscador-boton" type="submit" value="Buscar">
        </form>

        <!-- Muestra los mensajes guardados tras intentar enviar una solicitud. -->
        <?php if ($ok_amistad !== "") { ?>
            <p class="mensaje-ok"><?php echo htmlspecialchars($ok_amistad); ?></p>
        <?php } ?>

        <?php if ($error_amistad !== "") { ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($error_amistad); ?></p>
        <?php } ?>

        <?php if ($error_busqueda !== "") { ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($error_busqueda); ?></p>
        <?php } ?>

        <?php if ($q !== "" && $error_busqueda === "" && empty($resultados)) { ?>
            <p class="mensaje-vacio">No se han encontrado usuarios</p>
        <?php } ?>

        <!-- Presenta cada coincidencia y permite solicitar amistad si procede. -->
        <?php foreach ($resultados as $usuario) { ?>
            <div class="resultado">
                <div class="resultado-datos">
                    <span class="resultado-username"><?php echo htmlspecialchars($usuario['username']); ?></span>
                    <span class="resultado-nombre"><?php echo htmlspecialchars($usuario['nombre_real']); ?></span>
                </div>
                <?php if ($usuario['boton'] === "") { ?>
                    <!-- Envía la solicitud y conserva el texto de búsqueda. -->
                    <form action="../procesos/enviar_solicitud.php" method="POST">
                        <input type="hidden" name="id_receptor" value="<?php echo $usuario['id_usuario']; ?>">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                        <input class="resultado-boton" type="submit" value="Enviar solicitud">
                    </form>
                <?php } else { ?>
                    <!-- Informa del estado cuando ya hay una solicitud o amistad. -->
                    <input class="resultado-boton resultado-boton-off" type="button" value="<?php echo $usuario['boton']; ?>" disabled>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>