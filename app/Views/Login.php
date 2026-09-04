<?php
// ═══════════════════════════════════════════════════════════════
//  Login.php — SmartPlant CARE
//  Login normal + Google OAuth 2.0 + recuperación de contraseña
// ═══════════════════════════════════════════════════════════════
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Libraries\AuthSecurity;

require_once APPPATH . 'Libraries/AuthSecurity.php';

AuthSecurity::startSession();
AuthSecurity::redirectIfLoggedIn('/dashboard');

require_once APPPATH . 'Libraries/Database.php';
require_once APPPATH . 'ThirdParty/vendor/autoload.php';

// ════════════════════════════════════════════════════════════════
//  CONFIGURACIÓN GOOGLE OAUTH
// ════════════════════════════════════════════════════════════════
define('GOOGLE_CLIENT_ID',     env('GOOGLE_CLIENT_ID') ?: '210888582761-ttrr3brcifkkqlqvvg7hv98ru2g2565a.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET') ?: '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');
define('GOOGLE_REDIRECT_URI',  env('GOOGLE_REDIRECT_URI') ?: AuthSecurity::siteUrl('login?oauth=google'));

// SMTP
define('SMTP_USER', env('SMTP_USER') ?: 'anatom071@gmail.com');
define('SMTP_PASS', env('SMTP_PASS') ?: 'easg mhwx dimr coha');

$error            = null;
$msg_forgot       = null;
$msg_forgot_error = null;

// ════════════════════════════════════════════════════════════════
//  GOOGLE OAUTH — Callback (Google redirige acá con ?oauth=google)
// ════════════════════════════════════════════════════════════════
if (isset($_GET['oauth']) && $_GET['oauth'] === 'google') {

    if (isset($_GET['error'])) {
        $error = "Autenticación con Google cancelada.";

    } elseif (isset($_GET['code'])) {

        if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
            $error = "Error de seguridad. Intentá de nuevo.";
        } else {
            $tokenResp = @file_get_contents('https://oauth2.googleapis.com/token', false,
                stream_context_create(['http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'code'          => $_GET['code'],
                        'client_id'     => GOOGLE_CLIENT_ID,
                        'client_secret' => GOOGLE_CLIENT_SECRET,
                        'redirect_uri'  => GOOGLE_REDIRECT_URI,
                        'grant_type'    => 'authorization_code',
                    ]),
                ]])
            );

            $tokenData = json_decode($tokenResp ?: '{}', true);

            if (!empty($tokenData['access_token'])) {
                $userResp = @file_get_contents('https://www.googleapis.com/oauth2/v3/userinfo', false,
                    stream_context_create(['http' => [
                        'header' => 'Authorization: Bearer ' . $tokenData['access_token']
                    ]])
                );
                $googleUser = json_decode($userResp ?: '{}', true);

                if (!empty($googleUser['email']) && ($googleUser['email_verified'] ?? false)) {
                    $db      = Database::connect();
                    AuthSecurity::ensureUsuarioSecurityColumns($db);
                    $gEmail  = $googleUser['email'];
                    $gNombre = $googleUser['name'] ?? explode('@', $gEmail)[0];

                    $stmt = $db->prepare("SELECT id, nombre, email, plan, rol, foto_perfil FROM usuarios WHERE email = ? LIMIT 1");
                    $stmt->bind_param("s", $gEmail);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();

                    if ($row) {
                        AuthSecurity::loginUser($row, $gEmail);
                    } else {
                        $hash = AuthSecurity::hashPassword(bin2hex(random_bytes(16)));
                        $ins  = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, plan) VALUES (?, ?, ?, 'cliente', 'free')");
                        $ins->bind_param("sss", $gNombre, $gEmail, $hash);
                        $ins->execute();
                        $nuevo_id = $db->insert_id;

                        $pl = $db->prepare("INSERT INTO plantas (usuario_id, nombre, especie, descripcion, humedad_min, humedad_max, temp_min, temp_max) VALUES (?, 'Mi primera planta', 'Por definir', 'Cuenta creada con Google', 35, 65, 15.0, 35.0)");
                        $pl->bind_param("i", $nuevo_id);
                        $pl->execute();

                        AuthSecurity::loginUser([
                            'id' => $nuevo_id,
                            'email' => $gEmail,
                            'nombre' => $gNombre,
                            'plan' => 'free',
                            'rol' => 'cliente',
                        ], $gEmail);
                    }

                    unset($_SESSION['oauth_state']);
                    header("Location: /dashboard");
                    exit;
                } else {
                    $error = "No se pudo verificar el correo de Google.";
                }
            } else {
                $error = "Error al autenticar con Google. Verificá que el Client ID esté configurado.";
            }
        }
    }
}

