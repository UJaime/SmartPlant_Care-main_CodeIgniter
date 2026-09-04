<?php
service('session');
$logged = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartPlant CARE — El futuro en tu jardín</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-overlay text-white min-h-screen">

<header class="sticky top-6 z-50 mx-auto max-w-5xl px-4">
    <div class="glass-clean flex items-center justify-between px-8 py-3.5 rounded-full shadow-sm">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <nav class="hidden md:flex gap-7 items-center text-sm font-medium text-slate-700">
            <a href="/" data-i18n="nav_inicio" class="hover:text-emerald-600 transition-colors">Inicio</a>
            <a href="#utilidades" data-i18n="nav_utilidades" class="hover:text-emerald-600 transition-colors">Utilidades</a>
            <a href="#asistente-ia" data-i18n="nav_ia" class="hover:text-emerald-600 transition-colors">IA</a>
            <a href="#producto" data-i18n="nav_producto" class="hover:text-emerald-600 transition-colors">Producto</a>
            <a href="/store" data-i18n="nav_tienda" class="hover:text-emerald-600 transition-colors">Tienda</a>
            
            <div class="flex items-center gap-1.5 bg-slate-100/80 px-2.5 py-1 rounded-full border border-slate-200/80 text-xs">
                <i data-lucide="globe" class="w-3.5 h-3.5 text-slate-500"></i>
                <select id="langSelector" class="bg-transparent text-slate-800 text-xs outline-none cursor-pointer font-medium">
                    <option value="es" class="text-slate-900">ES</option>
                    <option value="en" class="text-slate-900">EN</option>
                    <option value="pt" class="text-slate-900">PT</option>
                    <option value="fr" class="text-slate-900">FR</option>
                    <option value="de" class="text-slate-900">DE</option>
                    <option value="ru" class="text-slate-900">RU</option>
                    <option value="zh" class="text-slate-900">ZH</option>
                </select>
            </div>

            <?php if ($logged): ?>
                <a href="/dashboard" data-i18n="nav_dashboard" class="btn-emerald px-5 py-2 rounded-full font-semibold hover:scale-105 transition-all text-xs">
                    Mi Dashboard
                </a>
            <?php else: ?>
                <a href="/login" data-i18n="nav_cuenta" class="btn-emerald px-5 py-2 rounded-full font-semibold hover:scale-105 transition-all text-xs">
                    Mi cuenta
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="flex flex-col items-center justify-center text-center pt-28 pb-20 px-6">
    <span data-i18n="hero_tag" class="reveal-blur bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-bold tracking-[0.15em] text-xs uppercase mb-6 px-4 py-1.5 rounded-full shadow-sm">Innovación Sustentable</span>

    <h2 class="reveal-blur text-5xl md:text-7xl font-bold tracking-tight mb-6 leading-[1.1] text-slate-900">
        <span data-i18n="hero_title_1">Inteligente por</span> <br><span data-i18n="hero_title_2" class="text-gradient-anim">naturaleza.</span>
    </h2>

    <p data-i18n="hero_desc" class="reveal text-lg md:text-xl text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed">
        Tecnología IoT y diseño de vanguardia para llevar un control total de tus plantas desde cualquier parte del mundo.
    </p>

    <div class="reveal mt-10 flex flex-col sm:flex-row gap-4">
        <a href="/store" data-i18n="hero_btn_1" class="btn-emerald px-8 py-3.5 rounded-full text-base font-semibold hover:scale-105 transition-all shadow-md">
            Comprar ahora
        </a>
        <a href="#producto" data-i18n="hero_btn_2" class="btn-emerald-outline px-8 py-3.5 rounded-full text-base font-semibold transition">
            Más información →
        </a>
    </div>
</section>



<section id="producto" class="max-w-7xl mx-auto px-6 py-32">
    <div class="text-center mb-24 reveal-blur">
        <span data-i18n="prod_tag" class="text-white/50 font-semibold tracking-[0.2em] text-xs uppercase">Nuestro producto</span>
        <h2 class="text-5xl md:text-7xl font-semibold tracking-tight mt-4">
            <span data-i18n="prod_title_1">Conocé</span> <span class="text-gradient-anim">SmartPlant.</span>
        </h2>
    </div>

    <div class="grid md:grid-cols-2 gap-16 items-center">
        <div class="reveal-left flex justify-center">
            <div class="product-glow relative">
                <div class="orbit-ring" style="width:350px;height:350px;top:50%;left:50%;margin-top:-175px;margin-left:-175px;"></div>
                <div class="product-image-container float-product">
                    <div class="w-[300px] h-[300px] md:w-[380px] md:h-[380px] rounded-[3rem] border border-white/10 overflow-hidden relative">
                        <img src="/assets/product-lifestyle.png" alt="SmartPlant" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>

        <div class="reveal-right space-y-8">
            <div>
                <div class="line-accent mb-6"></div>
                <h3 data-i18n="prod_desc_title" class="text-4xl md:text-5xl font-semibold tracking-tight leading-tight">Diseñado para <br>vivir al aire libre.</h3>
            </div>
            <p data-i18n="prod_desc" class="text-gray-400 text-lg font-light leading-relaxed max-w-md">
                Un dispositivo compacto, resistente al agua y alimentado por energía solar. Monitorea tus plantas 24/7 y riega automáticamente.
            </p>
            <a href="/store" data-i18n="prod_btn" class="inline-block px-8 py-4 rounded-full bg-white text-black font-semibold text-lg hover:opacity-90 transition-all">
                Comprar SmartPlant →
            </a>
        </div>
    </div>
