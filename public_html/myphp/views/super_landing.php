<?php
/**
 * Vista para Super Landing
 * Variables disponibles: $landing
 */

// Obtener códigos relacionados
$all_codigos = get_super_landing_codes($landing, 50);

// Función para obtener el mejor código super de una marca específica
$get_super_for_brand = function($brand_slug) use ($all_codigos) {
    foreach ($all_codigos as $c) {
        if (isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super' && ($c['marca'] === $brand_slug || (isset($c['marca_id']) && $c['marca_id'] === $brand_slug))) {
            return $c;
        }
    }
    return null;
};

$super_codigos = array_filter($all_codigos, function($c) {
    return isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super';
});
$normal_codigos = array_filter($all_codigos, function($c) {
    return !(isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super');
});

// For the top carousel, we still use $super_codigos
$codigos = array_values($super_codigos);
?>

<style>
/* Estilos específicos para Super Landing */
.super-landing-hero {
    position: relative;
    padding: 80px 0 60px;
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo htmlspecialchars($landing['hero_image'] ?? '/img/hero-default.jpg'); ?>');
    background-size: cover;
    background-position: center;
    color: white;
    text-align: center;
    border-radius: 0 0 20px 20px;
    margin-bottom: 40px;
}

.super-landing-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.super-landing-hero .lead {
    font-size: 1.2rem;
    max-width: 800px;
    margin: 0 auto;
    opacity: 0.9;
}

.sl-section {
    padding: 60px 0;
}

.sl-section-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.sl-section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: #E30613;
    border-radius: 2px;
}

/* Pros & Cons */
.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.pros-box, .cons-box {
    padding: 30px;
    border-radius: 12px;
}

.pros-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.cons-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
}

