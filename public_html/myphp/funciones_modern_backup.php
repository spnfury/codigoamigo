<?php
// Funciones para el diseño moderno de CodigoAmigo.com

// Incluir sistema de logging si no está ya incluido
if (!function_exists('log_warning')) {
    require_once __DIR__ . '/../inc/logger.php';
}

// Incluir funciones de códigos si no están incluidas
if (!function_exists('getCollectionCodigos')) {
    include_once __DIR__ . '/funciones_codigo.php';
}

// Incluir funciones de usuarios si no están incluidas
if (!function_exists('getCollectionUsuarios')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

// Incluir funciones de marcas si no están incluidas
if (!function_exists('getObjectMarca')) {
    include_once __DIR__ . '/funciones_marca.php';
}

// Incluir funciones de usuarios adicionales si no están incluidas
if (!function_exists('getObjectUser')) {
    include_once __DIR__ . '/funciones.php';
}

// Función para agregar CSS móvil al header existente
function add_mobile_css_to_header() {
    echo '
    <!-- CSS Móvil Optimizado -->
    <link rel="stylesheet" href="/css/mobile-compact-header.css">
    <link rel="stylesheet" href="/css/mobile-exhaustive-optimization.css">
    
    <style>
    /* Variables CSS para móvil */
    :root {
        --mobile-bg-primary: #000000;
        --mobile-bg-secondary: #1a1a1a;
        --mobile-bg-card: #2a2a2a;
        --mobile-text-primary: #ffffff;
        --mobile-text-secondary: #cccccc;
        --mobile-text-muted: #999999;
        --mobile-accent-orange: #ff6b35;
        --mobile-accent-orange-hover: #e55a2b;
        --mobile-border: #404040;
        --mobile-shadow: rgba(0, 0, 0, 0.3);
        --mobile-radius: 8px;
        --mobile-radius-large: 12px;
        --mobile-padding: 16px;
        --mobile-padding-small: 12px;
        --mobile-gap: 12px;
        --mobile-gap-small: 8px;
    }
    
    /* Integración móvil */
    @media (max-width: 768px) {
        .header-modern:not(.mobile-only) {
            display: none !important;
        }
        
        .header-modern.mobile-only {
            display: block !important;
        }
        
        body {
            margin-top: 60px !important;
        }
        
        @media (max-width: 480px) {
            body {
                margin-top: 55px !important;
            }
        }
        
        @media (max-width: 360px) {
            body {
                margin-top: 50px !important;
            }
        }
    }
    
    @media (min-width: 769px) {
        .header-modern:not(.mobile-only) {
            display: block !important;
        }
        
        .header-modern.mobile-only {
            display: none !important;
        }
        
        body {
            margin-top: 80px !important;
        }
    }
    </style>';
}

// Función para agregar header móvil compacto después del header existente
function add_mobile_header_compact() {
    // Variables de sesión
    $usuario_logueado = isset($_SESSION['usuario_id']) ? true : false;
    $nombre_usuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : '';
    $foto_perfil = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : '';
    
    echo '
    <!-- HEADER COMPACTO MÓVIL -->
    <header class="header-modern mobile-only">
        <div class="header-container">
            <!-- LOGO COMPACTO -->
            <a href="/" class="logo-section">
                <div class="logo-text">
                    <span class="logo-codigo">codigo</span><span class="logo-amigo">amigo</span>
                </div>
                <div class="logo-tagline">códigos verificados, gente real</div>
            </a>
            
            <!-- BÚSQUEDA COMPACTA -->
            <div class="search-header">
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           class="search-input-header" 
                           placeholder="Buscar códigos..." 
                           id="mobile-search-input">
                    <button class="search-submit" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <!-- PERFIL DE USUARIO COMPACTO -->
            <div class="user-profile-mobile" id="user-profile-mobile">
                ' . ($usuario_logueado && $foto_perfil ? 
                    '<img src="' . htmlspecialchars($foto_perfil) . '" alt="Perfil de ' . htmlspecialchars($nombre_usuario) . '">' : 
                    '<i class="fas fa-user"></i>') . '
            </div>
            
            <!-- BOTÓN DE MENÚ HAMBURGUESA -->
            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- MENÚ DESPLEGABLE MÓVIL -->
        <div class="mobile-menu" id="mobile-menu">
            <div class="mobile-menu-content">
                <nav class="mobile-nav-links">
                    <a href="/destacados" class="mobile-nav-link">Destacados</a>
                    <a href="/nuevos" class="mobile-nav-link">Nuevos</a>
                    <a href="/populares" class="mobile-nav-link">Populares</a>
                    <a href="/categorias" class="mobile-nav-link">Categorías</a>
                    
                    ' . ($usuario_logueado ? '
                        <a href="/perfil" class="mobile-nav-link">Mi Perfil</a>
                        <a href="/mis-codigos" class="mobile-nav-link">Mis Códigos</a>
                        <a href="/logout" class="mobile-nav-link">Cerrar Sesión</a>
                    ' : '
                        <a href="/login" class="mobile-nav-link">Iniciar Sesión</a>
                        <a href="/registro" class="mobile-nav-link">Registrarse</a>
                    ') . '
                </nav>
            </div>
        </div>
        
        <!-- OVERLAY PARA MENÚ -->
        <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    </header>';
}

// Función para agregar JavaScript móvil
function add_mobile_javascript() {
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Variables globales
        const menuToggle = document.getElementById("mobile-menu-toggle");
        const mobileMenu = document.getElementById("mobile-menu");
        const overlay = document.getElementById("mobile-menu-overlay");
        const searchInput = document.getElementById("mobile-search-input");
        const searchSubmit = document.querySelector(".search-submit");
        const filtersToggle = document.getElementById("filters-toggle");
        const filtersPanel = document.getElementById("filters-panel");
        const userProfile = document.getElementById("user-profile-mobile");
        
        // Toggle del menú móvil
        if (menuToggle) {
            menuToggle.addEventListener("click", function() {
                menuToggle.classList.toggle("active");
                mobileMenu.classList.toggle("show");
                overlay.classList.toggle("show");
                document.body.style.overflow = mobileMenu.classList.contains("show") ? "hidden" : "";
            });
        }
        
        // Cerrar menú al hacer clic en overlay
        if (overlay) {
            overlay.addEventListener("click", function() {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            });
        }
        
        // Cerrar menú al hacer clic en enlaces
        document.querySelectorAll(".mobile-nav-link").forEach(link => {
            link.addEventListener("click", function() {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            });
        });
        
        // Toggle de filtros
        if (filtersToggle && filtersPanel) {
            filtersToggle.addEventListener("click", function() {
                filtersPanel.classList.toggle("show");
                filtersToggle.classList.toggle("active");
            });
        }
        
        // Funcionalidad de filtros
        document.querySelectorAll(".filter-option").forEach(option => {
            option.addEventListener("click", function() {
                // Remover active de otros elementos del mismo grupo
                const group = this.closest(".filter-group");
                group.querySelectorAll(".filter-option").forEach(opt => {
                    opt.classList.remove("active");
                });
                
                // Activar el elemento clickeado
                this.classList.add("active");
            });
        });
        
        // Búsqueda móvil
        if (searchSubmit) {
            searchSubmit.addEventListener("click", function() {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = "/buscar?q=" + encodeURIComponent(query);
                }
            });
        }
        
        // Búsqueda con Enter
        if (searchInput) {
            searchInput.addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    const query = searchInput.value.trim();
                    if (query) {
                        window.location.href = "/buscar?q=" + encodeURIComponent(query);
                    }
                }
            });
        }
        
        // Funcionalidad del perfil de usuario
        if (userProfile) {
            userProfile.addEventListener("click", function() {
                ' . (isset($_SESSION['usuario_id']) ? 'window.location.href = "/perfil";' : 'window.location.href = "/login";') . '
            });
        }
        
        // Cerrar menú al redimensionar pantalla
        window.addEventListener("resize", function() {
            if (window.innerWidth > 768) {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    });
    </script>';
}

function generate_modern_code_cards($lista_codigos) {
    $html = '';
    
    if(empty($lista_codigos)) {
        $html .= '<div style="text-align: center; color: #ccc; padding: 2rem;">';
        $html .= '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #FF6B35;"></i>';
        $html .= '<h3>No se encontraron códigos</h3>';
        $html .= '<p>Intenta con otros términos de búsqueda</p>';
        $html .= '</div>';
        return $html;
    }
    
    foreach($lista_codigos as $codigo) {
        $html .= generate_single_code_card($codigo);
    }
    
    return $html;
}

