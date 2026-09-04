<?php
// ═══════════════════════════════════════════════════════════════
//  HardwareConnect.php — SmartPlant CARE (VISTA MVC PURA)
//  Presentación visual. Recibe los datos preparados por SmartPlant::hardwareConnect()
// ═══════════════════════════════════════════════════════════════

$usuarioId      = $usuarioId ?? ($_SESSION['usuario_id'] ?? 1);
$plantas        = $plantas ?? [];
$devices        = $devices ?? [];
$selectedDevice = $selectedDevice ?? ($devices[0] ?? null);
$components     = $components ?? [];
$latest         = $latest ?? null;
$analysis       = $analysis ?? [];
$connectedCount = $connectedCount ?? 0;
$error_msg      = $error_msg ?? '';
$success_msg    = $success_msg ?? '';
$createdDevice  = $createdDevice ?? null;

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function statusClass(string $state): string
{
    return match ($state) {
        'conectado' => 'hw-status-on',
        'advertencia' => 'hw-status-warn',
        default => 'hw-status-off',
    };
}

function componentDisplayValue(array $component): string
{
    $value = trim((string) ($component['valor'] ?? ''));
    if ($value === '') {
        return '-';
    }

    $unit = trim((string) ($component['unidad'] ?? ''));
    if ($unit === '' || preg_match('/[A-Za-z%]/', $value)) {
        return $value;
    }

    return trim($value . ' ' . $unit);
}

