<?php
// Nueva plantilla moderna basada en Referral Codes
get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
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
    <link href="/css/modern-design.css" rel="stylesheet">
    
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
        .brand-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1d4ed8 100%);
        }
        
        .code-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .code-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
                    <h1 class="brand-title"><?php echo $marca['nombre']; ?> Códigos de Descuento 2025</h1>
                    <p class="brand-subtitle">Ahorra dinero con los mejores códigos promocionales</p>
                    
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
                        <h3>¿Qué es <?php echo $marca['nombre']; ?>?</h3>
                        <p><?php echo $marca["descripción"]; ?></p>
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
                            <p>Comparte tu código con la comunidad y ayuda a otros usuarios a ahorrar dinero</p>
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

                <!-- Codes Section -->
                <div class="codes-section">
                    <?php if ($marca["nombre_clave"] == 'airbnb'): ?>
                        <h2 class="codes-title" id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">
                            <?php echo $numero_codigos_format; ?> Créditos de viaje y Códigos amigo para AirBnb
                        </h2>
                    <?php else: ?>
                        <h2 class="codes-title" id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">
                            <?php echo $numero_codigos_format; ?> Cupones y Códigos amigo para <?php echo $marca["nombre"]; ?>
                        </h2>
                    <?php endif; ?>

                    <?php if (filter_input(INPUT_GET, "page", FILTER_SANITIZE_STRING) != ""): ?>
                        <h3 class="pagination-info">
                            Mostrando del <?php echo $num_inicio; ?> al <?php echo $num_fin; ?> de un total de <?php echo $numero_codigos_format; ?> códigos
                        </h3>
                    <?php endif; ?>

                    <?php
                    // Mostrar filtros móviles (se ocultan con CSS en desktop)
                    echo generate_chollometro_filter_menu($marca['nombre']);
                    ?>

                    <div class="codes-list" id="codes-tab">
                        <?php if ($numero_codigos == 0): ?>
                            <div class="no-codes">
                                <i class="fas fa-search"></i>
                                <h3>Aún no hay códigos de esta marca</h3>
                                <p>Sé el primero en compartir un código de descuento</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            // Mostrar códigos patrocinados primero
                            if (!empty($lista_codigos_patrocinados)) {
                                foreach ($lista_codigos_patrocinados as $codigo) {
                                    if (is_object($codigo)) $codigo = (array)$codigo;
                                    show_code_item($codigo, true);
                                }
                            }
                            
                            // Mostrar códigos normales
                            if (!empty($lista_codigos)) {
                                foreach ($lista_codigos as $codigo) {
                                    if (is_object($codigo)) $codigo = (array)$codigo;
                                    show_code_item($codigo, false);
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Texto largo de la marca -->
                <?php if(isset($marca["descripción_larga"]) && !empty($marca["descripción_larga"])): ?>
                <div class="brand-description-long">
                    <h3>Información detallada sobre <?php echo $marca['nombre']; ?></h3>
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
                    <h3 class="sidebar-title">Compartir Página</h3>
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
                    <h3 class="sidebar-title">Consejos para Usar Códigos</h3>
                    <ol class="tips-list">
                        <li>Verifica la fecha de expiración del código</li>
                        <li>Asegúrate de que el código sea aplicable a tu compra</li>
                        <li>Lee los términos y condiciones antes de usar</li>
                        <li>Algunos códigos no son acumulables con otras ofertas</li>
                    </ol>
                </div>

                <!-- Related Brands -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Marcas Relacionadas</h3>
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
                    <h3 class="sidebar-title">Reseñas de Usuarios</h3>
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
        // Tab functionality
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
    ?>
    <div class="code-item <?php echo $featured_class; ?>">
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
                <span class="user-name">
                    <?php echo $nombre_usuario; ?>
                </span>
            </div>
        </div>
        
        <div class="code-description">
            <?php echo htmlspecialchars($descripcion); ?>
        </div>
        
        <div class="code-actions">
            <a href="<?php echo $enlace; ?>" class="btn-get-code" target="_blank">
                <i class="fas fa-external-link-alt"></i> Obtener Código
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

