<?php
// Página de listado principal de chollos
?>

<link rel="stylesheet" href="/css/chollos-cards.css?v=<?php echo time(); ?>">

<style>
.chollos-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.chollos-header {
    text-align: center;
    margin-bottom: 40px;
}

.chollos-header h1 {
    font-size: 2.5em;
    color: #E30613;
    margin-bottom: 10px;
}

.chollos-header p {
    font-size: 1.2em;
    color: #666;
}

.chollos-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #2a2a2a;
    border-radius: 8px;
}

.chollo-filter {
    padding: 10px 20px;
    background: #333;
    border: 1px solid #444;
    border-radius: 25px;
    text-decoration: none;
    color: #e0e0e0;
    font-weight: 500;
    transition: all 0.3s;
}

.chollo-filter:hover,
.chollo-filter.active {
    background: #E30613;
    color: white;
    border-color: #E30613;
}

@media (max-width: 600px) {
    .chollos-container {
        padding: 10px;
    }
}
</style>


    <link rel="stylesheet" href="/css/chollos-layout.css?v=<?php echo time(); ?>">

    <div class="chollos-container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb">
            <a href="/">Inicio</a> <span style="color: #999;">&rsaquo;</span> 
            <a href="/chollos">Chollos</a>
        </nav>

        <!-- Mobile Filter Overlay -->
        <div class="mobile-sidebar-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>

        <div class="chollos-layout">
        
        <?php include __DIR__ . '/../myphp/layouts/sidebar_chollos.php'; ?>

        <!-- Main Content -->
        <main class="chollos-main">

            <!-- Mobile Sidebar Toggle -->
            <div class="mobile-filter-toggle" onclick="openMobileSidebar()">
                <i class="fas fa-filter" style="margin-right: 8px;"></i> Filtrar y Ordenar
            </div>

            <script>
            function openMobileSidebar() {
                document.getElementById('filterSidebar').classList.add('open');
                document.getElementById('mobileOverlay').classList.add('open');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }

            function closeMobileSidebar() {
                document.getElementById('filterSidebar').classList.remove('open');
                document.getElementById('mobileOverlay').classList.remove('open');
                document.body.style.overflow = ''; // Restore scrolling
            }
            </script>

            <div class="chollos-header">
                <h1>Chollos y Ofertas</h1>
                <p>Los mejores descuentos encontrados por la comunidad</p>
                
                 <!-- Active Filters Tags -->
                <?php if(isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['marca']) || isset($_GET['min_discount'])): ?>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; justify-content: center;">
                        <?php if(isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px; border: 1px solid #dee2e6;">
                                Precio: <?php echo ($_GET['min_price'] ?? '0') . '€ - ' . ($_GET['max_price'] ?? '∞'); ?>
                                <a href="javascript:clearPriceFilter()" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                         <?php if(isset($_GET['min_discount'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px; border: 1px solid #dee2e6;">
                                Descuento: > <?php echo htmlspecialchars($_GET['min_discount']); ?>%
                                <a href="javascript:applyDiscount(null)" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                         <?php if(isset($_GET['marca'])): ?>
                            <span style="background: #eef2ff; color: #E30613; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; display: flex; align-items: center; gap: 6px; border: 1px solid #dee2e6;">
                                Marca: <?php echo htmlspecialchars($_GET['marca']); ?>
                                <a href="javascript:document.getElementById('brandInput').value=''; applyBrandFilter(new Event('submit'))" style="color: inherit; text-decoration: none;">&times;</a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php 
            // Obtener la categoría actual para filtrar los Tops
            $categoria_top = null;
            if (isset($filtros['categoria'])) {
                if (is_array($filtros['categoria'])) {
                    // Si es un array (SILO), intentamos obtener el nombre legible si está disponible
                    $categoria_top = $GLOBALS['nombre_categoria'] ?? null;
                } else {
                    $categoria_top = $filtros['categoria'];
                }
            }
            
            // Solo mostrar TOP si NO hay filtros activos (excepto categoría base)
            $filtros_activos = isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['marca']) || isset($_GET['min_discount']);
            
            if (!$filtros_activos) {
                echo renderHotDealsWidget($categoria_top); 
                echo renderSocialProofSlider(); // Nuevo Slider "Social Proof"
            }
            ?>

            <!-- CSS y JS para votación -->
            <link rel="stylesheet" href="/css/chollos-voting.css?v=<?php echo time(); ?>">
            <script>
                const currentUserId = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : ""; ?>';
            </script>

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
                <div style="text-align: center; padding: 60px 20px; background: #2a2a2a; border-radius: 12px; border: 1px solid #444;">
                     <i class="fas fa-search" style="font-size: 3em; color: #444; margin-bottom: 20px;"></i>
                    <p style="font-size: 1.2em; color: #bbb;">No hay chollos disponibles en este momento.</p>
                    <p style="color: #666; margin-top: 10px;">Vuelve pronto para descubrir nuevas ofertas.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Widget de Últimos Comentarios -->
    <?php
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
        <div style="background: #2a2a2a; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.4em; color: #f0f0f0; display: flex; align-items: center; gap: 10px;">
                <i class="far fa-comments" style="color: #E30613;"></i> Últimos comentarios
            </h3>
            
            <div class="latest-comments-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                <?php foreach ($ultimos_comentarios as $comentario): ?>
                    <div class="comment-card" style="border: 1px solid #444; border-radius: 8px; padding: 15px; transition: transform 0.2s; background: #333;">
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <img src="<?php echo !empty($comentario['usuario_img']) ? $comentario['usuario_img'] : '/assets/img/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($comentario['usuario_nombre']); ?>"
                                 style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($comentario['usuario_nombre']); ?>&background=random'">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9em; color: #e0e0e0;"><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></div>
                                <div style="font-size: 0.75em; color: #999;"><?php echo date('d/m H:i', strtotime($comentario['fecha'])); ?></div>
                            </div>
                        </div>
                        
                        <a href="/chollos/<?php echo $comentario['chollo_categoria'] != 'general' ? categoriaToSlug($comentario['chollo_categoria']) : 'general'; ?>/<?php echo $comentario['chollo_id']; ?>" 
                           style="text-decoration: none; color: inherit; display: block;">
                            <div style="font-size: 0.85em; color: #E30613; font-weight: 600; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                En: <?php echo htmlspecialchars($comentario['chollo_titulo']); ?>
                            </div>
                            <div style="font-size: 0.95em; color: #bbb; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
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

    <!-- Enlace al canal de Telegram al final del listado -->
    <div class="telegram-channel-link" style="text-align: center; margin-top: 50px; padding: 30px; background: linear-gradient(135deg, #0088cc 0%, #006699 100%); border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 136, 204, 0.3);">
        <h3 style="color: white; margin-bottom: 15px; font-size: 1.5em; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span style="font-size: 1.3em;">✈️</span>
            ¡Únete a nuestro canal de Telegram!
        </h3>
        <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 20px; font-size: 1.1em;">
            Recibe las mejores ofertas y chollos directamente en tu móvil
        </p>
        <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" 
           class="telegram-button" 
           style="display: inline-flex; align-items: center; gap: 10px; padding: 15px 30px; background: white; color: #0088cc; text-decoration: none; border-radius: 30px; font-weight: 700; font-size: 1.1em; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);">
            <span style="font-size: 1.3em;">✈️</span>
            Unirse al canal
        </a>
    </div>
</div>


<style>
.telegram-channel-link {
    animation: fadeInUp 0.6s ease;
}

.telegram-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    background: #f0f0f0;
}
</style>

<!-- Script de votación -->
<script src="/js/chollos-voting.js?v=<?php echo time(); ?>"></script>

<?php get_footer(); ?>
