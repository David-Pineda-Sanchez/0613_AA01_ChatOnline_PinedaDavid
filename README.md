# ChatOnline

Versión web inicial de una aplicación de chat tipo WhatsApp, desarrollada para el módulo 0613 Desarrollo web en entorno servidor (DAW2, actividad AA01).

Autores: David Pineda y Raül Castro.

## Requisitos

- Servidor local con Apache, PHP y MySQL (por ejemplo WAMP).
- PHP con PDO. No se utiliza JavaScript.

## Instalación

1. Copiar la carpeta del proyecto en el directorio `www` del servidor.
2. Importar el archivo `db_chat.sql` desde phpMyAdmin. Crea desde cero la base `db_chat_validación` y sus tablas, sin modificar `db_chat`.
3. Revisar las credenciales de la base de datos en `includes/config.php` (`$db_host`, `$db_user`, `$db_pass`, `$db_name`).
4. Crear una cuenta desde el registro y asignarle el rol de administrador con la consulta indicada en «Administración».
5. Abrir `index.php` en el navegador. Redirige al login o a la página principal según haya sesión iniciada.

## Administración

- Los roles se guardan en `tbl_roles` y se asignan a las cuentas mediante `tbl_usuario_roles`; así puede haber más de un administrador.
- Para dar de alta a un administrador, registrar primero su cuenta y ejecutar en phpMyAdmin la siguiente consulta, sustituyendo `nombre_admin` por su username:

```sql
INSERT INTO tbl_usuario_roles (id_usuario, id_rol)
SELECT u.id_usuario, r.id_rol
FROM tbl_usuario u
JOIN tbl_roles r ON r.nombre = 'administrador'
WHERE u.username = 'nombre_admin';
```

- Una vez iniciada la sesión con esa cuenta, aparece el acceso «Administrar usuarios» en la página principal. Desde allí se pueden editar los datos y contraseña o eliminar una cuenta.
- Al eliminar un usuario se borran sus solicitudes de amistad y todos los mensajes enviados o recibidos. También se quitan las imágenes que esos mensajes guardaban en `uploads/`.
- No se usa `ON DELETE CASCADE`: el proceso PDO elimina explícitamente las asignaciones de rol, solicitudes y mensajes, en una transacción. Las claves foráneas impiden borrar una cuenta si queda alguna relación dependiente.
- Un administrador no puede eliminar su propia cuenta desde esta sección. Para dar de alta a otro administrador, repetir la consulta con su username.

## Funcionalidades

- Registro de usuarios con la contraseña cifrada mediante bcrypt.
- Login y logout. El username del usuario aparece en todas las páginas internas.
- Búsqueda de usuarios por username o por nombre real, con coincidencia en cualquier parte del texto.
- Solicitudes de amistad: enviar, ver las recibidas y aceptarlas o rechazarlas.
- Lista de amigos.
- Chat entre amigos con mensajes de máximo 250 caracteres, ordenados del más antiguo al más reciente y con el emisor indicado en cada mensaje.

## Estructura de carpetas

- `view/` páginas que ve el usuario, incluida la sección de administración.
- `procesos/` lógica de registro, autenticación, solicitudes, mensajes y administración.
- `includes/` conexión a la base de datos, configuración, sesión y permisos.
- `css/` hojas de estilo, incluida la de administración.
- `db_chat.sql` crea la base de datos `db_chat_validación` con las tablas de la aplicación y los roles.

## Base de datos

- `tbl_usuario` guarda el username (único), el nombre real y la contraseña cifrada.
- `tbl_roles` define los roles `usuario` y `administrador`; `tbl_usuario_roles` permite asignar uno o varios roles a cada cuenta.
- `tbl_soli_amistad` guarda quién envía cada solicitud, a quién y su estado (`pendiente`, `aceptada` o `rechazada`). La combinación emisor y receptor es única, así que no se duplican solicitudes.
- `tbl_chats` guarda los mensajes con emisor, receptor, texto, tipo de contenido, ruta de archivo y fecha de envío.
- Las tablas de solicitudes y mensajes tienen claves foráneas hacia `tbl_usuario`.

## Seguridad

- Todas las consultas usan sentencias preparadas con parámetros (PDO), como protección frente a inyección SQL.
- Las contraseñas se cifran con `password_hash` (bcrypt) y se comprueban con `password_verify`.
- Los datos se validan en PHP en todos los formularios.
- Los datos se escapan con `htmlspecialchars` antes de mostrarse.
- Las páginas internas comprueban la sesión con `includes/sesion.php` y redirigen al login si no hay usuario.
- La sección y las acciones de administración comprueban el rol en la base de datos; no basta con conocer la dirección de la página.
- El chat solo se abre entre usuarios con una amistad aceptada, y el envío de mensajes lo comprueba también en el servidor.

## Decisiones de diseño

- Una solicitud rechazada no se puede volver a enviar.
- Sin JavaScript, los mensajes nuevos se ven al recargar la página.

## Reparto del trabajo

- Raül Castro: registro de usuarios (formulario y validación), búsqueda de usuarios, envío de solicitudes de amistad y vista del chat con la lista de mensajes.
- David Pineda: sql de la base de datos, login y sesión, logout, página principal, solicitudes recibidas (aceptar y rechazar), lista de amigos, envío de mensajes y estilos del chat.
- Los dos: navegación entre páginas y pruebas con varias cuentas.
