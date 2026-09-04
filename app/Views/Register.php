<?php
// ═══════════════════════════════════════════════════════════════
//  Register.php — SmartPlant CARE
//  Flujo: Formulario → Código de verificación al email → Cuenta creada
// ═══════════════════════════════════════════════════════════════
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

service('session');
if (isset($_SESSION['usuario_id'])) {
    header("Location: /dashboard");
    exit;
}

require_once APPPATH . 'Libraries/Database.php';
require_once APPPATH . 'ThirdParty/vendor/autoload.php';

define('SMTP_USER', 'anatom071@gmail.com');
define('SMTP_PASS', 'easg mhwx dimr coha');
define('SMTP_FROM', 'noreply@smartplantcare.com');
define('SMTP_NAME', 'SmartPlant CARE');

$error    = null;
$step     = $_SESSION['register_step'] ?? 'form'; 
$form     = $_SESSION['register_form'] ?? ['nombre' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_code') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = trim($_POST['password']  ?? '');
        $confirmar = trim($_POST['confirmar'] ?? '');

        $form = ['nombre' => $nombre, 'email' => $email];

        if (!$nombre || !$email || !$password || !$confirmar) {
            $error = "Completá todos los campos.";
        } elseif (strlen($nombre) < 2) {
            $error = "El nombre debe tener al menos 2 caracteres.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo electrónico no es válido.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($password !== $confirmar) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $db    = Database::connect();
            $check = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Ese correo ya está registrado. ¿Querés iniciar sesión?";
            } else {
                $codigo  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expira  = time() + 600; 

                $_SESSION['register_step']     = 'verify';
                $_SESSION['register_form']     = $form;
                $_SESSION['register_codigo']   = $codigo;
                $_SESSION['register_expira']   = $expira;
                $_SESSION['register_password'] = password_hash($password, PASSWORD_DEFAULT);
                $_SESSION['register_intentos'] = 0;

                $enviado = enviarCodigo($email, $nombre, $codigo);

                if ($enviado === true) {
                    $step = 'verify';
                } else {
                    unset($_SESSION['register_step'], $_SESSION['register_codigo'],
                          $_SESSION['register_expira'], $_SESSION['register_password']);
                    $step  = 'form';
                    $error = "No se pudo enviar el código al correo. Revisá que sea correcto. ($enviado)";
                }
            }
        }
    }
    elseif ($_POST['action'] === 'verify_code') {
        $ingresado = trim(str_replace(' ', '', $_POST['codigo'] ?? ''));
        $_SESSION['register_intentos'] = ($_SESSION['register_intentos'] ?? 0) + 1;

        if ($_SESSION['register_intentos'] > 5) {
            unset($_SESSION['register_step'], $_SESSION['register_codigo'],
                  $_SESSION['register_expira'], $_SESSION['register_password'],
                  $_SESSION['register_intentos'], $_SESSION['register_form']);
            $step  = 'form';
            $error = "Demasiados intentos fallidos. Volvé a registrarte.";

        } elseif (time() > ($_SESSION['register_expira'] ?? 0)) {
            unset($_SESSION['register_step'], $_SESSION['register_codigo'],
                  $_SESSION['register_expira'], $_SESSION['register_password'],
                  $_SESSION['register_intentos']);
            $step  = 'form';
            $error = "El código expiró (10 minutos). Intentá de nuevo.";

        } elseif ($ingresado !== $_SESSION['register_codigo']) {
            $step  = 'verify';
            $error = "Código incorrecto. Te quedan " . (5 - $_SESSION['register_intentos']) . " intentos.";

        } else {
            $db     = Database::connect();
            $nombre = $_SESSION['register_form']['nombre'];
            $email  = $_SESSION['register_form']['email'];
            $hash   = $_SESSION['register_password'];

            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, plan) VALUES (?, ?, ?, 'free')");
            $stmt->bind_param("sss", $nombre, $email, $hash);

            if ($stmt->execute()) {
                $nuevo_id = $db->insert_id;

                $planta = $db->prepare("
                    INSERT INTO plantas (usuario_id, nombre, especie, descripcion, humedad_min, humedad_max, temp_min, temp_max)
                    VALUES (?, 'Mi primera planta', 'Por definir', 'Planta registrada al crear la cuenta', 35, 65, 15.0, 35.0)
                ");
                $planta->bind_param("i", $nuevo_id);
                $planta->execute();

                $plan = 'free';
                unset($_SESSION['register_step'], $_SESSION['register_codigo'],
                      $_SESSION['register_expira'], $_SESSION['register_password'],
                      $_SESSION['register_intentos'], $_SESSION['register_form']);

                $_SESSION['usuario_id'] = $nuevo_id;
                $_SESSION['usuario']    = $email;
                $_SESSION['nombre']     = $nombre;
                $_SESSION['plan']       = $plan;

                header("Location: /dashboard");
                exit;
            } else {
                $error = "Error al crear la cuenta. Intentá de nuevo.";
                $step  = 'form';
            }
        }
    }
    elseif ($_POST['action'] === 'resend_code') {
        if ($step === 'verify' && isset($_SESSION['register_form'])) {
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['register_codigo']   = $codigo;
            $_SESSION['register_expira']   = time() + 600;
            $_SESSION['register_intentos'] = 0;

            $nombre  = $_SESSION['register_form']['nombre'];
            $email   = $_SESSION['register_form']['email'];
            $enviado = enviarCodigo($email, $nombre, $codigo);

            if ($enviado !== true) {
                $error = "No se pudo reenviar el código. ($enviado)";
            }
        }
        $step = 'verify';
    }
    elseif ($_POST['action'] === 'back_to_form') {
        unset($_SESSION['register_step'], $_SESSION['register_codigo'],
              $_SESSION['register_expira'], $_SESSION['register_password'],
              $_SESSION['register_intentos']);
        $_SESSION['register_step'] = 'form';
        $step = 'form';
    }
}

