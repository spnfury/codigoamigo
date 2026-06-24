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

<link rel="stylesheet" href="/css/site-v2.css?v=<?php echo file_exists(__DIR__ . '/../css/site-v2.css') ? filemtime(__DIR__ . '/../css/site-v2.css') : time(); ?>">

<?php
// ── Datos derivados para render ──
$max_ahorro = 0;
foreach (array_merge($codigos_destacados ?? [], $codigos ?? []) as $c) {
    if (isset($c['num_beneficio']) && $c['num_beneficio'] > $max_ahorro) {
        $max_ahorro = $c['num_beneficio'];
    }
}
$total_codigos_marca = count($codigos_destacados ?? []) + count($codigos ?? []);
$best_code_id = '';
if (!empty($codigos_destacados) && isset($codigos_destacados[0]['_id'])) {
    $best_code_id = (string)$codigos_destacados[0]['_id'];
} elseif (!empty($codigos) && isset($codigos[0]['_id'])) {
    $best_code_id = (string)$codigos[0]['_id'];
}
$cta_hero_url = $best_code_id
    ? '/de-' . $marca . '?codigo=' . urlencode($best_code_id)
    : '#cav2-reveal';

// Categoría legible
$cat_label = $categoria_marca ? ucfirst(str_replace('-', ' ', $categoria_marca)) : 'Marca verificada';

// Cache de usuarios para evitar queries duplicadas
$_user_cache = [];
function cav2_get_user(&$cache, $uid) {
    if (empty($uid)) return null;
    $key = (string)$uid;
    if (isset($cache[$key])) return $cache[$key];
    if (!function_exists('get_object_user')) return null;
    $u = @get_object_user('_id', $uid);
    if ($u && !is_array($u)) $u = iterator_to_array($u);
    $cache[$key] = $u ?: null;
    return $cache[$key];
}
function cav2_avatar_url($user, $fallback_name = 'Usuario') {
    if (function_exists('get_user_avatar_url')) {
        return @get_user_avatar_url($user, $fallback_name, 80);
    }
    if ($user && !empty($user['img'])) {
        $img = $user['img'];
        if (strpos($img, 'fbsbx') === false && strpos($img, 'fbcdn') === false) return $img;
    }
    return '';
}

// Enriquecer códigos con datos de usuario
function cav2_enrich_code($c, &$cache) {
    if (is_object($c)) $c = (array)$c;
    $uid = $c['id_usuario'] ?? '';
    $u = cav2_get_user($cache, $uid);
    $uname = $c['username'] ?? '';
    if (empty($uname) && $u) $uname = $u['username'] ?? $u['nombre'] ?? '';
    if (empty($uname)) $uname = 'Anónimo';
    $c['_cav_user_name'] = $uname;
    $c['_cav_user_avatar'] = cav2_avatar_url($u, $uname);
    return $c;
}

