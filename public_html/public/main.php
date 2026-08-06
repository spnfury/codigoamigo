<?php
include_once __DIR__ . '/../inc/logger.php';

// Inicializar variables meta si no están definidas
if (!isset($title)) $title = 'Códigos Descuento - CodigoAmigo.com';
if (!isset($description)) $description = 'Los mejores códigos descuento y cupones de las principales marcas. Ahorra dinero con CodigoAmigo.com';
if (!isset($title_social)) $title_social = $title;
if (!isset($description_social)) $description_social = $description;
if (!isset($imagen_social)) $imagen_social = 'https://www.codigoamigo.com/images/logo.png';
if (!isset($links_meta)) $links_meta = '';

get_header_modern($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
$GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno para el footer correspondiente

// Incluir funciones de Chollometro
include_once __DIR__ . '/../myphp/funciones_chollometro.php';

// Incluir estilos de Chollometro
echo '<link rel="stylesheet" href="/assets/css/chollometro-style.css">';

// Inicializar variable si no está definida
if (!isset($numero_codigos)) $numero_codigos = 0;
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

?>
<div class="container-fluid main_entremedio">
	<div class="container text-center bloque_titulo_home">
		<div class="container">
    		<div class="row ">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                	<h1>Códigos Amigos y Códigos Descuento</h1>
                	<p>Comparte todos tus códigos y gana dinero</p><br>
                </div>
    		</div>
    	</div>
	</div>

<!-- Slider de Marcas Destacadas -->
<?php if(!isset($_GET["page"]) || $_GET["page"] == ""): ?>
    <?php
    // Incluir funciones necesarias al inicio
    if (!function_exists('generate_featured_brands_slider')) {
        include_once __DIR__ . '/../myphp/funciones_modern.php';
    }
    if (!function_exists('getMarcas')) {
        include_once __DIR__ . '/../myphp/funciones_marca.php';
    }
    if (!function_exists('link_marca')) {
        include_once __DIR__ . '/../myphp/links.php';
    }
    if (!function_exists('get_all_listado_codigos_array')) {
        include_once __DIR__ . '/../myphp/funciones_codigo.php';
    }
    if (!function_exists('recorta_texto_pos')) {
        // Intentar primero con inc/funciones.php
        if (file_exists(__DIR__ . '/../inc/funciones.php')) {
            include_once __DIR__ . '/../inc/funciones.php';
        } elseif (file_exists(__DIR__ . '/../myphp/funciones.php')) {
            include_once __DIR__ . '/../myphp/funciones.php';
        }
    }
    
    // Generar el slider de marcas destacadas
    if (function_exists('generate_featured_brands_slider')) {
        try {
            $slider_html = generate_featured_brands_slider(6);
            if (!empty($slider_html)) {
                echo $slider_html;
            }
        } catch (Exception $e) {
            // Silenciar errores en producción, solo log
            log_error("Error en generate_featured_brands_slider: " . $e->getMessage());
        } catch (Throwable $e) {
            log_error("Error fatal en generate_featured_brands_slider: " . $e->getMessage());
        }
    }
    ?>
<?php endif; ?>

<!-- Sistema de Tabs estilo Chollometro -->
<div class="chollometro-tabs-container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
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
        </div>
    </div>
</div>

<!-- Contenido de los tabs -->
<div class="chollometro-content">
    <div class="container">
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
                                Más Recientes
                            </span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="visitas">
                            <span class="filter-label">
                                <i class="fas fa-eye"></i>
                                Más Visitados
                            </span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="votos_total">
                            <span class="filter-label">
                                <i class="fas fa-star"></i>
                                Mejor Valorados
                            </span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="votos_positivos">
                            <span class="filter-label">
                                <i class="fas fa-thumbs-up"></i>
                                Más Votos Positivos
                            </span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="beneficio">
                            <span class="filter-label">
                                <i class="fas fa-euro-sign"></i>
                                Mayor Beneficio
                            </span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="destacado">
                            <span class="filter-label">
                                <i class="fas fa-star"></i>
                                Destacados Primero
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
                        <h2><i class="fas fa-star"></i> Códigos Destacados</h2>
                        <p>Códigos promocionados y verificados</p>
                    </div>
                    <div class="codes-list-container">
                        <?php if (!empty($lista_codigos_patrocinados)): ?>
                            <?php foreach ($lista_codigos_patrocinados as $codigo): ?>
                                <?php echo generate_chollometro_code_card($codigo, true); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-codes-message">
                                <i class="fas fa-star"></i>
                                <h4>No hay códigos destacados</h4>
                                <p>Los códigos destacados aparecerán aquí</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab de Códigos Amigo -->
                <div class="tab-content" id="tab-amigos">
                    <div class="codes-list-header">
                        <h2><i class="fas fa-users"></i> Códigos Amigo</h2>
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
</div>

<!-- Sección de últimos chollos (solo en home, justo después de destacados) -->
<?php if(!isset($_GET["page"]) || $_GET["page"] == ""): ?>
    <?php
    if (!function_exists('imprimir_seccion_ultimos_chollos')) {
        include_once __DIR__ . '/../myphp/funciones_modern.php';
    }
    if (function_exists('imprimir_seccion_ultimos_chollos')) {
        imprimir_seccion_ultimos_chollos(3, 'Últimos Chollos', true);
    }
    ?>
<?php endif; ?>

<!-- Contenido SEO reubicado al final -->
<?php if(!isset($_GET["page"]) || $_GET["page"] == ""): ?>
    <div class="seo-content-section" style="margin-top: 50px;">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <?php if ($detect->isMobile()) {
                        bloque_info_home_mobile();
                    } else {
                        bloque_info_home();
                    } ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <h2 class="cd-home-title titulo_zona_home">Marcas Nuevas esta semana</h2>
                    <?php bloque_marcas_home(); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Estadísticas movidas al final para no interferir -->
<?php if(!isset($_GET["page"]) || $_GET["page"] == ""): ?>
<div class="row search-stats-section" style="margin-top: 50px;">
    <div class="container">
        <div class="col-md-12">
            <h2 class="cd-home-title titulo_zona_home">Estadísticas de Búsquedas</h2>
            <?php
            // Incluir funciones de búsqueda
            include_once __DIR__ . '/../myphp/funciones_busqueda.php';
            include_once __DIR__ . '/../myphp/funciones_modern.php';
            
            // Obtener estadísticas
            $search_stats = get_search_statistics();
            ?>
            
            <div class="search-stats-container">
                <div class="popular-searches">
                    <h3><i class="fas fa-fire"></i> Búsquedas populares</h3>
                    <p class="popular-searches-subtitle">Descubre lo que la comunidad está buscando ahora mismo.</p>
                    <?php 
                    // Obtener búsquedas populares por período
                    $popular_by_period = get_popular_searches_all_periods(8);
                    ?>
                    
                    <!-- Búsquedas de hoy -->
                    <div class="popular-period-section">
                        <h4><i class="fas fa-clock"></i> Hoy</h4>
                        <?php if (!empty($popular_by_period['today'])): ?>
                            <div class="popular-searches-list">
                                <?php foreach ($popular_by_period['today'] as $index => $term): ?>
                                    <?php if ($index >= 8) break; ?>
                                    <a class="popular-search-chip chip-today" href="/ofertas/<?php echo urlencode($term['term']); ?>?from=trends-today">
                                        <span class="chip-rank"><?php echo $index + 1; ?></span>
                                        <span class="chip-text"><?php echo htmlspecialchars($term['term']); ?></span>
                                        <span class="chip-count"><?php echo $term['count']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="popular-searches-empty">
                                <p>Aún no hay búsquedas hoy. ¡Sé el primero!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Búsquedas de esta semana -->
                    <div class="popular-period-section">
                        <h4><i class="fas fa-calendar-week"></i> Esta Semana</h4>
                        <?php if (!empty($popular_by_period['week'])): ?>
                            <div class="popular-searches-list">
                                <?php foreach ($popular_by_period['week'] as $index => $term): ?>
                                    <?php if ($index >= 8) break; ?>
                                    <a class="popular-search-chip chip-week" href="/ofertas/<?php echo urlencode($term['term']); ?>?from=trends-week">
                                        <span class="chip-rank"><?php echo $index + 1; ?></span>
                                        <span class="chip-text"><?php echo htmlspecialchars($term['term']); ?></span>
                                        <span class="chip-count"><?php echo $term['count']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="popular-searches-empty">
                                <p>Aún no hay búsquedas esta semana.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Búsquedas de este mes -->
                    <div class="popular-period-section">
                        <h4><i class="fas fa-calendar-alt"></i> Este Mes</h4>
                        <?php if (!empty($popular_by_period['month'])): ?>
                            <div class="popular-searches-list">
                                <?php foreach ($popular_by_period['month'] as $index => $term): ?>
                                    <?php if ($index >= 8) break; ?>
                                    <a class="popular-search-chip chip-month" href="/ofertas/<?php echo urlencode($term['term']); ?>?from=trends-month">
                                        <span class="chip-rank"><?php echo $index + 1; ?></span>
                                        <span class="chip-text"><?php echo htmlspecialchars($term['term']); ?></span>
                                        <span class="chip-count"><?php echo $term['count']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="popular-searches-empty">
                                <p>Aún no hay búsquedas este mes.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen general -->
                <div class="stats-summary">
                    <div class="row">
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($search_stats['total_today']); ?></div>
                                    <div class="stat-label">Búsquedas Hoy</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($search_stats['total_this_week']); ?></div>
                                    <div class="stat-label">Esta Semana</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($search_stats['total_this_month']); ?></div>
                                    <div class="stat-label">Este Mes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos de estadísticas -->
                <div class="stats-charts">
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <div class="chart-container">
                                <h3>Búsquedas Diarias (7 días)</h3>
                                <canvas id="dailyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="chart-container">
                                <h3>Búsquedas Semanales (4 semanas)</h3>
                                <canvas id="weeklyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="chart-container">
                                <h3>Búsquedas Mensuales (6 meses)</h3>
                                <canvas id="monthlyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Términos más buscados -->
                <div class="top-terms">
                    <h3>Términos Más Buscados (30 días)</h3>
                    <div class="terms-list">
                        <?php if (!empty($search_stats['top_terms'])): ?>
                            <?php foreach ($search_stats['top_terms'] as $index => $term): ?>
                                <div class="term-item">
                                    <span class="term-rank"><?php echo $index + 1; ?></span>
                                    <span class="term-text"><?php echo htmlspecialchars($term['_id']); ?></span>
                                    <span class="term-count"><?php echo $term['count']; ?> búsquedas</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No hay datos de búsquedas disponibles</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Paginación para códigos -->
<?php if(isset($_REQUEST["page"]) && $_REQUEST["page"]): ?>
<div class="container ultimos_container">
	<div class="row">
		<div class="col-md-12">
			<div class="bloque_publica_nuevo_codigo">
				<div class="col-md-12 text-center">
					<p class="titulo_zona_home">Mostrando del <?php echo isset($num_inicio) ? $num_inicio : 1; ?> al <?php echo isset($num_fin) ? $num_fin : 20; ?> de un total de <b style="display:block;font-size:20px;"><?php echo isset($numero_codigos_format) ? $numero_codigos_format : 0; ?> Códigos Amigo</b></p>
				</div>
			</div>
			
			<div class="listado_codigos">
				<?php
				// Inicializar variable si no está definida
				if (!isset($lista_codigos)) {
					$lista_codigos = array();
				}
				block_listado_codigos($lista_codigos, "home");
				?>
			</div>
			
			<?php
			// Inicializar variable si no está definida
			if (!isset($codigos_restantes)) {
				$codigos_restantes = 0;
			}
			show_buttons_paginate($numero_codigos, $codigos_restantes);
			?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if(false){?>
<div class="row">
	<div class="<?php if($_REQUEST["codigo"]){ echo "col-md-12"; }else{ echo "col-md-12"; } ?>">

	<div class="cd-home-title titulo_zona_home">Quizá te interese</div>
    <div class="col-md-12" style="max-width:800px !important;color:white !important;">

        <p style="text-color:white !important;">
        	<div class=" titulo_zona_home">Casinuevo</div>
        	<p>Encuentra auténticas gangas cerca de tí en tiempo record. Compra y vende
        	<a class="underline" href="https://www.casinuevo.com/coches-de-segunda-mano/" title="coches de segunda mano" target="_blank">coches de segunda mano</a> y otros productos.
        </p>
	</div>
	</div>
</div>
<?php } ?>

</div>

<!-- CSS para estadísticas de búsquedas -->
<style>
.search-stats-section {
    background: #f8f9fa;
    padding: 40px 0;
    margin: 30px 0;
}

.search-stats-container {
    background: #ffffff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.popular-searches {
    margin-bottom: 40px;
    text-align: left;
}

.popular-searches h4 {
    font-size: 1.4rem;
    color: #333;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.popular-searches-subtitle {
    color: #666;
    margin-bottom: 30px;
}

.popular-period-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.popular-period-section h5 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.popular-searches-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.popular-search-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-radius: 999px;
    color: #ffffff;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
    border: 2px solid transparent;
}

