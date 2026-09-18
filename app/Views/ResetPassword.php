<?php
// ═══════════════════════════════════════════════════════════════
//  ResetPassword.php — SmartPlant CARE
//  Permite al usuario restablecer su contraseña usando un token
//  enviado por email desde Login.php
// ═══════════════════════════════════════════════════════════════
service('session');

require_once APPPATH . 'Libraries/Database.php';

$error   = null;
$success = null;
$valid   = false;

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

// ── Validar token ─────────────────────────────────────────────
if ($token && $email) {
    $db = Database::connect();

    // Auto-create columns if needed (same safety as Login.php)
    $colCheck = $db->query("SHOW COLUMNS FROM usuarios LIKE 'reset_token'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $db->query("ALTER TABLE usuarios ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
        $db->query("ALTER TABLE usuarios ADD COLUMN reset_expira DATETIME DEFAULT NULL");
    }

    $stmt = $db->prepare("SELECT id, reset_token, reset_expira FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['reset_token'] === $token) {
        // Verificar que no expiró
        if (strtotime($user['reset_expira']) > time()) {
            $valid = true;
        } else {
            $error = "El enlace ha expirado. Solicitá uno nuevo desde el login.";
        }
    } else {
        $error = "El enlace no es válido. Solicitá uno nuevo desde el login.";
    }
} else {
    $error = "Enlace inválido o incompleto.";
}

