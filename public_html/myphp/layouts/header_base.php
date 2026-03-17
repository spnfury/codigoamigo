<?php
// Defaults
$title = isset($title) ? $title : "";
$description = isset($description) ? $description : "";
$title_social = isset($title_social) ? $title_social : "";
$description_social = isset($description_social) ? $description_social : "";
$imagen_social = isset($imagen_social) ? $imagen_social : "";
$links_meta = isset($links_meta) ? $links_meta : '';

require_once __DIR__ . '/../../inc/sentry_bootstrap.php';
require_once __DIR__ . '/../links.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

global $author_web, $img_compartir_pagina, $ubicacion_actual, $force_css, $name_page;
global $provincia, $data_usuario, $detect, $author_web, $datos_usuario, $que_es;
global $noindex, $nombre_pag, $anula_adsense;

// Initialization checks
if (!isset($anula_adsense)) {
    if (isset($GLOBALS['anula_adsense'])) {
        $anula_adsense = $GLOBALS['anula_adsense'];
    } else {
        $anula_adsense = false;
    }
}
if (!isset($noindex)) {
    $noindex = 0;
}
if (!isset($panel)) {
    $panel = false;
}


    // Inicializar variables si no están definidas
    // Verificar primero en $GLOBALS antes de inicializar
    if (!isset($anula_adsense)) {
        if (isset($GLOBALS['anula_adsense'])) {
            $anula_adsense = $GLOBALS['anula_adsense'];
        } else {
            $anula_adsense = false;
        }
    }
    if (!isset($noindex)) {
        $noindex = 0;
    }
    if (!isset($panel)) {
        $panel = false;
    }
    
    ?><!DOCTYPE html>
    <html lang="es">
    <head>
        <title><?php echo $title; ?></title>
        <link rel="shortcut icon" href="/img/favicon_moneda_real.png">
        
        <!-- Preconnect para recursos externos (reduce 200-500ms por conexión) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com">
        <link rel="preconnect" href="https://cdn.jsdelivr.net">
        <link rel="dns-prefetch" href="https://pagead2.googlesyndication.com">
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
        <?php
        if(strpos($_SERVER['SERVER_NAME'],"dev.") !==false || $que_es==1 || $noindex == 1 || (isset($datos_usuario) && isset($datos_usuario["username"])) || isset($_GET["codigo"]) || isset($_GET["page"]) || isset($provincia) || (isset($args) && isset($args["mes"])) || (isset($GLOBALS["actual_url"]) && strpos($GLOBALS["actual_url"],"/public/") !==false)){ ?>
            <meta name="robots" content="noindex" />
        <?php }else{ ?>
            <meta name="robots" content="index,follow" />
        <?php } ?>

        <meta name="author" content="<?php echo $author_web; ?>">
        <meta name="description" content="<?php echo $description; ?>">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">

        <?php if(!isset($_GET["page"])){ ?>
            <link rel="canonical" href="<?php echo isset($GLOBALS["actual_url_limpia"]) ? $GLOBALS["actual_url_limpia"] : ''; ?>"/>
        <?php } ?>

        <!-- Open Graph -->
        <meta property="og:title" content="<?php echo $title_social ? $title_social : $title; ?>">
        <meta property="og:description" content="<?php echo $description_social ? $description_social : $description; ?>">
        <meta property="og:image" content="<?php echo $imagen_social ? $imagen_social : 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'; ?>">
        <meta property="og:url" content="<?php echo isset($GLOBALS["actual_url"]) ? $GLOBALS["actual_url"] : ''; ?>">
        <meta property="og:type" content="website">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo $title_social ? $title_social : $title; ?>">
        <meta name="twitter:description" content="<?php echo $description_social ? $description_social : $description; ?>">
        <meta name="twitter:image" content="<?php echo $imagen_social ? $imagen_social : 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'; ?>">

        <!-- Plausible Analytics -->
        <script defer data-domain="codigoamigo.com" src="https://clase-plausible.s0e6bf.easypanel.host/js/script.js"></script>

        <!-- CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/css/design-fixed.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="/css/modern-design.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="/css/mobile-header-new.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="/css/mobile-new-design.css?v=<?php echo time(); ?>">
        
        <style>
            /* Premium Instagram-style VIP Badges */
            .vip-avatar-wrapper {
                position: relative;
                display: inline-block;
            }
            .vip-avatar-wrapper .verified-badge {
                position: absolute;
                bottom: 5%;
                right: 5%;
                width: 25%;
                height: 25%;
                min-width: 18px;
                min-height: 18px;
                background: #ffd700;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 5;
            }
            .vip-avatar-wrapper .verified-badge i {
                font-size: 0.6em;
            }
            
            /* User Badge styling */
            .vip-premium-badge {
                background: linear-gradient(135deg, #ffd700 0%, #f9a825 100%);
                color: #fff;
                padding: 3px 10px;
                border-radius: 50px;
                font-size: 0.75rem;
                font-weight: 900;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                vertical-align: middle;
                margin-left: 8px;
                box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
                border: 1px solid rgba(255, 255, 255, 0.2);
                text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .vip-premium-badge i {
                font-size: 0.85rem;
            }
            
            /* Dashboard Avatar Specific */
            .dashboard-avatar-vip {
                position: relative;
                width: 60px;
                height: 60px;
            }
            .dashboard-avatar-vip .verified-tick {
                position: absolute;
                bottom: -2px;
                right: -2px;
                background: #ffd700;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                border: 3px solid #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            }
        </style>
        
        <?php if($force_css==1){ ?>
            <!-- CSS adicional solo si es necesario -->
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css?a=<?php echo strtotime("now"); ?>">
        <?php }else{ ?>
            <!-- CSS adicional solo si es necesario -->
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css">
        <?php } ?>
        <link rel="stylesheet" href="/css/notificaciones.css?v=<?php echo file_exists(__DIR__ . '/../css/notificaciones.css') ? filemtime(__DIR__ . '/../css/notificaciones.css') : time(); ?>">
        <style>
        /* Estilos específicos adicionales si son necesarios */
        .btn-publicar span {
            display: inline;
        }

        @media (max-width: 768px) {
            .btn-publicar span {
                display: none;
            }
        }

        /* Estilos mejorados para el buscador */
        .search-input-header,
        .search-input-hero,
        .mobile-search-input {
            transition: all 0.3s ease !important;
        }

        .search-input-header:focus,
        .search-input-hero:focus,
        .mobile-search-input:focus {
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.2) !important;
            border-color: #E30613 !important;
        }

        .search-btn:hover,
        .btn-buscar:hover {
            background-color: rgba(227, 6, 19, 0.9) !important;
            transform: translateY(-1px) !important;
        }

        /* Debug indicator para desarrollo */
        .search-debug {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            display: none; /* Oculto por defecto, mostrar solo en desarrollo */
        }
        </style>
        
        <!-- CSS para header fijo -->
        <style>
        /* HEADER FIJO - DESKTOP Y MÓVIL */
        .header-modern.desktop-only,
        .header-modern.mobile-only {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            width: auto !important; /* Force auto so left/right works */
            transition: all 0.3s ease;
        }
        
        /* Ajustar el body para compensar el header fijo */
        body {
            margin-top: 80px !important;
        }

        /* Asegurar que el contenedor use el 100% del ancho disponible */
        /* Header containers and basic layout */
        .header-modern {
            background-color: #2d2d2d;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header-modern.scrolled {
            background-color: rgba(45, 45, 45, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .header-container {
            width: 100% !important;
            max-width: 1400px !important;
            height: 100%;
            padding: 0 20px !important;
            margin: 0 auto !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-wrap: nowrap !important; /* Strictly no line breaks */
            align-items: center !important;
            justify-content: space-between !important;
            gap: 15px;
        }
        
        /* Nav modern optimization */
        .nav-modern {
            display: flex !important;
            align-items: center !important;
            gap: 5px; /* Reduced gap to fit more items */
            flex: 1;
            justify-content: center;
            min-width: 0;
        }
        
        .nav-link {
            white-space: nowrap !important;
            font-size: 14px !important;
            padding: 8px 10px !important; /* Slightly more compact */
            color: #fff !important;
            font-weight: 500;
        }

        /* Search header optimization */
        .search-header {
            display: flex !important;
            align-items: center !important;
            gap: 10px;
            flex-shrink: 0;
        }

        .search-container {
            width: 250px;
            max-width: 250px;
            transition: all 0.3s ease;
        }

        .search-container:focus-within {
            width: 320px;
            max-width: 350px;
        }

        /* Logo section optimization */
        .logo-section {
            flex-shrink: 0;
            margin-right: 10px;
        }

        /* User menu and Publicar button */
        .user-menu {
            display: flex;
            align-items: center !important;
            gap: 8px;
        }

        .btn-publicar {
            white-space: nowrap !important;
            padding: 8px 14px !important;
            font-size: 13px !important;
            flex-shrink: 0;
            display: flex !important;
            align-items: center !important;
            gap: 6px;
            border-radius: 20px !important;
            background-color: #E30613 !important;
            color: #fff !important;
            border: none !important;
            font-weight: 600 !important;
        }

        /* Responsive Visibility */
        @media (max-width: 900px) {
            .nav-modern {
                display: none !important; /* Hide nav links on smaller desktop screens instead of breaking */
            }
        }

        @media (max-width: 768px) {
            body { margin-top: 0 !important; }
            .header-modern.mobile-only { display: block !important; height: 48px; }
            .header-modern.desktop-only { display: none !important; }
        }
        
        @media (min-width: 769px) {
            body { margin-top: 80px !important; }
            .header-modern.desktop-only { display: block !important; height: 80px; }
            .header-modern.mobile-only { display: none !important; }
        }

        /* Mobile Header Fixes (from funciones_modern.php classes) */
        .mobile-header-top {
            background-color: #2d2d2d !important;
            border-bottom: 1px solid #3d3d3d !important;
        }
        
        .mobile-header-content-unified {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 12px !important;
            height: 48px !important;
            gap: 10px !important;
        }

        .mobile-iconotype-link {
            flex-shrink: 0 !important;
        }

        .mobile-search-box-unified {
            flex: 1 !important;
            position: relative !important;
            min-width: 0 !important;
        }

        .mobile-search-box-unified .search-icon {
            position: absolute !important;
            left: 14px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: rgba(255,255,255,0.45) !important;
            font-size: 14px !important;
            z-index: 2 !important;
            pointer-events: none !important;
        }

        .mobile-search-input-header {
            background: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.15) !important;
            border-radius: 20px !important;
            color: #fff !important;
            width: 100% !important;
            font-size: 14px !important;
            padding: 0 15px 0 38px !important;
            height: 38px !important;
            outline: none !important;
        }
        </style>
        
        <?php if($force_css){ ?>
            <link rel="stylesheet" href="<?php echo $force_css; ?>">
        <?php } ?>

        <?php
        if (function_exists('codigoamigo_get_sentry_browser_snippet')) {
            echo codigoamigo_get_sentry_browser_snippet();
        }
        ?>

        <!-- Indicador de debug para desarrollo -->
        <div class="search-debug"></div>

        <!-- JavaScript -->
        <script>
            // Configuración global para manejar errores de JavaScript
            window.onerror = function(message, source, lineno, colno, error) {
                // No mostrar errores en producción para evitar alertas
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    $('.search-debug').html('❌ Error JS: ' + message + ' - ' + new Date().toLocaleTimeString());
                }
                return false; // No bloquear la ejecución
            };

            // Configuración para evitar bloqueo de recursos externos
            document.addEventListener('DOMContentLoaded', function() {
                // Verificar que jQuery se cargó correctamente
                if (typeof jQuery === 'undefined') {
                    // jQuery no se cargó correctamente - skip further checks
                    return;
                }
                // jQuery cargado correctamente

                // Verificar que EasyAutoComplete se cargó correctamente
                if (typeof jQuery.fn.easyAutocomplete === 'undefined') {
                    // EasyAutoComplete no se cargó correctamente
                } else {
                    // EasyAutoComplete cargado correctamente
                }

                // Verificar recursos bloqueados
                setTimeout(function() {
                    const failedResources = performance.getEntriesByType('resource').filter(r => r.transferSize === 0 && r.name.includes('easy-autocomplete'));
                    if (failedResources.length > 0) {
                        // Recursos de EasyAutoComplete posiblemente bloqueados
                    }
                }, 2000);
            });

            // Variable Global para ID de Usuario
            window.currentUserId = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : ""; ?>';
            window.currentUserIdVar = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : ""; ?>';
        </script>

        <script>
        // Manejo de errores de carga de recursos
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'SCRIPT' || e.target.tagName === 'LINK') {
                // Error cargando recurso
                
                // Si jQuery falla, usar versión de respaldo
                if (e.target.src && e.target.src.includes('jquery')) {
                    // Intentando cargar jQuery desde CDN alternativo
                    var script = document.createElement('script');
                    script.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js';
                    script.onerror = function() {
                        // Fallo al cargar jQuery desde todos los CDNs
                    };
                    document.head.appendChild(script);
                }
            }
        }, true);
        </script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/easy-autocomplete/1.3.5/jquery.easy-autocomplete.min.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/easy-autocomplete/1.3.5/easy-autocomplete.min.css" crossorigin="anonymous">
        
        <!-- Estilos personalizados para EasyAutocomplete -->
        <style>
            /* Mejorar apariencia del dropdown de autocompletado */
            .easy-autocomplete {
                width: 100% !important;
            }
            
            .easy-autocomplete-container {
                /* background: white;  REMOVED TO FIX CONFLICT */
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                /* margin-top: 5px; REMOVED TO FIX SPACING */
            }
            
            .easy-autocomplete-container ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .easy-autocomplete-container ul li {
                border-bottom: 1px solid #f0f0f0;
                transition: background 0.2s ease;
            }
            
            .easy-autocomplete-container ul li:last-child {
                border-bottom: none;
            }
            
            .easy-autocomplete-container ul li:hover {
                background: rgba(0,0,0,0.05);
            }
            
            .easy-autocomplete-container ul li.selected {
                background: #E30613 !important;
            }
            
            .easy-autocomplete-container ul li.selected a {
                color: white !important;
            }
            
            .easy-autocomplete-container ul li.selected div {
                color: white !important;
            }
            
            /* Mejorar el input de búsqueda */
            .easy-autocomplete input {
                border-radius: 25px;
                padding: 12px 0px 12px 40px;
                border: 2px solid #ddd;
                transition: all 0.3s ease;
            }
            
            .easy-autocomplete input:focus {
                border-color: #E30613;
                outline: none;
                box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
            }
        </style>

        <!-- Script de verificación de recursos externos -->
        <script>
            // Verificar recursos externos después de carga completa
            window.addEventListener('load', function() {
                setTimeout(function() {
                    // Verificar jQuery
                    if (typeof jQuery !== 'undefined') {
                        // jQuery disponible
                    } else {
                        // jQuery no disponible
                    }

                    // Verificar EasyAutoComplete
                    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.easyAutocomplete !== 'undefined') {
                        // EasyAutoComplete disponible
                    } else {
                        // EasyAutoComplete no disponible
                    }

                    // Verificar recursos bloqueados
                    if (typeof performance !== 'undefined' && typeof performance.getEntriesByType !== 'undefined') {
                        const resources = performance.getEntriesByType('resource');
                        const blockedResources = resources.filter(r => 
                            r.transferSize === 0 &&
                            (r.name.includes('easy-autocomplete') || r.name.includes('jquery'))
                        );

                        if (blockedResources.length > 0) {
                            // Recursos posiblemente bloqueados
                        }
                    }
                }, 3000); // Esperar 3 segundos para que todos los recursos se carguen
            });
        </script>
        
        <?php 
        // Incluir funciones de AdSense
        include_once __DIR__ . '/../funciones_adsense.php';
        
        // Mostrar código de AdSense en el header si corresponde
        // should_show_adsense() ya verifica $anula_adsense internamente, así que solo llamamos a la función
        if (should_show_adsense()) {
            echo get_adsense_header_code();
        }

        ?>
        
        <?php if(isset($links_meta["schema"])){ ?>
            <?php echo $links_meta["schema"]; ?>
        <?php } ?>
        
        <?php
        $chat_script_path = __DIR__ . '/../js/chat-modal.js';
        $chat_script_version = file_exists($chat_script_path) ? filemtime($chat_script_path) : time();
        $current_user_id_value = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : '';
        $chat_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        ?>
        <script>
            window.currentUserId = <?php echo json_encode($current_user_id_value); ?>;
            window.currentUserIdVar = window.currentUserId;
            window.codigoAmigoChatLoggedIn = <?php echo $chat_logged_in ? 'true' : 'false'; ?>;
        </script>
        <script src="/js/chat-modal.js?v=<?php echo $chat_script_version; ?>" defer></script>
        
        <!-- VIP Styles and Code Viewer Modal -->
        <link rel="stylesheet" href="/css/vip-styles.css?v=<?php echo file_exists(__DIR__ . '/../../css/vip-styles.css') ? filemtime(__DIR__ . '/../../css/vip-styles.css') : time(); ?>">
        <?php
        $code_viewer_script_path = __DIR__ . '/../../js/code-viewer-modal.js';
        $code_viewer_script_version = file_exists($code_viewer_script_path) ? filemtime($code_viewer_script_path) : time();
        ?>
        <script src="/js/code-viewer-modal.js?v=<?php echo $code_viewer_script_version; ?>" defer></script>
        
        <!-- VIP Badge Click Modal -->
        <div id="vipInfoModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:999999; background:rgba(0,0,0,0.7); justify-content:center; align-items:center;">
            <div style="background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border:2px solid rgba(255,215,0,0.4); border-radius:20px; padding:30px; max-width:380px; width:90%; text-align:center; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
                <button onclick="document.getElementById('vipInfoModal').style.display='none'" style="position:absolute; top:12px; right:15px; background:none; border:none; color:#aaa; font-size:1.3rem; cursor:pointer;">&times;</button>
                <div style="font-size:2.5rem; margin-bottom:10px;">👑</div>
                <h3 style="color:#ffd700; margin:0 0 5px 0; font-size:1.3rem;">Usuario VIP verificado</h3>
                <p style="color:rgba(255,255,255,0.7); font-size:0.85rem; margin:0 0 20px 0;">Ventajas exclusivas del plan VIP</p>
                <div style="text-align:left; margin-bottom:20px;">
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; color:rgba(255,255,255,0.9); font-size:0.9rem;"><i class="fas fa-check" style="color:#ffd700; width:16px;"></i> Badge dorado de confianza</div>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; color:rgba(255,255,255,0.9); font-size:0.9rem;"><i class="fas fa-comments" style="color:#ffd700; width:16px;"></i> Chat ilimitado con viewers</div>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; color:rgba(255,255,255,0.9); font-size:0.9rem;"><i class="fas fa-paper-plane" style="color:#ffd700; width:16px;"></i> Mensajes masivos</div>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; color:rgba(255,255,255,0.9); font-size:0.9rem;"><i class="fas fa-wallet" style="color:#ffd700; width:16px;"></i> +10€ saldo gratis/mes</div>
                </div>
                <a href="/public/suscripcion_vip.php" style="display:block; background:linear-gradient(135deg, #ffd700 0%, #E30613 100%); color:white; padding:12px 20px; border-radius:25px; text-decoration:none; font-weight:700; font-size:1rem; transition:transform 0.2s;">Quiero ser VIP →</a>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Click handler for VIP badges - opens modal
            document.addEventListener('click', function(e) {
                var badge = e.target.closest('.vip-badge-gold');
                if (badge) {
                    e.preventDefault();
                    e.stopPropagation();
                    var modal = document.getElementById('vipInfoModal');
                    if (modal) modal.style.display = 'flex';
                }
            });
            // Close modal on backdrop click
            var vipModal = document.getElementById('vipInfoModal');
            if (vipModal) {
                vipModal.addEventListener('click', function(e) {
                    if (e.target === this) this.style.display = 'none';
                });
            }
        });
        </script>
        
        <!-- Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-DVE5FZ2SZY"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-DVE5FZ2SZY');
        </script>
        <script src="/js/notificaciones.js?v=<?php echo file_exists(__DIR__ . '/../js/notificaciones.js') ? filemtime(__DIR__ . '/../js/notificaciones.js') : time(); ?>" defer></script>
        <style>
            /* DEFINITIVE FIX FOR SEARCH RESULTS LAYOUT */
            .easy-autocomplete-container ul li > div {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: flex-start !important;
                width: 100% !important;
                word-break: normal !important;
                white-space: nowrap !important;
                text-align: left !important;
                gap: 15px !important;
                padding: 12px 15px !important;
                box-sizing: border-box !important;
            }
            .brand-suggestion-item {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 15px !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            .brand-suggestion-info {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: center !important;
                flex: 1 !important;
                min-width: 0 !important;
                gap: 2px !important;
                overflow: hidden !important;
            }
            .brand-suggestion-name {
                font-weight: 700 !important;
                font-size: 16px !important;
                color: #222 !important;
                display: block !important;
                width: 100% !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            .brand-suggestion-count {
                font-size: 13px !important;
                color: #888 !important;
                display: block !important;
            }
            .brand-suggestion-image-wrapper {
                width: 45px !important;
                height: 45px !important;
                min-width: 45px !important;
                background: white !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 5px !important;
                flex-shrink: 0 !important;
                overflow: hidden !important;
            }
            .brand-suggestion-image-wrapper img {
                max-width: 100% !important;
                max-height: 100% !important;
                width: auto !important;
                height: auto !important;
                object-fit: contain !important;
            }
        </style>
    </head>
    <body>
    <?php
    if(isset($data_usuario['_id'])) {
        if(!function_exists('getCollectionCodeViewers')) {
            if(file_exists(__DIR__ . '/../funciones_usuario.php')) {
                include_once __DIR__ . '/../funciones_usuario.php'; 
            }
        }
        
        if(function_exists('getCollectionCodeViewers')) {
             try {
                $coll = getCollectionCodeViewers();
                $uid = is_string($data_usuario['_id']) ? new MongoDB\BSON\ObjectId($data_usuario['_id']) : $data_usuario['_id'];
                
                // Count uncontacted viewers
                $count = $coll->countDocuments([
                    'codigo_owner_id' => $uid,
                    'viewer_user_id' => ['$exists' => true],
                    'contacted' => false
                ]);
                
                if ($count > 0) {
                     // Check VIP
                     if(!function_exists('es_usuario_vip')) { 
                         if(file_exists(__DIR__ . '/../funciones_usuario.php')) {
                             include_once __DIR__ . '/../funciones_usuario.php'; 
                         }
                     }
                     $is_vip = function_exists('es_usuario_vip') ? es_usuario_vip((string)$data_usuario['_id']) : false;
                     
                     $bg_color = $is_vip ? 'linear-gradient(90deg, #ffd700 0%, #E30613 100%)' : 'linear-gradient(90deg, #1a1a2e 0%, #30475e 100%)';
                     $text_color = '#fff';
                     $icon = $is_vip ? 'fa-crown' : 'fa-users';
                     $msg = $is_vip 
                        ? "<strong>¡Tienes $count leads esperando!</strong> Contacta con ellos ahora." 
                        : "<strong>¡Tienes $count potenciales clientes esperando!</strong> Hazte VIP para contactarlos.";
                     $btn_text = $is_vip ? "Ver Leads" : "Ver oportunidad";
                     $link = "/public/mis_viewers.php";
                     
                     echo '<div style="background: '.$bg_color.'; color: '.$text_color.'; padding: 10px 20px; text-align: center; position: relative; z-index: 10000; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-family: \'Inter\', sans-serif;">';
                     echo '<div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap;">';
                     echo '<span><i class="fas '.$icon.'" style="margin-right: 5px;"></i> ' . $msg . '</span>';
                     echo '<a href="'.$link.'" style="background: rgba(255,255,255,0.2); color: inherit; padding: 5px 15px; border-radius: 20px; text-decoration: none; border: 1px solid rgba(255,255,255,0.3); font-weight: bold; font-size: 0.9em;">'.$btn_text.' <i class="fas fa-arrow-right"></i></a>';
                     echo '</div>';
                     echo '</div>';
                }
             } catch(Throwable $e) {
                 // ignore
             }
        }
    }
    ?>
        <!-- HEADER MODERNO -->
        <header class="header-modern desktop-only" id="main-header">
            <div class="header-container">
                <a href="/" class="logo-section">
                    <div class="logo-text">
                        <span class="logo-codigo">codigo</span>
                        <span class="logo-amigo">amigo</span>
                    </div>
                </a>
                
            <nav class="nav-modern">
                <!-- Menú desplegable de Códigos Amigo (Agrupado) -->
                <div class="dropdown-modern">
                    <button class="nav-link dropdown-toggle" type="button">
                        Códigos Amigo <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu-modern" style="max-height: 80vh; overflow-y: auto;">
                        <a href="/" class="dropdown-item-modern">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                        <div class="dropdown-divider"></div>
                        <span class="dropdown-header-modern"><i class="fas fa-tags"></i> Marcas</span>
                        <a href="<?php echo link_listado_marcas(); ?>" class="dropdown-item-modern">
                            Ver todas las marcas
                        </a>
                        <?php
                        if (!function_exists('getListMarcaSpecial')) {
                            include_once __DIR__ . '/../funciones.php';
                        }
                        $lista_marcas = function_exists('getListMarcaSpecial') ? getListMarcaSpecial() : [];
                        if($lista_marcas){
                            foreach($lista_marcas as $marca) {
                                $codes = $marca["codes"] ?? 0;
                                $nombre = $marca["nombre"] ?? '';
                                $nombre_clave = $marca["nombre_clave"] ?? '';
                                $imagen = $marca["imagen"] ?? '';

                                if($nombre && $codes > 0){
                                ?>
                                <a href="<?php echo link_marca($nombre_clave); ?>" class="dropdown-item-modern" title="Cupones descuento para <?php echo $nombre; ?>">
                                    <img alt="cupones <?php echo $nombre; ?>" loading="lazy" src="<?php echo $imagen;?>" />
                                    <span><?php echo $nombre ?> (<?php echo $codes; ?>)</span>
                                </a>
                                <?php
                                }
                            }
                        }
                        ?>
                        
                        <div class="dropdown-divider"></div>
                        
                        <span class="dropdown-header-modern"><i class="fas fa-list"></i> Categorías</span>
                        <a href="<?php echo link_listado_categorias(); ?>" class="dropdown-item-modern">
                            Ver todas las categorías
                        </a>
                        <?php
                        if (!function_exists('getCategorias')) {
                            include_once __DIR__ . '/../../inc/conexion.php';
                        }
                        $listacategorias = function_exists('getCategorias') ? getCategorias() : [];
                        foreach($listacategorias as $categoria) { 
                            if(isset($categoria["nombre"]) && isset($categoria["nombre_clave"])) {
                        ?>
                        <a href="<?php echo link_categoria($categoria["nombre_clave"]); ?>" class="dropdown-item-modern" title="<?php echo $categoria["descripcion"] ?? ''; ?>">
                            <i class="<?php echo $categoria["icon"] ?? 'fas fa-folder'; ?>" aria-hidden="true"></i>
                            <span><?php echo $categoria["nombre"] ?></span>
                        </a>
                        <?php 
                            }
                        } 
                        ?>
                    </div>
                </div>
                
                <!-- Menú desplegable de Guías -->
                <div class="dropdown-modern">
                    <button class="nav-link dropdown-toggle" type="button">
                        Guías <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu-modern">
                        <?php
                        // Asegurar que las funciones estén cargadas
                        if (!function_exists('get_active_super_landings')) {
                            include_once __DIR__ . '/../_super_landing_functions.php';
                        }
                        
                        if (function_exists('get_active_super_landings')) {
                            $guias = get_active_super_landings();
                            foreach($guias as $guia) {
                        ?>
                        <a href="/guias/<?php echo $guia['slug']; ?>" class="dropdown-item-modern">
                            <i class="fas fa-book"></i> <?php echo $guia['title']; ?>
                        </a>
                        <?php
                            }
                        }
                        ?>
                        <div class="dropdown-divider"></div>
                        <a href="/marcas" class="dropdown-item-modern">
                            <i class="fas fa-search"></i> Ver todas las marcas
                        </a>
                    </div>
                </div>
                


                <!-- Menú desplegable de Chollos -->
                <div class="dropdown-modern">
                    <button class="nav-link dropdown-toggle" type="button">
                        Chollos <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu-modern">
                        <a href="/amazon" class="dropdown-item-modern">
                            <i class="fas fa-crown text-warning"></i> Amazon Gratis
                        </a>
                        <a href="https://www.malprecio.com/chollos" target="_blank" class="dropdown-item-modern">
                            <i class="fas fa-tag"></i> Todos los chollos
                        </a>
                        <a href="https://www.malprecio.com/chollos-shorts" target="_blank" class="dropdown-item-modern" style="color: #E30613; font-weight: 700;">
                            <i class="fab fa-youtube"></i> Chollos Shorts <span style="background: #E30613; color: white; padding: 1px 5px; border-radius: 4px; font-size: 9px; margin-left: 5px;">NUEVO</span>
                        </a>
                        <a href="https://t.me/cholloscodigoamigo" target="_blank" class="dropdown-item-modern telegram-link">
                            <i class="fa-brands fa-telegram"></i> Canal de Telegram
                        </a>
                    </div>
                </div>
            </nav>
                
                <div class="search-header">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <input type="text" id="busqueda_marca" class="search-input-header" placeholder="Buscar...">
                            <button class="search-btn" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    
                    
                    <!-- Botón de login (oculto cuando está logueado) -->
                    <button class="btn-acceder open_modal_login" id="btn-login" style="<?php echo (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) ? 'display: none;' : 'display: block;'; ?>">
                        <i class="fas fa-user"></i>
                        Acceder
                    </button>

                    <!-- Menú de usuario (visible cuando está logueado) -->
                    <div class="user-menu" id="user-menu" style="<?php echo (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) ? 'display: flex;' : 'display: none;'; ?>">
                        <!-- Notificaciones -->
                        <div class="notification-container" id="notification-container">
                            <button class="notification-btn" id="notification-btn" title="Notificaciones">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                            </button>
                            <div class="notification-dropdown" id="notification-dropdown">
                                <div class="notification-header">
                                    <span>Notificaciones</span>
                                    <button class="mark-all-read" id="mark-all-read" title="Marcar todas como leídas"><i class="fas fa-check-double"></i></button>
                                </div>
                                <div class="notification-list" id="notification-list">
                                    <div class="notification-empty">Cargando...</div>
                                </div>
                            </div>
                        </div>

                        <div class="user-profile" id="user-profile">
                            <img src="" alt="Avatar" class="user-avatar-small" id="user-avatar">
                            <span class="user-name-small" id="user-name">Usuario</span>
                            <?php if(isset($_SESSION["user_id"]) && es_usuario_vip($_SESSION["user_id"])): ?>
                                <i class="fas fa-crown" style="color: #ffd700; margin-left: 5px;" title="VIP"></i>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        
                        <?php 
                        // Calcular contadores para el menú desktop
                        $count_codigos = 0;
                        $count_afiliados = 0;
                        $count_favoritos = 0;
                        $count_chollos = 0;
                        $count_mensajes = 0;
                        $header_total_potential = 0;
                        
                        if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
                            $uid_session = $_SESSION["user_id"];
                            
                            // Include functions
                            if(!function_exists('getCollectionCodigos')) include_once __DIR__ . '/../funciones_codigo.php';
                            if(!function_exists('contar_favoritos_usuario')) include_once __DIR__ . '/../funciones_favoritos.php';
                            if(!function_exists('getCollectionAfiliados')) include_once __DIR__ . '/../funciones_afiliados.php';
                            if(!function_exists('getCollectionMensajes')) include_once __DIR__ . '/../funciones_usuario.php';
                            
                            try {
                                // Count Codes
                                $coll_codes = getCollectionCodigos();
                                $idFilter = ['$in' => [$uid_session]];
                                try { $idFilter['$in'][] = new \MongoDB\BSON\ObjectId($uid_session); } catch (Throwable $e) {}
                                $count_codigos = $coll_codes->countDocuments(['id_usuario' => $idFilter, 'estado' => ['$in' => [0, 1]]]); 
                                
                                // Count Affiliates
                                if (function_exists('getCollectionAfiliados')) {
                                    $coll_aff = getCollectionAfiliados();
                                    if($coll_aff) $count_afiliados = $coll_aff->countDocuments(['usuario_id' => $uid_session, 'activo' => true]);
                                }
                                
                                // Count Favorites
                                if(function_exists('contar_favoritos_usuario')) {
                                    $count_favoritos = contar_favoritos_usuario($uid_session, 'codigo');
                                    $count_chollos = contar_favoritos_usuario($uid_session, 'chollo');
                                }
                                
                                // Count Conversations (Total)
                                if (function_exists('obtenerConversacionesUsuario')) {
                                    $conversaciones = obtenerConversacionesUsuario($uid_session);
                                    $count_mensajes = count($conversaciones);
                                } elseif (function_exists('getCollectionMensajes')) {
                                    $coll_mensajes = getCollectionMensajes();
                                    if ($coll_mensajes) {
                                        $idUserObj = new \MongoDB\BSON\ObjectId($uid_session);
                                        $count_mensajes = $coll_mensajes->countDocuments([
                                            'para_usuario_id' => $idUserObj,
                                            'leido' => false
                                        ]);
                                    }
                                }
                                
                                // Potential Earnings Calculation
                                if (!function_exists('obtener_potencial_completo_usuario')) {
                                    include_once __DIR__ . '/../funciones_usuario.php';
                                }
                                $potencial_global = obtener_potencial_completo_usuario($uid_session);
                                $header_total_potential = $potencial_global['total_potential'] ?? 0;
                                
                                // Count Leads (viewers)
                                $count_leads = 0;
                                if(function_exists('getCollectionCodeViewers')) {
                                    $coll_viewers = getCollectionCodeViewers();
                                    $uidObj = is_string($uid_session) ? new MongoDB\BSON\ObjectId($uid_session) : $uid_session;
                                    $count_leads = $coll_viewers->countDocuments([
                                        'codigo_owner_id' => $uidObj,
                                        'viewer_user_id' => ['$exists' => true]
                                    ]);
                                }
                            } catch(Exception $e) {}
                        }
                        ?>

                        <div class="user-dropdown" id="user-dropdown">
                            <div class="dropdown-header-modern" style="padding: 5px 20px; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 700;">Mi Contenido</div>
                            
                            <a href="/mis-anuncios" class="dropdown-item">
                                <i class="fas fa-list"></i>
                                Mis Códigos Amigo
                                <?php if($count_codigos > 0) { echo '<span class="badge pull-right" style="margin-left:auto; background:#eee; color:#333; padding:2px 8px; border-radius:10px; font-size:12px;">'.$count_codigos.'</span>'; } ?>
                            </a>
                            <a href="/afiliados" class="dropdown-item">
                                <i class="fas fa-link"></i>
                                Mis URLs de Afiliados
                                <?php if($count_afiliados > 0) { echo '<span class="badge pull-right" style="margin-left:auto; background:#eee; color:#333; padding:2px 8px; border-radius:10px; font-size:12px;">'.$count_afiliados.'</span>'; } ?>
                            </a>
                            <a href="/mis-favoritos" class="dropdown-item">
                                <i class="fas fa-heart"></i>
                                Códigos Favoritos
                                <?php if($count_favoritos > 0) { echo '<span class="badge pull-right" style="margin-left:auto; background:#eee; color:#333; padding:2px 8px; border-radius:10px; font-size:12px;">'.$count_favoritos.'</span>'; } ?>
                            </a>

                            <hr class="dropdown-divider">
                            <div class="dropdown-header-modern" style="padding: 5px 20px; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 700;">Social</div>

                            <a href="/chat" class="dropdown-item">
                                <i class="fas fa-comments"></i>
                                Chat
                                <?php if($count_mensajes > 0) { echo '<span class="badge pull-right" style="margin-left:auto; background:#eee; color:#333; padding:2px 8px; border-radius:10px; font-size:12px;">'.$count_mensajes.'</span>'; } ?>
                            </a>
                            <?php if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) { ?>
                            <a href="/invitar-amigos" class="dropdown-item" style="color: #E30613; font-weight: 600;">
                                <i class="fas fa-gift"></i>
                                Invita amigos
                            </a>
                            <?php } ?>
                            
                            <?php 
                            $dropdown_is_vip = isset($_SESSION["user_id"]) && function_exists('es_usuario_vip') && es_usuario_vip($_SESSION["user_id"]);
                            $has_potential = isset($header_total_potential) && $header_total_potential > 0;
                            $leads_link = $dropdown_is_vip ? '/public/mis_viewers.php' : '/public/suscripcion_vip.php';
                            ?>
                            <!-- Bloque visual Leads + VIP -->
                            <div style="margin: 8px 10px; border-radius: 12px; overflow: hidden; border: 1px solid rgba(102,126,234,0.3);">
                                <a href="<?php echo $leads_link; ?>" style="display:block; text-decoration:none; padding: 14px 15px; background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,226,0.15) 100%);">
                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                                        <i class="fas fa-crosshairs" style="color:#667eea; font-size:16px;"></i>
                                        <span style="color:#fff; font-weight:700; font-size:14px;">Mis Leads</span>
                                        <?php if($count_leads > 0): ?>
                                            <span style="margin-left:auto; background:#667eea; color:white; padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700;"><?php echo $count_leads; ?> personas</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($has_potential): ?>
                                    <div style="display:flex; align-items:baseline; gap:6px;">
                                        <span style="color:#4ade80; font-size:22px; font-weight:900; letter-spacing:-0.5px;"><?php echo number_format($header_total_potential, 0, ',', '.'); ?>€</span>
                                        <span style="color:rgba(255,255,255,0.6); font-size:12px;">potencial de ganancias</span>
                                    </div>
                                    <?php else: ?>
                                    <div style="color:rgba(255,255,255,0.5); font-size:12px;">Publica códigos para generar leads</div>
                                    <?php endif; ?>
                                </a>
                                <?php if(!$dropdown_is_vip): ?>
                                <a href="/public/suscripcion_vip.php" style="display:flex; align-items:center; gap:10px; padding:10px 15px; background: linear-gradient(135deg, rgba(255,215,0,0.2) 0%, rgba(227,6,19,0.15) 100%); text-decoration:none; border-top: 1px solid rgba(255,215,0,0.2);">
                                    <i class="fas fa-crown" style="color:#ffd700; font-size:14px;"></i>
                                    <span style="color:#ffd700; font-weight:700; font-size:13px;">Hazte VIP para contactarlos</span>
                                    <span style="margin-left:auto; background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color:white; padding:3px 8px; border-radius:10px; font-size:10px; font-weight:800;">9,99€/mes</span>
                                </a>
                                <?php endif; ?>
                            </div>

                            <hr class="dropdown-divider">
                            <div class="dropdown-header-modern" style="padding: 5px 20px; font-size: 11px; text-transform: uppercase; color: #888; font-weight: 700;">Mi Cuenta</div>

                            <?php if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) { ?>
                            <a href="<?php echo link_usuario($_SESSION["username"], $_SESSION["user_id"]); ?>" class="dropdown-item">
                                <i class="fas fa-user-circle"></i>
                                Mi perfil público
                            </a>
                            <?php } ?>
                            
                            <?php 
                            // Verificar si el usuario es admin
                            $array_admins = [
                                "58bd851da54e295b8b52f702", //thevega82@gmail.com
                                "5e78170e6b68e6519b7c5df2", //edna
                                "639899bc6321ee0d0e4010d2", //aron
                                "5c8a10ce2f55c86d6e707d82"  //jose
                            ];
                            $es_admin = (isset($_SESSION["user_id"]) && in_array($_SESSION["user_id"], $array_admins)) || 
                                        (isset($_SESSION["mail"]) && $_SESSION["mail"] === "thevega82@gmail.com");
                            
                            if($es_admin) { ?>
                            <a target="_blank" href="/public/admin_dashboard.php" class="dropdown-item" style="color: #E30613; font-weight: 700;">
                                <i class="fas fa-cogs"></i>
                                Panel de Admin
                            </a>
                            <?php } ?>
                            
                            <a href="/usuario" class="dropdown-item">
                                <i class="fas fa-user-edit"></i>
                                Editar perfil
                            </a>
                            <a href="#" class="dropdown-item" id="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar sesión
                            </a>
                        </div>
                    </div>

                    <!-- Botón de Publicar Código -->
                    <button class="btn-publicar" id="btn-publicar">
                        <i class="fas fa-plus-circle"></i>
                        Publicar Código
                    </button>
                    
                    
                </div>
            </div>
        </header>

        <?php
        // Verificar si las funciones móviles ya están incluidas
        if (!function_exists('add_mobile_header_compact')) {
            include_once __DIR__ . '/../funciones_modern.php';
        }
        
        // Agregar header móvil compacto
        add_mobile_header_compact();
        ?>
        
        <?php
        // El menú móvil antiguo ha sido eliminado ya que ahora se usa add_mobile_header_compact()
        // que añade el nuevo menú hamburguesa y el bottom nav.
        ?>

        <?php
        // Widget de chollos eliminado de header global - movido a vistas específicas
        ?>

        <!-- HERO SECTION - Solo en la home -->
        <!-- HERO SECTION eliminado de header global - movido a index.php -->


   
        <!-- Datos de sesión del servidor - respetar datos ya establecidos -->
        <script>
        // Datos de sesión del servidor - HEADER MODERN (datos básicos para respaldo)
        if (typeof window.serverUserDataBasic === "undefined" || window.serverUserDataBasic === null) {
            window.serverUserDataBasic = <?php
                if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                    // Obtener información completa del usuario
                    $usuario_completo = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION['user_id']));

                    $userData = [
                        'id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'] ?? '',
                        'mail' => $_SESSION['mail'] ?? '',
                        'img' => $usuario_completo['img'] ?? $_SESSION['img'] ?? '',
                        'avatar' => $usuario_completo['img'] ?? $_SESSION['img'] ?? '',
                        'zumbido_saldo' => $_SESSION['zumbido_saldo'] ?? 0
                    ];

                    echo json_encode($userData);
                } else {
                    echo 'null';
                }
            ?>;
        }
        </script>
        
        <script>
        if (typeof window.openLoginModalWithRedirect !== 'function') {
            window.openLoginModalWithRedirect = function(targetUrl) {
                var redirectUrl = targetUrl || window.location.href;
                try {
                    localStorage.setItem('redirectAfterLogin', redirectUrl);
                } catch (storageError) {
                    console.warn('[LoginModal] No se pudo guardar redirectAfterLogin', storageError);
                }

                // Verificar que jQuery esté disponible
                if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
                    console.warn('[openLoginModalWithRedirect] jQuery no disponible!');
                }

                // Cerrar otros modales
                if (typeof $.fn.modal === 'function') {
                    $('.modal.in').not('#modal_login').each(function () {
                        try {
                            $(this).modal('hide');
                        } catch (hideError) {
                            console.warn('[LoginModal] Error al cerrar modal previo', hideError);
                        }
                    });
                } else {
                    $('.modal').not('#modal_login').removeClass('in').hide().attr('aria-hidden', 'true');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }

                // Intentar usar showLoginModal si existe
                if (typeof window.showLoginModal === 'function') {
                    // Limpiar backdrops de Bootstrap antes de mostrar el modal custom
                    if (typeof $ !== 'undefined') {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    }
                    window.showLoginModal();
                    return;
                }

                // Si no existe showLoginModal, intentar mostrar el modal directamente
                var $modal = $("#modal_login");
                
                if ($modal.length) {
                    if (typeof $modal.modal === 'function') {
                        try {
                            $modal.modal({
                                backdrop: 'static',
                                keyboard: true,
                                show: true
                            });
                        } catch (error) {
                            console.error('[LoginModal] Error mostrando modal:', error);
                            // Fallback manual
                            $modal.addClass('in show').css('display', 'flex').attr('aria-hidden', 'false');
                            $('body').addClass('modal-open');
                            if (!$('.modal-backdrop').length) {
                                $('<div class="modal-backdrop fade in"></div>').appendTo(document.body);
                            }
                        }
                    } else {
                        $modal.addClass('in show').css('display', 'flex').attr('aria-hidden', 'false');
                        $('body').addClass('modal-open');
                        if (!$('.modal-backdrop').length) {
                            $('<div class="modal-backdrop fade in"></div>').appendTo(document.body);
                        }
                    }
                    return;
                }

                window.location.href = '/login.php';
            };
        }
        // JavaScript para funcionalidad moderna con autocompletado original
        $(document).ready(function() {
            // Manejar clic en botón Publicar Código
            $(document).on('click', '#btn-publicar', function(event) {
                event.preventDefault();
                // Botón Publicar Código clickeado

                // Verificar primero si el usuario está logueado en el frontend
                if (!window.serverUserData || window.serverUserData === null) {
                    openLoginModalWithRedirect('/nuevo_codigo');
                    return;
                }

                // Usuario parece estar logueado en frontend - verificar sesión en servidor antes de redirigir
                console.log('🔍 Verificando sesión en servidor antes de redirigir...');
                $.ajax({
                    type: "POST",
                    url: "/myphp/ajax_actions.php",
                    data: {
                        metodo: "check_session"
                    },
                    cache: false,
                    timeout: 5000,
                    success: function(data) {
                        console.log('📥 Respuesta del servidor:', data);
                        try {
                            var response;
                            if (typeof data === 'string') {
                                response = JSON.parse(data.trim());
                            } else {
                                response = data;
                            }

                            console.log('✅ Respuesta parseada:', response);

                            if (response.success === true && response.logged_in === true) {
                                // Sesión válida - redirigir al formulario
                                console.log('✅ Sesión válida - redirigiendo a /nuevo_codigo');
                                window.location.href = '/nuevo_codigo';
                            } else {
                                // Sesión expirada o inválida - limpiar datos del frontend y mostrar modal de login
                                console.log('❌ Sesión expirada o inválida:', response.message || 'Sin mensaje');
                                window.clearUserSession();
                                console.log('🔓 Mostrando modal de login');
                                openLoginModalWithRedirect('/nuevo_codigo');
                            }
                        } catch (e) {
                            console.error('❌ Error procesando respuesta de verificación de sesión:', e, data);
                            // Si hay error parseando, asumir que la sesión no es válida y mostrar login
                            window.clearUserSession();
                            console.log('🔓 Mostrando modal de login tras error de parseo');
                            openLoginModalWithRedirect('/nuevo_codigo');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Error verificando sesión:', error, xhr.responseText);
                        // Si hay error en la petición, asumir que la sesión no es válida y mostrar login
                        window.clearUserSession();
                        console.log('🔓 Mostrando modal de login tras error de petición');
                        openLoginModalWithRedirect('/nuevo_codigo');
                    }
                });
            });

            // Función mejorada para inicializar el buscador
            function initializeSearch() {
                // Configurar autocompletado para ambos campos de búsqueda
                var url_d = "/datos.json?t=" + Date.now();

                var options_busqueda = {
                    url: url_d,
                    getValue: "nombre",
                    listLocation: function(data) {
                        return data.filter(function(item) {
                            // Filtrado estricto: eliminar "false", strings "false", y objetos sin nombre válido
                            if (!item || item === "false" || item === false) return false; console.log("EAC item is:", item);
                            if (typeof item === 'object' && (!item.nombre || item.nombre === "false" || item.nombre === "false" || item.nombre === false)) return false;
                            return true;
                        });
                    },
                    template: {
                        type: "custom",
                        method: function(value, item) {
                            // Si llegara algo inválido, no mostrar nada
                            if (!item || !item.nombre_clave || value === "false" || value === false) {
                                return "";
                            }

                            var ruta = "/de-" + item.nombre_clave;
                            // Usar ruta directa del item si no hay nombre_clave pero hay url
                            if (!item.nombre_clave && item.url) {
                                ruta = item.url;
                            }
                            
                            var codes = "";
                            if (item.codes == 1) {
                                codes = "1 código";
                            } else if (item.codes > 1) {
                                codes = item.codes + " códigos";
                            }

                            // Procesar URL de imagen
                            var imagenUrl = item.imagen || '/img/no_image.png';
                            try {
                                if (imagenUrl && typeof imagenUrl === 'string' && imagenUrl.indexOf('cdn.codigoamigo.com') !== -1) {
                                    var urlObj = new URL(imagenUrl);
                                    var path = urlObj.pathname;
                                    imagenUrl = (path.indexOf('/panel_marcas/') !== -1) ? 
                                                'https://www.codigoamigo.com/img' + path : 
                                                'https://www.codigoamigo.com' + path;
                                }
                                if (imagenUrl && typeof imagenUrl === 'string' && imagenUrl.indexOf('http://') !== -1) {
                                    imagenUrl = imagenUrl.replace('http://', 'https://');
                                }
                            } catch (e) {
                                imagenUrl = '/img/no_image.png';
                            }

                            // Usar etiqueta <a> para mejorar compatibilidad móvil y SEO
                            return "<a href='" + ruta + "' class='brand-suggestion-item' style='text-decoration: none; color: inherit; display: flex; width: 100%;'>" +
                                   "<div class='brand-suggestion-image-wrapper'>" +
                                   "<img src='" + imagenUrl + "' onerror=\"this.src='/img/no_image.png'\" alt='" + value + "' />" +
                                   "</div>" +
                                   "<div class='brand-suggestion-info'>" +
                                   "<div class='brand-suggestion-name'>" + value + "</div>" +
                                   (codes ? "<div class='brand-suggestion-count'>" + codes + "</div>" : "") +
                                   "</div>" +
                                   "</a>";
                        }
                    },
                    list: {
                        maxNumberOfElements: 10,
                        match: {
                            enabled: true,
                            method: function(element, phrase) {
                                if (!element || !phrase) return false;
                                
                                var p = phrase.toLowerCase().replace(/\s+/g, '');
                                if (p === "ing" || p === "ingdirect") {
                                    if (element.toLowerCase() === "banco ing") return true;
                                }
                                
                                var e = element.toLowerCase().replace(/\s+/g, '');
                                return e.indexOf(p) > -1;
                            }
                        },
                        sort: {
                            enabled: true,
                            method: function(a, b) {
                                var p = "";
                                if ($("#busqueda_marca").is(":focus")) p = $("#busqueda_marca").val();
                                else if ($("#busqueda_marca2").is(":focus")) p = $("#busqueda_marca2").val();
                                else if ($("#mobile-search-input").is(":focus")) p = $("#mobile-search-input").val();
                                else if ($("#mobile-search-input-header").is(":focus")) p = $("#mobile-search-input-header").val();
                                else p = $(".easy-autocomplete input:focus").val() || "";
                                
                                p = p.toLowerCase().replace(/\s+/g, '');
                                
                                if (p === "ing" || p === "ingdirect") {
                                    if (a.nombre.toLowerCase() === "banco ing") return -1;
                                    if (b.nombre.toLowerCase() === "banco ing") return 1;
                                }
                                
                                var ea = a.nombre.toLowerCase().replace(/\s+/g, '');
                                var eb = b.nombre.toLowerCase().replace(/\s+/g, '');
                                
                                var startsWithA = ea.indexOf(p) === 0;
                                var startsWithB = eb.indexOf(p) === 0;
                                
                                if (startsWithA && !startsWithB) return -1;
                                if (!startsWithA && startsWithB) return 1;
                                
                                return 0;
                            }
                        },
                        onDrawEvent: function() {
                            var $input = $(this);
                            var $container = $input.siblings(".easy-autocomplete-container");
                            var $list = $container.find("ul");
                            if ($list.children().length === 0) {
                                $list.append("<li class='eac-item'><div style='padding:12px 15px; color:#888; text-align:center;'>Sin resultados</div></li>");
                                // Eliminar elemento que contenga solo el texto "false"
                                $list.children().each(function() {
                                    var txt = $(this).text().trim(); if (txt === "false" || txt.toLowerCase() === "false" ) {
                                        $(this).remove();
                                    }
                                });

                                $list.show();
                                $container.show();
                            }
                        },
                        onChooseEvent: function() {
                            // Obtener el item seleccionado del input que disparó el evento
                            var $input = $(this);
                            var item = $input.getSelectedItemData();
                            
                            if (item && item.nombre_clave) {
                                window.location.href = "/de-" + item.nombre_clave;
                            } else if (item && item.url) {
                                window.location.href = item.url;
                            }
                        },
                        // Evento click específico para móvil
                        onClickEvent: function() {
                            var $input = $(this);
                            var item = $input.getSelectedItemData();
                            if (item && item.nombre_clave) {
                                window.location.href = "/de-" + item.nombre_clave;
                            } else if (item && item.url) {
                                window.location.href = item.url;
                            }
                        }
                    }
                };

                // Función para aplicar autocompletado con manejo de errores
                var autocompleteRetries = 0;
                var maxRetries = 10;
                
                function applyAutocomplete() {
                    // Verificar que EasyAutocomplete esté disponible
                    if (typeof $.fn.easyAutocomplete === 'undefined') {
                        autocompleteRetries++;
                        if (autocompleteRetries < maxRetries) {
                            setTimeout(applyAutocomplete, 500);
                        } else {
                            // EasyAutocomplete no se pudo cargar después de varios intentos
                        }
                        return;
                    }

                    // Aplicar autocompletado con verificación de elementos
                    // Solo incluir campos que existen en la página actual
                    var searchFields = ["#busqueda_marca", "#mobile-search-input", "#mobile-search-input-header"];
                    if (document.getElementById('busqueda_marca2')) {
                        searchFields.push("#busqueda_marca2");
                    }
                    var appliedCount = 0;

                    searchFields.forEach(function(selector) {
                        var element = $(selector);
                        if (element.length > 0) {
                            try {
                                element.easyAutocomplete(options_busqueda);
                                appliedCount++;
                            } catch (error) {
                                // Error aplicando EasyAutocomplete
                            }
                        } else {
                            // Elemento no encontrado
                        }
                    });

                }

                // Aplicar autocompletado después de que la página esté completamente cargada
                // Solo si Select2 no está presente
                $(window).on('load', function() {
                    // Página completamente cargada, verificando librerías
                    
                    if (typeof $.fn.select2 === 'undefined') {
                        // Select2 no detectado, iniciando EasyAutocomplete
                        // Esperar un poco más para asegurar que todo esté listo
                        setTimeout(applyAutocomplete, 500);
                    } else {
                        // Select2 detectado, omitiendo inicialización de EasyAutocomplete
                    }
                });

                // Configurar manejadores de eventos para botones de búsqueda
                function setupSearchButtons() {
                    // Configurando botones de búsqueda

                    // Botón de búsqueda del hero (con verificación mejorada)
                    $(document).off('click.search-hero').on('click.search-hero', '.btn-buscar', function(e) {
                        e.preventDefault();
                        // Clic en botón de búsqueda

                        // Verificar si el elemento existe antes de usarlo
                        var heroInput = $('#busqueda_marca2');
                        if (heroInput.length === 0) {
                            // Elemento no encontrado
                            return;
                        }

                        var query = heroInput.val();
                        // Query del hero

                        if(query && query.trim() !== '') {
                            var encodedQuery = encodeURIComponent(query.trim());
                            // Redirigiendo a búsqueda
                            window.location.href = '/ofertas/' + encodedQuery;
                        } else {
                            // Query vacío en búsqueda
                            heroInput.focus();
                        }
                    });

                    // Botón de búsqueda del header (con verificación mejorada)
                    $(document).off('click.search-header').on('click.search-header', '.search-btn', function(e) {
                        e.preventDefault();
                        // Clic en botón de búsqueda del header

                        var query = $('#busqueda_marca').val();
                        // Query del header

                        if(query && query.trim() !== '') {
                            var encodedQuery = encodeURIComponent(query.trim());
                            // Redirigiendo a búsqueda
                            window.location.href = '/ofertas/' + encodedQuery;
                        } else {
                            // Query vacío en búsqueda del header
                            $('#busqueda_marca').focus();
                        }
                    });

                    // Campo de búsqueda móvil (con verificación mejorada)
                    $(document).off('click.mobile-search').on('click.mobile-search', '.mobile-search-container .search-icon', function(e) {
                        e.preventDefault();
                        // Clic en icono de búsqueda móvil

                        var query = $('#mobile-search-input').val();
                        // Query del móvil

                        if(query && query.trim() !== '') {
                            var encodedQuery = encodeURIComponent(query.trim());
                            // Redirigiendo a búsqueda
                            window.location.href = '/ofertas/' + encodedQuery;
                        } else {
                            // Query vacío en búsqueda móvil
                            $('#mobile-search-input').focus();
                        }
                    });
                }

                // Configurar botones después de un delay
                setTimeout(setupSearchButtons, 200);

                // Configurar manejadores de teclado para Enter (con verificación mejorada)
                function setupEnterKey() {
                    // Configurando eventos de teclado

                    $(document).off('keypress.search-enter').on('keypress.search-enter', '#busqueda_marca, #busqueda_marca2, #mobile-search-input, #mobile-search-input-header', function(e) {
                        if(e.which == 13) {
                            e.preventDefault();
                            // Enter presionado en campo de búsqueda

                            var query = $(this).val();
                            // Query desde teclado

                            if(query && query.trim() !== '') {
                                var encodedQuery = encodeURIComponent(query.trim());
                                // Redirigiendo desde teclado
                                window.location.href = '/ofertas/' + encodedQuery;
                            } else {
                                // Query vacío desde teclado
                                $(this).focus();
                            }
                        }
                    });
                }

                // Configurar eventos de teclado después de un delay
                setTimeout(setupEnterKey, 300);
            }

            // Inicializar búsqueda cuando el documento esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeSearch);
            } else {
                initializeSearch();
            }

            // Función para probar el buscador manualmente (útil para debugging)
            window.testSearch = function() {
                // Ejecutando pruebas del buscador

                // Verificar elementos de búsqueda
                var searchElements = {
                    '#busqueda_marca': 'Campo de búsqueda del header',
                    '#busqueda_marca2': 'Campo de búsqueda del hero',
                    '#mobile-search-input': 'Campo de búsqueda móvil',
                    '.search-btn': 'Botón de búsqueda del header',
                    '.btn-buscar': 'Botón de búsqueda del hero'
                };

                var results = [];
                Object.keys(searchElements).forEach(function(selector) {
                    var element = $(selector);
                    var status = element.length > 0 ? '✅' : '❌';
                    var name = searchElements[selector];
                    results.push(status + ' ' + name + ' (' + selector + ')');
                    // Elemento encontrado o no encontrado
                });

                // Verificar autocompletado
                var autocompleteStatus = typeof $.fn.easyAutocomplete !== 'undefined' ? '✅' : '❌';
                results.push(autocompleteStatus + ' EasyAutocomplete disponible');

                // Mostrar resultados
                $('.search-debug').html('🧪 Pruebas: ' + results.join(' | '));

                return results;
            };

            // Indicador de debug (solo mostrar en desarrollo local)
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                $('.search-debug').show().html('🔍 Buscador inicializado - ' + new Date().toLocaleTimeString());

                // Añadir enlace para ejecutar pruebas
                $(document).ready(function() {
                    $('<div style="position: fixed; bottom: 50px; right: 10px; z-index: 9999;">' +
                      '<button onclick="testSearch()" style="background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 3px; font-size: 11px;">🧪 Test Buscador</button>' +
                      '</div>').appendTo('body');
                });

                // Actualizar indicador cada 10 segundos
                setInterval(function() {
                    $('.search-debug').html('🔍 Buscador activo - Última verificación: ' + new Date().toLocaleTimeString());
                }, 10000);
            }
            
            // La funcionalidad del menú móvil antiguo ha sido eliminada 
            // ya que ahora se gestiona en bottom_menu_script.php
            
            // HEADER FIJO - Efecto de scroll
            let lastScrollTop = 0;
            const header = $('.header-modern');
            
            $(window).on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                
                // Agregar/quitar clase de scroll para efecto visual
                if (scrollTop > 50) {
                    header.addClass('scrolled');
                } else {
                    header.removeClass('scrolled');
                }
                
                // Opcional: Ocultar/mostrar header al hacer scroll (descomenta si lo quieres)
                /*
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down
                    header.css('transform', 'translateY(-100%)');
                } else {
                    // Scrolling up
                    header.css('transform', 'translateY(0)');
                }
                */
                
                lastScrollTop = scrollTop;
            });
            
            // Asegurar que el header esté visible al cargar la página
            $(window).on('load', function() {
                header.css('transform', 'translateY(0)');
            });
            
            // Ajustar altura del body dinámicamente
            function adjustBodyMargin() {
                const headerHeight = $('.header-modern:visible').outerHeight() || 0;
                $('body').css('margin-top', headerHeight + 'px');
            }
            
            // Ajustar en resize de ventana
            $(window).on('resize', function() {
                adjustBodyMargin();
            });
            
            // Ajustar inicial
            adjustBodyMargin();
        });
        </script>
        
        <style>
        .modern-auth-modal .modal-dialog {
            max-width: 480px;
            margin: 80px auto;
        }

        @media (max-width: 576px) {
            .modern-auth-modal .modal-dialog {
                margin: 40px auto;
            }
        }

        .modern-auth-modal .modal-content {
            border: none;
            border-radius: 20px;
            padding: 35px 32px 32px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
            background: #ffffff;
        }

        .modern-auth-modal .modern-auth-close {
            border: none;
            background: transparent;
            font-size: 26px;
            color: #999;
            position: absolute;
            top: 18px;
            right: 22px;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .modern-auth-modal .modern-auth-close:hover {
            color: #E30613;
        }

        .modern-auth-modal .modern-auth-header {
            text-align: center;
            margin-bottom: 28px;
            padding: 26px 22px 22px;
            background: linear-gradient(135deg, #E30613, #FF4D4D);
            border-radius: 18px;
            box-shadow: 0 18px 35px rgba(227, 6, 19, 0.25);
        }

        .modern-auth-modal .modern-auth-header h3 {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            display: none; /* Oculto por defecto */
        }

        .modern-auth-modal.show .modern-auth-header h3,
        .modern-auth-modal.in .modern-auth-header h3 {
            display: block; /* Solo visible cuando el modal esté activo */
        }

        .modern-auth-modal .modern-auth-header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 15px;
            margin: 0;
        }

        .modern-auth-modal .form-group {
            margin-bottom: 18px;
        }

        .modern-auth-modal .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .modern-auth-modal .form-control:focus {
            border-color: #E30613;
            box-shadow: 0 0 0 0.15rem rgba(227, 6, 19, 0.25);
        }

        .modern-auth-modal .btn-auth-primary {
            background: #E30613;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 10px 25px rgba(227, 6, 19, 0.3);
        }

        .modern-auth-modal .btn-auth-primary:hover {
            background: #C40510;
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(227, 6, 19, 0.35);
        }

        .modern-auth-modal .auth-links {
            font-size: 14px;
            text-align: center;
            margin-top: 18px;
            color: #6c757d;
        }

        .modern-auth-modal .auth-links a {
            color: #E30613;
            font-weight: 600;
        }

        .modern-auth-modal .auth-divider {
            position: relative;
            text-align: center;
            margin: 22px 0 18px;
            color: #adb5bd;
            font-size: 13px;
        }

        .modern-auth-modal .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .modern-auth-modal .auth-divider span {
            background: #fff;
            padding: 0 12px;
            position: relative;
        }

        .modern-auth-modal .google-wrapper {
            display: flex;
            justify-content: center;
        }

        .modern-auth-modal .google-wrapper .g_id_signin {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        </style>


        <!-- Modal de Login - Sistema Overlay Custom (sin Bootstrap) -->
        <div class="login-modal-overlay" id="modal_login">
            <div class="login-modal-content">
                <div class="login-modal-header">
                    <div class="login-modal-title" style="margin: 0; font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: 10px;"><i class="fas fa-sign-in-alt"></i> Iniciar sesión o registrarse</div>
                    <button class="login-modal-close">&times;</button>
                </div>
                
                <div class="login-modal-body">
                    <p class="login-subtitle" id="login-modal-subtitle">Inicia sesión o regístrate en Código Amigo para que se escuche tu voz.</p>
                    
                    <form role="form" name="login" id="login" method="post">
                        <div class="form-group">
                            <input type="text" name="mail_login" id="mail_login" placeholder="Correo electrónico" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="pass_login" id="pass_login" placeholder="Contraseña" class="form-control" required>
                        </div>
                        <button class="btn btn-auth-primary" type="submit">Iniciar sesión</button>
                        <div class="auth-links" style="margin-top:14px;">
                            <a title="Recuperar contraseña" class="enlace" href="cambiar_password">¿Olvidaste tu contraseña?</a>
                        </div>
                        <div class="auth-divider"><span>o continúa con</span></div>
                        <div class="google-wrapper">
                            <div class="g_id_signin"
                                 data-type="standard"
                                 data-size="large"
                                 data-theme="filled_blue"
                                 data-text="sign_in_with"
                                 data-shape="rectangular"
                                 data-logo_alignment="left">
                            </div>
                        </div>
                        <div class="auth-links" style="margin-top:20px;">
                            ¿Aún no te has registrado?
                            <a class="open_modal_registro" href="#" style="cursor: pointer;">Crea tu usuario</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        /* Login Modal Styles - Sistema Overlay Custom (igual que user modal) */
        .login-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-modal-overlay.active {
            display: flex !important;
            opacity: 1 !important;
        }

        .login-modal-content {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            border: 1px solid #ddd;
        }

        .login-modal-overlay.active .login-modal-content {
            transform: translateY(0);
        }

        .login-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: linear-gradient(135deg, #E30613, #FF4D4D);
            color: white;
        }

        .login-modal-title-header {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .login-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: transform 0.2s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-modal-close:hover {
            transform: rotate(90deg);
        }

        .login-modal-body {
            padding: 30px 25px;
        }

        .login-subtitle {
            color: #666;
            margin-bottom: 24px;
            font-size: 0.95rem;
            text-align: center;
        }

        .login-modal-body .form-group {
            margin-bottom: 18px;
        }

        .login-modal-body .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 15px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .login-modal-body .form-control:focus {
            border-color: #E30613;
            outline: none;
            box-shadow: 0 0 0 0.15rem rgba(227, 6, 19, 0.25);
        }

        .login-modal-body .btn-auth-primary {
            background: #E30613;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 10px 25px rgba(227, 6, 19, 0.3);
            cursor: pointer;
        }

        .login-modal-body .btn-auth-primary:hover {
            background: #C40510;
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(227, 6, 19, 0.35);
        }

        .login-modal-body .auth-links {
            font-size: 14px;
            text-align: center;
            color: #6c757d;
        }

        .login-modal-body .auth-links a {
            color: #E30613;
            font-weight: 600;
            text-decoration: none;
        }

        .login-modal-body .auth-links a:hover {
            text-decoration: underline;
        }

        .login-modal-body .auth-divider {
            position: relative;
            text-align: center;
            margin: 22px 0 18px;
            color: #adb5bd;
            font-size: 13px;
        }

        .login-modal-body .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .login-modal-body .auth-divider span {
            background: #fff;
            padding: 0 12px;
            position: relative;
        }

        .login-modal-body .google-wrapper {
            display: flex;
            justify-content: center;
        }

        @media (max-width: 480px) {
            .login-modal-content {
                max-width: 100%;
                border-radius: 12px 12px 0 0;
                margin-top: auto;
            }
            .login-modal-overlay {
                align-items: flex-end;
                padding: 0;
            }
        }
        </style>
        
        <script>
        // JavaScript para manejar el modal de login (sistema simple como user modal)
        (function() {
            const modal = document.getElementById('modal_login');
            const closeBtn = modal ? modal.querySelector('.login-modal-close') : null;
            
            // Función para cerrar el modal
            function closeLoginModal() {
                if (!modal) return;
                modal.classList.remove('active');
                
                // Limpiar backdrops de Bootstrap (evita pantalla gris bloqueada)
                if (typeof $ !== 'undefined') {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                } else {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                }

                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }
            
            // Exponer función para cerrar
            window.closeLoginModal = closeLoginModal;
            
            // Función para abrir el modal
            window.showLoginModal = function(customMessage, redirectUrl) {
                if (!modal) return;
                
                // Guardar la URL de redirección si se proporciona
                if (redirectUrl) {
                    try {
                        localStorage.setItem('redirectAfterLogin', redirectUrl);
                        console.log('[showLoginModal] Redirect URL guardada:', redirectUrl);
                    } catch (e) {
                        console.warn('[showLoginModal] No se pudo guardar redirectAfterLogin', e);
                    }
                }
                
                // Actualizar mensaje si se proporciona
                const subtitle = document.getElementById('login-modal-subtitle');
                if (subtitle) {
                    if (customMessage) {
                        subtitle.innerHTML = '<span style="color: #E30613; font-weight: bold;">' + customMessage + '</span>';
                    } else {
                        subtitle.textContent = 'Inicia sesión o regístrate en Código Amigo para que se escuche tu voz.';
                    }
                }
                
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('active');
                }, 10);
                document.body.style.overflow = 'hidden';
            };
            
            // Event listener para el botón de cerrar
            if (closeBtn) {
                closeBtn.addEventListener('click', closeLoginModal);
            }
            
            // Cerrar al hacer click fuera del modal
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeLoginModal();
                    }
                });
            }

            // Event listener para cambiar de login a registro
            const registroLink = modal ? modal.querySelector('.open_modal_registro') : null;
            if (registroLink) {
                registroLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeLoginModal();
                    if (typeof window.showRegistroModal === 'function') {
                        setTimeout(() => window.showRegistroModal(), 100);
                    }
                });
            }
        })();
        </script>
        

        <!-- Modal de Registro - Sistema Overlay Custom -->
        <div class="login-modal-overlay" id="modal_registro">
            <div class="login-modal-content">
                <div class="login-modal-header">
                    <div class="login-modal-title-header"><i class="fas fa-user-plus"></i> Crea tu cuenta</div>
                    <button class="login-modal-close">&times;</button>
                </div>
                
                <div class="login-modal-body">
                    <p class="login-subtitle">Publica códigos, haz seguimiento y gana recompensas</p>
                    
                    <form id="registrar_usuario_modal" action="" method="POST">
                        <div class="form-group">
                            <input type="text" placeholder="Nombre completo" id="nombre_modal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Dirección de correo" id="correo_modal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="password" placeholder="Introduce tu contraseña" id="password_modal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="password" placeholder="Confirma tu contraseña" id="confirm_password_modal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="text" placeholder="Código de referido (opcional)" id="codigo_referido_modal" class="form-control">
                            <small style="color: #6c757d; font-size: 12px;">Si tienes un código de referido, introdúcelo aquí</small>
                        </div>
                        <p class="help-block hide" id="text_ayuda_modal">La contraseña debe tener un mínimo de 8 caracteres</p>
                        <div class="form-group" style="display:flex; align-items:flex-start; gap:10px;">
                            <input style="width: 20px; margin-top:4px;" type="checkbox" id="acepto_modal" required />
                            <span style="font-size: 13px; color:#495057;">
                                Acepto <a href='/politica-de-privacidad' target="_blank">la política de privacidad y protección de datos de <strong>Código Amigo</strong></a>
                            </span>
                        </div>
                        <button class="btn btn-auth-primary" type="submit">Registrar usuario</button>
                        <div class="auth-divider"><span>o regístrate con</span></div>
                        <div class="google-wrapper">
                            <div class="g_id_signin"
                                 data-type="standard"
                                 data-size="large"
                                 data-theme="filled_blue"
                                 data-text="sign_up_with"
                                 data-shape="rectangular"
                                 data-logo_alignment="left">
                            </div>
                        </div>
                        <div class="auth-links" style="margin-top:20px;">
                            ¿Ya tienes cuenta?
                            <a class="open_modal_login" href="#" style="cursor: pointer;">Inicia sesión</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        // JavaScript para manejar el modal de registro
        (function() {
            const modal = document.getElementById('modal_registro');
            const closeBtn = modal ? modal.querySelector('.login-modal-close') : null;
            
            // Función para cerrar el modal
            function closeRegistroModal() {
                if (!modal) return;
                modal.classList.remove('active');

                // Limpiar backdrops de Bootstrap
                if (typeof $ !== 'undefined') {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }

                setTimeout(() => {
                    modal.style.display = 'none';
                    if (!document.getElementById('modal_login') || !document.getElementById('modal_login').classList.contains('active')) {
                        document.body.style.overflow = '';
                    }
                }, 300);
            }
            
            // Exponer función window
            window.closeRegistroModal = closeRegistroModal;
            
            // Función para abrir el modal
            window.showRegistroModal = function() {
                if (!modal) return;
                
                // Cerrar login si está abierto
                const loginModal = document.getElementById('modal_login');
                if (loginModal && loginModal.classList.contains('active')) {
                    loginModal.classList.remove('active');
                    setTimeout(() => {
                        loginModal.style.display = 'none';
                    }, 300);
                }
                
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('active');
                }, 10);
                document.body.style.overflow = 'hidden';
            };
            
            // Event listener para el botón de cerrar
            if (closeBtn) {
                closeBtn.addEventListener('click', closeRegistroModal);
            }
            
            // Cerrar al hacer click fuera del modal
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeRegistroModal();
                    }
                });
            }
            
            // Event listener para abrir registro desde cualquier botón
            document.addEventListener('click', function(e) {
                if (e.target.closest('.open_modal_registro')) {
                    e.preventDefault();
                    window.showRegistroModal();
                }
            });
            
            // Event listener para cambiar de registro a login
            const loginLink = modal ? modal.querySelector('.open_modal_login') : null;
            if (loginLink) {
                loginLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeRegistroModal();
                    if (typeof window.showLoginModal === 'function') {
                        setTimeout(() => window.showLoginModal(), 100);
                    }
                });
            }
        })();
        </script>
        
        <!-- Modal de Activación de Usuario -->

        <!-- Modal de Activación de Usuario - Sistema Overlay Custom -->
        <div class="login-modal-overlay" id="modal_activacion">
            <div class="login-modal-content">
                <div class="login-modal-header" style="background: #E30613;">
                    <div class="login-modal-title-header"><i class="fas fa-envelope"></i> Activa tu cuenta</div>
                    <button class="login-modal-close">&times;</button>
                </div>
                
                <div class="login-modal-body" style="text-align: center;">
                    <div style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #E30613; margin-bottom: 15px;"></i>
                    </div>
                    <div style="color: #E30613; margin-bottom: 15px; font-weight: 600; font-size: 18px;">
                        ¡Cuenta pendiente de activación!
                    </div>
                    <p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 25px;">
                        Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.
                    </p>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: left;">
                        <p style="margin: 0; color: #666; font-size: 14px;">
                            <i class="fas fa-info-circle"></i> 
                            <strong>¿No recibiste el email?</strong> Revisa tu carpeta de spam o solicita un nuevo enlace de activación.
                        </p>
                    </div>
                    
                    <div class="form-group text-center">
                        <button class="btn btn-primary" id="btn-reenviar-email" style="background: #E30613; border-color: #E30613; padding: 12px 30px; font-size: 16px; margin-right: 10px; border-radius: 8px;">
                            <i class="fas fa-paper-plane"></i>
                            <span id="btn-text">Reenviar email de activación</span>
                            <span id="btn-countdown" style="display: none;">(60s)</span>
                        </button>
                        <button class="btn btn-secondary login-modal-close-btn" style="padding: 12px 20px; font-size: 16px; border-radius: 8px;">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                    
                    <div id="reenvio-status" style="margin-top: 15px; display: none;">
                        <div class="alert alert-success" style="margin: 0;">
                            <i class="fas fa-check-circle"></i>
                            <span id="status-message">Email de activación reenviado correctamente</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // JavaScript para manejar el modal de activación
        (function() {
            const modal = document.getElementById('modal_activacion');
            const closeBtn = modal ? modal.querySelector('.login-modal-close') : null;
            const cancelBtn = modal ? modal.querySelector('.login-modal-close-btn') : null;
            
            // Función para cerrar el modal
            function closeActivationModal() {
                if (!modal) return;
                modal.classList.remove('active');

                // Limpiar backdrops de Bootstrap
                if (typeof $ !== 'undefined') {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }

                setTimeout(() => {
                    modal.style.display = 'none';
                    if (!document.getElementById('modal_login') || !document.getElementById('modal_login').classList.contains('active')) {
                        document.body.style.overflow = '';
                    }
                }, 300);
            }
            
            // Exponer función window para cerrar (usada por otros scripts)
            window.closeActivationModal = closeActivationModal;
            
            // Función para abrir el modal
            window.showActivationModalCustom = function() {
                if (!modal) return;
                
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('active');
                }, 10);
                document.body.style.overflow = 'hidden';
            };
            
            // Event listener para botones de cerrar
            if (closeBtn) closeBtn.addEventListener('click', closeActivationModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeActivationModal);
            
            // Cerrar al hacer click fuera del modal
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeActivationModal();
                    }
                });
            }
        })();
        </script>
        
        <!-- Google OAuth -->
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        
        <!-- Google reCAPTCHA -->
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script>
        function parseLoginResponse(data) {
            if (data === null || typeof data === 'undefined') {
                return null;
            }

            if (typeof data === 'string') {
                var trimmed = data.trim();
                if (!trimmed) {
                    return null;
                }
                try {
                    return JSON.parse(trimmed);
                } catch (e) {
                    return null;
                }
            }

            return data;
        }

        function showLoginError(message) {
            var finalMessage = message || 'Error al procesar el inicio de sesión. Inténtalo de nuevo.';
            alert(finalMessage);
        }

        // Función callback para Google OAuth
        function googleLoginEndpoint(response) {
            console.log('[GoogleLogin] googleLoginEndpoint triggered', response);
            $.ajax({
                type: "POST",
                url: "/api/login.php",
                data: {
                    metodo: "google_login",
                    credential: response.credential
                },
                cache: false
            }).done(function(data) {
                console.log('[GoogleLogin] /api/login.php response raw:', data);
                var responseData = parseLoginResponse(data);
                console.log('[GoogleLogin] Parsed response:', responseData);

                if (!responseData) {
                    showLoginError('Respuesta inválida del servidor de Google.');
                    return;
                }

                if (responseData.success && responseData.user) {
                    console.log('[GoogleLogin] Success, applying user session');
                    if (typeof window.applyUserSession === 'function') {
                        window.applyUserSession(responseData.user);
                    } else if (typeof showUserMenu === 'function') {
                        showUserMenu(responseData.user);
                    }

                    if (typeof updateMenuSession === 'function') {
                        updateMenuSession();
                    }

                    if (responseData.message && typeof showSuccessToast === 'function') {
                        setTimeout(function() {
                            showSuccessToast(responseData.message);
                        }, 100);
                    }

                    if (typeof window.closeLoginModal === 'function') {
                        window.closeLoginModal();
                    } else {
                        $("#modal_login").modal('hide');
                    }


                    var redirectUrl = localStorage.getItem('redirectAfterLogin');
                    if (redirectUrl && redirectUrl !== window.location.href) {
                        localStorage.removeItem('redirectAfterLogin');
                        window.location.href = redirectUrl;
                    } else {
                        location.reload();
                    }
                } else if (responseData.error) {
                    console.warn('[GoogleLogin] Error payload recibido:', responseData.error);
                    if (responseData.error === 'no_verificado' && typeof showActivationModal === 'function') {
                        showActivationModal();
                    } else {
                        showLoginError('Error en el login con Google: ' + responseData.error);
                    }
                } else {
                    console.error('[GoogleLogin] Respuesta inesperada', responseData);
                    showLoginError('No se pudo completar el login con Google.');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('[GoogleLogin] AJAX fail', textStatus, errorThrown, jqXHR);
                showLoginError('Error al procesar el login con Google. Inténtalo de nuevo.');
            });
        }
        </script>
        
        <div id="g_id_onload"
            data-client_id="298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com"
            data-context="signin"
            data-callback="googleLoginEndpoint"
            data-close_on_tap_outside="false"
            data-auto_prompt="false">
        </div>
        
        <!-- Bootstrap 3 JS para modales -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
        
        <!-- JavaScript para funcionalidad de login -->
        <script>
        $(document).ready(function() {
            // Event listener para el botón de login usando capture phase
            document.addEventListener('click', function(event) {
                const loginButton = event.target.closest('.open_modal_login');
                if (!loginButton) return;
                
                event.preventDefault();
                event.stopPropagation();
                
                var redirectUrl = loginButton.getAttribute('data-redirect-url') || window.location.href;
                
                if (typeof window.openLoginModalWithRedirect !== 'function') {
                    alert('Error: La función de login no está disponible. Por favor recarga la página.');
                    return;
                }
                
                try {
                    openLoginModalWithRedirect(redirectUrl);
                } catch (error) {
                    console.error('[LoginModal] Error:', error);
                    alert('Error al abrir el modal: ' + error.message);
                }
            }, true);
            
            var loginInProgress = false;
            if (typeof window.loginSubmitHandlerAttached === 'undefined') {
                window.loginSubmitHandlerAttached = false;
            }

            if (!window.loginSubmitHandlerAttached) {
                window.loginSubmitHandlerAttached = true;

                $(document).on('submit', '#login', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (loginInProgress) {
                        return false;
                    }

                    loginInProgress = true;

                    var $submitBtn = $(this).find('button[type="submit"]');
                    var originalText = $submitBtn.text();
                    $submitBtn.prop('disabled', true).text('Iniciando sesión...');

                    $.ajax({
                        type: "POST",
                        url: "/api/login.php",
                        data: {
                            metodo: "login_user",
                            mail: $("#mail_login").val(),
                            pass: $("#pass_login").val()
                        },
                        cache: false,
                        timeout: 10000
                    }).done(function(data) {
                        var response = parseLoginResponse(data);

                        if (!response) {
                            showLoginError();
                            return;
                        }

                        if (response.error) {
                            if (response.error === "no_trobat") {
                                showLoginError("El usuario y contraseña introducidos no aparecen en nuestra base de datos");
                            } else if (response.error === "no_verificado") {
                                if (typeof showActivationModal === 'function') {
                                    showActivationModal();
                                } else {
                                    showLoginError("Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.");
                                }
                            } else {
                                showLoginError(response.error);
                            }
                            return;
                        }

                        if (response.success && response.user) {
                            if (typeof window.applyUserSession === 'function') {
                                window.applyUserSession(response.user);
                            } else if (typeof showUserMenu === 'function') {
                                showUserMenu(response.user);
                            }

                            if (typeof updateMenuSession === 'function') {
                                updateMenuSession();
                            }

                            if (response.message && typeof showSuccessToast === 'function') {
                                setTimeout(function() {
                                    showSuccessToast(response.message);
                                }, 100);
                            }

                            if (typeof window.closeLoginModal === 'function') {
                                window.closeLoginModal();
                            } else {
                                $("#modal_login").modal('hide');
                            }


                            var redirectUrl = localStorage.getItem('redirectAfterLogin');
                            if (redirectUrl && redirectUrl !== window.location.href) {
                                localStorage.removeItem('redirectAfterLogin');
                                window.location.href = redirectUrl;
                            } else {
                                location.reload();
                            }
                        } else {
                            showLoginError('Respuesta inesperada del servidor.');
                        }
                    }).fail(function() {
                        showLoginError('Error al procesar el login. Inténtalo de nuevo.');
                    }).always(function() {
                        $submitBtn.prop('disabled', false).text(originalText);
                        loginInProgress = false;
                    });
                });
            }
            
            
            // Funcionalidad del menú de usuario
            initializeUserMenu();
            
            // Manejar clic en botón de registro
            // Event listener .open_modal_registro removido ya que se maneja globalmente en el script del modal

            
            // Manejar envío del formulario de registro
            $(document).on('submit', '#registrar_usuario_modal', function(event) {
                event.preventDefault();
                event.stopPropagation();
                // Formulario de registro enviado

                var nombre = $("#nombre_modal").val();
                var correo = $("#correo_modal").val();
                var password = $("#password_modal").val();
                var confirm_password = $("#confirm_password_modal").val();
                var codigo_referido = $("#codigo_referido_modal").val();
                var acepto = $("#acepto_modal").is(':checked');

                var tamaño_password = password.length;

                if(tamaño_password < 8) {
                    alert("La contraseña debe tener un mínimo de 8 caracteres");
                    $("#password_modal").focus();
                    return false;
                } else if(password != confirm_password) {
                    alert("Las contraseñas deben ser iguales");
                    $("#password_modal").focus();
                    return false;
                } else if(!acepto) {
                    alert("Debes aceptar la política de privacidad para continuar");
                    $("#acepto_modal").focus();
                    return false;
                } else {
                    $.ajax({
                        type: "POST",
                        url: "/myphp/ajax_actions.php",
                        data: {
                            metodo: "registrar_usuario",
                            nombre: nombre,
                            correo: correo,
                            password: password,
                            codigo_referido: codigo_referido,
                            origin: "web",
                        }, 
                        cache: false,
                        success: function(data){
                            if(data == "trobat") { 
                                alert("Este correo ya existe. Prueba con otro !!!");
                                $("#correo_modal").focus(); 
                            } else {
                                // Registro exitoso - cerrar modal y mostrar mensaje
                                // Registro exitoso - cerrar modal y mostrar mensaje
                                if (typeof window.closeRegistroModal === 'function') {
                                    window.closeRegistroModal();
                                } else {
                                    $("#modal_registro").modal('hide');
                                }

                                alert("¡Registro exitoso! Ahora puedes iniciar sesión.");
                                
                                // Abrir modal de login
                                openLoginModalWithRedirect(window.location.href);
                                
                                // Limpiar formulario de registro
                                $("#registrar_usuario_modal")[0].reset();
                            }
                        },
                        error: function(xhr, status, error) {
                            // Error en AJAX
                            alert('Error al procesar el registro. Inténtalo de nuevo.');
                        }
                    });
                }
            });
        });
        
        // Función para inicializar el menú de usuario
        function initializeUserMenu() {
            // Verificar si el usuario está logueado
            checkLoginStatus();
            
            // Manejar clic en el perfil de usuario
            $(document).on('click', '#user-profile', function(e) {
                e.stopPropagation();
                toggleUserDropdown();
            });
            
            // Cerrar menú al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-menu').length) {
                    closeUserDropdown();
                }
            });
            
            // Manejar logout
            $(document).on('click', '#logout-btn', function(e) {
                e.preventDefault();
                logout();
            });
        }
        
        function cacheUserData(user) {
            if (!user || typeof user !== 'object') { return; }
            try {
                var serialized = JSON.stringify(user);
                localStorage.setItem('userData', serialized);
                sessionStorage.setItem('userData', serialized);
            } catch (e) {
                // No se pudo guardar userData en almacenamiento local
            }
        }

        function removeCachedUserData() {
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
        }

        function parseUserData(raw) {
            if (!raw) { return null; }
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function getCachedUserData() {
            var raw = localStorage.getItem('userData') || sessionStorage.getItem('userData');
            return parseUserData(raw);
        }

        window.applyUserSession = function(user) {
            if (!user || !user.id) {
                window.clearUserSession();
                return;
            }

            window.serverUserData = user;
            if (typeof window.serverUserDataBasic !== 'undefined') {
                window.serverUserDataBasic = user;
            }

            cacheUserData(user);
            showUserMenu(user);

            if (typeof window.updateBottomMenu === 'function') {
                window.updateBottomMenu();
            }
        };

        window.clearUserSession = function() {
            window.serverUserData = null;
            if (typeof window.serverUserDataBasic !== 'undefined') {
                window.serverUserDataBasic = null;
            }

            removeCachedUserData();
            showLoginButton();

            if (typeof window.updateBottomMenu === 'function') {
                window.updateBottomMenu();
            }
        };

        function requestServerSession(onSuccess, onInvalid, onError) {
            return $.ajax({
                type: "POST",
                url: "/myphp/ajax_actions.php",
                data: { metodo: "check_session" },
                cache: false,
                timeout: 5000
            }).done(function(data) {
                var response = data;
                if (typeof data === 'string') {
                    try {
                        response = JSON.parse(data.trim());
                    } catch (e) {
                        response = null;
                    }
                }

                if (!response || response.success !== true || response.logged_in !== true || !response.user) {
                    if (typeof onInvalid === 'function') {
                        onInvalid(response);
                    }
                    return;
                }

                if (typeof onSuccess === 'function') {
                    onSuccess(response.user);
                }
            }).fail(function(jqXHR, status, error) {
                if (typeof onError === 'function') {
                    onError(status || error);
                }
            });
        }

        // Función para verificar el estado de login
        function checkLoginStatus(options) {
            options = options || {};
            var allowCacheFallback = options.allowCacheFallback !== false;

            requestServerSession(
                function(user) {
                    if (typeof window.applyUserSession === 'function') {
                        window.applyUserSession(user);
                    } else if (typeof showUserMenu === 'function') {
                        showUserMenu(user);
                    }
                },
                function() {
                    window.clearUserSession();
                },
                function() {
                    if (allowCacheFallback) {
                        var cachedUser = getCachedUserData();
                        if (cachedUser) {
                            if (typeof window.applyUserSession === 'function') {
                                window.applyUserSession(cachedUser);
                            } else if (typeof showUserMenu === 'function') {
                                showUserMenu(cachedUser);
                            }
                            return;
                        }
                    }
                    window.clearUserSession();
                }
            );
        }
        
        // Función para mostrar el menú de usuario
        function showUserMenu(user) {
            $('#btn-login').hide();
            $('#btn-publicar').show(); // El botón de publicar código debe permanecer visible
            $('#user-menu').css('display', 'flex');
            
            // Actualizar datos del usuario
            var username = user.username || user.name || 'Usuario';
            $('#user-name').text(username);
            
            if (user.avatar || user.img) {
                var avatarUrl = user.avatar || user.img;
                $('#user-avatar').attr('src', avatarUrl);
            } else {
                // Mostrar iniciales si no hay avatar
                var initials = getInitials(user.username || user.name || 'U');
                $('#user-avatar').attr('src', 'data:image/svg+xml;base64,' + btoa(createAvatarSVG(initials)));
            }
        }
        
        // Función para mostrar el botón de login
        function showLoginButton() {
            $('#user-menu').hide();
            $('#btn-login').show();
            $('#btn-publicar').show();
        }

        // Función para mostrar toast de éxito
        function showSuccessToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast toast-success';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                z-index: 10000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-family: Arial, sans-serif;
                font-size: 14px;
            `;

            document.body.appendChild(toast);

            // Animación de entrada
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 100);

            // Auto-remover después de 4 segundos
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 4000);
        }

        // Función de test para probar el toast
        window.testToast = function() {
            // Ejecutando test de toast
            if (typeof showSuccessToast === 'function') {
                showSuccessToast('Test de toast funcionando ✅');
            } else {
                // showSuccessToast no está definida
            }
        };

        // Función para alternar el menú desplegable
        function toggleUserDropdown() {
            $('#user-dropdown').toggleClass('show');
            $('#user-profile').toggleClass('active');
        }
        
        // Función para cerrar el menú desplegable
        function closeUserDropdown() {
            $('#user-dropdown').removeClass('show');
            $('#user-profile').removeClass('active');
        }

        // Función para actualizar el menú de sesión (llamada desde el login)
        function updateMenuSession() {
            // Esta función se define en el JavaScript móvil, pero la declaramos aquí para compatibilidad
            // Si existe la implementación móvil, usará esa; si no, no hará nada
            if (typeof window.updateMobileProfileMenu === 'function') {
                window.updateMobileProfileMenu();
            }

            // También intentar después de un pequeño retraso para asegurar que el JS móvil esté cargado
            setTimeout(function() {
                if (typeof window.updateMobileProfileMenu === 'function') {
                    // Llamando updateMobileProfileMenu con retraso
                    window.updateMobileProfileMenu();
                }
            }, 200);
        }
        
        // Función para cerrar sesión
        function logout() {
            window.clearUserSession();
            window.location.href = '/logout';
        }
        
        // Función para obtener iniciales
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().substring(0, 2);
        }
        
        // Función para crear SVG de avatar con iniciales
        function createAvatarSVG(initials) {
            return `<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" fill="#E30613" rx="16"/>
                <text x="16" y="20" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="12" font-weight="bold">${initials}</text>
            </svg>`;
        }
    
    // Función para ver código (redirigir a página de detalle)
    window.viewCode = function(codeId, marca = null) {
        // Si no se proporciona la marca, intentar obtenerla de la URL actual
        if (!marca) {
            var currentPath = window.location.pathname;
            if (currentPath.startsWith('/de-')) {
                marca = currentPath.replace('/de-', '');
            } else {
                // Si estamos en la home, necesitamos obtener la marca del código
                // Por ahora, redirigir a una página genérica de códigos
                window.location.href = '/codigos?codigo=' + codeId;
                return;
            }
        }
        
        // Redirigir a la página de detalle del código
        window.location.href = '/de-' + marca + '?codigo=' + codeId;
    };

    // Función para abrir chat directo con un usuario desde tarjeta de código
    window.openDirectChat = function(userId, username) {
        // Comprobar si el usuario está logueado
        var currentUserId = null;
        if (window.serverUserData && window.serverUserData.id) {
            currentUserId = window.serverUserData.id;
        }

        if (!currentUserId) {
            // No logueado: mostrar modal de login con redirect al chat
            var chatUrl = '/public/chat_usuario.php?open_chat=' + userId;
            if (typeof window.showLoginModal === 'function') {
                window.showLoginModal('Para contactar con ' + username + ' necesitas iniciar sesión en Código Amigo.', chatUrl);
            } else if (typeof window.openLoginModalWithRedirect === 'function') {
                localStorage.setItem('redirectAfterLogin', chatUrl);
                window.openLoginModalWithRedirect(chatUrl);
            } else {
                window.location.href = '/login.php';
            }
            return;
        }

        // Logueado: abrir chat directamente con mensaje predeterminado
        var defaultMsg = encodeURIComponent('Hola buenas, me ayudas con el proceso y lo hacemos juntos?');
        window.location.href = '/public/chat_usuario.php?open_chat=' + userId + '&msg=' + defaultMsg;
    };
    
    // Función para copiar código al portapapeles
    window.copyCode = function() {
        var codeText = document.getElementById('codeText');
        if (codeText) {
            navigator.clipboard.writeText(codeText.textContent).then(function() {
                // Mostrar notificación de éxito
                var btn = document.querySelector('.btn-copy');
                var originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                btn.style.backgroundColor = '#28a745';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.backgroundColor = '';
                }, 2000);
            }).catch(function(err) {
                // Error al copiar
                alert('Error al copiar el código');
            });
        }
    };
    
    // Función para compartir código
    window.shareCode = function() {
        if (navigator.share) {
            navigator.share({
                title: 'Código de descuento',
                text: 'Mira este código de descuento que encontré',
                url: window.location.href
            }).catch(function(err) {
                if (err.name !== 'AbortError') {
                    console.error('Error sharing:', err);
                }
            });
        } else {
            // Fallback: copiar URL al portapapeles
            navigator.clipboard.writeText(window.location.href).then(function() {
                alert('URL copiada al portapapeles');
            });
        }
    };
    
    // Función para toggle favorito
    window.toggleFavorite = function() {
        var btn = document.querySelector('.btn-favorite');
        var icon = btn.querySelector('i');
        
        if (icon.classList.contains('fas')) {
            icon.classList.remove('fas');
            icon.classList.add('far');
            btn.style.backgroundColor = '#444';
        } else {
            icon.classList.remove('far');
            icon.classList.add('fas');
            btn.style.backgroundColor = '#dc3545';
        }
    };
    
    // Función para volver atrás
    window.goBack = function() {
        // Obtener la marca actual de la URL
        var currentPath = window.location.pathname;
        var marca = currentPath.replace('/de-', '');
        
        // Redirigir a la página de listado de la marca
        window.location.href = '/de-' + marca;
    };

    // Funciones para filtros
    window.applyFilters = function() {
        const fechaSeleccionada = document.querySelector('input[name="fecha"]:checked').value;
        
        // Construir la URL con el filtro de fecha
        const url = new URL(window.location);
        url.searchParams.set('fecha', fechaSeleccionada);
        
        // Recargar la página con el filtro aplicado
        window.location.href = url.toString();
    };

    window.clearFilters = function() {
        // Seleccionar "todo el tiempo" por defecto
        document.querySelector('input[value="todo"]').checked = true;
        
        // Remover parámetros de filtro de la URL
        const url = new URL(window.location);
        url.searchParams.delete('fecha');
        
        // Recargar la página sin filtros
        window.location.href = url.toString();
    };

    // Efecto de parallax para el fondo de marca (solo si existe)
    if (document.querySelector('.brand-bg-image')) {
        window.addEventListener('scroll', function() {
            const bgImage = document.querySelector('.brand-bg-image');
            if (bgImage && bgImage.style) {
                const scrolled = window.pageYOffset;
                const parallax = scrolled * 0.5;
                bgImage.style.transform = `translateY(${parallax}px) scale(1.1)`;
            }
        });

        // Efecto de hover para el fondo de marca
        document.addEventListener('DOMContentLoaded', function() {
            const bgImage = document.querySelector('.brand-bg-image');
            if (bgImage && bgImage.addEventListener) {
                bgImage.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.15)';
                    this.style.filter = 'blur(1px)';
                });
                
                bgImage.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1.1)';
                    this.style.filter = 'blur(2px)';
                });
            }
        });
    }
    
    // Variables globales para el modal de activación
    let countdownTimer = null;
    let countdownSeconds = 60;
    let isResendEnabled = true;
    
    // Función para mostrar el modal de activación
    function showActivationModal() {
        // Resetear estado del modal
        resetActivationModal();
        
        // Mostrar el modal
        if (typeof window.showActivationModalCustom === 'function') {
            window.showActivationModalCustom();
        } else {
            // Fallback por si acaso
            $("#modal_activacion").modal('show');
        }
        
        // Manejar clic en reenviar email
        $("#btn-reenviar-email").off('click').on('click', function() {
            if (isResendEnabled) {
                reenviarEmailActivacion();
            }
        });
    }
    
    // Función para resetear el modal de activación
    function resetActivationModal() {
        countdownSeconds = 60;
        isResendEnabled = true;
        
        // Resetear botón
        $("#btn-text").show();
        $("#btn-countdown").hide();
        $("#btn-reenviar-email").prop('disabled', false);
        $("#btn-reenviar-email").removeClass('btn-secondary').addClass('btn-primary');
        
        // Ocultar mensaje de estado
        $("#reenvio-status").hide();
        
        // Limpiar timer si existe
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }
    
    // Función para reenviar email de activación
    function reenviarEmailActivacion() {
        var email = $("#mail_login").val();
        
        if (!email) {
            alert("Por favor, introduce tu email primero");
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        $("#btn-reenviar-email").prop('disabled', true);
        $("#btn-text").text("Enviando...");
        
        $.ajax({
            type: "POST",
            url: "/reenviar-activacion",
            data: {
                email: email
            },
            cache: false,
            success: function(data) {
                // Respuesta de reenvío de activación

                var response;
                if (typeof data === 'string') {
                    response = data.trim();
                } else {
                    // Si es JSON, buscar el mensaje de error o success
                    response = data.success ? "success" : (data.error || "error");
                }

                if (response === "success") {
                    // Mostrar mensaje de éxito
                    $("#reenvio-status").show();
                    $("#status-message").text("Email de activación reenviado correctamente. Revisa tu bandeja de entrada.");
                    
                    // Iniciar countdown
                    iniciarCountdown();
                } else if (response === "not_found") {
                    alert("No se encontró una cuenta con ese email");
                    resetActivationModal();
                } else if (response === "already_verified") {
                    alert("Esta cuenta ya está activada. Puedes iniciar sesión normalmente.");
                    $("#modal_activacion").modal('hide');
                } else {
                    alert("Error al reenviar el email. Inténtalo de nuevo.");
                    resetActivationModal();
                }
            },
            error: function() {
                alert("Error de conexión. Inténtalo de nuevo.");
                resetActivationModal();
            }
        });
    }
    
    // Función para iniciar el countdown
    function iniciarCountdown() {
        isResendEnabled = false;
        countdownSeconds = 60;
        
        // Cambiar apariencia del botón
        $("#btn-text").hide();
        $("#btn-countdown").show();
        $("#btn-reenviar-email").removeClass('btn-primary').addClass('btn-secondary');
        
        // Iniciar timer
        countdownTimer = setInterval(function() {
            countdownSeconds--;
            $("#btn-countdown").text("(" + countdownSeconds + "s)");
            
            if (countdownSeconds <= 0) {
                // Rehabilitar botón
                clearInterval(countdownTimer);
                countdownTimer = null;
                isResendEnabled = true;
                
                $("#btn-text").show().text("Reenviar email de activación");
                $("#btn-countdown").hide();
                $("#btn-reenviar-email").removeClass('btn-secondary').addClass('btn-primary');
                $("#btn-reenviar-email").prop('disabled', false);
            }
        }, 1000);
    }
  
    </script>
    
    <?php
    // Incluir funcionalidades adicionales
    echo '<script src="/js/read-more.js"></script>';

    // Incluir script del menú inferior
    echo '<script src="/myphp/bottom_menu_script.php"></script>';

    // Agregar JavaScript móvil
    add_mobile_javascript();
    ?>
</body>
</html>
