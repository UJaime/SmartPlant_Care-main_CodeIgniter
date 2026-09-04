-- SmartPlant CARE - Actualizacion no destructiva para el hito de compra visual.
-- Usar si ya tenes la base smartplant_care creada y no queres recrear todo.

CREATE DATABASE IF NOT EXISTS smartplant_care
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE smartplant_care;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) DEFAULT NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin', 'cliente', 'tecnico') NOT NULL DEFAULT 'cliente',
  plan ENUM('free', 'pro', 'premium') NOT NULL DEFAULT 'free',
  telefono VARCHAR(20) DEFAULT NULL,
  foto_perfil VARCHAR(255) DEFAULT NULL,
  reset_token VARCHAR(64) DEFAULT NULL,
  reset_expira DATETIME DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plantas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  especie VARCHAR(120) NOT NULL DEFAULT 'Por definir',
  descripcion TEXT DEFAULT NULL,
  humedad_min DECIMAL(5,2) NOT NULL DEFAULT 35.00,
  humedad_max DECIMAL(5,2) NOT NULL DEFAULT 65.00,
  temp_min DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  temp_max DECIMAL(5,2) NOT NULL DEFAULT 35.00,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_plantas_usuario_activa (usuario_id, activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS dispositivos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  planta_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  tipo_dispositivo VARCHAR(80) NOT NULL DEFAULT 'Sensor IoT',
  corresponde_a VARCHAR(120) NOT NULL DEFAULT 'Planta',
  ubicacion VARCHAR(120) DEFAULT NULL,
  firmware VARCHAR(30) DEFAULT '1.0.0',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ultima_conexion DATETIME DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dispositivos_codigo (codigo),
  KEY idx_dispositivos_usuario (usuario_id),
  KEY idx_dispositivos_planta (planta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS compras (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  usuario_nombre VARCHAR(100) NOT NULL,
  usuario_email VARCHAR(150) NOT NULL,
  metodo_pago VARCHAR(50) NOT NULL,
  estado ENUM('pendiente', 'aprobado', 'rechazado', 'cancelado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
  moneda CHAR(3) NOT NULL DEFAULT 'ARS',
  monto_total DECIMAL(10,2) NOT NULL,
  cantidad_items INT UNSIGNED NOT NULL DEFAULT 0,
  referencia_externa VARCHAR(120) DEFAULT NULL,
  detalle_items LONGTEXT DEFAULT NULL,
  notas TEXT DEFAULT NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_usuario_fecha (usuario_id, fecha_pago),
  KEY idx_estado (estado),
  KEY idx_referencia (referencia_externa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