// Determinar pestaña inicial
$initialTab = 'telemetry';
if (isset($_GET['tab']) && $_GET['tab'] === 'new' || isset($_GET['action']) && $_GET['action'] === 'new' || (count($devices) === 0 && empty($success_msg))) {
    $initialTab = 'new';
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware & Sensores — SmartPlant CARE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .hw-panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .hw-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 0.35rem 0.7rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-sub);
            background: var(--surface);
        }
        .hw-dot {
            width: 0.58rem;
            height: 0.58rem;
            border-radius: 999px;
            background: #9ca3af;
            box-shadow: 0 0 0 4px rgba(156, 163, 175, 0.12);
            flex: 0 0 auto;
        }
        .hw-status-on .hw-dot {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.16);
        }
        .hw-status-warn .hw-dot {
            background: #d97706;
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.16);
        }
        .hw-status-off .hw-dot {
            background: #9ca3af;
            box-shadow: 0 0 0 4px rgba(156, 163, 175, 0.12);
        }
        .hw-reading {
            min-height: 7.5rem;
        }

        /* Estilos de Alta Visibilidad */
        .card-soil {
            background: #ecfdf5;
            border: 2px solid #059669;
        }
        .card-light {
            background: #fffbeb;
            border: 2px solid #d97706;
        }
        html[data-theme="dark"] .card-soil {
            background: rgba(6, 78, 59, 0.4);
            border: 2px solid #10b981;
        }
        html[data-theme="dark"] .card-light {
            background: rgba(120, 53, 15, 0.4);
            border: 2px solid #f59e0b;
        }
        .card-title-soil { color: #064e3b; font-weight: 900; }
        .card-title-light { color: #78350f; font-weight: 900; }
        html[data-theme="dark"] .card-title-soil { color: #a7f3d0; font-weight: 900; }
        html[data-theme="dark"] .card-title-light { color: #fde68a; font-weight: 900; }
        .badge-live-soil { background-color: #059669; color: #ffffff; font-weight: 800; }
        .badge-live-light { background-color: #d97706; color: #ffffff; font-weight: 800; }
        .val-soil { color: #064e3b; font-weight: 900; }
        .val-light { color: #78350f; font-weight: 900; }
        html[data-theme="dark"] .val-soil { color: #ffffff; font-weight: 900; }
        html[data-theme="dark"] .val-light { color: #ffffff; font-weight: 900; }
        .sub-soil { color: #065f46; font-weight: 700; }
        .sub-light { color: #92400e; font-weight: 700; }
        html[data-theme="dark"] .sub-soil { color: #d1fae5; font-weight: 700; }
        html[data-theme="dark"] .sub-light { color: #fef3c7; font-weight: 700; }

        /* Estilos de las pestañas unificadas */
        .tab-btn {
            transition: all 0.2s ease;
        }
        .tab-btn.active {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-solid-dark text-white min-h-screen">
<script>
    document.documentElement.setAttribute('data-theme', localStorage.getItem('sp_theme') || 'dark');
    if (localStorage.getItem('sp_theme') === 'dark' || !localStorage.getItem('sp_theme')) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>

<!-- ═══ HEADER ═══ -->
<header class="sticky top-4 z-50 mx-auto max-w-6xl px-4">
    <div class="glass-clean flex items-center justify-between px-6 py-3.5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800">
        <a href="/" class="flex items-center gap-2.5 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        
        <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
            <button onclick="switchTab('telemetry')" id="btnTabTelemetry" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 <?= $initialTab === 'telemetry' ? 'active' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' ?>">
                <i data-lucide="cpu" class="w-3.5 h-3.5"></i> Monitoreo
            </button>
            <button onclick="switchTab('new')" id="btnTabNew" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 <?= $initialTab === 'new' ? 'active' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' ?>">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Alta de Sensor
            </button>
        </div>

        <div class="flex items-center gap-3">
            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-all shadow-sm" title="Cambiar tema" id="themeToggleBtn">
                <i data-lucide="moon" class="w-4 h-4"></i>
            </button>
            
            <a href="/dashboard" class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3.5 py-1.5 rounded-full text-xs font-semibold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                Dashboard
            </a>
            <a href="/dashboard?logout=1" class="bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-200/80 dark:border-red-900/40 px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-red-100 dark:hover:bg-red-950/60 transition-all">
                Salir
            </a>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-8">
    
    <!-- Mensajes de Notificación Globales -->
    <?php if ($success_msg): ?>
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
            <div>
                <p><?= h($success_msg) ?></p>
                <?php if ($createdDevice): ?>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1 font-mono">Código: <strong><?= h($createdDevice['codigo']) ?></strong> | API Key: <strong><?= h($createdDevice['api_key']) ?></strong></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/50 border border-red-300 dark:border-red-800 text-red-800 dark:text-red-300 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 flex-shrink-0"></i>
            <p><?= h($error_msg) ?></p>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════
         TAB 1: MONITOREO & TELEMETRÍA DE HARDWARE
         ═══════════════════════════════════════════════════════════════ -->
    <div id="tabContentTelemetry" class="tab-content <?= $initialTab === 'telemetry' ? 'active' : '' ?>">
        <section class="grid lg:grid-cols-[1fr_0.72fr] gap-8 items-start">
            <div>
                <span class="bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 font-bold tracking-[0.15em] text-xs uppercase px-3 py-1 rounded-full shadow-sm">
                    Telemetría en tiempo real
                </span>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mt-3 mb-2 text-slate-900 dark:text-white">
                    Monitoreo de Hardware & Sensores
                </h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed max-w-2xl">
                    Estado en vivo, diagnóstico de componentes físicos y control de actuadores IoT.
                </p>
            </div>

            <aside class="hw-panel rounded-2xl p-5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <?php if ($selectedDevice): ?>
                    <form method="GET" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Dispositivo Activo</label>
                            <button type="button" onclick="switchTab('new')" class="text-xs font-bold text-emerald-600 hover:text-emerald-500 flex items-center gap-1">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Nuevo
                            </button>
                        </div>
                        <select name="device" class="input-glass w-full rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" onchange="this.form.submit()">
                            <?php foreach ($devices as $device): ?>
                                <option value="<?= (int) $device['id'] ?>" <?= (int) $device['id'] === (int) $selectedDevice['id'] ? 'selected' : '' ?>>
                                    <?= h($device['nombre'] . ' (' . $device['planta_nombre'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Código:</span>
                            <strong class="text-slate-900 dark:text-white font-mono"><?= h($selectedDevice['codigo']) ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Planta vinculada:</span>
                            <strong class="text-slate-900 dark:text-white font-semibold"><?= h($selectedDevice['planta_nombre']) ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Última lectura:</span>
                            <strong class="text-slate-900 dark:text-white font-semibold"><?= $selectedDevice['ultima_conexion'] ? h(date('d/m H:i', strtotime($selectedDevice['ultima_conexion']))) : 'Sin datos' ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i data-lucide="cpu" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No tenés ningún hardware vinculado</p>
                        <p class="text-[11px] text-slate-500 mt-1 mb-3">Registrá tu primer sensor para comenzar a recibir telemetría.</p>
                        <button onclick="switchTab('new')" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-2 rounded-xl text-xs inline-flex items-center gap-1.5 shadow-sm transition-all">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> Dar de alta sensor
                        </button>
                    </div>
                <?php endif; ?>
            </aside>
        </section>

        <?php if ($selectedDevice): ?>
        <!-- Tarjetas Principales de Lectura -->
        <section class="grid sm:grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="hw-panel rounded-2xl p-5 hw-reading border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Componentes online</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white"><span id="connectedCount"><?= $connectedCount ?></span>/<span id="totalCount"><?= count($components) ?></span></p>
                </div>
                <p class="text-[11px] text-slate-500 font-medium">Sensores físicos sincronizados.</p>
            </div>

            <!-- Tarjeta Humedad Suelo (P32) -->
            <div class="hw-panel card-soil rounded-2xl p-5 hw-reading flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-black uppercase tracking-wider card-title-soil">Humedad Suelo</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] badge-live-soil shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> EN VIVO
                        </span>
                    </div>
                    <p class="text-3xl font-black val-soil"><span id="readingSoil"><?= $latest ? number_format((float) $latest['humedad_suelo'], 1) : '-' ?></span><span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 ml-0.5">%</span></p>
                </div>
                <div>
                    <div class="w-full bg-emerald-200 dark:bg-white/10 rounded-full h-2 overflow-hidden">
                        <div id="soilProgressBar" class="bg-emerald-600 h-full transition-all duration-500 rounded-full" style="width: <?= $latest ? max(5, min(100, (float) $latest['humedad_suelo'])) : 0 ?>%"></div>
                    </div>
                    <p class="text-[11px] font-bold mt-1.5 flex justify-between items-center">
                        <span class="sub-soil">Ideal: 35% - 65%</span> 
                        <span id="soilStatusLabel" class="font-bold text-emerald-700 dark:text-emerald-300">Normal</span>
                    </p>
                </div>
            </div>

            <!-- Tarjeta Sensor BH-1750 (I2C) -->
            <div class="hw-panel card-light rounded-2xl p-5 hw-reading flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-black uppercase tracking-wider card-title-light">Luz BH-1750</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] badge-live-light shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> EN VIVO
                        </span>
                    </div>
                    <p class="text-3xl font-black val-light"><span id="readingLightTop"><?= $latest ? (int) $latest['luz_ambiental'] : '-' ?></span><span class="text-lg font-bold text-amber-600 dark:text-amber-400 ml-0.5">lx</span></p>
                </div>
                <div>
                    <div class="w-full bg-amber-200 dark:bg-white/10 rounded-full h-2 overflow-hidden">
                        <div id="lightProgressBar" class="bg-amber-500 h-full transition-all duration-500 rounded-full" style="width: <?= $latest ? max(5, min(100, ((float) $latest['luz_ambiental'] / 3000.0) * 100)) : 0 ?>%"></div>
                    </div>
                    <p class="text-[11px] font-bold mt-1.5 flex justify-between items-center">
                        <span class="sub-light">Bus I2C (P21/P22)</span> 
                        <span id="lightStatusLabel" class="font-bold text-amber-700 dark:text-amber-300">Luz Óptima</span>
                    </p>
                </div>
            </div>

            <div class="hw-panel rounded-2xl p-5 hw-reading border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Estado de Salud</p>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400 leading-tight" id="analysisSummary"><?= h($analysis['resumen']) ?></p>
                </div>
                <p class="text-[11px] text-slate-400 font-medium">Diagnóstico automatizado.</p>
            </div>
        </section>

        <!-- Detección de Componentes y Lecturas Secundarias -->
        <section class="grid lg:grid-cols-[1.1fr_0.9fr] gap-6 mt-6 items-start">
            <div class="hw-panel rounded-2xl p-6 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Sensores & Módulos</h2>
                        <p class="text-slate-500 text-xs mt-0.5">Componentes físicos vinculados a este hardware.</p>
                    </div>
                    <button type="button" onclick="refreshHardware()" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refrescar
                    </button>
                </div>

                <div class="grid sm:grid-cols-2 gap-3.5" id="componentsGrid">
                    <?php foreach ($components as $component): ?>
                    <article class="hw-panel rounded-xl p-3.5 border border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-800/30" data-component="<?= h($component['codigo_componente']) ?>">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <p class="font-bold text-xs text-slate-900 dark:text-white leading-tight" data-field="name"><?= h($component['nombre']) ?></p>
                                <p class="text-[10px] text-slate-400 mt-0.5"><?= h($component['categoria']) ?> · <?= h($component['pin']) ?></p>
                            </div>
                            <span class="hw-status-pill <?= statusClass($component['estado_visual']) ?>" data-field="statusWrap">
                                <span class="hw-dot"></span>
                                <span data-field="statusText"><?= h($component['estado_texto']) ?></span>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs pt-1.5 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <p class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Valor</p>
                                <p class="font-extrabold text-slate-900 dark:text-white" data-field="value"><?= h(componentDisplayValue($component)) ?></p>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Lectura</p>
                                <p class="font-semibold text-slate-600 dark:text-slate-300 text-[11px]" data-field="lastSeen"><?= h($component['ultima_conexion_relativa']) ?></p>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="space-y-6">
                <!-- Control de Actuadores -->
                <div class="hw-panel rounded-2xl p-6 border border-emerald-500/30 dark:border-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="power" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i> Control de Actuadores
                        </h2>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">ESP32 Relé</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">Comandos manuales inmediatos sobre la bomba de agua y válvulas.</p>
                    
                    <div class="space-y-2.5">
                        <button type="button" onclick="sendDeviceCommand('TOGGLE_PUMP')" id="btnTogglePump" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-all flex items-center justify-center gap-2 active:scale-98">
                            <i data-lucide="droplets" class="w-4 h-4"></i> Alternar Riego Manual
                        </button>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="sendDeviceCommand('WATER_PUMP_ON')" class="py-2 px-3 rounded-lg text-xs font-semibold bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white transition-all active:scale-95 flex items-center justify-center gap-1.5">
                                <i data-lucide="play" class="w-3.5 h-3.5 text-emerald-400"></i> Encender
                            </button>
                            <button type="button" onclick="sendDeviceCommand('WATER_PUMP_OFF')" class="py-2 px-3 rounded-lg text-xs font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 transition-all active:scale-95 flex items-center justify-center gap-1.5">
                                <i data-lucide="square" class="w-3.5 h-3.5 text-red-500"></i> Detener
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Diagnóstico y Alertas -->
                <div class="hw-panel rounded-2xl p-6 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i> Diagnóstico de Hardware
                    </h2>
                    <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300" id="analysisList">
                        <?php foreach ($analysis['alertas'] as $alert): ?>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 mt-0.5 text-emerald-500 flex-shrink-0"></i>
                            <span><?= h($alert) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </section>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TAB 2: FORMULARIO DE ALTA DE DISPOSITIVO / SENSOR
         ═══════════════════════════════════════════════════════════════ -->
    <div id="tabContentNew" class="tab-content <?= $initialTab === 'new' ? 'active' : '' ?>">
        <section class="grid lg:grid-cols-[0.9fr_1.1fr] gap-8 items-start">
            <div>
                <span class="bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 font-bold tracking-[0.15em] text-xs uppercase px-3 py-1 rounded-full shadow-sm">
                    Registro de nuevo equipo
                </span>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mt-3 mb-2 text-slate-900 dark:text-white">
                    Vincular Dispositivo o Sensor.
                </h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed max-w-lg mb-6">
                    Asigna un nombre, tipo de hardware y la planta a la que pertenecerá para que empiece a reportar datos inmediatamente.
                </p>

                <div class="space-y-3.5 bg-slate-50 dark:bg-slate-900/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs flex-shrink-0">1</div>
                        <p>Completa los datos del formulario y presiona <strong>Dar de alta</strong>.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs flex-shrink-0">2</div>
                        <p>El sistema generará automáticamente un <strong>Código de dispositivo</strong> y una <strong>API Key</strong> segura.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs flex-shrink-0">3</div>
                        <p>Copia el código en el firmware de tu placa ESP32 o usa la pestaña <strong>Monitoreo</strong> para ver la telemetría.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-7 shadow-sm">
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="create_device">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nombre del Dispositivo *</label>
                        <input type="text" name="nombre" placeholder="Ej: Sensor Balcón Este, Hub Invernadero..." required class="input-glass w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-emerald-500 outline-none">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Tipo de Hardware *</label>
                            <select name="tipo_dispositivo" required class="input-glass w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-emerald-500 outline-none">
                                <option value="Sensor IoT (Humedad + Luz)">Sensor IoT (Humedad + Luz)</option>
                                <option value="Controlador de Riego Pro">Controlador de Riego Pro</option>
                                <option value="Estación Meteorológica Solar">Estación Meteorológica Solar</option>
                                <option value="Smart Hub Central">Smart Hub Central</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Planta Asignada *</label>
                            <select name="planta_id" required class="input-glass w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-emerald-500 outline-none">
                                <?php if (empty($plantas)): ?>
                                    <option value="" disabled selected>No tienes plantas registradas</option>
                                <?php else: ?>
                                    <?php foreach ($plantas as $planta): ?>
                                        <option value="<?= (int) $planta['id'] ?>">
                                            <?= h($planta['nombre'] . ' (' . $planta['especie'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Ubicación física</label>
                            <input type="text" name="ubicacion" placeholder="Ej: Balcón, Living, Terraza..." class="input-glass w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-emerald-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Código de Dispositivo (opcional)</label>
                            <input type="text" name="codigo" placeholder="Dejar vacío para auto-generar" class="input-glass w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3.5 px-6 rounded-2xl font-bold text-sm bg-emerald-600 hover:bg-emerald-500 text-white shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Dar de Alta Dispositivo
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

</main>

<!-- Toast de Notificaciones -->
<div id="hwToast" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-4 py-2.5 rounded-full text-xs font-bold shadow-xl opacity-0 pointer-events-none transition-all z-[10000]"></div>

<script>
    const deviceId = <?= $selectedDevice ? (int) $selectedDevice['id'] : 0 ?>;
    const statusUrl = deviceId ? `/hardware/api?action=status&device_id=${deviceId}` : null;

    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        if (tabName === 'telemetry') {
            document.getElementById('btnTabTelemetry').classList.add('active');
            document.getElementById('tabContentTelemetry').classList.add('active');
        } else {
            document.getElementById('btnTabNew').classList.add('active');
            document.getElementById('tabContentNew').classList.add('active');
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showToast(msg) {
        const toast = document.getElementById('hwToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.remove('opacity-0', 'pointer-events-none');
        setTimeout(() => toast.classList.add('opacity-0', 'pointer-events-none'), 2800);
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el && val !== undefined && val !== null) el.textContent = val;
    }

    function statusClass(state) {
        if (state === 'conectado') return 'hw-status-on';
        if (state === 'advertencia') return 'hw-status-warn';
        return 'hw-status-off';
    }

    function formatComponentValue(c) {
        const val = (c.valor !== null && c.valor !== undefined) ? String(c.valor).trim() : '';
        if (!val) return '-';
        const unit = (c.unidad || '').trim();
        if (!unit || /[A-Za-z%]/.test(val)) return val;
        return `${val} ${unit}`;
    }

    function updateFromPayload(data) {
        if (!data || !data.success) return;

        const components = data.components || [];
        let connected = 0;

        components.forEach(component => {
            if (component.online && component.estado_visual !== 'desconectado') connected++;

            const card = document.querySelector(`[data-component="${component.codigo_componente}"]`);
            if (!card) return;

            const wrap = card.querySelector('[data-field="statusWrap"]');
            if (wrap) {
                wrap.classList.remove('hw-status-on', 'hw-status-warn', 'hw-status-off');
                wrap.classList.add(statusClass(component.estado_visual));
            }
            const txt = card.querySelector('[data-field="statusText"]');
            if (txt) txt.textContent = component.estado_texto;
            const val = card.querySelector('[data-field="value"]');
            if (val) val.textContent = formatComponentValue(component);
            const seen = card.querySelector('[data-field="lastSeen"]');
            if (seen) seen.textContent = component.ultima_conexion_relativa || 'Sin lecturas';
        });

        setText('connectedCount', connected);
        setText('totalCount', components.length);

        const reading = data.reading;
        if (reading) {
            const soilVal = Number(reading.humedad_suelo);
            setText('readingSoil', soilVal.toFixed(1));
            
            const bar = document.getElementById('soilProgressBar');
            if (bar) bar.style.width = `${Math.max(5, Math.min(100, soilVal))}%`;

            const label = document.getElementById('soilStatusLabel');
            if (label) {
                if (soilVal < 35) {
                    label.textContent = 'Seco (Bajo)';
                    label.className = 'font-bold text-amber-700 dark:text-amber-300';
                } else if (soilVal > 70) {
                    label.textContent = 'Muy Húmedo';
                    label.className = 'font-bold text-blue-700 dark:text-blue-300';
                } else {
                    label.textContent = 'Normal';
                    label.className = 'font-bold text-emerald-700 dark:text-emerald-300';
                }
            }

            setText('readingLightTop', reading.luz_ambiental);

            const lightBar = document.getElementById('lightProgressBar');
            if (lightBar) {
                const luxVal = Number(reading.luz_ambiental) || 0;
                lightBar.style.width = `${Math.max(5, Math.min(100, (luxVal / 3000) * 100))}%`;
            }

            const lightLabel = document.getElementById('lightStatusLabel');
            if (lightLabel) {
                const luxVal = Number(reading.luz_ambiental) || 0;
                if (luxVal < 100) {
                    lightLabel.textContent = 'Oscuro / Sombra';
                    lightLabel.className = 'font-bold text-gray-700 dark:text-gray-300';
                } else if (luxVal > 2000) {
                    lightLabel.textContent = 'Luz Alta';
                    lightLabel.className = 'font-bold text-yellow-700 dark:text-yellow-300';
                } else {
                    lightLabel.textContent = 'Luz Óptima';
                    lightLabel.className = 'font-bold text-amber-700 dark:text-amber-300';
                }
            }
        }

        if (data.analysis) {
            setText('analysisSummary', data.analysis.resumen);
            const list = document.getElementById('analysisList');
            if (list) {
                list.innerHTML = '';
                (data.analysis.alertas || []).forEach(alert => {
                    const li = document.createElement('li');
                    li.className = 'flex items-start gap-2.5';
                    li.innerHTML = '<i data-lucide="check-circle-2" class="w-4 h-4 mt-0.5 text-emerald-500 flex-shrink-0"></i><span></span>';
                    li.querySelector('span').textContent = alert;
                    list.appendChild(li);
                });
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }
    }

    async function refreshHardware() {
        if (!statusUrl) return;
        try {
            const res = await fetch(statusUrl);
            const data = await res.json();
            updateFromPayload(data);
        } catch (error) {
            // Silencioso en fondo
        }
    }

    async function sendDeviceCommand(comando, parametros = null) {
        if (!deviceId) {
            showToast('No hay dispositivo seleccionado.');
            return;
        }
        try {
            const res = await fetch('/hardware/api?action=control', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ device_id: deviceId, comando: comando, parametros: parametros })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.mensaje || 'Comando ejecutado con éxito.');
                refreshHardware();
            } else {
                showToast(data.error || 'Error al enviar comando.');
            }
        } catch (err) {
            showToast('Error de conexión al enviar el comando.');
        }
    }

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

        if (btn) {
            btn.innerHTML = next === 'dark' 
                ? '<i data-lucide="sun" class="w-4 h-4 text-amber-400"></i>' 
                : '<i data-lucide="moon" class="w-4 h-4 text-slate-700"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sp_theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(localStorage.getItem('sp_theme') || 'dark');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        if (statusUrl) {
            refreshHardware();
            setInterval(refreshHardware, 3000);
        }
    });
</script>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

</body>
</html>