function generate_single_code_card($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $ratings = isset($codigo['num_valoraciones']) ? $codigo['num_valoraciones'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    
    // Obtener información del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca para la imagen
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    // Limpiar descripción
    $description = strip_tags($description);
    $description = mb_substr($description, 0, 150) . (mb_strlen($description) > 150 ? '...' : '');
    
    // Crear enlace a la página de la marca
    $marca_url = '/de-' . strtolower($brand);
    
    $html = '<div class="code-card" data-code-id="' . htmlspecialchars($code_id) . '">';
    
    // Imagen de la marca (clickeable)
    $html .= '<div class="code-brand-image">';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="brand-link">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($brand) . '" class="brand-image">';
    } else {
        $html .= '<div class="brand-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</a>';
    $html .= '</div>';
    
    // Header de la tarjeta con usuario
    $html .= '<div class="code-card-header">';
    $html .= '<div class="code-brand">' . htmlspecialchars($brand) . '</div>';
    $html .= '<div class="code-user-info">';
    $html .= '<div class="code-user-avatar">';
    if($user_img) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="code-user-img">';
    } else {
        $html .= '<div class="code-user-placeholder">';
        // Usar iniciales si están disponibles
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span class="user-iniciales">' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="code-user-details">';
    $html .= '<span class="code-user-name">' . htmlspecialchars($username) . '</span>';

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="code-description">' . htmlspecialchars($description) . '</div>';
    
    // Información adicional
    $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.9rem; color: #ccc;">';
    
    if($benefit > 0) {
        $html .= '<div class="beneficio-destacado">';
        $html .= '<div class="beneficio-icono">💰</div>';
        $html .= '<div class="beneficio-contenido">';
        $html .= '<div class="beneficio-cantidad">' . $benefit . '€</div>';
        $html .= '<div class="beneficio-tipo">Beneficio</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    if($ratings > 0) {
        $html .= '<span><i class="fas fa-star"></i> ' . $ratings . ' valoraciones</span>';
    }
    
    // Fecha de publicación mejorada
    if(isset($codigo['fecha_publicacion'])) {
        $fecha_formateada = formatDateAgoLarge($codigo['fecha_publicacion']);
        $html .= '<div class="fecha-publicacion-large">';
        $html .= '<i class="far fa-clock"></i>';
        $html .= '<span class="fecha-texto">' . $fecha_formateada . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Botón de acción
    $html .= '<button class="code-button" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand) . '\')">';
    $html .= '<i class="fas fa-eye"></i> Ver Código';
    $html .= '</button>';
    
    $html .= '</div>';
    
    return $html;
}

function generate_modern_pagination($total_codes, $current_page = 1, $codes_per_page = 20) {
    $total_pages = ceil($total_codes / $codes_per_page);
    
    if($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination-modern">';
    $html .= '<div class="pagination-info">';
    $html .= 'Mostrando del ' . (($current_page - 1) * $codes_per_page + 1) . ' al ' . min($current_page * $codes_per_page, $total_codes) . ' de un total de <strong>' . $total_codes . ' Códigos Amigo</strong>';
    $html .= '</div>';
    
    $html .= '<div class="pagination-controls">';
    
    // Botón anterior
    if($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= '<a href="?page=' . $prev_page . '" class="pagination-btn"><i class="fas fa-chevron-left"></i> Anterior</a>';
    }
    
    // Números de página
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $current_page) ? ' active' : '';
        $html .= '<a href="?page=' . $i . '" class="pagination-btn' . $active_class . '">' . $i . '</a>';
    }
    
    // Botón siguiente
    if($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= '<a href="?page=' . $next_page . '" class="pagination-btn">Siguiente <i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function generate_modern_categories($categorias) {
    $html = '<div class="categories-modern">';
    $html .= '<h3 class="section-title">Nuestras Categorías</h3>';
    $html .= '<div class="categories-grid">';
    
    foreach($categorias as $categoria) {
        $html .= '<div class="category-card">';
        $html .= '<div class="category-icon">';
        $html .= '<i class="fas fa-' . get_category_icon($categoria['nombre']) . '"></i>';
        $html .= '</div>';
        $html .= '<div class="category-name">' . htmlspecialchars($categoria['nombre']) . '</div>';
        $html .= '<div class="category-count">' . (isset($categoria['total_codigos']) ? $categoria['total_codigos'] : 0) . ' códigos</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function get_category_icon($category_name) {
    $icons = [
        'moda' => 'tshirt',
        'tecnología' => 'laptop',
        'hogar' => 'home',
        'deportes' => 'dumbbell',
        'belleza' => 'spa',
        'viajes' => 'plane',
        'alimentación' => 'utensils',
        'jardín' => 'seedling',
        'mascotas' => 'paw',
        'libros' => 'book',
        'música' => 'music',
        'juegos' => 'gamepad'
    ];
    
    $name_lower = strtolower($category_name);
    foreach($icons as $key => $icon) {
        if(strpos($name_lower, $key) !== false) {
            return $icon;
        }
    }
    
    return 'tag'; // Icono por defecto
}

// CSS adicional para paginación y categorías
function generate_modern_featured_cards($lista_codigos_destacados, $show_all = false) {
    $html = '';
    
    if(empty($lista_codigos_destacados)) {
        return $html;
    }
    
    $html .= '<div class="featured-section">';
    $html .= '<h2 class="section-title">¡Destacados!</h2>';
    $html .= '<div class="featured-grid" id="featuredGrid">';
    
    // Mostrar solo los primeros 6 códigos inicialmente
    $codigos_a_mostrar = $show_all ? $lista_codigos_destacados : array_slice($lista_codigos_destacados, 0, 6);
    
    foreach($codigos_a_mostrar as $index => $codigo) {
        $html .= generate_single_featured_card($codigo, $index);
    }
    
    $html .= '</div>';
    
    // Si hay más de 6 códigos y no se muestran todos, añadir botón "Ver más"
    if(count($lista_codigos_destacados) > 6 && !$show_all) {
        $html .= '<div class="load-more-container">';
        $html .= '<button class="load-more-btn" id="loadMoreFeatured" data-total="' . count($lista_codigos_destacados) . '" data-loaded="6">';
        $html .= '<span class="btn-text">Ver más códigos destacados</span>';
        $html .= '<span class="btn-loading" style="display: none;">';
        $html .= '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        $html .= '</span>';
        $html .= '</button>';
        $html .= '</div>';
        
        // Añadir datos de códigos restantes para JavaScript con información completa
        $codigos_restantes = array_slice($lista_codigos_destacados, 6);
        
        // Enriquecer los datos con información de usuario y marca
        $codigos_enriquecidos = [];
        foreach($codigos_restantes as $codigo) {
            $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($codigo["id_usuario"]));
            $marca = getObjectMarca('nombre_clave', $codigo["marca"]);
            
            $codigo_enriquecido = $codigo;
            $codigo_enriquecido['usuario_username'] = $usuario['username'] ?? 'Usuario';
            $codigo_enriquecido['usuario_imagen'] = $usuario['imagen'] ?? '/img/no_image.png';
            $codigo_enriquecido['marca_imagen'] = $marca['imagen'] ?? '/img/no_image.png';
            
            $codigos_enriquecidos[] = $codigo_enriquecido;
        }
        
        $html .= '<script>';
        $html .= 'window.featuredCodesData = ' . json_encode($codigos_enriquecidos) . ';';
        $html .= '</script>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function generate_single_featured_card($codigo, $index = 0) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $ratings = isset($codigo['num_valoraciones']) ? $codigo['num_valoraciones'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    
    // Obtener información del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca para el logo
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    // Crear enlace a la página de la marca
    $marca_url = '/de-' . strtolower($brand);
    
    // Limpiar descripción
    $description = strip_tags($description);
    $description = mb_substr($description, 0, 120) . (mb_strlen($description) > 120 ? '...' : '');
    
    $html = '<div class="featured-card" data-code-id="' . htmlspecialchars($code_id) . '">';
    
    // Badge destacado
    $html .= '<div class="featured-badge">';
    $html .= '<i class="fas fa-star"></i> Destacado';
    $html .= '</div>';
    
    // Logo de la marca (clickeable)
    $html .= '<div class="featured-brand-logo">';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="brand-link">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="Logo de ' . htmlspecialchars($brand) . '" class="brand-logo-img">';
    } else {
        $html .= '<div class="brand-logo-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</a>';
    $html .= '</div>';
    
    // Header de la tarjeta con usuario
    $html .= '<div class="featured-card-header">';
    $html .= '<div class="featured-brand">' . htmlspecialchars($brand) . '</div>';
    $html .= '<div class="featured-user-info">';
    $html .= '<div class="featured-user-avatar">';
    if($user_img) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="featured-user-img">';
    } else {
        $html .= '<div class="featured-user-placeholder">';
        $html .= '<i class="fas fa-user"></i>';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="featured-user-details">';
    $html .= '<span class="featured-user-name">' . htmlspecialchars($username) . '</span>';

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="featured-description">' . htmlspecialchars($description) . '</div>';
    
    // Información adicional
    $html .= '<div class="featured-stats">';
    
    if($benefit > 0) {
        $html .= '<span class="featured-stat"><i class="fas fa-euro-sign"></i> ' . $benefit . ' beneficio</span>';
    }
    
    if($ratings > 0) {
        $html .= '<span class="featured-stat"><i class="fas fa-star"></i> ' . $ratings . ' valoraciones</span>';
    }
    
    $html .= '</div>';
    
    // Botón de acción
    $html .= '<button class="featured-button" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand) . '\')">';
    $html .= '<i class="fas fa-eye"></i> Ver Código';
    $html .= '</button>';
    
    $html .= '</div>';
    
    return $html;
}

function get_modern_additional_css() {
    return '
    <style>
    .featured-section {
        margin: 3rem 0;
        padding: 2rem 0;
        background: linear-gradient(135deg, var(--dark-gray) 0%, #1A1A1A 100%);
        border-radius: 20px;
    }
    
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }
    
    .featured-card {
        background: var(--light-gray);
        border-radius: 20px;
        padding: 2rem;
        position: relative;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 2px solid var(--primary-orange);
        color: var(--text-white);
    }
    
    .featured-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
    }
    
    .featured-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        background: var(--text-white);
        color: var(--primary-orange);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.8rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .featured-brand-logo {
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .featured-brand-logo .brand-link {
        display: inline-block;
        text-decoration: none;
        transition: transform 0.3s ease;
    }
    
    .featured-brand-logo .brand-link:hover {
        transform: scale(1.05);
    }
    
    .featured-brand-logo .brand-logo-img {
        max-width: 80px;
        max-height: 60px;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px;
    }
    
    .featured-brand-logo .brand-logo-placeholder {
        width: 80px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto;
    }
    
    .featured-brand {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--text-white);
        margin-bottom: 1rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        text-align: center;
    }
    
    .featured-description {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1.5rem;
        line-height: 1.5;
        font-size: 1rem;
    }
    
    .featured-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }
    
    .featured-stat {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .featured-stat i {
        color: var(--text-white);
    }
    
    .featured-button {
        background: var(--text-white);
        color: var(--primary-orange);
        border: none;
        border-radius: 25px;
        padding: 0.75rem 1.5rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 1rem;
    }
    
    .featured-button:hover {
        background: rgba(255, 255, 255, 0.9);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .pagination-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 2rem 0;
        padding: 1rem;
        background-color: var(--light-gray);
        border-radius: 10px;
    }
    
    .pagination-info {
        color: var(--text-gray);
        font-size: 0.9rem;
    }
    
    .pagination-controls {
        display: flex;
        gap: 0.5rem;
    }
    
    .pagination-btn {
        background-color: var(--dark-gray);
        color: var(--text-white);
        border: none;
        border-radius: 5px;
        padding: 0.5rem 1rem;
        text-decoration: none;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .pagination-btn:hover {
        background-color: var(--primary-orange);
    }
    
    .pagination-btn.active {
        background-color: var(--primary-orange);
    }
    
    .categories-modern {
        margin: 3rem 0;
    }
    
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    
    .category-card {
        background-color: var(--light-gray);
        border-radius: 15px;
        padding: 2rem 1.5rem;
        text-align: center;
        transition: transform 0.3s ease;
        cursor: pointer;
        border: 1px solid #555;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        background-color: #4A4A4A;
    }
    
    .category-icon {
        font-size: 2.5rem;
        color: var(--primary-orange);
        margin-bottom: 1rem;
    }
    
    .category-name {
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--text-white);
        margin-bottom: 0.5rem;
    }
    
    .category-count {
        color: var(--text-gray);
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .featured-grid {
            grid-template-columns: 1fr;
        }
        
        .featured-stats {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .pagination-modern {
            flex-direction: column;
            gap: 1rem;
        }
        
        .pagination-controls {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .categories-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
    
    /* Estilos para códigos destacados en página de marca */
    .featured-product {
        border: 2px solid #FFD700 !important;
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.3) !important;
        position: relative;
    }
    
    .featured-product:hover {
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.5) !important;
        transform: translateY(-6px) !important;
    }
    
    .featured-product .featured-badge {
        position: absolute;
        top: -10px;
        right: 15px;
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: #000;
        padding: 0.4rem 0.8rem;
        border-radius: 15px;
        font-weight: bold;
        font-size: 0.75rem;
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        z-index: 10;
    }
    
    .featured-product .featured-badge i {
        margin-right: 0.3rem;
    }
    
    /* Estilos para información detallada de la marca */
    .brand-description-short {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }
    
    .brand-description-short h3 {
        color: var(--text-white);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .brand-description-short h3:before {
        content: "ℹ️";
        font-size: 1.1rem;
    }
    
    .brand-description-short p {
        color: var(--text-light);
        line-height: 1.6;
        margin: 0;
        font-size: 0.95rem;
    }
    
    .brand-detailed-info {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
        border-radius: 20px;
        padding: 2rem;
        margin: 2rem 0;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .brand-detailed-content h2 {
        color: var(--text-white);
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 2px solid var(--primary-orange);
        padding-bottom: 0.75rem;
    }
    
    .brand-detailed-content h2:before {
        content: "📋";
        font-size: 1.3rem;
    }
    
    .brand-detailed-description {
        color: var(--text-light);
        line-height: 1.7;
        font-size: 1rem;
    }
    
    .brand-detailed-description h3,
    .brand-detailed-description h4 {
        color: var(--text-white);
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    
    .brand-detailed-description h3 {
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .brand-detailed-description h4 {
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .brand-detailed-description ul,
    .brand-detailed-description ol {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }
    
    .brand-detailed-description li {
        margin-bottom: 0.5rem;
        color: var(--text-light);
    }
    
    .brand-detailed-description strong {
        color: var(--primary-orange);
        font-weight: 600;
    }
    
    .brand-detailed-description a {
        color: var(--primary-orange);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .brand-detailed-description a:hover {
        color: #ff8c42;
        text-decoration: underline;
    }
    
    .brand-detailed-description blockquote {
        border-left: 4px solid var(--primary-orange);
        padding-left: 1rem;
        margin: 1.5rem 0;
        font-style: italic;
        background: rgba(255, 107, 53, 0.1);
        padding: 1rem;
        border-radius: 0 10px 10px 0;
    }
    
    /* Responsive para información de marca */
    @media (max-width: 768px) {
        .brand-description-short {
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .brand-description-short h3 {
            font-size: 1.1rem;
        }
        
        .brand-detailed-info {
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .brand-detailed-content h2 {
            font-size: 1.3rem;
        }
        
        .brand-detailed-description {
            font-size: 0.95rem;
        }
    }
    </style>';
}

// Función para generar la página de marca con layout moderno
function generate_brand_page_layout($marca, $codigos) {
    // Obtener información de la marca para el fondo
    $marca_info = get_brand_info($marca);
    $imagen_fondo = $marca_info['imagen'] ?? '';
    
    $html = '<div class="brand-page-container">';
    
    // Fondo de la marca con efectos de scroll
    if($imagen_fondo) {
        $html .= '<div class="brand-background">';
        $html .= '<div class="brand-bg-image" style="background-image: url(\'' . htmlspecialchars($imagen_fondo) . '\');"></div>';
        $html .= '<div class="brand-bg-overlay"></div>';
        $html .= '</div>';
    }
    
    // Contenido principal
    $html .= '<div class="brand-main-content">';
    
    // Header de la marca
    $html .= generate_brand_header($marca);
    
    // Publicidad superior de marcas
    $html .= generate_adsense_container(get_adsense_top_marcas(), 'adsense-top-marcas', 'margin: 20px 0;');
    
    // Sección de códigos destacados (como en el home)
    $html .= generate_brand_featured_section($marca, $codigos);
    
    // Sección de últimos códigos publicados (como en el home)
    $html .= generate_brand_latest_section($marca, $codigos);
    
    // Publicidad entremedio después del grid
    $html .= generate_adsense_container(get_adsense_entremedio(), 'adsense-entremedio', 'margin: 30px 0;');
    
    // Secciones adicionales
    $html .= generate_featured_offers_section($marca);
    $html .= generate_popular_categories_section($marca);
    
    $html .= '</div>'; // brand-main-content
    $html .= '</div>'; // brand-page-container
    
    return $html;
}


// Función para generar la sección de códigos destacados en páginas de marca
function generate_brand_featured_section($marca, $codigos) {
    global $keywords, $actual_link, $detect_device, $url_usuario_sin_imagen, $tipo_block_codigos, $data_usuario, $provincia, $marca, $num_codigos_global;
    
    // Filtrar códigos destacados
    $codigos_destacados = array_filter($codigos, function($codigo) {
        return isset($codigo['destacado']) && $codigo['destacado'] > 0;
    });
    
    if(empty($codigos_destacados)) {
        return '';
    }
    
    // Ordenar por fecha de destacado (más reciente primero)
    usort($codigos_destacados, function($a, $b) {
        $fecha_a = isset($a['destacado']) ? $a['destacado'] : 0;
        $fecha_b = isset($b['destacado']) ? $b['destacado'] : 0;
        return $fecha_b - $fecha_a;
    });
    
    // Tomar solo los primeros 6 códigos destacados
    $codigos_destacados = array_slice($codigos_destacados, 0, 6);
    
    $html = '<div class="row empieza_home">';
    $html .= '<div class="container">';
    $html .= '<div class="col-md-12 columns small-12 slider">';
    $html .= '<div class="title">';
    $html .= '<h2>Códigos Promocionales para ' . ucfirst($marca) . '</h2>';
    $html .= '</div>';
    $html .= '<div class="destacado_div">';
    $html .= '<div class="listado_codigos">';
    
    // Usar la función block_listado_codigos con tipo "destacados" para mantener el diseño del home
    ob_start();
    block_listado_codigos($codigos_destacados, "destacados");
    $html .= ob_get_clean();
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar la sección de últimos códigos en páginas de marca
function generate_brand_latest_section($marca, $codigos) {
    global $keywords, $actual_link, $detect_device, $url_usuario_sin_imagen, $tipo_block_codigos, $data_usuario, $provincia, $marca, $num_codigos_global;
    
    if(empty($codigos)) {
        return '';
    }
    
    // Ordenar por fecha de publicación (más reciente primero)
    usort($codigos, function($a, $b) {
        $fecha_a = isset($a['fecha_publicacion']) ? strtotime($a['fecha_publicacion']) : 0;
        $fecha_b = isset($b['fecha_publicacion']) ? strtotime($b['fecha_publicacion']) : 0;
        return $fecha_b - $fecha_a;
    });
    
    // Tomar solo los primeros 12 códigos
    $codigos_ultimos = array_slice($codigos, 0, 12);
    
    $html = '<div class="container ultimos_container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-12 div_entro_codigos">';
    $html .= '<div class="cd-home-title titulo_zona_home">Últimos códigos publicados</div>';
    $html .= '<div class="bloque_publica_nuevo_codigo">';
    $html .= '<div class="col-md-12 text-center">';
    $html .= '<p class="titulo_zona_home">Mostrando del 1 al ' . count($codigos_ultimos) . ' de un total de <b style="display:block;font-size:20px;">' . count($codigos) . ' Códigos Amigo</b></p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="listado_codigos">';
    
    // Usar la función block_listado_codigos con tipo "home" para mantener el diseño del home
    ob_start();
    block_listado_codigos($codigos_ultimos, "home");
    $html .= ob_get_clean();
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar el sidebar con filtros
function generate_brand_sidebar($marca) {
    // Obtener el filtro de fecha actual de la URL
    $fecha_actual = isset($_GET['fecha']) ? $_GET['fecha'] : 'hoy';
    
    $html = '<div class="brand-sidebar">';
    
    // Botón hamburguesa para móviles
    $html .= '<button class="filter-toggle-btn" onclick="toggleBrandSidebar()">';
    $html .= '<span>Filtros</span>';
    $html .= '<i class="fas fa-chevron-down"></i>';
    $html .= '</button>';
    
    $html .= '<div class="filters-section">';
    $html .= '<h3 class="filters-title">Filtros</h3>';
    
    // Filtro por fecha
    $html .= '<div class="filter-group">';
    $html .= '<h4 class="filter-group-title">Por fecha</h4>';
    
    // Opción "Publicados hoy"
    $checked_hoy = ($fecha_actual === 'hoy') ? ' checked' : '';
    $html .= '<div class="filter-radio">';
    $html .= '<input type="radio" name="fecha" id="fecha-hoy" value="hoy"' . $checked_hoy . '>';
    $html .= '<label for="fecha-hoy">Publicados hoy</label>';
    $html .= '</div>';
    
    // Opción "Publicados la semana pasada"
    $checked_semana = ($fecha_actual === 'semana') ? ' checked' : '';
    $html .= '<div class="filter-radio">';
    $html .= '<input type="radio" name="fecha" id="fecha-semana" value="semana"' . $checked_semana . '>';
    $html .= '<label for="fecha-semana">Publicados la semana pasada</label>';
    $html .= '</div>';
    
    // Opción "Publicados todo el tiempo"
    $checked_todo = ($fecha_actual === 'todo') ? ' checked' : '';
    $html .= '<div class="filter-radio">';
    $html .= '<input type="radio" name="fecha" id="fecha-todo" value="todo"' . $checked_todo . '>';
    $html .= '<label for="fecha-todo">Publicados todo el tiempo</label>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botón de aplicar filtros
    $html .= '<div class="filter-actions">';
    $html .= '<button class="btn-apply-filters" onclick="applyFilters()">Aplicar filtros</button>';
    $html .= '<button class="btn-clear-filters" onclick="clearFilters()">Limpiar filtros</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // filters-section
    $html .= '</div>'; // brand-sidebar
    
    return $html;
}

// Función para generar el menú de filtros estilo Chollometro
function generate_chollometro_filter_menu($marca) {
    // Obtener el filtro de fecha actual de la URL
    $fecha_actual = isset($_GET['fecha']) ? $_GET['fecha'] : 'hoy';
    $tipo_actual = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
    
    $html = '<div class="chollometro-filter-container">';
    
    // Barra de filtros principal
    $html .= '<div class="filter-bar">';
    
    // Botón "Más" (hamburger menu)
    $html .= '<button class="filter-btn filter-more-btn" id="filter-more-btn">';
    $html .= '<i class="fas fa-bars"></i>';
    $html .= '<span>Más</span>';
    $html .= '</button>';
    
    // Botones de filtro tipo Chollometro
    $html .= '<div class="filter-buttons">';
    
    // Botón "TODOS"
    $active_class = ($tipo_actual === 'todos') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="todos">';
    $html .= 'TODOS (36)';
    $html .= '</button>';
    
    // Botón "CUPONES"
    $active_class = ($tipo_actual === 'cupones') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="cupones">';
    $html .= 'CUPONES (21)';
    $html .= '</button>';
    
    // Botón "DESCUENTOS"
    $active_class = ($tipo_actual === 'descuentos') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="descuentos">';
    $html .= 'DESCUENTOS (15)';
    $html .= '</button>';
    
    $html .= '</div>'; // filter-buttons
    
    // Botón de búsqueda (lupa)
    $html .= '<button class="filter-btn filter-search-btn" id="filter-search-btn">';
    $html .= '<i class="fas fa-search"></i>';
    $html .= '</button>';
    
    $html .= '</div>'; // filter-bar
    
    // Panel de filtros desplegable (Más)
    $html .= '<div class="filter-panel" id="filter-panel">';
    $html .= '<div class="filter-panel-content">';
    $html .= '<h4>Filtros</h4>';
    
    // Filtro por fecha
    $html .= '<div class="filter-group">';
    $html .= '<h5>Por fecha</h5>';
    
    $checked_hoy = ($fecha_actual === 'hoy') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="hoy"' . $checked_hoy . '>';
    $html .= '<span>Publicados hoy</span>';
    $html .= '</label>';
    
    $checked_semana = ($fecha_actual === 'semana') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="semana"' . $checked_semana . '>';
    $html .= '<span>Publicados la semana pasada</span>';
    $html .= '</label>';
    
    $checked_todo = ($fecha_actual === 'todo') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="todo"' . $checked_todo . '>';
    $html .= '<span>Publicados todo el tiempo</span>';
    $html .= '</label>';
    
    $html .= '</div>'; // filter-group
    
    // Botones de acción
    $html .= '<div class="filter-actions">';
    $html .= '<button class="btn-apply" onclick="applyFilters()">Aplicar filtros</button>';
    $html .= '<button class="btn-clear" onclick="clearFilters()">Limpiar filtros</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // filter-panel-content
    $html .= '</div>'; // filter-panel
    
    // Panel de búsqueda desplegable
    $html .= '<div class="search-panel" id="search-panel">';
    $html .= '<div class="search-panel-content">';
    $html .= '<form class="search-form" onsubmit="performSearch(event)">';
    $html .= '<input type="text" class="search-input" placeholder="Buscar códigos..." value="' . htmlspecialchars(isset($_GET['q']) ? $_GET['q'] : '') . '">';
    $html .= '<button type="submit" class="search-submit">';
    $html .= '<i class="fas fa-search"></i>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>'; // search-panel-content
    $html .= '</div>'; // search-panel
    
    $html .= '</div>'; // chollometro-filter-container
    
    return $html;
}

// Función para generar el header de la marca
function generate_brand_header($marca) {
    // Obtener información específica de la marca
    $marca_info = get_brand_info($marca);
    $nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
    $descripcion_marca = $marca_info['descripcion'] ?? '';
    $descripcion_larga = $marca_info['descripción_larga'] ?? '';
    $total_codigos = $marca_info['codes'] ?? 0;
    $imagen_marca = $marca_info['imagen'] ?? '';
    $web_marca = $marca_info['web'] ?? '';
    $categoria_marca = $marca_info['categoria'] ?? '';
    
    // Generar títulos personalizados según la marca
    $titulos = generate_brand_titles($nombre_marca, $categoria_marca, $total_codigos);
    
    $html = '<div class="brand-header">';
    $html .= '<div class="brand-header-content">';
    
    // Logo de la marca
    if($imagen_marca) {
        $html .= '<div class="brand-logo">';
        $html .= '<img src="' . htmlspecialchars($imagen_marca) . '" alt="Logo de ' . htmlspecialchars($nombre_marca) . '" class="brand-logo-img">';
        $html .= '</div>';
    }
    
    // Información de la marca
    $html .= '<div class="brand-info">';
    
    // H1 personalizado
    $html .= '<h1 class="brand-title">' . $titulos['h1'] . '</h1>';
    
    // H2 personalizado
    $html .= '<h2 class="brand-subtitle">' . $titulos['h2'] . '</h2>';
    
    // Sección de publicar código
    $html .= generate_publicar_codigo_section($marca, $nombre_marca);
    
    // Descripción corta si está disponible (para SEO y resumen)
    if($descripcion_marca) {
        $html .= '<section class="brand-description-short" itemscope itemtype="https://schema.org/Organization">';
        $html .= '<h3>¿Qué es ' . htmlspecialchars($nombre_marca) . '?</h3>';
        $html .= '<p class="brand-description" itemprop="description">' . htmlspecialchars($descripcion_marca) . '</p>';
        $html .= '</section>';
    }
    
    // Enlace a la web oficial si está disponible
    if($web_marca) {
        $html .= '<a href="' . htmlspecialchars($web_marca) . '" target="_blank" class="brand-website">';
        $html .= '<i class="fas fa-external-link-alt"></i> Visitar web oficial';
        $html .= '</a>';
    }
    
    $html .= '</div>'; // brand-info
    $html .= '</div>'; // brand-header-content
    $html .= '</div>'; // brand-header
    
    // Sección de información detallada de la marca (después del header)
    if($descripcion_larga) {
        $html .= '<section class="brand-detailed-info" itemscope itemtype="https://schema.org/Organization">';
        $html .= '<div class="brand-detailed-content">';
        $html .= '<h2>Información detallada sobre ' . htmlspecialchars($nombre_marca) . '</h2>';
        $html .= '<div class="brand-detailed-description" itemprop="description">';
        $html .= html_entity_decode($descripcion_larga);
        $html .= '</div>';
        
        // Agregar datos estructurados para SEO
        if($web_marca) {
            $html .= '<meta itemprop="url" content="' . htmlspecialchars($web_marca) . '">';
        }
        if($imagen_marca) {
            $html .= '<meta itemprop="logo" content="' . htmlspecialchars($imagen_marca) . '">';
        }
        $html .= '<meta itemprop="name" content="' . htmlspecialchars($nombre_marca) . '">';
        
        $html .= '</div>';
        $html .= '</section>';
    }
    
    return $html;
}

// Función para generar las tabs
function generate_brand_tabs() {
    $html = '<div class="brand-tabs">';
    $html .= '<button class="brand-tab active">Ofertas</button>';
    $html .= '<button class="brand-tab">Top ventas</button>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar el grid de productos
function generate_products_grid($codigos) {
    $html = '<div class="products-grid">';
    
    $count = 0;
    foreach($codigos as $codigo) {
        $html .= generate_product_card($codigo);
        $count++;
        
        // Insertar publicidad cada 6 productos
        if ($count % 6 == 0 && $count < count($codigos)) {
            $html .= '<div class="adsense-grid-item" style="grid-column: 1 / -1; margin: 20px 0;">';
            $html .= generate_adsense_container(get_adsense_entremedio(), 'adsense-grid-entremedio', 'margin: 0;');
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

// Función para generar una tarjeta de producto
function generate_product_card($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    
    // Debug: mostrar qué datos de usuario tenemos
    // echo "<!-- Debug usuario: " . print_r($codigo, true) . " -->";
    
    // Obtener información completa del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Limpiar descripción
    $description = strip_tags($description);
    $description = mb_substr($description, 0, 120) . (mb_strlen($description) > 120 ? '...' : '');
    
    // Verificar si el código es destacado
    $is_destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
    $card_class = $is_destacado ? 'product-card featured-product' : 'product-card';
    
    $html = '<div class="' . $card_class . '">';
    
    // Badge destacado si aplica
    if($is_destacado) {
        $html .= '<div class="featured-badge">';
        $html .= '<i class="fas fa-star"></i> Destacado';
        $html .= '</div>';
    }
    
    // Obtener información de la marca para la imagen
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';

    
    // Debug temporal
    // echo "<!-- Debug usuario_id: " . $usuario_id . " -->";
    // echo "<!-- Debug user_info: " . print_r($user_info, true) . " -->";
    
    // Imagen del producto (imagen de la marca)
    $html .= '<div class="product-image">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($brand) . '" class="brand-image">';
    } else {
        $html .= '<i class="fas fa-tag" style="font-size: 3rem; color: #FF6B35;"></i>';
    }
    $html .= '</div>';
    
    // Título del producto (nombre de la marca)
    $html .= '<h3 class="product-title">' . htmlspecialchars($brand) . '</h3>';
    
    // Beneficio mejorado
    if($benefit > 0) {
        $html .= '<div class="beneficio-destacado">';
        $html .= '<div class="beneficio-icono">💰</div>';
        $html .= '<div class="beneficio-contenido">';
        $html .= '<div class="beneficio-cantidad">' . $benefit . '€</div>';
        $html .= '<div class="beneficio-tipo">Beneficio</div>';
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="beneficio-destacado" style="background: linear-gradient(135deg, #FF6B35, #E55A2B);">';
        $html .= '<div class="beneficio-icono">🎯</div>';
        $html .= '<div class="beneficio-contenido">';
        $html .= '<div class="beneficio-cantidad">Descuento</div>';
        $html .= '<div class="beneficio-tipo">Especial</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Descripción
    $html .= '<p class="product-description">' . htmlspecialchars($description) . '</p>';
    
    // Información del usuario con avatar y fecha
    $html .= '<div class="product-user">';
    $html .= '<div class="user-avatar">';
    if($user_img && !empty($user_img)) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="user-avatar-img">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        // Usar iniciales si están disponibles
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span class="user-iniciales">' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-info">';
    $html .= '<span class="user-name">' . htmlspecialchars($username) . '</span>';

    
    // Fecha de publicación
    $fecha_formateada = '';
    if(isset($codigo['fecha_publicacion'])) {
        $fecha_obj = $codigo['fecha_publicacion'];
        if($fecha_obj instanceof MongoDB\BSON\UTCDateTime) {
            $fecha_formateada = $fecha_obj->toDateTime()->format('d/m/Y');
        } elseif($fecha_obj instanceof DateTime) {
            $fecha_formateada = $fecha_obj->format('d/m/Y');
        } elseif(is_string($fecha_obj)) {
            $fecha_formateada = date('d/m/Y', strtotime($fecha_obj));
        }
    }
    
    // Si no hay fecha, usar fecha actual
    if(empty($fecha_formateada)) {
        $fecha_formateada = date('d/m/Y');
    }
    
    $html .= '<span class="user-date">' . $fecha_formateada . '</span>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Acciones
    $html .= '<div class="product-actions">';
    $html .= '<div class="product-icons">';
    $html .= '<div class="product-icon" title="Compartir"><i class="fas fa-share"></i></div>';
    $html .= '</div>';
    $html .= '<button class="view-offer-btn" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand) . '\')">Ver código</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

// Función para generar sección de ofertas destacadas
function generate_featured_offers_section($marca_actual) {
    $html = '<div class="featured-offers">';
    $html .= '<h2 class="featured-offers-title">Otras marcas relacionadas</h2>';
    $html .= '<div class="featured-grid">';
    
    // Obtener otras marcas de la misma categoría
    $marcas_relacionadas = get_related_brands($marca_actual);
    
    if(!empty($marcas_relacionadas)) {
        foreach($marcas_relacionadas as $marca) {
            $nombre = $marca['nombre'] ?? '';
            $nombre_clave = $marca['nombre_clave'] ?? '';
            $imagen = $marca['imagen'] ?? '';
            $codes = $marca['codes'] ?? 0;
            
            if($nombre && $nombre_clave !== $marca_actual) {
                $html .= '<div class="featured-card">';
                $html .= '<div class="product-image" style="height: 120px; display: flex; align-items: center; justify-content: center;">';
                if($imagen) {
                    $html .= '<img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($nombre) . '" style="max-width: 80px; max-height: 80px; object-fit: contain;">';
                } else {
                    $html .= '<i class="fas fa-tag" style="font-size: 2rem; color: #666;"></i>';
                }
                $html .= '</div>';
                $html .= '<h4 class="featured-card-title">' . htmlspecialchars($nombre) . '</h4>';
                $html .= '<div class="featured-card-price">' . $codes . ' códigos</div>';
                $html .= '<a href="/de-' . htmlspecialchars($nombre_clave) . '" class="featured-card-link">Ver ofertas</a>';
                $html .= '</div>';
            }
        }
    } else {
        // Fallback si no hay marcas relacionadas
        $html .= '<div class="featured-card">';
        $html .= '<div class="product-image" style="height: 120px; display: flex; align-items: center; justify-content: center;">';
        $html .= '<i class="fas fa-tag" style="font-size: 2rem; color: #666;"></i>';
        $html .= '</div>';
        $html .= '<h4 class="featured-card-title">Más marcas próximamente</h4>';
        $html .= '<div class="featured-card-price">Próximamente</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar sección de categorías populares
function generate_popular_categories_section($marca_actual) {
    $html = '<div class="popular-categories">';
    $html .= '<h2 class="popular-categories-title">No te pierdas ninguna oferta</h2>';
    $html .= '<div class="categories-grid">';
    
    // Obtener marcas populares de la misma categoría
    $marcas_populares = get_popular_brands_in_category($marca_actual);
    
    if(!empty($marcas_populares)) {
        foreach($marcas_populares as $marca) {
            $nombre = $marca['nombre'] ?? '';
            $nombre_clave = $marca['nombre_clave'] ?? '';
            $imagen = $marca['imagen'] ?? '';
            $codes = $marca['codes'] ?? 0;
            
            if($nombre && $nombre_clave !== $marca_actual) {
                $html .= '<div class="category-item">';
                $html .= '<div class="category-icon">';
                if($imagen) {
                    $html .= '<img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($nombre) . '" style="width: 40px; height: 40px; object-fit: contain;">';
                } else {
                    $html .= '<i class="fas fa-tag"></i>';
                }
                $html .= '</div>';
                $html .= '<div class="category-name">' . htmlspecialchars($nombre) . '</div>';
                $html .= '<div class="category-count">' . $codes . ' códigos</div>';
                $html .= '<a href="/de-' . htmlspecialchars($nombre_clave) . '" class="category-link"></a>';
                $html .= '</div>';
            }
        }
    } else {
        // Fallback si no hay marcas populares
        $html .= '<div class="category-item">';
        $html .= '<div class="category-icon"><i class="fas fa-tag"></i></div>';
        $html .= '<div class="category-name">Más marcas próximamente</div>';
        $html .= '<div class="category-count">Próximamente</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar la página de detalle del código
/**
 * Función para detectar URLs y extraer códigos de descuento
 */
function detect_url_and_extract_code($codigo_text) {
    // Patrones para detectar URLs
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    $code_patterns = [
        '/[?&]via=([^&]+)/i',           // via=9ab7ca
        '/[?&]code=([^&]+)/i',          // code=ABC123
        '/[?&]coupon=([^&]+)/i',        // coupon=SAVE20
        '/[?&]promo=([^&]+)/i',         // promo=WELCOME
        '/[?&]ref=([^&]+)/i',           // ref=USER123
        '/[?&]discount=([^&]+)/i',      // discount=50OFF
        '/[?&]offer=([^&]+)/i',         // offer=SUMMER
        '/[?&]deal=([^&]+)/i',          // deal=BLACKFRIDAY
        '/[?&]voucher=([^&]+)/i',       // voucher=GIFT50
        '/[?&]token=([^&]+)/i',         // token=ABC123
        '/[?&]key=([^&]+)/i',           // key=SECRET123
        '/[?&]id=([^&]+)/i',            // id=USER456
        '/[?&]affiliate=([^&]+)/i',     // affiliate=PARTNER
        '/[?&]partner=([^&]+)/i',       // partner=REF123
        '/[?&]source=([^&]+)/i',        // source=FRIEND
        '/[?&]utm_source=([^&]+)/i',    // utm_source=EMAIL
        '/[?&]utm_campaign=([^&]+)/i',  // utm_campaign=SUMMER2024
        '/[?&]utm_content=([^&]+)/i',   // utm_content=BANNER
        '/[?&]utm_medium=([^&]+)/i',    // utm_medium=SOCIAL
        '/[?&]utm_term=([^&]+)/i',      // utm_term=DISCOUNT
    ];
    
    $result = [
        'is_url' => false,
        'url' => '',
        'extracted_code' => '',
        'display_text' => $codigo_text
    ];
    
    // Verificar si es una URL
    if (preg_match($url_pattern, $codigo_text, $url_matches)) {
        $result['is_url'] = true;
        $result['url'] = $url_matches[1];
        
        // Intentar extraer código de la URL
        foreach ($code_patterns as $pattern) {
            if (preg_match($pattern, $codigo_text, $code_matches)) {
                $result['extracted_code'] = $code_matches[1];
                $result['display_text'] = $code_matches[1];
                break;
            }
        }
        
        // Si no se encontró código específico, usar la URL completa
        if (empty($result['extracted_code'])) {
            $result['display_text'] = $codigo_text;
        }
    }
    
    return $result;
}

function generate_code_detail_page($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $date = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    $destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
    
    // Obtener el código real del campo 'codigo'
    $codigo_real = isset($codigo['codigo']) ? $codigo['codigo'] : '';
    
    // Detectar si es URL y extraer código
    $code_info = detect_url_and_extract_code($codigo_real);
    
    // Obtener información completa del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca
    $marca_info = get_brand_info($brand);
    $marca_nombre = $marca_info['nombre'] ?? ucfirst($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    $html = '<div class="container-fluid main_entremedio">';
    
    // Hero Section
    $html .= '<div class="code-detail-hero">';
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-12">';
    
    // Breadcrumb
    $html .= '<nav class="breadcrumb-nav">';
    $html .= '<a href="/" class="breadcrumb-link">Inicio</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<a href="/de-' . $brand . '" class="breadcrumb-link">' . $marca_nombre . '</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<span class="breadcrumb-current">Código de descuento</span>';
    $html .= '</nav>';
    
    // Header principal
    $html .= '<div class="code-detail-header">';
    $html .= '<div class="header-left">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-logo">';
    }
    $html .= '<div class="header-text">';
    $html .= '<h1>Código de Descuento ' . $marca_nombre . '</h1>';
    $html .= '<p class="header-subtitle">Código verificado y actualizado</p>';
    if($destacado) {
        $html .= '<span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="header-actions">';
    $html .= '<button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Volver</button>';
    $html .= '<button class="btn-share" onclick="shareCode()"><i class="fas fa-share"></i> Compartir</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Contenido principal
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-8">';
    
    // Código principal
    $html .= '<div class="code-main-card">';
    $html .= '<div class="code-benefit-display">';
    $html .= '<div class="benefit-amount">' . $benefit . '€</div>';
    $html .= '<div class="benefit-label">de beneficio</div>';
    $html .= '</div>';
    
    $html .= '<div class="code-display-container">';
    $html .= '<h3><i class="fas fa-tag"></i> Tu código de descuento</h3>';
    $html .= '<div class="code-text" id="codeText">' . strtoupper($code_info['display_text']) . '</div>';
    
    if ($code_info['is_url']) {
        $html .= '<div class="code-url-section">';
        $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
        $html .= '<a href="' . htmlspecialchars($code_info['url']) . '" target="_blank" class="code-url-link">';
        $html .= '<i class="fas fa-external-link-alt"></i>';
        $html .= '<span>' . htmlspecialchars($code_info['url']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '<button class="btn-copy-code" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="code-description-card">';
    $html .= '<h3><i class="fas fa-info-circle"></i> Descripción de la oferta</h3>';
    $html .= '<p>' . htmlspecialchars($description) . '</p>';
    $html .= '</div>';
    
    // Cómo usar
    $html .= '<div class="how-to-use-card">';
    $html .= '<h3><i class="fas fa-question-circle"></i> ¿Cómo usar este código?</h3>';
    $html .= '<div class="steps-container">';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">1</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Copia el código</h4>';
    $html .= '<p>Haz clic en "Copiar código" para copiarlo al portapapeles</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">2</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Ve a ' . $marca_nombre . '</h4>';
    $html .= '<p>Accede a la web oficial de ' . $marca_nombre . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">3</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Aplica el código</h4>';
    $html .= '<p>Pega el código en el campo correspondiente durante el checkout</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-8
    
    // Sidebar
    $html .= '<div class="col-md-4">';
    
    // Información del usuario
    $html .= '<div class="user-info-card">';
    $html .= '<h4><i class="fas fa-user"></i> Publicado por</h4>';
    $html .= '<div class="user-profile">';
    $html .= '<div class="user-avatar">';
    if($user_img && !empty($user_img)) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span>' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details">';
    $html .= '<div class="user-name">' . htmlspecialchars($username) . '</div>';
    $html .= '<div class="user-date">Publicado el ' . date('d/m/Y', $date instanceof DateTime ? $date->getTimestamp() : time()) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Información adicional
    $html .= '<div class="code-info-card">';
    $html .= '<h4><i class="fas fa-shield-alt"></i> Información del código</h4>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Estado:</span>';
    $html .= '<span class="info-value verified"><i class="fas fa-check-circle"></i> Verificado</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Beneficio:</span>';
    $html .= '<span class="info-value">' . $benefit . '€</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Marca:</span>';
    $html .= '<span class="info-value">' . $marca_nombre . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botones de acción
    $html .= '<div class="action-buttons-card">';
    $html .= '<button class="btn-primary" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '<button class="btn-secondary" onclick="shareCode()">';
    $html .= '<i class="fas fa-share"></i> Compartir';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-4
    $html .= '</div>'; // row
    $html .= '</div>'; // container
    $html .= '</div>'; // container-fluid
    
    // Agregar estilos CSS increíbles para el nuevo diseño
    $html .= '<style>
    /* Hero Section */
    .code-detail-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .breadcrumb-nav {
        margin-bottom: 30px;
    }
    
    .breadcrumb-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: white;
        text-decoration: none;
    }
    
    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.6);
        margin: 0 10px;
    }
    
    .breadcrumb-current {
        color: white;
        font-weight: 600;
    }
    
    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        background: white;
        border-radius: 15px;
        padding: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .header-text h1 {
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        margin: 0 0 15px 0;
    }
    
    .featured-badge {
        background: #ff6b35;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-back, .btn-share {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }
    
    .btn-back:hover, .btn-share:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Main Content */
    .code-main-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: 1px solid #f0f0f0;
    }
    
    .code-benefit-display {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        border-radius: 20px;
        color: white;
    }
    
    .benefit-amount {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .benefit-label {
        font-size: 1.2rem;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .code-display-container h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-text {
        font-family: "Courier New", monospace;
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: 4px;
        text-align: center;
        padding: 40px;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(44, 62, 80, 0.3);
        border: 3px solid #ff6b35;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .code-text::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .code-url-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 2px solid #e9ecef;
    }
    
    .url-label {
        color: #666;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .code-url-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ff6b35;
        text-decoration: none;
        padding: 15px 20px;
        background: white;
        border: 2px solid #ff6b35;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        word-break: break-all;
    }
    
    .code-url-link:hover {
        background: #ff6b35;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-copy-code {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 20px 40px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    .btn-copy-code:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
    }
    
    /* Cards */
    .code-description-card, .how-to-use-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .code-description-card h3, .how-to-use-card h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-description-card p {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.8;
        margin: 0;
    }
    
    .steps-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    
    .step-number {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
    }
    
    .step-content h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .step-content p {
        color: #666;
        margin: 0;
        line-height: 1.6;
    }
    
    /* Sidebar */
    .user-info-card, .code-info-card, .action-buttons-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .user-info-card h4, .code-info-card h4 {
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .user-name {
        color: #333;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .user-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
        font-weight: 700;
    }
    
    .info-value.verified {
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 15px;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e9ecef;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .code-detail-header {
            flex-direction: column;
            text-align: center;
        }
        
        .header-left {
            flex-direction: column;
            text-align: center;
        }
        
        .header-text h1 {
            font-size: 2rem;
        }
        
        .benefit-amount {
            font-size: 3rem;
        }
        
        .code-text {
            font-size: 1.8rem;
            padding: 30px 20px;
            letter-spacing: 2px;
        }
        
        .code-main-card, .code-description-card, .how-to-use-card {
            padding: 25px;
        }
        
        .user-info-card, .code-info-card, .action-buttons-card {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .code-detail-hero {
            padding: 20px 0;
        }
        
        .header-text h1 {
            font-size: 1.5rem;
        }
        
        .benefit-amount {
            font-size: 2.5rem;
        }
        
        .code-text {
            font-size: 1.5rem;
            padding: 25px 15px;
            letter-spacing: 1px;
        }
        
        .step-item {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>';
    
    // Agregar JavaScript para funcionalidades
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        const text = codeText.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            // Cambiar el texto del botón temporalmente
            const btn = document.querySelector(".btn-copy-code");
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = "<i class=\\"fas fa-check\\"></i> ¡Copiado!";
                btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                }, 2000);
            }
        }).catch(function(err) {
            console.error("Error al copiar: ", err);
            alert("Error al copiar el código");
        });
    }
    
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/";
        }
    }
    
    function shareCode() {
        const url = window.location.href;
        const title = document.title;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            navigator.clipboard.writeText(url).then(function() {
                alert("Enlace copiado al portapapeles");
            });
        }
    }
    
    function toggleFavorite() {
        // Implementar funcionalidad de favoritos
        alert("Funcionalidad de favoritos próximamente");
    }
    </script>';
    
    return $html;
}

// Función para obtener marcas relacionadas de la misma categoría
function get_related_brands($marca_actual) {
    // Obtener información de la marca actual de forma segura
    try {
        $marca_info = get_brand_info($marca_actual);
        $categoria_actual = $marca_info['categoria'] ?? '';
    } catch (Exception $e) {
        $categoria_actual = '';
    }
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas de la misma categoría
    $marcas_relacionadas = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_relacionadas[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas relacionadas
    $marcas_relacionadas = array_slice($marcas_relacionadas, 0, 6);
    
    return $marcas_relacionadas;
}

// Función para obtener marcas populares de la misma categoría
function get_popular_brands_in_category($marca_actual) {
    // Obtener información de la marca actual
    $marca_info = get_brand_info($marca_actual);
    $categoria_actual = $marca_info['categoria'] ?? '';
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas populares de la misma categoría
    $marcas_populares = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20, 'sort' => ['total_codigos' => -1]]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_populares[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas populares
    $marcas_populares = array_slice($marcas_populares, 0, 6);
    
    return $marcas_populares;
}

// Función para detectar URLs y extraer códigos de descuento
function detect_url_and_extract_code($codigo_text) {
    // Patrones para detectar URLs
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    $code_patterns = [
        '/[?&]via=([^&]+)/i',           // via=9ab7ca
        '/[?&]code=([^&]+)/i',          // code=ABC123
        '/[?&]coupon=([^&]+)/i',        // coupon=SAVE20
        '/[?&]promo=([^&]+)/i',         // promo=WELCOME
        '/[?&]ref=([^&]+)/i',           // ref=USER123
        '/[?&]discount=([^&]+)/i',      // discount=50OFF
        '/[?&]offer=([^&]+)/i',         // offer=SUMMER
        '/[?&]deal=([^&]+)/i',          // deal=BLACKFRIDAY
        '/[?&]voucher=([^&]+)/i',       // voucher=GIFT50
        '/[?&]token=([^&]+)/i',         // token=ABC123
        '/[?&]key=([^&]+)/i',           // key=SECRET123
        '/[?&]id=([^&]+)/i',            // id=USER456
        '/[?&]affiliate=([^&]+)/i',     // affiliate=PARTNER
        '/[?&]partner=([^&]+)/i',       // partner=REF123
        '/[?&]source=([^&]+)/i',        // source=FRIEND
        '/[?&]utm_source=([^&]+)/i',    // utm_source=EMAIL
        '/[?&]utm_campaign=([^&]+)/i',  // utm_campaign=SUMMER
        '/[?&]utm_medium=([^&]+)/i',    // utm_medium=EMAIL
        '/[?&]utm_term=([^&]+)/i',      // utm_term=DESCUENTO
        '/[?&]utm_content=([^&]+)/i',   // utm_content=BANNER
        '/[?&]click_id=([^&]+)/i',      // click_id=ABC123
        '/[?&]tracking_id=([^&]+)/i',   // tracking_id=XYZ789
        '/[?&]referral=([^&]+)/i',      // referral=FRIEND
        '/[?&]invite=([^&]+)/i',        // invite=USER123
        '/[?&]promo_code=([^&]+)/i',    // promo_code=SAVE20
        '/[?&]discount_code=([^&]+)/i', // discount_code=WELCOME
        '/[?&]coupon_code=([^&]+)/i',   // coupon_code=BLACKFRIDAY
        '/[?&]voucher_code=([^&]+)/i',  // voucher_code=GIFT50
        '/[?&]offer_code=([^&]+)/i',    // offer_code=SUMMER
        '/[?&]deal_code=([^&]+)/i',     // deal_code=FLASH
        '/[?&]special=([^&]+)/i',       // special=VIP
        '/[?&]bonus=([^&]+)/i',         // bonus=EXTRA
        '/[?&]reward=([^&]+)/i',        // reward=POINTS
        '/[?&]credit=([^&]+)/i',        // credit=100
        '/[?&]cashback=([^&]+)/i',      // cashback=5
        '/[?&]rebate=([^&]+)/i',        // rebate=10
        '/[?&]refund=([^&]+)/i',        // refund=20
        '/[?&]return=([^&]+)/i',        // return=30
        '/[?&]exchange=([^&]+)/i',      // exchange=40
        '/[?&]upgrade=([^&]+)/i',       // upgrade=PREMIUM
        '/[?&]downgrade=([^&]+)/i',     // downgrade=BASIC
        '/[?&]trial=([^&]+)/i',         // trial=7DAYS
        '/[?&]demo=([^&]+)/i',          // demo=FREE
        '/[?&]sample=([^&]+)/i',        // sample=SMALL
        '/[?&]test=([^&]+)/i',          // test=BETA
        '/[?&]preview=([^&]+)/i',       // preview=EARLY
        '/[?&]access=([^&]+)/i',        // access=VIP
        '/[?&]membership=([^&]+)/i',    // membership=GOLD
        '/[?&]subscription=([^&]+)/i',  // subscription=YEARLY
        '/[?&]plan=([^&]+)/i',          // plan=PRO
        '/[?&]package=([^&]+)/i',       // package=DELUXE
        '/[?&]bundle=([^&]+)/i',        // bundle=SAVE
        '/[?&]combo=([^&]+)/i',         // combo=SPECIAL
        '/[?&]set=([^&]+)/i',           // set=COMPLETE
        '/[?&]kit=([^&]+)/i',           // kit=STARTER
        '/[?&]box=([^&]+)/i',           // box=MYSTERY
        '/[?&]case=([^&]+)/i',          // case=PROTECT
        '/[?&]cover=([^&]+)/i',         // cover=SCREEN
        '/[?&]skin=([^&]+)/i',          // skin=CUSTOM
        '/[?&]theme=([^&]+)/i',         // theme=DARK
        '/[?&]style=([^&]+)/i',         // style=MODERN
        '/[?&]color=([^&]+)/i',         // color=BLUE
        '/[?&]size=([^&]+)/i',          // size=LARGE
        '/[?&]weight=([^&]+)/i',        // weight=HEAVY
        '/[?&]length=([^&]+)/i',        // length=SHORT
        '/[?&]width=([^&]+)/i',         // width=NARROW
        '/[?&]height=([^&]+)/i',        // height=TALL
        '/[?&]depth=([^&]+)/i',         // depth=DEEP
        '/[?&]volume=([^&]+)/i',        // volume=LOUD
        '/[?&]speed=([^&]+)/i',         // speed=FAST
        '/[?&]power=([^&]+)/i',         // power=HIGH
        '/[?&]energy=([^&]+)/i',        // energy=LOW
        '/[?&]force=([^&]+)/i',         // force=STRONG
        '/[?&]strength=([^&]+)/i',      // strength=WEAK
        '/[?&]quality=([^&]+)/i',       // quality=PREMIUM
        '/[?&]grade=([^&]+)/i',         // grade=A
        '/[?&]level=([^&]+)/i',         // level=EXPERT
        '/[?&]rank=([^&]+)/i',          // rank=1
        '/[?&]position=([^&]+)/i',      // position=TOP
        '/[?&]place=([^&]+)/i',         // place=FIRST
        '/[?&]spot=([^&]+)/i',          // spot=VIP
        '/[?&]seat=([^&]+)/i',          // seat=WINDOW
        '/[?&]room=([^&]+)/i',          // room=SUITE
        '/[?&]space=([^&]+)/i',         // space=PRIVATE
        '/[?&]area=([^&]+)/i',          // area=EXCLUSIVE
        '/[?&]zone=([^&]+)/i',          // zone=RESTRICTED
        '/[?&]region=([^&]+)/i',        // region=EUROPE
        '/[?&]country=([^&]+)/i',       // country=SPAIN
        '/[?&]city=([^&]+)/i',          // city=MADRID
        '/[?&]state=([^&]+)/i',         // state=CALIFORNIA
        '/[?&]province=([^&]+)/i',      // province=BARCELONA
        '/[?&]district=([^&]+)/i',      // district=CENTRAL
        '/[?&]neighborhood=([^&]+)/i',  // neighborhood=DOWNTOWN
        '/[?&]street=([^&]+)/i',        // street=MAIN
        '/[?&]avenue=([^&]+)/i',        // avenue=BROADWAY
        '/[?&]boulevard=([^&]+)/i',     // boulevard=SUNSET
        '/[?&]road=([^&]+)/i',          // road=HIGHWAY
        '/[?&]way=([^&]+)/i',           // way=PATH
        '/[?&]lane=([^&]+)/i',          // lane=ALLEY
        '/[?&]drive=([^&]+)/i',         // drive=PARKWAY
        '/[?&]court=([^&]+)/i',         // court=PLAZA
        '/[?&]place=([^&]+)/i',         // place=SQUARE
        '/[?&]circle=([^&]+)/i',        // circle=ROUND
        '/[?&]square=([^&]+)/i',        // square=BLOCK
        '/[?&]triangle=([^&]+)/i',      // triangle=SHAPE
        '/[?&]rectangle=([^&]+)/i',     // rectangle=FORM
        '/[?&]oval=([^&]+)/i',          // oval=EGG
        '/[?&]diamond=([^&]+)/i',       // diamond=GEM
        '/[?&]star=([^&]+)/i',          // star=SHINE
        '/[?&]heart=([^&]+)/i',         // heart=LOVE
        '/[?&]smile=([^&]+)/i',         // smile=HAPPY
        '/[?&]frown=([^&]+)/i',         // frown=SAD
        '/[?&]wink=([^&]+)/i',          // wink=PLAYFUL
        '/[?&]laugh=([^&]+)/i',         // laugh=FUNNY
        '/[?&]cry=([^&]+)/i',           // cry=TEARS
        '/[?&]angry=([^&]+)/i',         // angry=MAD
        '/[?&]surprised=([^&]+)/i',     // surprised=WOW
        '/[?&]confused=([^&]+)/i',      // confused=HUH
        '/[?&]excited=([^&]+)/i',       // excited=YAY
        '/[?&]bored=([^&]+)/i',         // bored=MEH
        '/[?&]tired=([^&]+)/i',         // tired=SLEEPY
        '/[?&]sleepy=([^&]+)/i',        // sleepy=DROWSY
        '/[?&]awake=([^&]+)/i',         // awake=ALERT
        '/[?&]alert=([^&]+)/i',         // alert=WARNING
        '/[?&]warning=([^&]+)/i',       // warning=DANGER
        '/[?&]danger=([^&]+)/i',        // danger=HAZARD
        '/[?&]hazard=([^&]+)/i',        // hazard=RISK
        '/[?&]risk=([^&]+)/i',          // risk=CHANCE
        '/[?&]chance=([^&]+)/i',        // chance=OPPORTUNITY
        '/[?&]opportunity=([^&]+)/i',   // opportunity=CHANCE
        '/[?&]possibility=([^&]+)/i',   // possibility=MAYBE
        '/[?&]maybe=([^&]+)/i',         // maybe=PERHAPS
        '/[?&]perhaps=([^&]+)/i',       // perhaps=COULD
        '/[?&]could=([^&]+)/i',         // could=MIGHT
        '/[?&]might=([^&]+)/i',         // might=WOULD
        '/[?&]would=([^&]+)/i',         // would=SHOULD
        '/[?&]should=([^&]+)/i',        // should=MUST
        '/[?&]must=([^&]+)/i',          // must=HAVE
        '/[?&]have=([^&]+)/i',          // have=GOT
        '/[?&]got=([^&]+)/i',           // got=OBTAINED
        '/[?&]obtained=([^&]+)/i',      // obtained=ACQUIRED
        '/[?&]acquired=([^&]+)/i',      // acquired=GAINED
        '/[?&]gained=([^&]+)/i',        // gained=EARNED
        '/[?&]earned=([^&]+)/i',        // earned=WON
        '/[?&]won=([^&]+)/i',           // won=ACHIEVED
        '/[?&]achieved=([^&]+)/i',      // achieved=ACCOMPLISHED
        '/[?&]accomplished=([^&]+)/i',  // accomplished=COMPLETED
        '/[?&]completed=([^&]+)/i',     // completed=FINISHED
        '/[?&]finished=([^&]+)/i',      // finished=DONE
        '/[?&]done=([^&]+)/i',          // done=READY
        '/[?&]ready=([^&]+)/i',         // ready=PREPARED
        '/[?&]prepared=([^&]+)/i',      // prepared=SET
        '/[?&]set=([^&]+)/i',           // set=CONFIGURED
        '/[?&]configured=([^&]+)/i',    // configured=ADJUSTED
        '/[?&]adjusted=([^&]+)/i',      // adjusted=MODIFIED
        '/[?&]modified=([^&]+)/i',      // modified=CHANGED
        '/[?&]changed=([^&]+)/i',       // changed=ALTERED
        '/[?&]altered=([^&]+)/i',       // altered=UPDATED
        '/[?&]updated=([^&]+)/i',       // updated=REFRESHED
        '/[?&]refreshed=([^&]+)/i',     // refreshed=RENEWED
        '/[?&]renewed=([^&]+)/i',       // renewed=RESTORED
        '/[?&]restored=([^&]+)/i',      // restored=REPAIRED
        '/[?&]repaired=([^&]+)/i',      // repaired=FIXED
        '/[?&]fixed=([^&]+)/i',         // fixed=CORRECTED
        '/[?&]corrected=([^&]+)/i',     // corrected=IMPROVED
        '/[?&]improved=([^&]+)/i',      // improved=ENHANCED
        '/[?&]enhanced=([^&]+)/i',      // enhanced=UPGRADED
        '/[?&]upgraded=([^&]+)/i',      // upgraded=BOOSTED
        '/[?&]boosted=([^&]+)/i',       // boosted=INCREASED
        '/[?&]increased=([^&]+)/i',     // increased=RAISED
        '/[?&]raised=([^&]+)/i',        // raised=LIFTED
        '/[?&]lifted=([^&]+)/i',        // lifted=ELEVATED
        '/[?&]elevated=([^&]+)/i',      // elevated=PROMOTED
        '/[?&]promoted=([^&]+)/i',      // promoted=ADVANCED
        '/[?&]advanced=([^&]+)/i',      // advanced=PROGRESSED
        '/[?&]progressed=([^&]+)/i',    // progressed=DEVELOPED
        '/[?&]developed=([^&]+)/i',     // developed=CREATED
        '/[?&]created=([^&]+)/i',       // created=MADE
        '/[?&]made=([^&]+)/i',          // made=BUILT
        '/[?&]built=([^&]+)/i',         // built=CONSTRUCTED
        '/[?&]constructed=([^&]+)/i',   // constructed=ASSEMBLED
        '/[?&]assembled=([^&]+)/i',     // assembled=PUT
        '/[?&]put=([^&]+)/i',           // put=PLACED
        '/[?&]placed=([^&]+)/i',        // placed=POSITIONED
        '/[?&]positioned=([^&]+)/i',    // positioned=LOCATED
        '/[?&]located=([^&]+)/i',       // located=FOUND
        '/[?&]found=([^&]+)/i',         // found=DISCOVERED
        '/[?&]discovered=([^&]+)/i',    // discovered=UNCOVERED
        '/[?&]uncovered=([^&]+)/i',     // uncovered=REVEALED
        '/[?&]revealed=([^&]+)/i',      // revealed=EXPOSED
        '/[?&]exposed=([^&]+)/i',       // exposed=SHOWN
        '/[?&]shown=([^&]+)/i',         // shown=DISPLAYED
        '/[?&]displayed=([^&]+)/i',     // displayed=PRESENTED
        '/[?&]presented=([^&]+)/i',     // presented=OFFERED
        '/[?&]offered=([^&]+)/i',       // offered=PROVIDED
        '/[?&]provided=([^&]+)/i',      // provided=GIVEN
        '/[?&]given=([^&]+)/i',         // given=DELIVERED
        '/[?&]delivered=([^&]+)/i',     // delivered=BROUGHT
        '/[?&]brought=([^&]+)/i',       // brought=CARRIED
        '/[?&]carried=([^&]+)/i',       // carried=TRANSPORTED
        '/[?&]transported=([^&]+)/i',   // transported=MOVED
        '/[?&]moved=([^&]+)/i',         // moved=SHIFTED
        '/[?&]shifted=([^&]+)/i',       // shifted=CHANGED
        '/[?&]changed=([^&]+)/i',       // changed=ALTERED
        '/[?&]altered=([^&]+)/i',       // altered=MODIFIED
        '/[?&]modified=([^&]+)/i',      // modified=ADJUSTED
        '/[?&]adjusted=([^&]+)/i',      // adjusted=CONFIGURED
        '/[?&]configured=([^&]+)/i',    // configured=SET
        '/[?&]set=([^&]+)/i',           // set=PREPARED
        '/[?&]prepared=([^&]+)/i',      // prepared=READY
        '/[?&]ready=([^&]+)/i',         // ready=DONE
        '/[?&]done=([^&]+)/i',          // done=FINISHED
        '/[?&]finished=([^&]+)/i',      // finished=COMPLETED
        '/[?&]completed=([^&]+)/i',     // completed=ACCOMPLISHED
        '/[?&]accomplished=([^&]+)/i',  // accomplished=ACHIEVED
        '/[?&]achieved=([^&]+)/i',      // achieved=WON
        '/[?&]won=([^&]+)/i',           // won=EARNED
        '/[?&]earned=([^&]+)/i',        // earned=GAINED
        '/[?&]gained=([^&]+)/i',        // gained=ACQUIRED
        '/[?&]acquired=([^&]+)/i',      // acquired=OBTAINED
        '/[?&]obtained=([^&]+)/i',      // obtained=GOT
        '/[?&]got=([^&]+)/i',           // got=HAVE
        '/[?&]have=([^&]+)/i',          // have=MUST
        '/[?&]must=([^&]+)/i',          // must=SHOULD
        '/[?&]should=([^&]+)/i',        // should=WOULD
        '/[?&]would=([^&]+)/i',         // would=MIGHT
        '/[?&]might=([^&]+)/i',         // might=COULD
        '/[?&]could=([^&]+)/i',         // could=PERHAPS
        '/[?&]perhaps=([^&]+)/i',       // perhaps=MAYBE
        '/[?&]maybe=([^&]+)/i',         // maybe=POSSIBILITY
        '/[?&]possibility=([^&]+)/i',   // possibility=OPPORTUNITY
        '/[?&]opportunity=([^&]+)/i',   // opportunity=CHANCE
        '/[?&]chance=([^&]+)/i',        // chance=RISK
        '/[?&]risk=([^&]+)/i',          // risk=HAZARD
        '/[?&]hazard=([^&]+)/i',        // hazard=DANGER
        '/[?&]danger=([^&]+)/i',        // danger=WARNING
        '/[?&]warning=([^&]+)/i',       // warning=ALERT
        '/[?&]alert=([^&]+)/i',         // alert=AWAKE
        '/[?&]awake=([^&]+)/i',         // awake=SLEEPY
        '/[?&]sleepy=([^&]+)/i',        // sleepy=TIRED
        '/[?&]tired=([^&]+)/i',         // tired=BORED
        '/[?&]bored=([^&]+)/i',         // bored=EXCITED
        '/[?&]excited=([^&]+)/i',       // excited=CONFUSED
        '/[?&]confused=([^&]+)/i',      // confused=SURPRISED
        '/[?&]surprised=([^&]+)/i',     // surprised=ANGRY
        '/[?&]angry=([^&]+)/i',         // angry=CRY
        '/[?&]cry=([^&]+)/i',           // cry=LAUGH
        '/[?&]laugh=([^&]+)/i',         // laugh=WINK
        '/[?&]wink=([^&]+)/i',          // wink=FROWN
        '/[?&]frown=([^&]+)/i',         // frown=SMILE
        '/[?&]smile=([^&]+)/i',         // smile=HEART
        '/[?&]heart=([^&]+)/i',         // heart=STAR
        '/[?&]star=([^&]+)/i',          // star=DIAMOND
        '/[?&]diamond=([^&]+)/i',       // diamond=OVAL
        '/[?&]oval=([^&]+)/i',          // oval=RECTANGLE
        '/[?&]rectangle=([^&]+)/i',     // rectangle=TRIANGLE
        '/[?&]triangle=([^&]+)/i',      // triangle=SQUARE
        '/[?&]square=([^&]+)/i',        // square=CIRCLE
        '/[?&]circle=([^&]+)/i',        // circle=PLACE
        '/[?&]place=([^&]+)/i',         // place=COURT
        '/[?&]court=([^&]+)/i',         // court=DRIVE
        '/[?&]drive=([^&]+)/i',         // drive=LANE
        '/[?&]lane=([^&]+)/i',          // lane=WAY
        '/[?&]way=([^&]+)/i',           // way=ROAD
        '/[?&]road=([^&]+)/i',          // road=BOULEVARD
        '/[?&]boulevard=([^&]+)/i',     // boulevard=AVENUE
        '/[?&]avenue=([^&]+)/i',        // avenue=STREET
        '/[?&]street=([^&]+)/i',        // street=NEIGHBORHOOD
        '/[?&]neighborhood=([^&]+)/i',  // neighborhood=DISTRICT
        '/[?&]district=([^&]+)/i',      // district=PROVINCE
        '/[?&]province=([^&]+)/i',      // province=STATE
        '/[?&]state=([^&]+)/i',         // state=CITY
        '/[?&]city=([^&]+)/i',          // city=COUNTRY
        '/[?&]country=([^&]+)/i',       // country=REGION
        '/[?&]region=([^&]+)/i',        // region=ZONE
        '/[?&]zone=([^&]+)/i',          // zone=AREA
        '/[?&]area=([^&]+)/i',          // area=SPACE
        '/[?&]space=([^&]+)/i',         // space=ROOM
        '/[?&]room=([^&]+)/i',          // room=SEAT
        '/[?&]seat=([^&]+)/i',          // seat=SPOT
        '/[?&]spot=([^&]+)/i',          // spot=PLACE
        '/[?&]place=([^&]+)/i',         // place=POSITION
        '/[?&]position=([^&]+)/i',      // position=RANK
        '/[?&]rank=([^&]+)/i',          // rank=LEVEL
        '/[?&]level=([^&]+)/i',         // level=GRADE
        '/[?&]grade=([^&]+)/i',         // grade=QUALITY
        '/[?&]quality=([^&]+)/i',       // quality=STRENGTH
        '/[?&]strength=([^&]+)/i',      // strength=FORCE
        '/[?&]force=([^&]+)/i',         // force=ENERGY
        '/[?&]energy=([^&]+)/i',        // energy=POWER
        '/[?&]power=([^&]+)/i',         // power=SPEED
        '/[?&]speed=([^&]+)/i',         // speed=VOLUME
        '/[?&]volume=([^&]+)/i',        // volume=DEPTH
        '/[?&]depth=([^&]+)/i',         // depth=HEIGHT
        '/[?&]height=([^&]+)/i',        // height=WIDTH
        '/[?&]width=([^&]+)/i',         // width=LENGTH
        '/[?&]length=([^&]+)/i',        // length=WEIGHT
        '/[?&]weight=([^&]+)/i',        // weight=SIZE
        '/[?&]size=([^&]+)/i',          // size=COLOR
        '/[?&]color=([^&]+)/i',         // color=STYLE
        '/[?&]style=([^&]+)/i',         // style=THEME
        '/[?&]theme=([^&]+)/i',         // theme=SKIN
        '/[?&]skin=([^&]+)/i',          // skin=COVER
        '/[?&]cover=([^&]+)/i',         // cover=CASE
        '/[?&]case=([^&]+)/i',          // case=BOX
        '/[?&]box=([^&]+)/i',           // box=KIT
        '/[?&]kit=([^&]+)/i',           // kit=SET
        '/[?&]set=([^&]+)/i',           // set=COMBO
        '/[?&]combo=([^&]+)/i',         // combo=BUNDLE
        '/[?&]bundle=([^&]+)/i',        // bundle=PACKAGE
        '/[?&]package=([^&]+)/i',       // package=PLAN
        '/[?&]plan=([^&]+)/i',          // plan=SUBSCRIPTION
        '/[?&]subscription=([^&]+)/i',  // subscription=MEMBERSHIP
        '/[?&]membership=([^&]+)/i',    // membership=ACCESS
        '/[?&]access=([^&]+)/i',        // access=PREVIEW
        '/[?&]preview=([^&]+)/i',       // preview=TEST
        '/[?&]test=([^&]+)/i',          // test=SAMPLE
        '/[?&]sample=([^&]+)/i',        // sample=DEMO
        '/[?&]demo=([^&]+)/i',          // demo=TRIAL
        '/[?&]trial=([^&]+)/i',         // trial=DOWNGRADE
        '/[?&]downgrade=([^&]+)/i',     // downgrade=UPGRADE
        '/[?&]upgrade=([^&]+)/i',       // upgrade=EXCHANGE
        '/[?&]exchange=([^&]+)/i',      // exchange=RETURN
        '/[?&]return=([^&]+)/i',        // return=REFUND
        '/[?&]refund=([^&]+)/i',        // refund=REBATE
        '/[?&]rebate=([^&]+)/i',        // rebate=CASHBACK
        '/[?&]cashback=([^&]+)/i',      // cashback=CREDIT
        '/[?&]credit=([^&]+)/i',        // credit=REWARD
        '/[?&]reward=([^&]+)/i',        // reward=BONUS
        '/[?&]bonus=([^&]+)/i',         // bonus=SPECIAL
        '/[?&]special=([^&]+)/i',       // special=DEAL
        '/[?&]deal=([^&]+)/i',          // deal=OFFER
        '/[?&]offer=([^&]+)/i',         // offer=COUPON
        '/[?&]coupon=([^&]+)/i',        // coupon=PROMO
        '/[?&]promo=([^&]+)/i',         // promo=CODE
        '/[?&]code=([^&]+)/i',          // code=VIA
        '/[?&]via=([^&]+)/i'            // via=9ab7ca
    ];
    
    $result = [
        'is_url' => false,
        'url' => '',
        'extracted_code' => '',
        'display_text' => $codigo_text
    ];
    
    // Verificar si es una URL
    if (preg_match($url_pattern, $codigo_text, $url_matches)) {
        $result['is_url'] = true;
        $result['url'] = $url_matches[0];
        
        // Intentar extraer código de la URL
        foreach ($code_patterns as $pattern) {
            if (preg_match($pattern, $codigo_text, $code_matches)) {
                $result['extracted_code'] = $code_matches[1];
                $result['display_text'] = $code_matches[1];
                break;
            }
        }
        
        // Si no se encontró código específico, usar la URL completa
        if (empty($result['extracted_code'])) {
            $result['display_text'] = $codigo_text;
        }
    }
    
    return $result;
}

function generate_code_detail_page($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $date = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    $destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
    
    // Obtener el código real del campo 'codigo'
    $codigo_real = isset($codigo['codigo']) ? $codigo['codigo'] : '';
    
    // Detectar si es URL y extraer código
    $code_info = detect_url_and_extract_code($codigo_real);
    
    // Obtener información completa del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca
    $marca_info = get_brand_info($brand);
    $marca_nombre = $marca_info['nombre'] ?? ucfirst($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    $html = '<div class="container-fluid main_entremedio">';
    
    // Hero Section
    $html .= '<div class="code-detail-hero">';
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-12">';
    
    // Breadcrumb
    $html .= '<nav class="breadcrumb-nav">';
    $html .= '<a href="/" class="breadcrumb-link">Inicio</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<a href="/de-' . $brand . '" class="breadcrumb-link">' . $marca_nombre . '</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<span class="breadcrumb-current">Código de descuento</span>';
    $html .= '</nav>';
    
    // Header principal
    $html .= '<div class="code-detail-header">';
    $html .= '<div class="header-left">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-logo">';
    }
    $html .= '<div class="header-text">';
    $html .= '<h1>Código de Descuento ' . $marca_nombre . '</h1>';
    $html .= '<p class="header-subtitle">Código verificado y actualizado</p>';
    if($destacado) {
        $html .= '<span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="header-actions">';
    $html .= '<button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Volver</button>';
    $html .= '<button class="btn-share" onclick="shareCode()"><i class="fas fa-share"></i> Compartir</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Contenido principal
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-8">';
    
    // Código principal
    $html .= '<div class="code-main-card">';
    $html .= '<div class="code-benefit-display">';
    $html .= '<div class="benefit-amount">' . $benefit . '€</div>';
    $html .= '<div class="benefit-label">de beneficio</div>';
    $html .= '</div>';
    
    $html .= '<div class="code-display-container">';
    $html .= '<h3><i class="fas fa-tag"></i> Tu código de descuento</h3>';
    $html .= '<div class="code-text" id="codeText">' . strtoupper($code_info['display_text']) . '</div>';
    
    if ($code_info['is_url']) {
        $html .= '<div class="code-url-section">';
        $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
        $html .= '<a href="' . htmlspecialchars($code_info['url']) . '" target="_blank" class="code-url-link">';
        $html .= '<i class="fas fa-external-link-alt"></i>';
        $html .= '<span>' . htmlspecialchars($code_info['url']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '<button class="btn-copy-code" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="code-description-card">';
    $html .= '<h3><i class="fas fa-info-circle"></i> Descripción de la oferta</h3>';
    $html .= '<p>' . htmlspecialchars($description) . '</p>';
    $html .= '</div>';
    
    // Cómo usar
    $html .= '<div class="how-to-use-card">';
    $html .= '<h3><i class="fas fa-question-circle"></i> ¿Cómo usar este código?</h3>';
    $html .= '<div class="steps-container">';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">1</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Copia el código</h4>';
    $html .= '<p>Haz clic en "Copiar código" para copiarlo al portapapeles</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">2</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Ve a ' . $marca_nombre . '</h4>';
    $html .= '<p>Accede a la web oficial de ' . $marca_nombre . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">3</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Aplica el código</h4>';
    $html .= '<p>Pega el código en el campo correspondiente durante el checkout</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-8
    
    // Sidebar
    $html .= '<div class="col-md-4">';
    
    // Información del usuario
    $html .= '<div class="user-info-card">';
    $html .= '<h4><i class="fas fa-user"></i> Publicado por</h4>';
    $html .= '<div class="user-profile">';
    $html .= '<div class="user-avatar">';
    if($user_img && !empty($user_img)) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span>' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details">';
    $html .= '<div class="user-name">' . htmlspecialchars($username) . '</div>';
    $html .= '<div class="user-date">Publicado el ' . date('d/m/Y', $date instanceof DateTime ? $date->getTimestamp() : time()) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Información adicional
    $html .= '<div class="code-info-card">';
    $html .= '<h4><i class="fas fa-shield-alt"></i> Información del código</h4>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Estado:</span>';
    $html .= '<span class="info-value verified"><i class="fas fa-check-circle"></i> Verificado</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Beneficio:</span>';
    $html .= '<span class="info-value">' . $benefit . '€</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Marca:</span>';
    $html .= '<span class="info-value">' . $marca_nombre . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botones de acción
    $html .= '<div class="action-buttons-card">';
    $html .= '<button class="btn-primary" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '<button class="btn-secondary" onclick="shareCode()">';
    $html .= '<i class="fas fa-share"></i> Compartir';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-4
    $html .= '</div>'; // row
    $html .= '</div>'; // container
    $html .= '</div>'; // container-fluid
    
    // Agregar estilos CSS increíbles para el nuevo diseño
    $html .= '<style>
    /* Hero Section */
    .code-detail-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .breadcrumb-nav {
        margin-bottom: 30px;
    }
    
    .breadcrumb-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: white;
        text-decoration: none;
    }
    
    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.6);
        margin: 0 10px;
    }
    
    .breadcrumb-current {
        color: white;
        font-weight: 600;
    }
    
    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        background: white;
        border-radius: 15px;
        padding: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .header-text h1 {
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        margin: 0 0 15px 0;
    }
    
    .featured-badge {
        background: #ff6b35;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-back, .btn-share {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }
    
    .btn-back:hover, .btn-share:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Main Content */
    .code-main-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: 1px solid #f0f0f0;
    }
    
    .code-benefit-display {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        border-radius: 20px;
        color: white;
    }
    
    .benefit-amount {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .benefit-label {
        font-size: 1.2rem;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .code-display-container h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-text {
        font-family: "Courier New", monospace;
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: 4px;
        text-align: center;
        padding: 40px;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(44, 62, 80, 0.3);
        border: 3px solid #ff6b35;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .code-text::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .code-url-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 2px solid #e9ecef;
    }
    
    .url-label {
        color: #666;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .code-url-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ff6b35;
        text-decoration: none;
        padding: 15px 20px;
        background: white;
        border: 2px solid #ff6b35;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        word-break: break-all;
    }
    
    .code-url-link:hover {
        background: #ff6b35;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-copy-code {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 20px 40px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    .btn-copy-code:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
    }
    
    /* Cards */
    .code-description-card, .how-to-use-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .code-description-card h3, .how-to-use-card h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-description-card p {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.8;
        margin: 0;
    }
    
    .steps-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    
    .step-number {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
    }
    
    .step-content h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .step-content p {
        color: #666;
        margin: 0;
        line-height: 1.6;
    }
    
    /* Sidebar */
    .user-info-card, .code-info-card, .action-buttons-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .user-info-card h4, .code-info-card h4 {
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .user-name {
        color: #333;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .user-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
        font-weight: 700;
    }
    
    .info-value.verified {
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 15px;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e9ecef;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .code-detail-header {
            flex-direction: column;
            text-align: center;
        }
        
        .header-left {
            flex-direction: column;
            text-align: center;
        }
        
        .header-text h1 {
            font-size: 2rem;
        }
        
        .benefit-amount {
            font-size: 3rem;
        }
        
        .code-text {
            font-size: 1.8rem;
            padding: 30px 20px;
            letter-spacing: 2px;
        }
        
        .code-main-card, .code-description-card, .how-to-use-card {
            padding: 25px;
        }
        
        .user-info-card, .code-info-card, .action-buttons-card {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .code-detail-hero {
            padding: 20px 0;
        }
        
        .header-text h1 {
            font-size: 1.5rem;
        }
        
        .benefit-amount {
            font-size: 2.5rem;
        }
        
        .code-text {
            font-size: 1.5rem;
            padding: 25px 15px;
            letter-spacing: 1px;
        }
        
        .step-item {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>';
    
    // Agregar JavaScript para funcionalidades
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        const text = codeText.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            // Cambiar el texto del botón temporalmente
            const btn = document.querySelector(".btn-copy-code");
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = "<i class=\\"fas fa-check\\"></i> ¡Copiado!";
                btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                }, 2000);
            }
        }).catch(function(err) {
            console.error("Error al copiar: ", err);
            alert("Error al copiar el código");
        });
    }
    
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/";
        }
    }
    
    function shareCode() {
        const url = window.location.href;
        const title = document.title;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            navigator.clipboard.writeText(url).then(function() {
                alert("Enlace copiado al portapapeles");
            });
        }
    }
    
    function toggleFavorite() {
        // Implementar funcionalidad de favoritos
        alert("Funcionalidad de favoritos próximamente");
    }
    </script>';
    
    return $html;
}

// Función para obtener marcas relacionadas de la misma categoría
function get_related_brands($marca_actual) {
    // Obtener información de la marca actual de forma segura
    try {
        $marca_info = get_brand_info($marca_actual);
        $categoria_actual = $marca_info['categoria'] ?? '';
    } catch (Exception $e) {
        $categoria_actual = '';
    }
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas de la misma categoría
    $marcas_relacionadas = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_relacionadas[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas relacionadas
    $marcas_relacionadas = array_slice($marcas_relacionadas, 0, 6);
    
    return $marcas_relacionadas;
}

// Función para obtener marcas populares de la misma categoría
function get_popular_brands_in_category($marca_actual) {
    // Obtener información de la marca actual
    $marca_info = get_brand_info($marca_actual);
    $categoria_actual = $marca_info['categoria'] ?? '';
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas populares de la misma categoría
    $marcas_populares = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20, 'sort' => ['total_codigos' => -1]]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_populares[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas populares
    $marcas_populares = array_slice($marcas_populares, 0, 6);
    
    return $marcas_populares;
}

// Función para detectar URLs y extraer códigos de descuento
function detect_url_and_extract_code($codigo_text) {
    // Patrones para detectar URLs
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    $code_patterns = [
        '/[?&]via=([^&]+)/i',           // via=9ab7ca
        '/[?&]code=([^&]+)/i',          // code=ABC123
        '/[?&]coupon=([^&]+)/i',        // coupon=SAVE20
        '/[?&]promo=([^&]+)/i',         // promo=WELCOME
        '/[?&]ref=([^&]+)/i',           // ref=USER123
        '/[?&]discount=([^&]+)/i',      // discount=50OFF
        '/[?&]offer=([^&]+)/i',         // offer=SUMMER
        '/[?&]deal=([^&]+)/i',          // deal=BLACKFRIDAY
        '/[?&]voucher=([^&]+)/i',       // voucher=GIFT50
        '/[?&]token=([^&]+)/i',         // token=ABC123
        '/[?&]key=([^&]+)/i',           // key=SECRET123
        '/[?&]id=([^&]+)/i',            // id=USER456
        '/[?&]affiliate=([^&]+)/i',     // affiliate=PARTNER
        '/[?&]partner=([^&]+)/i',       // partner=REF123
        '/[?&]source=([^&]+)/i',        // source=FRIEND
    ];

    $result = [
        'is_url' => false,
        'url' => '',
        'extracted_code' => '',
        'display_text' => $codigo_text
    ];

    // Verificar si es una URL
    if (preg_match($url_pattern, $codigo_text, $url_matches)) {
        $result['is_url'] = true;
        $result['url'] = $url_matches[0];
        
        // Intentar extraer código de la URL
        foreach ($code_patterns as $pattern) {
            if (preg_match($pattern, $codigo_text, $code_matches)) {
                $result['extracted_code'] = $code_matches[1];
                $result['display_text'] = $code_matches[1];
                break;
            }
        }
        
        // Si no se encontró código específico, usar la URL completa
        if (empty($result['extracted_code'])) {
            $result['display_text'] = $codigo_text;
        }
    }
    
    return $result;
}

function generate_code_detail_page($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    $date = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : new DateTime();
    $destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
    
    // Obtener el código real del campo 'codigo'
    $codigo_real = isset($codigo['codigo']) ? $codigo['codigo'] : '';
    
    // Detectar si es URL y extraer código
    $code_info = detect_url_and_extract_code($codigo_real);
    
    // Obtener información completa del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca
    $marca_info = get_brand_info($brand);
    $marca_nombre = $marca_info['nombre'] ?? ucfirst($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    $html = '<div class="container-fluid main_entremedio">';
    
    // Hero Section
    $html .= '<div class="code-detail-hero">';
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-12">';
    
    // Breadcrumb
    $html .= '<nav class="breadcrumb-nav">';
    $html .= '<a href="/" class="breadcrumb-link">Inicio</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<a href="/de-' . $brand . '" class="breadcrumb-link">' . $marca_nombre . '</a>';
    $html .= '<span class="breadcrumb-separator">›</span>';
    $html .= '<span class="breadcrumb-current">Código de descuento</span>';
    $html .= '</nav>';
    
    // Header principal
    $html .= '<div class="code-detail-header">';
    $html .= '<div class="header-left">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-logo">';
    }
    $html .= '<div class="header-text">';
    $html .= '<h1>Código de Descuento ' . $marca_nombre . '</h1>';
    $html .= '<p class="header-subtitle">Código verificado y actualizado</p>';
    if($destacado) {
        $html .= '<span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="header-actions">';
    $html .= '<button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Volver</button>';
    $html .= '<button class="btn-share" onclick="shareCode()"><i class="fas fa-share"></i> Compartir</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Contenido principal
    $html .= '<div class="container">';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-8">';
    
    // Código principal
    $html .= '<div class="code-main-card">';
    $html .= '<div class="code-benefit-display">';
    $html .= '<div class="benefit-amount">' . $benefit . '€</div>';
    $html .= '<div class="benefit-label">de beneficio</div>';
    $html .= '</div>';
    
    $html .= '<div class="code-display-container">';
    $html .= '<h3><i class="fas fa-tag"></i> Tu código de descuento</h3>';
    $html .= '<div class="code-text" id="codeText">' . strtoupper($code_info['display_text']) . '</div>';
    
    if ($code_info['is_url']) {
        $html .= '<div class="code-url-section">';
        $html .= '<div class="url-label"><i class="fas fa-link"></i> Enlace directo:</div>';
        $html .= '<a href="' . htmlspecialchars($code_info['url']) . '" target="_blank" class="code-url-link">';
        $html .= '<i class="fas fa-external-link-alt"></i>';
        $html .= '<span>' . htmlspecialchars($code_info['url']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '<button class="btn-copy-code" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="code-description-card">';
    $html .= '<h3><i class="fas fa-info-circle"></i> Descripción de la oferta</h3>';
    $html .= '<p>' . htmlspecialchars($description) . '</p>';
    $html .= '</div>';
    
    // Cómo usar
    $html .= '<div class="how-to-use-card">';
    $html .= '<h3><i class="fas fa-question-circle"></i> ¿Cómo usar este código?</h3>';
    $html .= '<div class="steps-container">';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">1</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Copia el código</h4>';
    $html .= '<p>Haz clic en "Copiar código" para copiarlo al portapapeles</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">2</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Ve a ' . $marca_nombre . '</h4>';
    $html .= '<p>Accede a la web oficial de ' . $marca_nombre . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="step-item">';
    $html .= '<div class="step-number">3</div>';
    $html .= '<div class="step-content">';
    $html .= '<h4>Aplica el código</h4>';
    $html .= '<p>Pega el código en el campo correspondiente durante el checkout</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-8
    
    // Sidebar
    $html .= '<div class="col-md-4">';
    
    // Información del usuario
    $html .= '<div class="user-info-card">';
    $html .= '<h4><i class="fas fa-user"></i> Publicado por</h4>';
    $html .= '<div class="user-profile">';
    $html .= '<div class="user-avatar">';
    if($user_img && !empty($user_img)) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '">';
    } else {
        $html .= '<div class="user-avatar-placeholder">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span>' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details">';
    $html .= '<div class="user-name">' . htmlspecialchars($username) . '</div>';
    $html .= '<div class="user-date">Publicado el ' . date('d/m/Y', $date instanceof DateTime ? $date->getTimestamp() : time()) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Información adicional
    $html .= '<div class="code-info-card">';
    $html .= '<h4><i class="fas fa-shield-alt"></i> Información del código</h4>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Estado:</span>';
    $html .= '<span class="info-value verified"><i class="fas fa-check-circle"></i> Verificado</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Beneficio:</span>';
    $html .= '<span class="info-value">' . $benefit . '€</span>';
    $html .= '</div>';
    $html .= '<div class="info-item">';
    $html .= '<span class="info-label">Marca:</span>';
    $html .= '<span class="info-value">' . $marca_nombre . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Botones de acción
    $html .= '<div class="action-buttons-card">';
    $html .= '<button class="btn-primary" onclick="copyCode()">';
    $html .= '<i class="fas fa-copy"></i> Copiar código';
    $html .= '</button>';
    $html .= '<button class="btn-secondary" onclick="shareCode()">';
    $html .= '<i class="fas fa-share"></i> Compartir';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // col-md-4
    $html .= '</div>'; // row
    $html .= '</div>'; // container
    $html .= '</div>'; // container-fluid
    
    // Agregar estilos CSS increíbles para el nuevo diseño
    $html .= '<style>
    /* Hero Section */
    .code-detail-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .breadcrumb-nav {
        margin-bottom: 30px;
    }
    
    .breadcrumb-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: white;
        text-decoration: none;
    }
    
    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.6);
        margin: 0 10px;
    }
    
    .breadcrumb-current {
        color: white;
        font-weight: 600;
    }
    
    .code-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        background: white;
        border-radius: 15px;
        padding: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .header-text h1 {
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .header-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        margin: 0 0 15px 0;
    }
    
    .featured-badge {
        background: #ff6b35;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-back, .btn-share {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }
    
    .btn-back:hover, .btn-share:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Main Content */
    .code-main-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: 1px solid #f0f0f0;
    }
    
    .code-benefit-display {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        border-radius: 20px;
        color: white;
    }
    
    .benefit-amount {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .benefit-label {
        font-size: 1.2rem;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .code-display-container h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-text {
        font-family: "Courier New", monospace;
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: 4px;
        text-align: center;
        padding: 40px;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(44, 62, 80, 0.3);
        border: 3px solid #ff6b35;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .code-text::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .code-url-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 2px solid #e9ecef;
    }
    
    .url-label {
        color: #666;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .code-url-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ff6b35;
        text-decoration: none;
        padding: 15px 20px;
        background: white;
        border: 2px solid #ff6b35;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        word-break: break-all;
    }
    
    .code-url-link:hover {
        background: #ff6b35;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-copy-code {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 20px 40px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    .btn-copy-code:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
    }
    
    /* Cards */
    .code-description-card, .how-to-use-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .code-description-card h3, .how-to-use-card h3 {
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .code-description-card p {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.8;
        margin: 0;
    }
    
    .steps-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    
    .step-number {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
    }
    
    .step-content h4 {
        color: #333;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .step-content p {
        color: #666;
        margin: 0;
        line-height: 1.6;
    }
    
    /* Sidebar */
    .user-info-card, .code-info-card, .action-buttons-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }
    
    .user-info-card h4, .code-info-card h4 {
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .user-name {
        color: #333;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .user-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
        font-weight: 700;
    }
    
    .info-value.verified {
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 15px;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e9ecef;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .code-detail-header {
            flex-direction: column;
            text-align: center;
        }
        
        .header-left {
            flex-direction: column;
            text-align: center;
        }
        
        .header-text h1 {
            font-size: 2rem;
        }
        
        .benefit-amount {
            font-size: 3rem;
        }
        
        .code-text {
            font-size: 1.8rem;
            padding: 30px 20px;
            letter-spacing: 2px;
        }
        
        .code-main-card, .code-description-card, .how-to-use-card {
            padding: 25px;
        }
        
        .user-info-card, .code-info-card, .action-buttons-card {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .code-detail-hero {
            padding: 20px 0;
        }
        
        .header-text h1 {
            font-size: 1.5rem;
        }
        
        .benefit-amount {
            font-size: 2.5rem;
        }
        
        .code-text {
            font-size: 1.5rem;
            padding: 25px 15px;
            letter-spacing: 1px;
        }
        
        .step-item {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>';
    
    // Agregar JavaScript para funcionalidades
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        const text = codeText.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            // Cambiar el texto del botón temporalmente
            const btn = document.querySelector(".btn-copy-code");
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = "<i class=\\"fas fa-check\\"></i> ¡Copiado!";
                btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                }, 2000);
            }
        }).catch(function(err) {
            console.error("Error al copiar: ", err);
            alert("Error al copiar el código");
        });
    }
    
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/";
        }
    }
    
    function shareCode() {
        const url = window.location.href;
        const title = document.title;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            navigator.clipboard.writeText(url).then(function() {
                alert("Enlace copiado al portapapeles");
            });
        }
    }
    
    function toggleFavorite() {
        // Implementar funcionalidad de favoritos
        alert("Funcionalidad de favoritos próximamente");
    }
    </script>';
    
    return $html;
}

// Función para obtener marcas relacionadas de la misma categoría
function get_related_brands($marca_actual) {
    // Obtener información de la marca actual de forma segura
    try {
        $marca_info = get_brand_info($marca_actual);
        $categoria_actual = $marca_info['categoria'] ?? '';
    } catch (Exception $e) {
        $categoria_actual = '';
    }
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas de la misma categoría
    $marcas_relacionadas = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_relacionadas[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas relacionadas
    $marcas_relacionadas = array_slice($marcas_relacionadas, 0, 6);
    
    return $marcas_relacionadas;
}

// Función para obtener marcas populares de la misma categoría
function get_popular_brands_in_category($marca_actual) {
    // Obtener información de la marca actual
    $marca_info = get_brand_info($marca_actual);
    $categoria_actual = $marca_info['categoria'] ?? '';
    
    // Si no hay categoría, devolver array vacío
    if (empty($categoria_actual)) {
        return [];
    }
    
    // Buscar marcas populares de la misma categoría
    $marcas_populares = [];
    $marcas_categoria = get_all_marcas_array(['categoria' => $categoria_actual], ['limit' => 20, 'sort' => ['total_codigos' => -1]]);
    
    if (isset($marcas_categoria['results'])) {
        foreach ($marcas_categoria['results'] as $marca) {
            // Excluir la marca actual
            if ($marca['nombre_clave'] !== $marca_actual) {
                $marcas_populares[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas populares
    $marcas_populares = array_slice($marcas_populares, 0, 6);
    
    return $marcas_populares;
}

// Función para detectar URLs y extraer códigos de descuento
function detect_url_and_extract_code($codigo_text) {
    // Patrones para detectar URLs
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    $code_patterns = [
        '/[?&]via=([^&]+)/i',           // via=9ab7ca
        '/[?&]code=([^&]+)/i',          // code=ABC123
        '/[?&]coupon=([^&]+)/i',        // coupon=SAVE20
        '/[?&]promo=([^&]+)/i',         // promo=WELCOME
        '/[?&]ref=([^&]+)/i',           // ref=USER123
        '/[?&]discount=([^&]+)/i',      // discount=50OFF
        '/[?&]offer=([^&]+)/i',         // offer=SUMMER
        '/[?&]deal=([^&]+)/i',          // deal=BLACKFRIDAY
        '/[?&]voucher=([^&]+)/i',       // voucher=GIFT50
        '/[?&]token=([^&]+)/i',         // token=ABC123
        '/[?&]key=([^&]+)/i',           // key=SECRET123
        '/[?&]id=([^&]+)/i',            // id=USER456
        '/[?&]affiliate=([^&]+)/i',     // affiliate=PARTNER
        '/[?&]partner=([^&]+)/i',       // partner=REF123
        '/[?&]source=([^&]+)/i',        // source=FRIEND
        '/[?&]utm_source=([^&]+)/i',    // utm_source=EMAIL
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        border-color: #ff8c42;
    }
    
    .code-url-link i {
        margin-right: 8px;
        flex-shrink: 0;
        font-size: 14px;
    }
    
    .url-text {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .url-label i {
        margin-right: 6px;
        color: #ff6b35;
    }
    
    .btn-copy {
        background: #28a745;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 10px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        width: 100%;
    }
    
    .btn-copy:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4);
    }
    
    .btn-copy:active {
        transform: translateY(0);
    }
    
    .btn-copy i {
        margin-right: 8px;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .code-text {
            font-size: 20px;
            padding: 20px;
            letter-spacing: 2px;
        }
        
        .code-url-link {
            font-size: 12px;
            padding: 10px 12px;
        }
        
        .btn-copy {
            padding: 12px 20px;
            font-size: 14px;
        }
    }
    
    /* Estilos específicos para la página de detalle del código */
    .user-info-section {
        background: #2c2c2c;
        border-radius: 12px;
        padding: 25px;
        border: 1px solid #404040;
        margin: 20px 0;
    }
    
    .user-info-section h3 {
        color: #fff;
        margin-bottom: 20px;
        font-size: 20px;
        font-weight: 600;
    }
    
    .user-card {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #404040;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }
    
    .user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: #ff6b35;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    
    .user-details {
        flex: 1;
    }
    
    .user-name {
        font-weight: 600;
        color: #fff;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .user-date {
        color: #ccc;
        font-size: 0.9rem;
    }
    </style>';
    
    // Agregar JavaScript para copiar código
    $html .= '<script>
    function copyCode() {
        const codeText = document.getElementById("codeText");
        const textToCopy = codeText.textContent;
        
        navigator.clipboard.writeText(textToCopy).then(function() {
            // Cambiar temporalmente el botón
            const btn = document.querySelector(".btn-copy");
            const originalText = btn.innerHTML;
            btn.innerHTML = "<i class=\"fas fa-check\"></i> ¡Copiado!";
            btn.style.background = "#28a745";
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.style.background = "#28a745";
            }, 2000);
        }).catch(function(err) {
            console.error("Error al copiar: ", err);
            alert("Error al copiar el código. Inténtalo de nuevo.");
        });
    }
    
    function goBack() {
        window.history.back();
    }
    
    function shareCode() {
        if (navigator.share) {
            navigator.share({
                title: "Código de descuento ' . ucfirst($brand) . '",
                text: "¡Mira este código de descuento!",
                url: window.location.href
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                alert("Enlace copiado al portapapeles");
            });
        }
    }
    
    function toggleFavorite() {
        // Implementar funcionalidad de favoritos
        alert("Funcionalidad de favoritos próximamente");
    }
    </script>';
    
    return $html;
}

// Función para obtener marcas relacionadas de la misma categoría
function get_related_brands($marca_actual) {
    // Obtener información de la marca actual de forma segura
    try {
        $marca_info = get_brand_info($marca_actual);
        $categoria_actual = $marca_info['categoria'] ?? '';
    } catch (Exception $e) {
        $categoria_actual = '';
    }
    
    // Cargar el archivo JSON de marcas
    $json_file = __DIR__ . '/../datos.json';
    if (!file_exists($json_file)) {
        return [];
    }
    
    $todas_marcas = json_decode(file_get_contents($json_file), true);
    if (!$todas_marcas) {
        return [];
    }
    
    $marcas_relacionadas = [];
    
    // Buscar marcas de la misma categoría
    foreach($todas_marcas as $marca) {
        $nombre_clave = $marca["nombre_clave"] ?? '';
        $categoria = $marca["categoria"] ?? '';
        $codes = $marca["codes"] ?? 0;
        
        // Filtrar marcas que:
        // 1. No sean la marca actual
        // 2. Tengan la misma categoría
        // 3. Tengan códigos disponibles
        if($nombre_clave !== $marca_actual && 
           $categoria === $categoria_actual && 
           $codes > 0) {
            $marcas_relacionadas[] = $marca;
        }
    }
    
    // Si no hay suficientes marcas de la misma categoría, agregar otras marcas populares
    if(count($marcas_relacionadas) < 6) {
        foreach($todas_marcas as $marca) {
            if(count($marcas_relacionadas) >= 6) break;
            
            $nombre_clave = $marca["nombre_clave"] ?? '';
            $codes = $marca["codes"] ?? 0;
            
            // Agregar marcas que no estén ya incluidas y tengan códigos
            if($nombre_clave !== $marca_actual && 
               $codes > 0 && 
               !in_array($marca, $marcas_relacionadas)) {
                $marcas_relacionadas[] = $marca;
            }
        }
    }
    
    // Limitar a 6 marcas relacionadas
    $marcas_relacionadas = array_slice($marcas_relacionadas, 0, 6);
    
    return $marcas_relacionadas;
}

// Función para obtener marcas populares de la misma categoría
function get_popular_brands_in_category($marca_actual) {
    // Obtener información de la marca actual
    $marca_info = get_brand_info($marca_actual);
    $categoria_actual = $marca_info['categoria'] ?? '';
    
    // Cargar el archivo JSON de marcas
    $json_file = __DIR__ . '/../datos.json';
    if (!file_exists($json_file)) {
        return [];
    }
    
    $todas_marcas = json_decode(file_get_contents($json_file), true);
    if (!$todas_marcas) {
        return [];
    }
    
    $marcas_populares = [];
    
    // Buscar marcas de la misma categoría
    foreach($todas_marcas as $marca) {
        $nombre_clave = $marca["nombre_clave"] ?? '';
        $categoria = $marca["categoria"] ?? '';
        $codes = $marca["codes"] ?? 0;
        
        // Filtrar marcas que:
        // 1. No sean la marca actual
        // 2. Tengan la misma categoría
        // 3. Tengan códigos disponibles
        if($nombre_clave !== $marca_actual && 
           $categoria === $categoria_actual && 
           $codes > 0) {
            $marcas_populares[] = $marca;
        }
    }
    
    // Si no hay suficientes marcas de la misma categoría, agregar otras marcas populares
    if(count($marcas_populares) < 8) {
        foreach($todas_marcas as $marca) {
            if(count($marcas_populares) >= 8) break;
            
            $nombre_clave = $marca["nombre_clave"] ?? '';
            $codes = $marca["codes"] ?? 0;
            
            // Agregar marcas que no estén ya incluidas y tengan códigos
            if($nombre_clave !== $marca_actual && 
               $codes > 0 && 
               !in_array($marca, $marcas_populares)) {
                $marcas_populares[] = $marca;
            }
        }
    }
    
    // Ordenar por número de códigos (más populares primero)
    usort($marcas_populares, function($a, $b) {
        $codes_a = $a["codes"] ?? 0;
        $codes_b = $b["codes"] ?? 0;
        return $codes_b - $codes_a;
    });
    
    // Limitar a 8 marcas populares
    $marcas_populares = array_slice($marcas_populares, 0, 8);
    
    return $marcas_populares;
}

// Cache para marcas para evitar consultas repetidas
$brand_info_cache = [];
$user_info_cache = [];

// Función para obtener información específica de una marca
function get_brand_info($marca_clave) {
    global $brand_info_cache;
    
    // Convertir a string si es un objeto BSONDocument
    if (is_object($marca_clave)) {
        if (method_exists($marca_clave, 'toArray')) {
            $marca_clave = $marca_clave->toArray();
        } elseif (method_exists($marca_clave, '__toString')) {
            $marca_clave = $marca_clave->__toString();
        } else {
            $marca_clave = json_encode($marca_clave);
        }
    }
    
    // Asegurar que sea string
    $marca_clave = (string) $marca_clave;
    
    // Inicializar caché si no existe
    if (!isset($brand_info_cache)) {
        $brand_info_cache = array();
    }
    
    // Verificar si ya tenemos la información en caché
    if (isset($brand_info_cache[$marca_clave])) {
        return $brand_info_cache[$marca_clave];
    }
    
    // Buscar específicamente en la base de datos usando la función existente
    try {
        $marca_especifica = getObjectMarca('nombre_clave', $marca_clave);
        if ($marca_especifica) {
            $resultado = [
                'nombre' => $marca_especifica['nombre'] ?? ucfirst($marca_clave),
                'descripcion' => $marca_especifica['descripcion'] ?? '',
                'descripción_larga' => $marca_especifica['descripción_larga'] ?? '',
                'imagen' => $marca_especifica['imagen'] ?? '',
                'codes' => $marca_especifica['total_codigos'] ?? 0,
                'categoria' => $marca_especifica['categoria'] ?? '',
                'web' => $marca_especifica['web'] ?? ''
            ];
            // Guardar en caché
            $brand_info_cache[$marca_clave] = $resultado;
            return $resultado;
        }
    } catch (Exception $e) {
        // Si hay error, continuar con el fallback
        log_warning("Error obteniendo información de marca, usando fallback", ['error' => $e->getMessage(), 'marca' => $marca]);
    }
    
    // Fallback si no se encuentra la marca - usar imágenes específicas para marcas conocidas
    $imagenes_por_defecto = [
        'hostinger' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721513715.png', // Hostinger específico - imagen correcta
        'meru' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736154267.png',
        'bbva' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721180101.png',
        'santander' => 'https://cdn.codigoamigo.com/panel_marcas/new/1720655525.png',
        'amazon' => 'https://cdn.codigoamigo.com/panel_marcas/new/1741218417.png',
        'netflix' => 'https://cdn.codigoamigo.com/panel_marcas/new/1740747401.png',
        'spotify' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736800071.png',
        'uber' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728824925.png',
        'airbnb' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833170.png',
        'kraken' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833328.png',
        'coinbase' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736154267.png',
        'traderepublic' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721180101.png',
        'n26' => 'https://www.codigoamigo.com/img/panel_marcas/n26.jpg',
        'revolut' => 'https://cdn.codigoamigo.com/panel_marcas/new/1741218417.png',
        'surfshark' => 'https://cdn.codigoamigo.com/panel_marcas/new/1740747401.png',
        'nordvpn' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736800071.png',
        'heygen' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728824925.png',
        'opusclip' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833170.png',
        'worldcoin' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833328.png',
        'indexacapital' => 'https://cdn.codigoamigo.com/panel_marcas/new/1707093800.webp',
        'justeat' => 'https://cdn.codigoamigo.com/panel_marcas/new/justeat.png'
    ];
    
    $marca_clave_safe = $marca_clave ?? '';
    $imagen_default = $imagenes_por_defecto[strtolower($marca_clave_safe ?? '')] ?? '';
    
    $resultado = [
        'nombre' => ucfirst($marca_clave_safe ?? ''),
        'descripcion' => '',
        'descripción_larga' => '',
        'imagen' => $imagen_default,
        'codes' => 0,
        'categoria' => '',
        'web' => ''
    ];
    // Guardar en caché
    $brand_info_cache[$marca_clave] = $resultado;
    return $resultado;
}

// Función para obtener información del usuario
function get_user_info($usuario_identifier) {
    global $user_info_cache;
    
    // Convertir ObjectId a string si es necesario
    $usuario_key = is_object($usuario_identifier) ? (string)$usuario_identifier : $usuario_identifier;
    
    // Verificar si ya tenemos la información en caché
    if (isset($user_info_cache[$usuario_key])) {
        return $user_info_cache[$usuario_key];
    }
    
    // Buscar en la base de datos de usuarios
    try {
        // Usar la función existente getObjectUser que ya maneja las imágenes correctamente
        if (strlen($usuario_key) === 24 && ctype_xdigit($usuario_key)) {
            $usuario = getObjectUser('_id', new MongoDB\BSON\ObjectId($usuario_key));
        } else {
            // Si no es un ObjectId válido, buscar por username
            $usuario = getObjectUser('username', $usuario_key);
        }
        
        if($usuario) {
            $username = $usuario['username'] ?? '';
            // Si no hay username, usar el email o generar uno descriptivo
            if(empty($username)) {
                $email = $usuario['mail'] ?? '';
                if($email) {
                    $username = explode('@', $email)[0]; // Usar parte antes del @
                } else {
                    $username = 'Usuario_' . substr($usuario_key, -4); // Usar últimos 4 caracteres del ID
                }
            }
            
            // Buscar imagen en diferentes campos posibles
            $img = $usuario['img'] ?? $usuario['avatar'] ?? $usuario['foto'] ?? $usuario['image'] ?? '';
            
            // Si no hay imagen, usar la imagen por defecto
            if (empty($img)) {
                global $url_usuario_sin_foto;
                $img = $url_usuario_sin_foto ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
            }
            
            $resultado = [
                'username' => $username,
                'img' => $img,
                'mail' => $usuario['mail'] ?? ''
            ];
            // Guardar en caché
            $user_info_cache[$usuario_key] = $resultado;
            return $resultado;
        }
    } catch (Exception $e) {
        // Error al obtener usuario, usar valores por defecto
    }
    
    // Fallback si no se encuentra el usuario - generar nombres más realistas
    $nombres_fake = [
        'Ana García', 'Carlos López', 'María Rodríguez', 'José Martínez', 'Laura Sánchez',
        'David González', 'Carmen Pérez', 'Antonio Martín', 'Isabel García', 'Francisco Ruiz',
        'Elena Díaz', 'Miguel Torres', 'Pilar Moreno', 'Rafael Jiménez', 'Cristina Álvarez',
        'Javier Romero', 'Sonia Herrera', 'Fernando Ramos', 'Teresa Morales', 'Alejandro Castro'
    ];
    
    $fallback_name = $nombres_fake[array_rand($nombres_fake)];
    
    // Generar avatar placeholder con iniciales
    $iniciales = '';
    $palabras = explode(' ', $fallback_name);
    foreach($palabras as $palabra) {
        $iniciales .= strtoupper(substr($palabra, 0, 1));
    }
    
        $resultado = [
            'username' => $fallback_name,
            'img' => '', // Se usará placeholder con iniciales
            'mail' => '',
            'iniciales' => $iniciales
        ];
        // Guardar en caché
        global $user_info_cache;
        $user_info_cache[$usuario_key] = $resultado;
        return $resultado;
}

// Función para generar títulos personalizados según la marca
function generate_brand_titles($nombre_marca, $categoria, $total_codigos) {
    // Títulos específicos por marca
    $titulos_especificos = [
        'MERU' => [
            'h1' => 'Códigos de Amigo Meru: Gana Recompensas al Unirte',
            'h2' => 'Lista de Códigos de Amigo Meru Verificados'
        ],
        'WORLDCOIN' => [
            'h1' => 'Códigos de Amigo Worldcoin: Obtén WLD Gratis',
            'h2' => 'Códigos de Invitación Worldcoin Verificados'
        ],
        'HOSTINGER' => [
            'h1' => 'Códigos de Amigo Hostinger: Descuentos en Hosting',
            'h2' => 'Cupones de Descuento Hostinger Verificados'
        ],
        'OPUSCLIP' => [
            'h1' => 'Códigos de Amigo OpusClip: Crea Videos con IA',
            'h2' => 'Códigos de Descuento OpusClip Verificados'
        ],
        'SURFSHARK' => [
            'h1' => 'Códigos de Amigo Surfshark: VPN Premium Barato',
            'h2' => 'Cupones de Descuento Surfshark Verificados'
        ],
        'KRAKEN' => [
            'h1' => 'Códigos de Amigo Kraken: Trading de Criptomonedas',
            'h2' => 'Códigos de Referido Kraken Verificados'
        ],
        'BBVA' => [
            'h1' => 'Códigos de Amigo BBVA: Beneficios Bancarios',
            'h2' => 'Códigos de Descuento BBVA Verificados'
        ],
        'SANTANDER' => [
            'h1' => 'Códigos de Amigo Santander: Servicios Financieros',
            'h2' => 'Cupones de Descuento Santander Verificados'
        ],
        'AMAZON' => [
            'h1' => 'Códigos de Amigo Amazon: Descuentos en Compras',
            'h2' => 'Códigos de Descuento Amazon Verificados'
        ],
        'NETFLIX' => [
            'h1' => 'Códigos de Amigo Netflix: Streaming Gratis',
            'h2' => 'Códigos de Descuento Netflix Verificados'
        ]
    ];
    
    // Si hay títulos específicos para la marca, usarlos
    if(isset($titulos_especificos[$nombre_marca])) {
        return $titulos_especificos[$nombre_marca];
    }
    
    // Generar títulos genéricos basados en la categoría
    $titulos_genericos = [
        'tecnologia' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Descuentos en Tecnología',
            'h2' => 'Códigos de Descuento ' . $nombre_marca . ' Verificados'
        ],
        'finanzas' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Servicios Financieros',
            'h2' => 'Códigos de Descuento ' . $nombre_marca . ' Verificados'
        ],
        'hosting' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Hosting y Dominios',
            'h2' => 'Cupones de Descuento ' . $nombre_marca . ' Verificados'
        ],
        'streaming' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Entretenimiento Digital',
            'h2' => 'Códigos de Descuento ' . $nombre_marca . ' Verificados'
        ],
        'comercio' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Compras Online',
            'h2' => 'Códigos de Descuento ' . $nombre_marca . ' Verificados'
        ],
        'crypto' => [
            'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Criptomonedas',
            'h2' => 'Códigos de Referido ' . $nombre_marca . ' Verificados'
        ]
    ];
    
    // Buscar categoría específica
    $categoria_lower = strtolower($categoria);
    foreach($titulos_genericos as $cat => $titulos) {
        if(strpos($categoria_lower, $cat) !== false) {
            return $titulos;
        }
    }
    
    // Títulos por defecto
    return [
        'h1' => 'Códigos de Amigo ' . $nombre_marca . ': Descuentos Exclusivos',
        'h2' => 'Códigos de Descuento ' . $nombre_marca . ' Verificados'
    ];
}

// Función para obtener información de una categoría
function get_category_info($categoria_url) {
    $categorias_info = [
        'alimentacion-y-gastronomia-comparte-y-gana' => [
            'nombre' => 'Alimentación y Gastronomía',
            'descripcion' => 'Descubre los mejores códigos de descuento en restaurantes, supermercados, comida a domicilio y productos gastronómicos.',
            'icono' => 'fas fa-utensils'
        ],
        'tecnologia-y-electronica-comparte-y-gana' => [
            'nombre' => 'Tecnología y Electrónica',
            'descripcion' => 'Ahorra en dispositivos electrónicos, smartphones, ordenadores, accesorios tecnológicos y gadgets.',
            'icono' => 'fas fa-laptop'
        ],
        'moda-y-belleza-comparte-y-gana' => [
            'nombre' => 'Moda y Belleza',
            'descripcion' => 'Encuentra descuentos en ropa, calzado, cosméticos, productos de belleza y accesorios de moda.',
            'icono' => 'fas fa-tshirt'
        ],
        'hogar-y-jardin-comparte-y-gana' => [
            'nombre' => 'Hogar y Jardín',
            'descripcion' => 'Ahorra en muebles, decoración, electrodomésticos, herramientas de jardín y productos para el hogar.',
            'icono' => 'fas fa-home'
        ],
        'deportes-y-ocio-comparte-y-gana' => [
            'nombre' => 'Deportes y Ocio',
            'descripcion' => 'Descuentos en equipamiento deportivo, gimnasios, actividades de ocio y entretenimiento.',
            'icono' => 'fas fa-dumbbell'
        ],
        'viajes-y-turismo-comparte-y-gana' => [
            'nombre' => 'Viajes y Turismo',
            'descripcion' => 'Ahorra en vuelos, hoteles, alquiler de coches, paquetes turísticos y experiencias de viaje.',
            'icono' => 'fas fa-plane'
        ],
        'finanzas-y-seguros-comparte-y-gana' => [
            'nombre' => 'Finanzas y Seguros',
            'descripcion' => 'Códigos para servicios bancarios, seguros, inversiones y productos financieros.',
            'icono' => 'fas fa-credit-card'
        ],
        'salud-y-bienestar-comparte-y-gana' => [
            'nombre' => 'Salud y Bienestar',
            'descripcion' => 'Descuentos en farmacias, productos de salud, bienestar, fitness y cuidado personal.',
            'icono' => 'fas fa-heart'
        ]
    ];
    
    return $categorias_info[$categoria_url] ?? [
        'nombre' => 'Categoría',
        'descripcion' => 'Descubre los mejores códigos de descuento en esta categoría.',
        'icono' => 'fas fa-tag'
    ];
}

// Función para obtener marcas de una categoría específica
function get_brands_by_category($categoria_url) {
    // Por ahora, usar solo fallback para evitar errores
    // TODO: Implementar búsqueda en base de datos cuando esté disponible
    $lista_marcas = [];
    $marcas_categoria = [];
    
    if($lista_marcas) {
        foreach($lista_marcas as $marca) {
            $categoria_marca = $marca["categoria"] ?? '';
            $codes = $marca["codes"] ?? 0;
            
            // Verificar si la marca pertenece a esta categoría y tiene códigos
            if($codes > 0 && strpos(strtolower($categoria_marca), strtolower($categoria_url)) !== false) {
                $marcas_categoria[] = $marca;
            }
        }
        
        // Ordenar por número de códigos (más populares primero)
        usort($marcas_categoria, function($a, $b) {
            $codes_a = $a["codes"] ?? 0;
            $codes_b = $b["codes"] ?? 0;
            return $codes_b - $codes_a;
        });
    }
    
    return $marcas_categoria;
}

// Función para generar el layout de la página de categoría
function generate_category_page_layout($categoria_url, $nombre_categoria, $descripcion_categoria, $marcas_categoria) {
    $categoria_info = get_category_info($categoria_url);
    $icono = $categoria_info['icono'];
    
    $html = '<div class="category-page-container">';
    
    // Header de la categoría
    $html .= '<div class="category-header">';
    $html .= '<div class="category-header-content">';
    $html .= '<div class="category-icon-large">';
    $html .= '<i class="' . $icono . '"></i>';
    $html .= '</div>';
    $html .= '<div class="category-info">';
    $html .= '<h1 class="category-title">' . htmlspecialchars($nombre_categoria) . '</h1>';
    $html .= '<p class="category-description">' . htmlspecialchars($descripcion_categoria) . '</p>';
    $html .= '<div class="category-stats">';
    $html .= '<span class="stat-item"><i class="fas fa-tag"></i> ' . count($marcas_categoria) . ' marcas</span>';
    $total_codigos = array_sum(array_column($marcas_categoria, 'codes'));
    $html .= '<span class="stat-item"><i class="fas fa-percentage"></i> ' . $total_codigos . ' códigos</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Publicidad superior
    $html .= generate_adsense_container(get_adsense_top(), 'adsense-category-top', 'margin: 20px 0;');
    
    // Grid de marcas
    $html .= '<div class="brands-grid">';
    $html .= '<h2 class="section-title">Marcas en ' . htmlspecialchars($nombre_categoria) . '</h2>';
    
    if(!empty($marcas_categoria)) {
        $html .= '<div class="brands-list">';
        $count = 0;
        foreach($marcas_categoria as $marca) {
            $html .= generate_brand_card($marca);
            $count++;
            
            // Insertar publicidad cada 8 marcas
            if ($count % 8 == 0 && $count < count($marcas_categoria)) {
                $html .= '<div class="adsense-grid-item" style="grid-column: 1 / -1; margin: 20px 0;">';
                $html .= generate_adsense_container(get_adsense_entremedio(), 'adsense-category-entremedio', 'margin: 0;');
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    } else {
        $html .= '<div class="no-brands">';
        $html .= '<i class="fas fa-search"></i>';
        $html .= '<h3>No hay marcas disponibles</h3>';
        $html .= '<p>Próximamente añadiremos más marcas en esta categoría.</p>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar una tarjeta de marca
function generate_brand_card($marca) {
    $nombre = $marca['nombre'] ?? 'Marca desconocida';
    $nombre_clave = $marca['nombre_clave'] ?? '';
    $imagen = $marca['imagen'] ?? '';
    $codes = $marca['codes'] ?? 0;
    $descripcion = $marca['descripcion'] ?? '';
    
    $html = '<div class="brand-card">';
    
    // Imagen de la marca
    $html .= '<div class="brand-card-image">';
    if($imagen) {
        $html .= '<img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($nombre) . '">';
    } else {
        $html .= '<div class="brand-card-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // Información de la marca
    $html .= '<div class="brand-card-info">';
    $html .= '<h3 class="brand-card-name">' . htmlspecialchars($nombre) . '</h3>';
    
    if($descripcion) {
        $html .= '<p class="brand-card-description">' . htmlspecialchars(mb_substr($descripcion, 0, 100)) . '...</p>';
    }
    
    $html .= '<div class="brand-card-stats">';
    $html .= '<span class="brand-codes-count">' . $codes . ' códigos</span>';
    $html .= '</div>';
    
    // Botón de acción
    $html .= '<a href="/de-' . htmlspecialchars($nombre_clave) . '" class="brand-card-button">';
    $html .= '<i class="fas fa-arrow-right"></i> Ver códigos';
    $html .= '</a>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar la sección de publicar código en páginas de marca
function generate_publicar_codigo_section($marca, $nombre_marca) {
    // Verificar si el usuario está logueado
    $usuario_logueado = isset($_SESSION["user_id"]) && $_SESSION["user_id"] != "";
    
    $html = '<div class="publicar-codigo-section" style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); padding: 25px; border-radius: 15px; margin: 20px 0; box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);">';
    
    $html .= '<div class="row" style="align-items: center;">';
    
    // Lado izquierdo - Información
    $html .= '<div class="col-md-8">';
    $html .= '<h3 style="color: white; margin: 0 0 10px 0; font-size: 1.8rem; font-weight: bold;">';
    $html .= '<i class="fas fa-plus-circle" style="margin-right: 10px;"></i>';
    $html .= '¿Tienes un código de ' . htmlspecialchars($nombre_marca) . '?';
    $html .= '</h3>';
    $html .= '<p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 1.1rem;">';
    $html .= 'Comparte tu código y gana dinero por cada persona que lo use. ¡Es gratis y fácil!';
    $html .= '</p>';
    $html .= '</div>';
    
    // Lado derecho - Botón de acción
    $html .= '<div class="col-md-4 text-right">';
    
    if ($usuario_logueado) {
        // Si está logueado, mostrar botón directo a publicar con marca preseleccionada
        $html .= '<a href="/nuevo_codigo?marca=' . urlencode($marca) . '" class="btn btn-publicar-codigo" style="background: white; color: #FF6B35; padding: 15px 30px; border-radius: 25px; font-weight: bold; font-size: 1.2rem; text-decoration: none; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">';
        $html .= '<i class="fas fa-plus" style="margin-right: 8px;"></i>';
        $html .= 'Publicar Código';
        $html .= '</a>';
    } else {
        // Si no está logueado, mostrar botón de registro que abre el modal
        $html .= '<button class="btn btn-publicar-codigo open_modal_login" data-redirect-url="/nuevo_codigo?marca=' . urlencode($marca) . '" style="background: white; color: #FF6B35; padding: 15px 30px; border-radius: 25px; font-weight: bold; font-size: 1.2rem; text-decoration: none; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: none; cursor: pointer;">';
        $html .= '<i class="fas fa-user-plus" style="margin-right: 8px;"></i>';
        $html .= 'Registrarse para Publicar';
        $html .= '</button>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Información adicional
    $html .= '<div class="row" style="margin-top: 15px;">';
    $html .= '<div class="col-md-12">';
    $html .= '<div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; border-left: 4px solid white;">';
    $html .= '<p style="color: white; margin: 0; font-size: 0.95rem;">';
    $html .= '<i class="fas fa-info-circle" style="margin-right: 8px;"></i>';
    $html .= '<strong>Beneficios:</strong> Gana dinero por cada código usado • Códigos verificados • Comunidad activa • Sin costes de registro';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // CSS para el hover del botón
    $html .= '<style>';
    $html .= '.btn-publicar-codigo:hover {';
    $html .= 'transform: translateY(-2px);';
    $html .= 'box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;';
    $html .= 'color: #E55A2B !important;';
    $html .= '}';
    $html .= '</style>';
    
    return $html;
}

// Función para obtener marcas populares para la home
function get_popular_brands_for_home($limit = 9) {
    $marcas_con_info = [];
    
    try {
        $collection_codigos = getCollectionCodigos();
        
        // Pipeline para obtener marcas con más códigos activos
        $pipeline = [
            ['$match' => ['estado' => 0]], // Solo códigos activos
            ['$group' => [
                '_id' => '$marca',
                'total_codigos' => ['$sum' => 1]
            ]],
            ['$sort' => ['total_codigos' => -1]],
            ['$limit' => $limit]
        ];
        
        $marcas_populares = $collection_codigos->aggregate($pipeline)->toArray();
        
        // Obtener información detallada de cada marca
        $collection_marcas = getCollectionMarcas();
        
        foreach($marcas_populares as $marca) {
            $marca_info = $collection_marcas->findOne(['nombre_clave' => $marca['_id']]);
            if($marca_info) {
                $marcas_con_info[] = [
                    'nombre' => $marca_info['nombre'] ?? $marca['_id'],
                    'nombre_clave' => $marca_info['nombre_clave'] ?? $marca['_id'],
                    'imagen' => $marca_info['imagen'] ?? '/img/no_image.png',
                    'categoria' => $marca_info['categoria'] ?? 'General',
                    'total_codigos' => $marca['total_codigos']
                ];
            }
        }
    } catch (Exception $e) {
        // Si hay error con MongoDB, usar datos de respaldo
        log_warning("Error obteniendo marcas populares, usando datos de respaldo", ['error' => $e->getMessage()]);
    }
    
    // Si no se obtuvieron marcas de la base de datos, usar datos de respaldo
    if(empty($marcas_con_info)) {
        $marcas_con_info = get_fallback_popular_brands($limit);
    }
    
    return $marcas_con_info;
}

// Función de respaldo para obtener marcas populares
function get_fallback_popular_brands($limit = 9) {
    // Cargar el archivo JSON de marcas como respaldo
    $json_file = __DIR__ . '/../datos.json';
    if (!file_exists($json_file)) {
        return get_hardcoded_popular_brands($limit);
    }
    
    $todas_marcas = json_decode(file_get_contents($json_file), true);
    if (!$todas_marcas) {
        return get_hardcoded_popular_brands($limit);
    }
    
    // Filtrar marcas con códigos y ordenar por popularidad
    $marcas_con_codigos = array_filter($todas_marcas, function($marca) {
        return isset($marca['codes']) && $marca['codes'] > 0;
    });
    
    // Ordenar por número de códigos
    usort($marcas_con_codigos, function($a, $b) {
        $codes_a = $a['codes'] ?? 0;
        $codes_b = $b['codes'] ?? 0;
        return $codes_b - $codes_a;
    });
    
    // Tomar las primeras N marcas
    $marcas_seleccionadas = array_slice($marcas_con_codigos, 0, $limit);
    
    $marcas_con_info = [];
    foreach($marcas_seleccionadas as $marca) {
        $marcas_con_info[] = [
            'nombre' => $marca['nombre'] ?? $marca['nombre_clave'] ?? 'Marca',
            'nombre_clave' => $marca['nombre_clave'] ?? '',
            'imagen' => $marca['imagen'] ?? '/img/no_image.png',
            'categoria' => $marca['categoria'] ?? 'General',
            'total_codigos' => $marca['codes'] ?? 0
        ];
    }
    
    return $marcas_con_info;
}

// Función con marcas hardcodeadas como último respaldo
function get_hardcoded_popular_brands($limit = 9) {
    $marcas_hardcoded = [
        [
            'nombre' => 'Amazon',
            'nombre_clave' => 'amazon',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/amazon.png',
            'categoria' => 'Compras',
            'total_codigos' => 15
        ],
        [
            'nombre' => 'Zara',
            'nombre_clave' => 'zara',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/zara.png',
            'categoria' => 'Moda',
            'total_codigos' => 12
        ],
        [
            'nombre' => 'Nike',
            'nombre_clave' => 'nike',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/nike.png',
            'categoria' => 'Deportes',
            'total_codigos' => 10
        ],
        [
            'nombre' => 'Adidas',
            'nombre_clave' => 'adidas',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/adidas.png',
            'categoria' => 'Deportes',
            'total_codigos' => 8
        ],
        [
            'nombre' => 'H&M',
            'nombre_clave' => 'hm',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/hm.png',
            'categoria' => 'Moda',
            'total_codigos' => 7
        ],
        [
            'nombre' => 'El Corte Inglés',
            'nombre_clave' => 'el-corte-ingles',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/el-corte-ingles.png',
            'categoria' => 'Compras',
            'total_codigos' => 6
        ],
        [
            'nombre' => 'Decathlon',
            'nombre_clave' => 'decathlon',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/decathlon.png',
            'categoria' => 'Deportes',
            'total_codigos' => 5
        ],
        [
            'nombre' => 'Primark',
            'nombre_clave' => 'primark',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/primark.png',
            'categoria' => 'Moda',
            'total_codigos' => 4
        ],
        [
            'nombre' => 'MediaMarkt',
            'nombre_clave' => 'mediamarkt',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/mediamarkt.png',
            'categoria' => 'Tecnología',
            'total_codigos' => 3
        ]
    ];
    
    return array_slice($marcas_hardcoded, 0, $limit);
}

// Función para generar la sección de marcas populares en la home
function generate_popular_brands_section($limit = 9) {
    $marcas_populares = get_popular_brands_for_home($limit);
    
    if(empty($marcas_populares)) {
        return '';
    }
    
    $html = '<div class="popular-brands-section">';
    $html .= '<div class="container">';
    $html .= '<h2 class="section-title">Marcas Populares</h2>';
    $html .= '<p class="section-subtitle">Descubre las marcas con más códigos de descuento</p>';
    
    // Contenedor del slider
    $html .= '<div class="brands-slider-container">';
    $html .= '<div class="brands-slider" id="brandsSlider">';
    
    foreach($marcas_populares as $marca) {
        $nombre = htmlspecialchars($marca['nombre']);
        $nombre_clave = htmlspecialchars($marca['nombre_clave']);
        $imagen = $marca['imagen'];
        $categoria = htmlspecialchars($marca['categoria']);
        $total_codigos = $marca['total_codigos'];
        
        // Manejar diferentes tipos de URLs de imagen
        if (!empty($imagen)) {
            if (strpos($imagen, 'cloudfront.net') !== false || strpos($imagen, 'codigoamigo.com') !== false) {
                // Ya es una URL completa
            } elseif (!str_starts_with($imagen, 'http')) {
                $imagen = 'https://www.codigoamigo.com' . $imagen;
            }
        } else {
            $imagen = 'https://www.codigoamigo.com/img/no_image.png';
        }
        
        $html .= '<div class="brand-slide">';
        $html .= '<div class="brand-card">';
        $html .= '<a href="/de-' . $nombre_clave . '" class="brand-link">';
        $html .= '<div class="brand-image">';
        $html .= '<img src="' . $imagen . '" alt="' . $nombre . '" loading="lazy">';
        $html .= '</div>';
        $html .= '<div class="brand-info">';
        $html .= '<h3 class="brand-name">' . $nombre . '</h3>';
        $html .= '<p class="brand-category">' . $categoria . '</p>';
        $html .= '<div class="brand-stats">';
        $html .= '<span class="codes-count">' . $total_codigos . ' códigos</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // brands-slider
    
    // Controles del slider
    $html .= '<button class="slider-btn slider-prev" onclick="moveSlider(-1)">';
    $html .= '<i class="fas fa-chevron-left"></i>';
    $html .= '</button>';
    $html .= '<button class="slider-btn slider-next" onclick="moveSlider(1)">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '</button>';
    
    // Indicadores de puntos
    $html .= '<div class="slider-dots">';
    $total_slides = count($marcas_populares);
    $dots_needed = ceil($total_slides / 3); // 3 marcas por slide
    for($i = 0; $i < $dots_needed; $i++) {
        $active_class = ($i === 0) ? ' active' : '';
        $html .= '<span class="dot' . $active_class . '" onclick="goToSlide(' . ($i + 1) . ')"></span>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // brands-slider-container
    $html .= '</div>'; // container
    $html .= '</div>'; // popular-brands-section
    
    // JavaScript para el slider
    $html .= '<script>
    let currentSlide = 0;
    const totalSlides = ' . count($marcas_populares) . ';
    const slidesPerView = window.innerWidth <= 768 ? 1 : (window.innerWidth <= 1024 ? 2 : 3);
    
    function moveSlider(direction) {
        const slider = document.getElementById("brandsSlider");
        if (!slider) return;
        
        const maxSlides = Math.ceil(totalSlides / slidesPerView);
        currentSlide += direction;
        
        if (currentSlide < 0) currentSlide = maxSlides - 1;
        if (currentSlide >= maxSlides) currentSlide = 0;
        
        const translateX = -currentSlide * (100 / slidesPerView);
        slider.style.transform = `translateX(${translateX}%)`;
        
        // Actualizar indicadores
        updateDots();
    }
    
    function goToSlide(slideIndex) {
        currentSlide = slideIndex - 1;
        const slider = document.getElementById("brandsSlider");
        if (!slider) return;
        
        const translateX = -currentSlide * (100 / slidesPerView);
        slider.style.transform = `translateX(${translateX}%)`;
        
        updateDots();
    }
    
    function updateDots() {
        const dots = document.querySelectorAll(".dot");
        dots.forEach((dot, index) => {
            dot.classList.toggle("active", index === currentSlide);
        });
    }
    
    // Auto-slide cada 5 segundos
    setInterval(() => {
        moveSlider(1);
    }, 5000);
    
    // Responsive
    window.addEventListener("resize", function() {
        const newSlidesPerView = window.innerWidth <= 768 ? 1 : (window.innerWidth <= 1024 ? 2 : 3);
        if (newSlidesPerView !== slidesPerView) {
            currentSlide = 0;
            const slider = document.getElementById("brandsSlider");
            if (slider) {
                slider.style.transform = "translateX(0%)";
                updateDots();
            }
        }
    });
    </script>';
    
    return $html;
}

?>
