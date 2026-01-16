<?php
/**
 * Página de detalle de marca con diseño moderno
 * Muestra información completa de una marca específica con sus códigos
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones_modern.php';

// Inicializar detector de móviles si no está definido
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}

// Variables meta base (se sobreescribirán después de obtener datos de la marca)
$title = 'Códigos Descuento ' . ucfirst($marca) . ' - CodigoAmigo.com';
$description = 'Los mejores códigos descuento y cupones de ' . ucfirst($marca) . '. Ahorra dinero con CodigoAmigo.com';
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
if (!isset($numero_codigos)) $numero_codigos = count($codigos) + count($codigos_destacados);
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

?>

<div class="main_entremedio brand-wrapper">
    <!-- Hero Section Compacto -->
    <div class="container text-center bloque_titulo_home" style="padding: 20px 0 10px;">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                <?php if($imagen_marca && $imagen_marca !== 'Sin imagen'): ?>
                    <div class="marca-logo-hero-container">
                        <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="Logo oficial <?php echo htmlspecialchars($nombre_marca); ?>" class="logo-marca-principal" loading="lazy">
                    </div>
                <?php endif; ?>
                <h1 class="brand-hero-title"><?php echo $seo_h1; ?></h1>

                <div class="hero-stats" style="font-size: 0.9rem;">
                    <span class="stat-item"><i class="fas fa-check-circle"></i> <?php echo isset($last_update_str) ? $last_update_str : 'Verificado hoy'; ?></span>
                    <span class="stat-divider">•</span>
                    <span class="stat-item"><?php echo $numero_codigos_format; ?> códigos</span>
                    <span class="stat-divider">•</span>
                    <span class="stat-item">100% Gratis</span>
                </div>
                <?php if(isset($verification_disclaimer)): ?>
                    <div style="font-size: 0.85rem; color: #bbb; margin-top: 8px; font-style: italic;">
                        <i class="fas fa-shield-alt"></i> <?php echo $verification_disclaimer; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

    // Schema FAQPage (Nueva implementación)
    $schema_faq_entries = [];
    if (!empty($seo_faq)) {
        $lines = preg_split('/\r\n|\r|\n/', $seo_faq);
        foreach ($lines as $line) {
            if (strpos($line, '|') !== false) {
                list($q, $a) = array_map('trim', explode('|', $line, 2));
                if ($q && $a) {
                    $schema_faq_entries[] = [
                        '@type' => 'Question',
                        'name' => $q,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $a
                        ]
                    ];
                }
            }
        }
    }

    if (!empty($schema_faq_entries)) {
        $schema_faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $schema_faq_entries
        ];
        echo '<script type="application/ld+json">' . json_encode($schema_faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }

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

    <!-- 1. CÓDIGOS DESTACADOS (Primero lo más importante) -->
    <?php if(!empty($codigos_destacados)): 
        // Verificar si el usuario tiene un código para esta marca
        $codigo_usuario_existente = null;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // Buscar código del usuario para esta marca
            if (!function_exists('get_codigos_by_user_brand')) {
                include_once __DIR__ . '/../myphp/funciones_codigo.php';
            }
            
            if (function_exists('get_codigos_by_user_brand')) {
                // El orden correcto es (marca, id_usuario)
                $mis_codigos = get_codigos_by_user_brand($marca, $_SESSION['user_id']);
                if ($mis_codigos && count($mis_codigos) > 0) {
                    $codigo_usuario_existente = $mis_codigos[0];
                }
            }
        }
    ?>
    <div class="row codigos-destacados-section" style="margin-top: 20px;">
        <div class="container">
            <div class="col-md-12 columns small-12 slider">
                <div class="title" style="margin-bottom: 15px;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #fff;"><i class="fas fa-fire" style="color: #E30613;"></i> Códigos <?php echo $nombre_marca; ?> Destacados</div>
                </div>
                <?php echo generate_modern_featured_cards($codigos_destacados, true, $marca, $codigo_usuario_existente); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. LISTADO DE TODOS LOS CÓDIGOS -->
    <div class="container ultimos_container" style="margin-top: 30px;">
        <div class="row">
            <div class="col-md-12">
               <h2 class="section-title" style="font-size: 1.4rem; margin-bottom: 20px;"><?php echo $seo_h2; ?></h2>
               
               <!-- Intro Text Global para todas las marcas -->
               <!-- Intro Text Global para todas las marcas -->
               <div class="brand-intro-text" style="color: #ddd; margin-bottom: 25px; line-height: 1.6;">
                    <?php 
                    if (!empty($brand_intro_html)) {
                        echo $brand_intro_html;
                    } else {
                        // Fallback Genérico
                        echo '<p>En esta página recopilamos cupones y códigos de descuento de <strong>' . $nombre_marca . '</strong> compartidos por usuarios y promociones activas disponibles actualmente. Los códigos se revisan de forma periódica, aunque su validez puede variar según la promoción oficial vigente.</p>';
                        echo '<p>Algunos descuentos se aplican automáticamente sin necesidad de introducir un código, mientras que otros requieren pegar el cupón durante el proceso de compra. Recomendamos comprobar siempre el precio final antes de confirmar el pago.</p>';
                        echo '<p><strong>Última actualización:</strong> ' . $last_update_str . '.</p>';
                    }
                    ?>
               </div>

               <?php
               if(!empty($codigos)) {
                    echo '<div class="codes-grid">';
                    echo generate_modern_code_cards($codigos);
                    echo '</div>';
                    
                    // Paginación
                    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    echo generate_modern_pagination($numero_codigos, $current_page, 9, 'Códigos Amigo');
               } else if(empty($codigos_destacados)) {
                    echo '<div style="text-align: center; color: #ccc; padding: 2rem;">';
                    echo '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #E30613;"></i>';
                    echo '<h3>No se encontraron códigos activos</h3>';
                    echo '<p>Pero no te preocupes, revisamos nuevas ofertas diariamente.</p>';
                    echo '</div>';
               }
               ?>
            </div>
        </div>
    </div>

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
                        <h2 style="font-size: 1.5rem; margin-top: 35px; margin-bottom: 20px; color: #fff;">Preguntas frecuentes sobre cupones de <?php echo $nombre_marca; ?></h2>
                        
                        <?php 
                        // FAQ Combined Logic
                        // 1. Hostinger specific (ya cargado en $seo_faq si es Hostinger)
                        // 2. Generic (ya asignado a $seo_faq si no hay específico)
                        // 3. DB FAQs (Concatenar)
                        
                        $faq_lines_to_display = [];
                        if (!empty($seo_faq)) {
                             $lines = preg_split('/\r\n|\r|\n/', $seo_faq);
                             foreach ($lines as $line) {
                                 if (strpos($line, '|') !== false) {
                                     list($q, $a) = array_map('trim', explode('|', $line, 2));
                                     if ($q && $a) $faq_lines_to_display[] = ['q' => $q, 'a' => $a];
                                 }
                             }
                        }
                        ?>

                        <?php foreach($faq_lines_to_display as $faq): ?>
                            <h3 style="font-size: 1.2rem; color: #E30613; margin-top: 25px; margin-bottom: 10px;"><?php echo htmlspecialchars($faq['q']); ?></h3>
                            <p><?php echo htmlspecialchars($faq['a']); ?></p>
                        <?php endforeach; ?>

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

</div>

<!-- CSS específico para la página de marca -->
<link rel="stylesheet" href="/css/chollometro-filters.css">

<!-- JavaScript para filtros y FAQ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad del accordion FAQ
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');

        question.addEventListener('click', () => {
            // Cerrar otros items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });

            // Toggle el item actual
            item.classList.toggle('active');
        });
    });

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

<?php 
// Incluir estilos CSS para el menú de acciones de códigos
echo get_code_actions_css();
?>
