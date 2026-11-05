<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
require_once '../includes/contador_solicitudes.php';

// Prepara la lista de amigos y el aviso para posibles errores.
$id_usuario = $_SESSION['id_usuario'];
$amigos = [];
$error = "";

try {
    // Busca solicitudes aceptadas en las que participe el usuario actual.
    // La comparación en ambos sentidos permite encontrar al otro usuario sin importar quién la inició.
    $sql = "SELECT u.id_usuario, u.username 
            FROM tbl_soli_amistad s 
            JOIN tbl_usuario u ON (u.id_usuario = s.id_emisor OR u.id_usuario = s.id_receptor)
            WHERE (s.id_emisor = :id_usuario1 OR s.id_receptor = :id_usuario2) 
              AND s.estado = 'aceptada' 
              AND u.id_usuario != :id_usuario3";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_usuario1', $id_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':id_usuario2', $id_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':id_usuario3', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Guarda un mensaje sencillo si no se puede cargar la lista.
    $error = "Error al cargar la lista de amigos.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Amigos - ChatOnline</title>
    <link rel="stylesheet" href="../css/principal.css">
    <link rel="stylesheet" href="../css/amigos.css">
</head>
<body>

    <!-- Mantiene los enlaces de navegación de la aplicación -->
    <header class="bar">
        <span class="brand">WhatsUp</span>
        <div class="row">
            <span class="user-greeting">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="principal.php" class="ghost">Inicio</a>
            <a href="buscar_usuarios.php" class="ghost">Buscar Usuarios</a>
            <a href="solicitudes.php" class="ghost">Ver Solicitudes<?php if ($num_solicitudes > 0) { echo " (" . $num_solicitudes . ")"; } ?></a>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <!-- Muestra los amigos encontrados o el estado vacío. -->
    <main class="container-amigos">
        <h2>Lista de Amigos</h2>

        <!-- Informa si la consulta de amigos ha fallado. -->
        <?php if ($error !== "") { ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <!-- Si hay amistades, crea una fila con acceso al chat para cada una. -->
        <?php if (empty($amigos)) { ?>
            <p class="vacio">Aún no tienes amigos agregados.</p>
        <?php } else { ?>
            <ul class="lista-amigos">
                <?php foreach ($amigos as $amigo) { ?>
                    <li class="amigo-item">
                        <span><strong><?php echo htmlspecialchars($amigo['username']); ?></strong></span>
                        <a href="chat.php?id=<?php echo $amigo['id_usuario']; ?>" class="btn-chat">Abrir Chat</a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </main>

</body>
</html>