function enviarCodigo(string $email, string $nombre, string $codigo): true|string {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = "Tu código de verificación — SmartPlant CARE";
        $mail->Body    = "
        <!DOCTYPE html>
        <html lang='es'>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif;'>
          <table width='100%' cellpadding='0' cellspacing='0' style='background:#0a0a0a;padding:40px 20px;'>
            <tr><td align='center'>
              <table width='520' cellpadding='0' cellspacing='0' style='background:#111;border:1px solid rgba(255,255,255,0.08);border-radius:24px;overflow:hidden;'>
                <tr>
                  <td style='background:linear-gradient(135deg,#14532d,#052e16);padding:40px 40px 30px;text-align:center;'>
                    <p style='color:#4ade80;font-size:13px;letter-spacing:4px;text-transform:uppercase;margin:0 0 12px;'>SmartPlant CARE</p>
                    <p style='color:white;font-size:28px;font-weight:700;margin:0;'>🌱 Verificá tu correo</p>
                  </td>
                </tr>
                <tr>
                  <td style='padding:40px;'>
                    <p style='color:#d1d5db;font-size:15px;margin:0 0 8px;'>Hola, <strong style='color:white;'>{$nombre}</strong></p>
                    <p style='color:#9ca3af;font-size:14px;margin:0 0 36px;line-height:1.6;'>
                      Usá el siguiente código para verificar tu cuenta. Expira en <strong style='color:white;'>10 minutos</strong>.
                    </p>
                    <div style='background:rgba(74,222,128,0.06);border:1px solid rgba(74,222,128,0.2);border-radius:16px;padding:32px;text-align:center;margin-bottom:36px;'>
                      <p style='color:#9ca3af;font-size:11px;letter-spacing:3px;text-transform:uppercase;margin:0 0 16px;'>Tu código de verificación</p>
                      <p style='color:#4ade80;font-size:52px;font-weight:900;letter-spacing:16px;margin:0;font-family:monospace;'>{$codigo}</p>
                    </div>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>";

        $mail->AltBody = "Tu código de verificación para SmartPlant CARE es: {$codigo}\nExpira en 10 minutos.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

if (empty($_POST)) {
    $step = $_SESSION['register_step'] ?? 'form';
    $form = $_SESSION['register_form'] ?? ['nombre' => '', 'email' => ''];
}

$segundos_restantes = max(0, ($_SESSION['register_expira'] ?? 0) - time());
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $step === 'verify' ? 'Verificar código' : 'Crear cuenta' ?> — SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .strength-bar { height: 3px; border-radius: 999px; transition: background 0.4s ease; }
        .check-custom { appearance: none; width: 16px; height: 16px; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; background: rgba(255,255,255,0.05); cursor: pointer; position: relative; flex-shrink: 0; transition: all 0.2s ease; }
        .check-custom:checked { background: #ffffff; border-color: #ffffff; }
        .check-custom:checked::after { content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 10px; color: black; font-weight: bold; }
        .otp-input { width: 52px; height: 64px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; color: white; font-size: 28px; font-weight: 700; text-align: center; transition: all 0.3s var(--ease-out); font-family: monospace; caret-color: #ffffff; }
        .otp-input:focus { outline: none; border-color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.06); box-shadow: 0 0 0 4px rgba(255,255,255,0.06); transform: scale(1.06); }
        .otp-input.filled { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.04); }
        .otp-input.error-shake { border-color: rgba(239,68,68,0.6); animation: shake 0.4s ease; }
        @keyframes shake { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
        .timer-ring { transform: rotate(-90deg); }
        .timer-circle { fill: none; stroke-width: 3; stroke-linecap: round; transition: stroke-dashoffset 1s linear, stroke 0.5s ease; }
    </style>
</head>

<body class="bg-overlay text-white min-h-screen flex flex-col">

<header class="sticky top-6 z-50 mx-auto w-full max-w-5xl px-4">
    <div class="glass-clean flex items-center justify-between px-8 py-4 rounded-[2.5rem]">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-9 h-9 transition-transform group-hover:scale-105 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.35)]">
            <span class="text-2xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <nav class="hidden md:flex gap-8 items-center text-sm font-medium text-slate-700">
            <a href="/#utilidades" data-i18n="nav_utilidades" class="hover:text-emerald-600 transition-colors">Utilidades</a>
            <a href="/#producto" data-i18n="nav_producto" class="hover:text-emerald-600 transition-colors">Producto</a>
            <a href="/store" data-i18n="nav_tienda" class="hover:text-emerald-600 transition-colors">Tienda</a>
            
            <select id="langSelector" class="bg-emerald-50/80 border border-emerald-200 text-slate-800 text-xs rounded-lg px-2.5 py-1.5 outline-none cursor-pointer font-medium hover:border-emerald-400 transition-all">
                <option value="es" class="text-slate-900">🇪🇸 ES</option>
                <option value="en" class="text-slate-900">🇬🇧 EN</option>
            </select>

            <a href="/login" data-i18n="btn_login" class="btn-emerald px-6 py-2.5 rounded-full font-bold hover:scale-105 transition-all text-sm">
                Iniciar sesión
            </a>
        </nav>
    </div>
