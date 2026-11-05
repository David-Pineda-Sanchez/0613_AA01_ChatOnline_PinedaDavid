# ChatOnline

Versión web inicial de una aplicación de chat tipo WhatsApp, desarrollada para el módulo 0613 Desarrollo web en entorno servidor (DAW2, actividad AA01).

Autores: David Pineda y Raül Castro.

## Requisitos

- Servidor local con Apache, PHP y MySQL (por ejemplo WAMP).
- PHP con PDO. No se utiliza JavaScript.

## Instalación

1. Copiar la carpeta del proyecto en el directorio `www` del servidor.
2. Importar el archivo `db_chat.sql` desde phpMyAdmin. Crea la base de datos `db_chat` y sus tablas.
3. Revisar las credenciales de la base de datos en `includes/config.php` (`$db_host`, `$db_user`, `$db_pass`, `$db_name`).
4. Abrir `index.php` en el navegador. Redirige al login o a la página principal según haya sesión iniciada.

## Funcionalidades

- Registro de usuarios con la contraseña cifrada mediante bcrypt.
- Login y logout. El username del usuario aparece en todas las páginas internas.
- Búsqueda de usuarios por username o por nombre real, con coincidencia en cualquier parte del texto.
- Solicitudes de amistad: enviar, ver las recibidas y aceptarlas o rechazarlas.
- Lista de amigos.
- Chat entre amigos con mensajes de máximo 250 caracteres, ordenados del más antiguo al más reciente y con el emisor indicado en cada mensaje.

## Estructura de carpetas

- `view/` páginas que ve el usuario (login, registro, principal, buscar, solicitudes, amigos y chat).
- `procesos/` lógica en PHP sin HTML (registro, autenticación, logout, solicitudes y envío de mensajes).
- `includes/` conexión a la base de datos, configuración y comprobación de sesión.
- `css/` hojas de estilo.
- `db_chat.sql` script para crear la base de datos.

## Base de datos

- `tbl_usuario` guarda el username (único), el nombre real y la contraseña cifrada.
- `tbl_soli_amistad` guarda quién envía cada solicitud, a quién y su estado (`pendiente`, `aceptada` o `rechazada`). La combinación emisor y receptor es única, así que no se duplican solicitudes.
- `tbl_chats` guarda los mensajes con emisor, receptor, texto, tipo de contenido, ruta de archivo y fecha de envío.
- Las tablas de solicitudes y mensajes tienen claves foráneas hacia `tbl_usuario`.

## Seguridad

- Todas las consultas usan sentencias preparadas con parámetros (PDO), como protección frente a inyección SQL.
- Las contraseñas se cifran con `password_hash` (bcrypt) y se comprueban con `password_verify`.
- Los datos se validan en PHP en todos los formularios.
- Los datos se escapan con `htmlspecialchars` antes de mostrarse.
- Las páginas internas comprueban la sesión con `includes/sesion.php` y redirigen al login si no hay usuario.
- El chat solo se abre entre usuarios con una amistad aceptada, y el envío de mensajes lo comprueba también en el servidor.

## Decisiones de diseño

- Una solicitud rechazada no se puede volver a enviar.
- Sin JavaScript, los mensajes nuevos se ven al recargar la página.

## Reparto del trabajo

- Raül Castro: registro de usuarios (formulario y validación), búsqueda de usuarios, envío de solicitudes de amistad y vista del chat con la lista de mensajes.
- David Pineda: sql de la base de datos, login y sesión, logout, página principal, solicitudes recibidas (aceptar y rechazar), lista de amigos, envío de mensajes y estilos del chat.
- Los dos: navegación entre páginas y pruebas con varias cuentas.
