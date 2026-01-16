<?php
// Página de detalle individual de chollo
// Asegurar que las funciones de votos estén disponibles
if (!function_exists('obtenerVotoUsuario')) {
    include_once __DIR__ . '/../myphp/funciones_chollos_votos.php';
}
if (!function_exists('renderHotDealsWidget')) {
    include_once __DIR__ . '/../myphp/funciones_chollos.php';
}
if (!function_exists('extraerASIN')) {
    include_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';
}
if (!function_exists('es_favorito')) {
    include_once __DIR__ . '/../myphp/funciones_usuario.php';
}

// Inicializar estado de favorito
$is_fav = false;
if (isset($chollo) && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $is_fav = es_favorito($_SESSION['user_id'], $chollo['id']);
}

// Generar contenido enriquecido
require_once __DIR__ . '/../myphp/services/CholloContentGenerator.php';
$content_generator = new CholloContentGenerator();
$rich_content = isset($chollo) ? $content_generator->generateFullContent($chollo) : null;
?>


<style>


.breadcrumb {
    margin-bottom: 25px;
    padding: 0;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    color: #666;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.breadcrumb a:hover {
    color: #E30613;
}

.breadcrumb span {
    color: #333;
    font-weight: 500;
}

.breadcrumb-separator {
    color: #ccc;
    margin: 0 4px;
}

.chollo-detail {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.06);
    margin-bottom: 40px;
    border: 1px solid #f0f0f0;
}

.chollo-detail-header {
    display: grid;
    grid-template-columns: 460px 1fr;
    gap: 40px;
    padding: 40px;
}

.chollo-detail-image-wrapper {
    position: relative;
    width: 100%;
}

.chollo-detail-image {
    width: 100%;
    aspect-ratio: 1/1;
    height: auto;
    overflow: hidden;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chollo-detail-image img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: transform 0.5s ease;
}

.chollo-detail-image-wrapper:hover img {
    transform: scale(1.03);
}

.chollo-detail-info {
    display: flex;
    flex-direction: column;
}

.chollo-detail-title {
    font-size: 2.2em;
    font-weight: 800;
    color: #1a1a1a;
    margin-bottom: 20px;
    line-height: 1.25;
    letter-spacing: -0.5px;
}

.chollo-detail-description {
    font-size: 1.05em;
    color: #4a4a4a;
    line-height: 1.7;
    margin-bottom: 30px;
    flex-grow: 1;
}

.chollo-detail-prices {
    display: flex;
    align-items: baseline;
    gap: 15px;
    margin-bottom: 30px;
    padding: 20px;
    background: #fdf2f2;
    border-radius: 12px;
    border: 1px dashed #ffcdd2;
}

.chollo-detail-price-discount {
    font-size: 3em;
    font-weight: 900;
    color: #E30613;
    line-height: 1;
}

.chollo-detail-price-original {
    font-size: 1.4em;
    color: #999;
    text-decoration: line-through;
}

.chollo-discount-pill {
    background: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.9em;
    margin-left: auto;
}

.chollo-detail-button {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #E30613 0%, #ff4d4d 100%);
    color: white;
    padding: 20px 40px;
    border-radius: 50px;
    font-weight: 800;
    font-size: 1.4em;
    text-decoration: none !important;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    width: 100%;
    box-shadow: 0 10px 25px rgba(227, 6, 19, 0.3);
    border: none;
    cursor: pointer;
    gap: 12px;
}

.chollo-detail-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(227, 6, 19, 0.4);
    background: linear-gradient(135deg, #C40510 0%, #E30613 100%);
    color: #fff;
}

.chollo-detail-button:active {
    transform: translateY(0);
}

.chollo-detail-meta {
    padding: 25px 40px;
    background: #fafafa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    font-size: 0.95em;
    color: #666;
    flex-wrap: wrap;
    gap: 20px;
}

.chollo-detail-meta span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.info-bullet {
    width: 4px;
    height: 4px;
    background: #ccc;
    border-radius: 50%;
    margin: 0 4px;
}



/* Related Chollos Section */
.related-chollos {
    background: white;
    padding: 40px 20px;
    margin-top: 50px;
    border-radius: 12px;
}

.related-chollos h2 {
    font-size: 1.8em;
    color: #333;
    margin-bottom: 25px;
    font-weight: 700;
    text-align: left;
}

.related-chollos .chollos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* Card styling for related chollos */
.related-chollos .chollo-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid #e0e0e0;
}

.related-chollos .chollo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    border-color: #E30613;
}

.related-chollos .chollo-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f9f9f9;
    position: relative;
}

.related-chollos .chollo-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-chollos .chollo-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.related-chollos .chollo-title {
    font-size: 1em;
    margin-bottom: 10px;
    color: #333;
    line-height: 1.4;
}

.related-chollos .chollo-prices {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
}

.related-chollos .chollo-price-discount {
    font-size: 1.3em;
    font-weight: 700;
    color: #E30613;
}

