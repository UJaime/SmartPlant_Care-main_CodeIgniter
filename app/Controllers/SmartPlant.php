<?php

namespace App\Controllers;

use App\Models\PlantaModel;
use App\Models\UsuarioModel;
use App\Models\DispositivoModel;
use App\Models\LecturaModel;
use App\Models\EventoModel;
use App\Models\InventarioModel;
use App\Libraries\AuthSecurity;
use CodeIgniter\Exceptions\PageNotFoundException;

class SmartPlant extends BaseController
{
    protected PlantaModel $plantaModel;
    protected UsuarioModel $usuarioModel;
    protected DispositivoModel $dispositivoModel;
    protected LecturaModel $lecturaModel;
    protected EventoModel $eventoModel;
    protected InventarioModel $inventarioModel;

    public function __construct()
    {
        $this->plantaModel      = new PlantaModel();
        $this->usuarioModel     = new UsuarioModel();
        $this->dispositivoModel = new DispositivoModel();
        $this->lecturaModel     = new LecturaModel();
        $this->eventoModel      = new EventoModel();
        $this->inventarioModel  = new InventarioModel();
    }

    public function index(): string
    {
        return view('index');
    }

    /**
     * Controlador para el Dashboard principal
     * Coordina los modelos de Usuario, Plantas, Sensores, Eventos e Inventario.
     */
    public function dashboard(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        AuthSecurity::startSession();

        // 1. Verificación de autenticación
        if (!isset($_SESSION['usuario_id'])) {
            return redirect()->to('/login');
        }

        // Manejo de logout
        if ($this->request->getGet('logout') !== null) {
            AuthSecurity::logout('/login');
            return redirect()->to('/login');
        }

        $usuario_id = (int) $_SESSION['usuario_id'];
        $msg_perfil   = "";
        $msg_settings = "";
        $msg_planta   = "";

        // 2. Procesamiento de acciones POST desde formularios
        if ($this->request->is('post')) {

            // A) Actualizar perfil
            if ($this->request->getPost('update_profile') !== null) {
                $nuevo_nombre   = trim($this->request->getPost('nombre') ?? '');
                $nuevo_apellido = trim($this->request->getPost('apellido') ?? '');
                $nuevo_email    = trim($this->request->getPost('email') ?? '');
                $nuevo_telefono = trim($this->request->getPost('telefono') ?? '');
                $nueva_password = $this->request->getPost('password') ?? '';

                if ($nuevo_nombre === '' || !filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
                    $msg_perfil = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>Revisá el nombre y el correo electrónico.</div>";
                } elseif ($nueva_password !== '' && strlen($nueva_password) < 8) {
                    $msg_perfil = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>La nueva contraseña debe tener al menos 8 caracteres.</div>";
                } else {
                    $usuarioActual = $this->usuarioModel->find($usuario_id);
                    $foto_path = $usuarioActual['foto_perfil'] ?? '';
                    $file = $this->request->getFile('foto_perfil');

                    if ($file && $file->isValid() && !$file->hasMoved()) {
                        $upload_dir = defined('FCPATH') ? FCPATH . 'assets/uploads/' : APPPATH . '../public/assets/uploads/';
                        if (!is_dir($upload_dir)) {
                            @mkdir($upload_dir, 0777, true);
                        }
                        $ext = $file->getClientExtension();
                        $newName = 'perfil_' . $usuario_id . '_' . time() . '.' . $ext;
                        $file->move($upload_dir, $newName);
                        $foto_path = '/assets/uploads/' . $newName;
                    }

                    $datosUpdate = [
                        'nombre'      => $nuevo_nombre,
                        'apellido'    => $nuevo_apellido,
                        'email'       => $nuevo_email,
                        'telefono'    => $nuevo_telefono,
                        'foto_perfil' => $foto_path,
                    ];

                    if (!empty($nueva_password)) {
                        $datosUpdate['password'] = password_hash($nueva_password, PASSWORD_DEFAULT);
                    }

                    if ($this->usuarioModel->actualizarPerfil($usuario_id, $datosUpdate)) {
                        $msg_perfil = "<div class='text-green-400 text-sm mb-4 bg-green-500/10 p-3 rounded-xl border border-green-500/20'>Perfil actualizado correctamente.</div>";
                        $_SESSION['foto_perfil'] = $foto_path;
                        $_SESSION['nombre']      = $nuevo_nombre;
                        $_SESSION['usuario']     = $nuevo_email;
                    } else {
                        $msg_perfil = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>Error al actualizar el perfil.</div>";
                    }
                }
            }

            // B) Actualizar configuración / umbrales de la planta
            if ($this->request->getPost('update_settings') !== null) {
                $pl_id = (int) $this->request->getPost('planta_id');
                $limites = [
                    'humedad_min' => (float) $this->request->getPost('humedad_min'),
                    'humedad_max' => (float) $this->request->getPost('humedad_max'),
                    'temp_min'    => (float) $this->request->getPost('temp_min'),
                    'temp_max'    => (float) $this->request->getPost('temp_max'),
                ];
                if ($this->plantaModel->actualizarLimites($pl_id, $usuario_id, $limites)) {
                    $msg_settings = "<div class='text-green-400 text-sm mb-4 bg-green-500/10 p-3 rounded-xl border border-green-500/20'>Configuración guardada correctamente.</div>";
                } else {
                    $msg_settings = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>Error al guardar configuración.</div>";
                }
            }

            // C) Agregar nueva planta
            if ($this->request->getPost('add_plant') !== null) {
                $nombre_p = trim($this->request->getPost('nombre_planta') ?? '');
                if ($nombre_p === '') {
                    $msg_planta = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>Ingresá un nombre para la planta.</div>";
                } else {
                    $nuevaPlanta = [
                        'usuario_id'   => $usuario_id,
                        'nombre'       => $nombre_p,
                        'especie'      => trim($this->request->getPost('especie_planta') ?? 'Por definir'),
                        'descripcion'  => trim($this->request->getPost('descripcion_planta') ?? ''),
                        'humedad_min'  => (float) ($this->request->getPost('humedad_min') ?? 35),
                        'humedad_max'  => (float) ($this->request->getPost('humedad_max') ?? 65),
                        'temp_min'     => (float) ($this->request->getPost('temp_min') ?? 15),
                        'temp_max'     => (float) ($this->request->getPost('temp_max') ?? 35),
                    ];
                    $nuevo_id = $this->plantaModel->registrarPlanta($nuevaPlanta);
                    if ($nuevo_id) {
                        return redirect()->to('/dashboard?planta=' . $nuevo_id);
                    } else {
                        $msg_planta = "<div class='text-red-400 text-sm mb-4 bg-red-500/10 p-3 rounded-xl border border-red-500/20'>Error al registrar la planta.</div>";
                    }
                }
            }

            // D) Eliminar planta (baja lógica)
            if ($this->request->getPost('delete_plant') !== null) {
                $del_planta_id = (int) $this->request->getPost('planta_id');
                $this->plantaModel->eliminarPlanta($del_planta_id, $usuario_id);
                return redirect()->to('/dashboard');
            }
        }

        // 3. Consulta de datos mediante los Modelos
        $usuario_data = $this->usuarioModel->find($usuario_id) ?? [];
        $plantas      = $this->plantaModel->activasPorUsuario($usuario_id);
        $dispositivos = $this->dispositivoModel->obtenerPorUsuario($usuario_id);

        $planta_param = (int) ($this->request->getGet('planta') ?? 0);
        $planta_id    = $planta_param ?: ($plantas[0]['id'] ?? 0);

        $planta_actual = null;
        foreach ($plantas as $p) {
            if ((int)$p['id'] === (int)$planta_id) {
                $planta_actual = $p;
                break;
            }
        }
        if (!$planta_actual && count($plantas) > 0) {
            $planta_actual = $plantas[0];
            $planta_id = (int) $planta_actual['id'];
        }

        // Métricas de sensores y telemetría
        $ultima     = $planta_id ? $this->lecturaModel->obtenerUltimaLectura($planta_id) : null;
        $historial  = $planta_id ? $this->lecturaModel->obtenerHistorialHumedad($planta_id) : [];
        $eventos    = $planta_id ? $this->eventoModel->obtenerRecientes($planta_id) : [];
        $inventario = $planta_id ? $this->inventarioModel->obtenerPorPlanta($planta_id, $usuario_id) : [];

        $humedad = $ultima ? (float)$ultima['humedad_suelo'] : 0;
        $temp    = $ultima ? (float)$ultima['temperatura']   : 0;
        $luz     = $ultima ? (int)  $ultima['luz_ambiental'] : 0;
        $tanque  = $ultima ? (int)  $ultima['nivel_tanque']  : 0;
        $bateria = $ultima ? (int)  $ultima['bateria']       : 0;
        $salud   = $ultima ? min(100, round(($humedad + $bateria) / 2)) : 0;

        // Estado del hardware
        $is_online = false;
        if (count($dispositivos) === 0 && count($plantas) === 0) {
            $status_label = "Sin dispositivos";
            $status_badge_class = "bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700";
            $status_dot_class = "bg-slate-400";
        } elseif (count($dispositivos) === 0) {
            $status_label = "Sin hardware vinculado";
            $status_badge_class = "bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200/80 dark:border-amber-800/60";
            $status_dot_class = "bg-amber-500";
        } elseif ($ultima && (time() - strtotime($ultima['creada_en']) <= 900)) {
            $is_online = true;
            $status_label = "Sistema en línea";
            $status_badge_class = "bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200/80 dark:border-emerald-800/60";
            $status_dot_class = "bg-emerald-500 animate-pulse";
        } elseif ($ultima) {
            $status_label = "Fuera de línea";
            $status_badge_class = "bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700";
            $status_dot_class = "bg-slate-400";
        } else {
            $status_label = "Esperando conexión";
            $status_badge_class = "bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200/80 dark:border-amber-800/60";
            $status_dot_class = "bg-amber-500";
        }

        // 4. Empaquetar datos para la Vista (MVC)
        $data = [
            'usuario_id'         => $usuario_id,
            'usuario_data'       => $usuario_data,
            'nombre'             => $usuario_data['nombre'] ?? 'Usuario',
            'plantas'            => $plantas,
            'planta_id'          => $planta_id,
            'planta_actual'      => $planta_actual,
            'dispositivos'       => $dispositivos,
            'ultima'             => $ultima,
            'historial'          => $historial,
            'eventos'            => $eventos,
            'inventario'         => $inventario,
            'humedad'            => $humedad,
            'temp'               => $temp,
            'luz'                => $luz,
            'tanque'             => $tanque,
            'bateria'            => $bateria,
            'salud'              => $salud,
            'is_online'          => $is_online,
            'status_label'       => $status_label,
            'status_badge_class' => $status_badge_class,
            'status_dot_class'   => $status_dot_class,
            'msg_perfil'         => $msg_perfil,
            'msg_settings'       => $msg_settings,
            'msg_planta'         => $msg_planta,
        ];

        // 5. Renderizado de la Vista con inyección de datos
        return view('Dashboard', $data);
    }

