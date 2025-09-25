<?php
/**
 * Página de detalle de marca con diseño moderno
 * Muestra información completa de una marca específica con sus códigos
 */

// Inicializar detector de móviles si no está definido
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}

// Inicializar variables meta si no están definidas
if (!isset($title)) $title = 'Códigos Descuento ' . ucfirst($marca) . ' - CodigoAmigo.com';
if (!isset($description)) $description = 'Los mejores códigos descuento y cupones de ' . ucfirst($marca) . '. Ahorra dinero con CodigoAmigo.com';
if (!isset($title_social)) $title_social = $title;
if (!isset($description_social)) $description_social = $description;
if (!isset($imagen_social)) $imagen_social = 'https://www.codigoamigo.com/images/logo.png';
if (!isset($links_meta)) $links_meta = '';

// No incluir header aquí ya que se incluye en la ruta principal

// Inicializar variables globales necesarias para block_listado_codigos
global $detect_device, $url_usuario_sin_imagen, $tipo_block_codigos, $data_usuario, $provincia, $marca, $num_codigos_global, $keywords, $actual_link;

if (!isset($detect_device)) $detect_device = $detect;
if (!isset($url_usuario_sin_imagen)) $url_usuario_sin_imagen = 'https://www.codigoamigo.com/img/usuario_sin_imagen.png';
if (!isset($tipo_block_codigos)) $tipo_block_codigos = 'home';
if (!isset($data_usuario)) $data_usuario = array();
if (!isset($provincia)) $provincia = '';
if (!isset($num_codigos_global)) $num_codigos_global = $numero_codigos;
if (!isset($keywords)) $keywords = '';
if (!isset($actual_link)) $actual_link = 'https://www.codigoamigo.com/de-' . $marca;

// Obtener información de la marca
$marca_info = get_brand_info($marca);
$nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
$imagen_marca = $marca_info['imagen'] ?? '';
$descripcion_marca = $marca_info['descripcion'] ?? '';
$descripcion_larga = $marca_info['descripción_larga'] ?? '';
$categoria_marca = $marca_info['categoria'] ?? '';

// Inicializar variable si no está definida
if (!isset($numero_codigos)) $numero_codigos = 0;
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

?>

