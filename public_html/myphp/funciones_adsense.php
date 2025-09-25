<?php
/**
 * Funciones de Google AdSense para el diseño moderno
 * Basado en los códigos recuperados de la versión antigua
 */

// Publisher IDs
define('ADSENSE_PUBLISHER_ID', 'ca-pub-2091026230098067');
define('ADSENSE_PUBLISHER_ID_ALT', 'ca-pub-8991940088210256');

// Slots de publicidad
define('ADSENSE_SLOT_TOP_MARCAS', '2215822301');
define('ADSENSE_SLOT_TOP', '9558662809');
define('ADSENSE_SLOT_DETALLE_LATERAL', '2861865272');
define('ADSENSE_SLOT_ENTREMEDIO', '6883957062');

/**
 * Genera el código principal de AdSense para el header
 */
function get_adsense_header_code() {
    global $anula_adsense, $title;
    
    // No mostrar en páginas de descubrimiento o si está anulado
    if (($title && strpos($title, "Descubre ") !== false) || $anula_adsense) {
        return '';
    }
    
    return '
    <script async data-ad-client="' . ADSENSE_PUBLISHER_ID . '" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js" crossorigin="anonymous"></script>
    
    <script>
    if (window.location.pathname !== "/destaca") {
        (adsbygoogle = window.adsbygoogle || []).push({});
    }
    </script>';
}

/**
 * Genera el código de AdSense para la parte superior de marcas
 */
function get_adsense_top_marcas() {
    return '
    <!-- Codigoamigo_top_marcas -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_TOP_MARCAS . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

/**
 * Genera el código de AdSense para la parte superior
 */
function get_adsense_top() {
    return '
    <!-- Codigoamigo - top -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_TOP . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

/**
 * Genera el código de AdSense para detalle lateral
 */
function get_adsense_detalle_lateral() {
    return '
    <!-- Codigoamigo Detalle Lateral -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_DETALLE_LATERAL . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

/**
 * Genera el código de AdSense para entremedio
 */
function get_adsense_entremedio() {
    return '
    <!-- Codigoamigo - entremedio -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_ENTREMEDIO . '"
         data-ad-format="rectangle"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

/**
 * Genera el código de Page Level Ads
 */
function get_adsense_page_level() {
    return '
    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <script>
      (adsbygoogle = window.adsbygoogle || []).push({
        google_ad_client: "' . ADSENSE_PUBLISHER_ID_ALT . '",
        enable_page_level_ads: true
      });
    </script>';
}

/**
 * La función google_adsense() ya está definida en publicidad.php
 * No la redeclaramos aquí para evitar conflictos
 */

/**
 * Verifica si se debe mostrar publicidad
 */
function should_show_adsense() {
    global $show_adsense, $panel, $anula_adsense, $title, $noindex;
    
    // No mostrar en páginas con noindex, en el panel de administración,
    // o en páginas de "Descubre" o "/destaca"
    if ($noindex == 1 || $panel || ($title && strpos($title, "Descubre ") !== false) || strpos($_SERVER['REQUEST_URI'], "/destaca") !== false) {
        return false;
    }
    
    // Si $anula_adsense está definido y es true, no mostrar
    if (isset($anula_adsense) && $anula_adsense) {
        return false;
    }
    
    return true;
}

/**
 * Genera un contenedor de publicidad con estilos
 */
function generate_adsense_container($ad_code, $class = 'adsense-container', $style = '') {
    if (!should_show_adsense()) {
        return '';
    }
    
    $default_style = 'text-align: center; margin: 20px 0; padding: 10px;';
    $final_style = $style ? $default_style . ' ' . $style : $default_style;
    
    return '<div class="' . $class . '" style="' . $final_style . '">' . $ad_code . '</div>';
}
?>
