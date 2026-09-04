<?php
// ═══════════════════════════════════════════════════════════════
//  inventory_controller.php — SmartPlant CARE
//  Maneja el inventario fotográfico de plantas con diagnóstico IA
//  Acciones: upload, list, delete
// ═══════════════════════════════════════════════════════════════
service('session');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once APPPATH . 'Libraries/Database.php';

$db         = Database::connect();
$usuario_id = (int) $_SESSION['usuario_id'];
$action     = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Auto-crear tabla si no existe ─────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS inventario_plantas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    foto_path VARCHAR(255) NOT NULL,
    diagnostico TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_planta (planta_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ═══════════════════════════════════════════════════════════════
//  ACTION: upload — Subir foto + diagnóstico IA
// ═══════════════════════════════════════════════════════════════
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $planta_id = (int) ($_POST['planta_id'] ?? 0);
    if (!$planta_id) {
        echo json_encode(['error' => 'No se especificó la planta.']);
        exit;
    }

    // Verificar que la planta pertenece al usuario
    $stmt = $db->prepare("SELECT id, nombre, especie FROM plantas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $planta_id, $usuario_id);
    $stmt->execute();
    $planta = $stmt->get_result()->fetch_assoc();

    if (!$planta) {
        echo json_encode(['error' => 'Planta no encontrada.']);
        exit;
    }

    // Validar imagen
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'No se recibió ninguna imagen.']);
        exit;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime    = $_FILES['foto']['type'];
    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => 'Formato no soportado. Usá JPG, PNG o WEBP.']);
        exit;
    }

    // Guardar la imagen
    $upload_dir = defined('FCPATH')
        ? FCPATH . 'assets/uploads/inventario/'
        : __DIR__ . '/../assets/uploads/inventario/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $filename = 'inv_' . $usuario_id . '_' . $planta_id . '_' . time() . '.' . strtolower($ext);
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $filepath)) {
        echo json_encode(['error' => 'Error al guardar la imagen.']);
        exit;
    }

    $foto_url = '/assets/uploads/inventario/' . $filename;

    // ── Obtener diagnóstico de la IA ──────────────────────────
    $diagnostico = obtenerDiagnosticoIA($filepath, $mime, $planta, $db, $planta_id);

    // ── Guardar en la base de datos ───────────────────────────
    $fecha = date('Y-m-d H:i:s');
    $stmt  = $db->prepare("INSERT INTO inventario_plantas (planta_id, usuario_id, foto_path, diagnostico, fecha) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $planta_id, $usuario_id, $foto_url, $diagnostico, $fecha);

    if ($stmt->execute()) {
        $nuevo_id = $db->insert_id;
        echo json_encode([
            'success' => true,
            'entry'   => [
                'id'           => $nuevo_id,
                'foto_path'    => $foto_url,
                'diagnostico'  => $diagnostico,
                'fecha'        => $fecha,
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Error al guardar en la base de datos.']);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: list — Listar inventario de una planta
// ═══════════════════════════════════════════════════════════════
if ($action === 'list') {
    $planta_id = (int) ($_GET['planta_id'] ?? 0);

    $stmt = $db->prepare("
        SELECT id, foto_path, diagnostico, fecha
        FROM inventario_plantas
        WHERE planta_id = ? AND usuario_id = ?
        ORDER BY fecha DESC
        LIMIT 50
    ");
    $stmt->bind_param("ii", $planta_id, $usuario_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['entries' => $rows]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: delete — Eliminar una entrada del inventario
// ═══════════════════════════════════════════════════════════════
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = (int) ($_POST['id'] ?? 0);

    // Obtener la entrada para borrar el archivo
    $stmt = $db->prepare("SELECT foto_path FROM inventario_plantas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $entry_id, $usuario_id);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();

    if ($entry) {
        // Borrar archivo físico
        $foto_path = (string) $entry['foto_path'];
        $relative  = ltrim($foto_path, '/');
        $base_path = defined('FCPATH') ? FCPATH : ROOTPATH . 'public' . DIRECTORY_SEPARATOR;
        $real_path = $base_path . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (file_exists($real_path)) {
            @unlink($real_path);
        }

        $stmt = $db->prepare("DELETE FROM inventario_plantas WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $entry_id, $usuario_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Entrada no encontrada.']);
    }
    exit;
}

echo json_encode(['error' => 'Acción no válida.']);
exit;

// ═══════════════════════════════════════════════════════════════
//  Función: Obtener diagnóstico de Gemini
// ═══════════════════════════════════════════════════════════════
function obtenerDiagnosticoIA(string $filepath, string $mime, array $planta, $db, int $planta_id): string {
    $GEMINI_API_KEY = "AIzaSyDB1CN6hiFKeVp1hZeL4tFEXsubFUvk6dI";

    // Leer imagen
    $img_data   = file_get_contents($filepath);
    $img_base64 = base64_encode($img_data);

    // Obtener última lectura de sensores para contexto
    $contexto_sensores = "";
    $stmt = $db->prepare("SELECT * FROM lecturas_sensores WHERE planta_id = ? ORDER BY creada_en DESC LIMIT 1");
    $stmt->bind_param("i", $planta_id);
    $stmt->execute();
    $ultima = $stmt->get_result()->fetch_assoc();
    if ($ultima) {
        $contexto_sensores = "\nDatos actuales de los sensores:\n";
        $contexto_sensores .= "- Humedad del suelo: {$ultima['humedad_suelo']}%\n";
        $contexto_sensores .= "- Temperatura: {$ultima['temperatura']}°C\n";
        $contexto_sensores .= "- Luz ambiental: {$ultima['luz_ambiental']} lx\n";
    }

    $prompt = "Eres un fitopatólogo experto de SmartPlant CARE. Analiza esta foto de la planta '{$planta['nombre']}' (especie: {$planta['especie']}).{$contexto_sensores}\n\n"
        . "Proporcioná un diagnóstico visual conciso que incluya:\n"
        . "1. Estado general (saludable, estresada, enferma, etc.)\n"
        . "2. Observaciones visuales relevantes (color de hojas, manchas, etc.)\n"
        . "3. Una recomendación breve si es necesario\n\n"
        . "Respondé en español, en máximo 3-4 líneas. Usá emojis relevantes. NO uses formato markdown ni negritas.";

    $data = [
        "contents" => [[
            "parts" => [
                ["inline_data" => ["mime_type" => $mime, "data" => $img_base64]],
                ["text" => $prompt]
            ]
        ]],
        "generationConfig" => [
            "temperature" => 0.5,
            "maxOutputTokens" => 400
        ]
    ];

    $json_data = json_encode($data);

    // Llamar a Gemini con fallback de modelos
    $modelos = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-flash-lite-latest'];

    foreach ($modelos as $modelo) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelo . ':generateContent?key=' . $GEMINI_API_KEY;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            $texto  = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($texto) {
                return trim($texto);
            }
        }

        if ($http_code !== 503 && $http_code !== 429) break;
    }

    return "⚠️ No se pudo obtener un diagnóstico automático. Revisá la foto manualmente.";
}
