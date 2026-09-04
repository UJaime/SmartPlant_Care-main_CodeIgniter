-- SmartPlant CARE - MySQL schema and seed data for local testing.
-- Database expected by app/Config/Database.php:
-- host: localhost, user: root, password: empty, database: smartplant_care
--
-- Test accounts:
--   admin@smartplant.test / 123456
--   demo@smartplant.test  / 123456
--
-- This script recreates the application tables inside smartplant_care.

CREATE DATABASE IF NOT EXISTS smartplant_care
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE smartplant_care;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS inventario_plantas;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS lecturas_sensores;
DROP TABLE IF EXISTS componentes_hardware;
DROP TABLE IF EXISTS dispositivos;
DROP TABLE IF EXISTS plantas;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
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
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol (rol),
  KEY idx_usuarios_plan (plan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE plantas (
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
  KEY idx_plantas_usuario_activa (usuario_id, activa),
  CONSTRAINT fk_plantas_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE dispositivos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  planta_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(80) NOT NULL,
  api_key VARCHAR(64) DEFAULT NULL,
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
  KEY idx_dispositivos_planta (planta_id),
  CONSTRAINT fk_dispositivos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_dispositivos_planta
    FOREIGN KEY (planta_id) REFERENCES plantas(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE componentes_hardware (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dispositivo_id INT UNSIGNED NOT NULL,
  codigo_componente VARCHAR(60) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  categoria VARCHAR(60) NOT NULL,
  pin VARCHAR(80) DEFAULT NULL,
  estado ENUM('conectado','desconectado','advertencia') NOT NULL DEFAULT 'desconectado',
  valor VARCHAR(80) DEFAULT NULL,
  unidad VARCHAR(24) DEFAULT NULL,
  detalle VARCHAR(255) DEFAULT NULL,
  ultima_conexion DATETIME DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_componentes_dispositivo (dispositivo_id, codigo_componente),
  KEY idx_componentes_estado (estado),
  CONSTRAINT fk_componentes_dispositivo
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE lecturas_sensores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dispositivo_id INT UNSIGNED NOT NULL,
  planta_id INT UNSIGNED NOT NULL,
  humedad_suelo DECIMAL(5,2) NOT NULL,
  temperatura DECIMAL(5,2) NOT NULL,
  humedad_ambiente DECIMAL(5,2) DEFAULT NULL,
  luz_ambiental INT UNSIGNED NOT NULL DEFAULT 0,
  ph DECIMAL(4,2) DEFAULT NULL,
  nivel_tanque TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bateria TINYINT UNSIGNED NOT NULL DEFAULT 0,
  riego_activo TINYINT(1) NOT NULL DEFAULT 0,
  fuente_5v DECIMAL(4,2) DEFAULT NULL,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lecturas_planta_fecha (planta_id, creada_en),
  KEY idx_lecturas_dispositivo_fecha (dispositivo_id, creada_en),
  CONSTRAINT fk_lecturas_dispositivo
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_lecturas_planta
    FOREIGN KEY (planta_id) REFERENCES plantas(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE eventos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  planta_id INT UNSIGNED NOT NULL,
  dispositivo_id INT UNSIGNED DEFAULT NULL,
  tipo VARCHAR(40) NOT NULL DEFAULT 'otro',
  mensaje VARCHAR(255) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eventos_planta_fecha (planta_id, creado_en),
  KEY idx_eventos_tipo (tipo),
  CONSTRAINT fk_eventos_planta
    FOREIGN KEY (planta_id) REFERENCES plantas(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_eventos_dispositivo
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE inventario_plantas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  planta_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  foto_path VARCHAR(255) NOT NULL,
  diagnostico TEXT DEFAULT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inventario_planta (planta_id),
  KEY idx_inventario_usuario (usuario_id),
  CONSTRAINT fk_inventario_planta
    FOREIGN KEY (planta_id) REFERENCES plantas(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_inventario_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE compras (
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
  KEY idx_referencia (referencia_externa),
  CONSTRAINT fk_compras_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO usuarios
  (id, nombre, apellido, email, password, rol, plan, telefono, foto_perfil)
VALUES
  (1, 'Admin', 'SmartPlant', 'admin@smartplant.test', '$2y$10$E8UkzrKK3LqGY1lk4holXuB29eYGOhWCq4HOHLfpm6ECAkAOMMMXe', 'admin', 'pro', '+5491100000001', NULL),
  (2, 'Usuario', 'Demo', 'demo@smartplant.test', '$2y$10$E8UkzrKK3LqGY1lk4holXuB29eYGOhWCq4HOHLfpm6ECAkAOMMMXe', 'cliente', 'free', '+5491100000002', NULL);

INSERT INTO plantas
  (id, usuario_id, nombre, especie, descripcion, humedad_min, humedad_max, temp_min, temp_max, activa)
VALUES
  (1, 1, 'Monstera Oficina', 'Monstera deliciosa', 'Planta principal conectada al sensor del dashboard.', 40.00, 65.00, 18.00, 29.00, 1),
  (2, 1, 'Albahaca Cocina', 'Ocimum basilicum', 'Maceta de hierbas con monitoreo de humedad.', 45.00, 70.00, 18.00, 32.00, 1),
  (3, 2, 'Ficus Demo', 'Ficus elastica', 'Planta de prueba para la cuenta demo.', 35.00, 60.00, 16.00, 30.00, 1);

INSERT INTO dispositivos
  (id, usuario_id, planta_id, codigo, nombre, tipo_dispositivo, corresponde_a, ubicacion, firmware, activo, ultima_conexion)
VALUES
  (1, 1, 1, 'SPC-ADMIN-MONSTERA-001', 'Aurea One Monstera', 'Sensor de humedad y ambiente', 'Monstera Oficina', 'Oficina', '1.2.0', 1, NOW()),
  (2, 1, 2, 'SPC-ADMIN-ALBAHACA-001', 'Aurea One Albahaca', 'Kit de riego automatico', 'Albahaca Cocina', 'Cocina', '1.2.0', 1, NOW()),
  (3, 2, 3, 'SPC-DEMO-FICUS-001', 'Aurea One Demo', 'Sensor de humedad y ambiente', 'Ficus Demo', 'Living', '1.1.5', 1, NOW());

INSERT INTO lecturas_sensores
  (dispositivo_id, planta_id, humedad_suelo, temperatura, luz_ambiental, nivel_tanque, bateria, riego_activo, creada_en)
VALUES
  (1, 1, 43.20, 22.10, 520, 86, 94, 0, NOW() - INTERVAL 11 HOUR),
  (1, 1, 44.80, 22.30, 560, 85, 94, 0, NOW() - INTERVAL 10 HOUR),
  (1, 1, 46.10, 22.80, 610, 84, 93, 0, NOW() - INTERVAL 9 HOUR),
  (1, 1, 48.40, 23.20, 680, 83, 93, 0, NOW() - INTERVAL 8 HOUR),
  (1, 1, 50.30, 24.00, 720, 82, 92, 0, NOW() - INTERVAL 7 HOUR),
  (1, 1, 52.90, 24.40, 760, 81, 92, 0, NOW() - INTERVAL 6 HOUR),
  (1, 1, 54.70, 24.10, 700, 80, 91, 0, NOW() - INTERVAL 5 HOUR),
  (1, 1, 56.20, 23.70, 640, 79, 91, 0, NOW() - INTERVAL 4 HOUR),
  (1, 1, 58.50, 23.40, 590, 78, 90, 0, NOW() - INTERVAL 3 HOUR),
  (1, 1, 61.00, 23.10, 540, 77, 90, 0, NOW() - INTERVAL 2 HOUR),
  (1, 1, 57.60, 22.90, 500, 76, 89, 1, NOW() - INTERVAL 90 MINUTE),
  (1, 1, 55.40, 22.70, 470, 75, 89, 0, NOW() - INTERVAL 45 MINUTE),
  (1, 1, 54.10, 22.50, 450, 75, 88, 0, NOW() - INTERVAL 5 MINUTE),
  (2, 2, 49.50, 25.20, 820, 68, 81, 0, NOW() - INTERVAL 30 MINUTE),
  (3, 3, 42.00, 21.80, 390, 91, 97, 0, NOW() - INTERVAL 20 MINUTE);

INSERT INTO eventos
  (planta_id, dispositivo_id, tipo, mensaje, creado_en)
VALUES
  (1, 1, 'riego', 'Riego automatico activado por 20 segundos.', NOW() - INTERVAL 90 MINUTE),
  (1, 1, 'alerta_humedad', 'Humedad cerca del limite superior recomendado.', NOW() - INTERVAL 2 HOUR),
  (1, 1, 'otro', 'Lectura sincronizada correctamente.', NOW() - INTERVAL 5 MINUTE),
  (2, 2, 'otro', 'Sensor de albahaca conectado.', NOW() - INTERVAL 30 MINUTE),
  (3, 3, 'otro', 'Cuenta demo lista para pruebas.', NOW() - INTERVAL 20 MINUTE);

INSERT INTO compras
  (usuario_id, usuario_nombre, usuario_email, metodo_pago, estado, moneda, monto_total, cantidad_items, referencia_externa, detalle_items, notas, fecha_pago)
VALUES
  (
    1,
    'Admin SmartPlant',
    'admin@smartplant.test',
    'tarjeta',
    'aprobado',
    'ARS',
    249999.00,
    1,
    'TEST-ORDER-001',
    '[{"id":"aurea-one","nombre":"Aurea One","color":"negro","cantidad":1,"precio":249999}]',
    'Compra seed para pruebas locales.',
    NOW() - INTERVAL 1 DAY
  );
