<?php
// ═══════════════════════════════════════════════════════════════
//  Dashboard.php — SmartPlant CARE (VISTA MVC PURA)
//  Presentación visual. Recibe los datos preparados por SmartPlant::dashboard()
// ═══════════════════════════════════════════════════════════════

$usuario_data       = $usuario_data ?? [];
$nombre             = $nombre ?? ($usuario_data['nombre'] ?? 'Usuario');
$plantas            = $plantas ?? [];
$planta_id          = $planta_id ?? 0;
$planta_actual      = $planta_actual ?? null;
$dispositivos       = $dispositivos ?? [];
$ultima             = $ultima ?? null;
$historial          = $historial ?? [];
$eventos            = $eventos ?? [];
$inventario         = $inventario ?? [];
$humedad            = $humedad ?? 0;
$temp               = $temp ?? 0;
$luz                = $luz ?? 0;
$tanque             = $tanque ?? 0;
$bateria            = $bateria ?? 0;
$salud              = $salud ?? 0;
$is_online          = $is_online ?? false;
$status_label       = $status_label ?? 'Sin dispositivos';
$status_badge_class = $status_badge_class ?? 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700';
$status_dot_class   = $status_dot_class ?? 'bg-slate-400';
$msg_perfil         = $msg_perfil ?? '';
$msg_settings       = $msg_settings ?? '';
$msg_planta         = $msg_planta ?? '';

// Íconos y textos para el renderizado visual de eventos
$evento_icono = [
    'riego'              => ['icon' => '💧', 'color' => 'green'],
    'alerta_humedad'     => ['icon' => '⚠️', 'color' => 'yellow'],
    'alerta_temperatura' => ['icon' => '🌡️', 'color' => 'orange'],
    'bateria_baja'       => ['icon' => '🔋', 'color' => 'cyan'],
    'sin_conexion'       => ['icon' => '📡', 'color' => 'red'],
    'otro'               => ['icon' => '🔔', 'color' => 'gray'],
];
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>

<body class="bg-solid-dark text-white min-h-screen">

<!-- Scroll Progress Bar -->
<div id="scrollProgress" class="fixed top-0 left-0 h-1 bg-emerald-500 z-[100] transition-all duration-150" style="width:0%"></div>

<!-- ═══ HEADER ═══ -->
<header class="sticky top-4 z-50 mx-auto max-w-6xl px-4">
    <div class="glass-clean flex items-center justify-between px-6 py-3.5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800">
        <a href="/" class="flex items-center gap-2.5 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        
        <div class="hidden sm:flex items-center gap-2 <?= $status_badge_class ?> border px-3 py-1 rounded-full text-xs font-semibold">
            <span class="w-2 h-2 rounded-full <?= $status_dot_class ?>"></span>
            <span><?= $status_label ?></span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-all shadow-sm" title="Cambiar tema" id="themeToggleBtn">
                <i data-lucide="moon" class="w-4 h-4"></i>
            </button>
            
            <div class="flex items-center gap-2.5 pl-2 border-l border-slate-200 dark:border-slate-800">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 border border-emerald-300 flex items-center justify-center text-xs text-white font-bold overflow-hidden shadow-sm cursor-pointer hover:scale-105 transition-transform" onclick="openProfileModal()" title="Mi Perfil">
                    <?php if(!empty($_SESSION['foto_perfil'])): ?>
                        <img id="headerAvatarImg" src="<?= htmlspecialchars($_SESSION['foto_perfil']) ?>" class="w-full h-full object-cover" alt="Perfil">
                        <span id="headerAvatarEmoji" class="hidden">👤</span>
                    <?php else: ?>
                        <span id="headerAvatarEmoji">👤</span>
                        <img id="headerAvatarImg" src="" class="w-full h-full object-cover hidden" alt="Perfil">
                    <?php endif; ?>
                </div>
                <div class="hidden md:block text-left">
                    <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight"><?= htmlspecialchars(explode(' ', $nombre)[0]) ?></p>
                    <p class="text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider"><?= $_SESSION['plan'] ?? 'free' ?></p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 ml-1">
                <button onclick="openProfileModal()" class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    Perfil
                </button>
                <a href="?logout=1" class="bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-200/80 dark:border-red-900/40 px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-red-100 dark:hover:bg-red-950/60 transition-all">
                    Salir
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ═══ HERO & PLANT SELECTOR ═══ -->
<section class="max-w-6xl mx-auto px-6 pt-10 pb-4">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                Hola, <span class="text-emerald-600"><?= htmlspecialchars(explode(' ', $nombre)[0]) ?></span>
            </h2>
            <p class="text-slate-500 text-sm mt-1">
                <?php if ($ultima): ?>
                    Última sincronización: <span class="font-medium text-slate-700 dark:text-slate-300"><?= date('d/m/Y H:i', strtotime($ultima['creada_en'])) ?></span>
                <?php else: ?>
                    Sin lecturas recientes. Conectá tu módulo ESP32.
                <?php endif; ?>
            </p>
        </div>

        <!-- Selector de planta -->
        <div class="flex items-center gap-2 flex-wrap">
            <?php foreach ($plantas as $p): ?>
            <a href="?planta=<?= $p['id'] ?>"
               class="px-4 py-2 rounded-full text-xs font-semibold border transition-all flex items-center gap-1.5 <?= $p['id'] == $planta_id ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:border-emerald-400' ?>">
                <i data-lucide="sprout" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($p['nombre']) ?>
            </a>
            <?php endforeach; ?>

            <?php if (count($plantas) > 0): ?>
            <button onclick="openAddPlantModal()" class="px-3.5 py-2 rounded-full text-xs border border-dashed border-emerald-500 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-all font-semibold flex items-center gap-1.5">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Agregar
            </button>
            <?php endif; ?>

            <?php if ($planta_actual): ?>
            <button onclick="openDeletePlantModal(<?= $planta_actual['id'] ?>, '<?= htmlspecialchars(addslashes($planta_actual['nombre'])) ?>')" class="px-3 py-2 rounded-full text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all font-medium flex items-center gap-1 border border-red-200 dark:border-red-900/40" title="Eliminar esta planta">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($msg_planta)): ?>
        <div class="mt-4 max-w-md"><?= $msg_planta ?></div>
    <?php endif; ?>

    <?php if (count($plantas) === 0): ?>
    <!-- Estado sin plantas -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center my-6 border border-emerald-500/20 max-w-lg mx-auto shadow-sm">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center mx-auto mb-3 border border-emerald-200 dark:border-emerald-800">
            <i data-lucide="sprout" class="w-7 h-7"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">No tenés plantas registradas</h3>
        <p class="text-slate-500 text-xs mt-1 mb-4">Agregá tu primera planta para comenzar a monitorear su estado en tiempo real.</p>
        <button onclick="openAddPlantModal()" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-md transition-all inline-flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Agregar mi primera planta
        </button>
    </div>
    <?php endif; ?>
