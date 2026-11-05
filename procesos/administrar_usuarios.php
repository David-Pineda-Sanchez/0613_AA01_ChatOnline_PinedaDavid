<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
require_once '../includes/permisos.php';

// Comprobamos el rol también aquí, no solo al mostrar la página.
if (!usuarioEsAdministrador($pdo, (int) $_SESSION['id_usuario'])) {
    header('Location: ../view/principal.php');
    exit();
}

// Este proceso se usa desde los formularios de administración.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/administrar_usuarios.php');
    exit();
}

// Comprobamos el id antes de usarlo en las consultas.
$id_usuario = 0;
if (isset($_POST['id_usuario']) && is_string($_POST['id_usuario'])
    && ctype_digit($_POST['id_usuario'])) {
    $id_usuario = (int) $_POST['id_usuario'];
}

// Al modificar, volveremos al formulario de la misma cuenta.
$accion = isset($_POST['accion']) && is_string($_POST['accion']) ? $_POST['accion'] : '';
$destino = '../view/administrar_usuarios.php';
if ($accion === 'editar' && $id_usuario > 0) {
    $destino .= '?editar=' . $id_usuario;
}

// El token confirma que el formulario se ha enviado desde nuestra página.
if (!isset($_POST['token'], $_SESSION['token_admin'])
    || !is_string($_POST['token'])
    || !hash_equals($_SESSION['token_admin'], $_POST['token'])) {
    $_SESSION['admin_error'] = 'La sesión del formulario ha caducado. Inténtalo de nuevo.';
    header('Location: ' . $destino);
    exit();
}

if ($accion === 'editar') {
    // Recogemos los datos del formulario.
    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username']) : '';
    $nombre_real = isset($_POST['nombre_real']) && is_string($_POST['nombre_real'])
        ? trim($_POST['nombre_real']) : '';
    $contrasena = isset($_POST['contrasena']) && is_string($_POST['contrasena'])
        ? $_POST['contrasena'] : '';
    $repetir_contrasena = isset($_POST['repetir_contrasena']) && is_string($_POST['repetir_contrasena'])
        ? $_POST['repetir_contrasena'] : '';

    // El username solo puede llevar letras, números y guion bajo.
    $username_valido = true;
    for ($i = 0; $i < strlen($username); $i++) {
        $caracter = $username[$i];
        if (!ctype_alnum($caracter) && $caracter !== '_') {
            $username_valido = false;
            break;
        }
    }

    // Aplicamos las mismas reglas para el nombre real que en el registro.
    $nombre_valido = true;
    $simbolos_no_permitidos = '<>&;"\'';
    for ($i = 0; $i < strlen($nombre_real); $i++) {
        $caracter = $nombre_real[$i];
        if (ctype_digit($caracter) || strpos($simbolos_no_permitidos, $caracter) !== false) {
            $nombre_valido = false;
            break;
        }
    }

    // Revisamos los campos antes de guardarlos.
    if ($id_usuario <= 0 || $username === '' || $nombre_real === '') {
        $_SESSION['admin_error'] = 'Rellena todos los campos obligatorios.';
    } elseif (strlen($username) < 3 || strlen($username) > 45 || !$username_valido) {
        $_SESSION['admin_error'] = 'El usuario debe tener entre 3 y 45 caracteres: letras, números o _.';
    } elseif (strlen($nombre_real) > 100 || !$nombre_valido) {
        $_SESSION['admin_error'] = 'El nombre real no puede tener números ni símbolos (máximo 100 caracteres).';
    } elseif ($contrasena !== '' && (strlen($contrasena) < 8 || strlen($contrasena) > 72)) {
        $_SESSION['admin_error'] = 'La nueva contraseña debe tener entre 8 y 72 caracteres.';
    } elseif ($contrasena !== '' && $contrasena !== $repetir_contrasena) {
        $_SESSION['admin_error'] = 'Las contraseñas no coinciden.';
    } else {
        // La contraseña debe tener una mayúscula y un número.
        $tiene_mayuscula = false;
        $tiene_numero = false;
        for ($i = 0; $i < strlen($contrasena); $i++) {
            if (ctype_upper($contrasena[$i])) {
                $tiene_mayuscula = true;
            }
            if (ctype_digit($contrasena[$i])) {
                $tiene_numero = true;
            }
        }

        if ($contrasena !== '' && (!$tiene_mayuscula || !$tiene_numero)) {
            $_SESSION['admin_error'] = 'La contraseña debe tener al menos una mayúscula y un número.';
        } else {
            try {
                // Si no se escribe una contraseña nueva, dejamos la que ya tiene.
                if ($contrasena === '') {
                    $stmt = $pdo->prepare(
                        'UPDATE tbl_usuario
                         SET username = :username, nombre_real = :nombre_real
                         WHERE id_usuario = :id'
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':nombre_real' => $nombre_real,
                        ':id' => $id_usuario
                    ]);
                } else {
                    // Guardamos la contraseña cifrada, igual que al registrarse.
                    $hash = password_hash($contrasena, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare(
                        'UPDATE tbl_usuario
                         SET username = :username, nombre_real = :nombre_real, contrasena = :contrasena
                         WHERE id_usuario = :id'
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':nombre_real' => $nombre_real,
                        ':contrasena' => $hash,
                        ':id' => $id_usuario
                    ]);
                }

                // Si hemos cambiado nuestro username, actualizamos el saludo de la sesión.
                if ($id_usuario === (int) $_SESSION['id_usuario']) {
                    $_SESSION['username'] = $username;
                }
                $_SESSION['admin_exito'] = 'Los datos del usuario se han actualizado.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $_SESSION['admin_error'] = 'Ese nombre de usuario ya está registrado.';
                } else {
                    error_log('Error al modificar un usuario desde administración: ' . $e->getMessage());
                    $_SESSION['admin_error'] = 'No se pudieron guardar los cambios.';
                }
            }
        }
    }

    header('Location: ' . $destino);
    exit();
}

