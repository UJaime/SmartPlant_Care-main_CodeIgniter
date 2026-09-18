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

<!-- Header -->
<header class="sticky top-4 z-50 mx-auto max-w-6xl px-4">
    <div class="glass-clean flex items-center justify-between px-6 py-3.5 rounded-2xl shadow-sm border border-slate-200/80">
        <a href="/" class="flex items-center gap-2.5 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <a href="/dashboard" class="btn-emerald-outline px-4 py-1.5 rounded-full text-xs font-semibold transition-all">Volver al Dashboard</a>
    </div>
</header>

<!-- Contenido Principal -->
<main class="flex-1 flex items-center justify-center py-12 px-4">
    <div class="max-w-xl w-full">
        <div class="text-center mb-10">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-200 shadow-sm">
                <i data-lucide="life-buoy" class="w-7 h-7"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4 text-slate-900">Centro de <span class="text-emerald-600">Soporte</span></h1>
            <p class="text-slate-600 font-light text-lg">¿Estás experimentando problemas con tus sensores o con el panel? Describinos tu problema y te ayudaremos a solucionarlo.</p>
        </div>

        <div class="card-glass rounded-[2rem] p-8 md:p-12 relative overflow-hidden">
            <?= $msg_estado ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Tu Correo Electrónico</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" name="email" value="<?= htmlspecialchars($usuario_email) ?>" required
                            class="input-glass w-full rounded-xl pl-12 pr-4 py-3.5 transition-all"
                            placeholder="tucorreo@ejemplo.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Descripción del Problema</label>
                    <textarea name="problema" rows="5" required
                        class="input-glass w-full rounded-xl px-4 py-3.5 transition-all resize-none"
                        placeholder="Ej: El sensor de humedad no está actualizando los datos..."></textarea>
                </div>

                <button type="submit" class="btn-emerald w-full py-4 rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <span>Enviar Solicitud</span>
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="w-full border-t border-slate-200/80 py-8 mt-auto">
    <div class="max-w-6xl mx-auto px-6 text-center flex flex-col items-center">
        <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5 mb-2"><i data-lucide="sprout" class="w-4 h-4 text-emerald-600"></i> SmartPlant CARE</span>
        <p class="text-[10px] text-slate-400">© 2026 Todos los derechos reservados.</p>
    </div>
</footer>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

</body>
</html>