</section>

<!-- ═══ DASHBOARD TAB NAVIGATION ═══ -->
<section class="max-w-6xl mx-auto px-6 pt-4 pb-2">
    <div class="flex items-center gap-2 p-1.5 bg-slate-100/90 dark:bg-slate-900/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 w-fit">
        <button class="dash-tab-btn active" data-tab="monitoring" onclick="switchDashboardTab('monitoring')">
            <i data-lucide="activity" class="w-4 h-4"></i>
            <span>Monitoreo en vivo</span>
        </button>
        <button class="dash-tab-btn" data-tab="history" onclick="switchDashboardTab('history')">
            <i data-lucide="history" class="w-4 h-4"></i>
            <span>Historial & Eventos</span>
        </button>
        <button class="dash-tab-btn" data-tab="gallery" onclick="switchDashboardTab('gallery')">
            <i data-lucide="camera" class="w-4 h-4"></i>
            <span>Galería & Diagnóstico</span>
        </button>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     TAB 1: MONITOREO EN VIVO
     ═══════════════════════════════════════════════════════════════ -->
<div id="tab-monitoring" class="dash-tab-panel active">

    <!-- STATS CARDS -->
    <section class="max-w-6xl mx-auto px-6 py-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="statsGrid">

            <!-- Humedad -->
            <div class="stat-card-clean">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Humedad de suelo</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 border border-emerald-200/60 dark:border-emerald-800/40">
                        <i data-lucide="droplets" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white" data-target="<?= $humedad ?>" data-suffix="%">0%</p>
                    <span class="badge-status <?= $humedad >= 40 && $humedad <= 60 ? 'badge-status-green' : 'badge-status-amber' ?>">
                        <?= $humedad >= 40 && $humedad <= 60 ? 'Óptimo' : ($humedad < 40 ? 'Bajo' : 'Exceso') ?>
                    </span>
                </div>
                <div class="progress-track h-1 mt-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-emerald-500 rounded-full" style="width:0%" data-width="<?= $humedad ?>%"></div>
                </div>
            </div>

            <!-- Temperatura -->
            <div class="stat-card-clean">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Temperatura</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 border border-amber-200/60 dark:border-amber-800/40">
                        <i data-lucide="thermometer" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white" data-target="<?= $temp ?>" data-suffix="°C">0°C</p>
                    <span class="badge-status <?= ($temp >= 20 && $temp <= 28) ? 'badge-status-green' : 'badge-status-amber' ?>">
                        <?= ($temp >= 20 && $temp <= 28) ? 'Ideal' : ($temp < 20 ? 'Frío' : 'Calor') ?>
                    </span>
                </div>
                <div class="progress-track h-1 mt-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-amber-500 rounded-full" style="width:0%" data-width="<?= min(100, round($temp / 50 * 100)) ?>%"></div>
                </div>
            </div>

            <!-- Luz -->
            <div class="stat-card-clean">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Luz Solar</span>
                    <div class="w-9 h-9 rounded-xl bg-yellow-50 dark:bg-yellow-950/50 flex items-center justify-center text-yellow-600 border border-yellow-200/60 dark:border-yellow-800/40">
                        <i data-lucide="sun" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white" data-target="<?= $luz ?>" data-suffix=" lx">0 lx</p>
                    <span class="badge-status <?= $luz > 200 ? 'badge-status-green' : 'badge-status-blue' ?>">
                        <?= $luz > 600 ? 'Alta' : ($luz > 200 ? 'Normal' : 'Poca') ?>
                    </span>
                </div>
                <div class="progress-track h-1 mt-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-yellow-500 rounded-full" style="width:0%" data-width="<?= min(100, round($luz / 1000 * 100)) ?>%"></div>
                </div>
            </div>
                
            <!-- Tanque -->
            <div class="stat-card-clean">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Agua en tanque</span>
                    <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center text-teal-600 border border-teal-200/60 dark:border-teal-800/40">
                        <i data-lucide="waves" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white" data-target="<?= $tanque ?>" data-suffix="%">0%</p>
                    <span class="badge-status <?= $tanque > 40 ? 'badge-status-green' : ($tanque > 20 ? 'badge-status-amber' : 'badge-status-red') ?>">
                        <?= $tanque > 50 ? 'Suficiente' : ($tanque > 20 ? 'Medio' : 'Rellenar') ?>
                    </span>
                </div>
                <div class="progress-track h-1 mt-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-teal-500 rounded-full" style="width:0%" data-width="<?= $tanque ?>%"></div>
                </div>
            

    </section>

    <!-- CHART + ESTADO SALUD -->
    <section class="max-w-6xl mx-auto px-6 py-4">
        <div class="grid lg:grid-cols-3 gap-4">

            <!-- Mini chart humedad -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Humedad en el tiempo</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">Últimas 12 horas</h3>
                    </div>
                    <span class="text-xs text-slate-500 font-medium bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">Lecturas cada 30 min</span>
                </div>
                <div class="flex items-end gap-1.5 h-32" id="chartBars">
                    <?php if (!empty($historial)): ?>
                        <?php foreach ($historial as $h): ?>
                            <?php $pct = min(100, max(5, round((float)$h['humedad_suelo']))); ?>
                            <div class="mini-bar flex-1 rounded-t-md hover:opacity-80 transition-opacity" style="height:<?= $pct ?>%;" title="<?= $h['hora'] ?> — <?= $h['humedad_suelo'] ?>%"></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ([35,50,40,65,60,55,70,45,58,62,50,47] as $v): ?>
                            <div class="mini-bar flex-1 rounded-t-md opacity-30" style="height:<?= $v ?>%;"></div>
                        <?php endforeach; ?>
                        <p class="absolute text-slate-500 text-xs font-light ml-2">Sin lecturas históricas aún</p>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-slate-400 text-[10px] font-medium">
                    <?php if (!empty($historial)): ?>
                        <span><?= $historial[0]['hora'] ?? '' ?></span>
                        <span><?= $historial[count($historial)-1]['hora'] ?? '' ?></span>
                    <?php else: ?>
                        <span>00:00</span>
                        <span>12:00</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estado de la planta -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-center text-center">
                <div class="ring-gauge mb-4">
                    <svg width="100" height="100" viewBox="0 0 120 120">
                        <circle class="ring-bg" cx="60" cy="60" r="52"/>
                        <circle class="ring-fill" cx="60" cy="60" r="52"
                            stroke="url(#ringGrad)"
                            stroke-dasharray="326.73"
                            stroke-dashoffset="326.73"
                            id="healthRing"/>
                        <defs>
                            <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#10b981"/>
                                <stop offset="100%" stop-color="#059669"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div>
                            <p class="text-2xl font-bold tracking-tight text-emerald-600"><?= $salud ?></p>
                            <p class="text-[8px] text-slate-400 font-semibold uppercase tracking-widest">Salud</p>
                        </div>
                    </div>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    <?= $salud >= 75 ? 'Excelente estado' : ($salud >= 50 ? 'Estado regular' : 'Atención requerida') ?>
                </h3>
                <p class="text-slate-500 text-xs font-normal mt-1 max-w-[220px]">
                    <?= $salud >= 75 ? 'Tu planta se encuentra en condiciones ideales.' : 'Revisá los parámetros de riego y luz.' ?>
                </p>
                <div class="w-full mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-3 text-center text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Agua</span>
                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5"><?= $humedad >= 40 ? 'Bien' : 'Baja' ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Clima</span>
                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5"><?= ($temp >= 20 && $temp <= 28) ? 'Ideal' : 'Alerta' ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Sol</span>
                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5"><?= $luz > 200 ? 'Bien' : 'Poca' ?></p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ACCIONES RÁPIDAS COMPACTAS -->
    <section class="max-w-6xl mx-auto px-6 py-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-emerald-600"></i> Control & Acciones Rápidas
                    </h4>
                    <p class="text-slate-500 text-xs mt-0.5">Comandos de acción inmediata sobre tus dispositivos.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition-all">
                        <i data-lucide="droplets" class="w-3.5 h-3.5"></i> Regar ahora
                    </button>
                    <button onclick="location.reload()" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Actualizar
                    </button>
                    <button onclick="openSettingsModal()" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all">
                        <i data-lucide="settings" class="w-3.5 h-3.5"></i> Umbrales
                    </button>
                    <a href="/hardware/connect" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all">
                        <i data-lucide="cpu" class="w-3.5 h-3.5"></i> Hardware & Sensores
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- ═══════════════════════════════════════════════════════════════
     TAB 2: HISTORIAL & EVENTOS
     ═══════════════════════════════════════════════════════════════ -->
