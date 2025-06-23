-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS reservacioneskawai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservacioneskawai;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso TIMESTAMP NULL,
    activo BOOLEAN DEFAULT TRUE,
    creado_por INT NULL,
    INDEX idx_usuario (nombre_usuario),
    INDEX idx_email (email),
    FOREIGN KEY (creado_por) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insertar administrador principal (contraseña: kawai2024!)
INSERT INTO administradores (nombre_usuario, nombre_completo, email, password_hash) 
VALUES ('admin_kawai', 'Administrador Principal', 'admin@lacasona.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE nombre_usuario = nombre_usuario;

-- Resto de tablas...
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  INDEX idx_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS desayuno (
  id_desayuno INT(11) NOT NULL AUTO_INCREMENT,
  bebida VARCHAR(100) NOT NULL,
  pan VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 9.00,
  PRIMARY KEY (id_desayuno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO desayuno (bebida, pan, precio) VALUES
('Café con leche', 'Pan con jamonada', 9.00),
('Té', 'Pan con pollo', 9.00),
('Chocolate caliente', 'Pan con palta', 9.00),
('Jugo de naranja', 'Pan tostado con mermelada', 9.00);

CREATE TABLE IF NOT EXISTS almuerzo (
  id_almuerzo INT(11) NOT NULL AUTO_INCREMENT,
  entrada VARCHAR(100) NOT NULL,
  plato_fondo VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 14.50,
  PRIMARY KEY (id_almuerzo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES
('Ensalada mixta', 'Arroz con pollo', 'Gelatina', 'Chicha morada', 14.50),
('Sopa de verduras', 'Lomo saltado', 'Flan', 'Refresco de maracuyá', 14.50),
('Causa limeña', 'Asado con puré', 'Mazamorra morada', 'Limonada', 14.50);

CREATE TABLE IF NOT EXISTS cena (
  id_cena INT(11) NOT NULL AUTO_INCREMENT,
  plato VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 16.50,
  PRIMARY KEY (id_cena)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO cena (plato, postre, bebida, precio) VALUES
('Pollo al sillao', 'Arroz con leche', 'Té de hierbas', 16.50),
('Pescado a la plancha', 'Pudín de chocolate', 'Jugo de piña', 16.50),
('Tallarines rojos', 'Suspiro limeño', 'Chicha morada', 16.50);

CREATE TABLE IF NOT EXISTS reservas (
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

CREATE TABLE IF NOT EXISTS detalle_reserva (
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

CREATE TABLE IF NOT EXISTS registro_pago (
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
  PRIMARY KEY (id_pago),-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS reservacioneskawai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservacioneskawai;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso TIMESTAMP NULL,
    activo BOOLEAN DEFAULT TRUE,
    creado_por INT NULL,
    INDEX idx_usuario (nombre_usuario),
    INDEX idx_email (email),
    FOREIGN KEY (creado_por) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insertar administrador principal (contraseña: kawai2024!)
INSERT INTO administradores (nombre_usuario, nombre_completo, email, password_hash) 
VALUES ('admin_kawai', 'Administrador Principal', 'admin@lacasona.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE nombre_usuario = nombre_usuario;

-- Resto de tablas...
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  INDEX idx_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS desayuno (
  id_desayuno INT(11) NOT NULL AUTO_INCREMENT,
  bebida VARCHAR(100) NOT NULL,
  pan VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 9.00,
  PRIMARY KEY (id_desayuno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO desayuno (bebida, pan, precio) VALUES
('Café con leche', 'Pan con jamonada', 9.00),
('Té', 'Pan con pollo', 9.00),
('Chocolate caliente', 'Pan con palta', 9.00),
('Jugo de naranja', 'Pan tostado con mermelada', 9.00);

CREATE TABLE IF NOT EXISTS almuerzo (
  id_almuerzo INT(11) NOT NULL AUTO_INCREMENT,
  entrada VARCHAR(100) NOT NULL,
  plato_fondo VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 14.50,
  PRIMARY KEY (id_almuerzo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES
('Ensalada mixta', 'Arroz con pollo', 'Gelatina', 'Chicha morada', 14.50),
('Sopa de verduras', 'Lomo saltado', 'Flan', 'Refresco de maracuyá', 14.50),
('Causa limeña', 'Asado con puré', 'Mazamorra morada', 'Limonada', 14.50);

CREATE TABLE IF NOT EXISTS cena (
  id_cena INT(11) NOT NULL AUTO_INCREMENT,
  plato VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 16.50,
  PRIMARY KEY (id_cena)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO cena (plato, postre, bebida, precio) VALUES
('Pollo al sillao', 'Arroz con leche', 'Té de hierbas', 16.50),
('Pescado a la plancha', 'Pudín de chocolate', 'Jugo de piña', 16.50),
('Tallarines rojos', 'Suspiro limeño', 'Chicha morada', 16.50);

CREATE TABLE IF NOT EXISTS reservas (
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

CREATE TABLE IF NOT EXISTS detalle_reserva (
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

CREATE TABLE IF NOT EXISTS registro_pago (
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
-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS reservacioneskawai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservacioneskawai;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso TIMESTAMP NULL,
    activo BOOLEAN DEFAULT TRUE,
    creado_por INT NULL,
    INDEX idx_usuario (nombre_usuario),
    INDEX idx_email (email),
    FOREIGN KEY (creado_por) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insertar administrador principal (contraseña: kawai2024!)
INSERT INTO administradores (nombre_usuario, nombre_completo, email, password_hash) 
VALUES ('admin_kawai', 'Administrador Principal', 'admin@lacasona.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE nombre_usuario = nombre_usuario;

-- Resto de tablas...
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  INDEX idx_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS desayuno (
  id_desayuno INT(11) NOT NULL AUTO_INCREMENT,
  bebida VARCHAR(100) NOT NULL,
  pan VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 9.00,
  PRIMARY KEY (id_desayuno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO desayuno (bebida, pan, precio) VALUES
('Café con leche', 'Pan con jamonada', 9.00),
('Té', 'Pan con pollo', 9.00),
('Chocolate caliente', 'Pan con palta', 9.00),
('Jugo de naranja', 'Pan tostado con mermelada', 9.00);

CREATE TABLE IF NOT EXISTS almuerzo (
  id_almuerzo INT(11) NOT NULL AUTO_INCREMENT,
  entrada VARCHAR(100) NOT NULL,
  plato_fondo VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 14.50,
  PRIMARY KEY (id_almuerzo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES
('Ensalada mixta', 'Arroz con pollo', 'Gelatina', 'Chicha morada', 14.50),
('Sopa de verduras', 'Lomo saltado', 'Flan', 'Refresco de maracuyá', 14.50),
('Causa limeña', 'Asado con puré', 'Mazamorra morada', 'Limonada', 14.50);

CREATE TABLE IF NOT EXISTS cena (
  id_cena INT(11) NOT NULL AUTO_INCREMENT,
  plato VARCHAR(100) NOT NULL,
  postre VARCHAR(100),
  bebida VARCHAR(100),
  precio DECIMAL(10,2) NOT NULL DEFAULT 16.50,
  PRIMARY KEY (id_cena)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO cena (plato, postre, bebida, precio) VALUES
('Pollo al sillao', 'Arroz con leche', 'Té de hierbas', 16.50),
('Pescado a la plancha', 'Pudín de chocolate', 'Jugo de piña', 16.50),
('Tallarines rojos', 'Suspiro limeño', 'Chicha morada', 16.50);

CREATE TABLE IF NOT EXISTS reservas (
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

CREATE TABLE IF NOT EXISTS detalle_reserva (
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

CREATE TABLE IF NOT EXISTS registro_pago (
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

  INDEX idx_codigo_reserva (codigo_reserva),
  INDEX idx_estado_verificacion (estado_verificacion),
  FOREIGN KEY (codigo_reserva) REFERENCES reservas(codigo_reserva) ON DELETE CASCADE,
  FOREIGN KEY (verificado_por) REFERENCES administradores(id_admin) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
