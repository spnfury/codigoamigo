<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * Configuración y funciones auxiliares para la página de marca
 */

// Configuración de la página
$brand_page_config = [
    'max_codes_display' => 6,
    'max_related_brands' => 6,
    'enable_voting' => true,
    'enable_sharing' => true,
    'enable_analytics' => true,
    'lazy_load_images' => true,
    'animation_duration' => 600, // milisegundos
    'toast_duration' => 3000, // milisegundos
];

/**
 * Obtiene la configuración de la página
 */
function get_brand_page_config($key = null) {
    global $brand_page_config;
    
    if ($key === null) {
        return $brand_page_config;
    }
    
    return isset($brand_page_config[$key]) ? $brand_page_config[$key] : null;
}

/**
 * Formatea el número de códigos para mostrar
 */
function format_codes_count($count) {
    if ($count >= 1000000) {
        return number_format($count / 1000000, 1) . 'M';
    } elseif ($count >= 1000) {
        return number_format($count / 1000, 1) . 'K';
    }
    
    return number_format($count, 0, ',', '.');
}

/**
 * Genera el texto del beneficio de la marca
 */
function get_brand_benefit_text($beneficio) {
    if (!$beneficio || $beneficio <= 0) {
        return null;
    }
    
    return $beneficio . '€ Beneficio';
}

/**
 * Obtiene la descripción truncada del código
 */
function get_truncated_description($description, $max_length = 150) {
    if (strlen($description) <= $max_length) {
        return $description;
    }
    
    return substr($description, 0, $max_length) . '...';
}

/**
 * Genera las clases CSS para la tarjeta de código
 */
function get_code_card_classes($item) {
    $classes = ['code-card'];
    
    if (isset($item['destacado']) && $item['destacado'] > 0) {
        $classes[] = 'featured';
    }
    
    if (isset($item['nuevo']) && $item['nuevo']) {
        $classes[] = 'new';
    }
    
    return implode(' ', $classes);
}

/**
 * Obtiene los datos del usuario de forma segura
 */
function get_safe_user_data($user_id) {
    $usuario = getObjectUser('_id', $user_id);
    
    if (!$usuario) {
        return [
            'username' => 'Usuario',
            'img' => '/assets/img/default-avatar.png',
            '_id' => ''
        ];
    }
    
    $datos_usuario = get_array_de_usuario($usuario);
    
    return [
        'username' => $datos_usuario['username'] ?? 'Usuario',
        'img' => $datos_usuario['img'] ?? '/assets/img/default-avatar.png',
        '_id' => $datos_usuario['_id'] ?? ''
    ];
}

/**
 * Genera el enlace de compartir para una plataforma específica
 */
function get_share_url($platform, $url, $title = '') {
    $encoded_url = urlencode($url);
    $encoded_title = urlencode($title);
    
    switch ($platform) {
        case 'facebook':
            return "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}";
        case 'twitter':
            return "https://twitter.com/intent/tweet?url={$encoded_url}&text={$encoded_title}";
        case 'whatsapp':
            return "https://wa.me/?text={$encoded_title}%20{$encoded_url}";
        case 'telegram':
            return "https://t.me/share/url?url={$encoded_url}&text={$encoded_title}";
        case 'linkedin':
            return "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}";
        default:
            return $url;
    }
}

/**
 * Verifica si el usuario puede votar
 */
function can_user_vote($user_id, $codigo_id) {
    if (empty($user_id) || empty($codigo_id)) {
        return false;
    }

    try {
        // Incluir funciones de código si no están disponibles
        if (!function_exists('getCollectionVotos')) {
            include_once __DIR__ . '/../myphp/funciones_codigo.php';
        }

        $collection_votos = getCollectionVotos();
        
        // Verificar si el usuario ya votó este código
        $voto_existente = $collection_votos->findOne([
            'usuario_id' => $user_id,
            'codigo_id' => $codigo_id
        ]);

        // Si no existe voto, puede votar
        return $voto_existente === null;
    } catch (Exception $e) {
        // En caso de error, permitir votar (fallback)
        if (function_exists('log_warning')) {
            log_warning("Error verificando voto", ['error' => $e->getMessage()]);
        }
        return true;
    }
}

/**
 * Obtiene el tipo de voto del usuario para un código
 */
