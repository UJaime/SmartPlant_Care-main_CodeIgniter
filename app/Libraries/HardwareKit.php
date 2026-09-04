<?php

class HardwareKit
{
    public const ONLINE_SECONDS = 120;

    public static function components(): array
    {
        return [
            'esp32' => [
                'nombre' => 'ESP32',
                'categoria' => 'Controlador',
                'pin' => 'USB / WiFi',
                'unidad' => '',
                'esperado' => 'Recibe WiFi y envia lecturas a la web.',
            ],
            'rele' => [
                'nombre' => 'Modulo rele',
                'categoria' => 'Actuador',
                'pin' => 'GPIO 26',
                'unidad' => '',
                'esperado' => 'Activa o corta la alimentacion de la bomba.',
            ],
            'humedad_capacitivo' => [
                'nombre' => 'Sensor de humedad capacitivo',
                'categoria' => 'Sensor',
                'pin' => 'GPIO 32 ADC (P32)',
                'unidad' => '%',
                'esperado' => 'Mide humedad del suelo.',
            ],
            'bh1750' => [
                'nombre' => 'Sensor BH-1750',
                'categoria' => 'Sensor',
                'pin' => 'SDA 21 / SCL 22',
                'unidad' => 'lx',
                'esperado' => 'Mide luz ambiental.',
            ],
        ];
    }