.pros-box h3, .cons-box h3 {
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pros-box h3 { color: #166534; }
.cons-box h3 { color: #991b1b; }

.pros-list, .cons-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pros-list li, .cons-list li {
    margin-bottom: 12px;
    position: relative;
    padding-left: 25px;
}

.pros-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #16a34a;
    font-weight: bold;
}

.cons-list li::before {
    content: '✕';
    position: absolute;
    left: 0;
    color: #dc2626;
    font-weight: bold;
}

/* Steps */
.steps-timeline {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.step-item {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.step-number {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #E30613;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.step-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

/* FAQ */
.faq-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 15px;
    padding: 20px;
}

.faq-question {
    font-weight: 600;
    font-size: 1.1rem;
    color: #1f2937;
    margin-bottom: 10px;
}

.faq-answer {
    color: #4b5563;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .pros-cons-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// ============================================================
// Schema.org Structured Data for SEO (JSON-LD)
// ============================================================

$landing_url = 'https://www.codigoamigo.com/guias/' . htmlspecialchars($landing['slug']);
$landing_title = $landing['title'] ?? '';
$landing_description = $landing['meta_description'] ?? '';
$landing_image = $landing['hero_image'] ?? 'https://www.codigoamigo.com/images/logo.png';

// 1. Article Schema
$schema_article = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $landing_title,
    'description' => $landing_description,
    'image' => $landing_image,
    'url' => $landing_url,
    'author' => [
        '@type' => 'Organization',
        'name' => 'CodigoAmigo',
        'url' => 'https://www.codigoamigo.com'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'CodigoAmigo',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://www.codigoamigo.com/images/logo.png'
        ]
    ],
    'datePublished' => date('Y-m-d'),
    'dateModified' => date('Y-m-d')
];
echo '<script type="application/ld+json">' . json_encode($schema_article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// 2. BreadcrumbList Schema
$breadcrumb_items = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://www.codigoamigo.com'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guías', 'item' => 'https://www.codigoamigo.com/guias'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $landing_title, 'item' => $landing_url]
];
$schema_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumb_items
];
echo '<script type="application/ld+json">' . json_encode($schema_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// 3. FAQPage Schema (from sections data)
if (!empty($landing['sections'])) {
    $faq_entities = [];
    $howto_steps = [];
    
    foreach ($landing['sections'] as $section) {
        // Collect FAQ items
        if ($section['type'] === 'faq' && !empty($section['faqs'])) {
            foreach ($section['faqs'] as $faq) {
                $faq_entities[] = [
                    '@type' => 'Question',
                    'name' => strip_tags($faq['question']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq['answer'])
                    ]
                ];
            }
        }
        
        // Collect HowTo steps
        if ($section['type'] === 'steps' && !empty($section['steps'])) {
            foreach ($section['steps'] as $idx => $step) {
                $howto_steps[] = [
                    '@type' => 'HowToStep',
                    'position' => $idx + 1,
                    'name' => strip_tags($step['title']),
                    'text' => strip_tags($step['text'])
                ];
            }
        }
    }
    
    // Output FAQPage schema
    if (!empty($faq_entities)) {
        $schema_faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faq_entities
        ];
        echo '<script type="application/ld+json">' . json_encode($schema_faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
    
    // Output HowTo schema
    if (!empty($howto_steps)) {
        $schema_howto = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $landing_title,
            'step' => $howto_steps
        ];
        echo '<script type="application/ld+json">' . json_encode($schema_howto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}

// 4. Offer Schema (from codes)
if (!empty($all_codigos)) {
    $offers = [];
    foreach (array_slice($all_codigos, 0, 5) as $cod) {
        $cod_brand = $cod['marca'] ?? '';
        $cod_benefit = $cod['num_beneficio'] ?? 0;
        $offers[] = [
            '@type' => 'Offer',
            'name' => 'Código amigo ' . $cod_brand,
            'price' => '0',
            'priceCurrency' => 'EUR',
            'availability' => 'https://schema.org/InStock',
            'description' => isset($cod['descripcion']) ? strip_tags(substr($cod['descripcion'], 0, 200)) : 'Código de descuento para ' . $cod_brand,
            'validThrough' => date('Y-12-31')
        ];
    }
    if (!empty($offers)) {
        $schema_offers = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $landing_title,
            'url' => $landing_url,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $offers
            ]
        ];
        echo '<script type="application/ld+json">' . json_encode($schema_offers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
?>

<div class="super-landing-container">
    <!-- Hero Section -->
    <div class="super-landing-hero">
        <div class="container">
            <h1><?php echo htmlspecialchars($landing['title']); ?></h1>
            <?php if (!empty($landing['meta_description'])): ?>
                <p class="lead"><?php echo htmlspecialchars($landing['meta_description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    // AdSense Top
    if (function_exists('get_adsense_top')) {
        echo generate_adsense_container(get_adsense_top(), 'adsense-sl-top', 'margin-bottom: 20px;');
    }
    ?>

    <div class="container">
        <!-- Main Content Area -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <!-- Dynamic Sections -->
                <?php if (!empty($landing['sections'])): ?>
                    <?php foreach ($landing['sections'] as $section): ?>
                        
                        <!-- Text/Intro Section -->
                        <?php if ($section['type'] === 'intro' || $section['type'] === 'text'): ?>
                            <div class="sl-section intro-section">
                                <?php if (!empty($section['title'])): ?>
                                    <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <?php endif; ?>
                                <div class="sl-content">
                                    <?php echo $section['content']; // Assumed to be safe HTML from DB ?>
                                </div>
                                
                                <?php if (isset($section['brand_slot'])): ?>
                                    <?php 
                                    $brand_super_code = $get_super_for_brand($section['brand_slot']);
                                    if ($brand_super_code): ?>
                                        <div class="targeted-super-slot mt-4 mb-4">
                                             <div class="super-featured-header mb-3" style="position: static; margin-bottom: 20px;">
                                                 <i class="fas fa-star text-warning"></i> Recomendado para <?php echo htmlspecialchars($section['brand_slot_name'] ?? $section['brand_slot']); ?>
                                             </div>
                                             <?php echo generate_modern_code_cards([$brand_super_code]); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        
                        <!-- Pros & Cons Section -->
                        <?php elseif ($section['type'] === 'pros_cons'): ?>
                            <div class="sl-section pros-cons-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="pros-cons-grid">
                                    <div class="pro-card">
                                <h4 class="text-success"><i class="fas fa-thumbs-up"></i> Lo bueno</h4>
                                <ul>
                                    <?php foreach ($section['items']['pros'] as $pro): ?>
                                        <li><i class="fas fa-check text-success"></i> <?php echo $pro; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="con-card">
                                <h4 class="text-danger"><i class="fas fa-thumbs-down"></i> Lo mejorable</h4>
                                <ul>
                                    <?php foreach ($section['items']['cons'] as $con): ?>
                                        <li><i class="fas fa-times text-danger"></i> <?php echo $con; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <style>
                            .pros-cons-grid {
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 20px;
                            }
                            .pro-card, .con-card {
                                padding: 25px;
                                border-radius: 12px;
                                color: #333; /* Explicit dark text for contrast */
                            }
                            .pro-card {
                                background-color: #f0f9f0;
                                border: 1px solid #d0e9d0;
                            }
                            .con-card {
                                background-color: #fff5f5;
                                border: 1px solid #fadbd8;
                            }
                            .pro-card h4, .con-card h4 {
                                margin-top: 0;
                                margin-bottom: 20px;
                                font-weight: 700;
                            }
                            .pro-card ul, .con-card ul {
                                list-style: none;
                                padding: 0;
                                margin: 0;
                            }
                            .pro-card li, .con-card li {
                                margin-bottom: 12px;
                                position: relative;
                                padding-left: 0;
                                display: flex;
                                align-items: flex-start;
                                gap: 10px;
                                line-height: 1.5;
                                color: #444; /* Dark gray for list items */
                            }
                            .pro-card i, .con-card i {
                                margin-top: 4px; /* Align icon with text */
                            }
                            @media (max-width: 768px) {
                                .pros-cons-grid { grid-template-columns: 1fr; }
                            }
                        </style>
                    </div>

                        <!-- Steps Section -->
                        <?php elseif ($section['type'] === 'steps'): ?>
                            <div class="sl-section steps-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="steps-timeline">
                                    <?php foreach ($section['steps'] as $index => $step): ?>
                                        <div class="step-item">
                                            <div class="step-number"><?php echo $index + 1; ?></div>
                                            <div class="step-content">
                                                <h4><?php echo htmlspecialchars($step['title']); ?></h4>
                                                <p><?php echo htmlspecialchars($step['text']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        <!-- FAQ Section -->
                        <?php elseif ($section['type'] === 'faq'): ?>
                            <div class="sl-section faq-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="faq-list">
                                    <?php foreach ($section['faqs'] as $faq): ?>
                                        <div class="faq-item">
                                            <div class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></div>
                                            <div class="faq-answer"><?php echo htmlspecialchars($faq['answer']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                <?php endif; ?>

                <?php 
                // AdSense Entremedio
                if (function_exists('get_adsense_entremedio')) {
                    echo generate_adsense_container(get_adsense_entremedio(), 'adsense-sl-middle', 'margin-top: 20px; margin-bottom: 40px;');
                }
                ?>

                <!-- Active Codes Section - Hero Layout -->
                <?php if (!empty($all_codigos)): ?>
                    <div class="sl-section codes-section" id="codigos-activos">
                        <h2 class="sl-section-title">Código Exclusivo del Editor</h2>
                        
                        <?php 
                        // Render each code as a full-width hero card
                        foreach ($all_codigos as $codigo):
                            $brand = isset($codigo['marca']) ? $codigo['marca'] : '';
                            $description = isset($codigo['descripcion']) ? strip_tags($codigo['descripcion']) : '';
                            $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
                            $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
                            $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
                            $tipo_descuento = isset($codigo['tipo_descuento']) ? $codigo['tipo_descuento'] : 'euros';
                            
                            // Get user info
                            $user_info = get_user_info($usuario_id);
                            $username = $user_info['username'];
                            $user_img = $user_info['img'];
                            $user_id_str = isset($user_info['id']) ? (string)$user_info['id'] : (string)$usuario_id;
                            
                            // VIP check
                            if (!function_exists('es_usuario_vip')) {
                                include_once __DIR__ . '/../funciones_usuario.php';
                            }
                            $es_vip = es_usuario_vip($usuario_id);
                            
                            // Brand info
                            $marca_info = get_brand_info($brand);
                            $marca_imagen = $marca_info['imagen'] ?? '';
                            $brand_slug = $marca_info['nombre_clave'] ?? generate_brand_slug($brand);
                            
                            // User link
                            $user_link = link_usuario($username, $user_id_str);
                            
                            // Chat visibility
                            $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
                            $show_chat = !empty($user_id_str) && (string)$current_user_id !== (string)$user_id_str;
                        ?>
                        
                        <div class="guide-hero-card">
                            <!-- Top ribbon -->
                            <div class="guide-hero-ribbon">
                                <i class="fas fa-crown"></i> Recomendado por el Editor de la Guía
                            </div>
                            
                            <div class="guide-hero-content">
                                <!-- Left: Brand + User -->
                                <div class="guide-hero-left">
                                    <!-- Brand logo -->
                                    <div class="guide-hero-brand">
                                        <a href="/de-<?php echo htmlspecialchars($brand_slug); ?>" title="Códigos <?php echo htmlspecialchars($brand); ?>">
                                            <?php if ($marca_imagen): ?>
                                                <img src="<?php echo htmlspecialchars($marca_imagen); ?>" alt="<?php echo htmlspecialchars($brand); ?>" class="guide-hero-brand-img">
                                            <?php else: ?>
                                                <div class="guide-hero-brand-placeholder"><i class="fas fa-tag"></i></div>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    
                                    <!-- Author section -->
                                    <div class="guide-hero-author">
                                        <div class="guide-hero-avatar">
                                            <?php if($user_img): ?>
                                                <img src="<?php echo htmlspecialchars($user_img); ?>" alt="<?php echo htmlspecialchars($username); ?>">
                                            <?php else: ?>
                                                <div class="guide-hero-avatar-placeholder">
                                                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="guide-hero-author-info">
                                            <a href="<?php echo htmlspecialchars($user_link); ?>" class="guide-hero-author-name">
                                                <?php echo htmlspecialchars($username); ?>
                                            </a>
                                            <?php if ($es_vip): ?>
                                                <span class="guide-hero-vip"><i class="fas fa-crown"></i> VIP Verificado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Center: Description -->
                                <div class="guide-hero-center">
                                    <div class="guide-hero-description">
                                        <?php echo htmlspecialchars($description); ?>
                                    </div>
                                    
                                    <?php if ($benefit > 0): ?>
                                        <div class="guide-hero-benefit">
                                            <div class="guide-hero-benefit-icon">💰</div>
                                            <div class="guide-hero-benefit-info">
                                                <?php
                                                if (!function_exists('generarHTMLPrecioConPromocion')) {
                                                    include_once __DIR__ . '/../funciones_premium.php';
                                                }
                                                echo generarHTMLPrecioConPromocion($code_id, $benefit, $tipo_descuento, false);
                                                ?>
                                                <div class="guide-hero-benefit-label">BENEFICIO GARANTIZADO</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Right: Actions -->
                                <div class="guide-hero-actions">
                                    <button class="guide-hero-btn-code" onclick="viewCode('<?php echo htmlspecialchars($code_id); ?>', '<?php echo htmlspecialchars($brand_slug); ?>')">
                                        <i class="fas fa-eye"></i> Ver Código
                                    </button>
                                    
                                        <button class="guide-hero-btn-chat" onclick="openDirectChat('<?php echo htmlspecialchars($user_id_str); ?>', '<?php echo htmlspecialchars($username); ?>')">
                                            <i class="fas fa-comments"></i> Ponte en contacto con <?php echo htmlspecialchars(explode(' ', $username)[0]); ?>
                                        </button>
                                        <p class="guide-hero-chat-hint">
                                            <i class="fas fa-info-circle"></i> Habla directamente con <?php echo htmlspecialchars(explode(' ', $username)[0]); ?> para que te ayude paso a paso
                                        </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                    </div>
                    
                    <style>
                        .guide-hero-card {
                            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
                            border: 2px solid rgba(255, 215, 0, 0.3);
                            border-radius: 20px;
                            overflow: hidden;
                            position: relative;
                            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 30px rgba(227, 6, 19, 0.1);
                        }
                        
                        .guide-hero-ribbon {
                            background: linear-gradient(135deg, #E30613, #ff4757);
                            color: white;
                            text-align: center;
                            padding: 10px 20px;
                            font-weight: 700;
                            font-size: 0.95rem;
                            letter-spacing: 0.5px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                        }
                        
                        .guide-hero-ribbon i {
                            color: #ffd700;
                        }
                        
                        .guide-hero-content {
                            display: grid;
                            grid-template-columns: 220px 1fr 280px;
                            gap: 0;
                            padding: 30px;
                        }
                        
                        /* LEFT - Brand + Author */
                        .guide-hero-left {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 20px;
                            padding-right: 25px;
                            border-right: 1px solid rgba(255,255,255,0.1);
                        }
                        
                        .guide-hero-brand {
                            background: white;
                            border-radius: 16px;
                            padding: 15px;
                            width: 160px;
                            height: 100px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                        }
                        
                        .guide-hero-brand-img {
                            max-width: 130px;
                            max-height: 70px;
                            object-fit: contain;
                        }
                        
                        .guide-hero-brand-placeholder {
                            font-size: 2.5rem;
                            color: #E30613;
                        }
                        
                        .guide-hero-author {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 8px;
                        }
                        
                        .guide-hero-avatar {
                            width: 56px;
                            height: 56px;
                            border-radius: 50%;
                            overflow: hidden;
                            border: 3px solid #ffd700;
                            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
                        }
                        
                        .guide-hero-avatar img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }
                        
                        .guide-hero-avatar-placeholder {
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(135deg, #E30613, #ff6b6b);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 1.4rem;
                            font-weight: 700;
                        }
                        
                        .guide-hero-author-info {
                            text-align: center;
                        }
                        
                        .guide-hero-author-name {
                            color: #fff;
                            font-weight: 700;
                            font-size: 1rem;
                            text-decoration: none;
                            display: block;
                        }
                        
                        .guide-hero-author-name:hover {
                            color: #ffd700;
                        }
                        
                        .guide-hero-vip {
                            display: inline-flex;
                            align-items: center;
                            gap: 4px;
                            background: linear-gradient(135deg, #ffd700, #ffab00);
                            color: #1a1a2e;
                            padding: 3px 10px;
                            border-radius: 12px;
                            font-size: 0.7rem;
                            font-weight: 800;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            margin-top: 4px;
                        }
                        
                        /* CENTER - Description + Benefit */
                        .guide-hero-center {
                            padding: 0 25px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            gap: 18px;
                        }
                        
                        .guide-hero-description {
                            color: rgba(255,255,255,0.9);
                            font-size: 1.05rem;
                            line-height: 1.7;
                        }
                        
                        .guide-hero-benefit {
                            display: flex;
                            align-items: center;
                            gap: 14px;
                            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.15));
                            border: 1px solid rgba(40, 167, 69, 0.4);
                            padding: 14px 18px;
                            border-radius: 14px;
                        }
                        
                        .guide-hero-benefit-icon {
                            font-size: 2rem;
                        }
                        
                        .guide-hero-benefit-info {
                            display: flex;
                            flex-direction: column;
                            gap: 2px;
                        }
                        
                        .guide-hero-benefit-info .beneficio-cantidad,
                        .guide-hero-benefit-info .precio-con-promocion {
                            color: #4ade80;
                            font-size: 1.5rem;
                            font-weight: 800;
                        }
                        
                        .guide-hero-benefit-label {
                            color: rgba(255,255,255,0.6);
                            font-size: 0.7rem;
                            font-weight: 700;
                            letter-spacing: 1.5px;
                            text-transform: uppercase;
                        }
                        
                        /* RIGHT - Actions */
                        .guide-hero-actions {
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            gap: 12px;
                            padding-left: 25px;
                            border-left: 1px solid rgba(255,255,255,0.1);
                        }
                        
                        .guide-hero-btn-code {
                            background: linear-gradient(135deg, #E30613, #ff4757);
                            color: white;
                            border: none;
                            border-radius: 14px;
                            padding: 16px 24px;
                            font-weight: 800;
                            font-size: 1.1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 10px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
                            animation: heroButtonPulse 2s infinite;
                        }
                        
                        .guide-hero-btn-code:hover {
                            transform: translateY(-3px);
                            box-shadow: 0 10px 30px rgba(227, 6, 19, 0.5);
                        }
                        
                        @keyframes heroButtonPulse {
                            0%, 100% { box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4); }
                            50% { box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4), 0 0 0 8px rgba(227, 6, 19, 0); }
                        }
                        
                        .guide-hero-btn-chat {
                            background: linear-gradient(135deg, #1f8ef1, #6c5ce7);
                            color: white;
                            border: none;
                            border-radius: 14px;
                            padding: 14px 24px;
                            font-weight: 700;
                            font-size: 1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 10px;
                        }
                        
                        .guide-hero-btn-chat:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(31, 142, 241, 0.4);
                            filter: brightness(1.1);
                        }
                        
                        .guide-hero-chat-hint {
                            color: rgba(255,255,255,0.5);
                            font-size: 0.78rem;
                            text-align: center;
                            margin: 0;
                            line-height: 1.4;
                        }
                        
                        .guide-hero-chat-hint i {
                            color: rgba(255,255,255,0.35);
                        }
                        
                        /* Mobile responsive */
                        @media (max-width: 992px) {
                            .guide-hero-content {
                                grid-template-columns: 1fr;
                                gap: 20px;
                                padding: 20px;
                            }
                            
                            .guide-hero-left {
                                flex-direction: row;
                                border-right: none;
                                border-bottom: 1px solid rgba(255,255,255,0.1);
                                padding-right: 0;
                                padding-bottom: 20px;
                                justify-content: center;
                            }
                            
                            .guide-hero-center {
                                padding: 0;
                            }
                            
                            .guide-hero-actions {
                                border-left: none;
                                border-top: 1px solid rgba(255,255,255,0.1);
                                padding-left: 0;
                                padding-top: 20px;
                            }
                        }
                        
                        @media (max-width: 480px) {
                            .guide-hero-left {
                                flex-direction: column;
                            }
                            .guide-hero-brand {
                                width: 120px;
                                height: 70px;
                                padding: 10px;
                            }
                            .guide-hero-brand-img {
                                max-width: 100px;
                                max-height: 50px;
                            }
                        }
                    </style>
                <?php endif; ?>
                <?php if (empty($all_codigos)): ?>
                    <div class="sl-section no-codes text-center">
                         <div class="empty-state-card">
                            <i class="fas fa-trophy empty-icon"></i>
                            <h3>¡Sé el primero en aparecer aquí!</h3>
                            <p>Esta guía es visitada por miles de usuarios buscando códigos. <br>Publica el tuyo ahora y comienza a ganar referidos.</p>
                            <a href="/nuevo_codigo?marca_preselected=<?php echo htmlspecialchars($landing['linked_brand_slugs'][0] ?? $landing['slug']); ?>" class="btn btn-primary btn-lg pulse-button">
                                <i class="fas fa-plus-circle"></i> Publicar mi código GRATIS
                            </a>
                            <p class="small text-muted mt-3"><i class="fas fa-check"></i> Registro en 1 minuto <i class="fas fa-check"></i> Sin coste</p>
                         </div>
                    </div>
                <?php endif; ?>

                <style>
                    /* Promo Card styles */
                    .promo-card-grid {
                        background: #fff5f5;
                        border: 2px dashed #E30613 !important;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        min-height: 250px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }
                    .promo-card-grid:hover {
                        background: #ffebeb;
                        transform: translateY(-5px);
                        box-shadow: 0 5px 15px rgba(227, 6, 19, 0.15);
                    }
                    .promo-icon {
                        font-size: 3rem;
                        color: #E30613;
                        margin-bottom: 15px;
                        opacity: 0.5;
                    }
                    .promo-card-grid:hover .promo-icon {
                        opacity: 1;
                        transform: scale(1.1);
                    }
                    .promo-content h3 {
                        font-size: 1.2rem;
                        font-weight: bold;
                        margin-bottom: 10px;
                        color: #333;
                    }
                    .promo-content p {
                        font-size: 0.9rem;
                        color: #666;
                        margin-bottom: 15px;
                    }

                    /* Empty State Styles */
                    .empty-state-card {
                        background: white;
                        padding: 60px 20px;
                        border-radius: 20px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                        border: 1px solid #eee;
                        max-width: 600px;
                        margin: 0 auto;
                    }
                    .empty-icon {
                        font-size: 5rem;
                        color: #E30613;
                        margin-bottom: 25px;
                        text-shadow: 0 5px 15px rgba(227, 6, 19, 0.2);
                        animation: float 3s ease-in-out infinite;
                    }
                    .empty-state-card h3 {
                        font-size: 2rem;
                        font-weight: 800;
                        margin-bottom: 15px;
                        color: #333;
                    }
                    .empty-state-card p {
                        font-size: 1.1rem;
                        color: #666;
                        margin-bottom: 30px;
                        line-height: 1.6;
                    }
                    .pulse-button {
                        background: #E30613;
                        border: none;
                        padding: 15px 40px;
                        border-radius: 50px;
                        font-weight: bold;
                        font-size: 1.1rem;
                        box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
                        transition: all 0.3s ease;
                    }
                    .pulse-button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
                        background: #c90511;
                    }
                    @keyframes float {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-10px); }
                        100% { transform: translateY(0px); }
                    }
                    
                    /* Ensure grid responsiveness for promo card */
                    .codes-grid-wrapper {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                        gap: 20px;
                    }
                    /* Inherit card styles if possible, otherwise rely on local */
                </style>

            </div>
        </div>
    </div>
</div>