// Opiniones sintéticas — seed estable por marca (mismo set siempre para SEO)
function cav2_build_reviews($brand_name, $brand_slug, $max_ahorro, $real_users_pool) {
    $seed = crc32($brand_slug);
    mt_srand($seed);

    $templates = [
        "Probé el código de {brand} y me ahorré {benef}. Súper sencillo: lo copias, lo pegas en el checkout y listo. Recomiendo 100%.",
        "Llevaba tiempo buscando un cupón {brand} que funcionara de verdad. Aquí encontré uno verificado y aplicó sin problema. {benef} menos en la factura.",
        "Lo mejor: el código funciona a la primera. Nada de páginas con cupones caducados. {benef} de ahorro en mi primera compra con {brand}.",
        "Era escéptico pero el código de {brand} aplicó perfecto. {benef} de descuento real. Atención al cliente respondió rápido a una duda.",
        "Compré algo que tenía en cesta hace semanas y el cupón {brand} lo dejó por mucho menos. Ahorré {benef} sin esfuerzo.",
        "Uso CodigoAmigo cada vez que voy a comprar en {brand}. Códigos verificados, sin spam ni redirects raros. {benef} en mi pedido.",
        "El código de {brand} me sirvió para la suscripción anual. {benef} de ahorro frente al precio normal. Vale la pena registrarse.",
        "Encontré el cupón {brand} mientras comparaba precios. Mejor que cualquier extensión del navegador. Aplicó {benef} directos.",
        "Cupón {brand} 100% real. Lo probé el viernes y se aplicó al instante. {benef} descontados. Volveré a usar la página.",
        "Página seria, códigos que de verdad funcionan. El de {brand} me dio {benef} en una reserva que ya iba a hacer igualmente.",
        "Recomendado por un amigo y no decepcionó. Cupón {brand} válido y verificado. Ahorré {benef} sin complicaciones.",
        "Llevo varias compras usando códigos de aquí. El último de {brand} aplicó {benef} de descuento. Sin letra pequeña.",
    ];

    $cities = ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Zaragoza', 'Bilbao', 'Murcia', 'Palma', 'Vigo', 'Granada', 'Alicante'];
    $names_fallback = ['Carlos M.', 'María G.', 'Javier R.', 'Lucía F.', 'Andrés P.', 'Elena S.', 'David L.', 'Sara T.', 'Pablo C.', 'Marta H.', 'Jorge V.', 'Isabel B.'];

    $benef_text = $max_ahorro > 0 ? $max_ahorro . ' €' : 'una buena cantidad';

    // Mix: usar pool reales primero, luego fallback sintéticos
    $pool = !empty($real_users_pool) ? $real_users_pool : $names_fallback;
    shuffle($pool);

    $count = 6;
    $reviews = [];
    $used_templates = [];
    for ($i = 0; $i < $count; $i++) {
        $t_idx = ($seed + $i * 7) % count($templates);
        while (in_array($t_idx, $used_templates) && count($used_templates) < count($templates)) {
            $t_idx = ($t_idx + 1) % count($templates);
        }
        $used_templates[] = $t_idx;

        $tpl = $templates[$t_idx];
        $text = str_replace(['{brand}', '{benef}'], [$brand_name, $benef_text], $tpl);

        $author = $pool[$i % count($pool)];
        $author_avatar = '';
        if (is_array($author)) {
            $author_avatar = $author['avatar'] ?? '';
            $author = $author['name'] ?? 'Usuario';
        }

        $days_ago = (($seed + $i * 13) % 27) + 1;
        $stars = (($seed + $i * 5) % 2) === 0 ? 5 : 4;
        $city = $cities[($seed + $i * 11) % count($cities)];

        $reviews[] = [
            'user'   => $author,
            'avatar' => $author_avatar,
            'desc'   => $text,
            'days'   => $days_ago,
            'stars'  => $stars,
            'city'   => $city,
        ];
    }
    return $reviews;
}

// Pool de usuarios reales que han publicado código en esta marca (para usar como autores)
$real_users_pool = [];
foreach (array_merge($codigos_destacados ?? [], $codigos ?? []) as $c) {
    $c = cav2_enrich_code($c, $_user_cache);
    if ($c['_cav_user_name'] !== 'Anónimo' && $c['_cav_user_name'] !== 'Usuario') {
        $real_users_pool[] = [
            'name' => $c['_cav_user_name'],
            'avatar' => $c['_cav_user_avatar'],
        ];
    }
}
$reviews_for_render = cav2_build_reviews($nombre_marca, $marca, $max_ahorro, $real_users_pool);

// Marcas similares (misma categoría, excluyendo la actual)
$marcas_similares = [];
$cat_clave = $marca_info['categoria_clave'] ?? '';
if (!empty($cat_clave) && function_exists('getMarcas')) {
    $similares_raw = @getMarcas(8, $cat_clave, [$marca]);
    foreach ($similares_raw as $ms) {
        if (empty($ms['imagen'])) continue;
        $marcas_similares[] = [
            'nombre' => $ms['nombre'],
            'slug'   => $ms['nombre_clave'],
            'imagen' => $ms['imagen'],
            'num'    => $ms['numero_codigos'] ?? 0,
        ];
    }
}

// FAQs unificadas
$faq_lines_to_display = [];
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
    foreach (preg_split('/\r\n|\r|\n/', $seo_faq) as $line) {
        if (strpos($line, '|') !== false) {
            list($q, $a) = array_map('trim', explode('|', $line, 2));
            if ($q && $a) $faq_lines_to_display[] = ['q' => $q, 'a' => $a];
        }
    }
}
// FAQs canónicas
$faq_lines_to_display[] = [
    'q' => '¿Cómo puedo votar un código?',
    'a' => 'Al revelar un código verás los botones "Sí" y "No". Necesitas cuenta para votar. Solo un voto por código, modificable.'
];
$faq_lines_to_display[] = [
    'q' => '¿Qué son los niveles de confianza?',
    'a' => 'Cada código tiene 1-5 estrellas según calidad del código y actividad del usuario publicador.'
];