<div id="tab-history" class="dash-tab-panel">
    <section class="max-w-6xl mx-auto px-6 py-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Registro de Actividad</h3>
                    <p class="text-slate-500 text-xs mt-0.5">Eventos automáticos, alertas de umbrales y riegos ejecutados.</p>
                </div>
                <span class="text-xs text-slate-500 font-medium bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">Últimos eventos</span>
            </div>
            
            <div class="space-y-3">
                <?php if (!empty($eventos)): ?>
                    <?php foreach ($eventos as $i => $ev): ?>
                        <?php
                        $info  = $evento_icono[$ev['tipo']] ?? $evento_icono['otro'];
                        $color = $info['color'];
                        $icon  = $info['icon'];
                        ?>
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-sm">
                                    <?= $icon ?>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($ev['mensaje']) ?></p>
                                    <p class="text-[10px] text-slate-400 uppercase font-medium mt-0.5"><?= htmlspecialchars($ev['tipo']) ?></p>
                                </div>
                            </div>
                            <span class="text-xs text-slate-500 font-medium">
                                <?php
                                $diff = time() - strtotime($ev['creado_en']);
                                if     ($diff < 3600)   echo 'Hace ' . round($diff/60)  . ' min';
                                elseif ($diff < 86400)  echo 'Hace ' . round($diff/3600) . ' h';
                                else                    echo 'Hace ' . round($diff/86400) . ' d';
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-10 text-slate-500">
                        <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                        <p class="text-sm font-medium">No hay eventos recientes registrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     TAB 3: GALERÍA & DIAGNÓSTICO IA
     ═══════════════════════════════════════════════════════════════ -->