@media (max-width: 900px) {
    .chollo-detail-header {
        grid-template-columns: 1fr;
        padding: 20px; /* Reduced from 30px */
        gap: 20px;
    }
    
    .chollo-detail-image-wrapper {
        max-width: 100%; /* Allow full width */
        margin: 0 auto;
        height: auto;
    }

    .chollo-detail-image {
        aspect-ratio: auto; /* Remove fixed aspect ratio */
        height: auto;
        max-height: 400px; /* Limit height */
        background: transparent;
        border: none;
    }
    
    .chollo-detail-image img {
        max-height: 350px; /* Limit image height */
    }
    
    .chollo-detail-title {
        font-size: 1.6em; /* Slightly smaller */
    }
    
    .related-chollos .chollos-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}

@media (max-width: 600px) {
    .chollo-detail-container {
        padding: 15px 10px; /* Less padding container */
    }

    .chollo-detail-header {
        padding: 15px;
    }

    .chollo-detail-image img {
        max-height: 280px; /* Even smaller for phones */
    }

    .related-chollos .chollos-grid {
        grid-template-columns: 1fr;
    }
    
    .chollo-detail-prices {
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px;
    }

    .chollo-detail-price-discount {
        font-size: 2em;
    }
    
    .chollo-detail-meta {
        padding: 15px;
        font-size: 0.85em;
        flex-direction: column;
        gap: 10px;
    }
    
    .chollo-detail-button {
        font-size: 1.2em;
        padding: 15px;
    }
}}
</style>

<!-- CSS para votación y comentarios -->
<link rel="stylesheet" href="/css/chollos-voting.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/css/chollos-comments.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/css/chollos-videos.css?v=<?php echo time(); ?>">

<script>
    // Variable global para el usuario actual
    const currentUserId = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : ""; ?>';
</script>

<link rel="stylesheet" href="/css/chollos-layout.css?v=<?php echo time(); ?>">

