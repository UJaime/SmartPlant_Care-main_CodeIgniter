<?php

use App\Libraries\AuthSecurity;

require_once APPPATH . 'Libraries/AuthSecurity.php';
require_once APPPATH . 'Libraries/Database.php';
require_once APPPATH . 'Libraries/HardwareKit.php';

AuthSecurity::startSession();

header('Content-Type: application/json; charset=utf-8');

$db = Database::connect();
HardwareKit::ensureSchema($db);

$input = json_decode(file_get_contents('php://input'), true);
if (! is_array($input)) {
    $input = $_POST;
}

$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'telemetry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceCode = trim((string) ($input['device_code'] ?? $input['codigo'] ?? ''));
    $apiKey = trim((string) ($input['api_key'] ?? ''));

    if ($deviceCode === '' || $apiKey === '') {
        jsonResponse(['error' => 'Faltan device_code y api_key.'], 422);
    }

    $device = HardwareKit::findDeviceByCode($db, $deviceCode);
    if (! $device) {
        jsonResponse(['error' => 'Dispositivo no encontrado.'], 404);
    }

    if (! hash_equals((string) ($device['api_key'] ?? ''), $apiKey)) {
        jsonResponse(['error' => 'API key invalida para este dispositivo.'], 403);
    }

    $result = HardwareKit::recordTelemetry($db, $device, $input);

    jsonResponse([
        'success' => true,
        'message' => 'Telemetria recibida.',
        'reading' => $result['reading'],
        'components' => $result['components'],
        'analysis' => $result['analysis'],
    ]);
}

if (empty($_SESSION['usuario_id'])) {
    jsonResponse(['error' => 'Debes iniciar sesion.'], 401);
}

$usuarioId = (int) $_SESSION['usuario_id'];
$deviceId = (int) ($_GET['device_id'] ?? $input['device_id'] ?? 0);

$device = loadOwnedDevice($db, $usuarioId, $deviceId);
if (! $device) {
    jsonResponse(['error' => 'Dispositivo no encontrado para este usuario.'], 404);
}

if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(devicePayload($db, $device));
}

if ($action === 'simulate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = HardwareKit::simulatePayload();
    $result = HardwareKit::recordTelemetry($db, $device, $payload);

    jsonResponse([
        'success' => true,
        'message' => 'Lectura de prueba recibida.',
        'simulated_payload' => $payload,
        'reading' => $result['reading'],
        'components' => $result['components'],
        'analysis' => $result['analysis'],
    ]);
}

if ($action === 'history' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $fechaDesde = trim((string) ($_GET['desde'] ?? $_GET['fecha_desde'] ?? ''));
    $fechaHasta = trim((string) ($_GET['hasta'] ?? $_GET['fecha_hasta'] ?? ''));
    $limit = (int) ($_GET['limit'] ?? 100);

    $readings = HardwareKit::getHistoricalReadings(
        $db,
        $usuarioId,
        $deviceId > 0 ? $deviceId : null,
        $fechaDesde !== '' ? $fechaDesde : null,
        $fechaHasta !== '' ? $fechaHasta : null,
        $limit
    );

    jsonResponse([
        'success' => true,
        'readings' => $readings,
        'count' => count($readings),
    ]);
}

if (($action === 'control' || $action === 'command') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $comando = trim((string) ($input['comando'] ?? $input['command'] ?? ''));
    $parametros = isset($input['parametros']) ? (is_array($input['parametros']) ? json_encode($input['parametros']) : (string) $input['parametros']) : null;

    if ($comando === '') {
        jsonResponse(['error' => 'Debes especificar el comando.'], 422);
    }

    $result = HardwareKit::recordCommand($db, (int) $device['id'], $usuarioId, $comando, $parametros);
    jsonResponse($result);
}

jsonResponse(['error' => 'Accion no valida.'], 400);

function loadOwnedDevice(mysqli $db, int $usuarioId, int $deviceId): ?array
{
    $db->query("UPDATE dispositivos SET usuario_id = " . (int) $usuarioId . " WHERE codigo = 'SPC-DEMO-FICUS-001'");

    if ($deviceId > 0) {
        $stmt = $db->prepare("
            SELECT d.*, p.nombre AS planta_nombre
            FROM dispositivos d
            INNER JOIN plantas p ON p.id = d.planta_id
            WHERE d.id = ? AND d.usuario_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $deviceId, $usuarioId);
    } else {
        $stmt = $db->prepare("
            SELECT d.*, p.nombre AS planta_nombre
            FROM dispositivos d
            INNER JOIN plantas p ON p.id = d.planta_id
            WHERE d.usuario_id = ?
            ORDER BY (d.ultima_conexion IS NOT NULL) DESC, d.ultima_conexion DESC, d.id ASC
            LIMIT 1
        ");
        $stmt->bind_param('i', $usuarioId);
    }

    $stmt->execute();
    $device = $stmt->get_result()->fetch_assoc();

    return $device ?: null;
}

function devicePayload(mysqli $db, array $device): array
{
    $components = HardwareKit::getComponentsForDevice($db, (int) $device['id']);
    $latest = HardwareKit::getLatestReading($db, (int) $device['id']);

    return [
        'success' => true,
        'device' => [
            'id' => (int) $device['id'],
            'codigo' => $device['codigo'],
            'nombre' => $device['nombre'],
            'planta' => $device['planta_nombre'] ?? $device['corresponde_a'],
            'ultima_conexion' => $device['ultima_conexion'],
        ],
        'components' => $components,
        'reading' => $latest,
        'analysis' => HardwareKit::processReading($latest, $components),
    ];
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
