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

    global $anula_adsense;


    if ($anula_adsense) {
        return '';
    }

    if (!should_show_adsense()) {
        return '';
    }

    return '
    <script>
    // Prevenir carga duplicada del script de AdSense
    if (!document.querySelector("script[src*=\"adsbygoogle.js\"]")) {
        var adsenseScript = document.createElement("script");
        adsenseScript.async = true;
        adsenseScript.setAttribute("data-ad-client", "' . ADSENSE_PUBLISHER_ID . '");
        adsenseScript.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
        adsenseScript.crossOrigin = "anonymous";
        adsenseScript.onerror = function() {
            console.warn("Error al cargar el script de AdSense");
        };
        document.head.appendChild(adsenseScript);
    }
    
    // Marcar que el script ya fue insertado para evitar duplicados
    window.adsenseScriptLoaded = true;
    </script>';
}

/**
 * Genera el código de AdSense para la parte superior de marcas
 */
function get_adsense_top_marcas() {
    if (!should_show_adsense()) {
        return '';
    }

    return '
    <!-- Codigoamigo_top_marcas -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_TOP_MARCAS . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (function() {
             try {
                 if (typeof window.adsbygoogle === "undefined") {
                     window.adsbygoogle = [];
                 }
                 
                 // Verificar si el elemento ya tiene un anuncio cargado
                 var adElement = document.currentScript.previousElementSibling;
                 if (adElement && adElement.classList.contains("adsbygoogle")) {
                     // Verificar si ya tiene un hijo (significa que ya está inicializado)
                     if (adElement.children.length > 0) {
                         return;
                     }
                     
                     // Verificar atributo data-adsbygoogle-status
                     var status = adElement.getAttribute("data-adsbygoogle-status");
                     if (status && status !== "") {
                         return;
                     }
                     
                     // Verificar si el elemento tiene el atributo data-processed
                     if (adElement.hasAttribute("data-processed")) {
                         return;
                     }
                 }
                 
                 // Marcar como procesado antes de hacer push
                 if (adElement) {
                     adElement.setAttribute("data-processed", "true");
                 }
                 
                 window.adsbygoogle.push({});
             } catch(e) {
                 // Silenciar errores de AdSense para evitar ruido en consola
                 if (e.message && e.message.indexOf("already have ads") === -1) {
                     console.warn("Error al cargar anuncio AdSense:", e);
                 }
             }
         })();
    </script>';
}

/**
 * Genera el código de AdSense para la parte superior
 */
function get_adsense_top() {
    if (!should_show_adsense()) {
        return '';
    }

    return '
    <!-- Codigoamigo - top -->
    <ins class="adsbygoogle moderno"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_TOP . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (function() {
             try {
                 if (typeof window.adsbygoogle === "undefined") {
                     window.adsbygoogle = [];
                 }
                 
                 var adElement = document.currentScript.previousElementSibling;
                 if (!adElement || !adElement.classList.contains("adsbygoogle")) {
                     return;
                 }
                 
                 // Evitar múltiples inicializaciones
                 if (adElement.getAttribute("data-processed") === "true") {
                     return;
                 }
                 
                 var status = adElement.getAttribute("data-adsbygoogle-status");
                 if (status && status !== "") {
                     if (adElement.getAttribute("data-adsInitialized") === "true") {
                         return;
                     }
                 }
                 
                 if (adElement.children.length > 0) {
                     adElement.setAttribute("data-adsInitialized", "true");
                     return;
                 }
                 
                 adElement.setAttribute("data-processed", "true");
                 window.adsbygoogle.push({});
             } catch(e) {
                 if (!e || !e.message || e.message.indexOf("adsbygoogle.push() error") === -1) {
                     console.warn("Error al cargar anuncio AdSense (top):", e);
                 }
             }
         })();
    </script>';
}

/**
 * Genera el código de AdSense para detalle lateral
 */
