<?php
/**
 * Página de marca con diseño idéntico al home
 * Basada en main.php pero mostrando solo información de la marca específica
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones_modern.php';

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

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

// Inicializar variable si no está definida
if (!isset($numero_codigos)) $numero_codigos = 0;
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

// Obtener información de la marca
$marca_info = get_brand_info($marca);
$nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
$imagen_marca = $marca_info['imagen'] ?? '';
$descripcion_marca = $marca_info['descripcion'] ?? '';

?>

<div class="container-fluid main_entremedio">
    <div class="container text-center bloque_titulo_home">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <h1>Códigos Amigos y Códigos Descuento de <?php echo $nombre_marca; ?></h1>
                    <p>Comparte todos tus códigos de <?php echo $nombre_marca; ?> y gana dinero</p><br>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Mostrar información de la marca (similar al bloque_info_home pero específico de la marca)
    if ($detect->isMobile()) {
        bloque_info_marca_mobile($marca, $marca_info);
    } else {
        bloque_info_marca($marca, $marca_info);
    }
    ?>

    <?php if(!isset($_REQUEST["page"]) || !$_REQUEST["page"]){ ?>

    <!-- Sección de códigos destacados de la marca -->
    <div class="row empieza_home">
        <div class="container">
            <div class="col-md-12 columns small-12 slider">
                <div class="title">
                    <h2>¡Destacados home!</h2>
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
                        
                        block_listado_codigos($codigos_destacados, "destacados");
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
                    bloque_marcas_home($marca_info['categoria'] ?? '', $marca, 12);
                }
                ?>
            </div>
        </div>
    </div>

    <?php } ?>

    <!-- Sección de últimos códigos publicados -->
    <div class="container ultimos_container">
        <div class="row">
            <div class="<?php if(isset($_REQUEST["codigo"]) && $_REQUEST["codigo"]){ echo "col-md-12"; }else{ echo "col-md-12 div_entro_codigos"; } ?>">
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
                        $fp_a = isset($a['fecha_publicacion']) ? $a['fecha_publicacion'] : null;
                        $fp_b = isset($b['fecha_publicacion']) ? $b['fecha_publicacion'] : null;
                        $fecha_a = ($fp_a instanceof \MongoDB\BSON\UTCDateTime) ? $fp_a->toDateTime()->getTimestamp() : (is_string($fp_a) ? strtotime($fp_a) : 0);
                        $fecha_b = ($fp_b instanceof \MongoDB\BSON\UTCDateTime) ? $fp_b->toDateTime()->getTimestamp() : (is_string($fp_b) ? strtotime($fp_b) : 0);
                        return $fecha_b - $fecha_a;
                    });
                    
                    block_listado_codigos($codigos, "home");
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

</div>

<!-- CSS adicional para la página de marca -->
<link rel="stylesheet" href="/css/chollometro-filters.css">

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