// ════════════════════════════════════════════════════════════════
//  POST — Login normal / Recuperar contraseña
// ════════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ── Recuperar contraseña ──────────────────────────────────
    if (isset($_POST['forgot_password'])) {
        $email_forgot = trim($_POST['email_forgot'] ?? '');
        if ($email_forgot) {
            $db   = Database::connect();
            AuthSecurity::ensureUsuarioSecurityColumns($db);

            $stmt = $db->prepare("SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->bind_param("s", $email_forgot);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                $reset = AuthSecurity::createPasswordReset($db, (int) $user['id']);
                $reset_link = AuthSecurity::siteUrl('reset-password?token=' . urlencode($reset['token']) . '&email=' . urlencode($email_forgot));
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
                    $mail->setFrom(SMTP_USER, 'SmartPlant CARE');
                    $mail->addAddress($email_forgot);
                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de contraseña — SmartPlant CARE';
                    $mail->Body    = "
                    <div style='font-family:Arial,sans-serif;background:#0a0a0a;padding:40px 20px;'>
                      <div style='max-width:500px;margin:auto;background:#111;border:1px solid rgba(255,255,255,0.08);border-radius:20px;overflow:hidden;'>
                        <div style='background:linear-gradient(135deg,#14532d,#052e16);padding:36px;text-align:center;'>
                          <p style='color:#4ade80;font-size:12px;letter-spacing:4px;margin:0 0 10px;text-transform:uppercase;'>SmartPlant CARE</p>
                          <p style='color:white;font-size:24px;font-weight:700;margin:0;'>Restablecer contraseña</p>
                        </div>
                        <div style='padding:36px;background:#171a20;'>
                          <p style='color:#9ca3af;font-size:14px;margin:0 0 28px;'>Hacé clic en el botón para crear una nueva contraseña. El enlace expira en 24 horas.</p>
                          <div style='text-align:center;'>
                            <a href='{$reset_link}' style='display:inline-block;background:#22c55e;color:#ffffff;padding:14px 32px;border-radius:12px;font-weight:700;text-decoration:none;font-size:15px;'>Restablecer contraseña</a>
                          </div>
                          <p style='color:#6b7280;font-size:12px;margin:28px 0 0;text-align:center;'>Si no solicitaste este cambio, ignorá este correo.</p>
                        </div>
                      </div>
                    </div>";
                    $mail->send();
                    $msg_forgot = "Te enviamos un enlace a tu correo.";
                } catch (Exception $e) {
                    $error = "No se pudo enviar. Error: {$mail->ErrorInfo}";
                }
            } else {
                $msg_forgot = "Si existe una cuenta con ese correo, recibirás un enlace.";
            }
        }

    // ── Login normal ──────────────────────────────────────────
    } else {
        $email    = trim($_POST["email"]    ?? "");
        $password = $_POST["password"] ?? "";

        if ($email && $password) {
            $db   = Database::connect();
            AuthSecurity::ensureUsuarioSecurityColumns($db);

            $stmt = $db->prepare("SELECT id, nombre, email, password, plan, rol, foto_perfil FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $row  = $stmt->get_result()->fetch_assoc();

            if ($row && password_verify($password, $row['password'])) {
                AuthSecurity::loginUser($row, $email);
                header("Location: /dashboard");
                exit;
            }
            $error = "Correo o contraseña incorrectos.";
        } else {
            $error = "Completá todos los campos.";
        }
    }
}

$oauth_state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $oauth_state;

$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $oauth_state,
    'prompt'        => 'select_account',
]);
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
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
                <option value="pt" class="text-slate-900">🇧🇷 PT</option>
                <option value="fr" class="text-slate-900">🇫🇷 FR</option>
                <option value="de" class="text-slate-900">🇩🇪 DE</option>
                <option value="ru" class="text-slate-900">🇷🇺 RU</option>
                <option value="zh" class="text-slate-900">🇨🇳 ZH</option>
            </select>

            <a href="/login" data-i18n="nav_cuenta" class="btn-emerald px-6 py-2.5 rounded-full font-bold hover:scale-105 transition-all text-sm">Mi cuenta</a>
        </nav>
    </div>