    public static function ensureSchema(mysqli $db): void
    {
        self::ensureColumn($db, 'dispositivos', 'api_key', "ALTER TABLE dispositivos ADD COLUMN api_key VARCHAR(64) DEFAULT NULL AFTER codigo");
        self::ensureColumn($db, 'lecturas_sensores', 'humedad_ambiente', "ALTER TABLE lecturas_sensores ADD COLUMN humedad_ambiente DECIMAL(5,2) DEFAULT NULL AFTER temperatura");
        self::ensureColumn($db, 'lecturas_sensores', 'ph', "ALTER TABLE lecturas_sensores ADD COLUMN ph DECIMAL(4,2) DEFAULT NULL AFTER luz_ambiental");
        self::ensureColumn($db, 'lecturas_sensores', 'fuente_5v', "ALTER TABLE lecturas_sensores ADD COLUMN fuente_5v DECIMAL(4,2) DEFAULT NULL AFTER riego_activo");

        $db->query("
            CREATE TABLE IF NOT EXISTS componentes_hardware (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS comandos_dispositivo (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                dispositivo_id INT UNSIGNED NOT NULL,
                usuario_id INT UNSIGNED NOT NULL,
                comando VARCHAR(80) NOT NULL,
                parametros TEXT DEFAULT NULL,
                estado ENUM('pendiente','ejecutado','cancelado') NOT NULL DEFAULT 'pendiente',
                creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ejecutado_en DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_comando_disp_estado (dispositivo_id, estado),
                KEY idx_comando_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::backfillApiKeys($db);
    }

    public static function backfillApiKeys(mysqli $db): void
    {
        $result = $db->query("SELECT id FROM dispositivos WHERE api_key IS NULL OR api_key = ''");
        if (! $result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $apiKey = bin2hex(random_bytes(16));
            $stmt = $db->prepare("UPDATE dispositivos SET api_key = ? WHERE id = ?");
            $id = (int) $row['id'];
            $stmt->bind_param('si', $apiKey, $id);
            $stmt->execute();
        }
    }

    public static function syncComponents(mysqli $db, int $deviceId): void
    {
        $validCodes = "'" . implode("','", array_keys(self::components())) . "'";
        $db->query("DELETE FROM componentes_hardware WHERE dispositivo_id = {$deviceId} AND codigo_componente NOT IN ({$validCodes})");

        $stmt = $db->prepare("
            INSERT INTO componentes_hardware (
                dispositivo_id, codigo_componente, nombre, categoria, pin, unidad, detalle
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                categoria = VALUES(categoria),
                pin = VALUES(pin),
                unidad = VALUES(unidad)
        ");

        foreach (self::components() as $code => $component) {
            $detail = $component['esperado'];
            $stmt->bind_param(
                'issssss',
                $deviceId,
                $code,
                $component['nombre'],
                $component['categoria'],
                $component['pin'],
                $component['unidad'],
                $detail
            );
            $stmt->execute();
        }
    }

    public static function getComponentsForDevice(mysqli $db, int $deviceId): array
    {
        self::syncComponents($db, $deviceId);

        $stmt = $db->prepare("
            SELECT *,
                CASE
                    WHEN ultima_conexion IS NULL THEN NULL
                    ELSE TIMESTAMPDIFF(SECOND, ultima_conexion, NOW())
                END AS segundos_desde_conexion
            FROM componentes_hardware
            WHERE dispositivo_id = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['codigo_componente']] = self::decorateComponent($row);
        }

        $ordered = [];
        foreach (self::components() as $code => $_) {
            if (isset($byCode[$code])) {
                $ordered[] = $byCode[$code];
            }
        }

        return $ordered;
    }

    public static function findDeviceByCode(mysqli $db, string $code): ?array
    {
        $stmt = $db->prepare("SELECT * FROM dispositivos WHERE codigo = ? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();

        return $device ?: null;
    }

    public static function getLatestReading(mysqli $db, int $deviceId): ?array
    {
        $stmt = $db->prepare("
            SELECT *
            FROM lecturas_sensores
            WHERE dispositivo_id = ?
            ORDER BY creada_en DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $reading = $stmt->get_result()->fetch_assoc();

        return $reading ?: null;
    }

    public static function recordTelemetry(mysqli $db, array $device, array $payload): array
    {
        self::ensureSchema($db);

        $deviceId = (int) $device['id'];
        $plantId = (int) $device['planta_id'];

        self::syncComponents($db, $deviceId);

        $soil = self::number($payload, ['humedad_suelo', 'soil_moisture'], 0.0);
        $temp = self::number($payload, ['temperatura', 'temperature'], 0.0);
        $air = self::number($payload, ['humedad_ambiente', 'air_humidity'], null);
        $light = (int) round(self::number($payload, ['luz_ambiental', 'lux', 'luz'], 0));
        $ph = self::number($payload, ['ph', 'ph_agua'], null);
        $tank = (int) round(self::number($payload, ['nivel_tanque', 'tank_level', 'deposito'], 0));
        $battery = (int) round(self::number($payload, ['bateria', 'battery'], 100));
        $irrigation = self::boolValue($payload, ['riego_activo', 'bomba_activa', 'pump_active', 'rele']);
        $voltage = self::number($payload, ['fuente_5v', 'voltage', 'voltaje'], null);

        $stmt = $db->prepare("
            INSERT INTO lecturas_sensores (
                dispositivo_id, planta_id, humedad_suelo, temperatura, humedad_ambiente,
                luz_ambiental, ph, nivel_tanque, bateria, riego_activo, fuente_5v, creada_en
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('iidddidiiid', $deviceId, $plantId, $soil, $temp, $air, $light, $ph, $tank, $battery, $irrigation, $voltage);
        $stmt->execute();

        $stmt = $db->prepare("UPDATE dispositivos SET ultima_conexion = NOW(), activo = 1 WHERE id = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();

        self::touchComponent($db, $deviceId, 'esp32', 'conectado', self::stringValue($payload, ['ip', 'wifi_ip', 'rssi'], 'WiFi activo'), 'Lectura recibida desde el controlador.');

        if (self::hasAny($payload, ['humedad_suelo', 'soil_moisture'])) {
            self::touchComponent($db, $deviceId, 'humedad_capacitivo', 'conectado', number_format($soil, 1, '.', ''), 'Humedad de suelo procesada.');
        }

        if (self::hasAny($payload, ['temperatura', 'temperature', 'humedad_ambiente', 'air_humidity'])) {
            $value = number_format($temp, 1, '.', '') . ' C';
            if ($air !== null) {
                $value .= ' / ' . number_format($air, 1, '.', '') . ' %';
            }
            self::touchComponent($db, $deviceId, 'dht22', 'conectado', $value, 'Temperatura y humedad ambiente actualizadas.');
        }

        if (self::hasAny($payload, ['luz_ambiental', 'lux', 'luz'])) {
            self::touchComponent($db, $deviceId, 'bh1750', 'conectado', (string) $light, 'Luz ambiental procesada.');
        }

        if (self::hasAny($payload, ['ph', 'ph_agua'])) {
            $phValue = $ph ?? 0.0;
            $state = ($phValue < 5.5 || $phValue > 7.5) ? 'advertencia' : 'conectado';
            self::touchComponent($db, $deviceId, 'ph4502c', $state, number_format($phValue, 2, '.', ''), 'pH recibido y validado.');
        }

        if (self::hasAny($payload, ['rele', 'relay_ok'])) {
            self::touchComponent($db, $deviceId, 'rele', 'conectado', $irrigation ? 'Activo' : 'Reposo', 'Rele disponible para controlar la bomba.');
        }

        if (self::hasAny($payload, ['bomba_activa', 'pump_active', 'caudal_ok'])) {
            $pumpState = $irrigation ? 'Regando' : 'Lista';
            self::touchComponent($db, $deviceId, 'bomba_sumergible', 'conectado', $pumpState, 'Bomba detectada desde el estado de riego.');
        }

        if (self::hasAny($payload, ['fuente_5v', 'voltage', 'voltaje'])) {
            $voltageValue = $voltage ?? 5.0;
            $state = ($voltageValue < 4.75 || $voltageValue > 5.25) ? 'advertencia' : 'conectado';
            self::touchComponent($db, $deviceId, 'fuente_5v', $state, number_format($voltageValue, 2, '.', ''), 'Voltaje de alimentacion revisado.');
        }

        if (self::hasAny($payload, ['microtubo_ok', 'caudal_ok'])) {
            $ok = self::boolValue($payload, ['microtubo_ok', 'caudal_ok']);
            self::touchComponent($db, $deviceId, 'microtubo_4mm', $ok ? 'conectado' : 'advertencia', $ok ? 'Flujo OK' : 'Revisar', 'Estado estimado por flujo de agua.');
        }

        if (self::hasAny($payload, ['nivel_tanque', 'tank_level', 'deposito'])) {
            $state = $tank < 20 ? 'advertencia' : 'conectado';
            self::touchComponent($db, $deviceId, 'deposito_interno', $state, (string) $tank, 'Nivel de agua procesado.');
        }

        $reading = self::getLatestReading($db, $deviceId);
        $components = self::getComponentsForDevice($db, $deviceId);

        return [
            'reading' => $reading,
            'components' => $components,
            'analysis' => self::processReading($reading, $components),
        ];
    }

    public static function processReading(?array $reading, array $components): array
    {
      if (! $reading) {
    return [
        'estado' => 'pendiente',
        'resumen' => 'Todavia no hay telemetria recibida desde el ESP32.',
        'alertas' => ['Envia telemetria desde tu ESP32 o ejecuta el comando cURL de ejemplo.'],
    ];
}

        $alerts = [];
        $state = 'ok';
        $soil = (float) $reading['humedad_suelo'];
        $temp = (float) $reading['temperatura'];
        $light = (int) $reading['luz_ambiental'];
        $tank = (int) $reading['nivel_tanque'];
        $battery = (int) $reading['bateria'];
        $air = array_key_exists('humedad_ambiente', $reading) && $reading['humedad_ambiente'] !== null
            ? (float) $reading['humedad_ambiente']
            : null;
        $ph = array_key_exists('ph', $reading) && $reading['ph'] !== null
            ? (float) $reading['ph']
            : null;
        $voltage = array_key_exists('fuente_5v', $reading) && $reading['fuente_5v'] !== null
            ? (float) $reading['fuente_5v']
            : null;

        if ($soil < 35) {
            $state = 'advertencia';
            $alerts[] = 'Humedad baja: conviene activar riego o revisar la bomba.';
        } elseif ($soil > 70) {
            $state = 'advertencia';
            $alerts[] = 'Humedad alta: evitar riego hasta que baje el valor.';
        }

        if ($temp < 12 || $temp > 34) {
            $state = 'advertencia';
            $alerts[] = 'Temperatura fuera del rango recomendado.';
        }

        if ($air !== null && ($air < 30 || $air > 85)) {
            $state = 'advertencia';
            $alerts[] = 'Humedad ambiente fuera del rango recomendado.';
        }

        if ($light < 120) {
            $alerts[] = 'Luz baja: revisar ubicacion de la planta o sensor BH-1750.';
        }

        if ($tank < 20) {
            $state = 'advertencia';
            $alerts[] = 'Deposito con poca agua.';
        }

        if ($battery < 20) {
            $state = 'advertencia';
            $alerts[] = 'Bateria baja o alimentacion inestable.';
        }

        if ($ph !== null && ($ph < 5.5 || $ph > 7.5)) {
            $state = 'advertencia';
            $alerts[] = 'pH fuera del rango ideal para riego.';
        }

        if ($voltage !== null && ($voltage < 4.75 || $voltage > 5.25)) {
            $state = 'advertencia';
            $alerts[] = 'Fuente 5V fuera del rango estable.';
        }

        foreach ($components as $component) {
            if ($ph === null && $component['codigo_componente'] === 'ph4502c' && $component['online'] && $component['valor'] !== null) {
                $ph = (float) $component['valor'];
                if ($ph < 5.5 || $ph > 7.5) {
                    $state = 'advertencia';
                    $alerts[] = 'pH fuera del rango ideal para riego.';
                }
            }
            if ($voltage === null && $component['codigo_componente'] === 'fuente_5v' && $component['online'] && $component['estado_visual'] === 'advertencia') {
                $state = 'advertencia';
                $alerts[] = 'Fuente 5V fuera del rango estable.';
            }
        }

        if ($alerts === []) {
            $alerts[] = 'Lecturas dentro de parametros normales.';
        }

        return [
            'estado' => $state,
            'resumen' => $state === 'ok'
                ? 'Sistema estable. Los sensores estan enviando datos utiles.'
                : 'Sistema con advertencias. Revisa los puntos marcados antes de dejar riego automatico.',
            'alertas' => $alerts,
        ];
    }

    public static function simulatePayload(): array
    {
        return [
            'humedad_suelo' => random_int(42, 63),
            'temperatura' => random_int(210, 285) / 10,
            'humedad_ambiente' => random_int(44, 68),
            'luz_ambiental' => random_int(360, 980),
            'ph' => random_int(60, 72) / 10,
            'nivel_tanque' => random_int(52, 96),
            'bateria' => random_int(78, 100),
            'riego_activo' => random_int(0, 5) === 1,
            'rele' => true,
            'bomba_activa' => false,
            'fuente_5v' => random_int(492, 511) / 100,
            'microtubo_ok' => true,
            'caudal_ok' => true,
            'ip' => '192.168.2.' . random_int(20, 240),
        ];
    }

    private static function decorateComponent(array $row): array
    {
        $last = $row['ultima_conexion'] ? strtotime($row['ultima_conexion']) : 0;
        $secondsSinceLast = isset($row['segundos_desde_conexion'])
            ? max(0, (int) $row['segundos_desde_conexion'])
            : max(0, time() - $last);
        $online = $last > 0 && $secondsSinceLast <= self::ONLINE_SECONDS;
        $row['online'] = $online;
        $row['estado_visual'] = $online ? $row['estado'] : 'desconectado';
        $row['estado_texto'] = match ($row['estado_visual']) {
            'conectado' => 'Conectado',
            'advertencia' => 'Advertencia',
            default => 'Desconectado',
        };
        $row['ultima_conexion_relativa'] = $last > 0 ? self::relativeTime($last, $secondsSinceLast) : 'Sin lecturas';

        return $row;
    }

    private static function touchComponent(mysqli $db, int $deviceId, string $code, string $state, string $value, string $detail): void
    {
        $stmt = $db->prepare("
            UPDATE componentes_hardware
            SET estado = ?, valor = ?, detalle = ?, ultima_conexion = NOW()
            WHERE dispositivo_id = ? AND codigo_componente = ?
        ");
        $stmt->bind_param('sssis', $state, $value, $detail, $deviceId, $code);
        $stmt->execute();
    }

    private static function ensureColumn(mysqli $db, string $table, string $column, string $sql): void
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table . $column)) {
            return;
        }

        $escaped = $db->real_escape_string($column);
        $result = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$escaped}'");
        if ($result && $result->num_rows === 0) {
            $db->query($sql);
        }
    }

    private static function number(array $payload, array $keys, ?float $default): ?float
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        return $default;
    }

    private static function stringValue(array $payload, array $keys, string $default): string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }

        return $default;
    }

    private static function boolValue(array $payload, array $keys): int
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $value = $payload[$key];
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }
                if (is_numeric($value)) {
                    return ((float) $value) > 0 ? 1 : 0;
                }

                return in_array(strtolower((string) $value), ['1', 'true', 'on', 'si', 'yes', 'activo'], true) ? 1 : 0;
            }
        }

