<?php
service('session');
$logged = isset($_SESSION['usuario_id']);
$paypalClientId = env('PAYPAL_CLIENT_ID') ?: 'AdS73P8zK4qdxTj28HQY2mwpHH56B1462yudL4DkwlMVAO4JYZyVVELvoiU6iC7jNU-XL5X1tcVajHb6';
$paypalCurrency = env('PAYPAL_CURRENCY') ?: 'USD';
$paypalArsToUsdRate = (float) (env('PAYPAL_ARS_TO_USD_RATE') ?: 1000);

$products = [
    [
        'id'       => 'aurea-one',
        'name'     => 'Aurea One',
        'tag'      => 'Más vendido',
        'tagColor' => 'green',
        'shortDesc'=> 'Sensor inteligente con panel solar integrado. Monitorea humedad, temperatura y luz en tiempo real.',
        'longDesc' => 'El Aurea One es un dispositivo compacto y resistente al agua (IP67) diseñado para vivir al aire libre. Equipado con panel solar, conectividad WiFi + Bluetooth y 5 sensores de alta precisión, te permite monitorear tus plantas 24/7 desde cualquier lugar del mundo. Su setup toma menos de 5 minutos y no requiere herramientas.',
        'price'    => 54990,
        'oldPrice' => 74990,
        'image'    => '/assets/product-device.png',
        'features' => ['Panel solar integrado', '5 sensores', 'WiFi + Bluetooth', 'IP67'],
        'specs'    => [
            'Autonomía'     => '120 hs sin sol',
            'Conectividad'  => 'WiFi 2.4GHz + BLE 5.0',
            'Sensores'      => 'Humedad, Temp, Luz, pH',
            'Protección'    => 'IP67 — Sumergible',
            'Peso'          => '148 g',
            'Dimensiones'   => '4.2 × 4.2 × 12 cm',
        ],
        'colors'   => [
            ['name' => 'Verde Bosque', 'hex' => '#3a5a40', 'image' => '/assets/product-device.png'],
            ['name' => 'Negro Mate',   'hex' => '#1a1a1a', 'image' => '/assets/product-device-black.png'],
            ['name' => 'Blanco Perla', 'hex' => '#e8e4de', 'image' => '/assets/product-device-white.png'],
        ],
        'gallery'  => [
            '/assets/product-device.png',
            '/assets/product-lifestyle.png',
            '/assets/product-device-black.png',
            '/assets/product-device-white.png',
        ],
    ],
    [
        'id'       => 'smartplant-kit',
        'name'     => 'SmartPlant Kit Pro',
        'tag'      => 'Kit completo',
        'tagColor' => 'cyan',
        'shortDesc'=> 'Todo lo que necesitás para automatizar tu jardín: controlador, panel solar, sensores y bomba de riego.',
        'longDesc' => 'El Kit Pro incluye todo lo necesario para convertir cualquier jardín en un sistema inteligente: controlador central con ESP32, panel solar de alta eficiencia, 2 sensores de suelo capacitivos, bomba de riego sumergible y toda la tubería. Conectá, configurá la app y olvidate del riego manual para siempre.',
        'price'    => 149990,
        'oldPrice' => 220000,
        'image'    => '/assets/product-kit.png',
        'features' => ['Controlador + Panel solar', 'Bomba de riego incluida', '2 sensores de suelo', 'Setup en 5 min'],
        'specs'    => [
            'Controlador'  => 'ESP32 Dual-Core 240MHz',
            'Panel Solar'  => '5V 2W Alta Eficiencia',
            'Bomba'        => '5V USB Sumergible 1.5L/min',
            'Sensores'     => '2× Capacitivo de suelo',
            'Alcance WiFi' => 'Hasta 100m',
            'Contenido'    => '6 piezas + manual',
        ],
        'colors'   => [
            ['name' => 'Negro Standard', 'hex' => '#1a1a1a', 'image' => '/assets/product-kit.png'],
        ],
        'gallery'  => [
            '/assets/product-kit.png',
            '/assets/product-solar.png',
            '/assets/product-lifestyle.png',
        ],
    ],
    [
        'id'       => 'smartplant-solar',
        'name'     => 'Panel Solar',
        'tag'      => 'Accesorio',
        'tagColor' => 'orange',
        'shortDesc'=> 'Panel solar de alta eficiencia para extender la autonomía de tu SmartPlant. Conexión USB-C.',
        'longDesc' => 'Ampliá la autonomía de tu SmartPlant con este panel solar adicional. Diseñado para exteriores con protección IP67, soporte ajustable de 0° a 45° y conexión USB-C universal. Compatible con todos los dispositivos SmartPlant. Instalación sin herramientas en menos de 2 minutos.',
        'price'    => 24990,
        'oldPrice' => null,
        'image'    => '/assets/product-solar.png',
        'features' => ['Alta eficiencia', 'USB-C', 'Montaje ajustable', 'Compatible IP67'],
        'specs'    => [
            'Potencia'     => '5V 2W',
            'Conexión'     => 'USB-C Universal',
            'Protección'   => 'IP67',
            'Ángulo'       => 'Ajustable 0°–45°',
            'Peso'         => '95 g',
            'Cable'        => '1.5m trenzado',
        ],
        'colors'   => [
            ['name' => 'Negro Mate', 'hex' => '#1a1a1a', 'image' => '/assets/product-solar.png'],
        ],
        'gallery'  => [
            '/assets/product-solar.png',
            '/assets/product-kit.png',
        ],
    ],
];

function formatPrice($price) {
    return '$' . number_format($price, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda — SmartPlant CARE</title>
    <meta name="description" content="Comprá tu SmartPlant CARE: sensores inteligentes, kits completos y accesorios para el cuidado automatizado de tus plantas.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode($paypalClientId) ?>&currency=<?= urlencode($paypalCurrency) ?>"></script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>

<body class="bg-overlay text-white min-h-screen">
<script>
    document.documentElement.setAttribute('data-theme', localStorage.getItem('sp_theme') || 'light');
    document.documentElement.setAttribute('lang', localStorage.getItem('sp_lang') || 'es');
</script>

<header class="sticky top-6 z-50 mx-auto max-w-5xl px-4">
    <div class="glass-clean flex items-center justify-between px-8 py-4 rounded-[2.5rem]">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/logo.svg" alt="SmartPlant Logo" class="w-9 h-9 transition-transform group-hover:scale-105 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.35)]">
            <span class="text-2xl font-bold tracking-tight text-slate-900">SmartPlant <span class="text-emerald-600 font-black">CARE</span></span>
        </a>
        <nav class="hidden md:flex gap-8 items-center text-sm font-medium text-slate-700">
            <a href="/" data-i18n="nav_inicio" class="hover:text-emerald-600 transition-colors">Inicio</a>
            <a href="/#utilidades" data-i18n="nav_utilidades" class="hover:text-emerald-600 transition-colors">Utilidades</a>
            <a href="/#producto" data-i18n="nav_producto" class="hover:text-emerald-600 transition-colors">Producto</a>
            <a href="/store" data-i18n="nav_tienda" class="text-emerald-600 font-bold">Tienda</a>
            
            <button class="cart-header-btn relative" onclick="toggleCart()" aria-label="Carrito">
                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                <span class="cart-badge" id="cartBadge">0</span>
            </button>

            <button type="button" class="cart-header-btn" onclick="toggleTheme()" id="themeToggleBtn" aria-label="Cambiar tema" title="Cambiar tema">
                <i data-lucide="moon" class="w-5 h-5"></i>
            </button>

            <select id="langSelector" class="bg-transparent border border-white/20 text-white/80 text-xs rounded-md px-2 py-1 outline-none cursor-pointer">
                <option value="es" class="text-black">🇪🇸 ES</option>
                <option value="en" class="text-black">🇬🇧 EN</option>
                <option value="pt" class="text-black">🇧🇷 PT</option>
                <option value="fr" class="text-black">🇫🇷 FR</option>
                <option value="de" class="text-black">🇩🇪 DE</option>
                <option value="ru" class="text-black">🇷🇺 RU</option>
                <option value="zh" class="text-black">🇨🇳 ZH</option>
            </select>

            <?php if ($logged): ?>
                <a href="/dashboard" data-i18n="nav_dashboard" class="bg-white text-black px-6 py-2.5 rounded-full font-bold hover:scale-105 transition-all shadow-lg text-sm">Mi Dashboard</a>
            <?php else: ?>
                <a href="/login" data-i18n="nav_cuenta" class="bg-white text-black px-6 py-2.5 rounded-full font-bold hover:scale-105 transition-all shadow-lg text-sm">Mi cuenta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="flex flex-col items-center text-center pt-36 pb-16 px-6">
    <span data-i18n="store_hero_tag" class="reveal-blur text-white/50 font-semibold tracking-[0.2em] text-xs uppercase mb-6">Tienda Oficial</span>
    <h1 class="reveal-blur text-5xl md:text-7xl font-semibold tracking-tight mb-6 leading-[1.1]">
        <span data-i18n="store_hero_title">Equipá tu</span> <span data-i18n="store_hero_title_span" class="text-gradient-anim">jardín.</span>
    </h1>
    <p data-i18n="store_hero_desc" class="reveal text-lg md:text-xl text-gray-400 max-w-xl mx-auto font-light leading-relaxed">
        Tecnología de vanguardia para el cuidado autónomo de tus plantas. Envío a todo el país.
    </p>
