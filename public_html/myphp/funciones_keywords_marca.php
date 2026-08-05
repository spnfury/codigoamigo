<?php
/**
 * Funciones para obtener y gestionar keywords SEO de marcas
 * Fuentes: Google Suggest (gratis) + Google Search Console (API ya conectada)
 */

/**
 * Obtiene sugerencias de Google Suggest (Autocomplete) para una query
 * Endpoint gratuito, no requiere API key
 * 
 * @param string $query Término de búsqueda
 * @param string $lang Idioma (default: es)
 * @param string $country País (default: es)
 * @return array Lista de sugerencias
 */
function fetch_google_suggest($query, $lang = 'es', $country = 'es') {
    $url = 'https://suggestqueries.google.com/complete/search?' . http_build_query([
        'client' => 'firefox',
        'hl' => $lang,
        'gl' => $country,
        'q' => $query
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            'timeout' => 5
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return [];
    }
    
    $data = json_decode($result, true);
    
    // Google Suggest returns [query, [suggestions]]
    if (is_array($data) && isset($data[1]) && is_array($data[1])) {
        return $data[1];
    }
    
    return [];
}

/**
 * Obtiene TODAS las keywords potenciales para una marca desde Google Suggest
 * Hace múltiples queries con variaciones del nombre de marca
 * 
 * @param string $brand_name Nombre visible de la marca (ej: "Back Market")
 * @param string $brand_slug Slug de la marca (ej: "backmarket")
 * @return array Lista de keywords únicas con su fuente
 */
function get_suggest_keywords_for_brand($brand_name, $brand_slug) {
    $all_keywords = [];
    
    // Variaciones de query para maximizar cobertura
    $queries = [
        $brand_name,                                    // "Back Market"
        $brand_name . ' ',                              // "Back Market " (trailing space)
        'codigo descuento ' . $brand_name,              // "codigo descuento Back Market"
        'codigo amigo ' . $brand_name,                  // "codigo amigo Back Market"
        'cupones ' . $brand_name,                       // "cupones Back Market"
        'cupon ' . $brand_name,                         // "cupon Back Market"
        $brand_name . ' opiniones',                     // "Back Market opiniones"
        $brand_name . ' codigo',                        // "Back Market codigo"
        $brand_name . ' descuento',                     // "Back Market descuento"
        $brand_name . ' es fiable',                     // "Back Market es fiable"
        $brand_name . ' oferta',                        // "Back Market oferta"
        'codigo invitacion ' . $brand_name,             // "codigo invitacion Back Market"
        'codigo promocional ' . $brand_name,            // "codigo promocional Back Market"
    ];
    
    // Si el slug es diferente del nombre, también buscar con el slug
    $slug_clean = str_replace('-', ' ', $brand_slug);
    if (strtolower($slug_clean) !== strtolower($brand_name)) {
        $queries[] = $slug_clean;
        $queries[] = $slug_clean . ' ';
        $queries[] = 'codigo descuento ' . $slug_clean;
    }
    
    foreach ($queries as $query) {
        $suggestions = fetch_google_suggest(trim($query));
        
        foreach ($suggestions as $suggestion) {
            $normalized = mb_strtolower(trim($suggestion));
            if (!isset($all_keywords[$normalized])) {
                $all_keywords[$normalized] = [
                    'kw' => $normalized,
                    'source' => 'suggest',
                    'type' => classify_keyword($normalized, $brand_name)
                ];
            }
        }
        
        // Respetar rate limit suave (100 req/min → ~600ms entre requests)
        usleep(600000); // 600ms
    }
    
    return array_values($all_keywords);
}

/**
 * Clasifica una keyword por intención de búsqueda
 * 
 * @param string $keyword La keyword a clasificar
 * @param string $brand_name Nombre de la marca
 * @return string Tipo: transactional, informational, product, navigational, generic
 */
function classify_keyword($keyword, $brand_name) {
    $kw_lower = mb_strtolower($keyword);
    
    // Transaccionales: quieren comprar/ahorrar
    $transactional_patterns = [
        'codigo', 'cupon', 'cupón', 'descuento', 'oferta', 'precio', 
        'promocion', 'promoción', 'promo', 'gratis', 'ahorra', 'ahorro',
        'barato', 'rebaja', 'invitacion', 'invitación', 'referido',
        'amigo', 'promocional'
    ];
    foreach ($transactional_patterns as $pattern) {
        if (strpos($kw_lower, $pattern) !== false) {
            return 'transactional';
        }
    }
    
    // Informacionales: quieren saber algo
    $informational_patterns = [
        'que es', 'qué es', 'como', 'cómo', 'opinion', 'opinión',
        'review', 'fiable', 'seguro', 'funciona', 'vale la pena',
        'merece', 'alternativa', 'mejor', 'comparar', 'versus', 'vs',
        'ventaja', 'desventaja', 'experiencia'
    ];
    foreach ($informational_patterns as $pattern) {
        if (strpos($kw_lower, $pattern) !== false) {
            return 'informational';
        }
    }
    
    // Producto: mencionan un producto específico
    $product_patterns = [
        'iphone', 'samsung', 'ipad', 'macbook', 'airpods', 'galaxy',
        'pixel', 'huawei', 'xiaomi', 'portatil', 'portátil', 'movil',
        'móvil', 'tablet', 'ordenador', 'pc', 'consola', 'ps5', 'xbox',
        'tv', 'televisor', 'watch', 'reloj'
    ];
    foreach ($product_patterns as $pattern) {
        if (strpos($kw_lower, $pattern) !== false) {
            return 'product';
        }
    }
    
    // Navegacional: solo el nombre de la marca
    $brand_lower = mb_strtolower($brand_name);
    if (trim($kw_lower) === trim($brand_lower)) {
        return 'navigational';
    }
    
    return 'generic';
}

/**
 * Combina keywords de Google Suggest y Google Search Console
 * Deduplica y enriquece con datos de GSC cuando están disponibles
 * 
 * @param array $suggest_keywords Keywords de Google Suggest
 * @param array $gsc_keywords Keywords de Google Search Console
 * @return array Keywords combinadas y deduplicadas
 */
function merge_and_deduplicate_keywords($suggest_keywords, $gsc_keywords) {
    $merged = [];
    
    // Primero, indexar las keywords de GSC por keyword normalizada
    $gsc_map = [];
    foreach ($gsc_keywords as $gsc_kw) {
        if (isset($gsc_kw['error'])) continue;
        $normalized = mb_strtolower(trim($gsc_kw['kw']));
        $gsc_map[$normalized] = $gsc_kw;
    }
    
    // Agregar keywords de Suggest, enriqueciendo con datos GSC si existen
    foreach ($suggest_keywords as $suggest_kw) {
        $normalized = mb_strtolower(trim($suggest_kw['kw']));
        
        if (isset($gsc_map[$normalized])) {
            // Existe en ambas fuentes: enriquecer con datos de GSC
            $merged[$normalized] = [
                'kw' => $normalized,
                'type' => $suggest_kw['type'],
                'source' => 'both',
                'impressions' => $gsc_map[$normalized]['impressions'] ?? 0,
                'clicks' => $gsc_map[$normalized]['clicks'] ?? 0,
                'position' => $gsc_map[$normalized]['position'] ?? 0,
                'ctr' => $gsc_map[$normalized]['ctr'] ?? 0,
            ];
            // Remover del mapa GSC (ya procesada)
            unset($gsc_map[$normalized]);
        } else {
            // Solo en Suggest
            $merged[$normalized] = [
                'kw' => $normalized,
                'type' => $suggest_kw['type'],
                'source' => 'suggest',
                'impressions' => 0,
                'clicks' => 0,
                'position' => 0,
                'ctr' => 0,
            ];
        }
    }
    
    // Agregar keywords que solo están en GSC
    foreach ($gsc_map as $normalized => $gsc_kw) {
        $merged[$normalized] = [
            'kw' => $normalized,
            'type' => classify_keyword($normalized, ''),
            'source' => 'gsc',
            'impressions' => $gsc_kw['impressions'] ?? 0,
            'clicks' => $gsc_kw['clicks'] ?? 0,
            'position' => $gsc_kw['position'] ?? 0,
            'ctr' => $gsc_kw['ctr'] ?? 0,
        ];
    }
    
    // Ordenar: primero las que tienen impresiones, luego por fuente 'both' > 'gsc' > 'suggest'
    $result = array_values($merged);
    usort($result, function($a, $b) {
        // Primero por impresiones (desc)
        if ($a['impressions'] != $b['impressions']) {
            return $b['impressions'] - $a['impressions'];
        }
        // Luego por fuente (both > gsc > suggest)
        $source_priority = ['both' => 3, 'gsc' => 2, 'suggest' => 1];
        $a_priority = $source_priority[$a['source']] ?? 0;
        $b_priority = $source_priority[$b['source']] ?? 0;
        return $b_priority - $a_priority;
    });
    
    return $result;
}

/**
 * Guarda las keywords SEO en el documento de marca en MongoDB
 * 
 * @param string $brand_slug Slug de la marca
 * @param array $keywords Array de keywords procesadas
 * @return bool Éxito de la operación
 */
function save_brand_keywords($brand_slug, $keywords) {
    try {
        include_once __DIR__ . '/funciones_marca.php';
        $collection = getCollectionMarcas();
        
        $seo_data = [
            'updated_at' => date('Y-m-d H:i:s'),
            'total_keywords' => count($keywords),
            'keywords' => $keywords
        ];
        
        $result = $collection->updateOne(
            ['nombre_clave' => $brand_slug],
            ['$set' => ['seo_keywords' => $seo_data]]
        );
        
        return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
    } catch (Exception $e) {
        error_log("Error saving brand keywords for $brand_slug: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene las keywords SEO almacenadas para una marca
 * 
 * @param string $brand_slug Slug de la marca
 * @return array|null Keywords almacenadas o null si no existen
 */
function get_brand_keywords($brand_slug) {
    try {
        include_once __DIR__ . '/funciones_marca.php';
        $collection = getCollectionMarcas();
        
        $marca = $collection->findOne(
            ['nombre_clave' => $brand_slug],
            ['projection' => ['seo_keywords' => 1]]
        );
        
        if ($marca && isset($marca['seo_keywords'])) {
            $data = $marca['seo_keywords'];
            // Convertir de BSONDocument a array si es necesario
            if ($data instanceof \MongoDB\Model\BSONDocument || $data instanceof \MongoDB\Model\BSONArray) {
                $data = json_decode(json_encode($data), true);
            }
            return $data;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting brand keywords for $brand_slug: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene keywords filtradas por tipo para renderizado en la página
 * 
 * @param string $brand_slug Slug de la marca
 * @param string|null $type Tipo a filtrar (transactional, informational, product, etc.) o null para todos
 * @param int $limit Máximo de keywords a devolver
 * @return array Keywords filtradas
 */
function get_brand_keywords_by_type($brand_slug, $type = null, $limit = 20) {
    $data = get_brand_keywords($brand_slug);
    
    if (!$data || !isset($data['keywords'])) {
        return [];
    }
    
    $keywords = $data['keywords'];
    
    // Convertir subdocumentos BSON si es necesario
    if ($keywords instanceof \MongoDB\Model\BSONArray) {
        $keywords = json_decode(json_encode($keywords), true);
    }
    
    if ($type !== null) {
        $keywords = array_filter($keywords, function($kw) use ($type) {
            return isset($kw['type']) && $kw['type'] === $type;
        });
    }
    
    return array_slice(array_values($keywords), 0, $limit);
}

/**
 * Genera la sección HTML de "Búsquedas populares" para la página de marca
 * Estrategia SEO: foco en keyword "código amigo" + internal linking real
 * 
 * @param string $brand_slug Slug de la marca
 * @param string $brand_name Nombre visible de la marca
 * @param string $brand_category Categoría clave de la marca (para buscar marcas similares)
 * @param int $max_items Máximo de items a mostrar
 * @return string HTML de la sección
 */
function render_related_searches_section($brand_slug, $brand_name, $brand_category = '', $max_items = 20) {
    $data = get_brand_keywords($brand_slug);
    
    $has_keywords = ($data && isset($data['keywords']) && !empty($data['keywords']));
    
    // Procesar keywords si existen
    $by_type = ['transactional' => [], 'informational' => []];
    if ($has_keywords) {
        $keywords = $data['keywords'];
        if ($keywords instanceof \MongoDB\Model\BSONArray) {
            $keywords = json_decode(json_encode($keywords), true);
        }
        
        // Filtrar: excluir navigational y cortos
        $filtered = array_filter($keywords, function($kw) {
            if (!isset($kw['kw']) || !isset($kw['type'])) return false;
            if ($kw['type'] === 'navigational') return false;
            if (mb_strlen($kw['kw']) < 5) return false;
            // Solo nos interesan transactional e informational
            return in_array($kw['type'], ['transactional', 'informational']);
        });
        
        $filtered = array_slice(array_values($filtered), 0, $max_items);
        
        foreach ($filtered as $kw) {
            $type = $kw['type'];
            $by_type[$type][] = $kw;
        }
        
        // Priorizar keywords con "código amigo" al inicio del bloque transactional
        if (!empty($by_type['transactional'])) {
            usort($by_type['transactional'], function($a, $b) {
                $a_has_ca = (stripos($a['kw'], 'codigo amigo') !== false || stripos($a['kw'], 'código amigo') !== false) ? 1 : 0;
                $b_has_ca = (stripos($b['kw'], 'codigo amigo') !== false || stripos($b['kw'], 'código amigo') !== false) ? 1 : 0;
                if ($a_has_ca !== $b_has_ca) return $b_has_ca - $a_has_ca;
                // Segundo criterio: impressions
                return ($b['impressions'] ?? 0) - ($a['impressions'] ?? 0);
            });
        }
    }
    
    // Obtener marcas similares de la misma categoría
    $related_brands = [];
    if (!empty($brand_category) && $brand_category !== 'select') {
        if (!function_exists('getMarcas')) {
            include_once __DIR__ . '/funciones_marca.php';
        }
        try {
            $related_brands = getMarcas(8, $brand_category, [$brand_slug]);
        } catch (Exception $e) {
            $related_brands = [];
        }
    }
    
    // Si no hay nada que mostrar, salir
    if (empty($by_type['transactional']) && empty($by_type['informational']) && empty($related_brands)) {
        return '';
    }
    
    $html = '<div class="related-searches-brand" style="margin-top: 40px;">';
    $html .= '<div class="container">';
    
    // Título principal
    $html .= '<h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-left: 14px; position: relative; line-height: 1.25;">';
    $html .= '<span style="position:absolute;left:0;top:4px;bottom:4px;width:4px;background:#E30613;border-radius:2px;"></span>';
    $html .= '<i class="fas fa-search" style="color: #16a34a; margin-right: 10px;"></i>';
    $html .= 'Búsquedas populares sobre ' . htmlspecialchars($brand_name);
    $html .= '</h2>';
    
    // ─── BLOQUE 1: Cupones y Ofertas (transactional → enlaces a sección de códigos) ───
    if (!empty($by_type['transactional'])) {
        $html .= '<div class="kw-group" style="margin-bottom: 20px;">';
        $html .= '<h3 style="font-size: 1.1rem; color: #334155; margin-bottom: 12px; font-weight: 600;">';
        $html .= '<i class="fas fa-tag" style="color: #f59e0b;"></i> Cupones y Ofertas</h3>';
        $html .= '<div class="kw-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">';
        foreach ($by_type['transactional'] as $kw) {
            // Se enlaza a la sección de códigos para aportar valor como atajo rápido
            $html .= '<a href="#codigos-section" class="kw-chip kw-transactional scroll-to-codes" title="' . htmlspecialchars($kw['kw']) . '">';
            $html .= htmlspecialchars($kw['kw']);
            if (!empty($kw['impressions'])) {
                $html .= ' <span class="kw-imp">' . number_format($kw['impressions']) . '</span>';
            }
            $html .= '</a>';
        }
        $html .= '</div></div>';
    }
    
    // ─── BLOQUE 2: La gente también pregunta (informational → #faqs-section) ───
    if (!empty($by_type['informational'])) {
        $html .= '<div class="kw-group" style="margin-bottom: 20px;">';
        $html .= '<h3 style="font-size: 1.1rem; color: #334155; margin-bottom: 12px; font-weight: 600;">';
        $html .= '<i class="fas fa-question-circle" style="color: #a78bfa;"></i> La gente también pregunta</h3>';
        $html .= '<div class="kw-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">';
        foreach ($by_type['informational'] as $kw) {
            $search_url = '#faqs-section';
            $html .= '<a href="' . $search_url . '" class="kw-chip kw-informational" title="' . htmlspecialchars($kw['kw']) . '">';
            $html .= htmlspecialchars($kw['kw']);
            if (!empty($kw['impressions'])) {
                $html .= ' <span class="kw-imp">' . number_format($kw['impressions']) . '</span>';
            }
            $html .= '</a>';
        }
        $html .= '</div></div>';
    }
    
    // ─── BLOQUE 3: Código amigo de marcas similares (real internal linking) ───
    if (!empty($related_brands)) {
        $html .= '<div class="kw-group" style="margin-bottom: 20px;">';
        $html .= '<h3 style="font-size: 1.1rem; color: #334155; margin-bottom: 12px; font-weight: 600;">';
        $html .= '<i class="fas fa-link" style="color: #10b981;"></i> Código amigo de marcas similares</h3>';
        $html .= '<div class="kw-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">';
        foreach ($related_brands as $rb) {
            $rb_name = $rb['nombre'] ?? ucfirst($rb['nombre_clave']);
            $rb_slug = $rb['nombre_clave'];
            $rb_codes = $rb['numero_codigos'] ?? 0;
            $brand_url = '/de-' . urlencode($rb_slug);
            // Anchor text optimizado para "código amigo {marca}"
            $html .= '<a href="' . $brand_url . '" class="kw-chip kw-related-brand" title="Código amigo ' . htmlspecialchars($rb_name) . '">';
            $html .= 'Código amigo ' . htmlspecialchars($rb_name);
            if ($rb_codes > 0) {
                $html .= ' <span class="kw-imp">' . $rb_codes . '</span>';
            }
            $html .= '</a>';
        }
        $html .= '</div></div>';
    }
    
    $html .= '</div></div>';
    
    // CSS para los chips
    $html .= '<style>
    .kw-chips {
        display: flex; flex-wrap: wrap; gap: 8px;
        max-width: 100%;
    }
    .kw-chip {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 14px; border-radius: 999px;
        font-size: 0.83rem; font-weight: 600;
        text-decoration: none; transition: all 0.15s ease;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        max-width: 100%;
        white-space: normal;
        word-break: break-word;
        line-height: 1.35;
    }
    .kw-chip:hover { background: #fef2f3; color: #E30613; border-color: #E30613; transform: translateY(-1px); }
    .kw-chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; flex-shrink: 0; }
    .kw-transactional::before { background: #E30613; }
    .kw-informational::before { background: #64748b; }
    .kw-related-brand::before { background: #16a34a; }
    .kw-imp { font-size: 0.7rem; opacity: 0.8; padding: 2px 7px; background: rgba(15,23,42,0.06); border-radius: 999px; color: #64748b; }
    @media (max-width: 768px) {
        .kw-chip { font-size: 0.78rem; padding: 6px 12px; }
    }
    </style>';
    
    return $html;
}
?>