<div id="tab-gallery" class="dash-tab-panel">
    <section class="max-w-6xl mx-auto px-6 py-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm" id="inventarioSection">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Inventario Botánico & Diagnóstico IA</h3>
                    <p class="text-slate-500 text-xs mt-0.5">Seguimiento fotográfico con análisis automático de salud vegetal.</p>
                </div>
                <button onclick="openInventoryUpload()" class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm transition-all">
                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                    <span>Subir foto</span>
                </button>
            </div>

            <!-- Timeline del inventario -->
            <div id="inventoryTimeline">
                <?php if (!empty($inventario)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($inventario as $i => $entry): ?>
                            <?php
                            $fecha_fmt  = date('d M Y, H:i', strtotime($entry['fecha']));
                            $diag_short = mb_strlen($entry['diagnostico']) > 100
                                ? mb_substr($entry['diagnostico'], 0, 100) . '...'
                                : $entry['diagnostico'];
                            ?>
                            <div class="inv-entry border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-800/40 hover:shadow-md transition-all group" data-id="<?= $entry['id'] ?>">
                                <div class="h-44 w-full bg-slate-200 dark:bg-slate-800 overflow-hidden relative cursor-pointer" onclick="openInventoryDetail(<?= $entry['id'] ?>, '<?= htmlspecialchars($entry['foto_path'], ENT_QUOTES) ?>', `<?= htmlspecialchars($entry['diagnostico'], ENT_QUOTES) ?>`, '<?= $entry['fecha'] ?>')">
                                    <img src="<?= htmlspecialchars($entry['foto_path']) ?>" alt="Foto planta" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                                    <div class="absolute top-2 right-2">
                                        <button onclick="event.stopPropagation(); deleteInventoryEntry(<?= $entry['id'] ?>)" class="w-7 h-7 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-red-600 transition-colors" title="Eliminar">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-3.5">
                                    <span class="text-[10px] text-slate-400 font-semibold block mb-1"><?= $fecha_fmt ?></span>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-normal leading-relaxed line-clamp-2"><?= htmlspecialchars($diag_short) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl" id="invEmptyState">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="camera" class="w-6 h-6"></i>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 text-sm font-semibold mb-1">Sin fotografías registradas</p>
                        <p class="text-slate-500 text-xs max-w-sm mx-auto mb-4">Subí una foto de tu planta para que la inteligencia artificial analice su estado.</p>
                        <button onclick="openInventoryUpload()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl inline-flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Subir primera foto
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Footer -->
<footer class="max-w-6xl mx-auto px-6 py-8 mt-6 border-t border-slate-200/80 dark:border-slate-800 text-slate-500 text-xs">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <span class="flex items-center gap-1.5 font-medium"><i data-lucide="sprout" class="w-4 h-4 text-emerald-600"></i> SmartPlant CARE</span>
        <div class="flex gap-4">
            <a href="/" class="hover:text-slate-900 dark:hover:text-white transition-colors">Inicio</a>
            <a href="/support" class="hover:text-slate-900 dark:hover:text-white transition-colors">Soporte</a>
        </div>
        <p class="text-[11px]">© 2026 Todos los derechos reservados.</p>
    </div>
</footer>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

<!-- ═══ MODAL CONFIGURACIÓN PLANTA ═══ -->
<div id="settingsModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
    <div class="glass-form rounded-3xl p-8 max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 border border-black/10 dark:border-white/10" id="settingsModalContent">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Configuración de Planta</h3>
            <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">✕</button>
        </div>
        
        <?= $msg_settings ?>

        <form method="POST" action="">
            <input type="hidden" name="update_settings" value="1">
            <input type="hidden" name="planta_id" value="<?= $planta_actual ? $planta_actual['id'] : 0 ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Humedad Mínima (%)</label>
                    <input type="number" name="humedad_min" value="<?= $planta_actual ? htmlspecialchars($planta_actual['humedad_min']) : 35 ?>" class="w-full input-glass rounded-xl px-4 py-3 text-sm font-medium" step="1" required>
                    <p class="text-[10px] text-gray-500 mt-1">Nivel en el que tu planta necesita agua.</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Humedad Máxima (%)</label>
                    <input type="number" name="humedad_max" value="<?= $planta_actual ? htmlspecialchars($planta_actual['humedad_max']) : 65 ?>" class="w-full input-glass rounded-xl px-4 py-3 text-sm font-medium" step="1" required>
                    <p class="text-[10px] text-gray-500 mt-1">Nivel donde el riego se detiene automáticamente.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Temp. Mínima (°C)</label>
                        <input type="number" name="temp_min" value="<?= $planta_actual ? htmlspecialchars($planta_actual['temp_min']) : 15 ?>" class="w-full input-glass rounded-xl px-4 py-3 text-sm font-medium" step="0.1" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Temp. Máxima (°C)</label>
                        <input type="number" name="temp_max" value="<?= $planta_actual ? htmlspecialchars($planta_actual['temp_max']) : 35 ?>" class="w-full input-glass rounded-xl px-4 py-3 text-sm font-medium" step="0.1" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl mt-8 shadow-lg shadow-emerald-600/25 transition-all">
                Guardar Ajustes
            </button>
        </form>
    </div>
</div>

<!-- ═══ MODAL PERFIL INTERACTIVO ═══ -->
<div id="profileModal" class="fixed inset-0 bg-black/50 backdrop-blur-md z-[9999] opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
    <div class="glass-form rounded-[2.5rem] p-6 md:p-8 max-w-lg w-full shadow-2xl transform scale-95 transition-all duration-300 relative border border-black/10 dark:border-white/10 overflow-hidden" id="profileModalContent">
        
        <!-- Header con avatar y badge de rol -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center gap-4">
                <div class="relative group cursor-pointer" onclick="switchProfileTab('foto')" title="Cambiar foto de perfil">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-green-500/10 border-2 border-emerald-500/30 flex items-center justify-center overflow-hidden shadow-md transition-transform group-hover:scale-105">
                        <img id="avatarPreviewImg" src="<?= !empty($usuario_data['foto_perfil']) ? htmlspecialchars($usuario_data['foto_perfil']) : 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%2310b981\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z\'/></svg>' ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white"><?= htmlspecialchars(($usuario_data['nombre'] ?? '') . ' ' . ($usuario_data['apellido'] ?? '')) ?></h3>
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mt-0.5"><?= htmlspecialchars($usuario_data['email'] ?? '') ?></p>
                </div>
            </div>
            <button type="button" onclick="closeProfileModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Alerta de mensaje backend -->
        <?= $msg_perfil ?>

        <!-- Navegación por pestañas interactivas -->
        <div class="flex border-b border-gray-200 dark:border-white/10 mb-6 gap-2">
            <button type="button" id="tabBtnInfo" onclick="switchProfileTab('info')" class="pb-3 px-3 text-xs font-bold border-b-2 border-emerald-500 text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 transition-all">
                <i data-lucide="user" class="w-4 h-4"></i> Datos personales
            </button>
            <button type="button" id="tabBtnFoto" onclick="switchProfileTab('foto')" class="pb-3 px-3 text-xs font-bold border-b-2 border-transparent text-gray-700 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1.5 transition-all">
                <i data-lucide="image" class="w-4 h-4"></i> Foto de perfil
            </button>
            <button type="button" id="tabBtnSecurity" onclick="switchProfileTab('security')" class="pb-3 px-3 text-xs font-bold border-b-2 border-transparent text-gray-700 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1.5 transition-all">
                <i data-lucide="lock" class="w-4 h-4"></i> Seguridad
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5" id="profileForm">
            <input type="hidden" name="update_profile" value="1">
            <input type="file" id="fotoInputProfile" name="foto_perfil" accept="image/*" class="hidden" onchange="previewProfileImage(event)">

            <!-- TAB 1: DATOS PERSONALES -->
            <div id="tabContentInfo" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Nombre</label>
                        <div class="relative">
                            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario_data['nombre'] ?? '') ?>" required minlength="2" class="w-full input-glass rounded-2xl px-4 py-3 text-sm pl-10 font-medium">
                            <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Apellido</label>
                        <div class="relative">
                            <input type="text" name="apellido" value="<?= htmlspecialchars($usuario_data['apellido'] ?? '') ?>" required minlength="2" class="w-full input-glass rounded-2xl px-4 py-3 text-sm pl-10 font-medium">
                            <i data-lucide="user-check" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Correo electrónico</label>
                    <div class="relative">
                        <input type="email" name="email" value="<?= htmlspecialchars($usuario_data['email'] ?? '') ?>" required class="w-full input-glass rounded-2xl px-4 py-3 text-sm pl-10 font-medium">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Teléfono</label>
                    <div class="relative">
                        <input type="text" name="telefono" value="<?= htmlspecialchars($usuario_data['telefono'] ?? '') ?>" placeholder="+54 9 11 1234-5678" class="w-full input-glass rounded-2xl px-4 py-3 text-sm pl-10 font-medium">
                        <i data-lucide="phone" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- TAB 2: FOTO DE PERFIL -->
            <div id="tabContentFoto" class="space-y-4 hidden">
                <div class="p-6 border-2 border-dashed border-gray-300 dark:border-white/15 rounded-3xl text-center bg-black/5 dark:bg-white/5 transition-colors hover:border-emerald-500 flex flex-col items-center justify-center gap-3 cursor-pointer" onclick="document.getElementById('fotoInputProfile').click()">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-emerald-500/40 shadow-lg relative group">
                        <img id="avatarBigPreview" src="<?= !empty($usuario_data['foto_perfil']) ? htmlspecialchars($usuario_data['foto_perfil']) : 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%2310b981\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z\'/></svg>' ?>" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Hacé clic para seleccionar una nueva foto</p>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mt-1">Formatos soportados: JPG, JPEG, JFIF, PNG, WEBP, GIF</p>
                    </div>
                    <button type="button" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md flex items-center gap-2 pointer-events-none">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> Elegir imagen
                    </button>
                    <p id="fileNameDisplay" class="text-xs text-emerald-700 dark:text-emerald-400 font-bold hidden"></p>
                </div>
            </div>

            <!-- TAB 3: SEGURIDAD Y CONTRASEÑA -->
            <div id="tabContentSecurity" class="space-y-4 hidden">
                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Nueva Contraseña (Opcional)</label>
                    <div class="relative">
                        <input type="password" id="profilePasswordInput" name="password" placeholder="••••••••" class="w-full input-glass rounded-2xl px-4 py-3 text-sm pl-10 pr-10 font-medium" oninput="checkPasswordStrength(this.value)">
                        <i data-lucide="key" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <button type="button" onclick="toggleProfilePassword()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i data-lucide="eye" id="profilePassEye" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <p class="text-[11px] font-medium text-gray-700 dark:text-gray-300 mt-1.5 ml-1">Dejá este campo vacío si no querés cambiar tu contraseña actual.</p>
                </div>

                <div id="strengthMeter" class="hidden space-y-1 pt-1">
                    <div class="flex justify-between text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                        <span>Fuerza de la contraseña:</span>
                        <span id="strengthText">Débil</span>
                    </div>
                    <div class="h-1.5 w-full bg-gray-200 dark:bg-white/10 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full w-1/4 bg-red-500 transition-all duration-300"></div>
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="pt-4 flex items-center gap-3 border-t border-gray-200 dark:border-white/10">
                <button type="button" onclick="closeProfileModal()" class="w-1/3 py-3.5 rounded-2xl text-sm font-bold border border-gray-300 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/5 transition-all text-gray-800 dark:text-gray-200">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl py-3.5 text-sm shadow-lg shadow-emerald-600/25 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Guardar cambios</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL AGREGAR PLANTA ═══ -->
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300" id="addPlantModal">
    <div class="glass-form rounded-[2.5rem] p-6 md:p-8 max-w-lg w-full shadow-2xl transform scale-95 transition-all duration-300 relative border border-black/10 dark:border-white/10 overflow-hidden" id="addPlantModalContent">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="sprout" class="w-5 h-5"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Agregar nueva planta</h3>
            </div>
            <button type="button" onclick="closeAddPlantModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_plant" value="1">
            
            <div>
                <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Nombre de la Planta *</label>
                <input type="text" name="nombre_planta" required placeholder="Ej. Ficus Balcón, Monstera..." class="w-full input-glass rounded-2xl px-4 py-3 text-sm font-medium">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Especie / Tipo</label>
                    <input type="text" name="especie_planta" placeholder="Ej. Monstera deliciosa" class="w-full input-glass rounded-2xl px-4 py-3 text-sm font-medium">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Ubicación / Notas</label>
                    <input type="text" name="descripcion_planta" placeholder="Ej. Living, Balcón norte" class="w-full input-glass rounded-2xl px-4 py-3 text-sm font-medium">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Humedad Mín (%)</label>
                    <input type="number" step="1" name="humedad_min" value="35" class="w-full input-glass rounded-2xl px-4 py-2.5 text-sm font-medium">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-300 uppercase tracking-widest mb-1.5 ml-1">Humedad Máx (%)</label>
                    <input type="number" step="1" name="humedad_max" value="65" class="w-full input-glass rounded-2xl px-4 py-2.5 text-sm font-medium">
                </div>
            </div>

            <div class="pt-4 flex items-center gap-3 border-t border-gray-200 dark:border-white/10">
                <button type="button" onclick="closeAddPlantModal()" class="w-1/3 py-3 rounded-2xl text-sm font-bold border border-gray-300 dark:border-white/10 text-gray-800 dark:text-gray-200">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl py-3 text-sm shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Guardar Planta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL ELIMINAR PLANTA ═══ -->
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300" id="deletePlantModal">
    <div class="glass-form rounded-[2.5rem] p-6 md:p-8 max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 relative border border-black/10 dark:border-white/10 overflow-hidden" id="deletePlantModalContent">
        <div class="text-center p-2">
            <div class="w-14 h-14 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center mx-auto mb-4 border border-red-500/20">
                <i data-lucide="trash-2" class="w-7 h-7"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">¿Eliminar planta?</h3>
            <p class="text-gray-500 text-sm mt-2 mb-6">Estás por eliminar la planta <strong id="deletePlantName" class="text-slate-900 dark:text-white"></strong>. Esta acción la quitará de tu panel.</p>

            <form method="POST">
                <input type="hidden" name="delete_plant" value="1">
                <input type="hidden" name="planta_id" id="deletePlantIdInput" value="">

                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeDeletePlantModal()" class="w-1/2 py-3 rounded-2xl text-sm font-bold border border-gray-300 dark:border-white/10 text-gray-800 dark:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" class="w-1/2 bg-red-600 hover:bg-red-500 text-white font-semibold rounded-2xl py-3 text-sm shadow-lg shadow-red-600/25">
                        Sí, eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ─── Theme Toggle ───
    function applyTheme(theme) {
        const next = theme === 'light' ? 'light' : 'dark';
        const btn = document.getElementById('themeToggleBtn');

        document.documentElement.setAttribute('data-theme', next);
        if (next === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('sp_theme', next);
        document.body.classList.toggle('text-gray-900', next === 'light');

        if (btn) {
            btn.innerHTML = next === 'dark' 
                ? '<i data-lucide="sun" class="w-4 h-4 text-amber-400"></i>' 
                : '<i data-lucide="moon" class="w-4 h-4 text-slate-700"></i>';
            btn.setAttribute('aria-label', next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            btn.setAttribute('title', next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sp_theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    // Load saved theme
    applyTheme(localStorage.getItem('sp_theme') || 'dark');

    // ─── Dashboard Tab Switching ───
    function switchDashboardTab(tabName) {
        document.querySelectorAll('.dash-tab-btn').forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        document.querySelectorAll('.dash-tab-panel').forEach(panel => {
            if (panel.id === 'tab-' + tabName) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
        if (tabName === 'monitoring') {
            animateRing();
            animateProgressBars();
        }
    }

    // ─── Profile Modal & Interactive Features ───
    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        const content = document.getElementById('profileModalContent');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
        if (window.lucide) lucide.createIcons();
    }

    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        const content = document.getElementById('profileModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    // ─── Add & Delete Plant Modals ───
    function openAddPlantModal() {
        const modal = document.getElementById('addPlantModal');
        const content = document.getElementById('addPlantModalContent');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
        if (window.lucide) lucide.createIcons();
    }

    function closeAddPlantModal() {
        const modal = document.getElementById('addPlantModal');
        const content = document.getElementById('addPlantModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function openDeletePlantModal(id, name) {
        document.getElementById('deletePlantIdInput').value = id;
        document.getElementById('deletePlantName').textContent = name;
        const modal = document.getElementById('deletePlantModal');
        const content = document.getElementById('deletePlantModalContent');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
        if (window.lucide) lucide.createIcons();
    }

    function closeDeletePlantModal() {
        const modal = document.getElementById('deletePlantModal');
        const content = document.getElementById('deletePlantModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function switchProfileTab(tab) {
        const tabs = ['info', 'foto', 'security'];
        tabs.forEach(t => {
            const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            const content = document.getElementById('tabContent' + t.charAt(0).toUpperCase() + t.slice(1));
            if (t === tab) {
                btn.classList.add('border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400');
                btn.classList.remove('border-transparent', 'text-gray-400');
                content.classList.remove('hidden');
            } else {
                btn.classList.remove('border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400');
                btn.classList.add('border-transparent', 'text-gray-400');
                content.classList.add('hidden');
            }
        });
        if (window.lucide) lucide.createIcons();
    }

    function previewProfileImage(event) {
        const file = event.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            const modalPreview = document.getElementById('avatarPreviewImg');
            const bigPreview = document.getElementById('avatarBigPreview');
            const headerImg = document.getElementById('headerAvatarImg');
            const headerEmoji = document.getElementById('headerAvatarEmoji');

            if (modalPreview) modalPreview.src = url;
            if (bigPreview) bigPreview.src = url;

            if (headerImg) {
                headerImg.src = url;
                headerImg.classList.remove('hidden');
            }
            if (headerEmoji) {
                headerEmoji.classList.add('hidden');
            }

            const nameDisplay = document.getElementById('fileNameDisplay');
            if (nameDisplay) {
                nameDisplay.textContent = 'Imagen seleccionada: ' + file.name;
                nameDisplay.classList.remove('hidden');
            }
        }
    }

    function toggleProfilePassword() {
        const input = document.getElementById('profilePasswordInput');
        const eye = document.getElementById('profilePassEye');
        if (input.type === 'password') {
            input.type = 'text';
            eye.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            eye.setAttribute('data-lucide', 'eye');
        }
        if (window.lucide) lucide.createIcons();
    }

    function checkPasswordStrength(val) {
        const meter = document.getElementById('strengthMeter');
        const text = document.getElementById('strengthText');
        const bar = document.getElementById('strengthBar');
        if (!val) {
            meter.classList.add('hidden');
            return;
        }
        meter.classList.remove('hidden');
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        if (score <= 1) {
            text.textContent = 'Débil';
            bar.className = 'h-full w-1/4 bg-red-500 transition-all duration-300';
        } else if (score === 2 || score === 3) {
            text.textContent = 'Media';
            bar.className = 'h-full w-2/3 bg-amber-500 transition-all duration-300';
        } else {
            text.textContent = 'Fuerte';
            bar.className = 'h-full w-full bg-emerald-500 transition-all duration-300';
        }
    }

    // Auto-open if there was a message
    <?php if(!empty($msg_perfil)): ?>
        setTimeout(openProfileModal, 300);
    <?php endif; ?>

    // Close on click outside
    document.getElementById('profileModal').addEventListener('click', (e) => {
        if(e.target === e.currentTarget) closeProfileModal();
    });

    // ─── Scroll Progress ───
    window.addEventListener('scroll', () => {
        const pct = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
        document.getElementById('scrollProgress').style.width = pct + '%';
    });

    // ─── Intersection Observer ───
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('active');
                if (e.target.id === 'statsGrid') { animateCounters(); animateProgressBars(); }
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

    document.querySelectorAll('.reveal, .reveal-blur, .reveal-scale, .stagger-children').forEach(el => observer.observe(el));

    // ─── Counter Animation ───
    let done = false;
    function animateCounters() {
        if (done) return; done = true;
        document.querySelectorAll('[data-target]').forEach(el => {
            const target = parseFloat(el.getAttribute('data-target'));
            const suffix = el.getAttribute('data-suffix') || '';
            const dur    = 2000;
            const start  = performance.now();
            function step(now) {
                const p = Math.min((now - start) / dur, 1);
                const e = 1 - Math.pow(1 - p, 3);
                el.textContent = (Number.isInteger(target) ? Math.round(e * target) : (e * target).toFixed(1)) + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    // ─── Progress Bars ───
    function animateProgressBars() {
        document.querySelectorAll('.progress-fill[data-width]').forEach(bar => {
            setTimeout(() => bar.style.width = bar.dataset.width, 400);
        });
    }

    // ─── Health Ring ───
    function animateRing() {
        const ring = document.getElementById('healthRing');
        if (!ring) return;
        const circ   = 326.73;
        const health = <?= $salud ?>;
        setTimeout(() => ring.style.strokeDashoffset = circ - (circ * health / 100), 700);
    }

    // ── Settings Modal ──
    function openSettingsModal() {
        const modal = document.getElementById('settingsModal');
        const content = document.getElementById('settingsModalContent');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }

    function closeSettingsModal() {
        const modal = document.getElementById('settingsModal');
        const content = document.getElementById('settingsModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    // ─── Init ───
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => document.querySelectorAll('.reveal-blur').forEach(el => el.classList.add('active')), 80);
        animateCounters();
        animateProgressBars();
        animateRing();
        if (typeof lucide !== 'undefined') lucide.createIcons();

        <?php if (!empty($msg_settings)): ?>
            openSettingsModal();
        <?php endif; ?>

        // Inventory drag & drop setup
        setupInventoryDropzone();
    });

    // ═══ INVENTORY ═══

    // ── Modals ──
    function openInventoryUpload() {
        const modal = document.getElementById('invUploadModal');
        const content = document.getElementById('invUploadModalContent');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }
    function closeInventoryUpload() {
        const modal = document.getElementById('invUploadModal');
        const content = document.getElementById('invUploadModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        // Reset form
        document.getElementById('invFileInput').value = '';
        document.getElementById('invPreviewImg').classList.add('hidden');
        document.getElementById('invPreviewImg').src = '';
        document.getElementById('invDropzoneContent').classList.remove('hidden');
        document.getElementById('invUploadBtn').disabled = true;
    }
    document.getElementById('invUploadModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeInventoryUpload();
    });

    function openInventoryDetail(id, foto, diagnostico, fecha) {
        const modal = document.getElementById('invDetailModal');
        const content = document.getElementById('invDetailModalContent');
        document.getElementById('invDetailImg').src = foto;
        document.getElementById('invDetailDiag').textContent = diagnostico;
        const d = new Date(fecha);
        document.getElementById('invDetailDate').textContent =
            d.toLocaleDateString('es-AR', { day: '2-digit', month: 'long', year: 'numeric' }) +
            ' — ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        modal.classList.remove('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }
    function closeInventoryDetail() {
        const modal = document.getElementById('invDetailModal');
        const content = document.getElementById('invDetailModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }
    document.getElementById('invDetailModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeInventoryDetail();
    });

    // ── Preview de imagen ──
    function previewInventoryImage(e) {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            alert('La imagen es muy pesada. Máximo 10MB.');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('invPreviewImg').src = ev.target.result;
            document.getElementById('invPreviewImg').classList.remove('hidden');
            document.getElementById('invDropzoneContent').classList.add('hidden');
            document.getElementById('invUploadBtn').disabled = false;
        };
        reader.readAsDataURL(file);
    }

    // ── Drag & Drop ──
    function setupInventoryDropzone() {
        const dz = document.getElementById('invDropzone');
        if (!dz) return;
        ['dragenter', 'dragover'].forEach(ev => {
            dz.addEventListener(ev, e => {
                e.preventDefault();
                dz.classList.add('inv-dropzone-active');
            });
        });
        ['dragleave', 'drop'].forEach(ev => {
            dz.addEventListener(ev, e => {
                e.preventDefault();
                dz.classList.remove('inv-dropzone-active');
            });
        });
        dz.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('invFileInput');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                previewInventoryImage({ target: input });
            }
        });
    }

    // ── Upload con diagnóstico IA ──
    async function handleInventoryUpload(e) {
        e.preventDefault();
        const fileInput = document.getElementById('invFileInput');
        const file = fileInput.files[0];
        if (!file) return;

        const btn = document.getElementById('invUploadBtn');
        const btnText = document.getElementById('invUploadBtnText');
        const btnLoad = document.getElementById('invUploadBtnLoading');
        btn.disabled = true;
        btnText.classList.add('hidden');
        btnLoad.classList.remove('hidden');
        btnLoad.classList.add('flex');

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('planta_id', '<?= $planta_id ?>');
        formData.append('foto', file);

        try {
            const res = await fetch('/inventory?action=upload', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success && data.entry) {
                addTimelineEntry(data.entry);
                closeInventoryUpload();
            } else {
                alert(data.error || 'Error al subir la foto.');
            }
        } catch (err) {
            alert('Error de conexión.');
        } finally {
            btn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoad.classList.add('hidden');
            btnLoad.classList.remove('flex');
        }
    }

    // ── Agregar entrada al timeline dinámicamente ──
    function addTimelineEntry(entry) {
        const timeline = document.getElementById('inventoryTimeline');
        // Remove empty state if present
        const empty = document.getElementById('invEmptyState');
        if (empty) empty.remove();

        // Create or get timeline container
        let container = timeline.querySelector('.inv-timeline');
        if (!container) {
            container = document.createElement('div');
            container.className = 'inv-timeline';
            timeline.appendChild(container);
        }

        const d = new Date(entry.fecha);
        const fechaFmt = d.toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' });
        const horaFmt = d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        const diagShort = entry.diagnostico.length > 120 ? entry.diagnostico.substring(0, 120) + '...' : entry.diagnostico;
        const diagEscaped = entry.diagnostico.replace(/'/g, "\\'").replace(/`/g, "\\`");

        const div = document.createElement('div');
        div.className = 'inv-entry';
        div.dataset.id = entry.id;
        div.style.animation = 'fadeSlideIn 0.5s var(--ease-out) forwards';
        div.innerHTML = `
            <div class="inv-entry-dot"></div>
            <div class="inv-entry-line"></div>
            <div class="inv-entry-content">
                <div class="inv-entry-card" onclick="openInventoryDetail(${entry.id}, '${entry.foto_path}', \`${diagEscaped}\`, '${entry.fecha}')">
                    <div class="inv-entry-img-wrap">
                        <img src="${entry.foto_path}" alt="Foto planta" class="inv-entry-img">
                    </div>
                    <div class="inv-entry-info">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="text-xs font-semibold text-white/90">${fechaFmt}</span>
                            <span class="text-[10px] text-gray-600">${horaFmt}</span>
                        </div>
                        <p class="text-xs text-gray-400 font-light leading-relaxed">${diagShort}</p>
                    </div>
                </div>
                <button onclick="event.stopPropagation(); deleteInventoryEntry(${entry.id})" class="inv-delete-btn" title="Eliminar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                </button>
            </div>
        `;

        container.prepend(div);
    }

    // ── Eliminar entrada ──
    async function deleteInventoryEntry(id) {
        if (!confirm('¿Eliminar esta foto del inventario?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        try {
            const res = await fetch('/inventory?action=delete', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                const entry = document.querySelector(`.inv-entry[data-id="${id}"]`);
                if (entry) {
                    entry.style.animation = 'fadeSlideOut 0.3s ease forwards';
                    setTimeout(() => {
                        entry.remove();
                        // Check if timeline is empty
                        const container = document.querySelector('.inv-timeline');
                        if (container && container.children.length === 0) {
                            container.remove();
                            const timeline = document.getElementById('inventoryTimeline');
                            timeline.innerHTML = `
                                <div class="inv-empty" id="invEmptyState">
                                    <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-3xl">📸</div>
                                    <p class="text-gray-400 text-sm font-medium mb-1">Sin fotos todavía</p>
                                    <p class="text-gray-600 text-xs font-light">Subí una foto de tu planta y la IA la analizará automáticamente.</p>
                                </div>`;
                        }
                    }, 300);
                }
            } else {
                alert(data.error || 'Error al eliminar.');
            }
        } catch (err) {
            alert('Error de conexión.');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
</body>
</html>