</section>

<section class="max-w-7xl mx-auto px-6 pb-32">
    <div class="grid md:grid-cols-3 gap-8 stagger-children" id="productsGrid">

        <?php foreach ($products as $idx => $p): ?>
        <div class="store-card group" id="<?= $p['id'] ?>" data-product-id="<?= $p['id'] ?>">
            <div class="store-card-badge store-badge-<?= $p['tagColor'] ?>" data-product-field="tag"><?= $p['tag'] ?></div>

            <div class="store-card-image-wrap">
                <div class="store-card-glow store-glow-<?= $p['tagColor'] ?>"></div>
                <img src="<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     class="store-card-img group-hover:scale-110 transition-transform duration-700">
            </div>

            <div class="store-card-body">
                <h3 class="text-2xl font-semibold tracking-tight mb-2" data-product-field="name"><?= $p['name'] ?></h3>
                <p class="text-gray-400 text-sm font-light leading-relaxed mb-5" data-product-field="shortDesc"><?= $p['shortDesc'] ?></p>

                <div class="store-features" data-product-field="features">
                    <?php foreach ($p['features'] as $f): ?>
                    <span class="store-feature-tag"><i data-lucide="check" class="w-3 h-3 inline"></i> <?= $f ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-end gap-3 mb-6">
                    <span class="text-3xl font-bold text-gradient-green"><?= formatPrice($p['price']) ?></span>
                    <?php if ($p['oldPrice']): ?>
                    <span class="text-gray-500 line-through text-lg"><?= formatPrice($p['oldPrice']) ?></span>
                    <?php endif; ?>
                </div>

                <button class="store-btn-info btn-glow w-full" data-i18n="btn_more_info" onclick="openModal(<?= $idx ?>)">
                    Más información →
                </button>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<section class="py-16 border-y border-white/5">
    <div class="max-w-5xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center stagger-children" id="trustBar">
        <?php foreach ([
            ['truck', 'trust_1_title', 'trust_1_desc', 'Envío gratis', 'A todo el país'],
            ['shield-check', 'trust_2_title', 'trust_2_desc', 'Garantía por 2 años', 'Cobertura total'],
            ['zap', 'trust_3_title', 'trust_3_desc', 'Setup rápido e intuitivo', 'Sin herramientas'],
            ['refresh-cw', 'trust_4_title', 'trust_4_desc', '30 días', 'Devolución libre'],
        ] as [$icon, $keyTitle, $keyDesc, $defaultTitle, $defaultDesc]): ?>
        <div class="flex flex-col items-center gap-2">
            <span class="mb-2 p-3 bg-white/5 rounded-2xl border border-white/10 flex items-center justify-center"><i data-lucide="<?= $icon ?>" class="w-6 h-6 text-white/80"></i></span>
            <p class="text-white font-semibold text-sm" data-i18n="<?= $keyTitle ?>"><?= $defaultTitle ?></p>
            <p class="text-gray-500 text-xs font-light" data-i18n="<?= $keyDesc ?>"><?= $defaultDesc ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="py-20 text-center text-gray-500 text-sm border-t border-white/10 glass-clean rounded-t-[3rem] mt-20">
    <div class="max-w-4xl mx-auto px-6">
        <p class="mb-4 flex items-center justify-center gap-2"><i data-lucide="leaf" class="w-4 h-4"></i> SmartPlant CARE</p>
        <p class="font-light tracking-widest uppercase text-[10px]" data-i18n="footer_tag">Diseñado para el futuro</p>
        <p class="mt-10 opacity-50">© 2026 Todos los derechos reservados.</p>
    </div>
</footer>

<div class="modal-overlay" id="productModal">
    <div class="modal-container">
        <button class="modal-close flex items-center justify-center" onclick="closeModal()" aria-label="Cerrar">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="modal-grid">
            <div class="modal-gallery">
                <div class="modal-main-img-wrap">
                    <img id="modalMainImg" src="" alt="" class="modal-main-img">
                </div>
                <div class="modal-thumbs" id="modalThumbs"></div>
            </div>

            <div class="modal-info">
                <span class="modal-badge" id="modalBadge"></span>
                <h2 class="text-3xl md:text-4xl font-semibold tracking-tight mb-3" id="modalName"></h2>

                <div class="flex items-end gap-3 mb-6">
                    <span class="text-4xl font-bold text-gradient-green" id="modalPrice"></span>
                    <span class="text-gray-500 line-through text-xl" id="modalOldPrice"></span>
                </div>

                <p class="text-gray-400 font-light leading-relaxed mb-8" id="modalDesc"></p>

                <div id="modalColorsSection" class="mb-8">
                    <p class="text-sm font-semibold text-white/60 uppercase tracking-widest mb-3" data-i18n="prod_color">Color</p>
                    <div class="flex gap-3 items-center" id="modalColors"></div>
                    <p class="text-sm text-gray-500 mt-2" id="modalColorName"></p>
                </div>

                <div class="mb-8">
                    <p class="text-sm font-semibold text-white/60 uppercase tracking-widest mb-4" data-i18n="prod_specs">Especificaciones</p>
                    <div class="modal-specs-grid" id="modalSpecs"></div>
                </div>

                <div class="store-features mb-8" id="modalFeatures"></div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button class="store-btn btn-glow flex-1 flex items-center justify-center" onclick="buyNow()">
                        <span data-i18n="btn_buy_now">Comprar ahora</span>
                    </button>
                    <button class="store-btn-cart flex-1 flex items-center justify-center gap-2" onclick="addToCart()">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i> <span data-i18n="btn_add_cart">Agregar al carrito</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="cartToast" class="store-toast" role="alert">
    <i data-lucide="check-circle" class="w-5 h-5 text-white/70"></i>
    <span id="cartToastMsg" class="ml-2">Producto agregado al carrito</span>
</div>

