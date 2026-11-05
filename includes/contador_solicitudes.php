<?php
// Cuenta las solicitudes pendientes del usuario para mostrarlas en el header.
// Requiere que la página ya haya creado $pdo y abierto la sesión.
$num_solicitudes = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM tbl_soli_amistad WHERE id_receptor = :id AND estado = 'pendiente'");
    $stmt->execute([':id' => $_SESSION['id_usuario']]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $num_solicitudes = (int)$fila['total'];
} catch (PDOException $e) {
    $num_solicitudes = 0;
}
?>
