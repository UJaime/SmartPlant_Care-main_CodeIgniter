<?php
require_once __DIR__ . '/config/database.php';
$db = Database::connect();

$colTelefono = $db->query("SHOW COLUMNS FROM usuarios LIKE 'telefono'");
if ($colTelefono && $colTelefono->num_rows === 0) {
    $db->query("ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) DEFAULT NULL");
}

$colFoto = $db->query("SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'");
if ($colFoto && $colFoto->num_rows === 0) {
    $db->query("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL");
}

$db->query("
    CREATE TABLE IF NOT EXISTS compras (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id INT(10) UNSIGNED NOT NULL,
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
        CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "OK";