<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<aside class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-header">
        <h2 class="text-xl font-semibold flex items-center gap-2">
            <i data-lucide="shopping-cart" class="w-5 h-5"></i> <span data-i18n="cart_title">Mi Carrito</span>
        </h2>
        <button class="modal-close flex items-center justify-center" onclick="toggleCart()" aria-label="Cerrar" style="position:static">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty" id="cartEmpty">
            <div class="flex justify-center mb-4 text-white/20">
                <i data-lucide="package-open" class="w-16 h-16"></i>
            </div>
            <p class="text-gray-400 font-light" data-i18n="cart_empty">Tu carrito está vacío</p>
            <p class="text-gray-600 text-sm mt-1" data-i18n="cart_empty_sub">Explorá la tienda y agregá productos</p>
        </div>
    </div>
    <div class="cart-footer" id="cartFooter" style="display:none">
        <div class="divider-glass mb-4"></div>
        <div class="flex justify-between items-center mb-6">
            <span class="text-gray-400 font-light" data-i18n="cart_subtotal">Subtotal</span>
            <span class="text-2xl font-bold text-gradient-green" id="cartSubtotal">$0</span>
        </div>
        <div class="flex flex-col gap-3">
            <button class="checkout-btn checkout-mp flex items-center justify-center gap-2" onclick="checkoutMercadoPago()">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                <span data-i18n="btn_pay_mp">Pagar con MercadoPago</span>
            </button>
            <div id="paypal-button-container" class="mt-2 w-full" style="position: relative; z-index: 1;"></div>
            <button class="checkout-btn checkout-pp hidden flex items-center justify-center gap-2" onclick="visualCheckout('PayPal')">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                <span data-i18n="btn_pay_pp">Pagar con PayPal</span>
            </button>
        </div>
        <p class="text-center text-gray-600 text-xs mt-4 font-light" data-i18n="cart_redirect_msg">Serás redirigido a la plataforma de pago segura</p>
    </div>
</aside>