<div class="chollos-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <div class="breadcrumb-item">
            <a href="/chollos">Chollos</a>
        </div>
        <span class="breadcrumb-separator">›</span>
        <div class="breadcrumb-item">
            <?php 
            // Manejar categoría (puede ser array o string)
            $categoria_chollo = $chollo['categoria'] ?? 'general';
            
            // Si no es array, convertirlo a array para facilitar el bucle
            if (!is_array($categoria_chollo)) {
                $categoria_chollo = [$categoria_chollo];
            }
            
            $categorias_map = obtenerCategoriasChollos();
            $total_cats = count($categoria_chollo);
            
            // Incluir funciones helper para slugs
            if (!function_exists('categoriaToSlug')) {
                include_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';
            }
            
            $ruta_acumulada = '';
            foreach ($categoria_chollo as $index => $cat) {
                // Para las nuevas categorías "al vuelo", usamos el nombre tal cual si no está en el mapa
                // Normalizamos para buscar en el mapa (que suele tener claves en minúscula)
                $cat_key = strtolower($cat);
                $nombre_categoria = $categorias_map[$cat_key] ?? ucfirst($cat);
                
                // Construir URL SILO con slugs SEO-friendly
                $cat_slug = categoriaToSlug($cat);
                $ruta_acumulada .= ($ruta_acumulada ? '/' : '') . $cat_slug;
                ?>
                <a href="/chollos/<?php echo htmlspecialchars($ruta_acumulada); ?>">
                    <?php echo htmlspecialchars($nombre_categoria); ?>
                </a>
                
                <?php if ($index < $total_cats - 1): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
            <?php 
            } 
            ?>
        </div>
        <span class="breadcrumb-separator">›</span>
        <div class="breadcrumb-item">
            <span title="<?php echo htmlspecialchars($chollo['titulo']); ?>">
                <?php 
                $titulo_breadcrumb = htmlspecialchars($chollo['titulo']);
                // Truncar título si es muy largo (máximo 60 caracteres)
                if (strlen($titulo_breadcrumb) > 60) {
                    $titulo_breadcrumb = substr($titulo_breadcrumb, 0, 57) . '...';
                }
                echo $titulo_breadcrumb;
                ?>
            </span>
        </div>
    </nav>


    <div class="chollos-layout" style="display: block;">
        
        <main class="chollos-main">

    <div class="chollo-detail">
        <div class="chollo-detail-header">
            <div class="chollo-detail-image-wrapper">
                <div class="chollo-detail-image">
                    <?php 
                    if (!isset($url_acortada)) {
                        $url_acortada = generarUrlAcortadaChollo($chollo['id']);
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" class="chollo-image-link" style="display:flex; width:100%; height:100%; cursor:pointer; align-items:center; justify-content:center;">
                        <img src="<?php echo htmlspecialchars($chollo['imagen'] ?: 'https://via.placeholder.com/500x500?text=Chollo'); ?>" 
                             alt="<?php echo htmlspecialchars($chollo['titulo']); ?> oferta descuento">
                    </a>
                </div>
                
                <?php if ($chollo['porcentaje_descuento']): ?>
                    <div style="position: absolute; top: 15px; right: 15px; background: #28a745; color: white; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 1.1em; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        -<?php echo $chollo['porcentaje_descuento']; ?>%
                    </div>
                <?php endif; ?>
            </div>

            <div class="chollo-detail-info">
                <!-- Temperature & Actions Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <?php 
                        $temp = isset($chollo['temperatura']) ? $chollo['temperatura'] : 0;
                        $temp_color = ($temp >= 50) ? '#ff5252' : (($temp < 0) ? '#81d4fa' : '#666');
                        
                        $voto_usuario = obtenerVotoUsuario($chollo['id'], $_SESSION['user_id'] ?? null);
                        $cls_up = ($voto_usuario === 'positivo') ? 'active' : '';
                        $cls_down = ($voto_usuario === 'negativo') ? 'active' : '';
                    ?>
                    
                    <div class="chollo-voting-premium" data-chollo-id="<?php echo $chollo['id']; ?>">
                        <button class="vote-btn-premium vote-down-premium <?php echo $cls_down; ?>" title="Votar negativo">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <div class="temp-premium" style="color: <?php echo $temp_color; ?>;">
                            <?php echo $temp; ?>°
                        </div>
                        <button class="vote-btn-premium vote-up-premium <?php echo $cls_up; ?>" title="Votar positivo">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                    </div>

                    <div style="display: flex; gap: 20px; color: #888; font-size: 0.95em;">
                        <a href="#comentarios" style="display: flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#E30613'" onmouseout="this.style.color='#888'">
                            <i class="far fa-comment-alt"></i> <strong><?php echo $chollo['total_comentarios'] ?? 0; ?></strong>
                        </a>
                        
                        <span id="btn-share-chollo" style="cursor: pointer; display: flex; align-items: center; gap: 6px; transition: color 0.2s;" onmouseover="this.style.color='#E30613'" onmouseout="this.style.color='#888'"
                              data-title="<?php echo htmlspecialchars($chollo['titulo']); ?>" 
                              data-url="<?php echo 'https://www.codigoamigo.com' . $_SERVER['REQUEST_URI']; ?>">
                            <i class="fas fa-share-alt"></i> Compartir
                        </span>
                        
                        <span id="btn-save-chollo" class="<?php echo $is_fav ? 'active' : ''; ?>" 
                              style="cursor: pointer; display: flex; align-items: center; gap: 6px; transition: color 0.2s; <?php echo $is_fav ? 'color: #E30613; font-weight: 700;' : ''; ?>"
                              onmouseover="if(!this.classList.contains('active')) this.style.color='#E30613'" 
                              onmouseout="if(!this.classList.contains('active')) this.style.color='#888'"
                              data-codigo-id="<?php echo $chollo['id']; ?>">
                            <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                            <span><?php echo $is_fav ? 'Guardado' : 'Guardar'; ?></span>
                        </span>
                    </div>
                </div>

                <!-- Timestamp -->
                <?php 
                $fecha_pub = $chollo['fecha_creacion'] ?? 'now';
                $tiempo_transcurrido = 'hace un momento';
                $hora_exacta = '';
                if ($fecha_pub instanceof MongoDB\BSON\UTCDateTime) {
                    $fecha_obj = $fecha_pub->toDateTime();
                    $fecha_obj->setTimezone(new DateTimeZone('Europe/Madrid'));
                    $ts = $fecha_obj->getTimestamp();
                    $diff = time() - $ts;
                    $hora_exacta = $fecha_obj->format('H:i');
                    
                    if ($diff < 3600) {
                        $min = floor($diff / 60);
                        $tiempo_transcurrido = 'hace ' . ($min > 0 ? $min : 1) . ' min';
                    } elseif ($diff < 86400) {
                        $horas = floor($diff / 3600);
                        $tiempo_transcurrido = 'hace ' . $horas . 'h';
                    } else {
                        $dias = floor($diff / 86400);
                        $tiempo_transcurrido = 'hace ' . $dias . 'd';
                    }
                }
                ?>
                <div style="color: #E30613; font-size: 0.9em; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-bolt"></i> Publicado <?php echo $tiempo_transcurrido; ?><?php if ($hora_exacta): ?> • <?php echo $hora_exacta; ?><?php endif; ?>
                </div>

                <h1 class="chollo-detail-title">
                    <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" style="text-decoration: none; color: inherit;">
                        <?php echo htmlspecialchars($chollo['titulo']); ?>
                    </a>
                </h1>
                
                <?php if (!empty($chollo['descripcion'])): ?>
                    <div class="chollo-detail-description">
                        <?php 
                        $desc = nl2br(htmlspecialchars($chollo['descripcion']));
                        echo $desc;
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($chollo['precio_original'] || $chollo['precio_descuento']): ?>
                    <div class="chollo-detail-prices">
                        <?php if ($chollo['precio_descuento']): ?>
                            <span class="chollo-detail-price-discount">
                                <?php echo number_format($chollo['precio_descuento'], 2, ',', '.'); ?>€
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($chollo['precio_original'] && $chollo['precio_original'] > $chollo['precio_descuento']): ?>
                            <span class="chollo-detail-price-original">
                                <?php echo number_format($chollo['precio_original'], 2, ',', '.'); ?>€
                            </span>
                        <?php endif; ?>

                        <?php if ($chollo['porcentaje_descuento']): ?>
                            <div class="chollo-discount-pill">
                                -<?php echo $chollo['porcentaje_descuento']; ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="<?php echo htmlspecialchars($url_acortada); ?>" 
                   target="_blank" 
                   rel="nofollow sponsored"
                   class="chollo-detail-button">
                    Ir al chollo <i class="fas fa-external-link-alt"></i>
                </a>

                <!-- Store info and Amazon Services Link -->
                <div style="margin-top: 20px;">
                    <div style="margin-bottom: 12px; color: #666; font-size: 0.95em; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                        <i class="fas fa-store" style="color: #ccc;"></i>
                        <span>Chollo disponible en</span> 
                        <?php 
                        $es_amazon = false;
                        if (!empty($chollo['enlace'])) {
                            if (!function_exists('esEnlaceAmazon')) {
                                include_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';
                            }
                            $es_amazon = esEnlaceAmazon($chollo['enlace']);
                        }
                        ?>
                        <strong style="color: #1a1a1a; background: #e0e0e0; padding: 4px 10px; border-radius: 6px; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php echo $es_amazon ? 'Amazon' : 'Tienda verificada'; ?>
                        </strong> 
                    </div>

                    <a href="/amazon" style="display: inline-flex; align-items: center; gap: 8px; color: #FF9900; text-decoration: none; font-size: 0.9em; font-weight: 600; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        <i class="fab fa-amazon"></i> Ver más ofertas de Amazon, Audible y más <i class="fas fa-chevron-right" style="font-size: 0.8em;"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="chollo-detail-meta">
            <div>
                <span><i class="fas fa-tag"></i> <strong>Categoría:</strong> 
                    <?php 
                    $categoria_chollo = $chollo['categoria'] ?? 'general';
                    $categoria_primera = is_array($categoria_chollo) ? $categoria_chollo[0] : $categoria_chollo;
                    $categorias = obtenerCategoriasChollos();
                    $nombre_categoria = $categorias[$categoria_primera] ?? $categoria_primera;
                    
                    if (!function_exists('categoriaToSlug')) {
                        include_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';
                    }
                    $categoria_slug = categoriaToSlug($categoria_primera);
                    ?>
                    <a href="/chollos/<?php echo htmlspecialchars($categoria_slug); ?>" style="color: #1a1a1a; text-decoration: none; font-weight: 600;">
                        <?php echo htmlspecialchars($nombre_categoria); ?>
                    </a>
                </span>
                <span class="info-bullet"></span>
                <?php if (!empty($chollo['fuente'])): ?>
                    <span><i class="fas fa-info-circle"></i> <strong>Fuente:</strong> 
                        <?php 
                        $fuente = strtolower(trim($chollo['fuente']));
                        if ($fuente === 'telegram') {
                            echo '<a href="https://t.me/cholloscodigoamigo" target="_blank" rel="nofollow noopener" style="color: #1a1a1a; text-decoration: none; font-weight: 600;">' . htmlspecialchars($chollo['fuente']) . '</a>';
                        } else {
                            echo '<strong>' . htmlspecialchars($chollo['fuente']) . '</strong>';
                        }
                        ?>
                    </span>
                    <span class="info-bullet"></span>
                <?php endif; ?>
                <?php if (isset($chollo['clicks']) && $chollo['clicks'] > 0): ?>
                    <span><i class="fas fa-fire" style="color:#ff9800;"></i> <strong><?php echo number_format($chollo['clicks']); ?></strong> clics</span>
                    <span class="info-bullet"></span>
                <?php endif; ?>
                <?php if (!empty($chollo['fecha_creacion'])): ?>
                    <span style="color: #E30613; font-weight: 700;">
                        <i class="fas fa-calendar-alt"></i> <?php 
                        $fecha = $chollo['fecha_creacion'];
                        if (is_object($fecha)) { $fecha = $fecha->format('Y-m-d H:i:s'); }
                        echo date('d/m/Y', strtotime($fecha)); 
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Keepa Price History Chart -->
        <?php
        $asin = null;
        $enlace = $chollo['enlace'] ?? '';
        // Try to extract ASIN from link
    if (!empty($enlace)) {
        if (!function_exists('extraerASIN')) {
            include_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';
        }
        $asin = extraerASIN($enlace);
        
        // Si no detecta ASIN pero es enlace de Amazon (posible acortador), intentar expandir
        if (empty($asin) && function_exists('esEnlaceAmazon') && esEnlaceAmazon($enlace)) {
            if (function_exists('expandirAcortadorAmazon')) {
                $enlace_expandido = expandirAcortadorAmazon($enlace);
                if ($enlace_expandido !== $enlace) {
                    $asin = extraerASIN($enlace_expandido);
                    
                    // PERSIST ASIN IN DB to avoid expanding on every load
                    if (!empty($asin) && isset($chollo['id'])) {
                        try {
                            $db_instance = createConnection();
                            $chollos_coll = $db_instance->selectCollection('chollos');
                            $chollos_coll->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($chollo['id'])],
                                ['$set' => ['asin' => $asin]]
                            );
                        } catch (Exception $e) {
                            // Silently fail if DB update fails
                        }
                    }
                }
            }
        }
    }
    
    // If not found in link, check separate field if it existed (schema doesn't show it but good to be safe)
        if (empty($asin) && !empty($chollo['asin'])) {
            $asin = $chollo['asin'];
        }
    
        if (!empty($asin)): 
        ?>
        <!-- DEBUG: ASIN FOUND: <?php echo htmlspecialchars($asin); ?> -->
        <div class="keepa-chart-container" style="margin-top: 30px; background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #f0f0f0; box-shadow: 0 5px 20px rgba(0,0,0,0.03);">
            <h3 style="margin: 0 0 20px 0; font-size: 1.3em; color: #333; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-line" style="color: #E30613;"></i> Historial de Precios
            </h3>
            <div style="width: 100%; overflow-x: auto; text-align: center;">
                <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" style="display: block; cursor: pointer;">
                    <img src="https://graph.keepa.com/pricehistory.png?asin=<?php echo htmlspecialchars($asin); ?>&domain=es&width=1200&height=500" 
                         alt="Historial de precios para <?php echo htmlspecialchars($chollo['titulo']); ?>" 
                         style="width: 100%; height: auto; border-radius: 8px;"
                         loading="lazy">
                </a>
            </div>
            <p style="margin: 15px 0 0 0; font-size: 0.9em; color: #888; text-align: center;">
                Gráfico de evolución de precios proporcionado por Keepa
            </p>
        </div>
        <?php else: ?>
        <!-- DEBUG: NO ASIN FOUND FOR ENLACE: <?php echo htmlspecialchars($enlace); ?> -->
        <?php endif; ?>
    </div>


    <!-- Sección de Videos (YouTube) -->
    <div class="chollo-videos-section" id="cholloVideosSection">
        <div class="videos-header">
            <h2 id="videoSectionTitle" style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.5rem;"><i class="fab fa-youtube" style="color: #FF0000;"></i> Videos destacados</h2>
        </div>
        
        <div id="shortsWrapper">
            <!-- Shorts se inyectan aquí -->
        </div>

        <div class="videos-grid" id="videosWrapper">
            <!-- Videos se inyectan aquí -->
        </div>
        
        <!-- Modal para reproducir -->
        <div class="video-modal-overlay" id="videoModal">
            <div class="video-modal-content">
                <button class="modal-close" id="closeVideoModal">&times;</button>
                <div class="video-container" id="videoPlayerContainer">
                    <!-- Iframe se inyecta aquí -->
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cholloId = '<?php echo $chollo['id']; ?>';
        const section = document.getElementById('cholloVideosSection');
        
        if (cholloId && section) {
            fetch('/public/api/get_related_videos.php?id=' + cholloId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar sección
                        section.classList.add('loaded');
                        
                        // Update Title with Product Name
                        if (data.product_name) {
                            const titleElement = document.getElementById('videoSectionTitle');
                            titleElement.innerHTML = `<i class="fab fa-youtube" style="color: #FF0000;"></i> Videos de ${data.product_name}`;
                        }
                        
                        // Render Shorts
                        const shortsContainer = document.getElementById('shortsWrapper');
                        if (data.shorts && data.shorts.length > 0) {
                            let html = '<h4 style="margin: 0 0 15px 0; font-size: 1.1em; color: #555;">Shorts destacados</h4><div class="shorts-container">';
                            data.shorts.forEach(video => {
                                html += `
                                    <div class="short-card" onclick="playVideo('${video.id}')">
                                        <img src="${video.thumbnail_hq || video.thumbnail}" class="short-thumbnail" loading="lazy">
                                        <div class="short-overlay">
                                            <div class="short-title">${video.title}</div>
                                        </div>
                                        <div class="short-icon"><i class="fas fa-play" style="font-size: 10px; color: white;"></i></div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            shortsContainer.innerHTML = html;
                        } else {
                            // Ocultar si no hay shorts
                            shortsContainer.style.display = 'none';
                        }
                        
                        // Render Videos
                        const videosContainer = document.getElementById('videosWrapper');
                        if (data.videos && data.videos.length > 0) {
                            let html = '';
                            data.videos.forEach(video => {
                                html += `
                                    <div class="video-card" onclick="playVideo('${video.id}')">
                                        <div class="video-thumb-wrapper">
                                            <img src="${video.thumbnail_hq || video.thumbnail}" class="video-thumb" loading="lazy">
                                            <div class="play-button"><i class="fas fa-play"></i></div>
                                            <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                                ${video.length || 'YouTube'}
                                            </div>
                                        </div>
                                        <div class="video-info">
                                            <div class="video-title">${video.title}</div>
                                            <div class="video-meta">
                                                <span>${video.author}</span>
                                                <span>${video.views}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            videosContainer.innerHTML = html;
                        } else {
                            if (!data.shorts || data.shorts.length === 0) {
                                // Si no hay nada de nada
                                section.style.display = 'none';
                            }
                        }
                        
                    } else {
                        console.log('No video data:', data.error);
                        section.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error('Error fetching videos:', err);
                    section.style.display = 'none';
                });
        }
        
        // Modal Logic
        window.playVideo = function(videoId) {
            const modal = document.getElementById('videoModal');
            const container = document.getElementById('videoPlayerContainer');
            
            container.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            
            modal.classList.add('active');
        };
        
        document.getElementById('closeVideoModal').onclick = function() {
            const modal = document.getElementById('videoModal');
            const container = document.getElementById('videoPlayerContainer');
            modal.classList.remove('active');
            container.innerHTML = ''; // Stop video
        };
        
        document.getElementById('videoModal').onclick = function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.getElementById('videoPlayerContainer').innerHTML = '';
            }
        };
    });
    </script>
    
    <!-- Sección de Votación -->
    <!-- Sección de Votación eliminada (ya movida arriba) -->

    <!-- Sección de Comentarios -->
    <?php
    // Obtener datos del usuario actual si está autenticado
    $usuario_actual = null;
    if (isset($_SESSION['user_id'])) {
        $usuario_actual = get_object_user('_id', new MongoDB\BSON\ObjectId($_SESSION['user_id']));
    }
    
    echo renderCholloComments($chollo['id'], $usuario_actual);
    ?>

    <!-- Banner de Telegram en el detalle (Movido después de comentarios) -->
    <div class="telegram-detail-promo" style="margin: 30px 0; padding: 25px; background: linear-gradient(135deg, #0088cc 0%, #006699 100%); border-radius: 12px; color: white; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 4px 15px rgba(0, 136, 204, 0.2);">
        <div style="flex: 1;">
            <h3 style="margin: 0 0 10px 0; font-size: 1.4em; color: white;">🚀 ¡No te pierdas ningún chollo!</h3>
            <p style="margin: 0; opacity: 0.9; font-size: 1.05em;">Únete a nuestro canal de Telegram y recibe las mejores ofertas al instante en tu móvil.</p>
        </div>
        <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" 
           style="background: white; color: #0088cc; padding: 12px 25px; border-radius: 30px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: transform 0.2s;">
            <i class="fa-brands fa-telegram"></i> Unirme ahora
        </a>
    </div>
    <style>
        .telegram-detail-promo a:hover { transform: scale(1.05); background: #f8f9fa; }
        @media (max-width: 600px) {
            .telegram-detail-promo { flex-direction: column; text-align: center; }
            .telegram-detail-promo a { width: 100%; }
        }
    </style>

    <!-- RICH CONTENT SECTIONS -->
    <div class="rich-content-wrapper" style="margin: 50px 0;">
        
        <!-- Análisis del Chollo -->
        <?php if (!empty($rich_content['analysis'])): ?>
        <section class="content-section analysis-section" id="why-good-deal" style="background: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h2 style="font-size: 2em; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-lightbulb" style="color: #E30613;"></i>
                <?php echo htmlspecialchars($rich_content['analysis']['titulo']); ?>
            </h2>
            <div class="analysis-content" style="font-size: 1.05em; line-height: 1.8; color: #555;">
                <?php echo nl2br($rich_content['analysis']['contenido']); ?>
            </div>
        </section>
        
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" class="chollo-detail-button" style="max-width: 300px; margin: 0 auto; font-size: 1.1em; padding: 15px 30px;">
                Ir al chollo <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        <?php endif; ?>

        <!-- Pros y Contras -->
        <?php if (!empty($rich_content['pros_cons'])): ?>
        <section class="content-section pros-cons-section" id="pros-cons" style="margin-bottom: 30px; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <h2 style="font-size: 2.2em; color: #1a1a1a; margin-bottom: 30px; text-align: center; font-weight: 700;">
                ⚖️ Ventajas y Desventajas
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Pros -->
                <div class="pros-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 35px; border-radius: 16px; border: 2px solid #86efac; box-shadow: 0 2px 12px rgba(34, 197, 94, 0.1);">
                    <h3 style="color: #15803d; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 1.5em; font-weight: 700;">
                        <i class="fas fa-check-circle" style="font-size: 1.2em;"></i> Ventajas
                    </h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach($rich_content['pros_cons']['pros'] as $pro): ?>
                            <li style="margin-bottom: 16px; display: flex; align-items: start; gap: 12px; color: #1a1a1a; font-size: 1.05em; line-height: 1.6;">
                                <i class="fas fa-plus-circle" style="color: #22c55e; margin-top: 2px; font-size: 1.1em; flex-shrink: 0;"></i>
                                <span style="color: #334155;"><?php echo htmlspecialchars($pro); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contras -->
                <div class="cons-card" style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); padding: 35px; border-radius: 16px; border: 2px solid #fca5a5; box-shadow: 0 2px 12px rgba(239, 68, 68, 0.1);">
                    <h3 style="color: #b91c1c; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 1.5em; font-weight: 700;">
                        <i class="fas fa-times-circle" style="font-size: 1.2em;"></i> Desventajas
                    </h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach($rich_content['pros_cons']['contras'] as $contra): ?>
                            <li style="margin-bottom: 16px; display: flex; align-items: start; gap: 12px; color: #1a1a1a; font-size: 1.05em; line-height: 1.6;">
                                <i class="fas fa-minus-circle" style="color: #ef4444; margin-top: 2px; font-size: 1.1em; flex-shrink: 0;"></i>
                                <span style="color: #334155;"><?php echo htmlspecialchars($contra); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
        
        <style>
            @media (max-width: 768px) {
                .pros-cons-section > div { grid-template-columns: 1fr; }
            }
        </style>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" class="chollo-detail-button" style="max-width: 300px; margin: 0 auto; font-size: 1.1em; padding: 15px 30px;">
                Ir al chollo <i class="fas fa-external-link-alt"></i>
            </a>
        </div>

        <!-- Para Quién Es -->
        <?php if (!empty($rich_content['who_for'])): ?>
        <section class="content-section who-for-section" id="who-for" style="background: white; padding: 45px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <h2 style="font-size: 2.2em; color: #1a1a1a; margin-bottom: 30px; text-align: center; font-weight: 700;">
                🎯 <?php echo htmlspecialchars($rich_content['who_for']['titulo']); ?>
            </h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 35px;">
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 30px; border-radius: 12px; border: 2px solid #86efac;">
                    <h3 style="color: #15803d; font-size: 1.4em; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                        <i class="fas fa-thumbs-up" style="font-size: 1.3em;"></i> Ideal para:
                    </h3>
                    <ul style="line-height: 2; padding-left: 0; list-style: none; margin: 0;">
                        <?php foreach($rich_content['who_for']['ideal_para'] as $item): ?>
                            <li style="margin-bottom: 12px; color: #334155; font-size: 1.05em; display: flex; align-items: start; gap: 10px;">
                                <i class="fas fa-check" style="color: #22c55e; margin-top: 6px; flex-shrink: 0;"></i>
                                <span><?php echo htmlspecialchars($item); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 30px; border-radius: 12px; border: 2px solid #fbbf24;">
                    <h3 style="color: #b45309; font-size: 1.4em; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.3em;"></i> Ten en cuenta:
                    </h3>
                    <ul style="line-height: 2; padding-left: 0; list-style: none; margin: 0;">
                        <?php foreach($rich_content['who_for']['no_recomendado'] as $item): ?>
                            <li style="margin-bottom: 12px; color: #334155; font-size: 1.05em; display: flex; align-items: start; gap: 10px;">
                                <i class="fas fa-info-circle" style="color: #f59e0b; margin-top: 6px; flex-shrink: 0;"></i>
                                <span><?php echo htmlspecialchars($item); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
        
        <style>
            @media (max-width: 768px) {
                .who-for-section > div { grid-template-columns: 1fr; }
            }
        </style>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" class="chollo-detail-button" style="max-width: 300px; margin: 0 auto; font-size: 1.1em; padding: 15px 30px;">
                Ir al chollo <i class="fas fa-external-link-alt"></i>
            </a>
        </div>

        <!-- Guía de Compra -->
        <?php if (!empty($rich_content['purchase_guide'])): ?>
        <section class="content-section purchase-guide-section" id="purchase-guide" style="background: linear-gradient(135deg, #f5f7fa 0%, #e8ebef 100%); padding: 40px; border-radius: 12px; margin-bottom: 30px; border: 2px solid #dee2e6;">
            <h2 style="font-size: 2em; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shopping-bag" style="color: #E30613;"></i>
                <?php echo htmlspecialchars($rich_content['purchase_guide']['titulo']); ?>
            </h2>
            <p style="font-size: 1.05em; color: #666; margin-bottom: 25px;">
                <?php echo htmlspecialchars($rich_content['purchase_guide']['intro']); ?>
            </p>
            
            <div class="purchase-points" style="display: grid; gap: 20px;">
                <?php foreach($rich_content['purchase_guide']['puntos'] as $punto): ?>
                    <div class="purchase-point" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #E30613;">
                        <h3 style="color: #E30613; font-size: 1.2em; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($punto['titulo']); ?>
                        </h3>
                        <p style="color: #555; line-height: 1.6; margin: 0;">
                            <?php echo htmlspecialchars($punto['descripcion']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <a href="<?php echo htmlspecialchars($url_acortada); ?>" target="_blank" rel="nofollow sponsored" class="chollo-detail-button" style="max-width: 300px; margin: 0 auto; font-size: 1.1em; padding: 15px 30px;">
                Ir al chollo <i class="fas fa-external-link-alt"></i>
            </a>
        </div>

        <!-- FAQs con Schema.org -->
        <?php if (!empty($rich_content['faqs'])): ?>
        <section class="content-section faq-section" id="faqs" itemscope itemtype="https://schema.org/FAQPage" style="background: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h2 style="font-size: 2em; color: #333; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-question-circle" style="color: #E30613;"></i>
                Preguntas Frecuentes
            </h2>
            
            <div class="faq-accordion" style="display: grid; gap: 15px;">
                <?php $faq_index = 0; foreach($rich_content['faqs'] as $pregunta => $respuesta): $faq_index++; ?>
                    <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question" style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                        <button class="faq-question" onclick="toggleFAQ(<?php echo $faq_index; ?>)" style="width: 100%; text-align: left; padding: 20px; background: #f8f9fa; border: none; cursor: pointer; font-size: 1.1em; font-weight: 600; color: #333; display: flex; justify-content: space-between; align-items: center; transition: background 0.3s;">
                            <span itemprop="name"><?php echo htmlspecialchars($pregunta); ?></span>
                            <i class="fas fa-chevron-down faq-icon-<?php echo $faq_index; ?>" style="transition: transform 0.3s; color: #E30613;"></i>
                        </button>
                        <div class="faq-answer faq-answer-<?php echo $faq_index; ?>" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div itemprop="text" style="padding: 20px; background: white; color: #555; line-height: 1.7;">
                                <?php echo nl2br(htmlspecialchars($respuesta)); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <script>
        function toggleFAQ(index) {
            const answer = document.querySelector('.faq-answer-' + index);
            const icon = document.querySelector('.faq-icon-' + index);
            const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';
            
            if (isOpen) {
                answer.style.maxHeight = '0';
                icon.style.transform = 'rotate(0deg)';
            } else {
                answer.style.maxHeight = answer.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            }
        }
        
        // Hover effect on FAQ questions
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.faq-question').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.background = '#e9ecef';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.background = '#f8f9fa';
                });
            });
        });
        </script>
        <?php endif; ?>
        
    </div>
    <!-- END RICH CONTENT SECTIONS -->


    <?php
    // Widget de Top Chollos antes de chollos relacionados - mejor UX y engagement
    if (function_exists('renderHotDealsWidget')) {
        echo '<div style="margin: 50px 0;">';
        echo renderHotDealsWidget($categoria_relacionados ?? 'general');
        echo '</div>';
    }
    
    // Mostrar chollos relacionados de la misma categoría
    // Mostrar chollos relacionados de la misma categoría
    // Usar la categoría más específica (la última del array) para mayor relevancia
    $categorias_chollo = is_array($chollo['categoria']) ? $chollo['categoria'] : [$chollo['categoria']];
    $categoria_relacionados = end($categorias_chollo); // Última categoría == más específica
    
    $chollos_relacionados = obtenerChollos([
        'categoria' => $categoria_relacionados,
        'estado' => 1,
        'limite' => 6
    ]);
    
    // Si hay pocos resultados y tenemos categorías padre, intentar con la penúltima
    if (count($chollos_relacionados) < 3 && count($categorias_chollo) > 1) {
        $categoria_padre = prev($categorias_chollo);
        $chollos_extra = obtenerChollos([
            'categoria' => $categoria_padre,
            'estado' => 1,
            'limite' => 6
        ]);
        // Fusionar sin duplicados
        foreach ($chollos_extra as $c) {
            $ids = array_column($chollos_relacionados, 'id');
            if (!in_array($c['id'], $ids)) {
                $chollos_relacionados[] = $c;
            }
        }
    }
    
    // Excluir el chollo actual
    $chollos_relacionados = array_filter($chollos_relacionados, function($c) use ($chollo) {
        return (string)$c['id'] !== (string)$chollo['id'];
    });
    
    if (!empty($chollos_relacionados)):
    ?>
        <div class="related-chollos">
            <h2>Chollos relacionados</h2>
            <?php imprimir_grid_chollos(array_slice($chollos_relacionados, 0, 3), 3); ?>
        </div>
    <?php endif; ?>
        </main>
    </div>
