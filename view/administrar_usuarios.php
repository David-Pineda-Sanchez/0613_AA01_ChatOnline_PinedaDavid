<?php
// Cargamos la sesión, la conexión y la función que comprueba los permisos.
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
require_once '../includes/permisos.php';

// Si alguien entra directamente sin ser administrador, vuelve a la página principal.
if (!usuarioEsAdministrador($pdo, (int) $_SESSION['id_usuario'])) {
    header('Location: principal.php');
    exit();
}
// Los avisos se muestran una vez y luego se quitan de la sesión.
$error = isset($_SESSION['admin_error']) ? $_SESSION['admin_error'] : '';
$exito = isset($_SESSION['admin_exito']) ? $_SESSION['admin_exito'] : '';
unset($_SESSION['admin_error'], $_SESSION['admin_exito']);

// El token se añade a los formularios para comprobar su origen.
if (!isset($_SESSION['token_admin'])) {
    $_SESSION['token_admin'] = bin2hex(random_bytes(32));
}

// Variables para la lista, la edición y los posibles errores.
$usuarios = [];
$usuario_editar = null;
$error_carga = '';

try {
    // Traemos las cuentas que tienen un rol asignado.
    $stmt = $pdo->query(
        "SELECT u.id_usuario, u.username, u.nombre_real,
                GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
         FROM tbl_usuario u
         JOIN tbl_usuario_roles ur ON ur.id_usuario = u.id_usuario
         JOIN tbl_roles r ON r.id_rol = ur.id_rol
         GROUP BY u.id_usuario, u.username, u.nombre_real
         ORDER BY u.username"
    );
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si se ha pulsado «Modificar», cargamos los datos de esa cuenta.
    if (isset($_GET['editar']) && ctype_digit((string) $_GET['editar'])) {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, username, nombre_real FROM tbl_usuario WHERE id_usuario = :id'
        );
        $stmt->execute([':id' => (int) $_GET['editar']]);
        $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_editar) {
            $error_carga = 'El usuario seleccionado ya no existe.';
        }
    }
} catch (PDOException $e) {
    // No mostramos detalles de la base de datos en la página.
    $error_carga = 'No se pudieron cargar los usuarios.';
}
?>
<!-- Usamos los estilos generales y añadimos los de administración. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar usuarios</title>
    <link rel="stylesheet" href="../css/principal.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Desde aquí se puede volver al inicio o cerrar la sesión. -->
    <header class="bar">
        <a class="brand" href="../index.php">WhatsUp</a>
        <div class="row">
            <span class="user-greeting">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="principal.php" class="ghost">Inicio</a>
            <a href="../procesos/logout.php" class="ghost">Cerrar sesión</a>
        </div>
    </header>

    <main class="admin-page">
        <h1>Administrar usuarios</h1>
        <p class="admin-intro">Desde aquí puedes modificar los datos o eliminar una cuenta.</p>

        <!-- Avisos recibidos después de modificar o borrar una cuenta. -->
        <?php if ($error !== '') { ?>
            <p class="admin-message admin-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($exito !== '') { ?>
            <p class="admin-message admin-success" role="status"><?php echo htmlspecialchars($exito); ?></p>
        <?php } ?>
        <?php if ($error_carga !== '') { ?>
            <p class="admin-message admin-error" role="alert"><?php echo htmlspecialchars($error_carga); ?></p>
        <?php } ?>

        <!-- El formulario solo aparece al elegir una cuenta para modificar. -->
        <?php if ($usuario_editar) { ?>
            <section class="admin-card">
                <h2>Modificar usuario</h2>
                <form action="../procesos/administrar_usuarios.php" method="POST" class="admin-form">
                    <!-- El proceso comprueba estos datos antes de guardar. -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_admin']); ?>">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario_editar['id_usuario']; ?>">

                    <!-- El nombre de usuario y el nombre real son obligatorios. -->
                    <label for="username">Nombre de usuario</label>
                    <input id="username" type="text" name="username" maxlength="45" required
                        value="<?php echo htmlspecialchars($usuario_editar['username']); ?>">

                    <label for="nombre_real">Nombre real</label>
                    <input id="nombre_real" type="text" name="nombre_real" maxlength="100" required
                        value="<?php echo htmlspecialchars($usuario_editar['nombre_real']); ?>">

                    <!-- Si se deja vacía, se conserva la contraseña actual. -->
                    <label for="contrasena">Nueva contraseña</label>
                    <input id="contrasena" type="password" name="contrasena" maxlength="72" autocomplete="new-password">

                    <label for="repetir_contrasena">Repetir nueva contraseña</label>
                    <input id="repetir_contrasena" type="password" name="repetir_contrasena" maxlength="72" autocomplete="new-password">

                    <div class="admin-actions">
                        <button type="submit" class="admin-button">Guardar cambios</button>
                        <a class="admin-cancel" href="administrar_usuarios.php">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php } ?>

        <!-- Lista de cuentas y acciones disponibles. -->
        <section class="admin-card">
            <h2>Cuentas registradas</h2>
            <?php if (empty($usuarios)) { ?>
                <p>No hay usuarios registrados.</p>
            <?php } else { ?>
                <!-- En pantallas pequeñas, se puede desplazar la tabla. -->
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre real</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Mostramos una cuenta en cada fila. -->
                            <?php foreach ($usuarios as $usuario) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre_real']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['roles'] ?: 'Sin rol'); ?></td>
                                    <td class="admin-actions">
                                        <!-- Abre el formulario de edición de esta cuenta. -->
                                        <a class="admin-edit" href="administrar_usuarios.php?editar=<?php echo (int) $usuario['id_usuario']; ?>">Modificar</a>
                                        <!-- No se puede borrar la cuenta con la sesión abierta. -->
                                        <?php if ((int) $usuario['id_usuario'] !== (int) $_SESSION['id_usuario']) { ?>
                                            <form action="../procesos/administrar_usuarios.php" method="POST">
                                                <!-- Enviamos la acción, el id y el token al proceso. -->
                                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_admin']); ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id_usuario']; ?>">
                                                <button type="submit" class="admin-delete">Eliminar</button>
                                            </form>
                                        <?php } else { ?>
                                            <span class="admin-self">Tu cuenta</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </section>
    </main>
</body>
</html>