</header>

<main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md relative">
        <div class="orbit-ring" style="width:520px;height:520px;top:50%;left:50%;margin-top:-260px;margin-left:-260px;border-color:rgba(16,185,129,0.12);"></div>
        <div class="form-glow relative">

        <?php if ($step === 'form'): ?>
        <form method="POST" class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale shadow-2xl">
            <input type="hidden" name="action" value="send_code">

            <div class="text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-5 rounded-3xl bg-gradient-to-br from-emerald-100 to-teal-50 border border-emerald-200 flex items-center justify-center shadow-lg shadow-emerald-500/10 p-3">
                    <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-full h-full object-contain filter drop-shadow">
                </div>
                <h2 class="text-3xl md:text-4xl font-semibold tracking-tight">
                    <span data-i18n="reg_title_1">Crear</span> <span data-i18n="reg_title_2" class="text-gradient-anim">cuenta.</span>
                </h2>
                <p data-i18n="reg_sub" class="text-gray-400 text-sm font-light mt-2">Unite y empezá a cuidar tus plantas hoy.</p>
            </div>

            <?php if ($error): ?>
            <div class="error-toast rounded-2xl px-5 py-4 mb-6 flex items-center gap-3">
                <i data-lucide="alert-triangle" class="text-red-400 w-5 h-5"></i>
                <p class="text-red-300 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <label data-i18n="label_name" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Nombre completo</label>
                <input type="text" name="nombre" placeholder="Tu nombre"
                    value="<?= htmlspecialchars($form['nombre']) ?>"
                    class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light" required minlength="2">
            </div>

            <div class="mb-4">
                <label data-i18n="label_email" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Correo electrónico</label>
                <input type="email" name="email" placeholder="tucorreo@ejemplo.com"
                    value="<?= htmlspecialchars($form['email']) ?>"
                    class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light" required>
            </div>

            <div class="mb-4">
                <label data-i18n="label_pass" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Contraseña</label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput" placeholder="Mínimo 6 caracteres"
                        class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light pr-14" required oninput="checkStrength(this.value)">
                    <button type="button" onclick="togglePass('passwordInput','tb1')" id="tb1" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="mt-2 flex gap-1.5">
                    <div class="strength-bar flex-1 bg-white/10" id="bar1"></div><div class="strength-bar flex-1 bg-white/10" id="bar2"></div><div class="strength-bar flex-1 bg-white/10" id="bar3"></div><div class="strength-bar flex-1 bg-white/10" id="bar4"></div>
                </div>
                <p class="text-[11px] text-gray-600 mt-1 ml-1" id="strengthLabel"></p>
            </div>

            <div class="mb-6">
                <label data-i18n="label_conf_pass" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Confirmar contraseña</label>
                <div class="relative">
                    <input type="password" name="confirmar" id="confirmarInput" placeholder="Repetí tu contraseña"
                        class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light pr-14" required oninput="checkMatch()">
                    <button type="button" onclick="togglePass('confirmarInput','tb2')" id="tb2" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
                <p class="text-[11px] mt-1 ml-1 hidden" id="matchLabel"></p>
            </div>

            <label class="flex items-start gap-3 mb-8 cursor-pointer">
                <input type="checkbox" class="check-custom mt-0.5" required>
                <span data-i18n="reg_terms" class="text-gray-400 text-xs font-light leading-relaxed">
                    Acepto los términos y condiciones.
                </span>
            </label>

            <button type="submit" data-i18n="btn_reg_cont" class="btn-glow w-full bg-white text-black py-4 rounded-2xl font-semibold text-base shadow-lg">Continuar — Verificar correo →</button>

            <div class="my-7 flex items-center gap-4">
                <div class="divider-glass flex-1"></div>
                <span data-i18n="reg_have_acc" class="text-gray-500 text-xs font-light">¿ya tenés cuenta?</span>
                <div class="divider-glass flex-1"></div>
            </div>

            <a href="/login" data-i18n="btn_back_login" class="w-full flex items-center justify-center gap-2 input-glass rounded-2xl py-4 text-sm font-medium hover:bg-white/10 transition-all text-white/80 hover:text-white">
                ← Iniciar sesión
            </a>
        </form>

        <?php else: ?>
        <div class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale">
            <div class="text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-gradient-to-br from-white/10 to-white/5 border border-white/10 flex items-center justify-center shadow-lg text-white">
                    <i data-lucide="mail" class="w-8 h-8"></i>
                </div>
                <h2 class="text-3xl font-semibold tracking-tight" data-i18n="ver_title">Verificá tu correo.</h2>
                <p class="text-gray-400 text-sm font-light mt-3 leading-relaxed">
                    <span data-i18n="ver_sub">Enviamos un código de 6 dígitos a</span><br>
                    <span class="text-white font-medium"><?= htmlspecialchars($form['email']) ?></span>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="error-toast rounded-2xl px-5 py-4 mb-6 flex items-center gap-3">
                <i data-lucide="alert-triangle" class="text-red-400 w-5 h-5"></i>
                <p class="text-red-300 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="flex flex-col items-center mb-8">
                <div class="relative w-16 h-16">
                    <svg class="timer-ring w-16 h-16" viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="24" class="timer-circle" stroke="rgba(255,255,255,0.05)" stroke-dasharray="150.8" stroke-dashoffset="0"/>
                        <circle cx="28" cy="28" r="24" class="timer-circle" stroke="#ffffff" stroke-dasharray="150.8" stroke-dashoffset="<?= 150.8 * (1 - $segundos_restantes / 600) ?>" id="timerCircle"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-white" id="timerLabel"><?= gmdate('i:s', $segundos_restantes) ?></span>
                    </div>
                </div>
            </div>

            <form method="POST" id="otpForm">
                <input type="hidden" name="action" value="verify_code">
                <input type="hidden" name="codigo" id="codigoHidden">
                <div class="flex justify-center gap-3 mb-8" id="otpContainer">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-input" id="otp<?= $i ?>">
                    <?php endfor; ?>
                </div>
                <button type="submit" id="verifyBtn" data-i18n="btn_ver_code" class="btn-glow w-full bg-white text-black py-4 rounded-2xl font-semibold text-base shadow-lg opacity-50 cursor-not-allowed" disabled>Verificar código</button>
            </form>

            <div class="mt-6 space-y-3">
                <form method="POST">
                    <input type="hidden" name="action" value="resend_code">
                    <button type="submit" data-i18n="btn_resend" class="w-full text-center text-white/50 text-sm hover:text-white transition-colors py-2">¿No llegó el correo? Reenviar código</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="back_to_form">
                    <button type="submit" data-i18n="btn_change_mail" class="w-full flex items-center justify-center gap-2 input-glass rounded-2xl py-3.5 text-sm text-white/70 hover:bg-white/10 hover:text-white transition-all">← Cambiar correo</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        </div>
    </div>
