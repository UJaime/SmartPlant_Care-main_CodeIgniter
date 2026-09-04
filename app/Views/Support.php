<?php
// ═══════════════════════════════════════════════════════════════
//  Support.php — SmartPlant CARE (VISTA MVC PURA)
//  Presentación visual. Recibe los datos preparados por SmartPlant::support()
// ═══════════════════════════════════════════════════════════════

$usuario_email = $usuario_email ?? '';
$msg_estado    = $msg_estado ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - SmartPlant Care</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-overlay text-white font-['Inter'] min-h-screen flex flex-col antialiased selection:bg-white/20 overflow-x-hidden">

<!-- Background Effects -->
<div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-white/[0.03] blur-[120px] rounded-full mix-blend-screen"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-white/[0.03] blur-[120px] rounded-full mix-blend-screen"></div>
</div>

<!-- Header -->
<header class="w-full fixed top-0 left-0 z-50 transition-all duration-300 backdrop-blur-md glass-clean py-4">
    <div class="max-w-6xl mx-auto px-6 flex justify-between items-center">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-9 h-9 transition-transform group-hover:scale-105 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.35)]">
            <span class="text-2xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <a href="/dashboard" class="btn-emerald-outline px-5 py-2 rounded-full text-sm font-semibold transition-all">Volver al Dashboard</a>
    </div>
</header>

<!-- Contenido Principal -->
<main class="flex-1 flex items-center justify-center pt-24 pb-12 px-4">
    <div class="max-w-xl w-full">
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">Centro de <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400">Soporte</span></h1>
            <p class="text-gray-400 font-light text-lg">¿Estás experimentando problemas con tus sensores o con el panel? Describinos tu problema y te ayudaremos a solucionarlo.</p>
        </div>

        <div class="card-glass rounded-[2rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-white/30 to-white/10"></div>
            
            <?= $msg_estado ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Tu Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">✉️</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($usuario_email) ?>" required
                            class="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-white focus:outline-none focus:border-white/40 focus:bg-white/10 transition-all shadow-inner"
                            placeholder="tucorreo@ejemplo.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Descripción del Problema</label>
                    <textarea name="problema" rows="5" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-white/40 focus:bg-white/10 transition-all shadow-inner resize-none"
                        placeholder="Ej: El sensor de humedad no está actualizando los datos..."></textarea>
                </div>

                <button type="submit" class="w-full bg-white text-black font-semibold py-4 rounded-xl hover:opacity-90 transition-opacity transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
                    <span>Enviar Solicitud</span>
                    <span>📤</span>
                </button>
            </form>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="w-full border-t border-white/5 bg-black/20 py-8 mt-auto">
    <div class="max-w-6xl mx-auto px-6 text-center flex flex-col items-center">
        <span class="text-xs text-gray-600 font-light flex items-center gap-2 mb-2"><span class="text-white/40">🌱</span> SmartPlant CARE</span>
        <p class="text-[10px] text-gray-700">© 2026 Todos los derechos reservados.</p>
    </div>
</footer>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

</body>
</html>