function get_adsense_detalle_lateral() {
    if (!should_show_adsense()) {
        return '';
    }

    return '
    <!-- Codigoamigo Detalle Lateral -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_DETALLE_LATERAL . '"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (function() {
             try {
                 if (typeof window.adsbygoogle === "undefined") {
                     window.adsbygoogle = [];
                 }
                 
                 var adElement = document.currentScript.previousElementSibling;
                 if (!adElement || !adElement.classList.contains("adsbygoogle")) {
                     return;
                 }
                 
                 if (adElement.children.length > 0) {
                     return;
                 }
                 
                 var status = adElement.getAttribute("data-adsbygoogle-status");
                 if (status && status !== "") {
                     return;
                 }
                 
                 if (adElement.hasAttribute("data-processed")) {
                     return;
                 }
                 
                 adElement.setAttribute("data-processed", "true");
                 window.adsbygoogle.push({});
             } catch(e) {
                 if (!e || !e.message || e.message.indexOf("adsbygoogle.push() error") === -1) {
                     console.warn("Error al cargar anuncio AdSense (detalle lateral):", e);
                 }
             }
         })();
    </script>';
}

/**
 * Genera el código de AdSense para entremedio
 */
function get_adsense_entremedio() {
    if (!should_show_adsense()) {
        return '';
    }

    return '
    <!-- Codigoamigo - entremedio -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="' . ADSENSE_SLOT_ENTREMEDIO . '"
         data-ad-format="rectangle"
         data-full-width-responsive="true"></ins>
    <script>
         (function() {
             try {
                 if (typeof window.adsbygoogle === "undefined") {
                     window.adsbygoogle = [];
                 }
                 
                 var adElement = document.currentScript.previousElementSibling;
                 if (!adElement || !adElement.classList.contains("adsbygoogle")) {
                     return;
                 }
                 
                 if (adElement.children.length > 0) {
                     return;
                 }
                 
                 var status = adElement.getAttribute("data-adsbygoogle-status");
                 if (status && status !== "") {
                     return;
                 }
                 
                 if (adElement.hasAttribute("data-processed")) {
                     return;
                 }
                 
                 adElement.setAttribute("data-processed", "true");
                 window.adsbygoogle.push({});
             } catch(e) {
                 if (!e || !e.message || e.message.indexOf("adsbygoogle.push() error") === -1) {
                     console.warn("Error al cargar anuncio AdSense (entremedio):", e);
                 }
             }
         })();
    </script>';
}

/**
 * Genera el código de Page Level Ads
 */
