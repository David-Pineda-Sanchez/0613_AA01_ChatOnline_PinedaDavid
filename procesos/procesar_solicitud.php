<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/login.php");
    exit();
}

// Carga la base de datos y recoge la solicitud y la acción elegida.
// Solo se acepta una acción válida para no cambiar un registro accidentalmente.
require_once '../includes/conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$id_soli = isset($_POST['id_soli']) ? intval($_POST['id_soli']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Acepta solo identificadores positivos y acciones conocidas.
if ($id_soli > 0 && ($accion === 'aceptar' || $accion === 'rechazar')) {
    try {
        // Convierte el botón elegido en el estado que se guardará.
        $nuevo_estado = ($accion === 'aceptar') ? 'aceptada' : 'rechazada';

        // Actualiza solo solicitudes pendientes dirigidas al usuario actual
        $sql = "UPDATE tbl_soli_amistad 
                SET estado = :estado 
                WHERE id_soli_amistad = :id_soli AND id_receptor = :id_receptor AND estado = 'pendiente'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
        $stmt->bindParam(':id_soli', $id_soli, PDO::PARAM_INT);
        $stmt->bindParam(':id_receptor', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        // Prepara un aviso para mostrarlo después de volver a la página
        $_SESSION['mensaje_solicitud'] = ($accion === 'aceptar') ? "Solicitud aceptada correctamente." : "Solicitud rechazada.";

    } catch (PDOException $e) {
        $_SESSION['mensaje_solicitud'] = "Error al procesar la solicitud.";
    }
}

// Regresa a la lista de solicitudes después de procesar la acción
header("Location: ../view/solicitudes.php");
exit();