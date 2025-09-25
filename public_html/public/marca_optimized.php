<?php
/**
 * Página de marca optimizada y renovada
 * Versión final con código limpio, funcional y bien estructurado
 */

// Incluir configuración y funciones auxiliares
require_once __DIR__ . '/marca_config.php';

// Obtener datos de la marca (esto vendría del archivo original)
// $marca, $lista_codigos, $lista_codigos_patrocinados, $numero_codigos, etc.

// Validar datos de entrada
$errors = validate_brand_page_data($marca, $lista_codigos, $lista_codigos_patrocinados);
if (!empty($errors)) {
    error_log('Errores en datos de marca: ' . implode(', ', $errors));
    // Redirigir a página de error o mostrar mensaje
}

// Sanitizar datos
$marca = sanitize_brand_page_data($marca);

// Generar metadatos SEO
$seo_config = get_seo_config($marca, $numero_codigos);
$meta_data = generate_brand_page_meta($marca, $numero_codigos);

// Configuración de la página
$config = get_brand_page_config();

// Obtener datos del usuario si está logueado
$user_data = null;
if (!empty($_SESSION["user_id"])) {
    $user_data = get_safe_user_data($_SESSION["user_id"]);
}

// Incluir header con metadatos optimizados
get_header_new(
    $seo_config['title'],
    $seo_config['description'],
    $seo_config['title'],
    $seo_config['description'],
    $seo_config['og_image'],
    []
);

// Formatear número de códigos
$numero_codigos_format = format_codes_count($numero_codigos);

// Obtener marcas relacionadas
$marcas_relacionadas = get_related_brands($marca, $config['max_related_brands']);
?>

<!-- Incluir estilos CSS externos -->
<link rel="stylesheet" href="/assets/css/brand-page.css">

<!-- Metadatos adicionales -->
<meta name="keywords" content="<?php echo $seo_config['keywords']; ?>">
<meta name="robots" content="<?php echo $seo_config['robots']; ?>">
<link rel="canonical" href="<?php echo $seo_config['canonical']; ?>">

<!-- Open Graph -->
<meta property="og:type" content="<?php echo $seo_config['og_type']; ?>">
<meta property="og:site_name" content="<?php echo $seo_config['og_site_name']; ?>">
<meta property="og:url" content="<?php echo $seo_config['canonical']; ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="<?php echo $seo_config['twitter_card']; ?>">
<meta name="twitter:site" content="<?php echo $seo_config['twitter_site']; ?>">

<!-- JSON-LD para SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "<?php echo $marca['nombre']; ?>",
    "url": "<?php echo $seo_config['canonical']; ?>",
    "logo": "<?php echo $marca['imagen']; ?>",
    "description": "<?php echo $seo_config['description']; ?>",
    "offers": {
        "@type": "AggregateOffer",
        "offerCount": "<?php echo $numero_codigos; ?>",
        "description": "Códigos de descuento para <?php echo $marca['nombre']; ?>"
    }
}
</script>