// ── Procesar nueva contraseña ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && $valid) {
    $new_pass    = $_POST['new_password'];
    $confirm     = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($new_pass !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

        // Actualizar contraseña y limpiar token
        $upd = $db->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        $upd->bind_param("si", $hashed, $user['id']);

        if ($upd->execute()) {
            $success = true;
            $valid   = false; // Ya no mostrar el formulario
        } else {
            $error = "Error al actualizar la contraseña. Intentá de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña — SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .strength-bar {
            height: 3px; border-radius: 999px;
            transition: background 0.4s ease;
        }
    </style>
</head>

<body class="bg-overlay text-white min-h-screen flex flex-col">


<!-- ═══ HEADER ═══ -->
<header class="sticky top-6 z-50 mx-auto max-w-5xl px-4">
    <div class="glass-clean flex items-center justify-between px-8 py-3.5 rounded-full shadow-sm">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <nav class="hidden md:flex gap-7 items-center text-sm font-medium text-slate-700">
            <a href="/" class="hover:text-emerald-600 transition-colors">Inicio</a>
            <a href="/store" class="hover:text-emerald-600 transition-colors">Tienda</a>
            <a href="/login" class="btn-emerald px-5 py-2 rounded-full font-semibold hover:scale-105 transition-all text-xs">Iniciar sesión</a>
        </nav>
    </div>
</header>

<!-- ═══ MAIN ═══ -->
<main class="flex-1 flex items-center justify-center px-6 py-20">
    <div class="w-full max-w-md relative">

        <div class="orbit-ring" style="width:500px;height:500px;top:50%;left:50%;margin-top:-250px;margin-left:-250px;"></div>

        <div class="form-glow relative">

            <?php if ($success): ?>
            <!-- ═══ ÉXITO ═══ -->
            <div class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shadow-lg text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="check-circle-2" class="w-9 h-9"></i>
                </div>
                <h2 class="text-3xl font-semibold tracking-tight mb-4">
                    ¡Contraseña <span class="text-gradient-anim">actualizada!</span>
                </h2>
                <p class="text-gray-400 text-sm font-light mb-8 leading-relaxed">
                    Tu contraseña fue restablecida correctamente.<br>Ya podés iniciar sesión con tu nueva contraseña.
                </p>
                <a href="/login" class="btn-glow inline-block bg-white text-black px-10 py-4 rounded-2xl font-semibold text-base shadow-lg">
                    Ir a iniciar sesión →
                </a>
            </div>

            <?php elseif ($valid): ?>
            <!-- ═══ FORMULARIO NUEVA CONTRASEÑA ═══ -->
            <form method="POST" class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shadow-lg text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="key-round" class="w-8 h-8"></i>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-semibold tracking-tight">
                        Nueva <span class="text-gradient-anim">contraseña.</span>
                    </h2>
                    <p class="text-gray-400 text-sm font-light mt-2">
                        Creá una nueva contraseña para<br>
                        <span class="text-white font-medium"><?= htmlspecialchars($email) ?></span>
                    </p>
                </div>

                <?php if ($error): ?>
                <div class="error-toast rounded-2xl px-5 py-4 mb-6 flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="text-red-400 w-5 h-5"></i>
                    <p class="text-red-300 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <!-- Nueva contraseña -->
                <div class="mb-5">
                    <label class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Nueva contraseña</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="newPassInput"
                            placeholder="Mínimo 6 caracteres"
                            class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light pr-14"
                            required minlength="6" autocomplete="new-password"
                            oninput="checkStrength(this.value)">
                        <button type="button" onclick="togglePass('newPassInput','tb1')"
                            id="tb1" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="mt-2 flex gap-1.5">
                        <div class="strength-bar flex-1" style="background:var(--border)" id="bar1"></div>
                        <div class="strength-bar flex-1" style="background:var(--border)" id="bar2"></div>
                        <div class="strength-bar flex-1" style="background:var(--border)" id="bar3"></div>
                        <div class="strength-bar flex-1" style="background:var(--border)" id="bar4"></div>
                    </div>
                    <p class="text-[11px] text-gray-600 mt-1 ml-1" id="strengthLabel"></p>
                </div>

                <!-- Confirmar contraseña -->
                <div class="mb-8">
                    <label class="text-xs font-medium text-gray-400 tracking-widest uppercase mb-2 block ml-1">Confirmar contraseña</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirmPassInput"
                            placeholder="Repetí tu contraseña"
                            class="input-glass w-full rounded-2xl px-5 py-4 text-sm font-light pr-14"
                            required autocomplete="new-password" oninput="checkMatch()">
                        <button type="button" onclick="togglePass('confirmPassInput','tb2')"
                            id="tb2" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <p class="text-[11px] mt-1 ml-1 hidden" id="matchLabel"></p>
                </div>

                <button type="submit"
                    class="btn-glow w-full bg-white text-black py-4 rounded-2xl font-semibold text-base shadow-lg">
                    Restablecer contraseña
                </button>

                <p class="text-center text-gray-500 text-xs font-light mt-6">
                    <a href="/login" class="text-white/50 hover:text-white transition-colors">← Volver al login</a>
                </p>
            </form>

            <?php else: ?>
            <!-- ═══ ERROR / ENLACE INVÁLIDO ═══ -->
            <div class="glass-form rounded-[2.5rem] p-10 md:p-12 relative z-10 reveal-scale text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center shadow-lg text-red-600 dark:text-red-400">
                    <i data-lucide="alert-triangle" class="w-9 h-9"></i>
                </div>
                <h2 class="text-3xl font-semibold tracking-tight mb-4">
                    Enlace <span class="text-gradient-anim">no válido.</span>
                </h2>
                <p class="text-gray-400 text-sm font-light mb-8 leading-relaxed">
                    <?= htmlspecialchars($error ?? 'El enlace de recuperación no es válido o ya fue utilizado.') ?>
                </p>
                <div class="flex flex-col gap-3">
                    <a href="/login" class="btn-glow inline-block bg-white text-black px-10 py-4 rounded-2xl font-semibold text-base shadow-lg">
                        Volver al login
                    </a>
                    <p class="text-gray-500 text-xs font-light mt-2">
                        Podés solicitar un nuevo enlace desde "¿Olvidaste tu contraseña?"
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script>
lucide.createIcons();

window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => document.querySelectorAll('.reveal-scale').forEach(el => el.classList.add('active')), 80);
});

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
    if (!bars[0]) return;
    bars.forEach(b => b.style.background = 'var(--border)');
    if (!val) { label.textContent = ''; return; }
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const colors = ['#ef4444','#f59e0b','#10b981','#059669'];
    const labels = ['Muy débil','Débil','Buena','Fuerte'];
    for (let i = 0; i < score; i++) bars[i].style.background = colors[score-1];
    label.textContent = labels[score-1] || '';
    label.style.color = colors[score-1] || '';
}

function checkMatch() {
    const pass = document.getElementById('newPassInput')?.value;
    const conf = document.getElementById('confirmPassInput')?.value;
    const lbl  = document.getElementById('matchLabel');
    if (!lbl) return;
    if (!conf) { lbl.classList.add('hidden'); return; }
    lbl.classList.remove('hidden');
    lbl.textContent = pass === conf ? '✓ Las contraseñas coinciden' : '✗ No coinciden';
    lbl.style.color = pass === conf ? '#059669' : '#dc2626';
}

// Scroll progress removed
</script>
</body>
</html>
