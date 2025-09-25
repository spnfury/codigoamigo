<?php

// Inicializar variables meta si no están definidas
if (!isset($title)) $title = 'Códigos Descuento - CodigoAmigo.com';
if (!isset($description)) $description = 'Los mejores códigos descuento y cupones de las principales marcas. Ahorra dinero con CodigoAmigo.com';
if (!isset($title_social)) $title_social = $title;
if (!isset($description_social)) $description_social = $description;
if (!isset($imagen_social)) $imagen_social = 'https://www.codigoamigo.com/images/logo.png';
if (!isset($links_meta)) $links_meta = '';

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

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
<?php

if(!isset($_GET["page"]) || $_GET["page"] == ""){
    if ($detect->isMobile()) {
        bloque_info_home_mobile();
    }else{
        bloque_info_home();
    }
}

if(!isset($_REQUEST["page"]) || !$_REQUEST["page"]){

?>

<div class="row empieza_home">
    <div class="container ">
        	<div class="col-md-12 columns small-12 slider">

                <div class="title">
           			<h2>Destacados</h2>
           		</div>

				<div class="destacado_div">
					<div class="listado_codigos">
						<?php 
						// Inicializar variable si no está definida
						if (!isset($lista_codigos_patrocinados)) {
							$lista_codigos_patrocinados = array();
						}
						block_listado_codigos($lista_codigos_patrocinados, "destacados");
						?>
					</div>
				</div>
            </div>
    </div>
</div>

<div class="row">
	<div class="container">
        <?
        if(!isset($_GET["page"]) || $_GET["page"] == ""){ ?>

        <div class="col-md-12">
        		    <div class="cd-home-title titulo_zona_home">Marcas Nuevas esta semana</div>
                	<?php bloque_marcas_home(); ?>
                	<?php /*?><div class="hidden-xs"><?php tradedoubler("bookingcom_120x600v1"); ?></div><?*/ ?>
        		</div>

        <?php } ?>
    </div>
</div>

<!-- Sección de Estadísticas de Búsquedas -->
<div class="row search-stats-section">
    <div class="container">
        <div class="col-md-12">
            <div class="cd-home-title titulo_zona_home">Estadísticas de Búsquedas</div>
            <?php
            // Incluir funciones de búsqueda
            include_once __DIR__ . '/../myphp/funciones_busqueda.php';
            
            // Obtener estadísticas
            $search_stats = get_search_statistics();
            ?>
            
            <div class="search-stats-container">
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
                                <h4>Búsquedas Diarias (7 días)</h4>
                                <canvas id="dailyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="chart-container">
                                <h4>Búsquedas Semanales (4 semanas)</h4>
                                <canvas id="weeklyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="chart-container">
                                <h4>Búsquedas Mensuales (6 meses)</h4>
                                <canvas id="monthlyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Términos más buscados -->
                <div class="top-terms">
                    <h4>Términos Más Buscados (30 días)</h4>
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

<div class="row">

	<div class="container">

    <?php



    //include_once '../loaders/main_entremedio.php'; // COMENTADO POR RESTRICCIÓN DE open_basedir



    ?>
	</div>

</div>
 <?php  } ?>
<div class="container ultimos_container">
	<div class="row">
		<div class="<?php if(isset($_REQUEST["codigo"]) && $_REQUEST["codigo"]){ echo "col-md-12"; }else{ echo "col-md-12 div_entro_codigos"; } ?>">
     		<div class="cd-home-title titulo_zona_home">Últimos códigos publicados</div>
			<?php /*if($_GET["page"] != "") {*/ ?>
				<div class="bloque_publica_nuevo_codigo">
					<div class="col-md-12 text-center">
						<p class="titulo_zona_home">Mostrando del <?php echo isset($num_inicio) ? $num_inicio : 1; ?> al <?php echo isset($num_fin) ? $num_fin : 20; ?> de un total de <b style="display:block;font-size:20px;"><?php echo isset($numero_codigos_format) ? $numero_codigos_format : 0; ?> Códigos Amigo</b></p>
					</div>
				</div>

			<?php /*}*/ ?>

			<?php
			// Mostrar filtros móviles (se ocultan con CSS en desktop)
			echo generate_chollometro_filter_menu('home');
			?>

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
            <?php /*?><?php tradedoubler("bnext_728x90junio2018"); ?><? */ ?>

		</div>
	</div>
</div>

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

.stats-summary {
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #ff6b35, #e55a2b);
    color: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
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
    background: #ff6b35;
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
}
</style>

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
                borderColor: '#ff6b35',
                backgroundColor: 'rgba(255, 107, 53, 0.1)',
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
                backgroundColor: '#ff6b35',
                borderColor: '#e55a2b',
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
                    '#ff6b35',
                    '#e55a2b',
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
</script>