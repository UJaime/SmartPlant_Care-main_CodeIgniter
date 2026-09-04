<?php
service('session');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesion para registrar una compra.']);
    exit;
}

require_once APPPATH . 'Libraries/Database.php';

$db         = Database::connect();
$usuario_id = (int) $_SESSION['usuario_id'];
$input      = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

$action = $_GET['action'] ?? $input['action'] ?? '';

crearTablaCompras($db);

if ($action === 'create_mp_preference' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessToken = env('MERCADOPAGO_ACCESS_TOKEN') ?: '';

    if ($accessToken === '' || str_contains($accessToken, 'PEGAR_')) {
        http_response_code(500);
        echo json_encode(['error' => 'Configura MERCADOPAGO_ACCESS_TOKEN en el archivo .env.']);
        exit;
    }

    $stmt = $db->prepare("SELECT nombre, email FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado.']);
        exit;
    }

    $cart = $input['cart'] ?? [];
    if (!is_array($cart) || count($cart) === 0) {
        http_response_code(422);
        echo json_encode(['error' => 'El carrito esta vacio.']);
        exit;
    }

    $items = [];
    $items_normalizados = [];
    $cantidad_items = 0;
    $monto_total = 0.0;

    foreach ($cart as $item) {
        $nombre = trim((string) ($item['name'] ?? 'Producto SmartPlant'));
        $cantidad = max(1, (int) ($item['qty'] ?? 1));
        $precio = round((float) ($item['price'] ?? 0), 2);
        $color = trim((string) ($item['color'] ?? ''));

        if ($precio <= 0) {
            continue;
        }

        $cantidad_items += $cantidad;
        $monto_total += $precio * $cantidad;

        $items[] = [
            'id' => trim((string) ($item['id'] ?? 'smartplant')),
            'title' => $nombre . ($color !== '' ? ' - ' . $color : ''),
            'quantity' => $cantidad,
            'currency_id' => 'ARS',
            'unit_price' => $precio,
        ];

        $items_normalizados[] = [
            'id' => trim((string) ($item['id'] ?? '')),
            'nombre' => $nombre,
            'color' => $color,
            'cantidad' => $cantidad,
            'precio' => $precio,
        ];
    }

    if ($cantidad_items <= 0 || $monto_total <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'La compra debe incluir productos con precio valido.']);
        exit;
    }

    $baseUrl = rtrim(env('app.baseURL') ?: ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/'), '/') . '/';
    $referencia = 'MP-' . $usuario_id . '-' . time();
    $detalle_items = json_encode($items_normalizados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $fecha_pago = date('Y-m-d H:i:s');
    $notas = 'Preferencia de Mercado Pago creada desde la tienda.';

    $preferencePayload = [
        'items' => $items,
        'payer' => [
            'name' => $usuario['nombre'],
            'email' => $usuario['email'],
        ],
        'external_reference' => $referencia,
        'back_urls' => [
            'success' => $baseUrl . 'store',
            'failure' => $baseUrl . 'store',
            'pending' => $baseUrl . 'store',
        ],
        'auto_return' => 'approved',
        'binary_mode' => false,
        'statement_descriptor' => 'SMARTPLANT',
    ];

    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_POSTFIELDS => json_encode($preferencePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $mpData = json_decode($response ?: '{}', true);
    if ($httpCode < 200 || $httpCode >= 300 || empty($mpData['init_point'])) {
        http_response_code(502);
        echo json_encode(['error' => 'Mercado Pago rechazo la preferencia: ' . ($mpData['message'] ?? $curlError ?: 'error desconocido')]);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO compras (
            usuario_id,
            usuario_nombre,
            usuario_email,
            metodo_pago,
            estado,
            moneda,
            monto_total,
            cantidad_items,
            referencia_externa,
            detalle_items,
            notas,
            fecha_pago
        ) VALUES (?, ?, ?, 'mercadopago', 'pendiente', 'ARS', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issdissss",
        $usuario_id,
        $usuario['nombre'],
        $usuario['email'],
        $monto_total,
        $cantidad_items,
        $referencia,
        $detalle_items,
        $notas,
        $fecha_pago
    );
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'preference_id' => $mpData['id'] ?? null,
        'init_point' => shouldUseMercadoPagoSandboxInitPoint() && !empty($mpData['sandbox_init_point'])
            ? $mpData['sandbox_init_point']
            : $mpData['init_point'],
        'sandbox_init_point' => $mpData['sandbox_init_point'] ?? null,
        'referencia_externa' => $referencia,
    ]);
    exit;
}

if ($action === 'confirm_mp_payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessToken = env('MERCADOPAGO_ACCESS_TOKEN') ?: '';
    $paymentId = preg_replace('/[^0-9]/', '', (string) ($input['payment_id'] ?? ''));
    $externalReference = trim((string) ($input['external_reference'] ?? ''));

    if ($accessToken === '' || str_contains($accessToken, 'PEGAR_')) {
        http_response_code(500);
        echo json_encode(['error' => 'Configura MERCADOPAGO_ACCESS_TOKEN en el archivo .env.']);
        exit;
    }

    if ($paymentId === '' || $externalReference === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Faltan datos para confirmar el pago.']);
        exit;
    }

    $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $paymentId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $payment = json_decode($response ?: '{}', true);
    if ($httpCode < 200 || $httpCode >= 300 || empty($payment['id'])) {
        http_response_code(502);
        echo json_encode(['error' => 'No se pudo consultar el pago: ' . ($payment['message'] ?? $curlError ?: 'error desconocido')]);
        exit;
    }

    $mpExternalReference = trim((string) ($payment['external_reference'] ?? ''));
    if ($mpExternalReference !== $externalReference) {
        http_response_code(409);
        echo json_encode(['error' => 'La referencia del pago no coincide con la compra.']);
        exit;
    }

    $estado = mercadoPagoStatusToLocal((string) ($payment['status'] ?? 'pending'));
    $monto = round((float) ($payment['transaction_amount'] ?? 0), 2);
    $notas = 'Pago Mercado Pago #' . $paymentId . ' estado: ' . ($payment['status'] ?? 'sin_estado');
    $fechaPago = !empty($payment['date_approved']) ? date('Y-m-d H:i:s', strtotime($payment['date_approved'])) : date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        UPDATE compras
        SET estado = ?,
            monto_total = IF(? > 0, ?, monto_total),
            notas = ?,
            fecha_pago = ?
        WHERE usuario_id = ? AND referencia_externa = ?
        LIMIT 1
    ");
    $stmt->bind_param("sddssis", $estado, $monto, $monto, $notas, $fechaPago, $usuario_id, $externalReference);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontro la compra para actualizar.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'estado' => $estado,
        'payment_id' => $paymentId,
        'referencia_externa' => $externalReference,
    ]);
    exit;
}

