<?php
// ═══════════════════════════════════════════════════════════════
//  ai_assistant.php — SmartPlant CARE
//  Controlador para la integración con Gemini API
// ═══════════════════════════════════════════════════════════════
service('session');
header('Content-Type: application/json; charset=utf-8');

require_once APPPATH . 'Libraries/Database.php';

$GEMINI_API_KEY = "AIzaSyDB1CN6hiFKeVp1hZeL4tFEXsubFUvk6dI";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::connect();
    $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
    
    // Soporte para JSON body o multipart POST
    $input_json = json_decode(file_get_contents('php://input'), true);
    $planta_id = isset($_POST['planta_id']) ? (int)$_POST['planta_id'] : (isset($input_json['planta_id']) ? (int)$input_json['planta_id'] : 0);
    $mensaje_usuario = trim($_POST['mensaje'] ?? ($input_json['mensaje'] ?? ''));

    if (empty($mensaje_usuario) && empty($_FILES['imagen']['name'])) {
        echo json_encode(['error' => 'El mensaje no puede estar vacío']);
        exit;
    }

    // ── 1. Obtener contexto de la planta (si el usuario está logueado) ─
    $contexto = "";
    if ($usuario_id && $planta_id) {
        $stmt = $db->prepare("SELECT * FROM plantas WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $planta_id, $usuario_id);
        $stmt->execute();
        $planta = $stmt->get_result()->fetch_assoc();

        if ($planta) {
            $stmt = $db->prepare("
                SELECT l.* FROM lecturas_sensores l
                WHERE l.planta_id = ?
                ORDER BY l.creada_en DESC LIMIT 1
            ");
            $stmt->bind_param("i", $planta_id);
            $stmt->execute();
            $ultima = $stmt->get_result()->fetch_assoc();

            $contexto = "Eres el asistente inteligente oficial de SmartPlant CARE. Estás asesorando a un usuario sobre su planta llamada '{$planta['nombre']}' (Especie: {$planta['especie']}).\n";
            if ($ultima) {
                $contexto .= "Lecturas en tiempo real de los sensores IoT:\n";
                $contexto .= "- Humedad del suelo: {$ultima['humedad_suelo']}%\n";
                $contexto .= "- Temperatura: {$ultima['temperatura']}°C\n";
                $contexto .= "- Luz solar / ambiental: {$ultima['luz_ambiental']} lx\n";
                $contexto .= "- Nivel de agua en tanque: {$ultima['nivel_tanque']}%\n";
                $contexto .= "- Batería del dispositivo: {$ultima['bateria']}%\n";
            } else {
                $contexto .= "Esta planta todavía no tiene lecturas de sensores registradas.\n";
            }
            $contexto .= "Responde en español de forma concisa, cálida y profesional. La humedad óptima suele ser 40-60% y temperatura 20-28°C.\n";
        }
    }

    if (empty($contexto)) {
        $contexto = "Eres el asistente experto oficial de SmartPlant CARE (un sistema IoT inteligente para el cuidado, monitoreo y riego automatizado de plantas con energía solar y sensores de precisión). Responde preguntas sobre cuidado botánico, riego, especies de plantas, diagnóstico de plagas y sobre las soluciones de SmartPlant CARE de manera clara, amable, útil y concisa en español.";
    }

    // ── 2. Preparar el payload para Gemini ──────────────────────────
    $parts = [];
    
    // Si hay imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $img_tmp = $_FILES['imagen']['tmp_name'];
        $img_type = $_FILES['imagen']['type'];
        $img_data = file_get_contents($img_tmp);
        $img_base64 = base64_encode($img_data);
        
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($img_type, $allowed_mime)) {
            $parts[] = [
                "inline_data" => [
                    "mime_type" => $img_type,
                    "data" => $img_base64
                ]
            ];
        } else {
            echo json_encode(['error' => 'Formato de imagen no soportado. Usa JPG, PNG o WEBP.']);
            exit;
        }
    }

    $parts[] = [
        "text" => "Instrucciones de contexto del sistema:\n" . $contexto . "\n\nMensaje o consulta del usuario:\n" . $mensaje_usuario
    ];

    $data = [
        "contents" => [
            [
                "parts" => $parts
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800
        ]
    ];

    $json_data = json_encode($data);

    // ── 3. Llamar a la API de Gemini (con modelos activos y probados) ──
    $modelos_fallback = [
        'gemini-2.5-flash',
        'gemini-3.5-flash',
        'gemini-flash-lite-latest',
        'gemma-4-31b-it'
    ];

    $response = null;
    $http_code = 0;
    $error = null;

    foreach ($modelos_fallback as $modelo) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelo . ':generateContent?key=' . $GEMINI_API_KEY;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$error && $http_code == 200) {
            break;
        }
        
        if ($http_code != 503 && $http_code != 429 && $http_code != 404) {
             break;
        }
    }

    if ($error) {
        echo json_encode(['error' => 'Error de conexión: ' . $error]);
        exit;
    }

    if ($http_code != 200) {
        $resp_dec = json_decode($response, true);
        $err_msg = $resp_dec['error']['message'] ?? 'Error desconocido de la API de IA';
        echo json_encode(['error' => 'Error de IA: ' . $err_msg]);
        exit;
    }

    $result = json_decode($response, true);
    $texto_respuesta = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No pude generar una respuesta en este momento. Por favor intentá nuevamente.';

    echo json_encode(['respuesta' => trim($texto_respuesta)]);
    exit;
}
?>