</header>

<main class="flex-1 flex items-center justify-center px-6 py-20">
    <div class="w-full max-w-md relative">

        <div class="orbit-ring" style="width:500px;height:500px;top:50%;left:50%;margin-top:-250px;margin-left:-250px;"></div>
        <div class="orbit-ring" style="width:660px;height:660px;top:50%;left:50%;margin-top:-330px;margin-left:-330px;animation-duration:32s;animation-direction:reverse;border-color:rgba(16,185,129,0.12);"></div>

        <div class="form-glow relative">
            <form method="POST" class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale shadow-2xl">

                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-3xl bg-gradient-to-br from-emerald-100 to-teal-50 border border-emerald-200 flex items-center justify-center shadow-lg shadow-emerald-500/10 p-3">
                        <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-full h-full object-contain filter drop-shadow">
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900">
                        <span data-i18n="login_title_1">Bienvenido de</span> <span data-i18n="login_title_2" class="text-gradient-anim">vuelta.</span>
                    </h2>
                    <p data-i18n="login_sub" class="text-slate-500 text-sm font-medium mt-2">Ingresá para gestionar tu jardín inteligente.</p>
                </div>

                <?php if ($error): ?>
                <div class="error-toast rounded-2xl px-5 py-4 mb-6 flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="text-red-400 w-5 h-5"></i>
                    <p class="text-red-300 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <div class="mb-5">
                    <label data-i18n="label_email" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Correo</label>
                    <input type="email" name="email" placeholder="tucorreo@smartplant.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light"
                        required autocomplete="email">
                </div>

                <div class="mb-6">
                    <label data-i18n="label_pass" class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="passwordInput" placeholder="••••••••"
                            class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light pr-14"
                            required autocomplete="current-password">
                        <button type="button" onclick="togglePassword()"
                            id="toggleBtn" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="recordar" class="w-4 h-4 rounded accent-neutral-400">
                        <span data-i18n="login_rem" class="text-gray-400 text-xs font-light">Recordarme</span>
                    </label>
                    <a href="#" onclick="event.preventDefault(); openForgotModal();"
                        data-i18n="login_forgot" class="text-white/50 text-xs font-medium hover:text-white transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit" data-i18n="btn_login" class="btn-glow w-full bg-white text-black py-4 rounded-2xl font-semibold text-base shadow-lg">
                    Ingresar
                </button>

                <div class="my-7 flex items-center gap-4">
                    <div class="divider-glass flex-1"></div>
                    <span data-i18n="login_or" class="text-gray-500 text-xs font-light">o continuá con</span>
                    <div class="divider-glass flex-1"></div>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="<?= htmlspecialchars($google_auth_url) ?>"
                       class="w-full flex items-center justify-center gap-3 input-glass rounded-2xl py-4 text-sm font-medium hover:bg-white/10 transition-all">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span data-i18n="btn_google">Continuar con Google</span>
                    </a>

                    <div class="relative group/apple">
                        <button type="button" disabled
                            class="w-full flex items-center justify-center gap-3 input-glass rounded-2xl py-4 text-sm font-medium opacity-35 cursor-not-allowed">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                            </svg>
                            <span data-i18n="btn_apple">Continuar con Apple</span>
                        </button>
                    </div>
                </div>

                <p class="text-center text-gray-400 text-sm font-light mt-8">
                    <span data-i18n="login_no_acc">¿No tenés cuenta?</span>
                    <a href="/register" data-i18n="login_create" class="text-white/70 font-medium hover:underline">Crear cuenta</a>
                </p>

            </form>
        </div>
    </div>
</main>

<div class="py-6 overflow-hidden border-t border-white/5">
    <div class="marquee-track">
        <span class="text-[3rem] font-bold tracking-tighter text-white/[0.025] whitespace-nowrap px-8">
            SmartPlant CARE — Tu jardín inteligente — Monitoreo 24/7 — Riego automático — Energía solar — &nbsp;
        </span>
    </div>
</div>

