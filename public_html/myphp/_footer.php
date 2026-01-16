<?php


// if (session_status() === PHP_SESSION_ACTIVE) {
//     echo 'La sesión está activa.';
// } else {
//     echo 'La sesión no está activa.';
// }
//$_SESSION['mi_parametro_ttl'] = 1;

function get_footer_modern() {
    ?><!-- Footer moderno -->
    <footer class="footer-modern">
        <!-- Sección de bienvenida -->
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="welcome-section">
                            <div class="welcome-title">¡Gracias por usar <span class="highlight-number">CodigoAmigo</span>!</div>
                            <p class="welcome-description">
                                Somos la comunidad más grande de España dedicada a compartir códigos de descuento verificados.
                                Miles de usuarios como tú comparten sus mejores hallazgos cada día.
                            </p>
                            <div class="user-avatars">
                                <?php
                                // Obtener usuarios activos para mostrar sus avatares
                                if (!function_exists('get_usuarios_activos_footer')) {
                                    include_once __DIR__ . '/funciones_modern.php';
                                }
                                $usuarios_activos = get_usuarios_activos_footer(12);
                                foreach ($usuarios_activos as $usuario): 
                                ?>
                                    <div class="avatar user-modal-trigger" 
                                         data-username="<?php echo htmlspecialchars($usuario['username']); ?>"
                                         data-image="<?php echo htmlspecialchars($usuario['img']); ?>"
                                         data-joined="Top Contribuidor"
                                         data-stats-offers="<?php echo $usuario['total_codigos']; ?>"
                                         data-user-id="<?php echo isset($usuario['id']) ? (string)$usuario['id'] : ''; ?>"
                                         style="cursor: pointer; background-image: url('<?php echo htmlspecialchars($usuario['img']); ?>'); background-size: cover; background-position: center;">
                                        <?php if (empty($usuario['img']) || strpos($usuario['img'], 'usuario_sin_foto') !== false): ?>
                                            <span style="display: none;"></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <span style="color: #cccccc; margin-left: 10px; font-size: 0.9rem;">+12.500 usuarios activos</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="social-section">
                            <div class="social-title">Síguenos en redes</div>
                            <div class="social-icons">
                                <a href="https://t.me/codigoamigocom" class="social-icon" target="_blank" title="Telegram" style="display: inline-flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.442-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.099.155.232.171.326.016.094.036.308.02.475z"/></svg>
                                </a>
                                <a href="https://x.com/codigoamigoweb" class="social-icon" target="_blank" title="X (Twitter)" style="display: inline-flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a href="https://facebook.com/codigoamigo" class="social-icon" target="_blank" title="Facebook" style="display: inline-flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a href="https://instagram.com/codigoamigo" class="social-icon" target="_blank" title="Instagram" style="display: inline-flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección principal del footer -->
        <div class="footer-main">
            <div class="container">
                <div class="row">
                    <!-- Información de contacto -->
                    <div class="col-md-3 col-sm-6">
                        <div class="footer-column">
                            <div class="column-title">Contacto</div>
                            <ul class="footer-links">
                                <li><a href="/contacto" class="footer-link">Contacto</a></li>
                                <li><a href="/sobre-nosotros" class="footer-link">Sobre nosotros</a></li>
                                <li><a href="/preguntas-frecuentes" class="footer-link">FAQ</a></li>
                                <li><a href="https://t.me/spnfury" class="footer-link" target="_blank">Soporte técnico</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Categorías populares -->
                    <div class="col-md-3 col-sm-6">
                        <div class="footer-column">
                            <div class="column-title">Categorías</div>
                            <ul class="footer-links">
                                <?php
                                // Obtener categorías reales de MongoDB
                                if (!function_exists('getCategorias')) {
                                    include_once __DIR__ . '/../inc/conexion.php';
                                }
                                if (!function_exists('link_categoria')) {
                                    include_once __DIR__ . '/herramientas/links.php';
                                }
                                
                                $categorias = getCategorias();
                                $categorias_array = iterator_to_array($categorias);
                                $categorias_mostrar = array_slice($categorias_array, 0, 4); // Mostrar solo las primeras 4
                                
                                foreach ($categorias_mostrar as $categoria):
                                    $nombre_clave = $categoria['nombre_clave'] ?? '';
                                    $nombre = $categoria['nombre'] ?? 'Categoría';
                                    if (!empty($nombre_clave)):
                                ?>
                                    <li><a href="<?php echo link_categoria($nombre_clave); ?>" class="footer-link"><?php echo htmlspecialchars($nombre); ?></a></li>
                                <?php
                                    endif;
                                endforeach;
                                
                                // Si no hay categorías, mostrar las por defecto
                                if (empty($categorias_mostrar)):
                                ?>
                                    <li><a href="/tecnologia-y-electronica-comparte-y-gana" class="footer-link">Tecnología</a></li>
                                    <li><a href="/moda-y-belleza-comparte-y-gana" class="footer-link">Moda y Belleza</a></li>
                                    <li><a href="/hogar-y-jardin-comparte-y-gana" class="footer-link">Hogar y Jardín</a></li>
                                    <li><a href="/deportes-y-ocio-comparte-y-gana" class="footer-link">Deportes</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Recursos -->
                    <div class="col-md-3 col-sm-6">
                        <div class="footer-column">
                            <div class="column-title">Recursos</div>
                            <ul class="footer-links">
                                <li><a href="/amazon" class="footer-link" style="color: #FF9900; font-weight: bold;">Amazon Gratis <i class="fas fa-star"></i></a></li>
                                <li><a href="/nuevo_codigo" class="footer-link">Publicar código</a></li>
                                <li><a href="/listado_marcas" class="footer-link">Todas las marcas</a></li>
                                <li><a href="/destacados" class="footer-link">Códigos destacados</a></li>
                                <li><a href="/blog" class="footer-link">Blog</a></li>
                                <li><a href="https://t.me/cholloscodigoamigo" class="footer-link" target="_blank" rel="noopener noreferrer">
                                    <i class="fa-brands fa-telegram" style="margin-right: 5px;"></i>
                                    Canal de Chollos en Telegram
                                </a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Apps / Comunidad -->
                    <div class="col-md-3 col-sm-6">
                        <div class="footer-column">
                            <div class="column-title">Comunidad</div>
                            <p style="color: #cccccc; font-size: 0.9rem; margin-bottom: 15px;">
                                Únete a nuestros canales y no te pierdas ninguna oferta.
                            </p>
                            <div style="margin-top: 15px;">
                                <a href="https://t.me/codigoamigocom" target="_blank" 
                                   style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 20px; background: linear-gradient(135deg, #0088cc, #0066aa); color: white; text-decoration: none; border-radius: 8px; transition: all 0.3s ease; font-weight: 600;"
                                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(0,136,204,0.3)';" 
                                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                    <i class="fa-brands fa-telegram" style="font-size: 1.3rem;"></i>
                                    <span>Canal de Telegram</span>
                                </a>
                                <div style="text-align: center; margin-top: 8px;">
                                    <div style="color: #E30613; font-size: 0.85rem;">★★★★★</div>
                                    <div style="color: #cccccc; font-size: 0.8rem;">+5.000 miembros</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de últimos chollos (solo si no estamos en home) -->
        <?php
        $es_home = (!isset($_GET["page"]) || $_GET["page"] == "") && (strpos($_SERVER['REQUEST_URI'], '/') === 0 || $_SERVER['REQUEST_URI'] == '/');
        if (!$es_home) {
            if (!function_exists('imprimir_seccion_ultimos_chollos')) {
                include_once __DIR__ . '/funciones_modern.php';
            }
            if (function_exists('imprimir_seccion_ultimos_chollos')) {
                imprimir_seccion_ultimos_chollos(3, 'Últimos Chollos', true);
            }
        }
        ?>

        <!-- Sección promocional de chollos -->
        <div class="footer-chollos-section">
            <div class="container">
                <div class="footer-chollos-content">
                    <div class="footer-chollos-text">
                        <div class="footer-chollos-title">💰 Descubre los Chollos de CodigoAmigo</div>
                        <p class="footer-chollos-description">Encuentra las mejores ofertas y descuentos exclusivos todos los días. ¡No te pierdas ninguna oportunidad!</p>
                    </div>
                    <div class="footer-chollos-actions">
                        <a href="/chollos" class="footer-chollos-btn footer-chollos-btn-primary">
                            <span>Ver Chollos</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" class="footer-chollos-btn footer-chollos-btn-telegram">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.442-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.099.155.232.171.326.016.094.036.308.02.475z"/>
                            </svg>
                            <span>Telegram</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .footer-chollos-section {
            background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
            padding: 40px 20px;
            margin-top: 0;
        }
        
        .footer-chollos-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .footer-chollos-text {
            flex: 1;
            min-width: 300px;
        }
        
        .footer-chollos-title {
            color: white;
            font-size: 1.8em;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .footer-chollos-description {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.1em;
            margin: 0;
            line-height: 1.6;
        }
        
        .footer-chollos-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .footer-chollos-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .footer-chollos-btn-primary {
            background: white;
            color: #E30613;
        }
        
        .footer-chollos-btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: #E30613;
            text-decoration: none;
        }
        
        .footer-chollos-btn-telegram {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }
        
        .footer-chollos-btn-telegram:hover {
            background: white;
            color: #0088cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            text-decoration: none;
        }
        
        .footer-chollos-btn svg {
            width: 18px;
            height: 18px;
        }
        
        @media (max-width: 768px) {
            .footer-chollos-section {
                padding: 30px 15px;
            }
            
            .footer-chollos-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-chollos-title {
                font-size: 1.5em;
            }
            
            .footer-chollos-description {
                font-size: 1em;
            }
            
            .footer-chollos-actions {
                width: 100%;
                justify-content: center;
            }
            
            .footer-chollos-btn {
                flex: 1;
                min-width: 140px;
            }
        }
        </style>

        <!-- Footer bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="company-info">CodigoAmigo.com</div>
                        <p class="copyright-text">
                            © <?php echo date('Y'); ?> CodigoAmigo.com. Todos los derechos reservados.
                            La información proporcionada es orientativa y puede variar.
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="security-info">
                            <div class="security-title">Compra con seguridad</div>
                            <div class="security-badges">
                                <span class="security-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    Códigos verificados
                                </span>
                                <span class="security-badge">
                                    <i class="fas fa-lock"></i>
                                    Pago seguro
                                </span>
                                <span class="security-badge">
                                    <i class="fas fa-user-check"></i>
                                    Comunidad activa
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <?php
    global $force_css;
    $funciones_js_path = $_SERVER['DOCUMENT_ROOT'] . '/js/funciones.js';
    $funciones_js_version = file_exists($funciones_js_path) ? filemtime($funciones_js_path) : time();

    if ($force_css == 1) {
        ?>
        <script type="text/javascript" src="/js/funciones.js?v=<?php echo $funciones_js_version; ?>"></script>
        <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js?dd=<?php echo strtotime('now'); ?>"></script>
        <?php
    } else {
        ?>
        <script type="text/javascript" src="/js/funciones.js?v=<?php echo $funciones_js_version; ?>"></script>
        <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js"></script>
        <?php
    }
    ?>
    
    <!-- Script newsletter eliminado temporalmente -->
    <?php
    
    // Agregar el modal global de usuario
    if (function_exists('add_global_user_modal')) {
        add_global_user_modal();
    }
    
    // Botón flotante de Telegram - DESACTIVADO (no aporta valor)
    // include_once __DIR__ . '/telegram_floating_btn.php';
}

function get_footer()
{
    // Si se está usando el header moderno, usar el footer moderno correspondiente
    if (isset($GLOBALS['header_modern_used']) && $GLOBALS['header_modern_used'] === true) {
        get_footer_modern();
        return;
    }

    global $web, $show_adsense, $detect, $force_css, $panel, $marca, $nombre_pag, $u;


    //$lista_marcas = agregacionesMarcasByCategorias();

    // 	   $results = [];

    // 	   echo "<pre>";


    // 	   foreach ($lista_marcas as $value) {

    // 	       $results[] = $value;
// 	   }

    // 	   print_r($results);


    //echo $show_adsense."ee";
    if (true) {
        // if((isset($_SESSION['mi_parametro_ttl']) && time() > $_SESSION['mi_parametro_ttl']) || !$_SESSION['mi_parametro_ttl']){

        $muestra_popup = 1;

        ?>


        </div>

        <?php

    }


    ?>

    </div>


    <?php

    //if(isset($_COOKIE['telegram'])){ ?>

    <style>
        .dentro_codigo {
            height: inherit !important;
        }

        .premium {
            display: inline-block;
            background: #C5B358;
            padding: 3px;
            color: white;
            border-radius: 3px 3px 0px 3px;
            margin: 0px !important;
            position: absolute;
            right: 0px;
            top: -3px;
            font-size: 10px !important;

        }
    </style>

    <?php
    /*if(!$_SESSION["user_id"]){
    ?>
<div class="modal fade" id='modal_2' role="dialog" style="">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background: #0088cc; color: white; padding: 20px; font-size: 20px;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <span class="modal-title" style="font-size:20px;">
                <h3>Estrenamos nuevo canal de telegram con promociones y descuentos exclusivos!</h3></span>
            </div>
            <div class="modal-body">
            
                <ul>
                    <li>✔️ Descubrirás nuevos marcas y promos cada día para ganar dinero <b>GRATIS!</b></li>
                    <li>✔️ Accede a promociones exclusivas!</li>
                    <li>✔️ Forma parte de la mayor comunidad de descuentos en Español</li>
                </ul>
                
                <div class="zona_facebook text-center" style="margin-top: 10px;">
                    <a class="btn btn-primary " href="https://t.me/codigoamigocom" target="_blank" style="background:black;color:white !important;">Acceder Gratis al Canal de telegram ➡️</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }*/ ?>

    <script>
        /* Author: AdGlare Ad Server (https://www.adglare.com) */
        function hasAdblock() {
            var a = document.createElement('div');
            a.innerHTML = '&nbsp;';
            a.className = 'ads ad adsbox doubleclick ad-placement carbon-ads adglare';
            a.style = 'width: 1px !important; height: 1px !important; position: absolute !important; left: -5000px !important; top: -5000px !important;';
            var r = false;
            try {
                document.body.appendChild(a);
                var e = document.getElementsByClassName('adsbox')[0];
                if (e.offsetHeight === 0 || e.clientHeight === 0) r = true;
                if (window.getComputedStyle !== undefined) {
                    var tmp = window.getComputedStyle(e, null);
                    if (tmp && (tmp.getPropertyValue('display') == 'none' || tmp.getPropertyValue('visibility') == 'hidden')) r = true;
                }
                document.body.removeChild(a);
            } catch (e) { }
            return r;
        }

        if (hasAdblock()) {
            alert('Adblock, detectado\n\nPara ver correctamente los códigos, por favor desactiva Adblock');
        }
    </script>

    <?php

    /*setcookie('telegram', 'ro');
    $_COOKIE['telegram'] = 'ro';*/


    //} ?>



    <div class="loading" id="loading">
        <div class="dentro_loading" id="dentro_loading">
            <div class="busca_div_abs"></div>
            <div class="facebook_blockG"></div>
            <div id="blockG_2" class="facebook_blockG"></div>
            <div id="blockG_3" class="facebook_blockG"></div>
        </div>
    </div>

    <input type="hidden" class="last_codigo_final" name="last_codigo_final" />


    <?
    modal_compartir();

    modal_statistics();
    
    // Agregar el modal global de usuario si no se ha agregado ya (para evitar duplicados si se llamó a get_footer_modern)
    if (!isset($GLOBALS['header_modern_used']) || $GLOBALS['header_modern_used'] !== true) {
        if (function_exists('add_global_user_modal')) {
            add_global_user_modal();
        }
    }
    ?>



        <?php
        if (isset($_SESSION["user_id"]) && $_SESSION["user_id"]) {
            ?>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
        <?php } ?>

        <?php if (isset($detect) && $detect->isMobile()) { ?>
            <style>
                #at-share-dock {
                    display: none !important;
                }
            </style>
        <?php } ?>

        <?php if (!$panel) { ?>
        <?php } ?>






        <?php  /* CSS */ ?>


        <?

        /* END CSS */


        /*FUENTES*/ ?>

        <?php

        /*?><link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600' rel='stylesheet' type='text/css'>*/
        ?>
        <link href='https://fonts.googleapis.com/css?family=Roboto:400,500,900&display=swap' rel='stylesheet'
            type='text/css'>
        <?php /*?>
           <link href="https://fonts.googleapis.com/css?family=Raleway:500,800" rel="stylesheet" property="stylesheet" type="text/css" media="all" />
           <link href='https://fonts.googleapis.com/css?family=Titillium+Web:400,200,300,700,600' rel='stylesheet' type='text/css'>
           <link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300' rel='stylesheet' type='text/css'>
           <link href='https://fonts.googleapis.com/css?family=Raleway:400,100' rel='stylesheet' type='text/css'>

           <?php
           */
        /* END FUENTES */ ?>





        <script rel="preload" type="text/javascript" src="/js/libs/jquery.min.js"></script>

        <script rel="preload" type="text/javascript" src="/js/libs/lazyload.min.js"></script>
        <script rel="preload" type="text/javascript" src="/js/libs/slick.min.js"></script>
        <script rel="preload" type="text/javascript" src="/js/libs/bootstrap.min.js"></script>



        <script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
        <?php

        /*?>
<script type="text/javascript" src="https://www.codigoamigo.com/js/merge_js_app/site_<?php require_once($_SERVER["DOCUMENT_ROOT"].'/js/merge_js_app/combine_js.php');?>.js"></script>

        <?*/

        $funciones_js_path = $_SERVER['DOCUMENT_ROOT'] . '/js/funciones.js';
        $funciones_js_version = file_exists($funciones_js_path) ? filemtime($funciones_js_path) : time();

        if ($force_css == 1) {
            ?>
            <script type="text/javascript" src="/js/funciones.js?v=<?php echo $funciones_js_version; ?>"></script>

            <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js?dd=<?php echo strtotime('now'); ?>"></script>

        <?php } else { ?>
            <script type="text/javascript" src="/js/funciones.js?v=<?php echo $funciones_js_version; ?>"></script>

            <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js"></script>
        <?php } ?>

        <!-- JavaScript para filtros móviles -->
        <script src="/js/mobile-filters.js"></script>
        
        <!-- JavaScript para sistema de favoritos -->
        <?php if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])): ?>
        <script src="/js/favoritos.js?v=<?php echo file_exists($_SERVER['DOCUMENT_ROOT'] . '/js/favoritos.js') ? filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/favoritos.js') : time(); ?>"></script>
        <?php endif; ?>

        <script type="text/javascript">

            function reordena() {
                $('.listado_codigos').masonry({
                    // options
                    itemSelector: '.pre_card',
                    horizontalOrder: true,
                    gutter: 0
                });
            }

            function reordena_marcas() {
                $('.marcas_home').masonry({
                    // options
                    gutter: 0
                });
            }

            $(document).ready(function () {


                <?php
                if (isset($_GET["codigo"]) && isset($u["pro_user"]) && $u["pro_user"] == 1) {
                    ?>
                    $(document).ready(function () {

                        let modalShown = false;

                        // Función para mostrar el modal
                        function showPremiumModal() {

                            if (!modalShown) {
                                $('#premiumUserModal').modal({
                                    backdrop: 'static',
                                    keyboard: false,
                                    show: true
                                });
                                modalShown = true;
                            }
                        }
                        // Mostrar después de 10 segundos
                        setTimeout(function () {
                            showPremiumModal();
                        }, 1000);

                        // Mostrar al llegar al final de la página
                        $(window).scroll(function () {
                            if (!modalShown &&
                                ($(window).scrollTop() + $(window).height() > $(document).height() - 100)) {
                                showPremiumModal();
                            }
                        });


                    });
                <?php
                }
                ?>

                setTimeout(function () {

                    lazyload();

                }, 1);


                <?php if ($muestra_popup == 1 && (!isset($_GET["continua_viendo"]) || !$_GET["continua_viendo"])) { ?>


                    const dialogoElement = document.getElementById("dialogo");
                    if (dialogoElement) {
                        dialogoElement.addEventListener("click", () => {
                        $.fancybox.open([
                            {
                                src: "#dialog-content",
                                type: "inline",
                                closeClick: false, // prevents closing when clicking INSIDE fancybox 
                                openEffect: 'none',
                                closeEffect: 'none',
                                opts: {
                                    modal: true,
                                },
                                clickSlide: false, // disable close on outside click
                                touch: false, // disable close on swipe
                                helpers: {
                                    overlay: { closeClick: false } // prevents closing when clicking OUTSIDE fancybox 
                                }
                            }
                        ]);
                    });
                    }

                    $('#dialog-content').on('mousedown', function (e) {
                        e.preventDefault();
                    });

                    setTimeout(function () {

                        jQuery('.dialogo').trigger('click');

                    }, 3500);


                    <?php

                    $_SESSION['mi_parametro_ttl'] = time() + 120;

                } ?>




                <?php if (!isset($_GET["codigo"])) { ?>
                    reordena();
                <?php } else { ?>



                    $(function () {
                        $('.info_pop').popover({
                            container: 'body'
                        })
                    })

                <?php } ?>




            });

        </script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        
        <style>
        /* Estilos responsive para la sección de Telegram */
        @media (max-width: 768px) {
            .telegram-help-section {
                padding: 20px 15px !important;
                margin-top: 30px !important;
            }
            
            .telegram-help-section h3 {
                font-size: 1.5rem !important;
            }
            
            .telegram-help-section p {
                font-size: 1rem !important;
            }
            
            .telegram-help-section a {
                padding: 12px 25px !important;
                font-size: 1rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .telegram-help-section {
                padding: 15px 10px !important;
            }
            
            .telegram-help-section h3 {
                font-size: 1.3rem !important;
            }
            
            .telegram-help-section p {
                font-size: 0.9rem !important;
            }
            
            .telegram-help-section a {
                padding: 10px 20px !important;
                font-size: 0.9rem !important;
            }
        }
        </style>

    <!-- Sección promocional de chollos -->
    <div class="footer-chollos-section">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div class="footer-chollos-content">
                <div class="footer-chollos-text">
                    <h3 class="footer-chollos-title">💰 Descubre los Chollos de CodigoAmigo</h3>
                    <p class="footer-chollos-description">Encuentra las mejores ofertas y descuentos exclusivos todos los días. ¡No te pierdas ninguna oportunidad!</p>
                </div>
                <div class="footer-chollos-actions">
                    <a href="/chollos" class="footer-chollos-btn footer-chollos-btn-primary">
                        <span>Ver Chollos</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" class="footer-chollos-btn footer-chollos-btn-telegram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.442-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.099.155.232.171.326.016.094.036.308.02.475z"/>
                        </svg>
                        <span>Telegram</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
    .footer-chollos-section {
        background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
        padding: 40px 20px;
        margin-top: 0;
    }
    
    .footer-chollos-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        flex-wrap: wrap;
    }
    
    .footer-chollos-text {
        flex: 1;
        min-width: 300px;
    }
    
    .footer-chollos-title {
        color: white;
        font-size: 1.8em;
        font-weight: 700;
        margin: 0 0 10px 0;
    }
    
    .footer-chollos-description {
        color: rgba(255, 255, 255, 0.95);
        font-size: 1.1em;
        margin: 0;
        line-height: 1.6;
    }
    
    .footer-chollos-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .footer-chollos-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1em;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .footer-chollos-btn-primary {
        background: white;
        color: #E30613;
    }
    
    .footer-chollos-btn-primary:hover {
        background: #f0f0f0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: #E30613;
        text-decoration: none;
    }
    
    .footer-chollos-btn-telegram {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid white;
    }
    
    .footer-chollos-btn-telegram:hover {
        background: white;
        color: #0088cc;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        text-decoration: none;
    }
    
    .footer-chollos-btn svg {
        width: 18px;
        height: 18px;
    }
    
    @media (max-width: 768px) {
        .footer-chollos-section {
            padding: 30px 15px;
        }
        
        .footer-chollos-content {
            flex-direction: column;
            text-align: center;
        }
        
        .footer-chollos-title {
            font-size: 1.5em;
        }
        
        .footer-chollos-description {
            font-size: 1em;
        }
        
        .footer-chollos-actions {
            width: 100%;
            justify-content: center;
        }
        
        .footer-chollos-btn {
            flex: 1;
            min-width: 140px;
        }
    }
    </style>

    <!-- Sección de ayuda con Telegram -->
    <div class="telegram-help-section" style="background: linear-gradient(135deg, #E30613, #C40510); padding: 30px 20px; margin-top: 40px; text-align: center;">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                <i class="fa-brands fa-telegram" style="font-size: 2.5rem; color: white;"></i>
                <div>
                    <h3 style="color: white; margin: 0; font-size: 1.8rem; font-weight: bold;">¿Necesitas ayuda?</h3>
                    <p style="color: white; margin: 5px 0 0 0; font-size: 1.2rem; opacity: 0.9;">¡Escríbenos!</p>
                </div>
            </div>
            <a href="https://t.me/spnfury" target="_blank" style="display: inline-block; background: white; color: #E30613; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                <i class="fa-brands fa-telegram" style="margin-right: 8px;"></i>
                Contactar por Telegram
            </a>
        </div>
    </div>

    </footer>
    <div class="fondo_oscuro hide"></div>



    <?php

    ?>


    <script type="text/javascript">

        <?php if (isset($_GET["nuevo_codigo"]) && $_GET["nuevo_codigo"] == 1) { ?>
            $("#modal_publicar_codigo").modal();
        <?php } ?>


    </script>

    <?php
    // Incluir funciones de AdSense si no están incluidas
    if (!function_exists('google_adsense')) {
        include_once __DIR__ . '/funciones_adsense.php';
    }
    
    // Verificar si se debe anular AdSense
    global $anula_adsense;
    if (isset($GLOBALS['anula_adsense'])) {
        $anula_adsense = $GLOBALS['anula_adsense'];
    }
    
    $request_uri = $_SERVER["REQUEST_URI"];
    if (strpos($request_uri, "registro") || strpos($request_uri, "nuevo_codigo") || strpos($request_uri, "mis-anuncios")) {
        // No mostrar publicidad en registro, nuevo código o mis-anuncios
    } else if ($show_adsense == 1 && !$panel && (!isset($anula_adsense) || !$anula_adsense)) {
        // Verificar que google_adsense exista antes de llamarlo
        if (function_exists('google_adsense')) {
            google_adsense();
        }
    }
    ?>


    <?php /*?>
       <!-- Start of Survicate (www.survicate.com) code -->
 <script type="text/javascript">
   (function (w) {
     var s = document.createElement('script');
     s.src = '//survey.survicate.com/workspaces/33b6b5608b3e50ac3fb5e5f910b9222a/web_surveys.js';
     s.async = true;
     var e = document.getElementsByTagName('script')[0];
     e.parentNode.insertBefore(s, e);
   })(window);
 </script>
<!-- End of Survicate code -->

<? */ ?>


    <!-- Sección promocional de chollos -->
    <div class="footer-chollos-section">
        <div class="container">
            <div class="footer-chollos-content">
                <div class="footer-chollos-text">
                    <h3 class="footer-chollos-title">💰 Descubre los Chollos de CodigoAmigo</h3>
                    <p class="footer-chollos-description">Encuentra las mejores ofertas y descuentos exclusivos todos los días. ¡No te pierdas ninguna oportunidad!</p>
                </div>
                <div class="footer-chollos-actions">
                    <a href="/chollos" class="footer-chollos-btn footer-chollos-btn-primary">
                        <span>Ver Chollos</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" class="footer-chollos-btn footer-chollos-btn-telegram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.015-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.442-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.099.155.232.171.326.016.094.036.308.02.475z"/>
                        </svg>
                        <span>Telegram</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
    .footer-chollos-section {
        background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
        padding: 40px 20px;
        margin-top: 0;
    }
    
    .footer-chollos-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        flex-wrap: wrap;
    }
    
    .footer-chollos-text {
        flex: 1;
        min-width: 300px;
    }
    
    .footer-chollos-title {
        color: white;
        font-size: 1.8em;
        font-weight: 700;
        margin: 0 0 10px 0;
    }
    
    .footer-chollos-description {
        color: rgba(255, 255, 255, 0.95);
        font-size: 1.1em;
        margin: 0;
        line-height: 1.6;
    }
    
    .footer-chollos-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .footer-chollos-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1em;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .footer-chollos-btn-primary {
        background: white;
        color: #E30613;
    }
    
    .footer-chollos-btn-primary:hover {
        background: #f0f0f0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: #E30613;
        text-decoration: none;
    }
    
    .footer-chollos-btn-telegram {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid white;
    }
    
    .footer-chollos-btn-telegram:hover {
        background: white;
        color: #0088cc;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        text-decoration: none;
    }
    
    .footer-chollos-btn svg {
        width: 18px;
        height: 18px;
    }
    
    @media (max-width: 768px) {
        .footer-chollos-section {
            padding: 30px 15px;
        }
        
        .footer-chollos-content {
            flex-direction: column;
            text-align: center;
        }
        
        .footer-chollos-title {
            font-size: 1.5em;
        }
        
        .footer-chollos-description {
            font-size: 1em;
        }
        
        .footer-chollos-actions {
            width: 100%;
            justify-content: center;
        }
        
        .footer-chollos-btn {
            flex: 1;
            min-width: 140px;
        }
    }
    </style>

    </body>

    </html>

<?php }


// Funciones auxiliares para modales - definidas antes de get_footer()
function modal_compartir() {
    ?>
    <div class="modal fade" id="modal_compartir" tabindex="-1" role="dialog" aria-labelledby="modalCompartirLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCompartirLabel">Compartir Código</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modal_compartir_body">
                    <!-- Contenido cargado vía AJAX -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p>Cargando opciones de compartir...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function modal_statistics() {
    ?>
    <div class="modal fade" id="modal_estadisticas" tabindex="-1" role="dialog" aria-labelledby="modalEstadisticasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEstadisticasLabel">Estadísticas del Código</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modal_estadisticas_body">
                    <!-- Contenido cargado vía AJAX -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p>Cargando estadísticas...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>