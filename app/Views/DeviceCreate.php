<?php
service('session');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login");
    exit;
}

require_once APPPATH . 'Libraries/Database.php';
require_once APPPATH . 'Libraries/HardwareKit.php';

$db = Database::connect();
$usuario_id = (int) $_SESSION['usuario_id'];
$error = '';
$success = '';
$createdDevice = null;

$db->query("CREATE TABLE IF NOT EXISTS dispositivos (
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
    KEY idx_dispositivos_planta (planta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$checkCol = $db->query("SHOW COLUMNS FROM dispositivos LIKE 'tipo_dispositivo'");
if ($checkCol && $checkCol->num_rows === 0) {
    $db->query("ALTER TABLE dispositivos ADD COLUMN tipo_dispositivo VARCHAR(80) NOT NULL DEFAULT 'Sensor IoT' AFTER nombre");
}

$checkCol = $db->query("SHOW COLUMNS FROM dispositivos LIKE 'corresponde_a'");
if ($checkCol && $checkCol->num_rows === 0) {
    $db->query("ALTER TABLE dispositivos ADD COLUMN corresponde_a VARCHAR(120) NOT NULL DEFAULT 'Planta' AFTER tipo_dispositivo");
}

$checkCol = $db->query("SHOW COLUMNS FROM dispositivos LIKE 'api_key'");
if ($checkCol && $checkCol->num_rows === 0) {
    $db->query("ALTER TABLE dispositivos ADD COLUMN api_key VARCHAR(64) DEFAULT NULL AFTER codigo");
}

HardwareKit::ensureSchema($db);

$stmt = $db->prepare("SELECT id, nombre, especie FROM plantas WHERE usuario_id = ? AND activa = 1 ORDER BY nombre ASC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$plantas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = trim($_POST['tipo_dispositivo'] ?? '');
    $planta_id = (int) ($_POST['planta_id'] ?? 0);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');

    $planta = null;
    foreach ($plantas as $p) {
        if ((int) $p['id'] === $planta_id) {
            $planta = $p;
            break;
        }
    }

    if ($nombre === '' || $tipo === '' || !$planta) {
        $error = 'Completa el nombre, tipo y planta correspondiente.';
    } else {
        if ($codigo === '') {
            $codigo = 'SPC-' . $usuario_id . '-' . strtoupper(bin2hex(random_bytes(3)));
        }

        $corresponde_a = $planta['nombre'];
        $apiKey = bin2hex(random_bytes(16));
        $stmt = $db->prepare("
            INSERT INTO dispositivos (usuario_id, planta_id, codigo, api_key, nombre, tipo_dispositivo, corresponde_a, ubicacion, firmware, activo, ultima_conexion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, '1.0.0', 1, NULL)
        ");
        $stmt->bind_param("iissssss", $usuario_id, $planta_id, $codigo, $apiKey, $nombre, $tipo, $corresponde_a, $ubicacion);

        if ($stmt->execute()) {
            $newDeviceId = (int) $db->insert_id;
            HardwareKit::syncComponents($db, $newDeviceId);
            $createdDevice = [
                'id' => $newDeviceId,
                'codigo' => $codigo,
                'api_key' => $apiKey,
            ];
            $success = 'Dispositivo dado de alta correctamente. Ya podes conectarlo desde Hardware.';
            $_POST = [];
        } else {
            $error = 'No se pudo guardar el dispositivo. Revisa si el codigo ya existe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de dispositivo - SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="bg-overlay text-white min-h-screen">
<header class="sticky top-6 z-50 mx-auto max-w-5xl px-4">
    <div class="glass-clean flex items-center justify-between px-8 py-4 rounded-[2.5rem]">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-9 h-9 transition-transform group-hover:scale-105 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.35)]">
            <span class="text-2xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <nav class="hidden md:flex gap-6 items-center text-sm font-medium text-slate-700">
            <a href="/dashboard" class="hover:text-emerald-600 transition-colors">Dashboard</a>
            <a href="/hardware/connect" class="hover:text-emerald-600 transition-colors">Hardware</a>
            <a href="/store" class="hover:text-emerald-600 transition-colors">Tienda</a>
            <a href="/dashboard?logout=1" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-full font-semibold hover:bg-red-100 transition-all">Salir</a>
        </nav>
    </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-20">
    <section class="grid lg:grid-cols-[0.9fr_1.1fr] gap-10 items-start">
        <div class="pt-6">
            <span class="text-white/50 font-semibold tracking-[0.2em] text-xs uppercase">Alta de dispositivo</span>
            <h1 class="text-4xl md:text-6xl font-semibold tracking-tight mt-4 mb-6">Conecta un nuevo SmartPlant.</h1>
            <p class="text-gray-400 text-lg font-light leading-relaxed">
                Registra el nombre del dispositivo, el tipo de equipo y la planta o sector al que corresponde para que aparezca en tu sistema.
            </p>
        </div>

        <form method="POST" class="glass-form rounded-[2rem] p-8 md:p-10 space-y-5">
            <?php if ($error): ?>
                <div class="text-red-300 text-sm bg-red-500/10 border border-red-500/20 rounded-xl p-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="text-green-300 text-sm bg-green-500/10 border border-green-500/20 rounded-xl p-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($createdDevice): ?>
                <div class="text-sm bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-widest mb-1">Codigo</p>
                        <code class="break-all"><?= htmlspecialchars($createdDevice['codigo']) ?></code>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-widest mb-1">API key</p>
                        <code class="break-all"><?= htmlspecialchars($createdDevice['api_key']) ?></code>
                    </div>
                    <a href="/hardware/connect?device=<?= (int) $createdDevice['id'] ?>" class="inline-flex bg-white text-black px-5 py-2.5 rounded-full text-xs font-bold">Abrir conexion de hardware</a>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">Nombre del dispositivo</label>
                <input name="nombre" required maxlength="100" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Aurea One Patio" class="input-glass w-full rounded-2xl px-5 py-4 text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">Tipo de dispositivo</label>
                <select name="tipo_dispositivo" required class="input-glass w-full rounded-2xl px-5 py-4 text-sm">
                    <?php foreach (['Sensor de humedad y ambiente', 'Kit de riego automatico', 'Panel solar', 'Controlador ESP32', 'Otro'] as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo) ?>" <?= (($_POST['tipo_dispositivo'] ?? '') === $tipo) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">A donde corresponde</label>
                <select name="planta_id" required class="input-glass w-full rounded-2xl px-5 py-4 text-sm">
                    <option value="">Seleccionar planta o sector</option>
                    <?php foreach ($plantas as $planta): ?>
                        <option value="<?= $planta['id'] ?>" <?= ((int)($_POST['planta_id'] ?? 0) === (int)$planta['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($planta['nombre'] . ' - ' . $planta['especie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">Ubicacion</label>
                    <input name="ubicacion" maxlength="120" value="<?= htmlspecialchars($_POST['ubicacion'] ?? '') ?>" placeholder="Patio norte" class="input-glass w-full rounded-2xl px-5 py-4 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">Codigo opcional</label>
                    <input name="codigo" maxlength="80" value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" placeholder="SPC-PATIO-001" class="input-glass w-full rounded-2xl px-5 py-4 text-sm">
                </div>
            </div>

            <button type="submit" class="btn-glow w-full bg-white text-black py-4 rounded-2xl font-semibold">Guardar dispositivo</button>
            <a href="/dashboard" class="block text-center text-white/50 text-sm hover:text-white">Volver al dashboard</a>
        </form>
    </section>
</main>
</body>
</html>
