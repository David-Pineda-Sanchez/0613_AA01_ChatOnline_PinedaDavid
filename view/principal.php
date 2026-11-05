<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
require_once '../includes/contador_solicitudes.php';
require_once '../includes/permisos.php';

// Prepara los datos que la página mostrará debajo.
$chats_recientes = [];
$error_chats = '';
$id_usuario = (int) $_SESSION['id_usuario'];
$es_administrador = false;
$error_permisos = '';

try {
    $es_administrador = usuarioEsAdministrador($pdo, $id_usuario);
} catch (PDOException $e) {
    $error_permisos = 'No se pudo comprobar el acceso de administrador.';
}

try {
    // Busca el último mensaje de cada amigo con quien ya se haya chateado.
    // La subconsulta elimina mensajes antiguos para dejar solo la conversación más reciente por contacto.
    $stmt = $pdo->prepare(
        "SELECT u.id_usuario, u.username, c.fecha_envio
         FROM tbl_chats c
         -- Identifica al otro participante de cada conversación.
         JOIN tbl_usuario u
           ON u.id_usuario = CASE
               WHEN c.id_emisor = :contact_sender THEN c.id_receptor
               ELSE c.id_emisor
           END
         -- Limita los resultados a conversaciones del usuario actual.
         WHERE (c.id_emisor = :current_sender OR c.id_receptor = :current_receiver)
           -- Solo incluye amigos cuya solicitud fue aceptada.
           AND EXISTS (
               SELECT 1
               FROM tbl_soli_amistad s
               WHERE s.estado = 'aceptada'
                 AND (
                     (s.id_emisor = :friendship_sender AND s.id_receptor = u.id_usuario)
                     OR (s.id_emisor = u.id_usuario AND s.id_receptor = :friendship_receiver)
                 )
           )
           -- Descarta mensajes antiguos para conservar solo el más reciente por amigo.
           AND NOT EXISTS (
               SELECT 1
               FROM tbl_chats newer
               WHERE (
                   (newer.id_emisor = :newer_sender AND newer.id_receptor = u.id_usuario)
                   OR (newer.id_emisor = u.id_usuario AND newer.id_receptor = :newer_receiver)
               )
                 AND (
                     newer.fecha_envio > c.fecha_envio
                     OR (newer.fecha_envio = c.fecha_envio AND newer.id_chats > c.id_chats)
                 )
           )
         -- Muestra primero la conversación con actividad más reciente.
         ORDER BY c.fecha_envio DESC, c.id_chats DESC"
    );
    // Asigna el usuario actual a cada condición parametrizada de la consulta.
    $stmt->execute([
        ':contact_sender' => $id_usuario,
        ':current_sender' => $id_usuario,
        ':current_receiver' => $id_usuario,
        ':friendship_sender' => $id_usuario,
        ':friendship_receiver' => $id_usuario,
        ':newer_sender' => $id_usuario,
        ':newer_receiver' => $id_usuario,
    ]);
    // Guarda todas las conversaciones encontradas para mostrarlas en la página.
    $chats_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Informa del problema sin mostrar detalles internos de la base de datos.
    $error_chats = 'No se pudieron cargar tus chats recientes. Inténtalo de nuevo más tarde.';
}
?>
<!DOCTYPE html>
<!-- Define el idioma y la estructura básica de la página principal. -->
<html lang="es">
<head>
    <!-- Configura la codificación, adaptación a móviles, título y estilos. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsUp - Principal</title>
    <link rel="stylesheet" href="../css/principal.css">
</head>
<body>

    <!-- Barra con el acceso a las secciones principales y el cierre de sesión. -->
    <header class="bar">
        <a class="brand" href="../index.php">WhatsUp</a>
        <div class="row">
            <!-- Saluda al usuario; htmlspecialchars evita interpretar texto como HTML. -->
            <span class="user-greeting">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="amigos.php" class="ghost">Mis Amigos</a>
            <a href="buscar_usuarios.php" class="ghost">Buscar Usuarios</a>
            <a href="solicitudes.php" class="ghost">Ver Solicitudes<?php if ($num_solicitudes > 0) { echo " (" . $num_solicitudes . ")"; } ?></a>
            <?php if ($es_administrador) { ?>
                <a href="administrar_usuarios.php" class="ghost">Administrar usuarios</a>
            <?php } ?>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <!-- Contenido principal: bienvenida y lista de chats recientes. -->
    <main class="principal">
        <!-- Presenta al usuario y le invita a retomar sus conversaciones. -->
        <section class="bienvenida">
            <h1>Hola, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <p class="sub">Retoma tus conversaciones y mantente cerca de tus amigos.</p>
        </section>

        <?php if ($error_permisos !== '') { ?>
            <p class="mensaje-estado error-chats" role="alert"><?php echo htmlspecialchars($error_permisos); ?></p>
        <?php } ?>

        <!-- La sección muestra errores, el estado vacío o las conversaciones. -->
        <section class="seccion-chats" aria-labelledby="titulo-chats">
            <div class="encabezado-chats">
                <h2 id="titulo-chats">Chats recientes</h2>
            </div>

            <!-- Avisa si hubo un problema al consultar los chats. -->
            <?php if ($error_chats !== '') { ?>
                <p class="mensaje-estado error-chats" role="alert"><?php echo htmlspecialchars($error_chats); ?></p>
            <!-- Explica por qué la lista está vacía cuando aún no hay conversaciones. -->
            <?php } elseif (empty($chats_recientes)) { ?>
                <div class="mensaje-estado estado-vacio">
                    <h3>Aún no tienes conversaciones</h3>
                    <p>Cuando chatees con uno de tus amigos, aparecerá aquí.</p>
                </div>
            <?php } else { ?>
                <!-- Recorre los chats recientes y crea un acceso para cada amigo. -->
                <ul class="lista-chats">
                    <?php foreach ($chats_recientes as $chat) { ?>
                        <li>
                            <!-- Abre la conversación correspondiente al seleccionar la fila. -->
                            <a class="chat-reciente" href="chat.php?id=<?php echo (int) $chat['id_usuario']; ?>">
                                <span class="datos-chat">
                                    <!-- Muestra el nombre del amigo de forma segura. -->
                                    <span class="nombre-chat"><?php echo htmlspecialchars($chat['username']); ?></span>
                                </span>
                                <!-- Formatea la fecha y hora del último mensaje. -->
                                <span class="fecha-chat"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chat['fecha_envio']))); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>
    </main>

</body>
</html>