</main>

<script>
    lucide.createIcons();
    
    // I18N
    const translations = {
        es: { nav_utilidades: "Utilidades", nav_producto: "Producto", nav_tienda: "Tienda", btn_login: "Iniciar sesión",
              reg_title_1: "Crear", reg_title_2: "cuenta.", reg_sub: "Unite y empezá a cuidar tus plantas hoy.",
              label_name: "Nombre completo", label_email: "Correo", label_pass: "Contraseña", label_conf_pass: "Confirmar contraseña",
              reg_terms: "Acepto los términos y condiciones.", btn_reg_cont: "Continuar — Verificar correo →", reg_have_acc: "¿ya tenés cuenta?", btn_back_login: "← Iniciar sesión",
              ver_title: "Verificá tu correo.", ver_sub: "Enviamos un código de 6 dígitos a", btn_ver_code: "Verificar código", btn_resend: "¿No llegó el correo? Reenviar código", btn_change_mail: "← Cambiar correo" },
        en: { nav_utilidades: "Utilities", nav_producto: "Product", nav_tienda: "Store", btn_login: "Log in",
              reg_title_1: "Create", reg_title_2: "account.", reg_sub: "Join us and start caring for your plants.",
              label_name: "Full Name", label_email: "Email", label_pass: "Password", label_conf_pass: "Confirm password",
              reg_terms: "I accept the terms and conditions.", btn_reg_cont: "Continue — Verify email →", reg_have_acc: "already have an account?", btn_back_login: "← Log in",
              ver_title: "Verify your email.", ver_sub: "We sent a 6-digit code to", btn_ver_code: "Verify code", btn_resend: "Didn't receive it? Resend code", btn_change_mail: "← Change email" }
    };
    
    function applyTranslations(lang) {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (translations[lang] && translations[lang][key]) el.innerHTML = translations[lang][key];
            else if (translations['en'] && translations['en'][key]) el.innerHTML = translations['en'][key];
        });
        lucide.createIcons();
    }
    
    const langSelector = document.getElementById('langSelector');
    if (langSelector) {
        langSelector.value = localStorage.getItem('sp_lang') || 'es';
        applyTranslations(langSelector.value);
        langSelector.addEventListener('change', (e) => {
            localStorage.setItem('sp_lang', e.target.value);
            applyTranslations(e.target.value);
        });
    }

    window.addEventListener('DOMContentLoaded', () => { setTimeout(() => document.querySelectorAll('.reveal-scale').forEach(el => el.classList.add('active')), 80); });

    function togglePass(inputId, btnId) {
        const input = document.getElementById(inputId);
        const btn   = document.getElementById(btnId);
        input.type  = input.type === 'password' ? 'text' : 'password';
        btn.innerHTML = input.type === 'text' ? '<i data-lucide="eye-off" class="w-5 h-5"></i>' : '<i data-lucide="eye" class="w-5 h-5"></i>';
        lucide.createIcons();
    }

    function checkStrength(val) {
        const bars  = ['bar1','bar2','bar3','bar4'].map(id => document.getElementById(id));
        const label = document.getElementById('strengthLabel');
        bars.forEach(b => b.style.background = 'rgba(255,255,255,0.08)');
        if (!val) { label.textContent = ''; return; }
        let score = 0;
        if (val.length >= 6)  score++; if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++; if (/[^A-Za-z0-9]/.test(val)) score++;
        const colors = ['#666','#888','#bbb','#fff'];
        const labels = ['Muy débil','Débil','Buena','Fuerte'];
        for (let i = 0; i < score; i++) bars[i].style.background = colors[score-1];
        label.textContent = labels[score-1] || ''; label.style.color = colors[score-1] || '';
    }

    function checkMatch() {
        const pass = document.getElementById('passwordInput')?.value;
        const conf = document.getElementById('confirmarInput')?.value;
        const lbl  = document.getElementById('matchLabel');
        if (!lbl) return;
        if (!conf) { lbl.classList.add('hidden'); return; }
        lbl.classList.remove('hidden');
        lbl.textContent = pass === conf ? '✓ Coinciden' : '✗ No coinciden';
        lbl.style.color = pass === conf ? '#ffffff' : '#888888';
    }

    <?php if ($step === 'verify'): ?>
    const otpInputs  = Array.from({length: 6}, (_, i) => document.getElementById('otp' + i));
    const hidden     = document.getElementById('codigoHidden');
    const verifyBtn  = document.getElementById('verifyBtn');

    setTimeout(() => otpInputs[0]?.focus(), 200);

    otpInputs.forEach((inp, idx) => {
        inp.addEventListener('input', e => {
            inp.value = inp.value.replace(/\D/g, '').slice(-1);
            inp.classList.toggle('filled', inp.value !== '');
            if (inp.value && idx < 5) otpInputs[idx + 1].focus();
            syncHidden();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && idx > 0) {
                otpInputs[idx - 1].value = ''; otpInputs[idx - 1].classList.remove('filled');
                otpInputs[idx - 1].focus(); syncHidden();
            }
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            text.split('').forEach((ch, i) => { if (otpInputs[i]) { otpInputs[i].value = ch; otpInputs[i].classList.add('filled'); } });
            otpInputs[Math.min(text.length, 5)].focus(); syncHidden();
        });
    });

    function syncHidden() {
        const code = otpInputs.map(i => i.value).join('');
        hidden.value = code;
        const ready = code.length === 6;
        verifyBtn.disabled = !ready;
        verifyBtn.classList.toggle('opacity-50', !ready); verifyBtn.classList.toggle('cursor-not-allowed', !ready);
    }

    let secsLeft = <?= $segundos_restantes ?>;
    const circle  = document.getElementById('timerCircle');
    const timerLbl = document.getElementById('timerLabel');
    const tick = setInterval(() => {
        secsLeft = Math.max(0, secsLeft - 1);
        timerLbl.textContent = String(Math.floor(secsLeft / 60)).padStart(2, '0') + ':' + String(secsLeft % 60).padStart(2, '0');
        circle.style.strokeDashoffset = 150.8 * (1 - secsLeft / 600);
        if (secsLeft <= 0) { clearInterval(tick); verifyBtn.disabled = true; }
    }, 1000);
    <?php endif; ?>
</script>
</body>
</html>