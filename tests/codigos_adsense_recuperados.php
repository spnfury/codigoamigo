<?php
/**
 * CÓDIGOS DE GOOGLE ADSENSE RECUPERADOS DE LA VERSIÓN ANTIGUA
 * 
 * Este archivo contiene todos los códigos de publicidad de Google AdSense
 * encontrados en la versión antigua del sitio web.
 */

// ============================================================================
// CONFIGURACIÓN PRINCIPAL DE ADSENSE
// ============================================================================

// Publisher ID principal
$adsense_publisher_id = "ca-pub-2091026230098067";

// Publisher ID alternativo (encontrado en listado_categorias.php)
$adsense_publisher_id_alt = "ca-pub-8991940088210256";

// ============================================================================
// CÓDIGO PRINCIPAL DE ADSENSE (Header)
// ============================================================================
function get_adsense_header_code() {
    return '
    <script async data-ad-client="ca-pub-2091026230098067" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js" crossorigin="anonymous"></script>
    
    <script>
    if (window.location.pathname !== "/destaca") {
        (adsbygoogle = window.adsbygoogle || []).push({});
    }
    </script>';
}

// ============================================================================
// SLOTS DE PUBLICIDAD ESPECÍFICOS
// ============================================================================

// Slot: Codigoamigo_top_marcas (2215822301)
function get_adsense_top_marcas() {
    return '
    <!-- Codigoamigo_top_marcas -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="2215822301"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

// Slot: Codigoamigo - top (9558662809)
function get_adsense_top() {
    return '
    <!-- Codigoamigo - top -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="9558662809"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

// Slot: Codigoamigo Detalle Lateral (2861865272)
function get_adsense_detalle_lateral() {
    return '
    <!-- Codigoamigo Detalle Lateral -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="2861865272"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

// Slot: Codigoamigo - entremedio (6883957062)
function get_adsense_entremedio() {
    return '
    <!-- Codigoamigo - entremedio -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-slot="6883957062"
         data-ad-format="rectangle"
         data-full-width-responsive="true"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>';
}

// ============================================================================
// CÓDIGO DE PAGE LEVEL ADS (listado_categorias.php)
// ============================================================================
function get_adsense_page_level() {
    return '
    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <script>
      (adsbygoogle = window.adsbygoogle || []).push({
        google_ad_client: "ca-pub-8991940088210256",
        enable_page_level_ads: true
      });
    </script>';
}

// ============================================================================
// FUNCIÓN PRINCIPAL DE ADSENSE (desde publicidad.php)
// ============================================================================
function google_adsense() {
    // Esta función estaba vacía en la versión antigua
    // pero se usaba para controlar cuándo mostrar publicidad
    return 1;
}

// ============================================================================
// CONFIGURACIÓN DE VISIBILIDAD
// ============================================================================

// Variables de control de publicidad encontradas:
// - $show_adsense: Controla si mostrar publicidad
// - $anula_adsense: Anula la publicidad
// - $panel: Si está en panel, no mostrar publicidad

// Condiciones para mostrar publicidad:
// 1. $show_adsense == 1
// 2. !$panel (no está en panel de administración)
// 3. strpos($title,"Descubre ") === false (no en páginas de descubrimiento)
// 4. window.location.pathname !== "/destaca" (no en página de destacar)

// ============================================================================
// IMPLEMENTACIÓN EN TEMPLATES
// ============================================================================

/*
UBICACIONES DONDE SE USABA CADA SLOT:

1. Header (_header.php):
   - Código principal con ca-pub-2091026230098067
   - Condición: !$anula_adsense && strpos($title,"Descubre ") === false

2. Páginas de marca (marca.php):
   - Slot 2215822301: En sección de video de marca
   - Slot 9558662809: En listado de códigos
   - Slot 2861865272: En detalle lateral (2 ubicaciones)

3. Funciones HTML (funciones_html.php):
   - Slot 6883957062: Entremedio de contenido

4. Listado de categorías (listado_categorias.php):
   - Page level ads con ca-pub-8991940088210256

5. Footer (_footer.php):
   - Llamada a google_adsense() si $show_adsense == 1 && !$panel
*/

// ============================================================================
// EJEMPLO DE USO
// ============================================================================

/*
// En el header:
<?php if(strpos($title,"Descubre ") === false && !$anula_adsense){ ?>
    <?php echo get_adsense_header_code(); ?>
<?php } ?>

// En páginas de marca:
<?php echo get_adsense_top_marcas(); ?>

// En listados:
<?php echo get_adsense_top(); ?>

// En detalles laterales:
<?php echo get_adsense_detalle_lateral(); ?>

// Entre contenido:
<?php echo get_adsense_entremedio(); ?>

// En footer:
<?php if($show_adsense == 1 && !$panel) { ?>
    <?php echo google_adsense(); ?>
<?php } ?>
*/

echo "Códigos de Google AdSense recuperados exitosamente.\n";
echo "Publisher ID principal: " . $adsense_publisher_id . "\n";
echo "Publisher ID alternativo: " . $adsense_publisher_id_alt . "\n";
echo "Total de slots encontrados: 4\n";
echo "Slots: 2215822301, 9558662809, 2861865272, 6883957062\n";
?>
