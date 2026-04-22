<?php
// Headers para evitar caché durante desarrollo
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// Nueva plantilla moderna basada en Referral Codes
get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

// Incluir funciones modernas para el sistema de encabezados
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Incluir funciones de Chollometro
include_once __DIR__ . '/../myphp/funciones_chollometro.php';

global $detect_device, $codigo_existente, $u, $marca, $url_logo_rs;

$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

// Asegurar que url_logo_rs esté definida
if (!isset($url_logo_rs) || empty($url_logo_rs)) {
    $url_logo_rs = $marca['imagen'] ?? '/img/no_image.png';
}

// Manejar diferentes tipos de URLs de imagen
if (!empty($url_logo_rs)) {
    // Si es una URL de CloudFront, usarla directamente
    if (strpos($url_logo_rs, 'cloudfront.net') !== false || strpos($url_logo_rs, 'codigoamigo.com') !== false) {
        // Ya es una URL completa, no hacer nada
    }
    // Si es una ruta local, convertir a URL completa
    elseif (!str_starts_with($url_logo_rs, 'http')) {
        $url_logo_rs = 'https://www.codigoamigo.com' . $url_logo_rs;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $description; ?>">
    
    <!-- CSS Moderno -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/modern-design.css?v=<?php echo file_exists(__DIR__ . '/../css/modern-design.css') ? filemtime(__DIR__ . '/../css/modern-design.css') : time(); ?>" rel="stylesheet">
    <link href="/assets/css/chollometro-style.css" rel="stylesheet">
    
    <script>
    // Función para toggle del brand sidebar - definida globalmente
    window.toggleBrandSidebar = function() {
        console.log('toggleBrandSidebar llamada desde marca_moderna.php');
        
        const filtersSection = document.querySelector('.brand-sidebar .filters-section');
        const toggleBtn = document.querySelector('.brand-sidebar .filter-toggle-btn');
        
        console.log('filtersSection:', filtersSection);
        console.log('toggleBtn:', toggleBtn);
        
        if (filtersSection && toggleBtn) {
            console.log('Elementos encontrados, cambiando estado...');
            
            if (filtersSection.classList.contains('show')) {
                console.log('Cerrando sidebar...');
                filtersSection.classList.remove('show');
                toggleBtn.classList.remove('open');
            } else {
                console.log('Abriendo sidebar...');
                filtersSection.classList.add('show');
                toggleBtn.classList.add('open');
            }
            
            console.log('Estado actual - show:', filtersSection.classList.contains('show'));
            console.log('Estado actual - open:', toggleBtn.classList.contains('open'));
        } else {
            console.error('No se encontraron los elementos necesarios');
            console.log('filtersSection:', filtersSection);
            console.log('toggleBtn:', toggleBtn);
        }
    };
    
    // También definir como función global tradicional
    function toggleBrandSidebar() {
        return window.toggleBrandSidebar();
    }
    
    // Verificar que la función esté disponible
    console.log('toggleBrandSidebar disponible al inicio:', typeof toggleBrandSidebar);
    console.log('window.toggleBrandSidebar disponible:', typeof window.toggleBrandSidebar);
    </script>
    
    <style>
        /* Estilos específicos de la página */
        body {
            --brand-orange: #ff7a18;
            --brand-orange-dark: #ff4f0f;
            --brand-charcoal: #201a2b;
            --brand-cream: #fff3e8;
        }

        .brand-header {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--brand-orange) 0%, var(--brand-orange-dark) 100%);
            color: #fff;
            box-shadow: 0 12px 45px -20px rgba(255, 79, 15, 0.65);
        }

        .brand-header::before,
        .brand-header::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            filter: blur(0);
            z-index: 0;
        }

        .brand-header::before {
            width: 360px;
            height: 360px;
            top: -140px;
            right: -120px;
        }

        .brand-header::after {
            width: 220px;
            height: 220px;
            bottom: -90px;
            left: -60px;
            background: rgba(255, 178, 124, 0.22);
        }

        .brand-header > * {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            background: rgba(255, 255, 255, 0.18);
            padding: 18px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 18px 35px -25px rgba(32, 26, 43, 0.8);
        }

        .brand-logo img {
            filter: drop-shadow(0 12px 18px rgba(32, 26, 43, 0.35));
        }

        .rating-section .stars i {
            color: #ffe8c9;
        }

        .rating-section .rating-text {
            color: rgba(255, 255, 255, 0.85);
        }

        .brand-stats {
            gap: 18px;
        }

        .brand-stats .stat-item {
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 14px;
            box-shadow: 0 14px 32px -18px rgba(32, 26, 43, 0.75);
        }

        .brand-stats .stat-number {
            color: #fff;
        }

        .brand-stats .stat-label {
            color: rgba(255, 243, 232, 0.8);
        }

        .brand-description-short {
            background: rgba(255, 255, 255, 0.95);
            border-left: 4px solid var(--brand-orange);
        }

        .brand-description-short h3 {
            color: var(--brand-charcoal);
        }

        .brand-description-short p {
            color: #374151;
        }

        .brand-description-long {
            border-left-color: var(--brand-orange);
        }

        .tabs .tab.active,
        .tabs .tab:hover {
            background: linear-gradient(135deg, var(--brand-orange) 0%, var(--brand-orange-dark) 100%);
            color: #fff;
            box-shadow: 0 10px 25px -18px rgba(255, 79, 15, 0.85);
        }

        .chollometro-tabs .tab-button.active,
        .chollometro-tabs .tab-button:hover {
            background: linear-gradient(135deg, var(--brand-orange) 0%, var(--brand-orange-dark) 100%);
            color: #fff;
        }

        .btn-primary,
        .btn-publish,
        .header .btn-primary {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            border: none;
            color: #fff;
            box-shadow: 0 12px 24px -16px rgba(255, 79, 15, 0.85);
        }

        .btn-primary:hover,
        .btn-publish:hover,
        .header .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-orange-dark), #e63f00);
        }

        .btn-outline:hover {
            border-color: var(--brand-orange);
            color: var(--brand-orange);
        }

        .publish-card {
            border: 1px solid rgba(255, 122, 24, 0.18);
        }

        .publish-icon {
            background: rgba(255, 122, 24, 0.1);
            color: var(--brand-orange);
        }

        .publish-benefits .benefit-item i {
            color: var(--brand-orange);
        }

        .cta-button .btn {
            background: linear-gradient(135deg, #ffd167 0%, var(--brand-orange) 100%);
        }
        
        .code-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .code-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Estilos para el menú de propietario */
        .owner-menu {
            position: relative;
            display: inline-block;
            margin-right: 10px;
        }

        .owner-menu-toggle {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 6px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .owner-menu-toggle:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        .owner-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 160px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .owner-menu-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .owner-menu-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .owner-menu-item:hover {
            background: #f3f4f6;
        }

        .owner-menu-item:first-child {
            border-radius: 8px 8px 0 0;
        }

        .owner-menu-item:last-child {
            border-radius: 0 0 8px 8px;
        }

        .owner-menu-item.edit:hover {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .owner-menu-item.delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .owner-menu-item i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        /* Estilos para descripciones de marca */
        .brand-description-short {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .brand-description-short h3 {
            color: #1e40af;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .brand-description-short p {
            color: #374151;
            font-size: 1.1rem;
            line-height: 1.6;
            text-align: center;
            margin: 0;
        }
        
        .brand-description-long {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .brand-description-long h3 {
            color: #1e40af;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .brand-description-long .description-content {
            color: #374151;
            font-size: 1rem;
            line-height: 1.7;
            text-align: justify;
        }
        
        .brand-description-long .description-content p {
            margin-bottom: 15px;
        }
        
        .brand-description-long .description-content h1,
        .brand-description-long .description-content h2,
        .brand-description-long .description-content h3,
        .brand-description-long .description-content h4 {
            color: #1e40af;
            margin: 20px 0 10px 0;
        }
        
        .brand-description-long .description-content ul,
        .brand-description-long .description-content ol {
            margin: 15px 0;
            padding-left: 25px;
        }
        
        .brand-description-long .description-content li {
            margin-bottom: 8px;
        }
        
        .codes-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            text-align: center;
            margin: 10px 0 20px 0;
            font-weight: 500;
        }
        
        /* Estilos específicos para Lixsa */
        .lixsa-benefits {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 25px 0;
            color: white;
        }
        
        .lixsa-benefits h3 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .benefit-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .benefit-card i {
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 15px;
            display: block;
        }
        
        .benefit-card h4 {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .benefit-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }
        
        /* Responsive para móviles */
        @media (max-width: 768px) {
            .lixsa-benefits {
                padding: 20px;
                margin: 20px 0;
            }
            
            .lixsa-benefits h3 {
                font-size: 1.5rem;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .benefit-card {
                padding: 15px;
            }
            
            .benefit-card i {
                font-size: 2rem;
            }
        }
        
        /* Estilos para CTA de Lixsa */
        .lixsa-cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .lixsa-cta .sidebar-title {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .lixsa-cta-content p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .lixsa-features {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .lixsa-features li {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .cta-button {
            text-align: center;
            margin-top: 20px;
        }
        
        .cta-button .btn {
            background: #ffd700;
            color: #333;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .cta-button .btn:hover {
            background: #ffed4e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        /* Responsive para móviles */
        @media (max-width: 768px) {
            .brand-description-short {
                padding: 15px;
                margin: 15px 0;
            }
            
            .brand-description-short h3 {
                font-size: 1.3rem;
            }
            
            .brand-description-short p {
                font-size: 1rem;
            }
            
            .brand-description-long {
                padding: 20px;
                margin: 20px 0;
            }
            
            .brand-description-long h3 {
                font-size: 1.5rem;
            }
            
            .brand-description-long .description-content {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <i class="fas fa-gift"></i> CÓDIGO AMIGO
                </a>
                
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Buscar códigos de descuento...">
                </div>
                
                <div class="header-actions">
                    <a href="/compartir" class="btn btn-outline">
                        <i class="fas fa-share"></i> Compartir
                    </a>
                    <a href="/login" class="btn btn-primary">
                        <i class="fas fa-user"></i> Iniciar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="main-content">
            <!-- Left Column -->
            <div class="content-left fade-in-up">
                <!-- Brand Header -->
                <div class="brand-header">
                    <div class="brand-logo">
                        <img src="<?php echo $url_logo_rs; ?>" alt="<?php echo $marca['nombre']; ?>">
                    </div>
                    <?php
                    // Usar el nuevo sistema de encabezados jerárquicos
                    HeaderManager::reset();
                    echo generatePageHeader($marca['nombre'] . ' - Códigos de Descuento y Ofertas 2025', '¡Ahorra hasta 50€ con los mejores códigos promocionales y cupones de descuento!');
                    ?>
                    
                    <div class="rating-section">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="rating-text">5.0 (<?php echo $numero_codigos_format; ?> códigos)</span>
                    </div>
                    
                    <div class="brand-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $numero_codigos_format; ?></span>
                            <span class="stat-label">Códigos Disponibles</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">€<?php echo rand(10, 50); ?></span>
                            <span class="stat-label">Ahorro Promedio</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(85, 98); ?>%</span>
                            <span class="stat-label">Tasa de Éxito</span>
                        </div>
                    </div>
                    
                    <!-- Texto corto de la marca -->
                    <?php if(isset($marca["descripción"]) && !empty($marca["descripción"])): ?>
                    <div class="brand-description-short">
                        <?php echo generateMainSection('¿Qué es ' . $marca['nombre'] . '?', $marca["descripción"]); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Sección específica para Lixsa -->
                    <?php if($marca["nombre_clave"] == 'lixsaai'): ?>
                    <div class="lixsa-benefits">
                        <?php echo generateMainSection('🚀 ¿Por qué elegir Lixsa.ai para tu negocio?'); ?>
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <i class="fas fa-robot"></i>
                                <h4>IA Avanzada</h4>
                                <p>Chatbots inteligentes con procesamiento de lenguaje natural de última generación</p>
                            </div>
                            <div class="benefit-card">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>eCommerce Ready</h4>
                                <p>Integración completa con las principales plataformas de comercio electrónico</p>
                            </div>
                            <div class="benefit-card">
                                <i class="fas fa-clock"></i>
                                <h4>24/7 Disponible</h4>
                                <p>Atención al cliente automática las 24 horas del día, los 7 días de la semana</p>
                            </div>
                            <div class="benefit-card">
                                <i class="fas fa-chart-line"></i>
                                <h4>ROI Comprobado</h4>
                                <p>Reduce costos operativos hasta un 70% y aumenta las conversiones</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="description-section">
                    <div class="description-text">
                        <?php show_short_desc($marca); ?>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" data-tab="codes">
                        <i class="fas fa-ticket-alt"></i> Códigos de Descuento
                    </button>
                    <button class="tab" data-tab="recommendations">
                        <i class="fas fa-heart"></i> Recomendaciones
                    </button>
                </div>

                <!-- Publish Code Section -->
                <?php if (!filter_input(INPUT_GET, "codigo", FILTER_SANITIZE_STRING) && $marca["nombre_clave"] != 'bookingcom'): ?>
                <div class="publish-section">
                    <div class="publish-card">
                        <div class="publish-content">
                            <div class="publish-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h3>¿Tienes un código de descuento de <?php echo $marca["nombre"]; ?>?</h3>
                            <p><?php if($marca["nombre_clave"] == 'lixsaai'): ?>
                                Comparte tu código promocional de Lixsa.ai y ayuda a otros emprendedores a ahorrar en servicios de IA
                            <?php else: ?>
                                Comparte tu código con la comunidad y ayuda a otros usuarios a ahorrar dinero
                            <?php endif; ?></p>
                            <div class="publish-benefits">
                                <div class="benefit-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Ayuda a la comunidad</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-star"></i>
                                    <span>Destaca tu código</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-users"></i>
                                    <span>Gana visibilidad</span>
                                </div>
                            </div>
                            <?php if (!empty($_SESSION["user_id"])): ?>
                                <a href="<?php echo link_nuevo_codigo(); ?>?marca=<?php echo $marca["nombre"]; ?>" class="btn-publish">
                                    <i class="fas fa-plus"></i>
                                    Publicar mi código ahora
                                </a>
                            <?php else: ?>
                                <button class="btn-publish open_modal_login">
                                    <i class="fas fa-plus"></i>
                                    Publicar mi código ahora
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="publish-visual">
                            <img src="<?php echo $marca["imagen"]; ?>" alt="<?php echo $marca["nombre"]; ?>" class="publish-logo">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ============================================ -->
                <!-- SISTEMA "OBTENER MI CÓDIGO" -->
                <!-- ============================================ -->
                
                <?php
                // Calcular total de códigos disponibles para esta marca
                $total_codigos_marca = count($lista_codigos ?? []) + count($lista_codigos_patrocinados ?? []);
                
                // Determinar el mejor beneficio para mostrar en el CTA
                $mejor_beneficio_display = '';
                $all_codes_temp = array_merge($lista_codigos_patrocinados ?? [], $lista_codigos ?? []);
                $max_euros = 0;
                $max_descuento = 0;
                foreach ($all_codes_temp as $ct) {
                    if (is_object($ct)) $ct = (array)$ct;
                    $nb = floatval($ct['num_beneficio'] ?? 0);
                    $td = $ct['tipo_descuento'] ?? '';
                    if ($td === '% de descuento' && $nb > $max_descuento) $max_descuento = $nb;
                    elseif ($nb > $max_euros) $max_euros = $nb;
                }
                if ($max_euros > 0) {
                    $mejor_beneficio_display = $max_euros . '€';
                } elseif ($max_descuento > 0) {
                    $mejor_beneficio_display = $max_descuento . '% dto.';
                }
                ?>
                
                <style>
                    /* ===== Obtener Código Widget ===== */
                    .obtener-codigo-widget {
                        background: white;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
                        margin-bottom: 24px;
                    }
                    
                    .obtener-header {
                        background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8e 60%, #3b82f6 100%);
                        padding: 28px 24px;
                        text-align: center;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .obtener-header::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        right: -20%;
                        width: 200px;
                        height: 200px;
                        background: rgba(255,255,255,0.06);
                        border-radius: 50%;
                    }
                    
                    .obtener-header h3 {
                        color: white;
                        font-size: 1.4rem;
                        font-weight: 700;
                        margin: 0 0 6px 0;
                        position: relative;
                    }
                    
                    .obtener-header p {
                        color: rgba(255,255,255,0.8);
                        font-size: 0.95rem;
                        margin: 0;
                        position: relative;
                    }
                    
                    .obtener-beneficio-badge {
                        display: inline-block;
                        background: linear-gradient(135deg, #fbbf24, #f59e0b);
                        color: #1e3a5f;
                        font-weight: 800;
                        font-size: 1.3rem;
                        padding: 8px 20px;
                        border-radius: 50px;
                        margin: 12px 0 4px;
                        position: relative;
                        box-shadow: 0 4px 15px rgba(245,158,11,0.3);
                    }
                    
                    .obtener-body {
                        padding: 28px 24px;
                        text-align: center;
                    }
                    
                    /* CTA Button */
                    .btn-obtener-codigo {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                        border: none;
                        padding: 16px 40px;
                        font-size: 1.15rem;
                        font-weight: 700;
                        border-radius: 12px;
                        cursor: pointer;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: 0 6px 20px rgba(16,185,129,0.35);
                        width: 100%;
                        max-width: 380px;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .btn-obtener-codigo::after {
                        content: '';
                        position: absolute;
                        top: 0; left: -100%;
                        width: 100%; height: 100%;
                        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                        transition: left 0.5s;
                    }
                    
                    .btn-obtener-codigo:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 30px rgba(16,185,129,0.45);
                    }
                    
                    .btn-obtener-codigo:hover::after {
                        left: 100%;
                    }
                    
                    .btn-obtener-codigo:active {
                        transform: translateY(-1px);
                    }
                    
                    .btn-obtener-codigo.loading {
                        opacity: 0.8;
                        pointer-events: none;
                    }
                    
                    .obtener-meta {
                        margin-top: 14px;
                        color: #6b7280;
                        font-size: 0.85rem;
                    }
                    
                    .obtener-meta i {
                        color: #10b981;
                        margin-right: 4px;
                    }
                    
                    /* ===== Code Reveal Section ===== */
                    .codigo-revelado {
                        display: none;
                        animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    }
                    
                    .codigo-revelado.show {
                        display: block;
                    }
                    
                    @keyframes slideDown {
                        from { opacity: 0; transform: translateY(-15px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .codigo-resultado {
                        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
                        border: 2px solid #10b981;
                        border-radius: 14px;
                        padding: 24px;
                        margin-top: 20px;
                    }
                    
                    .codigo-valor-container {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 12px;
                        margin-bottom: 16px;
                    }
                    
                    .codigo-valor {
                        background: white;
                        border: 2px dashed #10b981;
                        border-radius: 10px;
                        padding: 14px 24px;
                        font-family: 'Courier New', monospace;
                        font-size: 1.5rem;
                        font-weight: 700;
                        color: #1e3a5f;
                        letter-spacing: 2px;
                        user-select: all;
                        flex: 1;
                        text-align: center;
                    }
                    
                    .btn-copiar-codigo {
                        background: linear-gradient(135deg, #3b82f6, #2563eb);
                        color: white;
                        border: none;
                        padding: 14px 20px;
                        border-radius: 10px;
                        cursor: pointer;
                        font-size: 1rem;
                        font-weight: 600;
                        transition: all 0.2s;
                        white-space: nowrap;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    }
                    
                    .btn-copiar-codigo:hover {
                        background: linear-gradient(135deg, #2563eb, #1d4ed8);
                        transform: scale(1.05);
                    }
                    
                    .btn-copiar-codigo.copied {
                        background: linear-gradient(135deg, #10b981, #059669);
                    }
                    
                    /* Publisher info */
                    .codigo-publisher {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 14px 0;
                        border-top: 1px solid #d1fae5;
                        margin-top: 12px;
                    }
                    
                    .publisher-avatar {
                        width: 42px;
                        height: 42px;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 2px solid #10b981;
                    }
                    
                    .publisher-info {
                        flex: 1;
                        text-align: left;
                    }
                    
                    .publisher-name {
                        font-weight: 600;
                        color: #1e3a5f;
                        font-size: 0.95rem;
                    }
                    
                    .trust-badge {
                        display: inline-flex;
                        align-items: center;
                        gap: 5px;
                        padding: 3px 10px;
                        border-radius: 20px;
                        font-size: 0.78rem;
                        font-weight: 600;
                    }
                    
                    .trust-premium { background: #fef3c7; color: #92400e; }
                    .trust-recommended { background: #d1fae5; color: #065f46; }
                    .trust-trusted { background: #dbeafe; color: #1e40af; }
                    .trust-verified { background: #f3f4f6; color: #374151; }
                    .trust-new { background: #f9fafb; color: #6b7280; }
                    
                    .trust-stars {
                        font-size: 0.75rem;
                    }
                    
                    .trust-stars .fas, .trust-stars .far {
                        margin: 0 1px;
                    }
                    
                    .codigo-descripcion {
                        color: #4b5563;
                        font-size: 0.9rem;
                        margin-top: 6px;
                        line-height: 1.4;
                    }
                    
                    /* Actions after reveal */
                    .codigo-acciones {
                        display: flex;
                        gap: 10px;
                        margin-top: 16px;
                        flex-wrap: wrap;
                        justify-content: center;
                    }
                    
                    .btn-prueba-otro {
                        background: white;
                        color: #6b7280;
                        border: 1px solid #d1d5db;
                        padding: 10px 18px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.88rem;
                        font-weight: 500;
                        transition: all 0.2s;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    }
                    
                    .btn-prueba-otro:hover {
                        background: #f9fafb;
                        border-color: #9ca3af;
                        color: #374151;
                    }
                    
                    /* ===== Ver Más Section ===== */
                    .ver-mas-section {
                        margin-top: 20px;
                    }
                    
                    .btn-ver-mas {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        width: 100%;
                        background: #f3f4f6;
                        color: #374151;
                        border: 1px solid #e5e7eb;
                        padding: 12px;
                        border-radius: 10px;
                        cursor: pointer;
                        font-size: 0.92rem;
                        font-weight: 500;
                        transition: all 0.2s;
                    }
                    
                    .btn-ver-mas:hover {
                        background: #e5e7eb;
                    }
                    
                    .ver-mas-lista {
                        display: none;
                        margin-top: 16px;
                    }
                    
                    .ver-mas-lista.show {
                        display: block;
                        animation: slideDown 0.4s ease;
                    }
                    
                    .ver-mas-item {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 14px 16px;
                        background: white;
                        border: 1px solid #e5e7eb;
                        border-radius: 10px;
                        margin-bottom: 8px;
                        transition: all 0.2s;
                    }
                    
                    .ver-mas-item:hover {
                        border-color: #3b82f6;
                        box-shadow: 0 2px 8px rgba(59,130,246,0.1);
                    }
                    
                    .ver-mas-avatar {
                        width: 36px;
                        height: 36px;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 1px solid #e5e7eb;
                    }
                    
                    .ver-mas-info {
                        flex: 1;
                        min-width: 0;
                    }
                    
                    .ver-mas-user {
                        font-weight: 600;
                        font-size: 0.85rem;
                        color: #1e3a5f;
                    }
                    
                    .ver-mas-benefit {
                        font-size: 0.8rem;
                        color: #10b981;
                        font-weight: 500;
                    }
                    
                    .ver-mas-trust {
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    }
                    
                    .btn-usar-este {
                        background: linear-gradient(135deg, #3b82f6, #2563eb);
                        color: white;
                        border: none;
                        padding: 8px 14px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 0.8rem;
                        font-weight: 600;
                        transition: all 0.2s;
                        white-space: nowrap;
                    }
                    
                    .btn-usar-este:hover {
                        transform: scale(1.05);
                    }
                    
                    /* Mobile responsive */
                    @media (max-width: 768px) {
                        .obtener-header { padding: 22px 18px; }
                        .obtener-header h3 { font-size: 1.2rem; }
                        .obtener-body { padding: 22px 16px; }
                        .btn-obtener-codigo { padding: 14px 24px; font-size: 1.05rem; }
                        .codigo-valor-container { flex-direction: column; }
                        .codigo-valor { font-size: 1.2rem; padding: 12px 16px; }
                        .btn-copiar-codigo { width: 100%; justify-content: center; }
                        .codigo-publisher { flex-wrap: wrap; }
                        .codigo-acciones { flex-direction: column; }
                        .btn-prueba-otro { width: 100%; justify-content: center; }
                    }
                </style>
                
                <div class="obtener-codigo-widget" id="obtenerCodigoWidget">
                    <!-- Header -->
                    <div class="obtener-header">
                        <h3><i class="fas fa-gift"></i> Códigos de <?php echo htmlspecialchars($marca["nombre"]); ?></h3>
                        <?php if ($mejor_beneficio_display): ?>
                            <div class="obtener-beneficio-badge">
                                Ahorra hasta <?php echo $mejor_beneficio_display; ?>
                            </div>
                        <?php endif; ?>
                        <p><?php echo $total_codigos_marca; ?> código<?php echo $total_codigos_marca != 1 ? 's' : ''; ?> disponible<?php echo $total_codigos_marca != 1 ? 's' : ''; ?></p>
                    </div>
                    
                    <!-- Body -->
                    <div class="obtener-body">
                        <!-- CTA Button (pre-reveal) -->
                        <div id="obtenerCTA">
                            <?php if ($total_codigos_marca > 0): ?>
                                <button class="btn-obtener-codigo" id="btnObtenerCodigo" data-marca="<?php echo htmlspecialchars($marca['nombre_clave']); ?>">
                                    <i class="fas fa-ticket-alt"></i>
                                    Obtener mi código
                                </button>
                                <div class="obtener-meta">
                                    <i class="fas fa-shield-alt"></i>
                                    Selección inteligente entre <?php echo $total_codigos_marca; ?> códigos verificados
                                </div>
                            <?php else: ?>
                                <div style="color: #6b7280; padding: 20px;">
                                    <i class="fas fa-info-circle"></i>
                                    No hay códigos disponibles en este momento.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Code Reveal (post-click) -->
                        <div class="codigo-revelado" id="codigoRevelado">
                            <div class="codigo-resultado" id="codigoResultado">
                                <!-- Filled by JS -->
                            </div>
                            
                            <div class="codigo-acciones">
                                <button class="btn-prueba-otro" id="btnPruebaOtro" data-marca="<?php echo htmlspecialchars($marca['nombre_clave']); ?>">
                                    <i class="fas fa-sync-alt"></i>
                                    ¿No funciona? Prueba otro
                                </button>
                            </div>
                        </div>
                        
                        <!-- Ver más códigos -->
                        <?php if ($total_codigos_marca > 1): ?>
                        <div class="ver-mas-section">
                            <button class="btn-ver-mas" id="btnVerMas" data-marca="<?php echo htmlspecialchars($marca['nombre_clave']); ?>">
                                <i class="fas fa-list"></i>
                                Ver los <?php echo $total_codigos_marca; ?> códigos
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="ver-mas-lista" id="verMasLista">
                                <!-- Filled by JS -->
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Texto largo de la marca -->
                <?php if(isset($marca["descripción_larga"]) && !empty($marca["descripción_larga"])): ?>
                <div class="brand-description-long">
                    <?php echo generateMainSection('Información detallada sobre ' . $marca['nombre']); ?>
                    <div class="description-content">
                        <?php echo html_entity_decode($marca["descripción_larga"]); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="content-right">
                <!-- Share Section -->
                <div class="sidebar-card">
                    <?php echo generateSubSection('Compartir Página'); ?>
                    <div class="share-buttons">
                        <a href="#" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="share-btn pinterest">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <a href="#" class="share-btn link">
                            <i class="fas fa-link"></i>
                        </a>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="sidebar-card">
                    <?php echo generateSubSection('Consejos para Usar Códigos'); ?>
                    <ol class="tips-list">
                        <li>Verifica la fecha de expiración del código</li>
                        <li>Asegúrate de que el código sea aplicable a tu compra</li>
                        <li>Lee los términos y condiciones antes de usar</li>
                        <li>Algunos códigos no son acumulables con otras ofertas</li>
                    </ol>
                </div>
                
                <!-- Sección específica para Lixsa -->
                <?php if($marca["nombre_clave"] == 'lixsaai'): ?>
                <div class="sidebar-card lixsa-cta">
                    <?php echo generateSubSection('🎯 ¿Listo para automatizar tu negocio?'); ?>
                    <div class="lixsa-cta-content">
                        <p>Con Lixsa.ai puedes:</p>
                        <ul class="lixsa-features">
                            <li>✅ Reducir costos operativos hasta 70%</li>
                            <li>✅ Atender clientes 24/7 automáticamente</li>
                            <li>✅ Aumentar conversiones en tu eCommerce</li>
                            <li>✅ Integrar fácilmente con tu plataforma</li>
                        </ul>
                        <div class="cta-button">
                            <a href="https://www.lixsa.ai" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i>
                                Visitar Lixsa.ai
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Related Brands -->
                <div class="sidebar-card">
                    <?php echo generateSubSection('Marcas Relacionadas'); ?>
                    <div class="related-brands">
                        <?php
                        // Obtener marcas relacionadas de la misma categoría
                        $marcas_relacionadas = array_slice(array_filter(json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/datos.json'), true), function($m) use ($marca) {
                            return $m['categoria'] === $marca['categoria'] && $m['nombre'] !== $marca['nombre'];
                        }), 0, 6);
                        
                        foreach ($marcas_relacionadas as $marca_rel):
                        ?>
                            <a href="<?php echo $marca_rel['url']; ?>" class="related-brand">
                                <img src="<?php echo $marca_rel['imagen']; ?>" alt="<?php echo $marca_rel['nombre']; ?>">
                                <span><?php echo $marca_rel['nombre']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Reviews -->
                <div class="sidebar-card">
                    <?php echo generateSubSection('Reseñas de Usuarios'); ?>
                    <div class="rating-section">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="rating-text">5.0 (<?php echo rand(50, 200); ?> reseñas)</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // ============================================
        // SISTEMA "OBTENER MI CÓDIGO" - JavaScript
        // ============================================
        
        let currentCodigoId = null;  // Track current code for "try another"
        let verMasLoaded = false;    // Track if "ver más" was loaded
        
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- "Obtener mi código" Button ---
            const btnObtener = document.getElementById('btnObtenerCodigo');
            if (btnObtener) {
                btnObtener.addEventListener('click', function() {
                    obtenerCodigo(this.dataset.marca);
                });
            }
            
            // --- "Prueba otro" Button ---
            const btnPruebaOtro = document.getElementById('btnPruebaOtro');
            if (btnPruebaOtro) {
                btnPruebaOtro.addEventListener('click', function() {
                    obtenerCodigo(this.dataset.marca, currentCodigoId);
                });
            }
            
            // --- "Ver más" Button ---
            const btnVerMas = document.getElementById('btnVerMas');
            if (btnVerMas) {
                btnVerMas.addEventListener('click', function() {
                    verMasCodigos(this.dataset.marca);
                });
            }
        });
        
        /**
         * Obtener un código aleatorio ponderado via AJAX
         */
        function obtenerCodigo(marca, excludeId) {
            const btnObtener = document.getElementById('btnObtenerCodigo');
            const obtenerCTA = document.getElementById('obtenerCTA');
            const codigoRevelado = document.getElementById('codigoRevelado');
            const btnPruebaOtro = document.getElementById('btnPruebaOtro');
            
            // Loading state
            if (btnObtener) {
                btnObtener.classList.add('loading');
                btnObtener.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando el mejor código...';
            }
            if (btnPruebaOtro && excludeId) {
                btnPruebaOtro.disabled = true;
                btnPruebaOtro.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            }
            
            // Build form data
            const formData = new FormData();
            formData.append('marca', marca);
            if (excludeId) {
                formData.append('exclude_id', excludeId);
            }
            
            fetch('/ajax/obtener_codigo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentCodigoId = data.codigo_id;
                    
                    // Hide CTA, show reveal
                    if (obtenerCTA) obtenerCTA.style.display = 'none';
                    if (codigoRevelado) {
                        codigoRevelado.classList.add('show');
                        renderCodigoRevelado(data);
                    }
                    
                    // Update "try another" button
                    if (btnPruebaOtro) {
                        btnPruebaOtro.disabled = false;
                        btnPruebaOtro.innerHTML = '<i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro';
                        // Hide if there's only 1 code
                        if (data.total_codigos <= 1) {
                            btnPruebaOtro.style.display = 'none';
                        }
                    }
                } else {
                    // No codes available
                    if (obtenerCTA) {
                        obtenerCTA.innerHTML = '<div style="color: #6b7280; padding: 20px;"><i class="fas fa-info-circle"></i> ' + (data.message || 'No hay códigos disponibles') + '</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error al obtener código:', error);
                if (btnObtener) {
                    btnObtener.classList.remove('loading');
                    btnObtener.innerHTML = '<i class="fas fa-ticket-alt"></i> Obtener mi código';
                }
                if (btnPruebaOtro) {
                    btnPruebaOtro.disabled = false;
                    btnPruebaOtro.innerHTML = '<i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro';
                }
            });
        }
        
        /**
         * Render the revealed code into the resultado container
         */
        function renderCodigoRevelado(data) {
            const container = document.getElementById('codigoResultado');
            if (!container) return;
            
            // Generate trust stars
            let starsHTML = '';
            for (let i = 0; i < 5; i++) {
                if (i < data.trust_stars) {
                    starsHTML += '<i class="fas fa-star"></i>';
                } else {
                    starsHTML += '<i class="far fa-star" style="color: #d1d5db;"></i>';
                }
            }
            
            // Benefit text
            let benefitHTML = '';
            if (data.beneficio_texto) {
                benefitHTML = '<div style="font-size: 0.85rem; color: #10b981; font-weight: 600; margin-bottom: 10px;"><i class="fas fa-tag"></i> ' + escapeHTML(data.beneficio_texto) + '</div>';
            }
            
            // Featured badge
            let featuredHTML = '';
            if (data.es_destacado) {
                featuredHTML = '<div style="display: inline-block; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e3a5f; font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 20px; margin-bottom: 10px;"><i class="fas fa-star"></i> Código Destacado</div><br>';
            }
            
            // Description
            let descHTML = '';
            if (data.descripcion && data.descripcion.length > 0) {
                descHTML = '<div class="codigo-descripcion">' + escapeHTML(data.descripcion.substring(0, 200)) + '</div>';
            }
            
            container.innerHTML = `
                ${featuredHTML}
                ${benefitHTML}
                <div class="codigo-valor-container">
                    <div class="codigo-valor" id="codigoTexto">${escapeHTML(data.codigo)}</div>
                    <button class="btn-copiar-codigo" onclick="copiarCodigoRevelado()">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
                <div class="codigo-publisher">
                    <img src="${escapeHTML(data.usuario_img)}" alt="${escapeHTML(data.usuario_nombre)}" class="publisher-avatar" onerror="this.src='/img/user-default.png'">
                    <div class="publisher-info">
                        <div class="publisher-name">${escapeHTML(data.usuario_nombre)}</div>
                        <div class="trust-badge ${escapeHTML(data.trust_class)}">
                            <span class="trust-stars" style="color: ${escapeHTML(data.trust_color)};">${starsHTML}</span>
                            ${escapeHTML(data.trust_label)}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #10b981; font-size: 0.8rem;">
                            <i class="fas fa-thumbs-up"></i> ${data.votos_positivos}
                        </div>
                    </div>
                </div>
                ${descHTML}
            `;
            
            // Scroll smoothly to revealed code
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        /**
         * Copy the revealed code to clipboard
         */
        function copiarCodigoRevelado() {
            const codigoTexto = document.getElementById('codigoTexto');
            if (!codigoTexto) return;
            
            const text = codigoTexto.textContent.trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess();
                });
            } else {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopySuccess();
            }
        }
        
        function showCopySuccess() {
            const btn = document.querySelector('.btn-copiar-codigo');
            if (!btn) return;
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
            }, 2500);
        }
        
        /**
         * Load and show all codes for "Ver más"
         */
        function verMasCodigos(marca) {
            const btnVerMas = document.getElementById('btnVerMas');
            const verMasLista = document.getElementById('verMasLista');
            
            if (!verMasLista) return;
            
            // Toggle if already loaded
            if (verMasLoaded) {
                verMasLista.classList.toggle('show');
                if (btnVerMas) {
                    const icon = btnVerMas.querySelector('.fa-chevron-down, .fa-chevron-up');
                    if (icon) {
                        icon.classList.toggle('fa-chevron-down');
                        icon.classList.toggle('fa-chevron-up');
                    }
                }
                return;
            }
            
            // Loading state
            if (btnVerMas) {
                btnVerMas.disabled = true;
                btnVerMas.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando códigos...';
            }
            
            const formData = new FormData();
            formData.append('marca', marca);
            
            fetch('/ajax/ver_mas_codigos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.codigos) {
                    let html = '';
                    data.codigos.forEach(function(c) {
                        // Trust stars
                        let stars = '';
                        for (let i = 0; i < 5; i++) {
                            if (i < c.trust_stars) {
                                stars += '<i class="fas fa-star" style="color: ' + escapeHTML(c.trust_color) + ';"></i>';
                            } else {
                                stars += '<i class="far fa-star" style="color: #d1d5db;"></i>';
                            }
                        }
                        
                        let benefitText = c.beneficio_texto ? '<div class="ver-mas-benefit">' + escapeHTML(c.beneficio_texto) + '</div>' : '';
                        let featuredIcon = c.es_destacado ? '<i class="fas fa-star" style="color: #f59e0b; margin-left: 4px;" title="Destacado"></i>' : '';
                        
                        html += `
                            <div class="ver-mas-item">
                                <img src="${escapeHTML(c.usuario_img)}" alt="${escapeHTML(c.usuario_nombre)}" class="ver-mas-avatar" onerror="this.src='/img/user-default.png'">
                                <div class="ver-mas-info">
                                    <div class="ver-mas-user">${escapeHTML(c.usuario_nombre)}${featuredIcon}</div>
                                    ${benefitText}
                                    <div class="ver-mas-trust">
                                        <span class="trust-stars" style="font-size: 0.7rem;">${stars}</span>
                                        <span class="trust-badge ${escapeHTML(c.trust_class)}" style="font-size: 0.7rem; padding: 1px 6px;">${escapeHTML(c.trust_label)}</span>
                                    </div>  
                                </div>
                                <a href="/codigo/${escapeHTML(c.marca || marca).toLowerCase()}-${c.codigo_id.slice(-8)}" 
                                   style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:600;color:#60a5fa;background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.25);text-decoration:none;margin-right:6px;white-space:nowrap;" 
                                   title="Ver ficha detallada">
                                    <i class="fas fa-id-card"></i> Ficha
                                </a>
                                <button class="btn-usar-este" onclick="usarEsteCodigo('${escapeHTML(c.codigo_id)}', '${escapeHTML(c.codigo)}', '${escapeHTML(c.usuario_nombre)}', '${escapeHTML(c.usuario_img)}', ${c.trust_stars}, '${escapeHTML(c.trust_label)}', '${escapeHTML(c.trust_class)}', '${escapeHTML(c.trust_color)}', ${c.votos_positivos}, '${escapeHTML(c.beneficio_texto)}', '${escapeHTML(c.descripcion || '')}', ${c.es_destacado ? 'true' : 'false'})">
                                    <i class="fas fa-arrow-right"></i> Usar
                                </button>
                            </div>
                        `;
                    });
                    
                    verMasLista.innerHTML = html;
                    verMasLista.classList.add('show');
                    verMasLoaded = true;
                    
                    if (btnVerMas) {
                        btnVerMas.disabled = false;
                        btnVerMas.innerHTML = '<i class="fas fa-list"></i> Ocultar códigos <i class="fas fa-chevron-up"></i>';
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar códigos:', error);
                if (btnVerMas) {
                    btnVerMas.disabled = false;
                    btnVerMas.innerHTML = '<i class="fas fa-list"></i> Error al cargar, intenta de nuevo <i class="fas fa-chevron-down"></i>';
                }
            });
        }
        
        /**
         * Use a specific code from "ver más" list
         */
        function usarEsteCodigo(codigoId, codigo, userName, userImg, trustStars, trustLabel, trustClass, trustColor, votosPos, beneficio, descripcion, esDestacado) {
            currentCodigoId = codigoId;
            
            const obtenerCTA = document.getElementById('obtenerCTA');
            const codigoRevelado = document.getElementById('codigoRevelado');
            
            if (obtenerCTA) obtenerCTA.style.display = 'none';
            if (codigoRevelado) codigoRevelado.classList.add('show');
            
            renderCodigoRevelado({
                codigo: codigo,
                usuario_nombre: userName,
                usuario_img: userImg,
                trust_stars: trustStars,
                trust_label: trustLabel,
                trust_class: trustClass,
                trust_color: trustColor,
                votos_positivos: votosPos,
                beneficio_texto: beneficio,
                descripcion: descripcion,
                es_destacado: esDestacado
            });
        }
        
        /**
         * Escape HTML to prevent XSS
         */
        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str)));
            return div.innerHTML;
        }

        // --- Keep existing functionality ---

        // Tab functionality (legacy, for other tabs on page)
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Copy code functionality (legacy, for other copy buttons)
        function copyCode(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    const btn = event.target.closest('.btn-copy');
                    if (!btn) return;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.style.background = 'var(--primary-green)';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                });
            }
        }

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        window.location.href = '/buscar?q=' + encodeURIComponent(query);
                    }
                }
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Owner menu functions (for code cards that might still exist elsewhere)
        function toggleOwnerMenu(codigoId) {
            const menu = document.getElementById('owner-menu-' + codigoId);
            if (menu) {
                menu.classList.toggle('show');
                document.querySelectorAll('.owner-menu-dropdown.show').forEach(otherMenu => {
                    if (otherMenu !== menu) otherMenu.classList.remove('show');
                });
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu(e) {
                        if (!menu.contains(e.target) && !e.target.closest('.owner-menu-toggle')) {
                            menu.classList.remove('show');
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }, 100);
            }
        }

        function deleteCode(codigoId, codigoTexto) {
            if (confirm('¿Estás seguro de que quieres borrar el código "' + codigoTexto + '"?\n\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/borrar_codigo/' + codigoId;
                const csrfInput = document.querySelector('meta[name="csrf-token"]');
                if (csrfInput) {
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = csrfInput.getAttribute('content');
                    form.appendChild(tokenInput);
                }
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.owner-menu')) {
                document.querySelectorAll('.owner-menu-dropdown.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>

<?php
// Modal informativo de Trust Score (clicable en badges)
include_once __DIR__ . '/../myphp/trust_info_modal.php';
?>
</body>
</html>

<?php
// Función para mostrar un elemento de código
function show_code_item($codigo, $is_featured = false) {
    global $url_usuario_sin_foto;
    $usuario = getObjectUser('_id', $codigo['id_usuario'] ?? null);
    $inicial = $usuario ? strtoupper(substr($usuario['nombre'], 0, 1)) : 'U';
    $nombre_usuario = $usuario ? htmlspecialchars($usuario['nombre']) : 'Usuario';
    $beneficio = $codigo['beneficio'] ?? 'Descuento';
    $descripcion = $codigo['descripcion'] ?? 'Código de descuento válido';
    $enlace = $codigo['enlace'] ?? '#';
    $codigo_texto = $codigo['codigo'] ?? '';

    $featured_class = $is_featured ? 'featured' : '';

    // Verificar si el código pertenece al usuario actual
    $is_owner = !empty($_SESSION["user_id"]) && isset($codigo['id_usuario']) && $codigo['id_usuario'] === $_SESSION["user_id"];
    $codigo_id = $codigo['_id'] ?? '';
    
    // Obtener foto del usuario
    $usuario_img = function_exists('get_user_avatar_url') 
        ? get_user_avatar_url($usuario, $nombre_usuario, 80) 
        : '/img/user-default.png';
    
    // Obtener ID del usuario para el enlace
    $user_id = '';
    $user_link = '';
    if($usuario && isset($usuario['_id'])) {
        if(is_object($usuario['_id'])) {
            $user_id = (string)$usuario['_id'];
        } else {
            $user_id = (string)$usuario['_id'];
        }
        if($user_id && $nombre_usuario && $nombre_usuario !== 'Usuario') {
            $user_link = link_usuario($nombre_usuario, $user_id);
        }
    }
    ?>
    <div class="code-card code-item <?php echo $featured_class; ?>">
        <?php if ($is_featured): ?>
            <div class="featured-badge">
                <i class="fas fa-star"></i> Destacado
            </div>
        <?php endif; ?>
        
        <div class="code-header">
            <div class="code-reward">
                <?php echo htmlspecialchars($beneficio); ?>
            </div>
            <div class="code-user">
                <div class="user-avatar" style="overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($usuario_img); ?>" alt="<?php echo $nombre_usuario; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onerror="this.style.display='none'; this.parentElement.textContent='<?php echo $inicial; ?>';">
                </div>
                <?php if($user_link): ?>
                    <a href="<?php echo htmlspecialchars($user_link); ?>" class="user-name-link">
                        <span class="user-name">
                            <?php echo $nombre_usuario; ?>
                        </span>
                    </a>
                <?php else: ?>
                    <span class="user-name">
                        <?php echo $nombre_usuario; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="code-description">
            <?php echo htmlspecialchars($descripcion); ?>
        </div>
        
        <div class="code-actions">
            <?php if ($is_owner): ?>
                <!-- Menú de propietario -->
                <div class="owner-menu">
                    <div class="owner-menu-toggle" onclick="toggleOwnerMenu('<?php echo $codigo_id; ?>')">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                    <div class="owner-menu-dropdown" id="owner-menu-<?php echo $codigo_id; ?>">
                        <a href="/modificar_codigo/<?php echo $codigo_id; ?>" class="owner-menu-item edit">
                            <i class="fas fa-edit"></i>
                            <span>Editar</span>
                        </a>
                        <button class="owner-menu-item delete" onclick="deleteCode('<?php echo $codigo_id; ?>', '<?php echo htmlspecialchars($codigo_texto); ?>')">
                            <i class="fas fa-trash"></i>
                            <span>Borrar</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <a href="<?php echo $enlace; ?>" class="code-button" target="_blank" style="text-align:center; text-decoration:none;">
                <i class="fas fa-eye"></i> Ver Código
            </a>
            <button class="btn-copy" onclick="copyCode('<?php echo $codigo_texto; ?>')">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </div>
    <?php
}
?>

<?php
// Incluir el footer si no se ha incluido ya
if (!function_exists('get_footer')) {
    include_once __DIR__ . '/../myphp/_footer.php';
}
get_footer();
?>

