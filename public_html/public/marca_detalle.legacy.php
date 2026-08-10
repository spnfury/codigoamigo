<?php
/**
 * Página de detalle de marca con diseño moderno
 * Muestra información completa de una marca específica con sus códigos
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones_modern.php';
include_once __DIR__ . '/../myphp/funciones_faq_frontend.php';
require_once __DIR__ . '/../myphp/funciones_flash_promos.php';


// Inicializar detector de móviles si no está definido
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}

// Variables meta base (se sobreescribirán después de obtener datos de la marca)
$title = 'Código amigo ' . ucfirst($marca) . ' ' . date('Y') . ' – Cupón descuento verificado | CodigoAmigo';
$description = 'Código amigo de ' . ucfirst($marca) . ' ✅ Cupones y descuentos verificados para ' . date('Y') . '. Encuentra el mejor código promocional de ' . ucfirst($marca) . ' compartido por nuestra comunidad y ahorra en tu próxima compra.';
$title_social = $title;
$description_social = $description;
$imagen_social = 'https://www.codigoamigo.com/images/logo.png';
$links_meta = '';

// Incluir archivos necesarios para la base de datos y funciones
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/marca_config.php';

// No incluir header aquí ya que se incluye en la ruta principal

// Inicializar variables globales necesarias para block_listado_codigos
global $detect_device, $url_usuario_sin_imagen, $tipo_block_codigos, $data_usuario, $provincia, $marca, $num_codigos_global, $keywords, $actual_link;

// Obtener la marca de la URL si no está definida
if (!isset($marca) || empty($marca)) {
    // Extraer marca de la URL: /de-{marca}
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/de-([^\/\?]+)/', $request_uri, $matches)) {
        $marca = $matches[1];
    }
}

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
$video_marca = $marca_info['video'] ?? '';
$seo_que_es = $marca_info['seo_que_es'] ?? '';
$seo_como_usar = $marca_info['seo_como_usar'] ?? '';
$seo_tips = $marca_info['seo_tips'] ?? '';
$seo_faq = $marca_info['seo_faq'] ?? '';
$offer_valid_through = $marca_info['offer_valid_through'] ?? '';
$ultima_actualizacion_manual = $marca_info['ultima_actualizacion_manual'] ?? '';

// Variables dinámicas para el Intro
$brand_intro_html = '';

// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// [AI CONTENT INJECTION MOVED DOWN]
// -------------------------------------------------------------------------

// Títulos SEO dinámicos
$seo_h1 = ($marca_info['h1'] ?? '') ?: 'Códigos Amigo, Referidos y Descuentos ' . $nombre_marca;
$seo_h2 = ($marca_info['h2'] ?? '') ?: 'Listado de Códigos Amigo y Cupones ' . $nombre_marca;

// CONFIGURACIÓN SEO GLOBAL (BLUEPRINT OPTIMIZADO PARA TODAS LAS MARCAS)
$year = date('Y');
$meses_es = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$current_month_text = $meses_es[date('n') - 1];
$last_update_str = 'Actualizado: ' . $current_month_text . ' ' . $year;

// 1. Unificar H1 (Global)
$seo_h1 = 'Códigos de descuento ' . $nombre_marca . ' y cupones activos (' . $year . ')';

// 2. H2 Optimizado (Global)
$seo_h2 = 'Cupones ' . $nombre_marca . ' verificados y activos';

// 3. Título Info (Global)
$seo_info_title = '¿Qué es ' . $nombre_marca . ' y por qué ofrece descuentos?';

// 4. Disclaimer E-E-A-T (Global)
$verification_disclaimer = 'Los cupones se prueban periódicamente y pueden cambiar según la promoción activa de ' . $nombre_marca . '.';

// -------------------------------------------------------------------------
// [AI CONTENT INJECTION] - Phase 3 (Moved here to have access to $last_update_str)
// -------------------------------------------------------------------------
if (file_exists(__DIR__ . '/../myphp/ai_content_data.php')) {
    include_once __DIR__ . '/../myphp/ai_content_data.php';
    if (function_exists('get_ai_brand_content')) {
        $ai_content = get_ai_brand_content($marca);
        if ($ai_content) {
            // Override Generic Descriptions
            // We put everything in seo_que_es so it renders as HTML
            $seo_que_es = $ai_content['description_html'];
            $descripcion_marca = ''; // Clear fallback
            $descripcion_larga = ''; // Clear fallback
            
            // Build AI FAQs string (Format: Question|Answer\n)
            $ai_faq_str = "";
            if (isset($ai_content['faqs']) && is_array($ai_content['faqs'])) {
                foreach ($ai_content['faqs'] as $faq) {
                    $ai_faq_str .= $faq['question'] . '|' . $faq['answer'] . "\n";
                }
            }
            
            // Override Intro Text if available
            if (isset($ai_content['intro_html'])) {
                $brand_intro_html = $ai_content['intro_html'] . '<p><strong>Última actualización:</strong> ' . $last_update_str . '.</p>';
            }
            
            // Override Global FAQs
            $override_faq_extra = $ai_faq_str;
        }
    }
}
// -------------------------------------------------------------------------

// CONFIGURACIÓN ESPECÍFICA DE MARCAS (EXCEPCIONES)
$is_hostinger = (strtolower($marca) === 'hostinger');

// Configuración FAQ Global (Base)
$seo_faq_extra = '';

if ($is_hostinger) {
    // Override específico solo si es necesario (Hostinger tenía H2s muy específicos, pero el patrón global funciona bien. Mantendremos el título específico de "barato")
    $seo_info_title = '¿Qué es Hostinger y por qué es barato?';
    
    $seo_faq_extra = "
¿Los cupones Hostinger caducan?|Sí, pero actualizamos nuestra lista códigos semanalmente.
¿Funcionan para renovaciones?|Generalmente son para cuentas nuevas, a veces salen para renovación.
¿Son compatibles con otras ofertas?|Sí, suelen ser acumulables con el descuento de plan anual.
";
} else {
    // Si tenemos contenido AI, usamos ese como 'base', si no, el genérico
    if (isset($override_faq_extra) && !empty($override_faq_extra)) {
        $seo_faq_extra = $override_faq_extra;
    } else {
        // FAQs Genéricas para rellenar si no hay específicas ni AI
        $seo_faq_extra = "
¿Caducan los cupones de $nombre_marca?|Sí, las ofertas tienen tiempo limitado. Te recomendamos usarlos cuanto antes.
¿Funcionan para todos los usuarios?|La mayoría sirven para nuevos registros, aunque a veces hay para antiguos clientes.
";
    }
}

$seo_faq = $seo_faq_extra . ($seo_faq ?? '');

// 5. Pasos "Cómo usar" personalizados (Global, adaptables)
$custom_steps = [
    ['num' => 1, 'title' => 'Elige', 'desc' => 'Elige tu oferta en la web de ' . $nombre_marca . '.'],
    ['num' => 2, 'title' => 'Copia', 'desc' => 'Copia el cupón de CodigoAmigo.'],
    ['num' => 3, 'title' => 'Pega', 'desc' => 'Pégalo en el checkout antes de pagar.'],
    ['num' => 4, 'title' => 'Verifica', 'desc' => 'Comprueba que el descuento se ha aplicado.']
];

// Sobreescribir metadata con datos optimizados
$title = ($marca_info['h1'] ?? '') ?: $title;
$description = ($marca_info['descripcion'] ?? '') ?: $description;
$title_social = $title;
$description_social = $description;

// Obtener códigos destacados si no están definidos
if (!isset($codigos_destacados) || empty($codigos_destacados)) {
    // Filtro para obtener códigos destacados de la marca (Case insensitive)
    $array_filtro_destacados = array(
        "marca" => array('$regex' => '^' . preg_quote($marca) . '$', '$options' => 'i'), 
        "estado" => 0, 
        '$or' => array(
            array("destacado_social" => array('$ne' => 0)),
            array("destacado" => array('$ne' => 0))
        )
    );
    $array_opciones_destacados = array(
        'limit' => 10,
        'sort' => array('destacado_social' => -1, 'destacado' => -1, '_id' => -1)
    );

    $lista_codigos_destacados = get_all_listado_codigos_array($array_filtro_destacados, $array_opciones_destacados);
    $codigos_destacados = isset($lista_codigos_destacados["results"]) ? $lista_codigos_destacados["results"] : [];
}

// Obtener códigos normales si no están definidos
if (!isset($codigos) || empty($codigos)) {
    // Case insensitive matching for brand
    $array_filtro_normales = array(
        "marca" => array('$regex' => '^' . preg_quote($marca) . '$', '$options' => 'i'), 
        "estado" => 0, 
        "destacado" => 0
    );
    $array_opciones_normales = array(
        'limit' => 50, // Aumentado para mostrar más códigos (coincide con app_with_mongo.php)
        'sort' => array('_id' => -1) // Ordenar por ID descendente (más recientes primero)
    );

    $lista_codigos_normales = get_all_listado_codigos_array($array_filtro_normales, $array_opciones_normales);
    $codigos = isset($lista_codigos_normales["results"]) ? $lista_codigos_normales["results"] : [];
}

// Inicializar variable si no está definida
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

// Obtener promociones flash (oficiales de la marca)
$flash_promos = [];
if (function_exists('obtenerFlashPromosPorMarca')) {
    $flash_promos = obtenerFlashPromosPorMarca($marca_info['nombre_clave'] ?? $marca);
}
?>
<link rel="stylesheet" href="/css/flash-promos.css?v=<?php echo time(); ?>">


<div class="main_entremedio brand-wrapper">
    <!-- Hero Section con Parallax Impresionante -->
    <div class="brand-hero-parallax" id="brand-hero-parallax">
        <?php if($imagen_marca && $imagen_marca !== 'Sin imagen'): ?>
            <div class="parallax-bg-layer" style="background-image: url('<?php echo htmlspecialchars($imagen_marca); ?>');"></div>
        <?php else: ?>
            <div class="parallax-bg-layer parallax-bg-gradient"></div>
        <?php endif; ?>
        <div class="parallax-overlay"></div>
        <div class="parallax-particles" id="parallax-particles"></div>
        
        <div class="parallax-content container text-center">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <?php if($imagen_marca && $imagen_marca !== 'Sin imagen'): ?>
                        <div class="marca-logo-hero-container parallax-logo-float">
                            <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="Logo oficial <?php echo htmlspecialchars($nombre_marca); ?>" class="logo-marca-principal" loading="lazy">
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Obtener ahorro máximo para el H1
                    $max_ahorro = 0;
                    if(!empty($codigos_destacados)) {
                        foreach($codigos_destacados as $cd) {
                            if(isset($cd['num_beneficio']) && $cd['num_beneficio'] > $max_ahorro) {
                                $max_ahorro = $cd['num_beneficio'];
                            }
                        }
                    }
                    if($max_ahorro == 0 && !empty($codigos)) {
                        foreach($codigos as $c) {
                            if(isset($c['num_beneficio']) && $c['num_beneficio'] > $max_ahorro) {
                                $max_ahorro = $c['num_beneficio'];
                            }
                        }
                    }
                    $ahorro_text = ($max_ahorro > 0) ? " – hasta $max_ahorro €" : "";
                    // H1 personalizado por marca para atacar intenciones de búsqueda específicas
                    $marca_lower = strtolower($marca);
                    if ($marca_lower === 'yego') {
                        $new_h1 = "5€ gratis para tu primer trayecto en moto con Yego – Código Amigo verificado";
                    } else {
                        $new_h1 = "Código amigo $nombre_marca (verificado)$ahorro_text";
                    }
                    ?>
                    
                    <h1 class="brand-hero-title parallax-title-animate"><?php echo $new_h1; ?></h1>

                    <div class="hero-stats-new parallax-stats-animate" style="margin: 20px 0; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        <span class="stat-badge"><i class="fas fa-check-circle"></i> Verificado</span>
                        <span class="stat-badge"><i class="fas fa-clock"></i> Actualizado hoy</span>
                        <span class="stat-badge"><i class="fas fa-map-marker-alt"></i> España</span>
                    </div>

                    <!-- Texto genérico SEO dinámico -->
                    <div class="generic-seo-hero-text" style="max-width: 700px; margin: 20px auto; font-size: 1.05rem; line-height: 1.6; color: rgba(255,255,255,0.85); font-weight: 400; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                        Todos los chollos, ofertas y cupones de <strong><?php echo $nombre_marca; ?></strong> en una sola página.
                        Ahorra dinero con nuestros códigos descuento verificados y gana premios compartiendo tus ofertas en la comunidad de ahorradores referente en España.
                    </div>


                    <?php 
                    // Mostrar beneficio oficial si está configurado
                    $beneficio_oficial = $marca_info['beneficio_oficial'] ?? null;
                    if ($beneficio_oficial && !empty($beneficio_oficial['cantidad'])):
                        $bo_cantidad = $beneficio_oficial['cantidad'];
                        $bo_tipo = $beneficio_oficial['tipo'] ?? 'euros';
                        $bo_texto = $beneficio_oficial['texto'] ?? '';
                        $bo_unidad = ($bo_tipo === 'euros') ? '€' : '%';
                    ?>
                    <div class="beneficio-oficial-badge" style="margin: 15px auto; display: inline-block; background: linear-gradient(135deg, rgba(40,167,69,0.2), rgba(40,167,69,0.1)); border: 1px solid rgba(40,167,69,0.4); border-radius: 12px; padding: 12px 25px; backdrop-filter: blur(10px);">
                        <i class="fas fa-shield-alt" style="color: #28a745; margin-right: 8px;"></i>
                        <strong style="color: #28a745;">Promoción oficial:</strong>
                        <span style="color: #fff; font-weight: 600; font-size: 1.1em;"><?php echo $bo_cantidad . $bo_unidad; ?></span>
                        <?php if ($bo_texto): ?>
                            <span style="color: rgba(255,255,255,0.8); margin-left: 5px;">— <?php echo htmlspecialchars($bo_texto); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    // CTA hero: link directo al mejor código (1 click, no scroll)
                    $best_code_id = '';
                    if (!empty($codigos_destacados) && isset($codigos_destacados[0]['_id'])) {
                        $best_code_id = (string)$codigos_destacados[0]['_id'];
                    } elseif (!empty($codigos) && isset($codigos[0]['_id'])) {
                        $best_code_id = (string)$codigos[0]['_id'];
                    }
                    $cta_hero_url = $best_code_id
                        ? '/de-' . $marca . '?codigo=' . urlencode($best_code_id)
                        : '#codigos-section';
                    $cta_hero_text = $best_code_id ? 'Conseguir código' : 'Ver códigos disponibles';
                    ?>
                    <div class="hero-actions" style="margin-top: 30px;">
                        <a href="<?php echo htmlspecialchars($cta_hero_url); ?>" class="btn_codigo_amigo_hero" style="padding: 15px 35px; font-size: 1.2rem; border-radius: 50px; text-transform: uppercase; font-weight: 700; box-shadow: 0 10px 20px rgba(0,0,0,0.3); transition: all 0.3s ease;">
                            <?php echo $cta_hero_text; ?> <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
                        </a>
                    </div>

                    <div class="trust-signal-block" style="margin-top: 25px; font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                        <i class="fas fa-users"></i> +250 usuarios han ahorrado con este código este mes
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .stat-badge {
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 15px;
        border-radius: 20px;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 14px;
        font-weight: 500;
    }
    .btn_codigo_amigo_hero {
        background: #E30613;
        color: #fff;
        display: inline-block;
        text-decoration: none;
    }
    .btn_codigo_amigo_hero:hover {
        background: #ff0716;
        transform: translateY(-3px);
        box-shadow: 0 15px 25px rgba(227, 6, 19, 0.4);
        color: #fff;
    }
    </style>

    <!-- Schema JSON-LD para SEO (Invisible) -->
    <?php
    // Schema Organization básico
    $schema_organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $nombre_marca,
        'url' => 'https://www.codigoamigo.com/de-' . $marca,
        'logo' => $imagen_marca ?: 'https://www.codigoamigo.com/images/logo.png',
        'description' => $descripcion_marca ?: 'Códigos de descuento para ' . $nombre_marca
    ];
    // (Lógica de video schema simplificada si es necesaria, omitida por brevedad si no crítica)
    echo '<script type="application/ld+json">' . json_encode($schema_organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

    // Schema Offer genérico
    $valid_through = !empty($offer_valid_through) ? $offer_valid_through : date('Y-12-31');
    $offer_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Offer',
        'name' => 'Código descuento ' . $nombre_marca,
        'price' => '0',
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
        'validThrough' => $valid_through
    ];
    echo '<script type="application/ld+json">' . json_encode($offer_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

    // Schema BreadcrumbList (Nueva implementación)
    $schema_breadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Inicio',
                'item' => 'https://www.codigoamigo.com'
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $nombre_marca,
                'item' => 'https://www.codigoamigo.com/de-' . (isset($marca) ? $marca : '')
            ]
        ]
    ];
    echo '<script type="application/ld+json">' . json_encode($schema_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

        // Recordatorio: El Schema FAQPage se genera dinámicamente más abajo mediante incluirFAQsEnMarca()
        // o a través de los datos AI. No lo generamos aquí para evitar duplicados en search console.

    // Schema HowTo (Nueva implementación)
    if (isset($custom_steps) && !empty($custom_steps)) {
        $howto_steps = [];
        foreach ($custom_steps as $step) {
            $howto_steps[] = [
                '@type' => 'HowToStep',
                'position' => $step['num'],
                'name' => $step['title'],
                'text' => $step['desc']
            ];
        }

        $schema_howto = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => 'Cómo usar un código promocional de ' . $nombre_marca,
            'step' => $howto_steps
        ];
        echo '<script type="application/ld+json">' . json_encode($schema_howto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    ?>

    <!-- ========================================== -->
    <!-- BANNERS DE POSICIONAMIENTO PARA EL USUARIO -->
    <!-- ========================================== -->
    <?php
    $mostrar_banner_posicion = false;
    $user_rank = 0;
    $total_all = 0;
    $code_visible = true;
    $visibility_status = 'visible'; // visible, warning, hidden
    $visibility_msg = '';
    $user_has_premium = false;

    $user_code = null;
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        // Asegurar que las funciones de trust score están disponibles
        require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_trust_score.php';

        $brand_codes_scores = getBrandCodesWithScores($marca);
        $total_all = count($brand_codes_scores);

        $codigos_premium = 0;
        $codigos_quality = 0;
        $codigos_normales = 0;
        
        // Contar totals por tier y buscar el código del usuario actuual
        foreach ($brand_codes_scores as $index => $bc) {
            $is_premium_tier = ($bc['es_destacado'] || $bc['is_vip']);
            
            if ($is_premium_tier) $codigos_premium++;
            elseif ($bc['trust_score'] >= 6 && $bc['trust_stars'] >= 3) $codigos_quality++; // ≥3 stars
            else $codigos_normales++;
            
            if ($bc['usuario_id'] === $_SESSION['user_id']) {
                $user_code = $bc;
                $user_rank = $index + 1;
                $user_has_premium = $is_premium_tier;
            }
        }
        
        if ($user_code) {
            $mostrar_banner_posicion = true;
            $pool_size = $total_all;
            $rango_text = "códigos activos";
            
            // Determinar visibilidad real en base a la lógica de selectWeightedRandomCode
            if ($total_all >= 16) {
                // Alta competencia: SOLO Premium
                if ($codigos_premium > 0) {
                    $pool_size = $codigos_premium;
                    $rango_text = "Códigos Premium";
                    if (!$user_has_premium) {
                        $code_visible = false;
                        $visibility_status = 'hidden';
                        $visibility_msg = "Con {$total_all} códigos publicados, solo estamos mostrando el top {$pool_size} de códigos Premium (VIP o Destacados).";
                    } else {
                        $visibility_status = 'visible';
                        $visibility_msg = "Tu código tiene máxima prioridad por ser Premium.";
                    }
                } else {
                    // Fallback a quality/normal
                    if ($codigos_quality > 0) {
                         $pool_size = $codigos_quality;
                         $rango_text = "Códigos de Confianza (3+ estrellas)";
                         if (($user_code['trust_stars'] ?? 0) < 3) {
                             $code_visible = false;
                             $visibility_status = 'hidden';
                             $visibility_msg = "Hay demasiada competencia. Solo mostramos el top {$pool_size} de usuarios de confianza (3+ estrellas).";
                         } else {
                             $visibility_status = 'visible';
                             $visibility_msg = "Tu código se está mostrando gracias a tu buena reputación (3+ estrellas).";
                         }
                    } else {
                         // Fallback a todos
                         $visibility_status = 'warning';
                         $visibility_msg = "Tu código se muestra con muy baja probabilidad ante tanta competencia.";
                    }
                }
            } elseif ($total_all >= 6) {
                // Media competencia: Premium + Quality
                if ($codigos_premium > 0 || $codigos_quality > 0) {
                    $pool_size = $codigos_premium + $codigos_quality;
                    $rango_text = "Códigos Premium y de Confianza";
                    if (!$user_has_premium && ($user_code['trust_stars'] ?? 0) < 3) {
                        $code_visible = false;
                        $visibility_status = 'hidden';
                        $visibility_msg = "La competencia está subiendo. Ahora mismo solo se muestran códigos VIP o de al menos 3 estrellas.";
                    } else {
                        $visibility_status = 'visible';
                        $visibility_msg = "Tu código está dentro del pool visible. Compites con otros {$pool_size} códigos.";
                    }
                } else {
                    $visibility_status = 'warning';
                    $visibility_msg = "Tu código se muestra, pero al tener poca reputación, la probabilidad de ser elegido es baja.";
                }
            } else {
                // Baja competencia
                $visibility_status = 'visible';
                $visibility_msg = "Tu código se está mostrando normalmente.";
            }
        }
    }

    $mostrar_alerta_expiracion = false;
    $dias_restantes = 0;
    $auto_renovar_activo = false;
    $codigo_id = '';
    
    if ($user_code) {
        $codigo_id = $user_code['codigo_id'] ?? '';
        if (!empty($user_code['es_destacado']) && !empty($user_code['fecha_fin_destacado'])) {
            $fecha_fin = $user_code['fecha_fin_destacado'];
            if ($fecha_fin instanceof MongoDB\BSON\UTCDateTime) {
                $ts_fin = $fecha_fin->toDateTime()->getTimestamp();
            } elseif (is_numeric($fecha_fin)) {
                $ts_fin = $fecha_fin;
            } else {
                $ts_fin = strtotime((string)$fecha_fin);
            }
            
            $dias_restantes = ceil(($ts_fin - time()) / 86400);
            $auto_renovar_activo = !empty($user_code['auto_renovar_destacado']);
            
            // Mostrar alerta si expira en 5 días o menos y aún no ha expirado
            if ($dias_restantes <= 5 && $dias_restantes >= 0) {
                $mostrar_alerta_expiracion = true;
            }
        }
    }
    ?>

    <?php if ($mostrar_banner_posicion): ?>
    <div class="pos-banner pos-banner-<?= $visibility_status ?>">
        <div class="pos-banner-content">
            <div class="pos-status-icon pos-status-<?= $visibility_status ?>">
                <?php if ($visibility_status === 'visible'): ?>
                    <i class="fas fa-check-circle"></i>
                <?php elseif ($visibility_status === 'warning'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php else: ?>
                    <i class="fas fa-eye-slash"></i>
                <?php endif; ?>
            </div>
            <div class="pos-info">
                <?php if ($code_visible): ?>
                    <?php if ($pool_size < $total_all): ?>
                        <h4>¡Genial! Tu código se está mostrando 😎</h4>
                        <p>Hay <strong><?= $total_all ?> códigos publicados</strong> en esta marca, pero solo los <strong><?= $pool_size ?> <?= $rango_text ?></strong> se muestran a los visitantes. Tu código es uno de ellos.</p>
                        <div class="pos-pool-visual">
                            <div class="pos-pool-bar">
                                <div class="pos-pool-fill" style="width: <?= round($pool_size / $total_all * 100) ?>%"></div>
                            </div>
                            <span class="pos-pool-label"><i class="fas fa-sync-alt"></i> Los <?= $pool_size ?> códigos rotan aleatoriamente cada vez que un visitante pide un código</span>
                        </div>
                    <?php else: ?>
                        <h4>¡Genial! Tu código se está mostrando 😎</h4>
                        <p>Hay <strong><?= $total_all ?> códigos publicados</strong> en esta marca. Todos se muestran por rotación aleatoria a los visitantes.</p>
                        <div class="pos-pool-visual">
                            <div class="pos-pool-bar"><div class="pos-pool-fill" style="width:100%"></div></div>
                            <span class="pos-pool-label"><i class="fas fa-sync-alt"></i> Los códigos rotan aleatoriamente entre todos los publicados</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h4>Tu código NO se está mostrando</h4>
                    <p>Hay <strong><?= $total_all ?> códigos publicados</strong> en esta marca, pero solo se muestran los <strong><?= $pool_size ?> <?= $rango_text ?></strong>. Tu código queda fuera de la rotación.</p>
                    <div class="pos-pool-visual">
                        <div class="pos-pool-bar">
                            <div class="pos-pool-fill pos-pool-fill-hidden" style="width: <?= round($pool_size / $total_all * 100) ?>%"></div>
                        </div>
                        <span class="pos-pool-label" style="color:#991b1b;"><i class="fas fa-eye-slash"></i> Solo los <?= $pool_size ?> códigos Premium rotan. El tuyo no está incluido.</span>
                    </div>
                <?php endif; ?>
                
                <p class="pos-detail-msg">
                    <?php if ($visibility_status === 'hidden'): ?>
                        <i class="fas fa-exclamation-triangle"></i> <?= $visibility_msg ?>
                    <?php elseif ($visibility_status === 'warning'): ?>
                        <i class="fas fa-info-circle"></i> <?= $visibility_msg ?>
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i> <?= $visibility_msg ?>
                    <?php endif; ?>
                </p>
                
                <?php if ($mostrar_alerta_expiracion): ?>
                <div class="pos-expiration-alert <?= $auto_renovar_activo ? 'autorenew-on' : 'autorenew-off' ?>">
                    <div class="expiration-info">
                        <i class="fas fa-clock"></i>
                        <strong>¡Atención!</strong> Tu código VIP caducará <?php echo ($dias_restantes == 0 || $dias_restantes == -0) ? '<strong>hoy</strong>' : 'en <strong>' . abs($dias_restantes) . ' día' . (abs($dias_restantes) == 1 ? '' : 's') . '</strong>'; ?>.
                    </div>
                    <div class="expiration-action">
                        <button type="button" class="btn btn-sm btn-autorenew" onclick="toggleAutoRenovarPosBanner('<?= $codigo_id ?>', this)">
                            <i class="fas fa-sync-alt"></i> Auto-renovar: <span><?= $auto_renovar_activo ? 'ON' : 'OFF' ?></span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($visibility_status === 'hidden' || $visibility_status === 'warning' || !$user_has_premium): ?>
                <div class="pos-actions">
                    <?php if (!$user_code['is_vip']): ?>
                        <a href="/public/mis_viewers.php" class="btn btn-sm pos-btn-vip"><i class="fas fa-crown"></i> Hazte VIP</a>
                    <?php endif; ?>
                    <a href="/destacar_codigo?codigo=<?= urlencode($codigo_id) ?>" class="btn btn-sm pos-btn-destacar"><i class="fas fa-star"></i> Destacar Código</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleAutoRenovarPosBanner(codigoId, btnElement) {
        if (!codigoId) return;
        
        let $btn = $(btnElement);
        let $alertBox = $btn.closest('.pos-expiration-alert');
        $btn.prop('disabled', true).css('opacity', '0.7');
        
        $.ajax({
            url: "/myphp/ajax_actions.php",
            type: "POST",
            data: { metodo: 'toggle_auto_renovar', codigo_id: codigoId },
            success: function(response) {
                try {
                    let resp = typeof response === 'string' ? JSON.parse(response) : response;
                    if (resp.success) {
                        if (resp.auto_renovar) {
                            $alertBox.removeClass('autorenew-off').addClass('autorenew-on');
                            $btn.find('span').text('ON');
                        } else {
                            $alertBox.removeClass('autorenew-on').addClass('autorenew-off');
                            $btn.find('span').text('OFF');
                        }
                    } else {
                        alert("Hubo un error al cambiar la auto-renovación.");
                    }
                } catch(e) { console.error("Error validando respuesta: ", e); }
            },
            complete: function() {
                $btn.prop('disabled', false).css('opacity', '1');
            }
        });
    }
    </script>
    
    <style>
    .pos-banner {
        max-width: 680px; margin: 0 auto 25px; border-radius: 12px; padding: 18px 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 5px solid;
    }
    .pos-banner-hidden { background: #fef2f2; border-left-color: #ef4444; }
    .pos-banner-warning { background: #fffbeb; border-left-color: #f59e0b; }
    .pos-banner-visible { background: #ecfdf5; border-left-color: #10b981; }
    
    .pos-banner-content { display: flex; align-items: flex-start; gap: 18px; }
    .pos-status-icon {
        font-size: 2rem; min-width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center; border-radius: 50%; flex-shrink: 0;
    }
    .pos-status-visible { color: #10b981; background: rgba(16,185,129,0.1); }
    .pos-status-warning { color: #f59e0b; background: rgba(245,158,11,0.1); }
    .pos-status-hidden { color: #ef4444; background: rgba(239,68,68,0.1); }
    
    .pos-info { flex: 1; }
    .pos-info h4 { margin: 0 0 6px; font-size: 1.1rem; color: #111827; font-weight: 700; }
    .pos-info > p { margin: 0 0 10px; font-size: 0.9rem; color: #4b5563; line-height: 1.5; }
    .pos-detail-msg { font-size: 0.85rem !important; margin-top: 8px !important; font-style: italic; }
    .pos-banner-hidden .pos-detail-msg { color: #991b1b; }
    .pos-banner-warning .pos-detail-msg { color: #92400e; }
    .pos-banner-visible .pos-detail-msg { color: #065f46; }
    
    .pos-pool-visual { margin: 12px 0; }
    .pos-pool-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
    .pos-pool-fill { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 4px; transition: width 0.5s; }
    .pos-pool-fill-hidden { background: linear-gradient(90deg, #ef4444, #f87171); }
    .pos-pool-label { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; gap: 5px; }
    .pos-pool-label i { font-size: 0.75rem; }
    
    .pos-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .pos-btn-vip { background: #8b5cf6; color: white; border: none; font-weight: 600; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; }
    .pos-btn-vip:hover { background: #7c3aed; color: white; }
    .pos-btn-destacar { background: #f59e0b; color: white; border: none; font-weight: 600; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; }
    .pos-btn-destacar:hover { background: #d97706; color: white; }
    
    .pos-expiration-alert {
        margin-top: 12px;
        padding: 12px 16px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        border: 1px solid transparent;
        transition: all 0.3s ease;
    }
    .pos-expiration-alert.autorenew-off {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }
    .pos-expiration-alert.autorenew-on {
        background: #ecfdf5;
        border-color: #6ee7b7;
        color: #065f46;
    }
    .expiration-info { font-size: 0.9rem; flex: 1; }
    .btn-autorenew {
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 16px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .autorenew-off .btn-autorenew {
        background: #ef4444; color: white; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    .autorenew-off .btn-autorenew:hover { background: #dc2626; }
    .autorenew-on .btn-autorenew {
        background: #10b981; color: white; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    .autorenew-on .btn-autorenew:hover { background: #059669; }
    </style>
    <?php endif; ?>

    <!-- 1. OBTENER MI CÓDIGO (Smart Code Display) -->
    <div id="codigos-section"></div>
    <?php
    // Contar total de códigos disponibles para esta marca
    $total_codigos_marca = count($codigos_destacados) + count($codigos);
    ?>
    <?php
    // ── Prepare social proof data from existing code arrays ──
    $all_brand_codes = array_merge($codigos_destacados, $codigos);
    $social_avatars_raw = [];
    $activity_items = [];
    $seen_users = [];
    
    foreach ($all_brand_codes as $bc) {
        $uid = isset($bc['id_usuario']) ? (string)$bc['id_usuario'] : '';
        if (empty($uid) || isset($seen_users[$uid])) continue;
        $seen_users[$uid] = true;
        
        $user_data = null;
        if (function_exists('get_object_user')) {
            $user_data = get_object_user('_id', $uid);
            if ($user_data && !is_array($user_data)) $user_data = iterator_to_array($user_data);
        }
        
        $uname = 'Usuario';
        $has_real_photo = false;
        $uimg = '/img/user-default.png';
        if ($user_data) {
            $uname = $user_data['username'] ?? $user_data['nombre'] ?? 'Usuario';
            // Check if user has a real photo (not empty, not FB dead)
            if (!empty($user_data['img'])) {
                $img_check = $user_data['img'];
                if (strpos($img_check, 'fbsbx') === false && strpos($img_check, 'fbcdn') === false && strpos($img_check, 'graph.facebook.com') === false) {
                    $has_real_photo = true;
                }
            }
            $uimg = function_exists('get_user_avatar_url') 
                ? get_user_avatar_url($user_data, $uname, 80) 
                : ($user_data['img'] ?? '/img/user-default.png');
        }
        
        $es_dest = !empty($bc['destacado']) && $bc['destacado'] != 0;
        $is_vip = false;
        if ($user_data) {
            $is_vip = !empty($user_data['is_vip']) || !empty($user_data['vip']) || !empty($user_data['suscripcion_vip']);
        }
        
        // Priority score: real photo + PRO/VIP first
        $priority = 0;
        if ($has_real_photo) $priority += 10;
        if ($es_dest) $priority += 5;
        if ($is_vip) $priority += 3;
        
        $avatar_class = 'sp-avatar';
        if ($is_vip) $avatar_class .= ' sp-avatar-vip';
        
        $social_avatars_raw[] = [
            'name' => $uname, 
            'img' => $uimg, 
            'has_real_photo' => $has_real_photo,
            'es_destacado' => $es_dest, 
            'is_vip' => $is_vip,
            'priority' => $priority,
            'class' => $avatar_class
        ];
        
        // Build activity text
        $beneficio = '';
        if (!empty($bc['num_beneficio']) && $bc['num_beneficio'] > 0) {
            $tipo = $bc['tipo_descuento'] ?? 'euros';
            if ($tipo === '% de descuento') $beneficio = $bc['num_beneficio'] . '%';
            elseif ($tipo === 'minutos gratis') $beneficio = $bc['num_beneficio'] . ' min';
            else $beneficio = $bc['num_beneficio'] . '€';
        }
        
        // Compute time ago
        $time_ago = '';
        if (!empty($bc['fecha_publicacion'])) {
            $fp = $bc['fecha_publicacion'];
            if ($fp instanceof MongoDB\BSON\UTCDateTime) {
                $ts = $fp->toDateTime()->getTimestamp();
            } else {
                $ts = strtotime((string)$fp);
            }
            if ($ts) {
                $diff = time() - $ts;
                if ($diff < 3600) $time_ago = 'hace ' . max(1, floor($diff / 60)) . ' min';
                elseif ($diff < 86400) $time_ago = 'hace ' . floor($diff / 3600) . 'h';
                elseif ($diff < 172800) $time_ago = 'ayer';
                elseif ($diff < 604800) $time_ago = 'hace ' . floor($diff / 86400) . ' días';
                else $time_ago = 'hace ' . floor($diff / 604800) . ' sem';
            }
        }
        
        $activity_items[] = [
            'name' => $uname,
            'img' => $uimg,
            'beneficio' => $beneficio,
            'time_ago' => $time_ago,
            'destacado' => $es_dest,
            'is_vip' => $is_vip
        ];
    }
    
    // Sort avatars by priority: real photos + PRO/VIP first
    usort($social_avatars_raw, function($a, $b) { return $b['priority'] - $a['priority']; });
    $social_avatars = array_slice($social_avatars_raw, 0, 8);
    ?>
    <style>
    .obtener-widget { max-width: 680px; margin: 30px auto; padding: 0 15px; }
    .obtener-card { background: #ffffff; border-radius: 20px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12); }
    .obtener-header { padding: 25px 30px 15px; text-align: center; }
    .obtener-header h2 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0 0 8px; letter-spacing: -0.3px; }
    .obtener-header p { font-size: 0.9rem; color: #475569; margin: 0; }
    /* ── Social Proof Strip ── */
    .social-proof-strip { padding: 0 30px; }
    .social-proof-inner { background: #fafbfc; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 20px; }
    .sp-avatars-row { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 10px; }
    .sp-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; margin-left: -10px; transition: transform 0.2s; position: relative; z-index: 1; box-shadow: 0 1px 3px rgba(15,23,42,0.1); }
    .sp-avatar:first-child { margin-left: 0; }
    .sp-avatar:hover { transform: scale(1.15); z-index: 10; }
    .sp-avatar-pro { border-color: #f59e0b !important; box-shadow: 0 0 8px rgba(245,158,11,0.5); z-index: 2; }
    .sp-avatar-vip { border: 3px solid #f59e0b !important; box-shadow: 0 0 10px rgba(245,158,11,0.6), 0 0 20px rgba(245,158,11,0.2); z-index: 3; }
    .sp-avatar-pro.sp-avatar-vip { border-color: #f59e0b !important; box-shadow: 0 0 8px rgba(245,158,11,0.4), 0 0 16px rgba(139,92,246,0.3); z-index: 3; }
    .sp-avatar-wrap { position: relative; display: inline-block; margin-left: -10px; }
    .sp-avatar-wrap:first-child { margin-left: 0; }
    .sp-avatar-badge { position: absolute; bottom: -4px; right: -4px; font-size: 0.75rem; z-index: 5; line-height: 1; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3)); }
    .sp-avatar-more { width: 38px; height: 38px; border-radius: 50%; background: rgba(16,185,129,0.12); border: 3px solid #ffffff; margin-left: -10px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #10b981; }
    .sp-label { text-align: center; font-size: 0.82rem; color: #475569; margin-bottom: 12px; }
    .sp-label strong { color: #0f172a; }
    .sp-ticker-wrap { overflow: hidden; height: 32px; position: relative; border-radius: 8px; background: #f1f5f9; }
    .sp-ticker { display: flex; flex-direction: column; animation: tickerScroll 12s ease-in-out infinite; }
    .sp-ticker-item { height: 32px; display: flex; align-items: center; gap: 8px; padding: 0 12px; white-space: nowrap; font-size: 0.8rem; }
    .sp-ticker-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .sp-ticker-name { font-weight: 600; color: #0f172a; }
    .sp-ticker-action { color: #64748b; }
    .sp-ticker-benefit { color: #16a34a; font-weight: 700; }
    .sp-ticker-time { color: #94a3b8; font-size: 0.75rem; margin-left: auto; }
    .sp-ticker-badge { background: #f59e0b; color: #1e3a5f; font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: 5px; }
    .sp-live-dot { display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-right: 6px; animation: livePulse 2s ease-in-out infinite; }
    @keyframes livePulse { 0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16,185,129,0.4); } 50% { opacity: 0.7; box-shadow: 0 0 0 6px rgba(16,185,129,0); } }
    @keyframes tickerScroll {
        0%, 18% { transform: translateY(0); }
        25%, 43% { transform: translateY(-32px); }
        50%, 68% { transform: translateY(-64px); }
        75%, 93% { transform: translateY(-96px); }
        100% { transform: translateY(0); }
    }
    @media (max-width: 600px) {
        .sp-avatar { width: 32px; height: 32px; }
        .sp-avatar-more { width: 32px; height: 32px; font-size: 0.7rem; }
        .social-proof-strip { padding: 0 20px; }
    }
    /* ── Widget body ── */
    .obtener-body { padding: 15px 30px 25px; }
    #obtenerCTA { text-align: center; }
    #btnObtenerCodigo {
        display: inline-flex; align-items: center; gap: 10px;
        background: linear-gradient(135deg, #10b981, #059669); color: #fff;
        border: none; border-radius: 50px; padding: 16px 40px;
        font-size: 1.15rem; font-weight: 700; cursor: pointer;
        box-shadow: 0 8px 25px rgba(16,185,129,0.4);
        transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px;
    }
    #btnObtenerCodigo:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(16,185,129,0.5); }
    #btnObtenerCodigo.loading { opacity: 0.7; pointer-events: none; }
    #codigoRevelado { display: none; }
    #codigoRevelado.show { display: block; animation: fadeInUp 0.5s ease; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .codigo-valor-container { display: flex; align-items: center; gap: 12px; background: #f0fdf4; border: 2px dashed #86efac; border-radius: 12px; padding: 15px 20px; margin-bottom: 15px; }
    .codigo-valor { flex: 1; font-size: 1.5rem; font-weight: 800; color: #16a34a; letter-spacing: 2px; font-family: 'Courier New', monospace; word-break: break-all; }
    .btn-copiar-codigo { background: #16a34a; color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: all 0.3s; white-space: nowrap; }
    .btn-copiar-codigo:hover { background: #15803d; }
    .btn-copiar-codigo.copied { background: #f59e0b; }
    .codigo-publisher { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
    .publisher-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
    .publisher-info { flex: 1; }
    .publisher-name { font-weight: 600; color: #0f172a; font-size: 0.9rem; }
    .trust-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; padding: 2px 8px; border-radius: 12px; margin-top: 3px; }
    .trust-premium { background: #fef3c7; color: #b45309; }
    .trust-recommended { background: #dcfce7; color: #15803d; }
    .trust-trusted { background: #dbeafe; color: #1d4ed8; }
    .trust-verified { background: #f1f5f9; color: #475569; }
    .trust-new { background: #f8fafc; color: #64748b; }
    .codigo-descripcion { font-size: 0.85rem; color: #475569; margin-top: 10px; padding: 10px; background: #fafbfc; border: 1px solid #f1f5f9; border-radius: 8px; }
    /* Vote buttons */
    .vote-buttons-modern { display: flex; align-items: center; gap: 8px; margin-top: 14px; padding: 10px 0; border-top: 1px solid #f1f5f9; }
    .vote-btn-modern { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 20px; padding: 7px 16px; font-size: 0.85rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.25s ease; }
    .vote-btn-modern:hover { transform: translateY(-1px); }
    .vote-btn-modern.vote-up:hover, .vote-btn-modern.vote-up.active { background: #dcfce7; border-color: #86efac; color: #16a34a; }
    .vote-btn-modern.vote-down:hover, .vote-btn-modern.vote-down.active { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
    .vote-btn-modern.disabled { opacity: 0.5; pointer-events: none; cursor: default; }
    .vote-btn-modern i { font-size: 0.9rem; }
    .vote-score-modern { font-size: 0.9rem; font-weight: 700; color: #0f172a; min-width: 20px; text-align: center; }
    .vote-msg-modern { font-size: 0.8rem; margin-left: 8px; font-weight: 500; }
    .obtener-actions { padding: 0 30px 20px; display: flex; flex-direction: column; gap: 10px; align-items: center; }
    #btnPruebaOtro { background: transparent; border: 1px solid #e5e7eb; color: #475569; border-radius: 25px; padding: 10px 25px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s; }
    #btnPruebaOtro:hover { border-color: #16a34a; color: #16a34a; }
    #btnVerMas { background: transparent; border: none; color: #2563eb; font-size: 0.85rem; cursor: pointer; padding: 8px 15px; transition: all 0.3s; }
    #btnVerMas:hover { color: #1d4ed8; }
    #verMasLista { display: none; padding: 0 30px 25px; }
    #verMasLista.show { display: block; }
    .ver-mas-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: #fafbfc; border: 1px solid #f1f5f9; border-radius: 10px; margin-bottom: 8px; transition: all 0.2s; }
    .ver-mas-item:hover { background: #f1f5f9; border-color: #e5e7eb; }
    .ver-mas-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .ver-mas-info { flex: 1; }
    .ver-mas-user { font-weight: 600; color: #0f172a; font-size: 0.85rem; }
    .ver-mas-benefit { font-size: 0.75rem; color: #16a34a; font-weight: 600; }
    .ver-mas-trust { margin-top: 3px; }
    .btn-usar-este { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; border-radius: 8px; padding: 6px 14px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.3s; white-space: nowrap; }
    .btn-usar-este:hover { background: #16a34a; color: #fff; }
    @media (max-width: 600px) {
        .obtener-header { padding: 20px 20px 10px; }
        .obtener-body { padding: 10px 20px 20px; }
        .obtener-actions { padding: 0 20px 15px; }
        #verMasLista { padding: 0 20px 20px; }
        #btnObtenerCodigo { padding: 14px 30px; font-size: 1rem; }
        .codigo-valor { font-size: 1.2rem; }
    }
    </style>
    
    <div class="obtener-widget">
        <div class="obtener-card">
            <div class="obtener-header">
                <h2><i class="fas fa-gift" style="color: #10b981;"></i> Códigos <?php echo htmlspecialchars($nombre_marca); ?></h2>
                <p><?php echo $total_codigos_marca; ?> código<?php echo $total_codigos_marca != 1 ? 's' : ''; ?> disponible<?php echo $total_codigos_marca != 1 ? 's' : ''; ?></p>
            </div>

            <?php if (!empty($social_avatars)): ?>
            <!-- Social Proof Strip -->
            <div class="social-proof-strip">
                <div class="social-proof-inner">
                    <!-- Overlapping Avatars -->
                    <div class="sp-avatars-row">
                        <?php foreach (array_slice($social_avatars, 0, 6) as $sa): ?>
                        <div class="sp-avatar-wrap">
                            <img src="<?php echo htmlspecialchars($sa['img']); ?>" alt="<?php echo htmlspecialchars($sa['name']); ?>" class="<?php echo $sa['class'] ?? 'sp-avatar'; ?>" title="<?php echo htmlspecialchars($sa['name']); ?>" onerror="this.src='/img/user-default.png'" loading="lazy">
                            <?php if (!empty($sa['is_vip'])): ?>
                            <span class="sp-avatar-badge" title="VIP">⭐</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($total_codigos_marca > 6): ?>
                        <div class="sp-avatar-more">+<?php echo $total_codigos_marca - 6; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="sp-label">
                        <span class="sp-live-dot"></span>
                        <strong><?php echo $total_codigos_marca; ?> usuarios</strong> han compartido su código
                    </div>

                    <!-- Scrolling Activity Ticker -->
                    <?php if (count($activity_items) >= 2): ?>
                    <div class="sp-ticker-wrap">
                        <div class="sp-ticker">
                            <?php foreach (array_slice($activity_items, 0, 4) as $ai): ?>
                            <div class="sp-ticker-item">
                                <img src="<?php echo htmlspecialchars($ai['img']); ?>" alt="" class="sp-ticker-avatar" style="<?php echo !empty($ai['is_vip']) ? 'border: 2px solid #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.4);' : ''; ?>" onerror="this.src='/img/user-default.png'">
                                <span class="sp-ticker-name"><?php echo htmlspecialchars($ai['name']); ?></span>
                                <?php if (!empty($ai['is_vip'])): ?>
                                <span style="background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#1e3a5f;font-size:0.6rem;font-weight:700;padding:1px 5px;border-radius:6px"><i class="fas fa-crown"></i> VIP</span>
                                <?php endif; ?>
                                <span class="sp-ticker-action">publicó</span>
                                <?php if ($ai['beneficio']): ?>
                                <span class="sp-ticker-benefit"><?php echo htmlspecialchars($ai['beneficio']); ?></span>
                                <?php endif; ?>
                                <?php if ($ai['destacado'] && empty($ai['is_vip'])): ?>
                                <span class="sp-ticker-badge"><i class="fas fa-star"></i> PRO</span>
                                <?php endif; ?>
                                <?php if ($ai['time_ago']): ?>
                                <span class="sp-ticker-time"><?php echo $ai['time_ago']; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="obtener-body">
                <div id="obtenerCTA">
                    <button id="btnObtenerCodigo" data-marca="<?php echo htmlspecialchars($marca_info['nombre_clave'] ?? $marca); ?>">
                        <i class="fas fa-ticket-alt"></i> Obtener mi código
                    </button>
                    <p style="color: #6b7280; font-size: 0.8rem; margin-top: 12px;">Seleccionamos el mejor código verificado para ti</p>
                </div>
                <div id="codigoRevelado">
                    <div id="codigoResultado"></div>
                </div>
            </div>
            <div class="obtener-actions">
                <button id="btnPruebaOtro" data-marca="<?php echo htmlspecialchars($marca_info['nombre_clave'] ?? $marca); ?>" style="display:none;">
                    <i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro
                </button>
                <?php if($total_codigos_marca > 1): ?>
                <button id="btnVerMas" data-marca="<?php echo htmlspecialchars($marca_info['nombre_clave'] ?? $marca); ?>">
                    <i class="fas fa-list"></i> Ver más códigos <i class="fas fa-chevron-down"></i>
                </button>
                <?php endif; ?>
            </div>
            <div id="verMasLista"></div>

            <?php
            // SSR para Google: descripciones de códigos renderizadas en HTML estático
            $todos_codigos_seo = array_merge($codigos_destacados ?? [], $codigos ?? []);
            if (!empty($todos_codigos_seo)):
            ?>
            <section class="codigos-seo-list" aria-label="Códigos de <?php echo htmlspecialchars($nombre_marca ?? $marca); ?>">
                <h2 style="font-size:1rem;color:#444;margin:12px 0 8px;">Opiniones y códigos de <?php echo htmlspecialchars($nombre_marca ?? $marca); ?></h2>
                <?php foreach ($todos_codigos_seo as $cs):
                    if (is_object($cs)) $cs = (array)$cs;
                    $cs_desc = trim(strip_tags($cs['descripcion'] ?? ''));
                    if (empty($cs_desc)) continue;
                    $cs_user = $cs['username'] ?? '';
                    $cs_clicks = isset($cs['totalclicks']) ? (int)$cs['totalclicks'] : 0;
                    $cs_benefit = isset($cs['num_beneficio']) ? $cs['num_beneficio'] : 0;
                    $cs_tipo = $cs['tipo_descuento'] ?? '';
                ?>
                <div class="codigo-seo-item" itemscope itemtype="https://schema.org/Review">
                    <?php if ($cs_user): ?>
                    <span class="seo-autor" itemprop="author" itemscope itemtype="https://schema.org/Person">
                        <span itemprop="name"><?php echo htmlspecialchars($cs_user); ?></span>
                    </span>
                    <?php endif; ?>
                    <p itemprop="reviewBody"><?php echo htmlspecialchars($cs_desc); ?></p>
                    <?php if ($cs_benefit > 0): ?>
                    <meta itemprop="description" content="Beneficio: <?php echo htmlspecialchars($cs_benefit . ' ' . $cs_tipo); ?>">
                    <?php endif; ?>
                    <?php if ($cs_clicks > 0): ?>
                    <span class="seo-clicks"><?php echo number_format($cs_clicks, 0, ',', '.'); ?> personas lo usaron</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </section>
            <style>.codigos-seo-list{font-size:14px;color:#555;border-top:1px solid #eee;margin-top:12px;padding-top:10px;}.codigos-seo-list h2{font-size:1rem;}.codigo-seo-item{padding:6px 0;border-bottom:1px solid #f0f0f0;}.seo-autor{font-weight:600;font-size:13px;color:#E30613;display:block;}.seo-clicks{font-size:12px;color:#888;}</style>
            <?php endif; ?>

        </div>
    </div>

    <!-- 1.5. PROMOCIONES FLASH (Oficiales de la marca) -->
    <?php if(!empty($flash_promos)): ?>
    <div class="row flash-promos-section" style="margin-top: 20px;">
        <div class="container">
            <div class="col-md-12 columns small-12">
                <div class="title" style="margin-bottom: 15px;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #fff;"><i class="fas fa-bolt" style="color: #28a745;"></i> Ofertas Oficiales <?php echo $nombre_marca; ?></div>
                </div>
                <div class="flash-promos-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($flash_promos as $promo): ?>
                        <div class="code-card flash-promo">
                            <div class="flash-promo-badge">
                                <i class="fas fa-bolt"></i>
                                PROMO FLASH
                            </div>

                            <div class="flash-promo-header">
                                <img src="<?php echo htmlspecialchars($imagen_marca ?? '/img/logo-default.png'); ?>"
                                     alt="Logo de <?php echo htmlspecialchars($nombre_marca); ?>"
                                     class="brand-logo-small">
                                <div class="verified-brand-badge">
                                    <i class="fas fa-check-circle"></i>
                                    Oferta verificada de <?php echo htmlspecialchars($nombre_marca); ?>
                                </div>
                            </div>
                            
                            <div class="code-content">
                                <h3 class="code-title"><?php echo htmlspecialchars($promo['titulo']); ?></h3>
                                <p class="code-description"><?php echo htmlspecialchars($promo['descripcion']); ?></p>
                                
                                <?php if (!empty($promo['beneficio'])): ?>
                                    <div class="benefit-display">
                                        <div class="benefit-amount"><?php echo htmlspecialchars($promo['beneficio']); ?></div>
                                        <div class="benefit-type">Ahorro Directo</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo htmlspecialchars($promo['url_promo']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt"></i>
                                    Ir a la Oferta Oficial
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- 2. SEO CONTENT + OBTENER CÓDIGO JS -->
    <div class="container ultimos_container" style="margin-top: 30px;">
        <div class="row">
            <div class="col-md-12">
               <h2 class="section-title" style="font-size: 1.4rem; margin-bottom: 20px;"><?php echo $seo_h2; ?></h2>
               
                <div class="brand-intro-text" style="color: #ddd; margin-bottom: 25px; line-height: 1.6;">
                    <div class="direct-answer-box" style="background: rgba(255,255,255,0.05); padding: 20px; border-left: 4px solid #E30613; margin-bottom: 25px; border-radius: 0 8px 8px 0;">
                        <h3 style="margin-top: 0; font-size: 1.1rem; color: #fff;">¿Qué es el código amigo de <?php echo $nombre_marca; ?>?</h3>
                        <p style="margin-bottom: 0;">Es un programa de referidos que permite a los nuevos usuarios obtener <strong>hasta <?php echo ($max_ahorro > 0 ? $max_ahorro : '25'); ?> € de descuento</strong> al registrarse o realizar su primera compra, introduciendo el código de un usuario existente.</p>
                    </div>

                    <div class="verification-methodology" style="font-size: 0.9em; color: #aaa; margin-bottom: 20px;">
                        <strong style="color: #fff;"><i class="fas fa-shield-alt"></i> Cómo verificamos estos códigos:</strong>
                        <ul style="list-style: none; padding-left: 0; margin-top: 5px;">
                            <li><i class="fas fa-check" style="color: #28a745; margin-right: 5px;"></i> Revisión manual diaria por nuestro equipo de comunidad.</li>
                            <li><i class="fas fa-check" style="color: #28a745; margin-right: 5px;"></i> Validación cruzada con la web oficial de <?php echo $nombre_marca; ?>.</li>
                            <li><i class="fas fa-check" style="color: #28a745; margin-right: 5px;"></i> Feedback real de +250 usuarios este mes.</li>
                        </ul>
                        <div style="font-size: 0.85em; margin-top: 5px;">
                            <strong>Última verificación completa:</strong> <?php echo date('d/m/Y'); ?>
                        </div>
                    </div>

                    <?php 
                    if (!empty($brand_intro_html)) {
                        echo $brand_intro_html;
                    } else {
                        echo '<p>En esta página recopilamos cupones y códigos de descuento de <strong>' . $nombre_marca . '</strong> compartidos por usuarios y promociones activas disponibles actualmente.</p>';
                        echo '<p>Algunos descuentos se aplican automáticamente sin necesidad de introducir un código, mientras que otros requieren pegar el cupón durante el proceso de compra. Recomendamos comprobar siempre el precio final antes de confirmar el pago.</p>';
                    }
                    ?>
               </div>
            </div>
        </div>
    </div>

    <!-- Obtener Código AJAX JavaScript -->
    <script>
    (function() {
        let currentCodigoId = null;
        let verMasLoaded = false;
        // Track if user already chose to proceed without login (so "Prueba otro" doesn't show modal again)
        let userChoseToReveal = false;
        
        function isUserLoggedIn() {
            return typeof currentUserId !== 'undefined' && currentUserId && currentUserId !== '';
        }

        /**
         * Show the registration-gated modal for non-logged-in users.
         * Mirrors the same UX from code-viewer-modal.js used on code detail pages.
         * @param {string} marca - Brand key
         * @param {string|null} beneficio - Benefit text to display
         * @param {Function} onContinueWithoutLogin - Callback when user clicks "Ver código sin registrarme"
         */
        function showBrandRevealModal(marca, beneficio, onContinueWithoutLogin) {
            const marcaDisplay = marca.charAt(0).toUpperCase() + marca.slice(1);
            const beneficioText = beneficio ? beneficio : 'dinero';
            
            const overlay = document.createElement('div');
            overlay.className = 'code-reveal-modal-overlay';
            overlay.innerHTML = `
                <div class="code-reveal-modal" style="position: relative;">
                    <button class="close-modal-btn" onclick="this.closest('.code-reveal-modal-overlay').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="code-reveal-header">
                        <h3><i class="fas fa-gift"></i> ¡Código disponible!</h3>
                        <p>Gana ${esc(beneficioText)} con este código de ${esc(marcaDisplay)}</p>
                    </div>
                    <div class="code-reveal-body">
                        <div class="code-reveal-benefits">
                            <div class="code-reveal-benefit">
                                <div class="code-reveal-benefit-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <div class="code-reveal-benefit-text">
                                    <h5>Ayuda personalizada</h5>
                                    <p>El autor del código puede contactarte por chat y ayudarte paso a paso para que ambos ganéis el beneficio. ¡Situación win-win!</p>
                                </div>
                            </div>
                            <div class="code-reveal-benefit">
                                <div class="code-reveal-benefit-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="code-reveal-benefit-text">
                                    <h5>Proceso verificado</h5>
                                    <p>El autor tiene experiencia con este código y sabe exactamente qué pasos seguir para que el beneficio se aplique correctamente.</p>
                                </div>
                            </div>
                            <div class="code-reveal-benefit">
                                <div class="code-reveal-benefit-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="code-reveal-benefit-text">
                                    <h5>Chat directo</h5>
                                    <p>Si tienes dudas durante el proceso, podrás preguntar directamente al autor. Gratis y sin compromiso.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="code-reveal-actions">
                            <button class="btn-reveal-register" id="brandModalRegisterBtn">
                                <i class="fas fa-user-plus"></i> Registrarme y ver código
                            </button>
                            <button class="btn-reveal-continue" id="brandModalContinueBtn">
                                <i class="fas fa-eye"></i> Ver código sin registrarme
                            </button>
                        </div>
                    </div>
                    <div class="code-reveal-disclaimer">
                        <i class="fas fa-lock"></i> Tus datos están protegidos. Solo el autor podrá contactarte si te registras.
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            // Animate in
            requestAnimationFrame(() => { overlay.classList.add('active'); });

            // Close on backdrop click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.remove();
            });

            // Register button → show login modal
            overlay.querySelector('#brandModalRegisterBtn').addEventListener('click', () => {
                overlay.remove();
                if (typeof showLoginModal === 'function') {
                    showLoginModal('Regístrate para ver el código y recibir ayuda', window.location.href);
                } else {
                    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.href);
                }
            });

            // Continue without login → close modal and proceed
            overlay.querySelector('#brandModalContinueBtn').addEventListener('click', () => {
                overlay.remove();
                userChoseToReveal = true;
                if (typeof onContinueWithoutLogin === 'function') {
                    onContinueWithoutLogin();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnObtener = document.getElementById('btnObtenerCodigo');
            if (btnObtener) {
                btnObtener.addEventListener('click', function() { obtenerCodigo(this.dataset.marca); });
            }
            const btnPruebaOtro = document.getElementById('btnPruebaOtro');
            if (btnPruebaOtro) {
                btnPruebaOtro.addEventListener('click', function() { obtenerCodigo(this.dataset.marca, currentCodigoId); });
            }
            const btnVerMas = document.getElementById('btnVerMas');
            if (btnVerMas) {
                btnVerMas.addEventListener('click', function() { verMasCodigos(this.dataset.marca); });
            }
        });
        
        function obtenerCodigo(marca, excludeId) {
            // Gate: non-logged-in users who haven't already chosen to proceed
            if (!isUserLoggedIn() && !userChoseToReveal && !excludeId) {
                // Get max benefit from the page for display
                var maxBenefitEl = document.querySelector('.codigo-valor, .ver-mas-benefit');
                var benefitText = maxBenefitEl ? maxBenefitEl.textContent.trim() : null;
                showBrandRevealModal(marca, benefitText, function() {
                    // User chose "sin registrarme" → proceed with the actual fetch
                    obtenerCodigoDirecto(marca, null);
                });
                return;
            }
            
            obtenerCodigoDirecto(marca, excludeId);
        }
        
        function obtenerCodigoDirecto(marca, excludeId) {
            const btnObtener = document.getElementById('btnObtenerCodigo');
            const obtenerCTA = document.getElementById('obtenerCTA');
            const codigoRevelado = document.getElementById('codigoRevelado');
            const btnPruebaOtro = document.getElementById('btnPruebaOtro');
            
            if (btnObtener) { btnObtener.classList.add('loading'); btnObtener.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando el mejor código...'; }
            if (btnPruebaOtro && excludeId) { btnPruebaOtro.disabled = true; btnPruebaOtro.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...'; }
            
            const formData = new FormData();
            formData.append('marca', marca);
            if (excludeId) formData.append('exclude_id', excludeId);
            
            fetch('/ajax/obtener_codigo.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    currentCodigoId = data.codigo_id;
                    userChoseToReveal = true; // User has seen a code, don't gate "Prueba otro"
                    if (obtenerCTA) obtenerCTA.style.display = 'none';
                    if (codigoRevelado) { codigoRevelado.classList.add('show'); renderCodigoRevelado(data); }
                    if (btnPruebaOtro) {
                        btnPruebaOtro.disabled = false;
                        btnPruebaOtro.style.display = data.total_codigos <= 1 ? 'none' : '';
                        btnPruebaOtro.innerHTML = '<i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro';
                    }
                    // Lead registrado server-side en obtener_codigo.php (email + notificación)
                } else {
                    if (obtenerCTA) obtenerCTA.innerHTML = '<div style="color: #6b7280; padding: 20px;"><i class="fas fa-info-circle"></i> ' + (data.message || 'No hay códigos disponibles') + '</div>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                if (btnObtener) { btnObtener.classList.remove('loading'); btnObtener.innerHTML = '<i class="fas fa-ticket-alt"></i> Obtener mi código'; }
                if (btnPruebaOtro) { btnPruebaOtro.disabled = false; btnPruebaOtro.innerHTML = '<i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro'; }
            });
        }
        
        function renderCodigoRevelado(data) {
            const container = document.getElementById('codigoResultado');
            if (!container) return;
            let starsHTML = '';
            for (let i = 0; i < 5; i++) starsHTML += i < data.trust_stars ? '<i class="fas fa-star"></i>' : '<i class="far fa-star" style="color: #d1d5db;"></i>';
            let benefitHTML = data.beneficio_texto ? '<div style="font-size:0.85rem;color:#10b981;font-weight:600;margin-bottom:10px"><i class="fas fa-tag"></i> ' + esc(data.beneficio_texto) + '</div>' : '';
            let featuredHTML = data.es_destacado ? '<div style="display:inline-block;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#1e3a5f;font-size:0.75rem;font-weight:700;padding:2px 10px;border-radius:20px;margin-bottom:10px"><i class="fas fa-star"></i> Código Destacado</div><br>' : '';
            let descHTML = data.descripcion && data.descripcion.length > 0 ? '<div class="codigo-descripcion">' + esc(data.descripcion.substring(0, 200)) + '</div>' : '';
            let vipBadgeHTML = data.is_vip ? '<span style="display:inline-flex;align-items:center;gap:3px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#1e3a5f;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:10px;margin-left:6px"><i class="fas fa-crown"></i> VIP</span>' : '';
            container.innerHTML = featuredHTML + benefitHTML +
                '<div class="codigo-valor-container"><div class="codigo-valor" id="codigoTexto">' + esc(data.codigo) + '</div>' +
                '<button class="btn-copiar-codigo" onclick="copiarCodigoRevelado()"><i class="fas fa-copy"></i> Copiar</button></div>' +
                '<div class="codigo-publisher"><img src="' + esc(data.usuario_img) + '" alt="' + esc(data.usuario_nombre) + '" class="publisher-avatar" style="' + (data.is_vip ? 'border: 2px solid #f59e0b; box-shadow: 0 0 8px rgba(245,158,11,0.5);' : '') + '" onerror="this.src=\'/img/user-default.png\'">' +
                '<div class="publisher-info"><div class="publisher-name">' + esc(data.usuario_nombre) + vipBadgeHTML + '</div>' +
                '<div class="trust-badge ' + esc(data.trust_class) + '"><span class="trust-stars" style="color:' + esc(data.trust_color) + '">' + starsHTML + '</span> ' + esc(data.trust_label) + '</div></div>' +
                '<div style="text-align:right"><div style="color:#10b981;font-size:0.8rem"><i class="fas fa-thumbs-up"></i> ' + data.votos_positivos + '</div></div></div>' + descHTML +
                '<div class="vote-buttons-modern" id="voteSection">' +
                    '<span style="font-size:0.8rem;color:#6b7280;">¿Te ha funcionado?</span>' +
                    '<button class="vote-btn-modern vote-up" id="btnVoteUp" onclick="votarCodigo(1)"><i class="fas fa-thumbs-up"></i> Sí <span id="voteUpCount">' + (parseInt(data.votos_positivos)||0) + '</span></button>' +
                    '<button class="vote-btn-modern vote-down" id="btnVoteDown" onclick="votarCodigo(0)"><i class="fas fa-thumbs-down"></i> No</button>' +
                    '<span class="vote-score-modern" id="voteScoreDisplay" style="color:' + ((parseInt(data.votos_positivos)||0)-(parseInt(data.votos_negativos)||0) > 0 ? '#10b981' : (parseInt(data.votos_positivos)||0)-(parseInt(data.votos_negativos)||0) < 0 ? '#ef4444' : '#9ca3af') + '">' + ((parseInt(data.votos_positivos)||0)-(parseInt(data.votos_negativos)||0) > 0 ? '+' : '') + ((parseInt(data.votos_positivos)||0)-(parseInt(data.votos_negativos)||0)) + '°</span>' +
                '</div>';
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        window.copiarCodigoRevelado = function() {
            const el = document.getElementById('codigoTexto');
            if (!el) return;
            const text = el.textContent.trim();
            if (navigator.clipboard) { navigator.clipboard.writeText(text).then(showCopyOK); }
            else { const t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); showCopyOK(); }
        };
        function showCopyOK() {
            const btn = document.querySelector('.btn-copiar-codigo');
            if (!btn) return;
            btn.classList.add('copied'); btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
            setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<i class="fas fa-copy"></i> Copiar'; }, 2500);
        }
        
        function verMasCodigos(marca) {
            const btnVerMas = document.getElementById('btnVerMas');
            const verMasLista = document.getElementById('verMasLista');
            if (!verMasLista) return;
            if (verMasLoaded) {
                verMasLista.classList.toggle('show');
                if (btnVerMas) { const ic = btnVerMas.querySelector('.fa-chevron-down,.fa-chevron-up'); if(ic){ic.classList.toggle('fa-chevron-down');ic.classList.toggle('fa-chevron-up');} }
                return;
            }
            if (btnVerMas) { btnVerMas.disabled = true; btnVerMas.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...'; }
            const fd = new FormData(); fd.append('marca', marca);
            fetch('/ajax/ver_mas_codigos.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.codigos) {
                    let html = '';
                    data.codigos.forEach(function(c) {
                        let stars = '';
                        for (let i = 0; i < 5; i++) stars += i < c.trust_stars ? '<i class="fas fa-star" style="color:' + esc(c.trust_color) + '"></i>' : '<i class="far fa-star" style="color:#d1d5db"></i>';
                        let bt = c.beneficio_texto ? '<div class="ver-mas-benefit">' + esc(c.beneficio_texto) + '</div>' : '';
                        let vipTag = c.is_vip ? '<span style="display:inline-flex;align-items:center;gap:2px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#1e3a5f;font-size:0.6rem;font-weight:700;padding:1px 6px;border-radius:8px;margin-left:5px;vertical-align:middle"><i class="fas fa-crown"></i> VIP</span>' : '';
                        let avatarStyle = c.is_vip ? ' style="border: 2px solid #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.4);"' : '';
                        html += '<div class="ver-mas-item">' +
                            '<img src="' + esc(c.usuario_img) + '" alt="' + esc(c.usuario_nombre) + '" class="ver-mas-avatar"' + avatarStyle + ' onerror="this.src=\'/img/user-default.png\'">' +
                            '<div class="ver-mas-info"><div class="ver-mas-user">' + esc(c.usuario_nombre) + vipTag + '</div>' + bt +
                            '<div class="ver-mas-trust"><span class="trust-stars" style="font-size:0.7rem">' + stars + '</span> ' +
                            '<span class="trust-badge ' + esc(c.trust_class) + '" style="font-size:0.7rem;padding:1px 6px">' + esc(c.trust_label) + '</span></div></div>' +
                            '<a href="/codigo/' + esc(c.marca || '').toLowerCase() + '-' + c.codigo_id.slice(-8) + '" style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:600;color:#60a5fa;background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.25);text-decoration:none;margin-right:6px;white-space:nowrap;" title="Ver ficha"><i class="fas fa-id-card"></i> Ficha</a>' +
                            '<button class="btn-usar-este" data-cid="' + esc(c.codigo_id) + '" data-code="' + esc(c.codigo) + '" data-uname="' + esc(c.usuario_nombre) + '" data-uimg="' + esc(c.usuario_img) + '" data-tstars="' + c.trust_stars + '" data-tlabel="' + esc(c.trust_label) + '" data-tclass="' + esc(c.trust_class) + '" data-tcolor="' + esc(c.trust_color) + '" data-vpos="' + c.votos_positivos + '" data-benefit="' + esc(c.beneficio_texto) + '" data-desc="' + esc(c.descripcion || '') + '" data-featured="' + (c.es_destacado ? '1' : '0') + '" data-vip="' + (c.is_vip ? '1' : '0') + '"><i class="fas fa-arrow-right"></i> Usar</button></div>';;
                    });
                    verMasLista.innerHTML = html;
                    verMasLista.classList.add('show');
                    verMasLoaded = true;
                    // Bind usar buttons (gated behind modal for non-logged-in users)
                    verMasLista.querySelectorAll('.btn-usar-este').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            const d = this.dataset;
                            const revealThisCode = function() {
                                currentCodigoId = d.cid;
                                userChoseToReveal = true;
                                document.getElementById('obtenerCTA').style.display = 'none';
                                const cr = document.getElementById('codigoRevelado'); if(cr) cr.classList.add('show');
                                renderCodigoRevelado({ codigo: d.code, usuario_nombre: d.uname, usuario_img: d.uimg, trust_stars: parseInt(d.tstars), trust_label: d.tlabel, trust_class: d.tclass, trust_color: d.tcolor, votos_positivos: parseInt(d.vpos), beneficio_texto: d.benefit, descripcion: d.desc, es_destacado: d.featured === '1', is_vip: d.vip === '1' });
                                // Registrar vista/lead para que aparezca en "Mis Leads" del dueño del código
                                fetch('/ajax/registrar_vista_codigo.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({ codigo_id: d.cid })
                                }).catch(function(e) { console.error('Error registrando vista:', e); });
                            };
                            
                            // Gate: non-logged-in users who haven't chosen to proceed yet
                            if (!isUserLoggedIn() && !userChoseToReveal) {
                                showBrandRevealModal(d.uname || marca, d.benefit, revealThisCode);
                            } else {
                                revealThisCode();
                            }
                        });
                    });
                    if (btnVerMas) { btnVerMas.disabled = false; btnVerMas.innerHTML = '<i class="fas fa-list"></i> Ocultar códigos <i class="fas fa-chevron-up"></i>'; }
                }
            })
            .catch(err => { console.error(err); if (btnVerMas) { btnVerMas.disabled = false; btnVerMas.innerHTML = '<i class="fas fa-list"></i> Error, intenta de nuevo <i class="fas fa-chevron-down"></i>'; } });
        }
        
        function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.appendChild(document.createTextNode(String(str))); return d.innerHTML; }

        // ── Vote handler ──
        window.votarCodigo = function(esPositivo) {
            if (!currentCodigoId) return;
            var btnUp = document.getElementById('btnVoteUp');
            var btnDown = document.getElementById('btnVoteDown');
            var scoreEl = document.getElementById('voteScoreDisplay');
            var countEl = document.getElementById('voteUpCount');
            // Disable buttons
            if (btnUp) { btnUp.classList.add('disabled'); }
            if (btnDown) { btnDown.classList.add('disabled'); }

            var fd = new FormData();
            fd.append('metodo', 'votar_codigo');
            fd.append('id_codigo', currentCodigoId);
            fd.append('votos_positivos', esPositivo ? 1 : 0);
            fd.append('votos_negativos', esPositivo ? 0 : 1);

            fetch('/ajax_actions', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success) {
                    // Highlight active vote
                    if (esPositivo && btnUp) { btnUp.classList.add('active'); }
                    if (!esPositivo && btnDown) { btnDown.classList.add('active'); }
                    // Update counts
                    var total = resp.total;
                    if (scoreEl) {
                        scoreEl.textContent = (total > 0 ? '+' : '') + total + '°';
                        scoreEl.style.color = total > 0 ? '#10b981' : total < 0 ? '#ef4444' : '#9ca3af';
                    }
                    if (countEl && resp.votos_positivos !== undefined) {
                        countEl.textContent = resp.votos_positivos;
                    }
                    // Show success message
                    var section = document.getElementById('voteSection');
                    if (section) {
                        var msg = document.createElement('span');
                        msg.className = 'vote-msg-modern';
                        msg.style.color = '#10b981';
                        msg.innerHTML = '<i class="fas fa-check-circle"></i> ¡Gracias por tu voto!';
                        section.appendChild(msg);
                    }
                } else {
                    // Re-enable or show message
                    var message = resp.message || 'Error al votar';
                    if (message.indexOf('sesión') !== -1) {
                        // Needs login
                        var section = document.getElementById('voteSection');
                        if (section) {
                            section.innerHTML = '<span class="vote-msg-modern" style="color:#f59e0b"><i class="fas fa-sign-in-alt"></i> <a href="/login" style="color:#f59e0b;text-decoration:underline">Inicia sesión</a> para votar</span>';
                        }
                    } else {
                        if (btnUp) { btnUp.classList.remove('disabled'); }
                        if (btnDown) { btnDown.classList.remove('disabled'); }
                        alert(message);
                    }
                }
            })
            .catch(function(err) {
                console.error('Error votando:', err);
                if (btnUp) { btnUp.classList.remove('disabled'); }
                if (btnDown) { btnDown.classList.remove('disabled'); }
                alert('Error de conexión. Inténtalo de nuevo.');
            });
        };
    })();
    </script>

    <!-- 3. CÓMO USAR (Guía visual rápida) - OCULTO GLOBALMENTE porque usamos el H2 de texto SEO abajo -->
    <!-- Se mantiene el bloque condicional por si se quisiera reactivar para algunas marcas, pero según blueprint SEO, mejor texto -->
    <?php if(false): // Desactivado globalmente en favor del blueprint de texto ?>
    <div class="row como-usar-section" style="margin-top: 40px; background: linear-gradient(145deg, #1e1e1e, #2a2a2a); padding: 40px 0; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <div class="container">
            <div class="col-md-12">
                <div style="text-align: center; margin-bottom: 30px; font-size: 1.4rem; font-weight: 700; color: #fff;">Cómo activar tu código referido <?php echo $nombre_marca; ?></div>
                <div class="pasos-grid" <?php if(isset($custom_steps) && count($custom_steps) > 3) echo 'style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));"'; ?>>
                    <?php 
                    $steps_to_show = isset($custom_steps) ? $custom_steps : [
                        ['num' => 1, 'title' => 'Elige', 'desc' => 'Selecciona la oferta que más te guste de la lista.'],
                        ['num' => 2, 'title' => 'Copia', 'desc' => 'Haz clic en "Ver Código" para copiarlo automáticamente.'],
                        ['num' => 3, 'title' => 'Ahorra', 'desc' => 'Pégalo en el checkout de la tienda ' . $nombre_marca . '.']
                    ];
                    
                    foreach($steps_to_show as $step): 
                    ?>
                    <div class="paso-item">
                        <div class="paso-numero"><?php echo $step['num']; ?></div>
                        <div class="paso-title"><?php echo $step['title']; ?></div>
                        <p><?php echo $step['desc']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. MARCAS RELACIONADAS -->
    <div class="row" style="margin-top: 40px;">
        <div class="container">
            <div class="col-md-12">
                <?php 
                $marcas_relacionadas = get_related_brands($marca, 6); // Reducido a 6 para menos ruido
                if (!empty($marcas_relacionadas)) {
                    bloque_marcas_home($categoria_marca, $marca, 6);
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 5. INFORMACIÓN SEO Y FAQ (Consolidado al final) -->
    <div class="row seo-content-section" style="margin-top: 50px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 40px;">
        <div class="container">
            <div class="col-md-12">
                <div class="seo-content-card">
                    
                    <!-- CONTENIDO SEO BLUEPRINT GLOBAL -->
                    <div class="hostinger-seo-blueprint" style="color: #eee; line-height: 1.7;">
                        
                        <!-- H2 Qué es Marca -->
                        <h2 style="font-size: 1.5rem; margin-top: 0; margin-bottom: 20px; color: #fff;"><?php echo $seo_info_title; ?></h2>
                        
                        <?php if(isset($is_hostinger) && $is_hostinger): ?>
                            <!-- Texto específico Hostinger hardcoded -->
                            <p>Hostinger es un proveedor internacional de alojamiento web conocido por ofrecer precios bajos y promociones agresivas, especialmente para nuevos clientes. Sus descuentos pueden superar el 70 % cuando se contratan planes de varios años.</p>
                            <p>Estos precios reducidos forman parte de su estrategia de captación y no afectan al rendimiento básico del servicio. Por este motivo, muchas ofertas no requieren cupón adicional y se aplican directamente al seleccionar un plan.</p>
                        <?php else: ?>
                            <!-- Texto dinámico para otras marcas (Data de BD) -->
                            <?php 
                            // Reemplazo dinámico de años
                            $current_year = date('Y');
                            $last_year = $current_year - 1;
                            
                            if ($seo_que_es) $seo_que_es = str_replace($last_year, $current_year, $seo_que_es);
                            if ($descripcion_marca) $descripcion_marca = str_replace($last_year, $current_year, $descripcion_marca);
                            
                            // Si no hay descripción específica, usar fallback genérico
                            if (empty($seo_que_es) && empty($descripcion_marca)) {
                                echo "<p>$nombre_marca es una de las marcas líderes en su sector, ofreciendo productos de alta calidad y servicio al cliente de confianza.</p>";
                                echo "<p>Aprovecha los códigos promocionales de $nombre_marca para obtener descuentos exclusivos en tu próxima compra.</p>";
                            }
                            ?>

                            <?php if($seo_que_es): ?>
                                <div class="seo-que-es-block" style="margin-bottom: 25px;">
                                    <?php echo nl2br($seo_que_es); ?>
                                </div>
                            <?php endif; ?>

                            <?php if($descripcion_marca): ?>
                                <div class="que-es-description" style="color: #ccc;"><?php echo html_entity_decode($descripcion_marca); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- H2 Cómo usar cupón (CRÍTICO - GLOBAL) -->
                        <h2 style="font-size: 1.5rem; margin-top: 35px; margin-bottom: 20px; color: #fff;">Cómo usar un cupón de <?php echo $nombre_marca; ?> paso a paso</h2>
                        <ol style="margin-left: 20px; margin-bottom: 20px;">
                            <li style="margin-bottom: 10px;">Accede a la web de <?php echo $nombre_marca; ?> y llena tu carrito de compra.</li>
                            <li style="margin-bottom: 10px;">Durante el proceso de pago, busca la casilla "¿Tienes un código promocional?" o similar.</li>
                            <li style="margin-bottom: 10px;">Copia el código verificado desde CodigoAmigo.</li>
                            <li style="margin-bottom: 10px;">Pégalo en el campo correspondiente y pulsa "Aplicar".</li>
                            <li style="margin-bottom: 10px;">Verifica que el precio total se ha reducido antes de pagar.</li>
                        </ol>

                        <!-- Tabla Comparativa (SOLO HOSTINGER) -->
                        <?php if(isset($is_hostinger) && $is_hostinger): ?>
                        <h2 style="font-size: 1.5rem; margin-top: 35px; margin-bottom: 20px; color: #fff;">Planes de Hostinger y ahorro aproximado con descuento</h2>
                        <div class="table-responsive">
                            <table class="table" style="color: #ddd; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
                                        <th style="padding: 12px;">Plan</th>
                                        <th style="padding: 12px;">Precio con descuento</th>
                                        <th style="padding: 12px;">Recomendado para</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);"><strong>Single</strong></td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Desde precio promocional</td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Webs personales o proyectos pequeños</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);"><strong>WordPress</strong></td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Desde precio promocional</td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Blogs y sitios WordPress</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);"><strong>Business</strong></td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Desde precio promocional</td>
                                        <td style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.05);">Tiendas online y webs con tráfico</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- H2 FAQs (GLOBAL) -->
                        <div id="faqs-section"></div>
                        <?php 
                        // FAQ Combined Logic
                        // 1. Hostinger specific (ya cargado en $seo_faq si es Hostinger)
                        // 2. Generic (ya asignado a $seo_faq si no hay específico)
                        // 3. Dynamic FAQs from marcas_faqs collection
                        // 4. DB FAQs (Concatenar)
                        
                        $faq_lines_to_display = [];
                        
                        // Load dynamic FAQs first
                        if (function_exists('getFAQsByMarca')) {
                             $dynamic_faqs = getFAQsByMarca($marca, true);
                             foreach ($dynamic_faqs as $df) {
                                 $faq_lines_to_display[] = [
                                     'q' => $df['titulo'] ?? $df['pregunta'],
                                     'a' => $df['respuesta']
                                 ];
                             }
                        }

                        if (!empty(trim($seo_faq ?? ''))) {
                             $lines = preg_split('/\r\n|\r|\n/', $seo_faq);
                             foreach ($lines as $line) {
                                 if (strpos($line, '|') !== false) {
                                     list($q, $a) = array_map('trim', explode('|', $line, 2));
                                     // FILTER: Only include relevant FAQs
                                     if (stripos($q, 'código') !== false || stripos($q, 'amigo') !== false || stripos($q, 'descuento') !== false || stripos($q, 'ahorra') !== false) {
                                         if ($q && $a) $faq_lines_to_display[] = ['q' => $q, 'a' => $a];
                                     }
                                 }
                             }
                        }
                        
                        // FAQ Global: Votación y Niveles de Confianza
                        $faq_lines_to_display[] = [
                            'q' => '¿Cómo puedo votar un código?',
                            'a' => 'Cuando revelas un código, verás los botones "Sí" (👍) y "No" (👎) debajo del código con la pregunta "¿Te ha funcionado?". Haz clic en el botón correspondiente para registrar tu voto. Necesitas tener una cuenta e iniciar sesión para poder votar. Solo puedes votar una vez por código, pero puedes cambiar tu voto después.'
                        ];
                        $faq_lines_to_display[] = [
                            'q' => '¿Qué son los niveles de confianza?',
                            'a' => 'Cada código tiene un nivel de confianza basado en la calidad del código y la actividad del usuario que lo publica. Los niveles son: ⭐⭐⭐⭐⭐ Premium (código destacado/promocionado), ⭐⭐⭐⭐ Recomendado (usuario muy activo y bien valorado), ⭐⭐⭐ De confianza (usuario con buen historial), ⭐⭐ Verificado (perfil básico completo) y ⭐ Nuevo (usuario recién registrado).'
                        ];
                        $faq_lines_to_display[] = [
                            'q' => '¿Cómo puedo subir de nivel de confianza?',
                            'a' => 'Para mejorar tu nivel de confianza puedes: subir una foto de perfil, verificar tu email, mantener tu cuenta activa (la antigüedad cuenta hasta 5 años), publicar códigos en varias marcas, recibir votos positivos en tus códigos y añadir descripciones detalladas a tus códigos.'
                        ];

                        // Render using the modern accordion component
                        if (function_exists('incluirFAQsEnMarca')) {
                            echo incluirFAQsEnMarca($marca, $nombre_marca, $faq_lines_to_display);
                        } else {
                            // Fallback rendering if component not available
                            foreach($faq_lines_to_display as $faq) {
                                echo '<h3 style="font-size: 1.2rem; color: #E30613; margin-top: 25px; margin-bottom: 10px;">' . htmlspecialchars($faq['q']) . '</h3>';
                                echo '<p>' . htmlspecialchars($faq['a']) . '</p>';
                            }
                        }
                        ?>

                        <!-- H2 Recomendación final (GLOBAL) -->
                        <h2 style="font-size: 1.5rem; margin-top: 35px; margin-bottom: 20px; color: #fff;">Recomendación final</h2>
                        <p>Si buscas ahorrar en <strong><?php echo $nombre_marca; ?></strong>, te recomendamos revisar esta página antes de comprar. Nuestros usuarios comparten códigos reales que pueden marcar la diferencia en el precio final. Revisa siempre las condiciones de cada oferta.</p>
                        
                         <?php if(!empty($seo_tips)): ?>
                            <div style="margin-top: 30px; background: rgba(227, 6, 19, 0.1); padding: 25px; border-radius: 12px; border: 1px solid rgba(227, 6, 19, 0.2);">
                                <h4 style="color: #FF4D4D; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;"><i class="fas fa-lightbulb"></i> Tips de Ahorro Extra</h4>
                                <div style="color: #eee;"><?php echo nl2br(html_entity_decode($seo_tips)); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <style>
                        /* Estilos para asegurar que las imágenes del contenido se encajen bien */
                        .seo-que-es-block img, 
                        .que-es-description img,
                        .hostinger-seo-blueprint img {
                            max-width: 100% !important;
                            height: auto !important;
                            display: block;
                            margin: 20px auto;
                            border-radius: 12px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. BÚSQUEDAS RELACIONADAS (Keywords SEO) -->
    <?php
    // Renderizar sección de búsquedas relacionadas con keywords SEO almacenadas
    if (!function_exists('render_related_searches_section')) {
        include_once __DIR__ . '/../myphp/funciones_keywords_marca.php';
    }
    $related_searches_html = render_related_searches_section(
        $marca_info['nombre_clave'] ?? $marca, 
        $nombre_marca,
        $marca_info['categoria_clave'] ?? ''
    );
    if (!empty($related_searches_html)) {
        echo $related_searches_html;
    }
    ?>

</div>

<!-- CSS específico para la página de marca -->
<link rel="stylesheet" href="/css/chollometro-filters.css">
<link rel="stylesheet" href="/css/marca-redesign.css?v=<?php echo file_exists(__DIR__ . '/../css/marca-redesign.css') ? filemtime(__DIR__ . '/../css/marca-redesign.css') : time(); ?>">

<!-- JavaScript para filtros y FAQ -->
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

// Smooth scroll for hero button
document.querySelectorAll('.scroll-to-codes').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            window.scrollTo({
                top: target.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    });
});

// =========================================
// PARALLAX EFFECT - BRAND HERO
// =========================================
(function() {
    const parallaxHero = document.getElementById('brand-hero-parallax');
    if (!parallaxHero) return;
    
    const bgLayer = parallaxHero.querySelector('.parallax-bg-layer');
    if (!bgLayer) return;
    
    let ticking = false;
    
    function updateParallax() {
        const scrollY = window.scrollY;
        const heroHeight = parallaxHero.offsetHeight;
        
        // Solo aplicar parallax mientras el hero es visible
        if (scrollY < heroHeight + 200) {
            const parallaxSpeed = 0.35;
            const translateY = scrollY * parallaxSpeed;
            bgLayer.style.transform = `scale(1.3) translateY(${translateY}px)`;
        }
        
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }, { passive: true });
    
    // Efecto de profundidad con el mouse (solo desktop)
    if (window.innerWidth > 768) {
        parallaxHero.addEventListener('mousemove', function(e) {
            const rect = parallaxHero.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            
            const moveX = x * 15;
            const moveY = y * 10;
            
            bgLayer.style.transform = `scale(1.3) translate(${moveX}px, ${moveY}px)`;
        });
        
        parallaxHero.addEventListener('mouseleave', function() {
            bgLayer.style.transform = 'scale(1.3) translate(0, 0)';
        });
    }
})();
</script>

<?php 
// Incluir estilos CSS para el menú de acciones de códigos
echo get_code_actions_css();

// Modal informativo de Trust Score (clicable en badges)
include_once __DIR__ . '/../myphp/trust_info_modal.php';
?>