        return 0;
    }

    private static function hasAny(array $payload, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        return false;
    }

    public static function recordCommand(mysqli $db, int $deviceId, int $usuarioId, string $comando, ?string $parametros = null): array
    {
        self::ensureSchema($db);

        $stmt = $db->prepare("
            INSERT INTO comandos_dispositivo (dispositivo_id, usuario_id, comando, parametros, estado)
            VALUES (?, ?, ?, ?, 'pendiente')
        ");
        $stmt->bind_param('iiss', $deviceId, $usuarioId, $comando, $parametros);
        $stmt->execute();
        $commandId = $stmt->insert_id;

        // Si el comando es activar/desactivar riego, actualizar también la última lectura para respuesta inmediata visual
        if ($comando === 'WATER_PUMP_ON' || $comando === 'WATER_PUMP_OFF' || $comando === 'TOGGLE_PUMP') {
            $riegoVal = ($comando === 'WATER_PUMP_OFF') ? 0 : 1;
            $db->query("
                UPDATE lecturas_sensores
                SET riego_activo = {$riegoVal}
                WHERE dispositivo_id = {$deviceId}
                ORDER BY creada_en DESC LIMIT 1
            ");
            
            $riegoTexto = $riegoVal ? '1' : '0';
            $db->query("
                UPDATE componentes_hardware
                SET valor = '{$riegoTexto}', estado = 'conectado', ultima_conexion = NOW()
                WHERE dispositivo_id = {$deviceId} AND codigo_componente = 'rele'
            ");
        }

        return [
            'success' => true,
            'command_id' => $commandId,
            'comando' => $comando,
            'estado' => 'pendiente',
            'mensaje' => 'Comando enviado correctamente al dispositivo.',
        ];
    }

    public static function getHistoricalReadings(mysqli $db, int $usuarioId, ?int $deviceId = null, ?string $fechaDesde = null, ?string $fechaHasta = null, int $limit = 100): array
    {
        $where = ["d.usuario_id = " . (int)$usuarioId];

        if ($deviceId && $deviceId > 0) {
            $where[] = "l.dispositivo_id = " . (int)$deviceId;
        }

        if ($fechaDesde && preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaDesde)) {
            $safeDesde = $db->real_escape_string($fechaDesde . ' 00:00:00');
            $where[] = "l.creada_en >= '{$safeDesde}'";
        }

        if ($fechaHasta && preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaHasta)) {
            $safeHasta = $db->real_escape_string($fechaHasta . ' 23:59:59');
            $where[] = "l.creada_en <= '{$safeHasta}'";
        }

        $whereSql = implode(' AND ', $where);
        $limitVal = max(1, min(500, (int)$limit));

        $sql = "
            SELECT 
                l.id,
                l.dispositivo_id,
                d.codigo AS dispositivo_codigo,
                d.nombre AS dispositivo_nombre,
                p.nombre AS planta_nombre,
                l.humedad_suelo,
                l.humedad_ambiente,
                l.temperatura,
                l.luz_ambiental,
                l.ph,
                l.bateria,
                l.riego_activo,
                l.creada_en
            FROM lecturas_sensores l
            INNER JOIN dispositivos d ON d.id = l.dispositivo_id
            LEFT JOIN plantas p ON p.id = l.planta_id
            WHERE {$whereSql}
            ORDER BY l.creada_en DESC
            LIMIT {$limitVal}
        ";

        $result = $db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = [
                    'id' => (int) $row['id'],
                    'dispositivo_id' => (int) $row['dispositivo_id'],
                    'dispositivo_codigo' => $row['dispositivo_codigo'],
                    'dispositivo_nombre' => $row['dispositivo_nombre'],
                    'planta_nombre' => $row['planta_nombre'] ?? 'Sin planta',
                    'humedad_suelo' => $row['humedad_suelo'] !== null ? (float) $row['humedad_suelo'] : null,
                    'humedad_ambiente' => $row['humedad_ambiente'] !== null ? (float) $row['humedad_ambiente'] : null,
                    'temperatura' => $row['temperatura'] !== null ? (float) $row['temperatura'] : null,
                    'luz_ambiental' => $row['luz_ambiental'] !== null ? (int) $row['luz_ambiental'] : null,
                    'ph' => $row['ph'] !== null ? (float) $row['ph'] : null,
                    'bateria' => $row['bateria'] !== null ? (int) $row['bateria'] : null,
                    'riego_activo' => (int) $row['riego_activo'],
                    'creada_en' => $row['creada_en'],
                    'fecha_fmt' => date('d/m/Y H:i:s', strtotime($row['creada_en'])),
                ];
            }
        }

        return $rows;
    }

    private static function relativeTime(int $timestamp, ?int $ageSeconds = null): string
    {
        $seconds = $ageSeconds ?? max(0, time() - $timestamp);
        if ($seconds < 5) {
            return 'Ahora';
        }
        if ($seconds < 60) {
            return 'Hace ' . $seconds . ' s';
        }
        if ($seconds < 3600) {
            return 'Hace ' . floor($seconds / 60) . ' min';
        }

        return date('d/m H:i', $timestamp);
    }
}
