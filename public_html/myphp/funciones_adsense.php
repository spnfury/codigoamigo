<?php
/**
 * Funciones de Google AdSense - DESACTIVADO
 * 
 * AdSense ha sido desactivado temporalmente.
 * Todas las funciones devuelven cadenas vacías para no romper llamadas existentes.
 * Si se quiere reactivar en el futuro, restaurar el contenido original de este archivo.
 */

// Constantes mantenidas para evitar errores en código que las referencie
if (!defined('ADSENSE_PUBLISHER_ID')) define('ADSENSE_PUBLISHER_ID', '');
if (!defined('ADSENSE_PUBLISHER_ID_ALT')) define('ADSENSE_PUBLISHER_ID_ALT', '');
if (!defined('ADSENSE_SLOT_TOP_MARCAS')) define('ADSENSE_SLOT_TOP_MARCAS', '');
if (!defined('ADSENSE_SLOT_TOP')) define('ADSENSE_SLOT_TOP', '');
if (!defined('ADSENSE_SLOT_DETALLE_LATERAL')) define('ADSENSE_SLOT_DETALLE_LATERAL', '');
if (!defined('ADSENSE_SLOT_ENTREMEDIO')) define('ADSENSE_SLOT_ENTREMEDIO', '');

function get_adsense_header_code() { return ''; }
function get_adsense_top_marcas() { return ''; }
function get_adsense_top() { return ''; }
function get_adsense_detalle_lateral() { return ''; }
function get_adsense_entremedio() { return ''; }
function get_adsense_page_level() { return ''; }
function should_show_adsense() { return false; }
function echo_adsense_top() {}
function echo_adsense_top_marcas() {}
function echo_adsense_detalle_lateral() {}
function echo_adsense_entremedio() {}
function generate_adsense_container($ad_code = '', $class = '', $style = '') { return ''; }
function get_adsense_search($search_term = '', $slot_id = null, $position = 'top') { return ''; }
?>