/* Colores por período */
.popular-search-chip.chip-today {
    background: linear-gradient(135deg, #E30613, #f7931e);
    border-color: #E30613;
}

.popular-search-chip.chip-today:hover {
    background: linear-gradient(135deg, #C40510, #d67d15);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(227, 6, 19, 0.3);
}

.popular-search-chip.chip-week {
    background: linear-gradient(135deg, #4a90e2, #357abd);
    border-color: #4a90e2;
}

.popular-search-chip.chip-week:hover {
    background: linear-gradient(135deg, #357abd, #2868a8);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(74, 144, 226, 0.3);
}

.popular-search-chip.chip-month {
    background: linear-gradient(135deg, #7b68ee, #6a5acd);
    border-color: #7b68ee;
}

.popular-search-chip.chip-month:hover {
    background: linear-gradient(135deg, #6a5acd, #5a4ab8);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(123, 104, 238, 0.3);
}

.popular-search-chip .chip-rank {
    background: rgba(255, 255, 255, 0.25);
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    min-width: 28px;
    text-align: center;
}

.popular-search-chip .chip-text {
    white-space: nowrap;
}

.popular-search-chip .chip-count {
    background: rgba(255, 255, 255, 0.25);
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    min-width: 28px;
    text-align: center;
}

.popular-searches-empty {
    background: #f0f4ff;
    border-radius: 12px;
    padding: 18px;
    color: #1f3c88;
    font-weight: 500;
}

.popular-searches-empty a {
    color: #1f3c88;
    text-decoration: underline;
    font-weight: 600;
}

.stats-summary {
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #E30613, #C40510);
    color: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.stat-card .stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    font-size: 1rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stats-charts {
    margin-bottom: 40px;
}

.chart-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

.chart-container h4 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

.chart-container canvas {
    max-height: 200px;
}

.top-terms {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
}

.top-terms h4 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
}

.terms-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.term-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.term-item:hover {
    transform: translateX(5px);
}

.term-rank {
    background: #E30613;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.term-text {
    flex: 1;
    font-weight: 500;
    color: #333;
}

.term-count {
    color: #666;
    font-size: 0.9rem;
    margin-left: 10px;
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .search-stats-container {
        padding: 20px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-card .stat-number {
        font-size: 2rem;
    }
    
    .terms-list {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        padding: 15px;
    }
    
    .popular-period-section {
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .popular-period-section h5 {
        font-size: 1rem;
    }
    
    .popular-searches-list {
        gap: 8px;
    }
    
    .popular-search-chip {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .search-stats-section {
        padding: 20px 0;
    }
    
    .search-stats-container {
        padding: 15px;
    }
    
    .stat-card .stat-icon {
        font-size: 2rem;
    }
    
    .stat-card .stat-number {
        font-size: 1.8rem;
    }
    
    .term-item {
        padding: 12px;
    }
    
    .term-rank {
        width: 25px;
        height: 25px;
        font-size: 0.9rem;
    }
    
    .popular-period-section {
        padding: 12px;
    }
    
    .popular-period-section h5 {
        font-size: 0.95rem;
    }
    
    .popular-search-chip {
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    
    .popular-search-chip .chip-rank,
    .popular-search-chip .chip-count {
        padding: 3px 8px;
        font-size: 0.75rem;
        min-width: 24px;
    }
}
</style>

<?php if(!isset($_GET["page"]) || $_GET["page"] == ""): ?>
<section class="container mt-5 mb-4" id="que-es-codigo-amigo">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="h4 mb-3">¿Qué es un código amigo?</h2>
            <p>Un <strong>código amigo</strong> (también llamado código de invitación, código referido o código promocional) es un código único que un usuario registrado comparte con otra persona para que esta obtenga un descuento, crédito o beneficio al registrarse en una app o servicio.</p>
            <p>Cuando alguien se registra usando un <strong>código amigo</strong>, normalmente ambos reciben una recompensa: el nuevo usuario consigue un descuento o bono de bienvenida, y quien compartió el código gana créditos, cashback o minutos gratuitos.</p>
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <h3 class="h6">¿Cómo funciona un código amigo?</h3>
                    <p class="small">El nuevo usuario introduce el código durante el registro o en su primera compra. El sistema valida el código y aplica el beneficio automáticamente.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h3 class="h6">¿Dónde usar los códigos amigo?</h3>
                    <p class="small">Bancos digitales, apps de movilidad, plataformas de inversión, supermercados online, seguros, telecomunicaciones y muchos más sectores ofrecen este tipo de descuentos.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h3 class="h6">¿Son seguros los códigos de descuento?</h3>
                    <p class="small">En CodigoAmigo.com todos los códigos son verificados por la comunidad. Solo publicamos códigos activos y comprobados por usuarios reales.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php

get_footer(); ?>

<!-- JavaScript para gráficos de estadísticas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos de estadísticas desde PHP
    const searchStats = <?php echo json_encode($search_stats); ?>;
    
    // Configuración común para los gráficos
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    };
    
    // Gráfico diario
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: searchStats.daily.map(day => day.formatted_date),
            datasets: [{
                label: 'Búsquedas',
                data: searchStats.daily.map(day => day.count),
                borderColor: '#E30613',
                backgroundColor: 'rgba(227, 6, 19, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: chartOptions
    });
    
    // Gráfico semanal
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: searchStats.weekly.map(week => week.period),
            datasets: [{
                label: 'Búsquedas',
                data: searchStats.weekly.map(week => week.count),
                backgroundColor: '#E30613',
                borderColor: '#C40510',
                borderWidth: 1
            }]
        },
        options: chartOptions
    });
    
    // Gráfico mensual
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'doughnut',
        data: {
            labels: searchStats.monthly.map(month => month.formatted_month),
            datasets: [{
                data: searchStats.monthly.map(month => month.count),
                backgroundColor: [
                    '#E30613',
                    '#C40510',
                    '#d44a1f',
                    '#c23a13',
                    '#b02a07',
                    '#9e1a00'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
});

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
    const mobileFilterOptions = document.querySelectorAll('input[name="mobile_sort"]');
    
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
                    
                case 'votos_total':
                    // Calcular total de votos (positivos - negativos)
                    const aPos = parseInt(a.querySelector('.stat-item:nth-child(2) span')?.textContent || 0);
                    const aNeg = parseInt(a.querySelector('.stat-item:nth-child(3) span')?.textContent || 0);
                    const bPos = parseInt(b.querySelector('.stat-item:nth-child(2) span')?.textContent || 0);
                    const bNeg = parseInt(b.querySelector('.stat-item:nth-child(3) span')?.textContent || 0);
                    aValue = aPos - aNeg;
                    bValue = bPos - bNeg;
                    return bValue - aValue; // Mejor valorados primero
                    
                case 'beneficio':
                    // Buscar elemento de beneficio
                    const aBenefit = a.querySelector('.beneficio-cantidad, .benefit-amount, [data-beneficio]');
                    const bBenefit = b.querySelector('.beneficio-cantidad, .benefit-amount, [data-beneficio]');
                    aValue = aBenefit ? parseFloat(aBenefit.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0 : 0;
                    bValue = bBenefit ? parseFloat(bBenefit.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0 : 0;
                    return bValue - aValue; // Mayor beneficio primero
                    
                case 'destacado':
                    // Los destacados primero
                    const aDestacado = a.querySelector('.featured-badge, .destacado-badge, [data-destacado="1"]') ? 1 : 0;
                    const bDestacado = b.querySelector('.featured-badge, .destacado-badge, [data-destacado="1"]') ? 1 : 0;
                    return bDestacado - aDestacado; // Destacados primero
                    
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
    
    // Event listeners para filtros desktop
    filterOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                applySorting(this.value);
            }
        });
    });
    
    // Event listeners para filtros móviles
    mobileFilterOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                applySorting(this.value);
            }
        });
    });
    
    // Manejo de filtros móviles
    const mobileFilterToggle = document.querySelector('.mobile-filter-toggle');
    const mobileFilterContent = document.querySelector('.mobile-filter-content');
    
    if (mobileFilterToggle && mobileFilterContent) {
        mobileFilterToggle.addEventListener('click', function() {
            mobileFilterContent.classList.toggle('active');
        });
    }
    
    // Manejo de botones "Ver Código"
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-view-code') || e.target.closest('.btn-view-code')) {
            const button = e.target.classList.contains('btn-view-code') ? e.target : e.target.closest('.btn-view-code');
            const codeId = button.getAttribute('data-code-id');
            
            if (codeId) {
                // Aquí puedes implementar la lógica para mostrar el código
                // Por ejemplo, hacer una petición AJAX o mostrar un modal
                console.log('Ver código:', codeId);
                
                // Ejemplo de implementación básica
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
</script>