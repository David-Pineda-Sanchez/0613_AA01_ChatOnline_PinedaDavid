CREATE DATABASE IF NOT EXISTS db_chat_validación;

USE db_chat_validación;

-- Guarda las cuentas y los datos necesarios para iniciar sesión.
CREATE TABLE tbl_usuario ( 
  id_usuario INT NOT NULL AUTO_INCREMENT, 
  username VARCHAR(45) NOT NULL UNIQUE, 
  nombre_real VARCHAR(100) NOT NULL, 
  contrasena VARCHAR(255) NOT NULL, 
  PRIMARY KEY (id_usuario) 
) ENGINE=InnoDB; 

-- Separa los permisos de las cuentas para poder asignar varios roles. 
CREATE TABLE tbl_roles (
  id_rol INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (id_rol)
) ENGINE=InnoDB;

CREATE TABLE tbl_usuario_roles (
  id_usuario INT NOT NULL,
  id_rol INT NOT NULL,
  PRIMARY KEY (id_usuario, id_rol),
  CONSTRAINT fk_usuario_rol_usuario FOREIGN KEY (id_usuario)
    REFERENCES tbl_usuario (id_usuario) ON DELETE RESTRICT,
  CONSTRAINT fk_usuario_rol_rol FOREIGN KEY (id_rol)
    REFERENCES tbl_roles (id_rol) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO tbl_roles (nombre) VALUES ('usuario'), ('administrador');

-- Registra quién envía cada solicitud de amistad y su estado.
CREATE TABLE tbl_soli_amistad ( 
  id_soli_amistad INT NOT NULL AUTO_INCREMENT, 
  id_emisor INT NOT NULL, 
  id_receptor INT NOT NULL, 
  estado ENUM('pendiente', 'aceptada', 'rechazada') NOT NULL DEFAULT 'pendiente', 
  PRIMARY KEY (id_soli_amistad), 
  UNIQUE KEY uq_solicitud (id_emisor, id_receptor) 
) ENGINE=InnoDB;   

-- Almacena los mensajes, participantes, tipo de contenido y fecha de envío.
CREATE TABLE tbl_chats ( 
  id_chats INT NOT NULL AUTO_INCREMENT, 
  id_emisor INT NOT NULL, 
  id_receptor INT NOT NULL, 
  mensaje VARCHAR(250) NULL, 
  tipo_contenido ENUM('texto', 'imagen', 'archivo') NOT NULL DEFAULT 'texto', 
  archivo_url VARCHAR(255) NULL, 
  fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  PRIMARY KEY (id_chats) 
) ENGINE=InnoDB; 

-- Evita solicitudes o mensajes asociados a usuarios que no existen.

ALTER TABLE tbl_soli_amistad
  ADD CONSTRAINT fk_soli_emisor FOREIGN KEY (id_emisor) REFERENCES tbl_usuario (id_usuario) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_soli_receptor FOREIGN KEY (id_receptor) REFERENCES tbl_usuario (id_usuario) ON DELETE RESTRICT;

ALTER TABLE tbl_chats
  ADD CONSTRAINT fk_chat_emisor FOREIGN KEY (id_emisor) REFERENCES tbl_usuario (id_usuario) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_chat_receptor FOREIGN KEY (id_receptor) REFERENCES tbl_usuario (id_usuario) ON DELETE RESTRICT;