<?php
// Devuelve true solo si la cuenta tiene asignado el rol de administrador.
function usuarioEsAdministrador(PDO $pdo, int $idUsuario)
{
    $stmt = $pdo->prepare(
        "SELECT r.nombre
         FROM tbl_usuario_roles ur
         JOIN tbl_roles r ON r.id_rol = ur.id_rol
         WHERE ur.id_usuario = :id_usuario AND r.nombre = 'administrador'"
    );
    $stmt->execute([':id_usuario' => $idUsuario]);

    // Si la consulta devuelve «administrador», el usuario tiene permisos.
    return $stmt->fetchColumn() === 'administrador';
}
