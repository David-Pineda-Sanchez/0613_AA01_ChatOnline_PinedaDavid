<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';

// Prepara el usuario, el aviso de resultado y las solicitudes pendientes.
$id_usuario = $_SESSION['id_usuario'];

$mensaje = isset($_SESSION['mensaje_solicitud']) ? $_SESSION['mensaje_solicitud'] : '';
unset($_SESSION['mensaje_solicitud']);

try {
    // Busca solicitudes pendientes que otros usuarios enviaron al usuario actual.
    // El estado pendiente es el único que aparece aquí para que el usuario decida aceptar o rechazar.
    $sql = "SELECT s.id_soli_amistad, u.username 
            FROM tbl_soli_amistad s 
            JOIN tbl_usuario u ON s.id_emisor = u.id_usuario 
            WHERE s.id_receptor = :id_receptor AND s.estado = 'pendiente'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_receptor', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Muestra un aviso y evita dejar la lista sin un valor definido.
    $solicitudes = [];
    $mensaje = "Error al cargar las solicitudes.";
}
?>
<!-- Estructura la página y carga los estilos comunes y de solicitudes. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Amistad - WhatsUp</title>
    <link rel="stylesheet" href="../css/principal.css">
    <link rel="stylesheet" href="../css/solicitudes.css">
</head>
<body>

    <!-- Muestra accesos de navegación y el nombre de quien inició sesión. -->
    <header class="bar">
        <span class="brand">WhatsUp</span>
        <div class="row">
            <span class="user-greeting">Estas son tus solicitudes, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="principal.php" class="ghost">Inicio</a>
            <a href="amigos.php" class="ghost">Mis Amigos</a>
            <a href="buscar_usuarios.php" class="ghost">Buscar Usuarios</a>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <!-- Presenta el aviso de resultado y la lista de solicitudes pendientes. -->
    <main class="container-solicitudes">
        <h2>Solicitudes de Amistad Pendientes</h2>

        <!-- Informa del resultado de la última acción realizada. -->
        <?php if ($mensaje !== "") { ?>
            <p class="mensaje-solicitud"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php } ?>

        <!-- Muestra el estado vacío o permite aceptar y rechazar cada solicitud. -->
        <div class="lista-solicitudes-container">
            <?php if (empty($solicitudes)) { ?>
                <p class="vacio">No tienes ninguna solicitud de amistad pendiente.</p>
            <?php } else { ?>
                <ul class="lista-solicitudes">
                    <?php foreach ($solicitudes as $soli) { ?>
                        <li class="solicitud-item">
                            <span>Usuario: <strong><?php echo htmlspecialchars($soli['username']); ?></strong></span>
                            
                            <!-- Cada formulario envía la solicitud y la acción elegida. -->
                            <div class="acciones">
                                <form action="../procesos/procesar_solicitud.php" method="POST">
                                    <input type="hidden" name="id_soli" value="<?php echo $soli['id_soli_amistad']; ?>">
                                    <input type="hidden" name="accion" value="aceptar">
                                    <button type="submit" class="btn-aceptar">Aceptar</button>
                                </form>

                                <form action="../procesos/procesar_solicitud.php" method="POST">
                                    <input type="hidden" name="id_soli" value="<?php echo $soli['id_soli_amistad']; ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <button type="submit" class="btn-rechazar">Rechazar</button>
                                </form>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </main>

</body>
</html>