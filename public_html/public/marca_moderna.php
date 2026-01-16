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

                <!-- Sistema de Tabs estilo Chollometro -->
                <div class="chollometro-tabs-container">
                    <div class="chollometro-tabs">
                        <button class="tab-button active" data-tab="destacados">
                            <i class="fas fa-star"></i>
                            Códigos Destacados
                            <?php if (!empty($lista_codigos_patrocinados)): ?>
                                <span class="tab-count"><?php echo count($lista_codigos_patrocinados); ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="tab-button" data-tab="amigos">
                            <i class="fas fa-users"></i>
                            Códigos Amigo
                            <?php if (!empty($lista_codigos)): ?>
                                <span class="tab-count"><?php echo count($lista_codigos); ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>

                <!-- Contenido de los tabs -->
                <div class="chollometro-content">
                    <div class="row">
                        <!-- Columna de filtros -->
                        <div class="col-md-3 col-sm-12">
                            <div class="filters-sidebar">
                                <h4><i class="fas fa-filter"></i> Ordenar por</h4>
                                <div class="filter-group">
                                    <label class="filter-option">
                                        <input type="radio" name="sort" value="fecha" checked>
                                        <span class="filter-label">
                                            <i class="fas fa-calendar"></i>
                                            Fecha
                                        </span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="radio" name="sort" value="visitas">
                                        <span class="filter-label">
                                            <i class="fas fa-eye"></i>
                                            Visitas
                                        </span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="radio" name="sort" value="votos_positivos">
                                        <span class="filter-label">
                                            <i class="fas fa-thumbs-up"></i>
                                            Votos Positivos
                                        </span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="radio" name="sort" value="votos_negativos">
                                        <span class="filter-label">
                                            <i class="fas fa-thumbs-down"></i>
                                            Votos Negativos
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna principal de códigos -->
                        <div class="col-md-9 col-sm-12">
                            <!-- Tab de Códigos Destacados -->
                            <div class="tab-content active" id="tab-destacados">
                                <div class="codes-list-header">
                                    <h3><i class="fas fa-star"></i> Códigos Destacados de <?php echo $marca["nombre"]; ?></h3>
                                    <p>Códigos promocionados y verificados</p>
                                </div>
                                <div class="codes-list-container">
                                    <?php if (!empty($lista_codigos_patrocinados)): ?>
                                        <?php foreach ($lista_codigos_patrocinados as $codigo): ?>
                                            <?php echo generate_chollometro_code_card($codigo, true); ?>
                                        <?php endforeach; ?>
                                        <?php 
                                        // Mostrar bloque "Tu código aquí" después de los códigos destacados
                                        // Asegurar que $marca esté disponible
                                        if (!isset($marca) || empty($marca)) {
                                            global $marca;
                                        }
                                        
                                        // Verificar si tenemos la información de marca y código existente
                                        $marca_nombre_clave = null;
                                        if (isset($marca)) {
                                            if (is_array($marca) && isset($marca["nombre_clave"])) {
                                                $marca_nombre_clave = $marca["nombre_clave"];
                                            } elseif (is_object($marca) && isset($marca->nombre_clave)) {
                                                $marca_nombre_clave = $marca->nombre_clave;
                                            }
                                        }
                                        
                                        // Verificar sesión directamente aquí donde sabemos que está disponible
                                        $is_logged_in = isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
                                        
                                        // Procesar código existente
                                        $codigo_usuario = null;
                                        if (isset($codigo_existente) && !empty($codigo_existente)) {
                                            // Si es un array de resultados (cursor o array numérico)
                                            if (is_array($codigo_existente) && isset($codigo_existente[0])) {
                                                $codigo_usuario = $codigo_existente[0];
                                            } 
                                            // Si es un array asociativo (un solo documento)
                                            elseif (is_array($codigo_existente) && (isset($codigo_existente['id']) || isset($codigo_existente['_id']))) {
                                                $codigo_usuario = $codigo_existente;
                                            }
                                            // Si es un objeto
                                            elseif (is_object($codigo_existente)) {
                                                $codigo_usuario = $codigo_existente;
                                            }
                                            // Fallback para otros casos o cursores iterables
                                            elseif (is_iterable($codigo_existente)) {
                                                foreach($codigo_existente as $c) {
                                                    $codigo_usuario = $c;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Pasar siempre la marca y el estado de sesión explícitamente
                                        echo generate_empty_featured_card($marca_nombre_clave, $codigo_usuario, $is_logged_in);
                                        ?>
                                    <?php else: ?>
                                        <?php 
                                        // Mostrar bloque "Tu código aquí" cuando no hay códigos destacados
                                        // Asegurar que $marca esté disponible
                                        if (!isset($marca) || empty($marca)) {
                                            global $marca;
                                        }
                                        
                                        // Verificar si tenemos la información de marca y código existente
                                        $marca_nombre_clave = null;
                                        if (isset($marca)) {
                                            if (is_array($marca) && isset($marca["nombre_clave"])) {
                                                $marca_nombre_clave = $marca["nombre_clave"];
                                            } elseif (is_object($marca) && isset($marca->nombre_clave)) {
                                                $marca_nombre_clave = $marca->nombre_clave;
                                            }
                                        }
                                        
                                        // Verificar sesión directamente aquí donde sabemos que está disponible
                                        $is_logged_in = isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
                                        
                                        // Procesar código existente
                                        $codigo_usuario = null;
                                        if (isset($codigo_existente) && !empty($codigo_existente)) {
                                            // Si es un array de resultados (cursor o array numérico)
                                            if (is_array($codigo_existente) && isset($codigo_existente[0])) {
                                                $codigo_usuario = $codigo_existente[0];
                                            } 
                                            // Si es un array asociativo (un solo documento)
                                            elseif (is_array($codigo_existente) && (isset($codigo_existente['id']) || isset($codigo_existente['_id']))) {
                                                $codigo_usuario = $codigo_existente;
                                            }
                                            // Si es un objeto
                                            elseif (is_object($codigo_existente)) {
                                                $codigo_usuario = $codigo_existente;
                                            }
                                            // Fallback para otros casos o cursores iterables
                                            elseif (is_iterable($codigo_existente)) {
                                                foreach($codigo_existente as $c) {
                                                    $codigo_usuario = $c;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Pasar siempre la marca y el estado de sesión explícitamente
                                        echo generate_empty_featured_card($marca_nombre_clave, $codigo_usuario, $is_logged_in);
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Tab de Códigos Amigo -->
                            <div class="tab-content" id="tab-amigos">
                                <div class="codes-list-header">
                                    <h3><i class="fas fa-users"></i> Códigos Amigo de <?php echo $marca["nombre"]; ?></h3>
                                    <p>Códigos compartidos por la comunidad</p>
                                </div>
                                <div class="codes-list-container">
                                    <?php if (!empty($lista_codigos)): ?>
                                        <?php foreach ($lista_codigos as $codigo): ?>
                                            <?php echo generate_chollometro_code_card($codigo, false); ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-codes-message">
                                            <i class="fas fa-users"></i>
                                            <h4>No hay códigos disponibles</h4>
                                            <p>Sé el primero en compartir un código</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
        // Funcionalidad de tabs estilo Chollometro
        document.addEventListener('DOMContentLoaded', function() {
            // Manejo de tabs
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remover clase active de todos los botones y contenidos
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Agregar clase active al botón clickeado y su contenido
                    this.classList.add('active');
                    document.getElementById('tab-' + targetTab).classList.add('active');
                });
            });

            // Manejo de filtros
            const filterOptions = document.querySelectorAll('input[name="sort"]');

            function applySorting(sortValue) {
                const activeTab = document.querySelector('.tab-content.active');
                const codeCards = activeTab.querySelectorAll('.chollometro-code-card');

                // Convertir NodeList a Array para poder ordenar
                const cardsArray = Array.from(codeCards);

                cardsArray.sort((a, b) => {
                    let aValue, bValue;

                    switch(sortValue) {
                        case 'fecha':
                            aValue = new Date(a.querySelector('.stat-item:last-child span').textContent);
                            bValue = new Date(b.querySelector('.stat-item:last-child span').textContent);
                            return bValue - aValue; // Más reciente primero

                        case 'visitas':
                            aValue = parseInt(a.querySelector('.stat-item:first-child span').textContent) || 0;
                            bValue = parseInt(b.querySelector('.stat-item:first-child span').textContent) || 0;
                            return bValue - aValue; // Más visitas primero

                        case 'votos_positivos':
                            aValue = parseInt(a.querySelector('.stat-item:nth-child(2) span').textContent) || 0;
                            bValue = parseInt(b.querySelector('.stat-item:nth-child(2) span').textContent) || 0;
                            return bValue - aValue; // Más votos positivos primero

                        case 'votos_negativos':
                            aValue = parseInt(a.querySelector('.stat-item:nth-child(3) span').textContent) || 0;
                            bValue = parseInt(b.querySelector('.stat-item:nth-child(3) span').textContent) || 0;
                            return bValue - aValue; // Más votos negativos primero

                        default:
                            return 0;
                    }
                });

                // Reorganizar las tarjetas en el DOM
                const container = activeTab.querySelector('.codes-list-container');
                cardsArray.forEach(card => {
                    container.appendChild(card);
                });
            }

            // Event listeners para filtros
            filterOptions.forEach(option => {
                option.addEventListener('change', function() {
                    if (this.checked) {
                        applySorting(this.value);
                    }
                });
            });

            // Manejo de botones "Ver Código"
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-view-code') || e.target.closest('.btn-view-code')) {
                    const button = e.target.classList.contains('btn-view-code') ? e.target : e.target.closest('.btn-view-code');
                    const codeId = button.getAttribute('data-code-id');

                    if (codeId) {
                        console.log('Ver código:', codeId);
                        button.innerHTML = '<i class="fas fa-check"></i> Código Copiado';
                        button.style.background = '#28a745';

                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-eye"></i> Ver Código';
                            button.style.background = '#E30613';
                        }, 2000);
                    }
                }
            });
        });

        // Tab functionality (legacy)
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Copy code functionality
        function copyCode(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    // Show success message
                    const btn = event.target.closest('.btn-copy');
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
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = '/buscar?q=' + encodeURIComponent(query);
                }
            }
        });

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

        // Add loading states
        document.querySelectorAll('.btn-get-code').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.add('loading');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            });
        });

        // Funciones para el menú de propietario
        function toggleOwnerMenu(codigoId) {
            const menu = document.getElementById('owner-menu-' + codigoId);
            if (menu) {
                menu.classList.toggle('show');

                // Cerrar otros menús abiertos
                document.querySelectorAll('.owner-menu-dropdown.show').forEach(otherMenu => {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('show');
                    }
                });

                // Cerrar menú al hacer clic fuera
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
                // Crear formulario y enviar petición de borrado
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/borrar_codigo/' + codigoId;

                // Agregar token CSRF si existe
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

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.owner-menu')) {
                document.querySelectorAll('.owner-menu-dropdown.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>

<?php
// Función para mostrar un elemento de código
function show_code_item($codigo, $is_featured = false) {
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
                <div class="user-avatar">
                    <?php echo $inicial; ?>
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