function get_adsense_page_level() {
    return '
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
    
    // Solo bloquear en panel admin y página de destaca
    if ($panel || strpos($_SERVER['REQUEST_URI'], "/destaca") !== false) {
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

/**
 * Genera el código de AdSense para búsquedas con keywords del término de búsqueda
 * @param string $search_term Término de búsqueda para usar como keyword
 * @param string $slot_id ID del slot de AdSense (opcional, usa uno por defecto si no se proporciona)
 * @param string $position Posición del anuncio: 'top' o 'middle' (por defecto: 'top')
 */
function get_adsense_search($search_term = '', $slot_id = null, $position = 'top') {
    // Verificar si se debe mostrar AdSense (pero siempre mostrar el contenedor si hay término de búsqueda)
    if (!should_show_adsense()) {
        // Aunque should_show_adsense retorne false, si hay término de búsqueda, mostrar el contenedor vacío
        // para que sea visible el espacio reservado
        return '<!-- AdSense deshabilitado para esta página -->';
    }
    
    // Sanitizar el término de búsqueda para usar como keyword
    $keyword = htmlspecialchars(trim($search_term), ENT_QUOTES, 'UTF-8');
    
    // Si no se proporciona un slot, usar el slot TOP por defecto
    $ad_slot = $slot_id ? $slot_id : ADSENSE_SLOT_TOP;
    
    // Usar un ID único para evitar duplicados
    $unique_id = 'search-ads-' . md5($keyword . $position . $ad_slot);
    
    // Preparar keywords para AdSense
    // AdSense puede usar las keywords del contenido y del término de búsqueda
    $keyword_escaped = addslashes($keyword);
    
    return '
    <!-- AdSense para búsqueda: ' . $keyword . ' -->
    <ins class="adsbygoogle ' . $unique_id . '"
         style="display:block; width:100%; max-width:100%; min-width:320px; min-height:100px;"
         data-ad-slot="' . $ad_slot . '"
         data-ad-format="auto"
         data-full-width-responsive="true"
         data-ad-channel="' . $keyword . '"></ins>
    <script>
         (function() {
             try {
                 if (typeof window.adsbygoogle === "undefined") {
                     window.adsbygoogle = [];
                 }
                 
                 var adElement = document.querySelector(".' . $unique_id . '");
                 if (!adElement || !adElement.classList.contains("adsbygoogle")) {
                     return;
                 }
                 
                 // Evitar múltiples inicializaciones
                 if (adElement.getAttribute("data-processed") === "true") {
                     return;
                 }
                 
                 var status = adElement.getAttribute("data-adsbygoogle-status");
                 if (status && status !== "") {
                     if (adElement.getAttribute("data-adsInitialized") === "true") {
                         return;
                     }
                 }
                 
                 if (adElement.children.length > 0) {
                     adElement.setAttribute("data-adsInitialized", "true");
                     return;
                 }
                 
                adElement.setAttribute("data-processed", "true");
                
                // Función para inicializar el anuncio cuando el script de AdSense esté listo
                function initializeSearchAd() {
                    try {
                        if (typeof window.adsbygoogle !== "undefined" && window.adsbygoogle.push) {
                            // Push con información de keywords para mejor targeting
                            var adConfig = {};
                            window.adsbygoogle.push(adConfig);
                            
                            // Establecer las keywords en el contexto de la página
                            // AdSense detectará automáticamente las keywords del contenido
                            try {
                                if (typeof document !== "undefined") {
                                    // AdSense usa el contenido de la página para determinar las keywords
                                    // El término de búsqueda ya está en el título y contenido
                                    var metaKeywords = document.querySelector(\'meta[name="keywords"]\');
                                    if (!metaKeywords) {
                                        var meta = document.createElement("meta");
                                        meta.name = "keywords";
                                        meta.content = "' . $keyword_escaped . '";
                                        document.head.appendChild(meta);
                                    }
                                }
                            } catch(e) {
                                // Ignorar errores al agregar meta keywords
                            }
                        } else {
                            // Si el script aún no está cargado, esperar un poco más
                            setTimeout(initializeSearchAd, 100);
                        }
                    } catch(e) {
                        // Si hay error, intentar de nuevo después de un tiempo
                        setTimeout(initializeSearchAd, 200);
                    }
                }
                
                // Esperar a que el script de AdSense esté cargado Y que el elemento tenga tamaño
                function waitForElementSize() {
                    if (adElement) {
                        var rect = adElement.getBoundingClientRect();
                        if (rect.width > 0 && rect.height > 0) {
                            // El elemento tiene tamaño, proceder con la inicialización
                            if (typeof window.adsbygoogle !== "undefined" && window.adsbygoogle.push) {
                                initializeSearchAd();
                            } else if (window.adsenseScriptLoaded) {
                                setTimeout(function() {
                                    if (typeof window.adsbygoogle !== "undefined" && window.adsbygoogle.push) {
                                        initializeSearchAd();
                                    }
                                }, 500);
                            } else {
                                setTimeout(waitForElementSize, 200);
                            }
                        } else {
                            // El elemento aún no tiene tamaño, esperar un poco más
                            setTimeout(waitForElementSize, 200);
                        }
                    }
                }
                
                // Esperar a que el script de AdSense esté cargado
                if (typeof window.adsbygoogle !== "undefined" && window.adsbygoogle.push) {
                    // Ya está cargado, pero verificar que el elemento tenga tamaño
                    setTimeout(waitForElementSize, 100);
                } else if (window.adsenseScriptLoaded) {
                    // El script está en proceso de carga, esperar un poco
                    setTimeout(waitForElementSize, 500);
                } else {
                    // El script aún no se ha iniciado, esperar más tiempo
                    setTimeout(waitForElementSize, 1000);
                    
                    // También intentar cuando el DOM esté listo
                    if (document.readyState === "loading") {
                        document.addEventListener("DOMContentLoaded", function() {
                            setTimeout(waitForElementSize, 500);
                        });
                    }
                    
                    // Y cuando la página esté completamente cargada
                    window.addEventListener("load", function() {
                        setTimeout(waitForElementSize, 500);
                    });
                }
             } catch(e) {
                 if (!e || !e.message || e.message.indexOf("adsbygoogle.push() error") === -1) {
                     console.warn("Error al cargar anuncio AdSense (búsqueda):", e);
                 }
             }
         })();
    </script>';
}
?>
