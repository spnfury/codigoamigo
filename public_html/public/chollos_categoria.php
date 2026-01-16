<?php
// Página de chollos por categoría
?>

<link rel="stylesheet" href="/css/chollos-voting.css?v=<?php echo time(); ?>">

<link rel="stylesheet" href="/css/chollos-cards.css?v=<?php echo time(); ?>">

<style>
.chollos-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 10px 20px; /* Reduced top padding */
}

.chollos-header {
    text-align: center;
    margin-bottom: 30px; /* Reduced bottom margin */
    margin-top: 0;
}

.chollos-header h1 {
    font-size: 2.5em;
    color: #E30613;
    margin-bottom: 10px;
}

.chollos-header p {
    font-size: 1.2em;
    color: #ccc;
}

.chollos-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.chollo-filter {
    padding: 10px 20px;
    background: white;
    border: 2px solid #ddd;
    border-radius: 25px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.3s;
}

.chollo-filter:hover,
.chollo-filter.active {
    background: #E30613;
    color: white;
    border-color: #E30613;
}

.breadcrumb {
    margin-bottom: 20px;
    font-size: 0.9em;
}

.breadcrumb a {
    color: #E30613;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}
</style>

    <style>
    /* New Layout Styles */
    .chollos-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 30px;
        margin-top: 30px;
        align-items: start;
    }

    .chollos-main {
        flex: 1;
    }

    /* SEO Content Truncation */
    .seo-content-container {
        position: relative;
        margin-bottom: 20px;
    }
    .seo-content-inner {
        color: #666;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .seo-content-inner.expanded {
        display: block;
        max-height: none;
        overflow: visible;
        -webkit-line-clamp: unset;
    }
    .read-more-trigger {
        background: none;
        border: none;
        color: #E30613;
        cursor: pointer;
        font-weight: bold;
        padding: 0;
        margin-top: 5px;
        font-size: 0.95em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .read-more-trigger:hover {
        text-decoration: underline;
    }

    /* Sidebar Styles */
    .chollos-sidebar {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 20px;
    }

    /* Force dark text on inputs in sidebar (fix for global dark mode) */
    .chollos-sidebar input, 
    .chollos-sidebar select {
        color: #333 !important;
        background-color: #fff !important;
        border-color: #ddd !important;
    }

    .filter-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .filter-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .filter-title {
        font-weight: 700;
        margin-bottom: 12px;
        color: #333;
        font-size: 1em;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .filter-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .filter-list li {
        margin-bottom: 8px;
    }

    .filter-link {
        text-decoration: none;
        color: #555;
        font-size: 0.95em;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-link:hover, .filter-link.active {
        color: #E30613;
        font-weight: 500;
    }

    .filter-link.active::before {
        content: "•";
        color: #E30613;
        font-weight: bold;
    }

    /* Price Filter */
    .price-inputs {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .price-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9em;
    }

    .price-btn {
        margin-top: 10px;
        width: 100%;
        padding: 8px;
        background: white;
        border: 1px solid #E30613;
        color: #E30613;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9em;
        transition: all 0.3s;
    }

    .price-btn:hover {
        background: #E30613;
        color: white;
    }

    /* Sort Options */
    .sort-select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        color: #555;
        font-size: 0.95em;
        cursor: pointer;
    }

    /* Mobile Filter Toggle */
    .mobile-filter-toggle {
        display: none;
        width: 100%;
        padding: 12px;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        margin-bottom: 20px;
        cursor: pointer;
    }

    @media (max-width: 900px) {
        .chollos-layout {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .chollos-sidebar {
            display: none; /* Hidden by default on mobile */
        }
        
        .chollos-sidebar.open {
            display: block;
        }

        .mobile-filter-toggle {
            display: block;
        }
    }
    </style>

    <div class="chollos-container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb">
            <a href="/">Inicio</a> <span style="color: #999;">&rsaquo;</span> 
            <a href="/chollos">Chollos</a>
            <?php 
            if (isset($GLOBALS['categorias_array']) && !empty($GLOBALS['categorias_array'])): 
                $ruta_acumulada = '';
                foreach ($GLOBALS['categorias_array'] as $index => $cat_nombre):
                    $cat_slug = categoriaToSlug($cat_nombre);
                    $ruta_acumulada .= ($ruta_acumulada ? '/' : '') . $cat_slug;
                    ?>
                    <span style="color: #999;">&rsaquo;</span> 
                    <a href="/chollos/<?php echo htmlspecialchars($ruta_acumulada); ?>"><?php echo htmlspecialchars($cat_nombre); ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <div class="chollos-layout">
        <!-- Sidebar Filters -->
        <aside class="chollos-sidebar" id="filterSidebar">
            
            <!-- Filter: Sort -->
            <div class="filter-section">
                <div class="filter-title">Ordenar por</div>
                <select class="sort-select" onchange="applySort(this.value)">
                    <?php 
                    $current_sort = $_GET['sort'] ?? 'recientes';
                    $options = [
                        'recientes' => 'Más recientes',
                        'antiguos' => 'Más antiguos',
                        'populares' => 'Más populares',
                        'precio_asc' => 'Precio: Bajo a Alto',
                        'precio_desc' => 'Precio: Alto a Bajo'
                    ];
                    foreach($options as $val => $label): 
                    ?>
                        <option value="<?php echo $val; ?>" <?php echo $current_sort == $val ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <script>
                function applySort(val) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', val);
                    window.location.href = url.toString();
                }
                </script>
            </div>

            <!-- Filter: Category Tree -->
            <div class="filter-section">
                <div class="filter-title">Categorías</div>
                <ul class="filter-list">
                    <li>
                        <a href="/chollos/<?php echo $GLOBALS['categoria_path'] ?? 'general'; ?>" class="filter-link active">
                            <?php echo htmlspecialchars($nombre_categoria); ?>
                        </a>
                        <?php if (!empty($subcategorias_sugeridas)): ?>
                            <ul style="padding-left: 15px; margin-top: 5px;">
                                <?php 
                                $ruta_base = $GLOBALS['categoria_path'] ?? categoriaToSlug($nombre_categoria);
                                foreach ($subcategorias_sugeridas as $subcat): 
                                    $subcat_slug = categoriaToSlug($subcat);
                                    $url_subcat = '/chollos/' . $ruta_base . '/' . $subcat_slug;
                                ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($url_subcat); ?>" class="filter-link">
                                            <?php echo htmlspecialchars(ucfirst($subcat)); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <!-- Filter: Price -->
            <div class="filter-section">
                <div class="filter-title">Precio</div>
                <form id="priceFilterForm" onsubmit="applyPriceFilter(event)">
                    <div class="price-inputs">
                        <input type="number" id="minPrice" placeholder="Min €" class="price-input" min="0" 
                               value="<?php echo $_GET['min_price'] ?? ''; ?>">
                        <span style="color: #999;">-</span>
                        <input type="number" id="maxPrice" placeholder="Max €" class="price-input" min="0"
                               value="<?php echo $_GET['max_price'] ?? ''; ?>">
                    </div>
                    <button type="submit" class="price-btn">Aplicar precio</button>
                    <?php if(isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                        <div style="text-align: center; margin-top: 8px;">
                           <a href="javascript:clearPriceFilter()" style="font-size: 0.85em; color: #999; text-decoration: underline;">Limpiar</a>
                        </div>
                    <?php endif; ?>
                </form>
                <script>
                function applyPriceFilter(e) {
                    e.preventDefault();
                    const min = document.getElementById('minPrice').value;
                    const max = document.getElementById('maxPrice').value;
                    const url = new URL(window.location.href);
                    
                    if(min) url.searchParams.set('min_price', min);
                    else url.searchParams.delete('min_price');
                    
                    if(max) url.searchParams.set('max_price', max);
                    else url.searchParams.delete('max_price');
                    
                    window.location.href = url.toString();
                }
                function clearPriceFilter() {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('min_price');
                    url.searchParams.delete('max_price');
                    window.location.href = url.toString();
                }
                </script>
            </div>

            <!-- Filter: Discount -->
            <div class="filter-section">
                <div class="filter-title">Descuento</div>
                <ul class="filter-list">
                    <?php 
                    $discounts = [50, 70, 85, 90, 95];
                    $current_discount = $_GET['min_discount'] ?? 0;
                    foreach($discounts as $disc): 
                        $active = ($current_discount == $disc) ? 'active' : '';
                    ?>
                    <li>
                        <a href="javascript:applyDiscount(<?php echo $disc; ?>)" class="filter-link <?php echo $active; ?>">
                            Más de <?php echo $disc; ?>%
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if(isset($_GET['min_discount'])): ?>
                    <div style="margin-top: 8px;">
                       <a href="javascript:applyDiscount(null)" style="font-size: 0.85em; color: #999; text-decoration: underline;">Borrar filtro</a>
                    </div>
                <?php endif; ?>
                <script>
                function applyDiscount(val) {
                    const url = new URL(window.location.href);
                    if(val) url.searchParams.set('min_discount', val);
                    else url.searchParams.delete('min_discount');
                    window.location.href = url.toString();
                }
                </script>
            </div>

            <!-- Filter: Brand Search -->
            <div class="filter-section">
                <div class="filter-title">Marca</div>
                 <form onsubmit="applyBrandFilter(event)">
                    <div style="position: relative;">
                         <input type="text" id="brandInput" placeholder="Buscar marca..." class="price-input" 
                                value="<?php echo htmlspecialchars($_GET['marca'] ?? ''); ?>">
                         <button type="submit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #999; cursor: pointer;">
                             <i class="fas fa-search"></i>
                         </button>
                    </div>
                </form>
                <script>
                function applyBrandFilter(e) {
                    e.preventDefault();
                    const brand = document.getElementById('brandInput').value;
                    const url = new URL(window.location.href);
                    if(brand) url.searchParams.set('marca', brand);
                    else url.searchParams.delete('marca');
                    window.location.href = url.toString();
                }
                </script>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="chollos-main">

            <!-- Mobile Sidebar Toggle -->
            <div class="mobile-filter-toggle" onclick="document.getElementById('filterSidebar').classList.toggle('open')">
                <i class="fas fa-filter" style="margin-right: 8px;"></i> Filtrar y Ordenar
            </div>

            <!-- Header con H1 -->
            <?php
            // Usar contenido SEO ya obtenido en el controlador para evitar doble llamada
            $contenido_seo = $GLOBALS['contenido_seo'] ?? null;
            
            $h1_mostrar = ($contenido_seo && !empty($contenido_seo['h1'])) 
                ? $contenido_seo['h1'] 
                : "Chollos de " . htmlspecialchars($nombre_categoria);
            ?>

            <!-- Header con H1 y Contenido SEO -->
            <div class="chollos-header" style="text-align: left; margin-bottom: 20px;">
                <h1 style="font-size: 2em; margin-bottom: 10px;"><?php echo htmlspecialchars($h1_mostrar); ?></h1>
                
                <?php if ($contenido_seo && !empty($contenido_seo['html'])): ?>
                    <div class="seo-content-container">
                        <div class="seo-content-inner" id="seoContentInner">
                            <?php 
                            // Limpiar HTML de enlaces y botones molestos, permitiendo solo texto y estructura básica
                            $html_limpio = html_entity_decode($contenido_seo['html']);
                            echo strip_tags($html_limpio, '<h1><h2><h3><h4><p><b><strong><ul><li><br>'); 
                            ?>
                        </div>
                        <button class="read-more-trigger" id="readMoreBtn" onclick="toggleSeoContent()">
                            Leer más <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <script>
                    function toggleSeoContent() {
                        const content = document.getElementById('seoContentInner');
                        const btn = document.getElementById('readMoreBtn');
                        const isExpanded = content.classList.toggle('expanded');
                        
                        if (isExpanded) {
                            btn.innerHTML = 'Leer menos <i class="fas fa-chevron-up"></i>';
                        } else {
                            btn.innerHTML = 'Leer más <i class="fas fa-chevron-down"></i>';
                            // Scroll back up to the header if they collapse it
                            document.querySelector('.chollos-header').scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                    
                    // Solo mostrar el botón si el texto realmente se trunca
                    window.addEventListener('load', function() {
                        const content = document.getElementById('seoContentInner');
                        const btn = document.getElementById('readMoreBtn');
                        if (content && content.scrollHeight <= content.offsetHeight) {
                            btn.style.display = 'none';
                        }
                    });
                    </script>
                <?php else: ?>
                    <h2 style="font-size: 1.1em; font-weight: normal; color: #888; margin-top: 0;">Las mejores ofertas y descuentos de <?php echo htmlspecialchars($nombre_categoria); ?></h2>
                <?php endif; ?>
                
                <!-- Active Filters Tags -->
                <?php if(isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['marca']) || isset($_GET['min_discount'])): ?>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                        <?php if(isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px;">
                                Precio: <?php echo ($_GET['min_price'] ?? '0') . '€ - ' . ($_GET['max_price'] ?? '∞'); ?>
                                <a href="javascript:clearPriceFilter()" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                        <?php if(isset($_GET['min_discount'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px;">
                                Descuento: > <?php echo htmlspecialchars($_GET['min_discount']); ?>%
                                <a href="javascript:applyDiscount(null)" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                         <?php if(isset($_GET['marca'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px;">
                                Marca: <?php echo htmlspecialchars($_GET['marca']); ?>
                                <a href="javascript:document.getElementById('brandInput').value=''; applyBrandFilter(new Event('submit'))" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php // Widget de Top Chollos movido a posición después del grid para mejor UX ?>

            <?php if (!empty($chollos)): ?>
                <div id="chollos-grid-container">
                    <?php imprimir_grid_chollos($chollos, 3); ?>
                </div>
                
                <?php
                // Mostrar paginación estilo Google
                $total_chollos = isset($total_chollos) ? $total_chollos : 0;
                $current_page = isset($current_page) ? $current_page : 1;
                $items_per_page = isset($items_per_page) ? $items_per_page : 24;
                
                if ($total_chollos > 0) {
                    echo generate_modern_pagination($total_chollos, $current_page, $items_per_page, 'chollos');
                }
                ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 12px;">
                    <i class="fas fa-search" style="font-size: 3em; color: #ddd; margin-bottom: 20px;"></i>
                    <p style="font-size: 1.2em; color: #666; font-weight: 600;">No encontramos chollos con estos filtros.</p>
                    <p style="color: #999; margin-top: 5px;">Intenta ajustar el precio o borrar los filtros.</p>
                    <a href="?" style="display: inline-block; margin-top: 15px; padding: 8px 20px; background: #E30613; color: white; border-radius: 20px; text-decoration: none;">Ver todos en <?php echo htmlspecialchars($nombre_categoria); ?></a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>


    <!-- Widget de Últimos Comentarios -->
    <?php
    // Widget de Top Chollos (Los más calientes y populares) - Ahora ubicado aquí para mejor UX
    // Solo mostrar si NO hay filtros activos (excepto categoría base)
    $filtros_activos = isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['marca']) || isset($_GET['min_discount']);
    
    if (!$filtros_activos && function_exists('renderHotDealsWidget')) {
        echo '<div style="margin: 40px auto; max-width: 1200px; padding: 0 20px;">';
        echo renderHotDealsWidget($nombre_categoria);
        echo '</div>';
    }
    
    if (!function_exists('obtenerUltimosComentariosGlobales')) {
        include_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';
    }
    if (!function_exists('categoriaToSlug')) {
        include_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';
    }
    
    $ultimos_comentarios = obtenerUltimosComentariosGlobales(6);
    ?>
    
    <?php if (!empty($ultimos_comentarios)): ?>
    <div class="latest-comments-widget" style="margin: 40px auto; max-width: 1200px; padding: 0 20px;">
        <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.4em; color: #333; display: flex; align-items: center; gap: 10px;">
                <i class="far fa-comments" style="color: #E30613;"></i> Últimos comentarios
            </h3>
            
            <div class="latest-comments-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                <?php foreach ($ultimos_comentarios as $comentario): ?>
                    <div class="comment-card" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; transition: transform 0.2s; background: #f8f9fa;">
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <img src="<?php echo !empty($comentario['usuario_img']) ? $comentario['usuario_img'] : '/assets/img/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($comentario['usuario_nombre']); ?>"
                                 style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($comentario['usuario_nombre']); ?>&background=random'">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9em; color: #333;"><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></div>
                                <div style="font-size: 0.75em; color: #666;"><?php echo date('d/m H:i', strtotime($comentario['fecha'])); ?></div>
                            </div>
                        </div>
                        
                        <a href="/chollos/<?php echo $comentario['chollo_categoria'] != 'general' ? categoriaToSlug($comentario['chollo_categoria']) : 'general'; ?>/<?php echo $comentario['chollo_id']; ?>" 
                           style="text-decoration: none; color: inherit; display: block;">
                            <div style="font-size: 0.85em; color: #E30613; font-weight: 600; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                En: <?php echo htmlspecialchars($comentario['chollo_titulo']); ?>
                            </div>
                            <div style="font-size: 0.95em; color: #555; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                "<?php echo htmlspecialchars($comentario['comentario']); ?>"
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <style>
        .comment-card:hover { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #E30613 !important; }
    </style>
    <?php endif; ?>

    <!-- Telegram Promo Block -->
    <div style="background: linear-gradient(135deg, #0088cc 0%, #32aaff 100%); border-radius: 12px; padding: 30px; margin: 40px 0; text-align: center; color: white; box-shadow: 0 10px 30px rgba(0, 136, 204, 0.3);">
        <div style="font-size: 3em; margin-bottom: 20px;">
            <i class="fab fa-telegram" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));"></i>
        </div>
        <h2 style="color: white; font-size: 2em; margin-bottom: 15px; font-weight: 800;">¡No te pierdas ningún chollo!</h2>
        <p style="font-size: 1.2em; margin-bottom: 25px; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
            Únete a nuestro canal de Telegram y recibe las mejores ofertas en tiempo real. ¡Ya somos más de 500!
        </p>
        <div class="telegram-cta-container">
            <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" 
               class="telegram-button" 
               style="display: inline-flex; align-items: center; gap: 10px; padding: 15px 30px; background: white; color: #0088cc; text-decoration: none; border-radius: 30px; font-weight: 700; font-size: 1.1em; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);">
                <span style="font-size: 1.3em;">✈️</span>
                Unirse al canal
            </a>
        </div>
        <style>
            .telegram-button:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3) !important;
                background: #f0f0f0 !important;
            }
        </style>
    </div>

    <!-- Script de votación -->
    <script src="/js/chollos-voting.js?v=<?php echo time(); ?>"></script>

<?php 
if(function_exists('add_sticky_telegram_button')) {
    // add_sticky_telegram_button();
}
get_footer(); 
?>