<div class="container-fluid main_entremedio">
    
    <!-- Hero Section con Logo de la Marca -->
    <div class="container text-center bloque_titulo_home">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <?php if($imagen_marca): ?>
                        <div class="marca-logo-hero">
                            <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="<?php echo htmlspecialchars($nombre_marca); ?>" class="logo-marca-principal">
                        </div>
                    <?php endif; ?>
                    <h1>Códigos Descuento <?php echo $nombre_marca; ?></h1>
                    <p class="hero-subtitle">Los mejores códigos de descuento y cupones de <?php echo $nombre_marca; ?> verificados y actualizados diariamente</p>
                    <div class="hero-stats">
                        <span class="stat-item"><?php echo $numero_codigos_format; ?> códigos disponibles</span>
                        <span class="stat-divider">•</span>
                        <span class="stat-item">Actualizados diariamente</span>
                        <span class="stat-divider">•</span>
                        <span class="stat-item">100% gratuitos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección ¿Qué es? -->
    <div class="row que-es-section">
        <div class="container">
            <div class="col-md-12">
                <div class="que-es-card">
                    <h2>¿Qué es <?php echo $nombre_marca; ?>?</h2>
                    <?php if($descripcion_marca): ?>
                        <p class="que-es-description"><?php echo htmlspecialchars($descripcion_marca); ?></p>
                    <?php endif; ?>
                    
                    <?php if($categoria_marca): ?>
                        <div class="categoria-info">
                            <span class="categoria-label">Categoría:</span>
                            <span class="categoria-value"><?php echo htmlspecialchars(ucfirst($categoria_marca)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="que-es-benefits">
                        <div class="benefit-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Códigos verificados</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-clock"></i>
                            <span>Actualizados diariamente</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-gift"></i>
                            <span>100% gratuitos</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de códigos destacados -->
    <div class="row empieza_home">
        <div class="container">
            <div class="col-md-12 columns small-12 slider">
                <div class="title">
                    <h2>¡Destacados!</h2>
                </div>

                <div class="destacado_div">
                    <div class="listado_codigos">
                        <?php 
                        // Filtrar códigos destacados de la marca
                        $codigos_destacados = array_filter($codigos, function($codigo) {
                            return isset($codigo['destacado']) && $codigo['destacado'] > 0;
                        });
                        
                        // Ordenar por fecha de destacado (más reciente primero)
                        usort($codigos_destacados, function($a, $b) {
                            $fecha_a = isset($a['destacado']) ? $a['destacado'] : 0;
                            $fecha_b = isset($b['destacado']) ? $b['destacado'] : 0;
                            return $fecha_b - $fecha_a;
                        });
                        
                        // Tomar solo los primeros 6 códigos destacados
                        $codigos_destacados = array_slice($codigos_destacados, 0, 6);
                        
                        if (!empty($codigos_destacados)) {
                            block_listado_codigos($codigos_destacados, "destacados");
                        } else {
                            echo '<div class="no-codes-message">No hay códigos destacados disponibles para ' . $nombre_marca . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de marcas relacionadas -->
    <div class="row">
        <div class="container">
            <div class="col-md-12">
                <div class="cd-home-title titulo_zona_home">Marcas Populares</div>
                <div class="cd-home-subtitle">Descubre las marcas con más códigos de descuento</div>
                <?php 
                // Obtener marcas relacionadas de la misma categoría
                $marcas_relacionadas = get_related_brands($marca, 12);
                if (!empty($marcas_relacionadas)) {
                    bloque_marcas_home($categoria_marca, $marca, 12);
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Sección de últimos códigos publicados -->
    <div class="container ultimos_container">
        <div class="row">
            <div class="col-md-12 div_entro_codigos">
                <div class="cd-home-title titulo_zona_home">Últimos Códigos Publicados</div>
                <div class="bloque_publica_nuevo_codigo">
                    <div class="col-md-12 text-center">
                        <p class="titulo_zona_home">Mostrando del <?php echo isset($num_inicio) ? $num_inicio : 1; ?> al <?php echo isset($num_fin) ? $num_fin : 20; ?> de un total de <b style="display:block;font-size:20px;"><?php echo $numero_codigos_format; ?> Códigos Amigo de <?php echo $nombre_marca; ?></b></p>
                    </div>
                </div>

                <?php
                // Mostrar filtros móviles (se ocultan con CSS en desktop)
                echo generate_chollometro_filter_menu('home');
                ?>

                <div class="listado_codigos">
                    <?php
                    // Ordenar códigos por fecha de publicación (más reciente primero)
                    usort($codigos, function($a, $b) {
                        $fecha_a = isset($a['fecha_publicacion']) ? strtotime($a['fecha_publicacion']) : 0;
                        $fecha_b = isset($b['fecha_publicacion']) ? strtotime($b['fecha_publicacion']) : 0;
                        return $fecha_b - $fecha_a;
                    });
                    
                    if (!empty($codigos)) {
                        block_listado_codigos($codigos, "home");
                    } else {
                        echo '<div class="no-codes-message">No hay códigos disponibles para ' . $nombre_marca . '</div>';
                    }
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

    <!-- Sección de texto SEO largo -->
    <div class="row seo-content-section">
        <div class="container">
            <div class="col-md-12">
                <div class="seo-content-card">
                    <h2>Códigos de Descuento <?php echo $nombre_marca; ?> - Guía Completa</h2>
                    
                    <?php if($descripcion_larga): ?>
                        <div class="seo-long-text">
                            <?php echo nl2br(htmlspecialchars($descripcion_larga)); ?>
                        </div>
                    <?php else: ?>
                        <div class="seo-long-text">
                            <p>En CodigoAmigo.com encontrarás los mejores códigos de descuento de <?php echo $nombre_marca; ?> para que puedas ahorrar en tus compras. Nuestros códigos son verificados diariamente y están actualizados para garantizar que funcionen correctamente.</p>
                            
                            <h3>¿Por qué elegir nuestros códigos de <?php echo $nombre_marca; ?>?</h3>
                            <ul>
                                <li><strong>Verificación diaria:</strong> Todos nuestros códigos son probados regularmente para asegurar su validez</li>
                                <li><strong>Actualizaciones constantes:</strong> Añadimos nuevos códigos de descuento cada día</li>
                                <li><strong>100% gratuitos:</strong> No cobramos por el uso de nuestros códigos</li>
                                <li><strong>Fácil de usar:</strong> Solo copia y pega el código en el proceso de compra</li>
                            </ul>
                            
                            <h3>Cómo usar los códigos de descuento de <?php echo $nombre_marca; ?></h3>
                            <ol>
                                <li>Selecciona el código de descuento que más te interese</li>
                                <li>Haz clic en "Ver Código" para revelar el código</li>
                                <li>Copia el código mostrado</li>
                                <li>Ve a la web de <?php echo $nombre_marca; ?> y añade productos a tu carrito</li>
                                <li>En el proceso de compra, busca el campo "Código de descuento" o "Cupón"</li>
                                <li>Pega el código y verifica que el descuento se aplique</li>
                                <li>¡Disfruta de tu ahorro!</li>
                            </ol>
                            
                            <h3>Consejos para maximizar tus ahorros con <?php echo $nombre_marca; ?></h3>
                            <p>Para obtener el máximo beneficio de nuestros códigos de descuento de <?php echo $nombre_marca; ?>, te recomendamos:</p>
                            <ul>
                                <li>Revisar regularmente nuestra página para encontrar nuevos códigos</li>
                                <li>Combinar códigos con ofertas especiales de la marca</li>
                                <li>Estar atento a las fechas de vencimiento de los códigos</li>
                                <li>Compartir códigos con amigos y familiares para que también ahorren</li>
                            </ul>
                            
                            <p>En CodigoAmigo.com nos enorgullece ofrecer la mejor selección de códigos de descuento de <?php echo $nombre_marca; ?> y otras marcas populares. Nuestro objetivo es ayudarte a ahorrar dinero en tus compras favoritas de manera fácil y segura.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- CSS específico para la página de marca -->
<link rel="stylesheet" href="/css/chollometro-filters.css">
<style>
/* Estilos para la página de marca */
.marca-logo-hero {
    margin-bottom: 30px;
}

.logo-marca-principal {
    max-width: 200px;
    max-height: 200px;
    object-fit: contain;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    background: white;
    padding: 20px;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #666;
    margin: 20px 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}

.stat-item {
    background: rgba(255, 107, 53, 0.1);
    color: #ff6b35;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.stat-divider {
    color: #ccc;
    font-weight: bold;
}

/* Sección ¿Qué es? */
.que-es-section {
    background: #f8f9fa;
    padding: 60px 0;
    margin: 40px 0;
}

.que-es-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    text-align: center;
}

.que-es-card h2 {
    color: #333;
    font-size: 2.5rem;
    margin-bottom: 20px;
    font-weight: 700;
}

.que-es-description {
    font-size: 1.2rem;
    color: #666;
    line-height: 1.8;
    margin-bottom: 30px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.categoria-info {
    background: #ff6b35;
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    display: inline-block;
    margin-bottom: 30px;
}

.categoria-label {
    font-weight: 600;
    margin-right: 10px;
}

.categoria-value {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.que-es-benefits {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    margin-top: 30px;
}

.benefit-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #333;
}

.benefit-item i {
    font-size: 2rem;
    color: #ff6b35;
}

.benefit-item span {
    font-weight: 600;
    font-size: 0.9rem;
}

/* Sección SEO */
.seo-content-section {
    background: #f8f9fa;
    padding: 60px 0;
    margin: 40px 0;
}

.seo-content-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.seo-content-card h2 {
    color: #333;
    font-size: 2.2rem;
    margin-bottom: 30px;
    font-weight: 700;
    text-align: center;
}

.seo-long-text {
    color: #555;
    line-height: 1.8;
    font-size: 1.1rem;
}

.seo-long-text h3 {
    color: #333;
    font-size: 1.5rem;
    margin: 30px 0 15px 0;
    font-weight: 600;
}

.seo-long-text ul, .seo-long-text ol {
    margin: 20px 0;
    padding-left: 30px;
}

.seo-long-text li {
    margin: 10px 0;
}

.seo-long-text strong {
    color: #ff6b35;
    font-weight: 700;
}

/* Mensaje cuando no hay códigos */
.no-codes-message {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2rem;
    background: #f8f9fa;
    border-radius: 15px;
    margin: 20px 0;
}

/* Responsive */
@media (max-width: 768px) {
    .logo-marca-principal {
        max-width: 150px;
        max-height: 150px;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .stat-divider {
        display: none;
    }
    
    .que-es-card, .seo-content-card {
        padding: 30px 20px;
    }
    
    .que-es-card h2, .seo-content-card h2 {
        font-size: 2rem;
    }
    
    .que-es-benefits {
        gap: 30px;
    }
    
    .benefit-item i {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .logo-marca-principal {
        max-width: 120px;
        max-height: 120px;
    }
    
    .que-es-card h2, .seo-content-card h2 {
        font-size: 1.8rem;
    }
    
    .que-es-description {
        font-size: 1.1rem;
    }
    
    .seo-long-text {
        font-size: 1rem;
    }
}
</style>

<?php
get_footer(); ?>

<!-- JavaScript para filtros -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para panel de filtros "Más"
    const filterMoreBtn = document.getElementById('filter-more-btn');
    const filterPanel = document.getElementById('filter-panel');
    
    if (filterMoreBtn && filterPanel) {
        filterMoreBtn.addEventListener('click', function() {
            filterPanel.classList.toggle('show');
            searchPanel.classList.remove('show');
        });
    }
    
    // Toggle para panel de búsqueda
    const filterSearchBtn = document.getElementById('filter-search-btn');
    const searchPanel = document.getElementById('search-panel');
    
    if (filterSearchBtn && searchPanel) {
        filterSearchBtn.addEventListener('click', function() {
            searchPanel.classList.toggle('show');
            filterPanel.classList.remove('show');
        });
    }
    
    // Cambiar tipo de filtro
    const filterTypeBtns = document.querySelectorAll('.filter-type-btn');
    filterTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover clase active de todos los botones
            filterTypeBtns.forEach(b => b.classList.remove('active'));
            // Agregar clase active al botón clickeado
            this.classList.add('active');
        });
    });
    
    // Cerrar paneles al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.chollometro-filter-container')) {
            if (filterPanel) filterPanel.classList.remove('show');
            if (searchPanel) searchPanel.classList.remove('show');
        }
    });
});

// Funciones globales para los botones
function applyFilters() {
    const tipo = document.querySelector('.filter-type-btn.active')?.dataset.type || 'todos';
    const fecha = document.querySelector('input[name="fecha"]:checked')?.value || 'hoy';
    
    const url = new URL(window.location);
    url.searchParams.set('tipo', tipo);
    url.searchParams.set('fecha', fecha);
    
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('tipo');
    url.searchParams.delete('fecha');
    url.searchParams.delete('q');
    
    window.location.href = url.toString();
}

function performSearch(event) {
    event.preventDefault();
    const query = document.querySelector('.search-input').value;
    
    if (query.trim()) {
        const url = new URL(window.location);
        url.searchParams.set('q', query.trim());
        window.location.href = url.toString();
    }
}
</script>