    public function login(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        AuthSecurity::startSession();
        if (AuthSecurity::isLoggedIn()) {
            return redirect()->to('/dashboard');
        }

        $error = null;
        if ($this->request->is('post')) {
            $email    = trim($this->request->getPost('usuario') ?? $this->request->getPost('email') ?? '');
            $password = $this->request->getPost('password') ?? '';

            if (empty($email) || empty($password)) {
                $error = "Completá todos los campos.";
            } else {
                $usuario = $this->usuarioModel->verificarLogin($email, $password);
                if ($usuario) {
                    AuthSecurity::loginUser($usuario, $usuario['email']);
                    return redirect()->to('/dashboard');
                } else {
                    $error = "Credenciales incorrectas.";
                }
            }
        }

        return view('Login', ['error' => $error]);
    }

    public function register(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        AuthSecurity::startSession();
        if (AuthSecurity::isLoggedIn()) {
            return redirect()->to('/dashboard');
        }

        return view('Register');
    }

    public function resetPassword(): string
    {
        return view('ResetPassword');
    }

    public function store(): string
    {
        return view('Store');
    }

    public function deviceCreate(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        return $this->hardwareConnect();
    }

    public function hardwareConnect(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        AuthSecurity::startSession();
        if (!AuthSecurity::isLoggedIn()) {
            return redirect()->to('/login');
        }

        require_once APPPATH . 'Libraries/Database.php';
        require_once APPPATH . 'Libraries/HardwareKit.php';

        $db = \Database::connect();
        \HardwareKit::ensureSchema($db);

        $usuarioId = (int) $_SESSION['usuario_id'];

        // Asegurar que el dispositivo demo activo esté asignado al usuario logueado
        $db->query("UPDATE dispositivos SET usuario_id = " . (int) $usuarioId . " WHERE codigo = 'SPC-DEMO-FICUS-001'");

        $plantas = $this->plantaModel->activasPorUsuario($usuarioId);
        $error_msg = '';
        $success_msg = '';
        $createdDevice = null;

        if ($this->request->is('post') && ($this->request->getPost('action') === 'create_device' || $this->request->getPost('crear_dispositivo') !== null)) {
            $nombre    = trim($this->request->getPost('nombre') ?? '');
            $tipo      = trim($this->request->getPost('tipo_dispositivo') ?? '');
            $planta_id = (int) ($this->request->getPost('planta_id') ?? 0);
            $ubicacion = trim($this->request->getPost('ubicacion') ?? '');
            $codigo    = trim($this->request->getPost('codigo') ?? '');

            $planta = null;
            foreach ($plantas as $p) {
                if ((int) $p['id'] === $planta_id) {
                    $planta = $p;
                    break;
                }
            }

            if ($nombre === '' || $tipo === '' || !$planta) {
                $error_msg = 'Por favor completa el nombre del dispositivo, tipo y la planta asignada.';
            } else {
                if ($codigo === '') {
                    $codigo = 'SPC-' . $usuarioId . '-' . strtoupper(bin2hex(random_bytes(3)));
                }

                $corresponde_a = $planta['nombre'];
                $apiKey = bin2hex(random_bytes(16));
                
                $datosDispositivo = [
                    'usuario_id'       => $usuarioId,
                    'planta_id'        => $planta_id,
                    'codigo'           => $codigo,
                    'api_key'          => $apiKey,
                    'nombre'           => $nombre,
                    'tipo_dispositivo' => $tipo,
                    'corresponde_a'    => $corresponde_a,
                    'ubicacion'        => $ubicacion,
                    'firmware'         => '1.0.0',
                    'activo'           => 1,
                ];

                $newDeviceId = $this->dispositivoModel->insert($datosDispositivo, true);
                if ($newDeviceId) {
                    \HardwareKit::syncComponents($db, (int) $newDeviceId);
                    $createdDevice = [
                        'id'      => $newDeviceId,
                        'codigo'  => $codigo,
                        'api_key' => $apiKey,
                        'nombre'  => $nombre,
                    ];
                    $success_msg = "¡Dispositivo '$nombre' dado de alta correctamente! Ya está listo para transmitir datos.";
                    $_GET['device'] = $newDeviceId;
                    $_GET['tab'] = 'telemetry';
                } else {
                    $error_msg = 'No se pudo registrar el dispositivo.';
                }
            }
        }

        // Listar dispositivos del usuario con información de la planta vinculada
        $stmt = $db->prepare("
            SELECT d.*, p.nombre AS planta_nombre, p.especie
            FROM dispositivos d
            INNER JOIN plantas p ON p.id = d.planta_id
            WHERE d.usuario_id = ?
            ORDER BY (d.ultima_conexion IS NOT NULL) DESC, d.ultima_conexion DESC, d.id ASC
        ");
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $devices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($devices as $device) {
            \HardwareKit::syncComponents($db, (int) $device['id']);
        }

        $selectedDevice = null;
        $requestedDevice = (int) ($this->request->getGet('device') ?? 0);
        foreach ($devices as $device) {
            if (($requestedDevice > 0 && (int) $device['id'] === $requestedDevice) || ($selectedDevice === null && $requestedDevice === 0)) {
                $selectedDevice = $device;
                break;
            }
        }
        if ($selectedDevice === null && $devices !== []) {
            $selectedDevice = $devices[0];
        }

        $components = $selectedDevice ? \HardwareKit::getComponentsForDevice($db, (int) $selectedDevice['id']) : [];
        $latest = $selectedDevice ? \HardwareKit::getLatestReading($db, (int) $selectedDevice['id']) : null;
        $analysis = \HardwareKit::processReading($latest, $components);

        $connectedCount = 0;
        foreach ($components as $component) {
            if ($component['online'] && $component['estado_visual'] !== 'desconectado') {
                $connectedCount++;
            }
        }

        return view('HardwareConnect', [
            'usuarioId'      => $usuarioId,
            'plantas'        => $plantas,
            'devices'        => $devices,
            'selectedDevice' => $selectedDevice,
            'components'     => $components,
            'latest'         => $latest,
            'analysis'       => $analysis,
            'connectedCount' => $connectedCount,
            'error_msg'      => $error_msg,
            'success_msg'    => $success_msg,
            'createdDevice'  => $createdDevice,
        ]);
    }

    public function support(): string
    {
        AuthSecurity::startSession();
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $usuario_email = "";
        if ($usuario_id) {
            $user = $this->usuarioModel->find($usuario_id);
            if ($user) {
                $usuario_email = $user['email'] ?? '';
            }
        }

        $msg_estado = "";
        if ($this->request->is('post')) {
            $email_remitente = filter_var($this->request->getPost('email') ?? '', FILTER_SANITIZE_EMAIL);
            $problema = htmlspecialchars(trim($this->request->getPost('problema') ?? ''));

            if (!filter_var($email_remitente, FILTER_VALIDATE_EMAIL)) {
                $msg_estado = "<div class='text-gray-300 text-sm mb-6 bg-white/5 p-4 rounded-xl border border-white/10'>Por favor, ingresa un correo electrónico válido.</div>";
            } elseif (empty($problema)) {
                $msg_estado = "<div class='text-gray-300 text-sm mb-6 bg-white/5 p-4 rounded-xl border border-white/10'>El campo del problema no puede estar vacío.</div>";
            } else {
                $destinatario = "tomasaraujo@alumnos.itr3.edu.ar";
                $asunto = "Nuevo Ticket de Soporte - SmartPlant Care";
                $cuerpo = "Has recibido una nueva solicitud de soporte desde SmartPlant Care.\n\nCorreo del usuario: $email_remitente\n\nDescripción del problema:\n$problema\n";
                $headers = "From: no-reply@smartplant.com\r\nReply-To: $email_remitente\r\nX-Mailer: PHP/" . phpversion();

                if (@mail($destinatario, $asunto, $cuerpo, $headers)) {
                    $msg_estado = "<div class='text-white/80 text-sm mb-6 bg-white/5 p-4 rounded-xl border border-white/10'>✅ Tu solicitud de soporte ha sido enviada correctamente. Nos comunicaremos contigo pronto.</div>";
                } else {
                    $msg_estado = "<div class='text-gray-300 text-sm mb-6 bg-white/5 p-4 rounded-xl border border-white/10'>Hubo un error al enviar tu solicitud. (Verifica la configuración de XAMPP sendmail).</div>";
                }
            }
        }

        return view('Support', [
            'usuario_email' => $usuario_email,
            'msg_estado'    => $msg_estado,
        ]);
    }

    public function hola(): string
    {
        return view('hola');
    }

    public function aiAssistant(): string
    {
        return $this->openControllerFile('ai_assistant.php');
    }

    public function inventory(): string
    {
        return $this->openControllerFile('inventory_controller.php');
    }

    public function purchase(): string
    {
        return $this->openControllerFile('purchase_controller.php');
    }

    public function hardwareApi(): string
    {
        return $this->openControllerFile('hardware_controller.php');
    }

    private function openControllerFile(string $fileName): string
    {
        $file = APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . $fileName;

        if (! is_file($file)) {
            throw PageNotFoundException::forPageNotFound($fileName);
        }

        service('session');

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}