</section>

<section id="utilidades" class="max-w-6xl mx-auto px-6 py-32">
    <div class="reveal text-center mb-20">
        <span class="text-white/50 font-semibold tracking-[0.2em] text-xs uppercase">Funcionalidades</span>
        <h2 class="text-4xl md:text-6xl font-semibold tracking-tight mt-4">Todo lo que necesitás.</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-6 stagger-children">
        <div class="card-glass rounded-[3rem] p-12">
            <i data-lucide="globe" class="w-10 h-10 mb-6 text-white/80"></i>
            <h3 class="text-3xl font-semibold mb-4">Monitoreo global.</h3>
            <p class="text-gray-400 font-light">Humedad, luz y temperatura bajo control total desde tu dispositivo, estés donde estés.</p>
        </div>
        <div class="card-glass rounded-[3rem] p-12">
            <i data-lucide="droplets" class="w-10 h-10 mb-6 text-cyan-400"></i>
            <h3 class="text-3xl font-semibold mb-4">Riego autónomo.</h3>
            <p class="text-gray-400 font-light">El sistema decide el momento exacto de riego según los datos del sensor. Sin intervención.</p>
        </div>
        <div class="card-glass rounded-[3rem] p-12">
            <i data-lucide="sun" class="w-10 h-10 mb-6 text-yellow-400"></i>
            <h3 class="text-3xl font-semibold mb-4">Energía Solar.</h3>
            <p class="text-gray-400 font-light">Panel integrado de alta eficiencia para un funcionamiento continuo 24/7.</p>
        </div>
        <div class="card-glass rounded-[3rem] p-12">
            <i data-lucide="bar-chart-2" class="w-10 h-10 mb-6 text-green-400"></i>
            <h3 class="text-3xl font-semibold mb-4">Dashboard intuitivo.</h3>
            <p class="text-gray-400 font-light">Visualizá la salud de tu jardín con estadísticas precisas y una interfaz impecable.</p>
        </div>
    </div>
</section>

<section id="asistente-ia" class="py-32 border-y border-white/5 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="text-center mb-20 reveal-blur">
            <span class="text-white/50 font-semibold tracking-[0.2em] text-xs uppercase">Inteligencia artificial</span>
            <h2 class="text-4xl md:text-6xl font-semibold tracking-tight mt-4">Tu experta en <span class="text-gradient-anim">plantas.</span></h2>
            <p class="text-gray-400 text-lg font-light mt-6 max-w-2xl mx-auto">
                Integrada directamente, nuestra IA analiza los datos de tus sensores en tiempo real.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div class="reveal-left space-y-8">
                <div class="flex items-start gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-lg"><i data-lucide="brain" class="text-purple-400"></i></div>
                    <div>
                        <p class="text-white font-medium">Diagnósticos en tiempo real</p>
                        <p class="text-gray-500 text-sm font-light">Analiza humedad, temperatura y luz.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-lg"><i data-lucide="camera" class="text-blue-400"></i></div>
                    <div>
                        <p class="text-white font-medium">Identificación por foto</p>
                        <p class="text-gray-500 text-sm font-light">Sube una foto y la IA te ayuda a identificarla.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-lg"><i data-lucide="message-square" class="text-green-400"></i></div>
                    <div>
                        <p class="text-white font-medium">Chat conversacional</p>
                        <p class="text-gray-500 text-sm font-light">Preguntale como a un experto botánico.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

<footer class="py-20 text-center text-gray-500 text-sm border-t border-white/10 glass-clean rounded-t-[3rem] mt-20">
    <div class="max-w-4xl mx-auto px-6">
        <p class="mb-4 flex items-center justify-center gap-2"><i data-lucide="leaf" class="w-4 h-4 text-emerald-500"></i> SmartPlant CARE</p>
        <p class="mt-10 opacity-50">© 2026 Todos los derechos reservados.</p>
    </div>