function get_user_vote_type($user_id, $codigo_id) {
    if (empty($user_id) || empty($codigo_id)) {
        return null;
    }

    try {
        if (!function_exists('getCollectionVotos')) {
            include_once __DIR__ . '/../myphp/funciones_codigo.php';
        }

        $collection_votos = getCollectionVotos();
        
        $voto = $collection_votos->findOne([
            'usuario_id' => $user_id,
            'codigo_id' => $codigo_id
        ]);

        return $voto ? ($voto['tipo_voto'] ?? null) : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtiene el contador de votos formateado
 */
function get_vote_count($item) {
    $positivos = $item['votos_positivos'] ?? 0;
    $negativos = $item['votos_negativos'] ?? 0;
    
    return $positivos - $negativos;
}

/**
 * Genera el texto de la fecha relativa
 */
function get_relative_date($date) {
    if (!$date) {
        return 'Hace un tiempo';
    }
    
    return formatDateAgoLarge($date);
}

/**
 * Obtiene la provincia formateada
 */
function get_formatted_province($provincia) {
    if (!$provincia) {
        return 'España';
    }
    
    return ucfirst($provincia);
}

/**
 * Verifica si hay códigos destacados
 */
function has_featured_codes($lista_codigos_patrocinados) {
    return !empty($lista_codigos_patrocinados) && count($lista_codigos_patrocinados) > 0;
}

/**
 * Verifica si hay códigos normales
 */
function has_normal_codes($lista_codigos) {
    return !empty($lista_codigos) && count($lista_codigos) > 0;
}

/**
 * Obtiene el número total de códigos
 */
function get_total_codes_count($lista_codigos_patrocinados, $lista_codigos) {
    $count = 0;
    
    if (has_featured_codes($lista_codigos_patrocinados)) {
        $count += count($lista_codigos_patrocinados);
    }
    
    if (has_normal_codes($lista_codigos)) {
        $count += count($lista_codigos);
    }
    
    return $count;
}

/**
 * Genera el texto del botón de acción principal
 */
function get_primary_button_text($item) {
    if (isset($item['destacado']) && $item['destacado'] > 0) {
        return '<i class="fas fa-star"></i> Ver Código Destacado';
    }
    
    return '<i class="fas fa-arrow-right"></i> Ver Código';
}

/**
 * Obtiene las marcas relacionadas
 */
function get_related_brands($marca, $limit = 6) {
    if (!$marca || !isset($marca['categoria_clave']) || !$marca['categoria_clave']) {
        return [];
    }
    
    $marcas_relacionadas = get_all_marcas_panel_control($limit, '', $marca['categoria_clave']);
    
    // Filtrar la marca actual
    return array_filter($marcas_relacionadas, function($marca_rel) use ($marca) {
        return isset($marca_rel['nombre_clave']) && isset($marca['nombre_clave']) && $marca_rel['nombre_clave'] !== $marca['nombre_clave'];
    });
}

/**
 * Genera los metadatos de la página
 */
function generate_brand_page_meta($marca, $numero_codigos) {
    $title = "Códigos de Descuento {$marca['nombre']} - {$numero_codigos} Cupones Gratis";
    $description = "Encuentra los mejores códigos de descuento para {$marca['nombre']}. " . 
                   "Ahorra dinero con nuestros {$numero_codigos} cupones verificados y actualizados diariamente.";
    
    return [
        'title' => $title,
        'description' => $description,
        'keywords' => "códigos descuento {$marca['nombre']}, cupones {$marca['nombre']}, ofertas {$marca['nombre']}",
        'og_title' => $title,
        'og_description' => $description,
        'og_image' => $marca['imagen'] ?? '/assets/img/default-brand.jpg'
    ];
}

/**
 * Valida los datos de entrada
 */
function validate_brand_page_data($marca, $lista_codigos, $lista_codigos_patrocinados) {
    $errors = [];
    
    if (!$marca || !isset($marca['nombre'])) {
        $errors[] = 'Datos de marca inválidos';
    }
    
    if (!is_array($lista_codigos)) {
        $errors[] = 'Lista de códigos inválida';
    }
    
    if (!is_array($lista_codigos_patrocinados)) {
        $errors[] = 'Lista de códigos patrocinados inválida';
    }
    
    return $errors;
}

/**
 * Sanitiza los datos de entrada
 */
function sanitize_brand_page_data($data) {
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    if (is_array($data)) {
        return array_map('sanitize_brand_page_data', $data);
    }
    
    return $data;
}

/**
 * Obtiene la configuración de analytics
 */
function get_analytics_config() {
    return [
        'track_page_views' => true,
        'track_button_clicks' => true,
        'track_votes' => true,
        'track_shares' => true,
        'custom_events' => [
            'brand_page_view',
            'code_card_click',
            'vote_submitted',
            'share_clicked'
        ]
    ];
}

/**
 * Registra un evento de analytics
 */
function track_analytics_event($event_name, $parameters = []) {
    // Aquí se implementaría la integración con Google Analytics, Facebook Pixel, etc.
    log_info("Analytics Event: {$event_name} - " . json_encode($parameters));
}

/**
 * Obtiene la configuración de SEO
 */
function get_seo_config($marca, $numero_codigos) {
    return [
        'title' => "Códigos de Descuento {$marca['nombre']} - {$numero_codigos} Cupones Gratis",
        'description' => "Encuentra los mejores códigos de descuento para {$marca['nombre']}. " . 
                        "Ahorra dinero con nuestros {$numero_codigos} cupones verificados y actualizados diariamente.",
        'keywords' => "códigos descuento {$marca['nombre']}, cupones {$marca['nombre']}, ofertas {$marca['nombre']}, descuentos {$marca['nombre']}",
        'canonical' => "https://www.codigoamigo.com/" . $marca['nombre_clave'],
        'robots' => 'index, follow',
        'og_type' => 'website',
        'og_site_name' => 'Código Amigo',
        'twitter_card' => 'summary_large_image',
        'twitter_site' => '@codigoamigo'
    ];
}
?>