if ($accion === 'eliminar') {
    // No dejamos borrar la cuenta con la que se ha iniciado sesión.
    if ($id_usuario <= 0) {
        $_SESSION['admin_error'] = 'El usuario seleccionado no es válido.';
        header('Location: ' . $destino);
        exit();
    }
    if ($id_usuario === (int) $_SESSION['id_usuario']) {
        $_SESSION['admin_error'] = 'No puedes eliminar la cuenta con la que has iniciado sesión.';
        header('Location: ' . $destino);
        exit();
    }

    try {
        // Si algo falla durante el borrado, podremos deshacer todos los cambios.
        $pdo->beginTransaction();

        // Guardamos las rutas para borrar también las imágenes al final.
        $stmt = $pdo->prepare(
            'SELECT archivo_url FROM tbl_chats WHERE id_emisor = :emisor OR id_receptor = :receptor'
        );
        $stmt->execute([':emisor' => $id_usuario, ':receptor' => $id_usuario]);
        $archivos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Quitamos primero las relaciones; la cuenta se borra al final.
        $stmt = $pdo->prepare('DELETE FROM tbl_usuario_roles WHERE id_usuario = :id');
        $stmt->execute([':id' => $id_usuario]);

        $stmt = $pdo->prepare(
            'DELETE FROM tbl_soli_amistad WHERE id_emisor = :emisor OR id_receptor = :receptor'
        );
        $stmt->execute([':emisor' => $id_usuario, ':receptor' => $id_usuario]);

        $stmt = $pdo->prepare(
            'DELETE FROM tbl_chats WHERE id_emisor = :emisor OR id_receptor = :receptor'
        );
        $stmt->execute([':emisor' => $id_usuario, ':receptor' => $id_usuario]);

        $stmt = $pdo->prepare('DELETE FROM tbl_usuario WHERE id_usuario = :id');
        $stmt->execute([':id' => $id_usuario]);

        // Si el usuario ya no existe, no damos el borrado por hecho.
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            $_SESSION['admin_error'] = 'El usuario seleccionado ya no existe.';
            header('Location: ' . $destino);
            exit();
        }

        $pdo->commit();

        // Borramos los archivos asociados a los mensajes eliminados.
        $directorio_uploads = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
        foreach (array_unique($archivos) as $archivo) {
            if (!is_string($archivo) || strpos($archivo, 'uploads/') !== 0) {
                continue;
            }

            $nombre_archivo = substr($archivo, strlen('uploads/'));
            if ($nombre_archivo === '' || basename($nombre_archivo) !== $nombre_archivo) {
                continue;
            }

            $ruta_archivo = $directorio_uploads . DIRECTORY_SEPARATOR . $nombre_archivo;
            if (is_file($ruta_archivo) && !unlink($ruta_archivo)) {
                error_log('No se pudo eliminar una imagen de un chat borrado: ' . $ruta_archivo);
            }
        }

        $_SESSION['admin_exito'] = 'El usuario, sus amistades y sus mensajes se han eliminado.';
    } catch (PDOException $e) {
        // Si falla una consulta, cancelamos la transacción para no dejar datos a medias.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error al eliminar un usuario desde administración: ' . $e->getMessage());
        $_SESSION['admin_error'] = 'No se pudo eliminar el usuario.';
    }

    header('Location: ' . $destino);
    exit();
}

// Solo se aceptan las acciones de editar y eliminar.
$_SESSION['admin_error'] = 'La acción solicitada no es válida.';
header('Location: ' . $destino);
exit();