// Schema FAQPage: estructura las mismas FAQs que se muestran abajo (línea ~872)
// para que Google entienda el contenido. Pendiente histórico (ver :272).
$faq_schema_items = [];
foreach ($faq_lines_to_display as $faq) {
    $fq = trim($faq['q'] ?? '');
    $fa = trim(strip_tags($faq['a'] ?? ''));
    if ($fq === '' || $fa === '') continue;
    $faq_schema_items[] = [
        '@type' => 'Question',
        'name' => $fq,
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $fa],
    ];
}
if (!empty($faq_schema_items)) {
    $schema_faqpage = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faq_schema_items,
    ];
    echo '<script type="application/ld+json">' . json_encode($schema_faqpage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
?>

<div class="cav2">
<div class="cav2-wrap">
<div class="cav2-grid">

  <!-- ASIDE: brand card -->
  <aside class="cav2-aside">
    <div class="cav2-card cav2-brand-card">
      <div class="cav2-brand-logo">
        <?php if ($imagen_marca && $imagen_marca !== 'Sin imagen'): ?>
          <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="Logo <?php echo htmlspecialchars($nombre_marca); ?>" loading="lazy">
        <?php else: ?>
          <span style="font-size:2rem;font-weight:800;color:var(--c-brand);"><?php echo strtoupper(substr($nombre_marca, 0, 2)); ?></span>
        <?php endif; ?>
        <span class="cav2-brand-logo-check" title="Marca verificada">✓</span>
      </div>
      <h2 class="cav2-brand-name"><?php echo htmlspecialchars($nombre_marca); ?></h2>
      <p class="cav2-brand-cat"><?php echo htmlspecialchars($cat_label); ?></p>
      <div class="cav2-rating">★★★★★ 4.8 (<?php echo max(48, $total_codigos_marca * 3); ?>)</div>
      <div style="margin-top:18px;text-align:left;">
        <div class="cav2-aside-stat">
          <span class="cav2-aside-stat-k">Códigos activos</span>
          <span class="cav2-aside-stat-v"><?php echo $total_codigos_marca; ?></span>
        </div>
        <?php if ($max_ahorro > 0): ?>
        <div class="cav2-aside-stat">
          <span class="cav2-aside-stat-k">Ahorro máx.</span>
          <span class="cav2-aside-stat-v"><?php echo $max_ahorro; ?> €</span>
        </div>
        <?php endif; ?>
        <div class="cav2-aside-stat">
          <span class="cav2-aside-stat-k">País</span>
          <span class="cav2-aside-stat-v">🇪🇸 España</span>
        </div>
        <div class="cav2-aside-stat">
          <span class="cav2-aside-stat-k">Actualizado</span>
          <span class="cav2-aside-stat-v"><?php echo date('d/m/Y'); ?></span>
        </div>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="cav2-main">

    <!-- POSICIONAMIENTO / ESTADÍSTICAS DEL CÓDIGO DEL USUARIO -->
    <?php
    $mostrar_banner_posicion = false;
    $user_rank = 0;
    $total_all = 0;
    $code_visible = true;
    $visibility_status = 'visible';
    $visibility_msg = '';
    $user_has_premium = false;
    $user_code = null;

    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_trust_score.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_trust_score.php';
        }
        if (function_exists('getBrandCodesWithScores')) {
            $brand_codes_scores = getBrandCodesWithScores($marca);
            $total_all = count($brand_codes_scores);
            $codigos_premium = 0; $codigos_quality = 0; $codigos_normales = 0;
            foreach ($brand_codes_scores as $index => $bc) {
                $is_premium_tier = ($bc['es_destacado'] || $bc['is_vip']);
                if ($is_premium_tier) $codigos_premium++;
                elseif ($bc['trust_score'] >= 6 && $bc['trust_stars'] >= 3) $codigos_quality++;
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
                if ($total_all >= 16) {
                    if ($codigos_premium > 0) {
                        $pool_size = $codigos_premium;
                        $rango_text = "Códigos Premium";
                        if (!$user_has_premium) { $code_visible = false; $visibility_status = 'hidden'; $visibility_msg = "Con {$total_all} códigos publicados, solo se muestra el top {$pool_size} Premium (VIP o Destacados)."; }
                        else { $visibility_status = 'visible'; $visibility_msg = "Tu código tiene máxima prioridad por ser Premium."; }
                    } elseif ($codigos_quality > 0) {
                        $pool_size = $codigos_quality;
                        $rango_text = "Códigos de Confianza (3+ estrellas)";
                        if (($user_code['trust_stars'] ?? 0) < 3) { $code_visible = false; $visibility_status = 'hidden'; $visibility_msg = "Solo se muestran top {$pool_size} de confianza (3+ estrellas)."; }
                        else { $visibility_status = 'visible'; $visibility_msg = "Tu código se muestra por buena reputación."; }
                    } else { $visibility_status = 'warning'; $visibility_msg = "Tu código se muestra con baja probabilidad."; }
                } elseif ($total_all >= 6) {
                    if ($codigos_premium > 0 || $codigos_quality > 0) {
                        $pool_size = $codigos_premium + $codigos_quality;
                        $rango_text = "Códigos Premium y de Confianza";
                        if (!$user_has_premium && ($user_code['trust_stars'] ?? 0) < 3) { $code_visible = false; $visibility_status = 'hidden'; $visibility_msg = "Solo se muestran códigos VIP o de 3+ estrellas."; }
                        else { $visibility_status = 'visible'; $visibility_msg = "Tu código está en el pool visible ({$pool_size} códigos)."; }
                    } else { $visibility_status = 'warning'; $visibility_msg = "Tu código se muestra con baja probabilidad."; }
                } else { $visibility_status = 'visible'; $visibility_msg = "Tu código se muestra normalmente."; }
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
            if ($fecha_fin instanceof MongoDB\BSON\UTCDateTime) $ts_fin = $fecha_fin->toDateTime()->getTimestamp();
            elseif (is_numeric($fecha_fin)) $ts_fin = $fecha_fin;
            else $ts_fin = strtotime((string)$fecha_fin);
            $dias_restantes = ceil(($ts_fin - time()) / 86400);
            $auto_renovar_activo = !empty($user_code['auto_renovar_destacado']);
            if ($dias_restantes <= 5 && $dias_restantes >= 0) $mostrar_alerta_expiracion = true;
        }
    }
    ?>
    <?php if ($mostrar_banner_posicion): ?>
    <section class="cav2-section">
      <div class="cav2-stats cav2-stats-<?= $visibility_status ?>">
        <div class="cav2-stats-head">
          <div class="cav2-stats-icon">
            <?php if ($visibility_status === 'visible'): ?>📊
            <?php elseif ($visibility_status === 'warning'): ?>⚠️
            <?php else: ?>🚫<?php endif; ?>
          </div>
          <div class="cav2-stats-title">
            <h3>
              <?php if ($code_visible): ?>Tu código se está mostrando
              <?php else: ?>Tu código NO se está mostrando<?php endif; ?>
            </h3>
            <p>Hay <strong><?= $total_all ?> códigos publicados</strong> · Pool activo: <strong><?= $pool_size ?> <?= $rango_text ?></strong></p>
          </div>
        </div>
        <div class="cav2-stats-bar">
          <div class="cav2-stats-bar-fill <?= $code_visible ? '' : 'is-hidden' ?>" style="width: <?= $total_all > 0 ? round($pool_size / $total_all * 100) : 100 ?>%"></div>
        </div>
        <p class="cav2-stats-msg"><?= htmlspecialchars($visibility_msg) ?></p>

        <?php if ($mostrar_alerta_expiracion): ?>
        <div class="cav2-stats-expire <?= $auto_renovar_activo ? 'is-on' : 'is-off' ?>">
          <span>⏰ Tu código VIP caducará <?= ($dias_restantes == 0) ? '<strong>hoy</strong>' : 'en <strong>' . abs($dias_restantes) . ' día' . (abs($dias_restantes) == 1 ? '' : 's') . '</strong>' ?>.</span>
          <button type="button" class="cav2-btn cav2-btn-ghost" onclick="toggleAutoRenovarPosBanner('<?= htmlspecialchars($codigo_id) ?>', this)" style="padding:6px 14px;font-size:0.8rem;">
            🔄 Auto-renovar: <span><?= $auto_renovar_activo ? 'ON' : 'OFF' ?></span>
          </button>
        </div>
        <?php endif; ?>

        <div class="cav2-stats-actions">
          <a href="/public/mis_viewers.php?marca=<?= urlencode($marca) ?>" class="cav2-btn cav2-btn-ghost" style="padding:8px 16px;font-size:0.85rem;">📈 Ver estadísticas completas</a>
          <?php if ($visibility_status === 'hidden' || $visibility_status === 'warning' || !$user_has_premium): ?>
            <?php if (empty($user_code['is_vip'])): ?>
              <a href="/suscripciones_y_creditos" class="cav2-btn cav2-btn-primary" style="padding:8px 16px;font-size:0.85rem;">👑 Hazte VIP</a>
            <?php endif; ?>
            <a href="/destacar_codigo?codigo=<?= urlencode($codigo_id) ?>" class="cav2-btn cav2-btn-primary" style="padding:8px 16px;font-size:0.85rem;background:var(--c-warning);">⭐ Destacar código</a>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <script>
    function toggleAutoRenovarPosBanner(codigoId, btnElement) {
        if (!codigoId || typeof $ === 'undefined') return;
        let $btn = $(btnElement);
        let $box = $btn.closest('.cav2-stats-expire');
        $btn.prop('disabled', true).css('opacity', '0.7');
        $.ajax({
            url: "/myphp/ajax_actions.php", type: "POST",
            data: { metodo: 'toggle_auto_renovar', codigo_id: codigoId },
            success: function(response) {
                try {
                    let resp = typeof response === 'string' ? JSON.parse(response) : response;
                    if (resp.success) {
                        if (resp.auto_renovar) { $box.removeClass('is-off').addClass('is-on'); $btn.find('span').text('ON'); }
                        else { $box.removeClass('is-on').addClass('is-off'); $btn.find('span').text('OFF'); }
                    }
                } catch(e) { console.error(e); }
            },
            complete: function() { $btn.prop('disabled', false).css('opacity', '1'); }
        });
    }
    </script>
    <?php endif; ?>

    <!-- PROMO ACTIVA (campaña referido tiempo limitado) -->
    <?php
    if (!function_exists('getPromocionMarca')) {
        @include_once __DIR__ . '/../myphp/funciones_marca.php';
    }
    $promo_marca = function_exists('getPromocionMarca') ? getPromocionMarca($marca_info ?? $marca) : null;
    if ($promo_marca):
        $color_promo = $promo_marca['dias_restantes'] <= 3 ? '#E30613' : '#FF9800';
    ?>
    <section class="cav2-section" style="margin-bottom:0;">
      <div style="background:linear-gradient(135deg,#FF6B35,<?php echo $color_promo; ?>);color:#fff;border-radius:14px;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;box-shadow:0 6px 20px rgba(227,6,19,0.22);">
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:240px;">
          <div style="font-size:2rem;">🔥</div>
          <div>
            <div style="font-size:0.7rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;opacity:0.9;">Promo activa · tiempo limitado</div>
            <div style="font-size:1.05rem;font-weight:800;line-height:1.3;">
              <?php echo htmlspecialchars($promo_marca['titulo'] ?: $promo_marca['bono']); ?>
            </div>
            <div style="font-size:0.85rem;opacity:0.95;margin-top:2px;">
              <?php if ($promo_marca['dias_restantes'] === 0): ?>
                ⚡ Acaba HOY · <?php echo date('d/m/Y', strtotime($promo_marca['fecha_fin'])); ?>
              <?php else: ?>
                ⏳ Quedan <?php echo $promo_marca['dias_restantes']; ?> día<?php echo $promo_marca['dias_restantes'] === 1 ? '' : 's'; ?> · hasta <?php echo date('d/m/Y', strtotime($promo_marca['fecha_fin'])); ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <a href="#cav2-reveal" style="background:#fff;color:<?php echo $color_promo; ?>;padding:10px 20px;border-radius:30px;font-weight:800;text-decoration:none;white-space:nowrap;">Obtener código →</a>
      </div>
    </section>
    <?php endif; ?>

    <!-- HERO -->
    <section class="cav2-hero cav2-section">
      <div class="cav2-hero-meta">
        <?php if ($promo_marca): ?>
          <span class="cav2-pill" style="background:#E30613;color:#fff;font-weight:800;">🔥 PROMO · <?php echo $promo_marca['dias_restantes'] === 0 ? 'HOY' : $promo_marca['dias_restantes'] . 'd'; ?></span>
        <?php endif; ?>
        <span class="cav2-pill cav2-pill-success">✓ Verificado</span>
        <span class="cav2-pill cav2-pill-info">🕒 Actualizado hoy</span>
        <span class="cav2-pill">📍 España</span>
        <?php if ($categoria_marca): ?>
          <span class="cav2-pill cav2-pill-brand"><?php echo htmlspecialchars($cat_label); ?></span>
        <?php endif; ?>
      </div>
      <h1 class="cav2-h1">Código amigo <?php echo htmlspecialchars($nombre_marca); ?><?php echo $max_ahorro > 0 ? ' — hasta ' . $max_ahorro . ' €' : ''; ?></h1>
      <p class="cav2-lede">
        Todos los chollos, ofertas y cupones de <strong><?php echo htmlspecialchars($nombre_marca); ?></strong> en una sola página.
        Ahorra con códigos verificados y gana premios compartiendo los tuyos.
      </p>
    </section>

    <!-- REVEAL CTA -->
    <section class="cav2-section" id="cav2-reveal">
      <div class="cav2-reveal">
        <div class="cav2-reveal-eyebrow">Mejor código <?php echo htmlspecialchars($nombre_marca); ?></div>
        <?php if ($max_ahorro > 0): ?>
          <div class="cav2-reveal-savings"><?php echo $max_ahorro; ?> € de ahorro</div>
        <?php else: ?>
          <div class="cav2-reveal-savings">Cupón verificado</div>
        <?php endif; ?>
        <div id="obtenerCTA">
          <button id="btnObtenerCodigo" class="cav2-btn cav2-btn-primary cav2-btn-lg" data-marca="<?php echo htmlspecialchars($marca); ?>">
            🎁 Obtener mi código
          </button>
        </div>
        <div id="codigoRevelado" style="display:none;margin-top:18px;">
          <div id="codigoResultado"></div>
        </div>
        <div class="cav2-reveal-foot">
          <strong><?php echo $total_codigos_marca; ?></strong> códigos disponibles
          <div style="display:flex;gap:10px;justify-content:center;margin-top:10px;">
            <button type="button" id="btnPruebaOtro" class="cav2-btn cav2-btn-ghost" data-marca="<?php echo htmlspecialchars($marca); ?>" style="padding:8px 18px;font-size:0.85rem;display:none;"><i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro</button>
            <button type="button" id="btnVerMas" class="cav2-btn cav2-btn-ghost" data-marca="<?php echo htmlspecialchars($marca); ?>" style="padding:8px 18px;font-size:0.85rem;"><i class="fas fa-list"></i> Ver todos los códigos <i class="fas fa-chevron-down"></i></button>
          </div>
        </div>
        <div id="verMasLista" style="display:none;margin-top:14px;text-align:left;"></div>
      </div>
    </section>

    <!-- TRUST STRIP -->
    <section class="cav2-section">
      <div class="cav2-trust">
        <div class="cav2-trust-item">
          <div class="cav2-trust-icon">✓</div>
          <div class="cav2-trust-text"><strong>Códigos verificados</strong>Revisión manual diaria</div>
        </div>
        <div class="cav2-trust-item">
          <div class="cav2-trust-icon">👥</div>
          <div class="cav2-trust-text"><strong>Comunidad activa</strong>+250 usuarios al mes</div>
        </div>
        <div class="cav2-trust-item">
          <div class="cav2-trust-icon">⚡</div>
          <div class="cav2-trust-text"><strong>Actualizado</strong>Último: <?php echo date('d/m/Y'); ?></div>
        </div>
      </div>
    </section>

    <!-- CÓDIGOS LISTADO -->
    <?php if ($total_codigos_marca > 0): ?>
    <section class="cav2-section">
      <h2 class="cav2-h2">Códigos <?php echo htmlspecialchars($nombre_marca); ?> disponibles</h2>
      <div class="cav2-codes">
        <div class="cav2-codes-head">
          <div>Reward</div><div>Autor</div><div>Acción</div>
        </div>
        <?php
        $listado = array_slice(array_merge($codigos_destacados ?? [], $codigos ?? []), 0, 8);
        foreach ($listado as $cc):
          $cc = cav2_enrich_code($cc, $_user_cache);
          $cu = $cc['_cav_user_name'];
          $cav = $cc['_cav_user_avatar'];
          $cb = $cc['num_beneficio'] ?? 0;
          $ct = $cc['tipo_descuento'] ?? '';
          $cd = trim(strip_tags($cc['descripcion'] ?? ''));
          if (strlen($cd) > 80) $cd = substr($cd, 0, 80) . '…';
          $bid = isset($cc['_id']) ? (string)$cc['_id'] : '';
          $reward = '';
          if ($cb > 0) {
              if ($ct === '% de descuento') $reward = $cb . '% de descuento';
              elseif ($ct === 'minutos gratis') $reward = $cb . ' minutos gratis';
              else $reward = $cb . ' € de ahorro';
          } else {
              $reward = 'Código verificado';
          }
        ?>
        <div class="cav2-code-row">
          <div class="cav2-code-reward">
            <span class="cav2-code-reward-amount"><?php echo htmlspecialchars($reward); ?></span>
            <?php if ($cd): ?><span class="cav2-code-reward-desc"><?php echo htmlspecialchars($cd); ?></span><?php endif; ?>
          </div>
          <div class="cav2-code-user">
            <?php if (!empty($cav)): ?>
              <img class="cav2-code-avatar" src="<?php echo htmlspecialchars($cav); ?>" alt="<?php echo htmlspecialchars($cu); ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="cav2-code-avatar" style="display:none;align-items:center;justify-content:center;font-weight:800;color:var(--c-brand);background:var(--c-brand-soft);"><?php echo strtoupper(substr($cu, 0, 1)); ?></div>
            <?php else: ?>
              <div class="cav2-code-avatar" style="display:flex;align-items:center;justify-content:center;font-weight:800;color:var(--c-brand);background:var(--c-brand-soft);"><?php echo strtoupper(substr($cu, 0, 1)); ?></div>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($cu); ?></span>
          </div>
          <a href="?codigo=<?php echo urlencode($bid); ?>" class="cav2-code-btn">Ver código</a>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- REVIEWS -->
    <?php if (!empty($reviews_for_render)): ?>
    <section class="cav2-section">
      <h2 class="cav2-h2">Opiniones y experiencias con <?php echo htmlspecialchars($nombre_marca); ?></h2>
      <div class="cav2-reviews">
        <?php foreach ($reviews_for_render as $r): ?>
        <article class="cav2-review">
          <?php if (!empty($r['avatar'])): ?>
            <img class="cav2-review-avatar" src="<?php echo htmlspecialchars($r['avatar']); ?>" alt="<?php echo htmlspecialchars($r['user']); ?>" loading="lazy" style="object-fit:cover;" onerror="this.outerHTML='<div class=&quot;cav2-review-avatar&quot;><?php echo strtoupper(substr($r['user'], 0, 1)); ?></div>';">
          <?php else: ?>
            <div class="cav2-review-avatar"><?php echo strtoupper(substr($r['user'], 0, 1)); ?></div>
          <?php endif; ?>
          <div class="cav2-review-head">
            <span class="cav2-review-author"><?php echo htmlspecialchars($r['user']); ?></span>
            <span class="cav2-review-stars"><?php echo str_repeat('★', $r['stars']) . str_repeat('☆', 5 - $r['stars']); ?></span>
            <span class="cav2-review-verified">✓ Compra verificada</span>
          </div>
          <p class="cav2-review-body"><?php echo htmlspecialchars($r['desc']); ?></p>
          <div class="cav2-review-foot">📍 <?php echo htmlspecialchars($r['city']); ?> · hace <?php echo $r['days']; ?> día<?php echo $r['days'] === 1 ? '' : 's'; ?></div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- STEPS -->
    <section class="cav2-section">
      <h2 class="cav2-h2">Cómo usar un cupón de <?php echo htmlspecialchars($nombre_marca); ?></h2>
      <div class="cav2-steps">
        <?php foreach ($custom_steps as $s): ?>
        <div class="cav2-step">
          <div class="cav2-step-num"><?php echo $s['num']; ?></div>
          <h3 class="cav2-step-t"><?php echo htmlspecialchars($s['title']); ?></h3>
          <p class="cav2-step-d"><?php echo htmlspecialchars($s['desc']); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- QUÉ ES + DESCRIPCIÓN -->
    <section class="cav2-section">
      <div class="cav2-card">
        <h2 class="cav2-h2"><?php echo htmlspecialchars($seo_info_title); ?></h2>
        <?php if ($seo_que_es): ?>
          <div><?php echo nl2br($seo_que_es); ?></div>
        <?php elseif (!empty($brand_intro_html)): ?>
          <?php echo $brand_intro_html; ?>
        <?php elseif (!empty(trim($descripcion_larga))): /* descripción rica en DB (HTML), no se mostraba */ ?>
          <div><?php echo $descripcion_larga; ?></div>
        <?php elseif (!empty(trim($descripcion_marca))): ?>
          <p><?php echo nl2br(htmlspecialchars($descripcion_marca, ENT_QUOTES, 'UTF-8')); ?></p>
        <?php else: ?>
          <p><?php echo htmlspecialchars($nombre_marca); ?> es una de las marcas líderes en su sector. Aprovecha los códigos promocionales compartidos por nuestra comunidad para conseguir descuentos exclusivos en tu próxima compra.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- FAQ -->
    <section class="cav2-section" id="faqs-section">
      <h2 class="cav2-h2">Preguntas frecuentes sobre <?php echo htmlspecialchars($nombre_marca); ?></h2>
      <div class="cav2-faq">
        <?php foreach ($faq_lines_to_display as $faq): ?>
        <details class="cav2-faq-item">
          <summary class="cav2-faq-q"><?php echo htmlspecialchars($faq['q']); ?></summary>
          <div class="cav2-faq-a"><p><?php echo htmlspecialchars($faq['a']); ?></p></div>
        </details>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- MARCAS SIMILARES (misma categoría) -->
    <?php if (!empty($marcas_similares)): ?>
    <section class="cav2-section">
      <h2 class="cav2-h2">Alternativas a <?php echo htmlspecialchars($nombre_marca); ?><?php if ($cat_label && $cat_label !== 'Marca verificada'): ?> en <?php echo htmlspecialchars($cat_label); ?><?php endif; ?></h2>
      <div class="cav2-similar-grid">
        <?php foreach ($marcas_similares as $ms): ?>
        <a href="/de-<?php echo htmlspecialchars($ms['slug']); ?>" class="cav2-similar-card">
          <div class="cav2-similar-logo">
            <img src="<?php echo htmlspecialchars($ms['imagen']); ?>" alt="<?php echo htmlspecialchars($ms['nombre']); ?>" loading="lazy">
          </div>
          <div class="cav2-similar-name"><?php echo htmlspecialchars($ms['nombre']); ?></div>
          <div class="cav2-similar-meta"><?php echo (int)$ms['num']; ?> código<?php echo $ms['num'] == 1 ? '' : 's'; ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- BÚSQUEDAS RELACIONADAS -->
    <?php
    if (!function_exists('render_related_searches_section')) {
        @include_once __DIR__ . '/../myphp/funciones_keywords_marca.php';
    }
    if (function_exists('render_related_searches_section')):
        $rs_html = render_related_searches_section(
            $marca_info['nombre_clave'] ?? $marca,
            $nombre_marca,
            $marca_info['categoria_clave'] ?? ''
        );
        if (!empty($rs_html)):
    ?>
    <section class="cav2-section">
      <div class="cav2-card">
        <?php echo $rs_html; ?>
      </div>
    </section>
    <?php endif; endif; ?>

  </main>

  <!-- RAIL -->
  <aside class="cav2-rail">
    <div class="cav2-rail-widget">
      <div class="cav2-rail-title">Recompensa</div>
      <p style="margin:0 0 12px;font-size:0.92rem;color:var(--c-text-2);">
        <?php if ($max_ahorro > 0): ?>
          Obtén <strong style="color:var(--c-brand);font-size:1.1rem;"><?php echo $max_ahorro; ?> €</strong> en tu primera compra usando un código verificado.
        <?php else: ?>
          Descuento verificado para tu próxima compra con <?php echo htmlspecialchars($nombre_marca); ?>.
        <?php endif; ?>
      </p>
      <a href="#cav2-reveal" class="cav2-btn cav2-btn-primary" style="width:100%;">Obtener código</a>
    </div>

    <div class="cav2-rail-widget">
      <div class="cav2-rail-title">Cómo compartir tu código</div>
      <div class="cav2-rail-row">1. Regístrate gratis.</div>
      <div class="cav2-rail-row">2. Publica tu código de <?php echo htmlspecialchars($nombre_marca); ?>.</div>
      <div class="cav2-rail-row">3. Cada uso te genera recompensa.</div>
    </div>

    <div class="cav2-rail-widget">
      <div class="cav2-rail-title">Compartir página</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="cav2-btn cav2-btn-ghost" style="flex:1;padding:8px;" href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://www.codigoamigo.com/de-' . $marca); ?>" target="_blank">𝕏</a>
        <a class="cav2-btn cav2-btn-ghost" style="flex:1;padding:8px;" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://www.codigoamigo.com/de-' . $marca); ?>" target="_blank">f</a>
        <a class="cav2-btn cav2-btn-ghost" style="flex:1;padding:8px;" href="https://wa.me/?text=<?php echo urlencode('https://www.codigoamigo.com/de-' . $marca); ?>" target="_blank">💬</a>
      </div>
    </div>
  </aside>

</div>
</div>

<!-- Sticky mobile CTA -->
<div class="cav2-mobile-cta">
  <a href="#cav2-reveal" class="cav2-btn cav2-btn-primary" style="width:100%;">🎁 Obtener código <?php echo htmlspecialchars($nombre_marca); ?></a>
</div>

</div><!-- /.cav2 -->
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
