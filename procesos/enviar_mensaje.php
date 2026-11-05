<?php
session_start();

// Solo se aceptan envíos del formulario de chat.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/amigos.php");
    exit();
}

// Solo los usuarios identificados pueden escribir.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php");
    exit();
}

// Conecta a la base de datos y prepara los datos del remitente y destinatario.
require_once '../includes/conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$id_receptor = isset($_POST['id_receptor']) ? (int)$_POST['id_receptor'] : 0;
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
$archivo = isset($_FILES['imagen']) ? $_FILES['imagen'] : null;
$subida_imagen = $archivo !== null && $archivo['error'] !== UPLOAD_ERR_NO_FILE;

// Rechaza destinatarios inválidos y mensajes demasiado largos.
// Aquí se corta antes de guardar datos que no cumplen el formato esperado.
if ($id_receptor <= 0 || mb_strlen($mensaje) > 250) {
    header("Location: ../view/amigos.php");
    exit();
}

if ($mensaje === '' && !$subida_imagen) {
    $_SESSION['error_chat'] = 'Escribe un mensaje o selecciona una imagen.';
    header("Location: ../view/chat.php?id=" . $id_receptor);
    exit();
}

$url_imagen = null;
$tipo_contenido = 'texto';
$ruta_archivo = null;
$tamano_maximo = 5 * 1024 * 1024;

if ($subida_imagen) {
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_chat'] = 'No se pudo subir la imagen. Inténtalo de nuevo.';
        header("Location: ../view/chat.php?id=" . $id_receptor);
        exit();
    }

    if ($archivo['size'] > $tamano_maximo) {
        $_SESSION['error_chat'] = 'La imagen no puede superar los 5 MB.';
        header("Location: ../view/chat.php?id=" . $id_receptor);
        exit();
    }

    $tipos_permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($tipos_permitidos[$mime]) || getimagesize($archivo['tmp_name']) === false) {
        $_SESSION['error_chat'] = 'El archivo debe ser una imagen JPG, PNG, GIF o WEBP válida.';
        header("Location: ../view/chat.php?id=" . $id_receptor);
        exit();
    }

    $directorio_uploads = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($directorio_uploads) || !is_writable($directorio_uploads)) {
        $_SESSION['error_chat'] = 'No se pueden guardar imágenes en este momento.';
        header("Location: ../view/chat.php?id=" . $id_receptor);
        exit();
    }

    $nombre_archivo = bin2hex(random_bytes(16)) . '.' . $tipos_permitidos[$mime];
    $ruta_archivo = $directorio_uploads . DIRECTORY_SEPARATOR . $nombre_archivo;
    $url_imagen = 'uploads/' . $nombre_archivo;
    $tipo_contenido = 'imagen';
}

try {
    // Confirma que el destinatario sea amigo del usuario actual.
    $stmt = $pdo->prepare("SELECT id_soli_amistad FROM tbl_soli_amistad WHERE estado = 'aceptada' AND ((id_emisor = :e1 AND id_receptor = :r1) OR (id_emisor = :r2 AND id_receptor = :e2))");
    $stmt->execute([
        ':e1' => $id_usuario,
        ':r1' => $id_receptor,
        ':r2' => $id_receptor,
        ':e2' => $id_usuario
    ]);
    $amistad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$amistad) {
        header("Location: ../view/amigos.php");
        exit();
    }

    // Guarda el mensaje con la hora actual, sin incluir segundos.
    $fecha_actual = date('Y-m-d H:i');

    if ($subida_imagen && !move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
        $_SESSION['error_chat'] = 'No se pudo guardar la imagen. Inténtalo de nuevo.';
        header("Location: ../view/chat.php?id=" . $id_receptor);
        exit();
    }

    // Inserta el texto y, si corresponde, la ruta de la imagen.
    $stmt = $pdo->prepare("INSERT INTO tbl_chats (id_emisor, id_receptor, mensaje, tipo_contenido, archivo_url, fecha_envio) VALUES (:emisor, :receptor, :mensaje, :tipo, :archivo, :fecha)");
    $stmt->execute([
        ':emisor' => $id_usuario,
        ':receptor' => $id_receptor,
        ':mensaje' => $mensaje !== '' ? $mensaje : null,
        ':tipo' => $tipo_contenido,
        ':archivo' => $url_imagen,
        ':fecha' => $fecha_actual
    ]);

} catch (PDOException $e) {
    if ($ruta_archivo !== null && is_file($ruta_archivo)) {
        unlink($ruta_archivo);
    }
    $_SESSION['error_chat'] = 'No se pudo enviar el mensaje. Inténtalo de nuevo.';
}

// Regresa a la conversación con el destinatario.
header("Location: ../view/chat.php?id=" . $id_receptor);
exit();