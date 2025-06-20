-- Crear base de datos
CREATE DATABASE IF NOT EXISTS reservacioneskawai;
USE reservacioneskawai;

-- Tabla: administradores
CREATE TABLE administradores (
  id_admin INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contraseña VARCHAR(255) NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: usuarios
CREATE TABLE usuarios (
  id_usuario INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  INDEX idx_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: desayuno
CREATE TABLE desayuno (
  id_desayuno INT(11) NOT NULL AUTO_INCREMENT,
  bebida VARCHAR(100) NOT NULL,
  pan VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 9.00,
  PRIMARY KEY (id_desayuno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: almuerzo
CREATE TABLE almuerzo (
  id_almuerzo INT(11) NOT NULL AUTO_INCREMENT,
  entrada VARCHAR(100) NOT NULL,
  plato_fondo VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 14.50,
  PRIMARY KEY (id_almuerzo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: cena
CREATE TABLE cena (
  id_cena INT(11) NOT NULL AUTO_INCREMENT,
  plato VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 16.50,
  PRIMARY KEY (id_cena)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: reservas (con estados actualizados)
CREATE TABLE reservas (
  id_reserva INT(11) NOT NULL AUTO_INCREMENT,
  codigo_reserva VARCHAR(20) NOT NULL UNIQUE,
  id_usuario INT(11),
  id_admin INT(11),
  fecha_reserva DATE NOT NULL,
  hora_reserva TIME NOT NULL,
  cantidad_personas INT(11) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  estado ENUM('Solicitada','Anticipo pagado','Completada','Cancelada') DEFAULT 'Solicitada',
  info_adicional TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_reserva),
  INDEX idx_codigo_reserva (codigo_reserva),
  INDEX idx_fecha_reserva (fecha_reserva),
  INDEX idx_estado (estado),
  INDEX idx_usuario (id_usuario),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  FOREIGN KEY (id_admin) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: detalle_reserva
CREATE TABLE detalle_reserva (
  id_detalle INT(11) NOT NULL AUTO_INCREMENT,
  id_reserva INT(11) NOT NULL,
  id_desayuno INT(11),
  id_almuerzo INT(11),
  id_cena INT(11),
  cantidad INT(11) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  INDEX idx_reserva (id_reserva),
  FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE,
  FOREIGN KEY (id_desayuno) REFERENCES desayuno(id_desayuno) ON DELETE SET NULL,
  FOREIGN KEY (id_almuerzo) REFERENCES almuerzo(id_almuerzo) ON DELETE SET NULL,
  FOREIGN KEY (id_cena) REFERENCES cena(id_cena) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: registro_pago (actualizada)
CREATE TABLE registro_pago (
  id_pago INT(11) NOT NULL AUTO_INCREMENT,
  codigo_reserva VARCHAR(20) NOT NULL,
  metodo_pago ENUM('yape', 'transferencia') NOT NULL,
  nombre_titular VARCHAR(100) NOT NULL,
  numero_operacion VARCHAR(50) NOT NULL,
  codigo_seguridad VARCHAR(10),
  banco VARCHAR(50),
  monto_pagado DECIMAL(10,2) NOT NULL,
  tipo_comprobante ENUM('boleta', 'factura') DEFAULT 'boleta',
  ruc_factura VARCHAR(15),
  comprobante_url VARCHAR(255),
  comentarios TEXT,
  estado_verificacion ENUM('pendiente','verificado','rechazado') DEFAULT 'pendiente',
  fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_verificacion TIMESTAMP NULL,
  verificado_por INT(11),
  PRIMARY KEY (id_pago),
  INDEX idx_codigo_reserva (codigo_reserva),
  INDEX idx_estado_verificacion (estado_verificacion),
  FOREIGN KEY (codigo_reserva) REFERENCES reservas(codigo_reserva) ON DELETE CASCADE,
  FOREIGN KEY (verificado_por) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar administrador por defecto
INSERT INTO administradores (nombre, email, contraseña) 
VALUES ('Administrador', 'admin@kawai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
