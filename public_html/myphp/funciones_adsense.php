<?php
/**
 * Funciones de Google AdSense
 *
 * Para activar: cambiar ADSENSE_ACTIVO a true (confirmar antes que la cuenta
 * ca-pub-2091026230098067 está activa y sin suspensión en adsense.google.com).
 */

define('ADSENSE_ACTIVO', true);

if (!defined('ADSENSE_PUBLISHER_ID'))      define('ADSENSE_PUBLISHER_ID',     'ca-pub-2091026230098067');
if (!defined('ADSENSE_PUBLISHER_ID_ALT'))  define('ADSENSE_PUBLISHER_ID_ALT', 'ca-pub-8991940088210256');
if (!defined('ADSENSE_SLOT_TOP_MARCAS'))   define('ADSENSE_SLOT_TOP_MARCAS',   '2215822301');
if (!defined('ADSENSE_SLOT_TOP'))          define('ADSENSE_SLOT_TOP',          '9558662809');
if (!defined('ADSENSE_SLOT_DETALLE_LATERAL')) define('ADSENSE_SLOT_DETALLE_LATERAL', '2861865272');
if (!defined('ADSENSE_SLOT_ENTREMEDIO'))   define('ADSENSE_SLOT_ENTREMEDIO',   '6883957062');

function should_show_adsense() {
    if (!ADSENSE_ACTIVO) return false;
    global $anula_adsense;
    return empty($anula_adsense);
}

function get_adsense_header_code() {
    if (!ADSENSE_ACTIVO) return '';
    return '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js" data-ad-client="' . ADSENSE_PUBLISHER_ID . '" crossorigin="anonymous"></script>';
}

function get_adsense_top_marcas() {
    if (!ADSENSE_ACTIVO) return '';
    return '<!-- Codigoamigo_top_marcas -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="' . ADSENSE_PUBLISHER_ID . '"
         data-ad-slot="' . ADSENSE_SLOT_TOP_MARCAS . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

function get_adsense_top() {
    if (!ADSENSE_ACTIVO) return '';
    return '<!-- Codigoamigo - top -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="' . ADSENSE_PUBLISHER_ID . '"
         data-ad-slot="' . ADSENSE_SLOT_TOP . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

function get_adsense_detalle_lateral() {
    if (!ADSENSE_ACTIVO) return '';
    return '<!-- Codigoamigo Detalle Lateral -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="' . ADSENSE_PUBLISHER_ID . '"
         data-ad-slot="' . ADSENSE_SLOT_DETALLE_LATERAL . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

function get_adsense_entremedio() {
    if (!ADSENSE_ACTIVO) return '';
    return '<!-- Codigoamigo - entremedio -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="' . ADSENSE_PUBLISHER_ID . '"
         data-ad-slot="' . ADSENSE_SLOT_ENTREMEDIO . '"
         data-ad-format="rectangle"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

function get_adsense_page_level() {
    if (!ADSENSE_ACTIVO) return '';
    return '<script>(adsbygoogle = window.adsbygoogle || []).push({ google_ad_client: "' . ADSENSE_PUBLISHER_ID_ALT . '", enable_page_level_ads: true });</script>';
}

function echo_adsense_top()           { echo get_adsense_top(); }
function echo_adsense_top_marcas()    { echo get_adsense_top_marcas(); }
function echo_adsense_detalle_lateral() { echo get_adsense_detalle_lateral(); }
function echo_adsense_entremedio()    { echo get_adsense_entremedio(); }

function generate_adsense_container($ad_code = '', $class = '', $style = '') {
    if (!ADSENSE_ACTIVO || empty($ad_code)) return '';
    $class_attr = $class ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    $style_attr = $style ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '';
    return '<div' . $class_attr . $style_attr . '>' . $ad_code . '</div>';
}

function get_adsense_search($search_term = '', $slot_id = null, $position = 'top') {
    return '';
}
?>