</footer>

<script>
    // Inicializar Iconos Profesionales (Lucide)
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ═══════════════════════════════════════════════════════════════
    // MULTI-IDIOMA (I18N)
    // ═══════════════════════════════════════════════════════════════
    const translations = {
        es: {
            nav_inicio: "Inicio", nav_utilidades: "Utilidades", nav_ia: "IA", nav_producto: "Producto", nav_tienda: "Tienda", nav_dashboard: "Mi Dashboard", nav_cuenta: "Mi cuenta",
            hero_tag: "Innovación Sustentable", hero_title_1: "Inteligente por", hero_title_2: "naturaleza.", hero_desc: "Tecnología IoT y diseño de vanguardia para llevar un control total de tus plantas.", hero_btn_1: "Comprar ahora", hero_btn_2: "Más información →"
        },
        en: {
            nav_inicio: "Home", nav_utilidades: "Utilities", nav_ia: "AI", nav_producto: "Product", nav_tienda: "Store", nav_dashboard: "My Dashboard", nav_cuenta: "My Account",
            hero_tag: "Sustainable Innovation", hero_title_1: "Smart by", hero_title_2: "nature.", hero_desc: "IoT technology and cutting-edge design to take full control of your plants.", hero_btn_1: "Buy now", hero_btn_2: "Learn more →"
        },
        pt: {
            nav_inicio: "Início", nav_utilidades: "Utilidades", nav_ia: "IA", nav_producto: "Produto", nav_tienda: "Loja", nav_dashboard: "Meu Dashboard", nav_cuenta: "Minha Conta",
            hero_tag: "Inovação Sustentável", hero_title_1: "Inteligente por", hero_title_2: "natureza.", hero_desc: "Tecnologia IoT e design de vanguarda para controle total de suas plantas.", hero_btn_1: "Compre agora", hero_btn_2: "Saiba mais →"
        },
        fr: {
            nav_inicio: "Accueil", nav_utilidades: "Utilitaires", nav_ia: "IA", nav_producto: "Produit", nav_tienda: "Boutique", nav_dashboard: "Mon Tableau de bord", nav_cuenta: "Mon Compte",
            hero_tag: "Innovation Durable", hero_title_1: "Intelligent par", hero_title_2: "nature.", hero_desc: "Technologie IoT et design de pointe pour un contrôle total de vos plantes.", hero_btn_1: "Acheter", hero_btn_2: "En savoir plus →"
        },
        de: {
            nav_inicio: "Startseite", nav_utilidades: "Werkzeuge", nav_ia: "KI", nav_producto: "Produkt", nav_tienda: "Geschäft", nav_dashboard: "Mein Dashboard", nav_cuenta: "Mein Konto",
            hero_tag: "Nachhaltige Innovation", hero_title_1: "Smart von", hero_title_2: "Natur aus.", hero_desc: "IoT-Technologie für die vollständige Kontrolle über Ihre Pflanzen.", hero_btn_1: "Jetzt kaufen", hero_btn_2: "Mehr erfahren →"
        },
        ru: {
            nav_inicio: "Главная", nav_utilidades: "Утилиты", nav_ia: "ИИ", nav_producto: "Продукт", nav_tienda: "Магазин", nav_dashboard: "Моя панель", nav_cuenta: "Мой аккаунт",
            hero_tag: "Устойчивые инновации", hero_title_1: "Умный от", hero_title_2: "природы.", hero_desc: "Технологии IoT и передовой дизайн для полного контроля над вашими растениями.", hero_btn_1: "Купить сейчас", hero_btn_2: "Узнать больше →"
        },
        zh: {
            nav_inicio: "首页", nav_utilidades: "实用工具", nav_ia: "人工智能", nav_producto: "产品", nav_tienda: "商店", nav_dashboard: "我的仪表板", nav_cuenta: "我的账户",
            hero_tag: "可持续创新", hero_title_1: "生而", hero_title_2: "智能。", hero_desc: "物联网技术和前沿设计，全面控制您的植物。", hero_btn_1: "立即购买", hero_btn_2: "了解更多 →"
        }
    };

    const langSelector = document.getElementById('langSelector');
    if (langSelector) {
        langSelector.addEventListener('change', (e) => {
            const lang = e.target.value;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if(translations[lang] && translations[lang][key]) el.innerHTML = translations[lang][key];
            });
        });
    }

    // Intersection Observers (Reveal general)
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('active');
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-blur, .stagger-children').forEach(el => obs.observe(el));

</script>
</body>
</html>