<script>
    // Inicializar Iconos Profesionales (Lucide) en la primera carga
    lucide.createIcons();

    // ═══════════════════════════════════════════════════════════════
    // MULTI-IDIOMA (I18N)
    // ═══════════════════════════════════════════════════════════════
    const translations = {
        es: {
            nav_inicio: "Inicio", nav_utilidades: "Utilidades", nav_producto: "Producto", nav_tienda: "Tienda", nav_cuenta: "Mi cuenta", nav_dashboard: "Mi Dashboard",
            store_hero_tag: "Tienda Oficial", store_hero_title: "Equipá tu", store_hero_title_span: "jardín.", store_hero_desc: "Tecnología de vanguardia para el cuidado autónomo de tus plantas. Envío a todo el país.",
            trust_1_title: "Envío gratis", trust_1_desc: "A todo el país", trust_2_title: "Garantía por 2 años", trust_2_desc: "Cobertura total", trust_3_title: "Setup rápido e intuitivo", trust_3_desc: "Sin herramientas", trust_4_title: "30 días", trust_4_desc: "Devolución libre",
            btn_more_info: "Más información →", btn_buy_now: "Comprar ahora", btn_add_cart: "Agregar al carrito", prod_specs: "Especificaciones", prod_color: "Color", footer_tag: "Diseñado para el futuro",
            cart_title: "Mi Carrito", cart_empty: "Tu carrito está vacío", cart_empty_sub: "Explorá la tienda y agregá productos", cart_subtotal: "Subtotal", btn_pay_mp: "Pagar con MercadoPago", btn_pay_pp: "Pagar con PayPal", cart_redirect_msg: "Serás redirigido a la plataforma de pago segura"
        },
        en: {
            nav_inicio: "Home", nav_utilidades: "Utilities", nav_producto: "Product", nav_tienda: "Store", nav_cuenta: "My account", nav_dashboard: "My Dashboard",
            store_hero_tag: "Official Store", store_hero_title: "Equip your", store_hero_title_span: "garden.", store_hero_desc: "Cutting-edge technology for autonomous plant care. Nationwide shipping.",
            trust_1_title: "Free shipping", trust_1_desc: "Nationwide", trust_2_title: "2-year Warranty", trust_2_desc: "Full coverage", trust_3_title: "Quick Setup", trust_3_desc: "No tools required", trust_4_title: "30 Days", trust_4_desc: "Free returns",
            btn_more_info: "Learn more →", btn_buy_now: "Buy now", btn_add_cart: "Add to cart", prod_specs: "Specifications", prod_color: "Color", footer_tag: "Designed for the future",
            cart_title: "My Cart", cart_empty: "Your cart is empty", cart_empty_sub: "Explore the store and add products", cart_subtotal: "Subtotal", btn_pay_mp: "Pay with MercadoPago", btn_pay_pp: "Pay with PayPal", cart_redirect_msg: "You will be redirected to the secure payment platform"
        },
        pt: {
            nav_inicio: "Início", nav_utilidades: "Utilidades", nav_producto: "Produto", nav_tienda: "Loja", nav_cuenta: "Minha conta", nav_dashboard: "Meu Dashboard",
            store_hero_tag: "Loja Oficial", store_hero_title: "Equipe seu", store_hero_title_span: "jardim.", store_hero_desc: "Tecnologia de ponta para o cuidado autônomo das plantas. Envio para todo o país.",
            trust_1_title: "Frete grátis", trust_1_desc: "Para todo o país", trust_2_title: "Garantia de 2 anos", trust_2_desc: "Cobertura total", trust_3_title: "Setup rápido", trust_3_desc: "Sem ferramentas", trust_4_title: "30 dias", trust_4_desc: "Devolução livre",
            btn_more_info: "Mais informações →", btn_buy_now: "Compre agora", btn_add_cart: "Adicionar ao carrinho", prod_specs: "Especificações", prod_color: "Cor", footer_tag: "Projetado para o futuro",
            cart_title: "Meu Carrinho", cart_empty: "Seu carrinho está vazio", cart_empty_sub: "Explore a loja e adicione produtos", cart_subtotal: "Subtotal", btn_pay_mp: "Pagar com MercadoPago", btn_pay_pp: "Pagar com PayPal", cart_redirect_msg: "Você será redirecionado para a plataforma de pagamento segura"
        },
        fr: {
            nav_inicio: "Accueil", nav_utilidades: "Utilitaires", nav_producto: "Produit", nav_tienda: "Boutique", nav_cuenta: "Mon compte", nav_dashboard: "Mon Dashboard",
            store_hero_tag: "Boutique Officielle", store_hero_title: "Équipez votre", store_hero_title_span: "jardin.", store_hero_desc: "Technologie de pointe pour le soin autonome de vos plantes. Livraison nationale.",
            trust_1_title: "Livraison gratuite", trust_1_desc: "Dans tout le pays", trust_2_title: "Garantie 2 ans", trust_2_desc: "Couverture totale", trust_3_title: "Installation rapide", trust_3_desc: "Sans outils", trust_4_title: "30 jours", trust_4_desc: "Retour gratuit",
            btn_more_info: "Plus d'informations →", btn_buy_now: "Acheter", btn_add_cart: "Ajouter au panier", prod_specs: "Spécifications", prod_color: "Couleur", footer_tag: "Conçu pour le futur",
            cart_title: "Mon Panier", cart_empty: "Votre panier est vide", cart_empty_sub: "Explorez la boutique et ajoutez des produits", cart_subtotal: "Sous-total", btn_pay_mp: "Payer avec MercadoPago", btn_pay_pp: "Payer avec PayPal", cart_redirect_msg: "Vous serez redirigé vers la plateforme de paiement sécurisée"
        },
        de: {
            nav_inicio: "Startseite", nav_utilidades: "Werkzeuge", nav_producto: "Produkt", nav_tienda: "Shop", nav_cuenta: "Mein Konto", nav_dashboard: "Mein Dashboard",
            store_hero_tag: "Offizieller Store", store_hero_title: "Rüsten Sie Ihren", store_hero_title_span: "Garten aus.", store_hero_desc: "Modernste Technologie für autonome Pflanzenpflege. Landesweiter Versand.",
            trust_1_title: "Kostenloser Versand", trust_1_desc: "Landesweit", trust_2_title: "2 Jahre Garantie", trust_2_desc: "Volle Abdeckung", trust_3_title: "Schnelle Einrichtung", trust_3_desc: "Keine Werkzeuge", trust_4_title: "30 Tage", trust_4_desc: "Kostenlose Rückgabe",
            btn_more_info: "Mehr erfahren →", btn_buy_now: "Jetzt kaufen", btn_add_cart: "In den Warenkorb", prod_specs: "Spezifikationen", prod_color: "Farbe", footer_tag: "Für die Zukunft entworfen",
            cart_title: "Mein Warenkorb", cart_empty: "Ihr Warenkorb ist leer", cart_empty_sub: "Entdecken Sie den Shop und fügen Sie Produkte hinzu", cart_subtotal: "Zwischensumme", btn_pay_mp: "Mit MercadoPago zahlen", btn_pay_pp: "Mit PayPal zahlen", cart_redirect_msg: "Sie werden zur sicheren Zahlungsplattform weitergeleitet"
        },
        ru: {
            nav_inicio: "Главная", nav_utilidades: "Утилиты", nav_producto: "Продукт", nav_tienda: "Магазин", nav_cuenta: "Мой аккаунт", nav_dashboard: "Моя панель",
            store_hero_tag: "Официальный магазин", store_hero_title: "Оборудуйте свой", store_hero_title_span: "сад.", store_hero_desc: "Передовые технологии для автономного ухода за растениями. Доставка по всей стране.",
            trust_1_title: "Бесплатная доставка", trust_1_desc: "По всей стране", trust_2_title: "Гарантия 2 года", trust_2_desc: "Полное покрытие", trust_3_title: "Быстрая настройка", trust_3_desc: "Без инструментов", trust_4_title: "30 дней", trust_4_desc: "Свободный возврат",
            btn_more_info: "Подробнее →", btn_buy_now: "Купить сейчас", btn_add_cart: "В корзину", prod_specs: "Характеристики", prod_color: "Цвет", footer_tag: "Создано для будущего",
            cart_title: "Моя корзина", cart_empty: "Ваша корзина пуста", cart_empty_sub: "Посмотрите магазин и добавьте товары", cart_subtotal: "Итого", btn_pay_mp: "Оплатить MercadoPago", btn_pay_pp: "Оплатить PayPal", cart_redirect_msg: "Вы будете перенаправлены на безопасную платежную платформу"
        },
        zh: {
            nav_inicio: "首页", nav_utilidades: "实用工具", nav_producto: "产品", nav_tienda: "商店", nav_cuenta: "我的账户", nav_dashboard: "我的仪表板",
            store_hero_tag: "官方商店", store_hero_title: "装备您的", store_hero_title_span: "花园。", store_hero_desc: "用于自主植物护理的前沿技术。全国配送。",
            trust_1_title: "免费配送", trust_1_desc: "全国范围", trust_2_title: "2年保修", trust_2_desc: "全面保障", trust_3_title: "快速设置", trust_3_desc: "无需工具", trust_4_title: "30天", trust_4_desc: "免费退货",
            btn_more_info: "了解更多 →", btn_buy_now: "立即购买", btn_add_cart: "加入购物车", prod_specs: "规格", prod_color: "颜色", footer_tag: "为未来而设计",
            cart_title: "我的购物车", cart_empty: "购物车为空", cart_empty_sub: "浏览商店并添加产品", cart_subtotal: "小计", btn_pay_mp: "使用 MercadoPago 支付", btn_pay_pp: "使用 PayPal 支付", cart_redirect_msg: "您将被重定向到安全支付平台"
        }
    };

    function getCurrentLang() {
        return localStorage.getItem('sp_lang') || document.documentElement.getAttribute('lang') || 'es';
    }

    function applyStaticTranslations(lang) {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (translations[lang] && translations[lang][key]) {
                el.innerHTML = translations[lang][key];
            } else if (translations['en'] && translations['en'][key]) {
                el.innerHTML = translations['en'][key];
            }
        });
        lucide.createIcons(); // Recargar los iconos si la traducción los pisa
    }

    function setLanguage(lang) {
        const next = translations[lang] ? lang : 'es';
        localStorage.setItem('sp_lang', next);
        document.documentElement.setAttribute('lang', next);
        applyStaticTranslations(next);

        if (typeof window.updateProductContent === 'function') {
            window.updateProductContent(next);
        }
    }

    const langSelector = document.getElementById('langSelector');
    if (langSelector) {
        langSelector.value = getCurrentLang();
        setLanguage(langSelector.value);
        langSelector.addEventListener('change', (e) => {
            setLanguage(e.target.value);
        });
    }

    function applyTheme(theme) {
        const next = theme === 'dark' ? 'dark' : 'light';
        const btn = document.getElementById('themeToggleBtn');

        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('sp_theme', next);

        if (btn) {
            btn.innerHTML = next === 'dark'
                ? '<i data-lucide="sun" class="w-5 h-5"></i>'
                : '<i data-lucide="moon" class="w-5 h-5"></i>';
            btn.setAttribute('aria-label', next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            btn.setAttribute('title', next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            lucide.createIcons();
        }
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sp_theme') || 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    applyTheme(localStorage.getItem('sp_theme') || 'light');


    // ═══════════════════════════════════════════════════════════════
    // LOGICA ORIGINAL DEL E-COMMERCE Y MODAL
    // ═══════════════════════════════════════════════════════════════
    const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productTranslations = {
        en: {
            'aurea-one': {
                tag: 'Best seller',
                name: 'Aurea One',
                shortDesc: 'Smart sensor with an integrated solar panel. Monitors moisture, temperature and light in real time.',
                longDesc: 'Aurea One is a compact, water-resistant device (IP67) designed to live outdoors. With solar charging, WiFi + Bluetooth and 5 high-precision sensors, it lets you monitor plants 24/7 from anywhere. Setup takes under 5 minutes and requires no tools.',
                features: ['Integrated solar panel', '5 sensors', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    'Battery life': '120 h without sun',
                    'Connectivity': 'WiFi 2.4GHz + BLE 5.0',
                    'Sensors': 'Moisture, Temp, Light, pH',
                    'Protection': 'IP67 - Submersible',
                    'Weight': '148 g',
                    'Dimensions': '4.2 x 4.2 x 12 cm'
                },
                colors: ['Forest Green', 'Matte Black', 'Pearl White']
            },
            'smartplant-kit': {
                tag: 'Full kit',
                name: 'SmartPlant Kit Pro',
                shortDesc: 'Everything you need to automate your garden: controller, solar panel, sensors and irrigation pump.',
                longDesc: 'Kit Pro includes everything needed to turn any garden into a smart system: ESP32 central controller, high-efficiency solar panel, 2 capacitive soil sensors, submersible irrigation pump and tubing. Connect it, configure the app and forget manual watering.',
                features: ['Controller + solar panel', 'Irrigation pump included', '2 soil sensors', '5 min setup'],
                specs: {
                    'Controller': 'ESP32 Dual-Core 240MHz',
                    'Solar panel': '5V 2W High Efficiency',
                    'Pump': '5V USB Submersible 1.5L/min',
                    'Sensors': '2x capacitive soil sensors',
                    'WiFi range': 'Up to 100m',
                    'Contents': '6 pieces + manual'
                },
                colors: ['Standard Black']
            },
            'smartplant-solar': {
                tag: 'Accessory',
                name: 'Solar Panel',
                shortDesc: 'High-efficiency solar panel to extend your SmartPlant autonomy. USB-C connection.',
                longDesc: 'Extend your SmartPlant autonomy with this extra solar panel. Built for outdoor use with IP67 protection, adjustable 0 to 45 degree stand and universal USB-C connection. Compatible with every SmartPlant device. Tool-free installation in under 2 minutes.',
                features: ['High efficiency', 'USB-C', 'Adjustable mount', 'IP67 compatible'],
                specs: {
                    'Power': '5V 2W',
                    'Connection': 'Universal USB-C',
                    'Protection': 'IP67',
                    'Angle': 'Adjustable 0-45 degrees',
                    'Weight': '95 g',
                    'Cable': '1.5m braided'
                },
                colors: ['Matte Black']
            }
        },
        pt: {
            'aurea-one': {
                tag: 'Mais vendido',
                name: 'Aurea One',
                shortDesc: 'Sensor inteligente com painel solar integrado. Monitora umidade, temperatura e luz em tempo real.',
                longDesc: 'O Aurea One é compacto, resistente à água (IP67) e feito para viver ao ar livre. Com painel solar, WiFi + Bluetooth e 5 sensores de alta precisão, permite monitorar suas plantas 24/7 de qualquer lugar. A configuração leva menos de 5 minutos.',
                features: ['Painel solar integrado', '5 sensores', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    'Autonomia': '120 h sem sol',
                    'Conectividade': 'WiFi 2.4GHz + BLE 5.0',
                    'Sensores': 'Umidade, Temp, Luz, pH',
                    'Proteção': 'IP67 - Submersível',
                    'Peso': '148 g',
                    'Dimensões': '4.2 x 4.2 x 12 cm'
                },
                colors: ['Verde Floresta', 'Preto Fosco', 'Branco Pérola']
            },
            'smartplant-kit': {
                tag: 'Kit completo',
                name: 'SmartPlant Kit Pro',
                shortDesc: 'Tudo que você precisa para automatizar seu jardim: controlador, painel solar, sensores e bomba de irrigação.',
                longDesc: 'O Kit Pro inclui tudo para transformar qualquer jardim em um sistema inteligente: controlador ESP32, painel solar de alta eficiência, 2 sensores capacitivos de solo, bomba submersível e tubulação. Conecte, configure o app e esqueça a rega manual.',
                features: ['Controlador + painel solar', 'Bomba de irrigação incluída', '2 sensores de solo', 'Setup em 5 min'],
                specs: {
                    'Controlador': 'ESP32 Dual-Core 240MHz',
                    'Painel solar': '5V 2W Alta eficiência',
                    'Bomba': '5V USB Submersível 1.5L/min',
                    'Sensores': '2x capacitivos de solo',
                    'Alcance WiFi': 'Até 100m',
                    'Conteúdo': '6 peças + manual'
                },
                colors: ['Preto Standard']
            },
            'smartplant-solar': {
                tag: 'Acessório',
                name: 'Painel Solar',
                shortDesc: 'Painel solar de alta eficiência para ampliar a autonomia do seu SmartPlant. Conexão USB-C.',
                longDesc: 'Amplie a autonomia do seu SmartPlant com este painel solar adicional. Projetado para exteriores com proteção IP67, suporte ajustável de 0 a 45 graus e conexão USB-C universal. Compatível com todos os dispositivos SmartPlant.',
                features: ['Alta eficiência', 'USB-C', 'Montagem ajustável', 'Compatível IP67'],
                specs: {
                    'Potência': '5V 2W',
                    'Conexão': 'USB-C Universal',
                    'Proteção': 'IP67',
                    'Ângulo': 'Ajustável 0-45 graus',
                    'Peso': '95 g',
                    'Cabo': '1.5m trançado'
                },
                colors: ['Preto Fosco']
            }
        },
        fr: {
            'aurea-one': {
                tag: 'Meilleure vente',
                name: 'Aurea One',
                shortDesc: 'Capteur intelligent avec panneau solaire intégré. Surveille humidité, température et lumière en temps réel.',
                longDesc: 'Aurea One est compact, résistant à l eau (IP67) et conçu pour l extérieur. Avec recharge solaire, WiFi + Bluetooth et 5 capteurs de précision, il permet de surveiller vos plantes 24/7 depuis n importe où. Installation en moins de 5 minutes.',
                features: ['Panneau solaire intégré', '5 capteurs', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    'Autonomie': '120 h sans soleil',
                    'Connectivité': 'WiFi 2.4GHz + BLE 5.0',
                    'Capteurs': 'Humidité, Temp, Lumière, pH',
                    'Protection': 'IP67 - Submersible',
                    'Poids': '148 g',
                    'Dimensions': '4.2 x 4.2 x 12 cm'
                },
                colors: ['Vert Forêt', 'Noir Mat', 'Blanc Perle']
            },
            'smartplant-kit': {
                tag: 'Kit complet',
                name: 'SmartPlant Kit Pro',
                shortDesc: 'Tout pour automatiser votre jardin : contrôleur, panneau solaire, capteurs et pompe d arrosage.',
                longDesc: 'Le Kit Pro inclut le nécessaire pour transformer tout jardin en système intelligent : contrôleur ESP32, panneau solaire haute efficacité, 2 capteurs de sol capacitifs, pompe submersible et tuyauterie. Connectez, configurez l app et oubliez l arrosage manuel.',
                features: ['Contrôleur + panneau solaire', 'Pompe d arrosage incluse', '2 capteurs de sol', 'Installation en 5 min'],
                specs: {
                    'Contrôleur': 'ESP32 Dual-Core 240MHz',
                    'Panneau solaire': '5V 2W Haute efficacité',
                    'Pompe': '5V USB Submersible 1.5L/min',
                    'Capteurs': '2x capteurs capacitifs',
                    'Portée WiFi': 'Jusqu à 100m',
                    'Contenu': '6 pièces + manuel'
                },
                colors: ['Noir Standard']
            },
            'smartplant-solar': {
                tag: 'Accessoire',
                name: 'Panneau Solaire',
                shortDesc: 'Panneau solaire haute efficacité pour prolonger l autonomie de votre SmartPlant. Connexion USB-C.',
                longDesc: 'Prolongez l autonomie de votre SmartPlant avec ce panneau solaire additionnel. Conçu pour l extérieur avec protection IP67, support réglable de 0 à 45 degrés et connexion USB-C universelle.',
                features: ['Haute efficacité', 'USB-C', 'Support réglable', 'Compatible IP67'],
                specs: {
                    'Puissance': '5V 2W',
                    'Connexion': 'USB-C Universelle',
                    'Protection': 'IP67',
                    'Angle': 'Réglable 0-45 degrés',
                    'Poids': '95 g',
                    'Câble': '1.5m tressé'
                },
                colors: ['Noir Mat']
            }
        },
        de: {
            'aurea-one': {
                tag: 'Bestseller',
                name: 'Aurea One',
                shortDesc: 'Intelligenter Sensor mit integriertem Solarpanel. Überwacht Feuchtigkeit, Temperatur und Licht in Echtzeit.',
                longDesc: 'Aurea One ist ein kompaktes, wasserfestes Gerät (IP67) für den Außeneinsatz. Mit Solarladung, WiFi + Bluetooth und 5 Präzisionssensoren können Sie Pflanzen rund um die Uhr von überall überwachen. Einrichtung in unter 5 Minuten.',
                features: ['Integriertes Solarpanel', '5 Sensoren', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    'Autonomie': '120 h ohne Sonne',
                    'Konnektivität': 'WiFi 2.4GHz + BLE 5.0',
                    'Sensoren': 'Feuchte, Temp, Licht, pH',
                    'Schutz': 'IP67 - Tauchfest',
                    'Gewicht': '148 g',
                    'Abmessungen': '4.2 x 4.2 x 12 cm'
                },
                colors: ['Waldgrün', 'Matt Schwarz', 'Perlweiß']
            },
            'smartplant-kit': {
                tag: 'Komplettes Kit',
                name: 'SmartPlant Kit Pro',
                shortDesc: 'Alles zur Automatisierung Ihres Gartens: Controller, Solarpanel, Sensoren und Bewässerungspumpe.',
                longDesc: 'Das Kit Pro enthält alles, um jeden Garten in ein intelligentes System zu verwandeln: ESP32 Controller, hocheffizientes Solarpanel, 2 kapazitive Bodensensoren, Tauchpumpe und Schläuche. Anschließen, App konfigurieren und manuelles Gießen vergessen.',
                features: ['Controller + Solarpanel', 'Bewässerungspumpe inklusive', '2 Bodensensoren', 'Setup in 5 min'],
                specs: {
                    'Controller': 'ESP32 Dual-Core 240MHz',
                    'Solarpanel': '5V 2W Hohe Effizienz',
                    'Pumpe': '5V USB Tauchpumpe 1.5L/min',
                    'Sensoren': '2x kapazitive Bodensensoren',
                    'WiFi-Reichweite': 'Bis 100m',
                    'Inhalt': '6 Teile + Handbuch'
                },
                colors: ['Standard Schwarz']
            },
            'smartplant-solar': {
                tag: 'Zubehör',
                name: 'Solarpanel',
                shortDesc: 'Hocheffizientes Solarpanel zur Verlängerung der Autonomie Ihres SmartPlant. USB-C Anschluss.',
                longDesc: 'Verlängern Sie die Autonomie Ihres SmartPlant mit diesem zusätzlichen Solarpanel. Für außen mit IP67 Schutz, einstellbarer Halterung von 0 bis 45 Grad und universellem USB-C Anschluss.',
                features: ['Hohe Effizienz', 'USB-C', 'Verstellbare Halterung', 'IP67 kompatibel'],
                specs: {
                    'Leistung': '5V 2W',
                    'Anschluss': 'Universal USB-C',
                    'Schutz': 'IP67',
                    'Winkel': 'Einstellbar 0-45 Grad',
                    'Gewicht': '95 g',
                    'Kabel': '1.5m geflochten'
                },
                colors: ['Matt Schwarz']
            }
        },
        ru: {
            'aurea-one': {
                tag: 'Хит продаж',
                name: 'Aurea One',
                shortDesc: 'Умный датчик со встроенной солнечной панелью. Отслеживает влажность, температуру и свет в реальном времени.',
                longDesc: 'Aurea One - компактное водостойкое устройство (IP67) для улицы. С солнечной зарядкой, WiFi + Bluetooth и 5 точными датчиками вы можете контролировать растения 24/7 из любой точки. Настройка занимает меньше 5 минут.',
                features: ['Встроенная солнечная панель', '5 датчиков', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    'Автономность': '120 ч без солнца',
                    'Подключение': 'WiFi 2.4GHz + BLE 5.0',
                    'Датчики': 'Влажность, Темп, Свет, pH',
                    'Защита': 'IP67 - Погружной',
                    'Вес': '148 г',
                    'Размеры': '4.2 x 4.2 x 12 см'
                },
                colors: ['Лесной зеленый', 'Матовый черный', 'Жемчужно-белый']
            },
            'smartplant-kit': {
                tag: 'Полный набор',
                name: 'SmartPlant Kit Pro',
                shortDesc: 'Все для автоматизации сада: контроллер, солнечная панель, датчики и насос полива.',
                longDesc: 'Kit Pro включает все, чтобы превратить сад в умную систему: контроллер ESP32, эффективную солнечную панель, 2 емкостных датчика почвы, погружной насос и трубки. Подключите, настройте приложение и забудьте о ручном поливе.',
                features: ['Контроллер + солнечная панель', 'Насос полива включен', '2 датчика почвы', 'Настройка за 5 мин'],
                specs: {
                    'Контроллер': 'ESP32 Dual-Core 240MHz',
                    'Солнечная панель': '5V 2W высокая эффективность',
                    'Насос': '5V USB погружной 1.5L/min',
                    'Датчики': '2x емкостных датчика почвы',
                    'Дальность WiFi': 'До 100м',
                    'Комплект': '6 деталей + инструкция'
                },
                colors: ['Стандартный черный']
            },
            'smartplant-solar': {
                tag: 'Аксессуар',
                name: 'Солнечная панель',
                shortDesc: 'Высокоэффективная солнечная панель для увеличения автономности SmartPlant. Подключение USB-C.',
                longDesc: 'Увеличьте автономность SmartPlant с дополнительной солнечной панелью. Для улицы, с защитой IP67, регулируемой подставкой 0-45 градусов и универсальным подключением USB-C.',
                features: ['Высокая эффективность', 'USB-C', 'Регулируемое крепление', 'Совместимость IP67'],
                specs: {
                    'Мощность': '5V 2W',
                    'Подключение': 'USB-C универсальный',
                    'Защита': 'IP67',
                    'Угол': 'Регулируемый 0-45 градусов',
                    'Вес': '95 г',
                    'Кабель': '1.5м плетеный'
                },
                colors: ['Матовый черный']
            }
        },
        zh: {
            'aurea-one': {
                tag: '畅销',
                name: 'Aurea One',
                shortDesc: '带集成太阳能板的智能传感器。实时监测湿度、温度和光照。',
                longDesc: 'Aurea One 是一款紧凑、防水（IP67）的户外设备。它配备太阳能充电、WiFi + Bluetooth 和 5 个高精度传感器，可让您随时随地 24/7 监测植物。设置不到 5 分钟。',
                features: ['集成太阳能板', '5 个传感器', 'WiFi + Bluetooth', 'IP67'],
                specs: {
                    '续航': '无阳光 120 小时',
                    '连接': 'WiFi 2.4GHz + BLE 5.0',
                    '传感器': '湿度、温度、光照、pH',
                    '防护': 'IP67 - 可浸水',
                    '重量': '148 g',
                    '尺寸': '4.2 x 4.2 x 12 cm'
                },
                colors: ['森林绿', '哑光黑', '珍珠白']
            },
            'smartplant-kit': {
                tag: '完整套装',
                name: 'SmartPlant Kit Pro',
                shortDesc: '自动化花园所需的一切：控制器、太阳能板、传感器和灌溉泵。',
                longDesc: 'Kit Pro 包含把任何花园变成智能系统所需的一切：ESP32 中央控制器、高效太阳能板、2 个电容式土壤传感器、潜水灌溉泵和管路。连接、配置应用，然后告别手动浇水。',
                features: ['控制器 + 太阳能板', '含灌溉泵', '2 个土壤传感器', '5 分钟设置'],
                specs: {
                    '控制器': 'ESP32 Dual-Core 240MHz',
                    '太阳能板': '5V 2W 高效率',
                    '水泵': '5V USB 潜水泵 1.5L/min',
                    '传感器': '2x 电容式土壤传感器',
                    'WiFi 范围': '最高 100m',
                    '内容': '6 件 + 手册'
                },
                colors: ['标准黑']
            },
            'smartplant-solar': {
                tag: '配件',
                name: '太阳能板',
                shortDesc: '高效率太阳能板，可延长 SmartPlant 续航。USB-C 连接。',
                longDesc: '使用这块额外太阳能板延长 SmartPlant 的续航。专为户外设计，具备 IP67 防护、0 到 45 度可调支架和通用 USB-C 连接。',
                features: ['高效率', 'USB-C', '可调安装', '兼容 IP67'],
                specs: {
                    '功率': '5V 2W',
                    '连接': '通用 USB-C',
                    '防护': 'IP67',
                    '角度': '0-45 度可调',
                    '重量': '95 g',
                    '线缆': '1.5m 编织线'
                },
                colors: ['哑光黑']
            }
        }
    };
    const isLoggedIn = <?= $logged ? 'true' : 'false' ?>;
    const paypalCurrency = <?= json_encode($paypalCurrency) ?>;
    const paypalArsToUsdRate = <?= json_encode($paypalArsToUsdRate) ?>;
    let currentProduct = null;
    let selectedColor = 0;

    function getProductById(id) {
        return products.find(product => product.id === id);
    }

    function getProductCopy(product, lang = getCurrentLang()) {
        const langProducts = productTranslations[lang] || {};
        const fallbackProducts = productTranslations.en || {};
        const copy = lang === 'es' ? {} : (langProducts[product.id] || fallbackProducts[product.id] || {});

        return {
            name: copy.name || product.name,
            tag: copy.tag || product.tag,
            shortDesc: copy.shortDesc || product.shortDesc,
            longDesc: copy.longDesc || product.longDesc,
            features: copy.features || product.features,
            specs: copy.specs || product.specs,
            colors: copy.colors || product.colors.map(color => color.name)
        };
    }

    function getColorName(product, colorIndex, lang = getCurrentLang()) {
        const copy = getProductCopy(product, lang);
        return copy.colors[colorIndex] || product.colors[colorIndex]?.name || '';
    }

    window.updateProductContent = function(lang = getCurrentLang()) {
        document.querySelectorAll('.store-card[data-product-id]').forEach(card => {
            const product = getProductById(card.dataset.productId);
            if (!product) return;

            const copy = getProductCopy(product, lang);
            const tag = card.querySelector('[data-product-field="tag"]');
            const name = card.querySelector('[data-product-field="name"]');
            const desc = card.querySelector('[data-product-field="shortDesc"]');
            const features = card.querySelector('[data-product-field="features"]');

            if (tag) tag.textContent = copy.tag;
            if (name) name.textContent = copy.name;
            if (desc) desc.textContent = copy.shortDesc;
            if (features) {
                features.innerHTML = copy.features
                    .map(feature => `<span class="store-feature-tag"><i data-lucide="check" class="w-3 h-3 inline"></i> ${feature}</span>`)
                    .join('');
            }
        });

        if (currentProduct && document.getElementById('productModal').classList.contains('modal-visible')) {
            renderModalCopy();
        }

        if (typeof renderCart === 'function') {
            renderCart();
        }

        lucide.createIcons();
    };

    setLanguage(getCurrentLang());

    function visualCheckout(metodo) {
        if (!ensureLoggedInForCheckout()) return;

        const cart = getCart();
        if (!cart.length) return;

        localStorage.setItem('sp_checkout_preview', JSON.stringify({
            metodo,
            cart,
            total: cart.reduce((sum, item) => sum + item.price * item.qty, 0),
            fecha: new Date().toISOString()
        }));
        showToast('Checkout visual con ' + metodo + '. Integracion pendiente.');
    }

    function getCartSubtotal() {
        return getCart().reduce((sum, item) => sum + item.price * item.qty, 0);
    }

    function getPayPalTotal() {
        const totalArs = getCartSubtotal();
        return Math.max(1, totalArs / paypalArsToUsdRate).toFixed(2);
    }

    // ═══════════ CART (localStorage) ═══════════
    function getCart() {
        try { return JSON.parse(localStorage.getItem('sp_cart')) || []; }
        catch { return []; }
    }
    function saveCart(cart) {
        localStorage.setItem('sp_cart', JSON.stringify(cart));
        renderCart();
    }

    function addToCart() {
        const cart = getCart();
        const color = currentProduct.colors[selectedColor];
        const productCopy = getProductCopy(currentProduct);
        const colorName = getColorName(currentProduct, selectedColor);
        const key = currentProduct.id + '_' + selectedColor;
        const existing = cart.find(i => i.key === key);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                key, id: currentProduct.id,
                name: productCopy.name,
                color: colorName,
                colorIndex: selectedColor,
                price: currentProduct.price,
                image: color.image,
                qty: 1
            });
        }
        saveCart(cart);
        showToast(`${productCopy.name} - ${colorName} agregado al carrito`);
        closeModal();
        setTimeout(() => toggleCart(true), 350);
    }

    function removeFromCart(key) {
        saveCart(getCart().filter(i => i.key !== key));
    }

    function updateQty(key, delta) {
        const cart = getCart();
        const item = cart.find(i => i.key === key);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) return removeFromCart(key);
        saveCart(cart);
    }

    function renderCart() {
        const cart = getCart();
        const container = document.getElementById('cartItems');
        const empty = document.getElementById('cartEmpty');
        const footer = document.getElementById('cartFooter');
        const badge = document.getElementById('cartBadge');
        const totalQty = cart.reduce((s, i) => s + i.qty, 0);
        const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);

        badge.textContent = totalQty;
        badge.style.display = totalQty > 0 ? '' : 'none';

        if (cart.length === 0) {
            empty.style.display = '';
            footer.style.display = 'none';
            container.querySelectorAll('.cart-item').forEach(el => el.remove());
            return;
        }

        empty.style.display = 'none';
        footer.style.display = '';
        document.getElementById('cartSubtotal').textContent = formatPrice(subtotal);

        // Remove old items
        container.querySelectorAll('.cart-item').forEach(el => el.remove());

        cart.forEach(item => {
            const product = getProductById(item.id);
            const copy = product ? getProductCopy(product) : null;
            const itemName = copy?.name || item.name;
            const itemColor = product && Number.isInteger(item.colorIndex)
                ? getColorName(product, item.colorIndex)
                : item.color;
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <img src="${item.image}" alt="${itemName}" class="cart-item-img">
                <div class="cart-item-info">
                    <p class="font-semibold text-sm">${itemName}</p>
                    <p class="text-gray-500 text-xs">${itemColor}</p>
                    <p class="text-white/70 font-bold text-sm mt-1">${formatPrice(item.price)}</p>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-qty-btn" onclick="updateQty('${item.key}',-1)">−</button>
                    <span class="cart-qty-num">${item.qty}</span>
                    <button class="cart-qty-btn" onclick="updateQty('${item.key}',1)">+</button>
                </div>
                <button class="cart-remove-btn flex items-center justify-center" onclick="removeFromCart('${item.key}')" title="Eliminar">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            container.appendChild(div);
        });
        
        // Recargar iconos de lucide recien insertados
        lucide.createIcons();
    }

    function toggleCart(forceOpen) {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        const isOpen = drawer.classList.contains('cart-drawer-open');
        if (forceOpen === true && isOpen) return;
        drawer.classList.toggle('cart-drawer-open');
        overlay.classList.toggle('cart-overlay-visible');
        document.body.style.overflow = drawer.classList.contains('cart-drawer-open') ? 'hidden' : '';
    }

    function ensureLoggedInForCheckout() {
        if (isLoggedIn) return true;
        showToast('Inicia sesion para registrar tu compra');
        setTimeout(() => {
            window.location.href = '/login';
        }, 1200);
        return false;
    }

    async function registrarCompra(payload) {
        const cart = payload.cart || getCart();
        const montoTotal = payload.monto_total ?? cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

        const res = await fetch('/purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create',
                metodo_pago: payload.metodo_pago,
                estado: payload.estado,
                moneda: payload.moneda || 'ARS',
                referencia_externa: payload.referencia_externa || '',
                fecha_pago: payload.fecha_pago || new Date().toISOString(),
                notas: payload.notas || '',
                monto_total: montoTotal,
                cart
            })
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'No se pudo registrar la compra');
        }

        return data;
    }

    // ═══════════ CHECKOUT ═══════════
    async function checkoutMercadoPago() {
        if (!ensureLoggedInForCheckout()) return;

        const cart = getCart();
        if (!cart.length) return;

        try {
            await registrarCompra({
                metodo_pago: 'mercadopago',
                estado: 'pendiente',
                moneda: 'ARS',
                referencia_externa: 'MP-PENDING-' + Date.now(),
                notas: 'Compra pendiente generada desde la tienda antes de redirigir a Mercado Pago'
            });

            showToast('Compra pendiente registrada. Redirigiendo a MercadoPago…');
            setTimeout(() => {
                window.open('https://www.mercadopago.com.ar', '_blank');
            }, 800);
        } catch (error) {
            console.error(error);
            showToast('No se pudo registrar la compra en la base de datos');
        }
    }

    async function checkoutMercadoPagoReal() {
        if (!ensureLoggedInForCheckout()) return;

        const cart = getCart();
        if (!cart.length) return;

        try {
            const res = await fetch('/purchase', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_mp_preference',
                    cart,
                    monto_total: getCartSubtotal()
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success || !data.init_point) {
                throw new Error(data.error || 'No se pudo crear la preferencia de Mercado Pago');
            }

            showToast('Redirigiendo a Mercado Pago...');
            window.location.href = data.init_point;
        } catch (error) {
            console.error(error);
            showToast(error.message || 'No se pudo iniciar Mercado Pago');
        }
    }

    checkoutMercadoPago = checkoutMercadoPagoReal;

    async function confirmMercadoPagoReturn() {
        const params = new URLSearchParams(window.location.search);
        const paymentState = params.get('payment');
        const paymentId = params.get('payment_id') || params.get('collection_id');
        const externalReference = params.get('external_reference');

        if (!paymentState || !paymentId || !externalReference || !isLoggedIn) return;

        try {
            const res = await fetch('/purchase', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'confirm_mp_payment',
                    payment_id: paymentId,
                    external_reference: externalReference
                })
            });
            const data = await res.json();
            if (res.ok && data.success && data.estado === 'aprobado') {
                localStorage.removeItem('sp_cart');
                renderCart();
                showToast('Pago aprobado. Compra guardada.');
            } else if (res.ok && data.success) {
                showToast('Pago ' + data.estado + '. Compra actualizada.');
            }
        } catch (error) {
            console.error(error);
        }
    }

    // ═══════════ INTEGRACIÓN PAYPAL ═══════════
    if (typeof paypal !== 'undefined') {
        if (!isLoggedIn) {
            document.getElementById('paypal-button-container').innerHTML =
                '<p class="text-center text-xs text-gray-500 font-light">Inicia sesion para habilitar el pago y guardar la compra.</p>';
        } else {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    const cart = getCart();
                    if (!cart.length) {
                        throw new Error('El carrito esta vacio');
                    }
                    const total = getPayPalTotal();

                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                currency_code: paypalCurrency,
                                value: total
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(async function(details) {
                        try {
                            const totalPagado = Number(details.purchase_units?.[0]?.amount?.value || 0);
                            const payerName = details.payer?.name?.given_name || 'cliente';

                            await registrarCompra({
                                metodo_pago: 'paypal',
                                estado: 'aprobado',
                                moneda: paypalCurrency,
                                referencia_externa: data.orderID || details.id || '',
                                fecha_pago: new Date().toISOString(),
                                notas: 'Pago aprobado por PayPal para ' + payerName,
                                monto_total: totalPagado
                            });

                            showToast('Pago completado por ' + payerName);
                            localStorage.removeItem('sp_cart');
                            renderCart();
                            toggleCart();
                        } catch (error) {
                            console.error(error);
                            showToast('El pago se aprobó, pero no se pudo guardar la compra');
                        }
                    });
                },
                onError: function(err) {
                    console.error('PayPal Error:', err);
                    showToast('Error al procesar el pago de PayPal');
                }
            }).render('#paypal-button-container');
        }
    }

    function buyNow() {
        addToCart();
    }

    // ═══════════ MODAL ═══════════
    function renderModalCopy() {
        if (!currentProduct) return;

        const copy = getProductCopy(currentProduct);
        const badge = document.getElementById('modalBadge');

        badge.textContent = copy.tag;
        document.getElementById('modalName').textContent = copy.name;
        document.getElementById('modalDesc').textContent = copy.longDesc;
        document.getElementById('modalColorName').textContent = getColorName(currentProduct, selectedColor);

        document.querySelectorAll('#modalColors .modal-color-btn').forEach((button, index) => {
            button.title = getColorName(currentProduct, index);
        });

        const specsC = document.getElementById('modalSpecs');
        specsC.innerHTML = '';
        Object.entries(copy.specs).forEach(([k, v]) => {
            specsC.innerHTML += `<div class="modal-spec-row"><span class="modal-spec-key">${k}</span><span class="modal-spec-val">${v}</span></div>`;
        });

        const featC = document.getElementById('modalFeatures');
        featC.innerHTML = '';
        copy.features.forEach(f => {
            featC.innerHTML += `<span class="store-feature-tag"><i data-lucide="check" class="w-3 h-3 inline"></i> ${f}</span>`;
        });

        lucide.createIcons();
    }

    function openModal(idx) {
        currentProduct = products[idx];
        selectedColor = 0;
        const copy = getProductCopy(currentProduct);
        const m = document.getElementById('productModal');
        const badge = document.getElementById('modalBadge');
        badge.textContent = copy.tag;
        badge.className = 'modal-badge store-badge-' + currentProduct.tagColor;

        document.getElementById('modalName').textContent = copy.name;
        document.getElementById('modalDesc').textContent = copy.longDesc;
        document.getElementById('modalPrice').textContent = formatPrice(currentProduct.price);

        const oldP = document.getElementById('modalOldPrice');
        oldP.textContent = currentProduct.oldPrice ? formatPrice(currentProduct.oldPrice) : '';
        oldP.style.display = currentProduct.oldPrice ? '' : 'none';

        document.getElementById('modalMainImg').src = currentProduct.image;
        document.getElementById('modalMainImg').alt = currentProduct.name;

        const thumbsC = document.getElementById('modalThumbs');
        thumbsC.innerHTML = '';
        currentProduct.gallery.forEach((src, i) => {
            const t = document.createElement('button');
            t.className = 'modal-thumb' + (i === 0 ? ' modal-thumb-active' : '');
            t.innerHTML = `<img src="${src}" alt="Vista ${i+1}">`;
            t.onclick = () => selectThumb(src, i);
            thumbsC.appendChild(t);
        });

        const colorsS = document.getElementById('modalColorsSection');
        const colorsC = document.getElementById('modalColors');
        colorsC.innerHTML = '';
        if (currentProduct.colors.length > 1) {
            colorsS.style.display = '';
            currentProduct.colors.forEach((c, i) => {
                const b = document.createElement('button');
                b.className = 'modal-color-btn' + (i === 0 ? ' modal-color-active' : '');
                b.style.background = c.hex;
                b.title = getColorName(currentProduct, i);
                b.onclick = () => selectColor(i);
                colorsC.appendChild(b);
            });
            document.getElementById('modalColorName').textContent = getColorName(currentProduct, 0);
        } else { colorsS.style.display = 'none'; }

        const specsC = document.getElementById('modalSpecs');
        specsC.innerHTML = '';
        Object.entries(copy.specs).forEach(([k, v]) => {
            specsC.innerHTML += `<div class="modal-spec-row"><span class="modal-spec-key">${k}</span><span class="modal-spec-val">${v}</span></div>`;
        });

        const featC = document.getElementById('modalFeatures');
        featC.innerHTML = '';
        copy.features.forEach(f => {
            featC.innerHTML += `<span class="store-feature-tag"><i data-lucide="check" class="w-3 h-3 inline"></i> ${f}</span>`; 
        });

        m.classList.add('modal-visible');
        document.body.style.overflow = 'hidden';
        
        // Recargar los iconos dinámicos que acabamos de meter (los checkmarks)
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('productModal').classList.remove('modal-visible');
        document.body.style.overflow = '';
    }
    function selectThumb(src, i) {
        document.getElementById('modalMainImg').src = src;
        document.querySelectorAll('.modal-thumb').forEach((t, idx) => t.classList.toggle('modal-thumb-active', idx === i));
    }
    function selectColor(i) {
        selectedColor = i;
        const c = currentProduct.colors[i];
        document.getElementById('modalMainImg').src = c.image;
        document.getElementById('modalColorName').textContent = getColorName(currentProduct, i);
        document.querySelectorAll('.modal-color-btn').forEach((b, idx) => b.classList.toggle('modal-color-active', idx === i));
        document.querySelectorAll('.modal-thumb').forEach(t => t.classList.remove('modal-thumb-active'));
    }
    function formatPrice(n) { return '$' + n.toLocaleString('es-AR'); }

    // ═══════════ UTILS ═══════════
    function showToast(msg) {
        const toast = document.getElementById('cartToast');
        document.getElementById('cartToastMsg').textContent = msg;
        toast.classList.add('store-toast-visible');
        setTimeout(() => toast.classList.remove('store-toast-visible'), 2800);
    }

    document.getElementById('productModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeModal(); const d = document.getElementById('cartDrawer'); if (d.classList.contains('cart-drawer-open')) toggleCart(); }
    });
    // Scroll progress
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('active'); });
    }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-blur, .stagger-children').forEach(el => obs.observe(el));

    confirmMercadoPagoReturn();

    // Init cart on load
    renderCart();
</script>

<!-- ═══ AI ASSISTANT CHAT WIDGET (GEMINI) ═══ -->
<?php include APPPATH . 'Views/partials/ai_assistant_widget.php'; ?>

</body>
</html>