if ($action !== 'create' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Accion no valida.']);
    exit;
}

$stmt = $db->prepare("SELECT nombre, email FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado.']);
    exit;
}

$metodo_pago = trim((string) ($input['metodo_pago'] ?? ''));
$estado      = trim((string) ($input['estado'] ?? 'pendiente'));
$moneda      = strtoupper(trim((string) ($input['moneda'] ?? 'ARS')));
$referencia  = trim((string) ($input['referencia_externa'] ?? ''));
$notas       = trim((string) ($input['notas'] ?? ''));
$cart        = $input['cart'] ?? [];

if ($metodo_pago === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Debes indicar el metodo de pago.']);
    exit;
}

$estados_validos = ['pendiente', 'aprobado', 'rechazado', 'cancelado', 'reembolsado'];
if (!in_array($estado, $estados_validos, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Estado de compra invalido.']);
    exit;
}

if (!is_array($cart)) {
    http_response_code(422);
    echo json_encode(['error' => 'El carrito recibido no es valido.']);
    exit;
}

$items_normalizados = [];
$cantidad_items     = 0;
$monto_total        = 0.0;

foreach ($cart as $item) {
    $nombre   = trim((string) ($item['name'] ?? 'Producto'));
    $cantidad = max(1, (int) ($item['qty'] ?? 1));
    $precio   = round((float) ($item['price'] ?? 0), 2);
    $color    = trim((string) ($item['color'] ?? ''));

    $cantidad_items += $cantidad;
    $monto_total    += $precio * $cantidad;

    $items_normalizados[] = [
        'id'       => trim((string) ($item['id'] ?? '')),
        'nombre'   => $nombre,
        'color'    => $color,
        'cantidad' => $cantidad,
        'precio'   => $precio,
    ];
}

$monto_payload = isset($input['monto_total']) ? round((float) $input['monto_total'], 2) : null;
if ($monto_payload !== null && $monto_payload > 0) {
    $monto_total = $monto_payload;
}

if ($cantidad_items <= 0 || $monto_total <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'La compra debe incluir al menos un producto con monto valido.']);
    exit;
}

$fecha_pago_input = trim((string) ($input['fecha_pago'] ?? ''));
$fecha_pago_ts    = $fecha_pago_input !== '' ? strtotime($fecha_pago_input) : time();
$fecha_pago       = $fecha_pago_ts ? date('Y-m-d H:i:s', $fecha_pago_ts) : date('Y-m-d H:i:s');
$detalle_items    = json_encode($items_normalizados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$stmt = $db->prepare("
    INSERT INTO compras (
        usuario_id,
        usuario_nombre,
        usuario_email,
        metodo_pago,
        estado,
        moneda,
        monto_total,
        cantidad_items,
        referencia_externa,
        detalle_items,
        notas,
        fecha_pago
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssssdissss",
    $usuario_id,
    $usuario['nombre'],
    $usuario['email'],
    $metodo_pago,
    $estado,
    $moneda,
    $monto_total,
    $cantidad_items,
    $referencia,
    $detalle_items,
    $notas,
    $fecha_pago
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar la compra.']);
    exit;
}

echo json_encode([
    'success'   => true,
    'compra_id' => $db->insert_id,
    'data'      => [
        'usuario'        => $usuario['nombre'],
        'metodo_pago'    => $metodo_pago,
        'estado'         => $estado,
        'monto_total'    => $monto_total,
        'cantidad_items' => $cantidad_items,
        'fecha_pago'     => $fecha_pago,
    ]
]);
exit;

function crearTablaCompras(mysqli $db): void {
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
}

function mercadoPagoStatusToLocal(string $status): string {
    return match ($status) {
        'approved', 'authorized' => 'aprobado',
        'rejected' => 'rechazado',
        'cancelled' => 'cancelado',
        'refunded', 'charged_back' => 'reembolsado',
        default => 'pendiente',
    };
}

function shouldUseMercadoPagoSandboxInitPoint(): bool {
    $value = strtolower((string) (env('MERCADOPAGO_USE_SANDBOX_INIT_POINT') ?: 'false'));
    return in_array($value, ['1', 'true', 'yes', 'si'], true);
}