<script>
    lucide.createIcons();

    // Multilenguaje
    const translations = {
        es: { nav_utilidades: "Utilidades", nav_producto: "Producto", nav_tienda: "Tienda", nav_cuenta: "Mi cuenta",
              login_title_1: "Bienvenido de", login_title_2: "vuelta.", login_sub: "Ingresá para gestionar tu jardín inteligente.",
              label_email: "Correo", label_pass: "Contraseña", login_rem: "Recordarme", login_forgot: "¿Olvidaste tu contraseña?",
              btn_login: "Ingresar", login_or: "o continuá con", btn_google: "Continuar con Google", btn_apple: "Continuar con Apple",
              login_no_acc: "¿No tenés cuenta?", login_create: "Crear cuenta" },
        en: { nav_utilidades: "Utilities", nav_producto: "Product", nav_tienda: "Store", nav_cuenta: "My account",
              login_title_1: "Welcome", login_title_2: "back.", login_sub: "Log in to manage your smart garden.",
              label_email: "Email", label_pass: "Password", login_rem: "Remember me", login_forgot: "Forgot password?",
              btn_login: "Log In", login_or: "or continue with", btn_google: "Continue with Google", btn_apple: "Continue with Apple",
              login_no_acc: "Don't have an account?", login_create: "Create account" },
        // (Otros idiomas base omitidos para no hacer gigante el mensaje, heredan de inglés o español visualmente igual que en los otros archivos)
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

    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => document.querySelectorAll('.reveal-scale').forEach(el => el.classList.add('active')), 80);
    });

    function togglePassword() {
        const i = document.getElementById('passwordInput');
        const b = document.getElementById('toggleBtn');
        i.type  = i.type === 'password' ? 'text' : 'password';
        b.innerHTML = i.type === 'text' ? '<i data-lucide="eye-off" class="w-5 h-5"></i>' : '<i data-lucide="eye" class="w-5 h-5"></i>';
        lucide.createIcons();
    }

    function openForgotModal() {
        document.getElementById('forgotModal').classList.remove('opacity-0','pointer-events-none');
        document.getElementById('forgotModalContent').classList.replace('scale-95','scale-100');
        if (window.lucide) lucide.createIcons();
    }
    function closeForgotModal() {
        document.getElementById('forgotModalContent').classList.replace('scale-100','scale-95');
        document.getElementById('forgotModal').classList.add('opacity-0','pointer-events-none');
    }
    <?php if (!empty($msg_forgot) || !empty($msg_forgot_error)): ?>setTimeout(openForgotModal, 200);<?php endif; ?>
    document.getElementById('forgotModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeForgotModal(); });
</script>

<div id="forgotModal" class="fixed inset-0 bg-black/50 backdrop-blur-md z-[9999] opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
    <div class="glass-form rounded-[2.2rem] p-8 md:p-10 max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 relative border border-black/10 dark:border-white/10" id="forgotModalContent">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm flex-shrink-0">
                    <i data-lucide="key-round" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 data-i18n="forgot_title" class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">Recuperar contraseña</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-light">SmartPlant CARE</p>
                </div>
            </div>
            <button type="button" onclick="closeForgotModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Alert messages -->
        <?php if (!empty($msg_forgot)): ?>
        <div class="rounded-2xl p-4 mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-sm flex items-start gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5"></i>
            <span class="font-medium leading-snug"><?= htmlspecialchars($msg_forgot) ?></span>
        </div>
        <?php elseif (!empty($msg_forgot_error)): ?>
        <div class="rounded-2xl p-4 mb-6 bg-red-500/10 border border-red-500/20 text-red-800 dark:text-red-300 text-sm flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5"></i>
            <span class="font-medium leading-snug"><?= htmlspecialchars($msg_forgot_error) ?></span>
        </div>
        <?php else: ?>
        <p data-i18n="forgot_sub" class="text-gray-600 dark:text-gray-400 text-sm mb-6 font-light leading-relaxed">
            Ingresá tu correo y te enviamos un enlace para restablecer tu contraseña.
        </p>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-5">
            <input type="hidden" name="forgot_password" value="1">
            <div>
                <label data-i18n="label_email" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Correo</label>
                <div class="relative">
                    <input type="email" name="email_forgot" placeholder="tucorreo@smartplant.com" required class="w-full input-glass rounded-2xl px-5 py-3.5 text-sm font-light pl-11">
                    <i data-lucide="mail" class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeForgotModal()" data-i18n="btn_cancel" class="w-1/3 py-3.5 rounded-2xl text-sm font-medium border border-gray-200 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/5 transition-all text-gray-600 dark:text-gray-300">
                    Cancelar
                </button>
                <button type="submit" data-i18n="forgot_btn" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl py-3.5 text-sm shadow-lg shadow-emerald-600/25 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Enviar enlace</span>
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