</div>

<!-- Scripts de votación y comentarios -->
<!-- Scripts de votación y comentarios -->
<script src="/js/chollos-voting.js?v=<?php echo time(); ?>"></script>
<script src="/js/chollos-comments.js?v=<?php echo time(); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ChollosComments !== 'undefined') {
            ChollosComments.init('<?php echo $chollo['id']; ?>');
        }
    });
</script>

</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Share functionality
    const btnShare = document.getElementById('btn-share-chollo');
    if(btnShare) {
        btnShare.addEventListener('click', async function() {
            const data = {
                title: btnShare.dataset.title || document.title,
                url: btnShare.dataset.url || window.location.href
            };
            
            if (navigator.share) {
                try {
                    await navigator.share(data);
                } catch (err) {
                    console.log('Error sharing:', err);
                }
            } else {
                // Fallback: Copy to clipboard
                try {
                    await navigator.clipboard.writeText(data.url);
                    const originalHTML = btnShare.innerHTML;
                    btnShare.innerHTML = '<i class="fas fa-check"></i> Copiado';
                    setTimeout(() => {
                        btnShare.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {
                    alert('Enlace: ' + data.url);
                }
            }
        });
    }

    // Save functionality
    const btnSave = document.getElementById('btn-save-chollo');
    if(btnSave) {
        btnSave.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Verificar login
            if (!currentUserId) {
                 // Trigger login modal
                 const loginBtn = document.querySelector('.open_modal_login');
                 if(loginBtn) loginBtn.click();
                 else alert('Debes iniciar sesión para guardar favoritos');
                 return;
            }

            const codigoId = this.dataset.codigoId;
            const isActive = this.classList.contains('active');
            const action = isActive ? 'eliminar_favorito' : 'añadir_favorito';
            
            // Visual feedback
            this.style.opacity = '0.7';
            
            try {
                const formData = new FormData();
                formData.append('metodo', action);
                formData.append('codigo_id', codigoId);
                formData.append('tipo', 'chollo');

                const response = await fetch('/myphp/ajax_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    if(action === 'añadir_favorito') {
                        this.classList.add('active');
                        this.style.color = '#E30613';
                        this.style.fontWeight = 'bold';
                        this.querySelector('i').className = 'fas fa-bookmark';
                        this.querySelector('.text').textContent = 'Guardado';
                    } else {
                        this.classList.remove('active');
                        this.style.color = ''; // reset
                        this.style.fontWeight = ''; // reset
                        this.querySelector('i').className = 'far fa-bookmark';
                        this.querySelector('.text').textContent = 'Guardar';
                    }
                } else {
                    alert(result.message || 'Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            } finally {
                this.style.opacity = '1';
            }
        });
    }
});
</script>
<?php get_footer(); ?>
