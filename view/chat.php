<?php
include "../includes/sesion.php";
include "../includes/conexion.php";
include "../includes/contador_solicitudes.php";

// Lee al usuario actual y valida el identificador de su amigo
$id_usuario = $_SESSION["id_usuario"];
$id_amigo = 0;

// Acepta solo un ID numérico y evita abrir un chat con el propio usuario.
if (isset($_GET["id"]) && is_numeric($_GET["id"])){
    $id_amigo = (int)$_GET["id"];
}

if (($id_amigo == 0) || ($id_amigo == $id_usuario)){
    header("Location: amigos.php");
    exit();
}

// Prepara el nombre y el historial antes de consultar la base de datos
$nombre_amigo = "";
$mensajes = [];
$error_chat = isset($_SESSION['error_chat']) ? $_SESSION['error_chat'] : '';
unset($_SESSION['error_chat']);

try {
    // Confirma que ambos usuarios sean amigos antes de abrir el chat.
    // La condición se escribe en ambos sentidos para aceptar la relación sin importar quién la inició.
    $stmt = $pdo->prepare("SELECT id_soli_amistad FROM tbl_soli_amistad WHERE estado = 'aceptada' AND ((id_emisor = :e1 AND id_receptor = :r1) OR (id_emisor = :r2 AND id_receptor = :e2))");
    $stmt->execute([
        ':e1' => $id_usuario,
        ':r1' => $id_amigo,
        ':r2' => $id_amigo,
        ':e2' => $id_usuario
    ]);
    $amistad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$amistad) {
        header("Location: amigos.php");
        exit();
    }

    // Obtiene el nombre de la persona que aparece en el encabezado.
    $stmt = $pdo->prepare("SELECT username FROM tbl_usuario WHERE id_usuario = :id");
    $stmt->execute([':id' => $id_amigo]);
    $amigo = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_amigo = $amigo['username'];

    // Carga los mensajes de ambos participantes en orden de envío.
    $stmt = $pdo->prepare("SELECT u.username, c.mensaje, c.tipo_contenido, c.archivo_url, c.fecha_envio, c.id_emisor
                           FROM tbl_chats c
                           JOIN tbl_usuario u ON u.id_usuario = c.id_emisor
                           WHERE (c.id_emisor = :emisor1 AND c.id_receptor = :receptor1)
                              OR (c.id_emisor = :emisor2 AND c.id_receptor = :receptor2)
                            ORDER BY c.fecha_envio ASC, c.id_chats ASC");
    $stmt->execute([
        ':emisor1' => $id_usuario,
        ':receptor1' => $id_amigo,
        ':emisor2' => $id_amigo,
        ':receptor2' => $id_usuario
    ]);
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la conversación no puede cargarse, vuelve a la lista de amigos.
    header("Location: amigos.php");
    exit();
}
?>

<!-- Estructura de la conversación con navegación, historial y formulario. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?php echo htmlspecialchars($nombre_amigo); ?> - WhatsUp</title>
    <link rel="stylesheet" href="../css/principal.css">
    <link rel="stylesheet" href="../css/chat.css">
</head>
<body>

    <!-- Barra de navegación común para moverse entre secciones -->
    <header class="bar">
        <a class="brand" href="principal.php">WhatsUp</a>
        <div class="row">
            <span class="user-greeting">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="principal.php" class="ghost">Inicio</a>
            <a href="amigos.php" class="ghost">Mis Amigos</a>
            <a href="buscar_usuarios.php" class="ghost">Buscar Usuarios</a>
            <a href="solicitudes.php" class="ghost">Ver Solicitudes<?php if ($num_solicitudes > 0) { echo " (" . $num_solicitudes . ")"; } ?></a>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <!-- Agrupa el título, los mensajes y el formulario de envío. -->
    <div class="chat-container">
        <h2 class="chat-titulo">Chat con <?php echo htmlspecialchars($nombre_amigo); ?></h2>

        <?php if ($error_chat !== '') { ?>
            <p class="chat-error"><?php echo htmlspecialchars($error_chat); ?></p>
        <?php } ?>

        <!-- Historial de mensajes o aviso si todavía no hay ninguno. -->
        <div class="chat-mensajes">
            <?php if (empty($mensajes)) { ?>
                <p class="chat-vacio">Todavía no hay mensajes</p>
            <?php } ?>

            <!-- Distingue los mensajes propios de los que envió el amigo. -->
            <!-- El mismo historial se reutiliza para ambos lados, así que se decide la alineación según el emisor. -->
            <?php foreach ($mensajes as $mensaje) { ?>
                <?php if ($mensaje['id_emisor'] == $id_usuario) { ?>
                    <div class="mensaje mensaje-propio">
                <?php } else { ?>
                    <div class="mensaje mensaje-amigo">
                <?php } ?>
                    <span class="mensaje-emisor"><strong><?php echo htmlspecialchars($mensaje['username']); ?></strong></span>
                    <?php if ($mensaje['archivo_url'] !== null) { ?>
                        <img class="mensaje-imagen" src="../<?php echo htmlspecialchars($mensaje['archivo_url']); ?>" alt="Imagen enviada por <?php echo htmlspecialchars($mensaje['username']); ?>" loading="lazy">
                    <?php } ?>
                    <?php if ($mensaje['mensaje'] !== null && $mensaje['mensaje'] !== '') { ?>
                        <p class="mensaje-texto"><?php echo htmlspecialchars($mensaje['mensaje']); ?></p>
                    <?php } ?>
                    <span class="mensaje-fecha"><?php echo htmlspecialchars($mensaje['fecha_envio']); ?></span>
                </div>
            <?php } ?>
        </div>

        <!-- Envía un mensaje nuevo al destinatario de este chat -->
        <form action="../procesos/enviar_mensaje.php" method="POST" class="chat-form" enctype="multipart/form-data">
            <input type="hidden" name="id_receptor" value="<?php echo $id_amigo; ?>">
            <input type="text" name="mensaje" class="chat-input" placeholder="Escribe un mensaje" maxlength="250" autocomplete="off">
            <label class="chat-imagen-label">
                <!-- Botón de seleccionar un archivo -->
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp">
            </label>
            <button type="submit" class="chat-boton">Enviar</button>
        </form>
    </div>

</body>
</html>