<div class="brand-page-container">
    <div class="container">
        
        <!-- Header de la marca -->
        <header class="brand-header fade-in-up" role="banner">
            <div class="brand-logo-section">
                <img src="<?php echo $marca['imagen']; ?>" 
                     alt="<?php echo $marca['nombre']; ?>" 
                     class="brand-logo"
                     loading="eager"
                     width="80" 
                     height="80">
                <div class="brand-info">
                    <h1><?php echo $marca['nombre']; ?></h1>
                    <?php if($marca['beneficio']): ?>
                        <div class="brand-benefit" aria-label="Beneficio de la marca">
                            <i class="fas fa-gift" aria-hidden="true"></i> 
                            <?php echo get_brand_benefit_text($marca['beneficio']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($marca['descripción']): ?>
                <div class="brand-description">
                    <?php echo $marca['descripción']; ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- Sección principal de códigos -->
        <main class="codes-section fade-in-up" role="main">
            <h2 class="section-title">
                <?php echo $numero_codigos_format; ?> Códigos de Descuento para <?php echo $marca['nombre']; ?>
            </h2>
            
            <?php if($numero_codigos == 0): ?>
                <!-- Estado sin códigos -->
                <div class="no-codes-message" role="status" aria-live="polite">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <h3>Aún no hay códigos de esta marca</h3>
                    <p>Sé el primero en publicar un código y ayuda a la comunidad</p>
                </div>
            <?php else: ?>
                <!-- Grid de códigos -->
                <div class="codes-grid" role="list" aria-label="Lista de códigos de descuento">
                    <?php 
                    // Mostrar códigos destacados primero
                    if(has_featured_codes($lista_codigos_patrocinados)):
                        foreach($lista_codigos_patrocinados as $item):
                            $datos_usuario = get_safe_user_data($item["id_usuario"]);
                    ?>
                        <article class="<?php echo get_code_card_classes($item); ?>" role="listitem">
                            <div class="featured-badge" aria-label="Código destacado">
                                <i class="fas fa-star" aria-hidden="true"></i> Destacado
                            </div>
                            
                            <div class="code-header">
                                <img src="<?php echo $datos_usuario['img']; ?>" 
                                     alt="<?php echo $datos_usuario['username']; ?>" 
                                     class="user-avatar"
                                     loading="lazy"
                                     width="50" 
                                     height="50">
                                <div class="user-info">
                                    <h4><?php echo $datos_usuario['username']; ?></h4>
                                    <small>
                                        <i class="fas fa-eye" aria-hidden="true"></i> 
                                        <?php echo $item["totalclicks"] ?? 0; ?> vistas
                                        <span class="mx-2" aria-hidden="true">•</span>
                                        <i class="far fa-clock" aria-hidden="true"></i> 
                                        <?php echo get_relative_date($item["fecha_publicacion"]); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="benefit-display">
                                <div class="benefit-amount"><?php echo $item["num_beneficio"]; ?>€</div>
                                <div class="benefit-type"><?php echo $item["tipo_descuento"]; ?></div>
                            </div>
                            
                            <div class="code-description">
                                <?php echo get_truncated_description($item["descripcion"]); ?>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" 
                                   class="btn-primary"
                                   aria-label="Ver código de descuento">
                                    <?php echo get_primary_button_text($item); ?>
                                </a>
                                <a href="<?php echo link_usuario($datos_usuario['username'], $datos_usuario['_id']); ?>" 
                                   class="btn-secondary"
                                   aria-label="Ver perfil de usuario">
                                    <i class="fas fa-user" aria-hidden="true"></i> Perfil
                                </a>
                            </div>
                            
                            <div class="stats-bar">
                                <div class="vote-section">
                                    <?php if(can_user_vote($user_data['_id'] ?? '', $item["_id"])): ?>
                                        <button class="vote-btn negative" 
                                                data-codigo-id="<?php echo $item["_id"]; ?>"
                                                aria-label="Votar negativamente">
                                            -
                                        </button>
                                        <span class="vote-count"><?php echo get_vote_count($item); ?>°</span>
                                        <button class="vote-btn positive" 
                                                data-codigo-id="<?php echo $item["_id"]; ?>"
                                                aria-label="Votar positivamente">
                                            +
                                        </button>
                                    <?php else: ?>
                                        <span class="vote-count"><?php echo get_vote_count($item); ?>°</span>
                                    <?php endif; ?>
                                </div>
                                <small>
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i> 
                                    <?php echo get_formatted_province($item["provincia"] ?? ''); ?>
                                </small>
                            </div>
                        </article>
                    <?php 
                        endforeach;
                    endif;
                    
                    // Mostrar códigos normales
                    if(has_normal_codes($lista_codigos)):
                        $codigos_normales = array_slice($lista_codigos, 0, $config['max_codes_display']);
                        foreach($codigos_normales as $item):
                            $datos_usuario = get_safe_user_data($item["id_usuario"]);
                    ?>
                        <article class="<?php echo get_code_card_classes($item); ?>" role="listitem">
                            <div class="code-header">
                                <img src="<?php echo $datos_usuario['img']; ?>" 
                                     alt="<?php echo $datos_usuario['username']; ?>" 
                                     class="user-avatar"
                                     loading="lazy"
                                     width="50" 
                                     height="50">
                                <div class="user-info">
                                    <h4><?php echo $datos_usuario['username']; ?></h4>
                                    <small>
                                        <i class="fas fa-eye" aria-hidden="true"></i> 
                                        <?php echo $item["totalclicks"] ?? 0; ?> vistas
                                        <span class="mx-2" aria-hidden="true">•</span>
                                        <i class="far fa-clock" aria-hidden="true"></i> 
                                        <?php echo get_relative_date($item["fecha_publicacion"]); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="benefit-display">
                                <div class="benefit-amount"><?php echo $item["num_beneficio"]; ?>€</div>
                                <div class="benefit-type"><?php echo $item["tipo_descuento"]; ?></div>
                            </div>
                            
                            <div class="code-description">
                                <?php echo get_truncated_description($item["descripcion"]); ?>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" 
                                   class="btn-primary"
                                   aria-label="Ver código de descuento">
                                    <?php echo get_primary_button_text($item); ?>
                                </a>
                                <a href="<?php echo link_usuario($datos_usuario['username'], $datos_usuario['_id']); ?>" 
                                   class="btn-secondary"
                                   aria-label="Ver perfil de usuario">
                                    <i class="fas fa-user" aria-hidden="true"></i> Perfil
                                </a>
                            </div>
                            
                            <div class="stats-bar">
                                <div class="vote-section">
                                    <?php if(can_user_vote($user_data['_id'] ?? '', $item["_id"])): ?>
                                        <button class="vote-btn negative" 
                                                data-codigo-id="<?php echo $item["_id"]; ?>"
                                                aria-label="Votar negativamente">
                                            -
                                        </button>
                                        <span class="vote-count"><?php echo get_vote_count($item); ?>°</span>
                                        <button class="vote-btn positive" 
                                                data-codigo-id="<?php echo $item["_id"]; ?>"
                                                aria-label="Votar positivamente">
                                            +
                                        </button>
                                    <?php else: ?>
                                        <span class="vote-count"><?php echo get_vote_count($item); ?>°</span>
                                    <?php endif; ?>
                                </div>
                                <small>
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i> 
                                    <?php echo get_formatted_province($item["provincia"] ?? ''); ?>
                                </small>
                            </div>
                        </article>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
                
                <?php if(count($lista_codigos) > $config['max_codes_display']): ?>
                    <div class="text-center mt-4">
                        <a href="<?php echo link_marca($marca["nombre_clave"]); ?>?page=2" 
                           class="btn-primary"
                           aria-label="Ver todos los códigos disponibles">
                            <i class="fas fa-list" aria-hidden="true"></i> Ver Todos los Códigos
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <!-- Sección para publicar código -->
        <section class="publish-section fade-in-up" role="complementary">
            <?php if($user_data): ?>
                <h3><i class="fas fa-plus-circle" aria-hidden="true"></i> ¿Tienes un código de <?php echo $marca['nombre']; ?>?</h3>
                <p>Comparte tu código y ayuda a la comunidad a ahorrar dinero</p>
                <a href="<?php echo link_nuevo_codigo(); ?>?marca=<?php echo urlencode($marca['nombre']); ?>" 
                   class="btn-publish"
                   aria-label="Publicar nuevo código">
                    <i class="fas fa-plus" aria-hidden="true"></i> Publicar Mi Código
                </a>
            <?php else: ?>
                <h3><i class="fas fa-plus-circle" aria-hidden="true"></i> ¿Tienes un código de <?php echo $marca['nombre']; ?>?</h3>
                <p>Regístrate gratis y comparte tu código para ayudar a otros usuarios</p>
                <a href="/registro" 
                   class="btn-publish"
                   aria-label="Registrarse para publicar códigos">
                    <i class="fas fa-user-plus" aria-hidden="true"></i> Registrarse Gratis
                </a>
            <?php endif; ?>
        </section>

        <!-- Sección de marcas relacionadas -->
        <?php if(!empty($marcas_relacionadas)): ?>
            <section class="codes-section fade-in-up" role="complementary">
                <h2 class="section-title">Marcas Relacionadas</h2>
                <div class="codes-grid" role="list" aria-label="Marcas relacionadas">
                    <?php foreach($marcas_relacionadas as $marca_rel): ?>
                        <article class="code-card" role="listitem">
                            <div class="code-header">
                                <img src="<?php echo $marca_rel["imagen"]; ?>" 
                                     alt="<?php echo $marca_rel["nombre"]; ?>" 
                                     class="user-avatar"
                                     loading="lazy"
                                     width="50" 
                                     height="50">
                                <div class="user-info">
                                    <h4><?php echo $marca_rel["nombre"]; ?></h4>
                                    <small><?php echo $marca_rel["num_codigos"] ?? 0; ?> códigos disponibles</small>
                                </div>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_marca($marca_rel["nombre_clave"]); ?>" 
                                   class="btn-primary"
                                   aria-label="Ver códigos de <?php echo $marca_rel["nombre"]; ?>">
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i> Ver Códigos
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<!-- Incluir JavaScript externo -->
<script src="/assets/js/brand-page.js"></script>

<!-- Configuración JavaScript -->
<script>
// Configuración de la página
window.brandPageConfig = <?php echo json_encode($config); ?>;
window.brandData = {
    marca: <?php echo json_encode($marca); ?>,
    totalCodes: <?php echo $numero_codigos; ?>,
    userLoggedIn: <?php echo $user_data ? 'true' : 'false'; ?>
};

// Tracking de analytics
<?php if($config['enable_analytics']): ?>
track_analytics_event('brand_page_view', {
    marca: '<?php echo $marca['nombre_clave']; ?>',
    total_codes: <?php echo $numero_codigos; ?>,
    user_logged_in: <?php echo $user_data ? 'true' : 'false'; ?>
});
<?php endif; ?>
</script>

<?php
// Incluir el footer
if (!function_exists('get_footer')) {
    include_once __DIR__ . '/../myphp/_footer.php';
}
get_footer();
?>



