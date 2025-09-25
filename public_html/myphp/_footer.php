<?php


// if (session_status() === PHP_SESSION_ACTIVE) {
//     echo 'La sesión está activa.';
// } else {
//     echo 'La sesión no está activa.';
// }
//$_SESSION['mi_parametro_ttl'] = 1;

function get_footer()
{

  
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

        <!-- Botón flotante de Telegram mejorado -->
        <div class="telegram-float-new">
            <a href="https://t.me/spnfury" target="_blank" class="telegram-btn">
                <div class="telegram-icon-new">
                    <i class="fab fa-telegram-plane"></i>
                </div>
                <div class="telegram-text">
                    <span class="telegram-main-text">¿Necesitas ayuda?</span>
                    <span class="telegram-sub-text">¡Escríbenos!</span>
                </div>
            </a>
        </div>

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
    ?>


    <footer class="footer">
        <!-- Footer moderno inspirado en Chollometro -->
        <div class="footer-modern">
            <!-- Sección superior con bienvenida y redes sociales -->
            <div class="footer-top">
                <div class="container">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="welcome-section">
                                <h2 class="welcome-title">¡Bienvenidos a la comunidad de ahorradores de España! 👋</h2>
                                <p class="welcome-description">
                                    Más de <strong class="highlight-number">50.000 personas</strong> como tú, se han unido ya a nuestra comunidad. 
                                    Entre todos hemos compartido más de <strong class="highlight-number">10.000 códigos verificados</strong> 
                                    y publicado en ellos más de <strong class="highlight-number">100.000 comentarios</strong>. 
                                    Juntos compartimos nuestra experiencia, consejos y recomendaciones para nuestras compras.
                                </p>
                                <div class="user-avatars">
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                    <div class="avatar"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="social-section">
                                <h3 class="social-title">Síguenos</h3>
                                <div class="social-icons">
                                    <a href="https://t.me/codigoamigocom" target="_blank" class="social-icon">
                                        <i class="fab fa-telegram"></i>
                                    </a>
                                    <a href="https://www.facebook.com/codigoamigoweb" target="_blank" class="social-icon">
                                        <i class="fab fa-facebook"></i>
                                    </a>
                                    <a href="https://twitter.com/codigoamigoweb" target="_blank" class="social-icon">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="https://www.instagram.com/codigoamigoweb/" target="_blank" class="social-icon">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección En redes -->
            <div class="footer-social-section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="social-main-section">
                                <h2 class="social-main-title">En redes</h2>
                                <div class="social-main-icons">
                                    <a href="https://www.facebook.com/codigoamigoweb" target="_blank" class="social-main-icon">
                                        <i class="fab fa-facebook"></i>
                                        <span>Facebook</span>
                                    </a>
                                    <a href="https://twitter.com/codigoamigoweb" target="_blank" class="social-main-icon">
                                        <i class="fab fa-twitter"></i>
                                        <span>Twitter</span>
                                    </a>
                                    <a href="https://www.instagram.com/codigoamigoweb/" target="_blank" class="social-main-icon">
                                        <i class="fab fa-instagram"></i>
                                        <span>Instagram</span>
                                    </a>
                                    <a href="https://t.me/codigoamigocom" target="_blank" class="social-main-icon">
                                        <i class="fab fa-telegram"></i>
                                        <span>Telegram</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección principal con columnas -->
            <div class="footer-main">
                <div class="container">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="footer-column">
                                <h4 class="column-title">Empresa</h4>
                                <ul class="footer-links">
                                    <li><a href="nosotros" class="footer-link">Nosotros</a></li>
                                    <li><a href="equipo" class="footer-link">Conoce al equipo</a></li>
                                    <li><a href="empleos" class="footer-link">Empleos</a></li>
                                    <li><a href="partners" class="footer-link">Hazte nuestro Partner</a></li>
                                    <li><a href="contacto" class="footer-link">Contacto</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="footer-column">
                                <h4 class="column-title">Comunidad</h4>
                                <ul class="footer-links">
                                    <li><a href="como-funciona" class="footer-link">Cómo funciona Código Amigo</a></li>
                                    <li><a href="normas" class="footer-link">Normas Comunitarias</a></li>
                                    <li><a href="registro" class="footer-link">Únete a nosotros</a></li>
                                    <li><a href="https://codigoamigo.canny.io/sugerencias" class="footer-link">Sugerencias</a></li>
                                    <li><a href="club" class="footer-link">Club</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="footer-column">
                                <h4 class="column-title">Legal</h4>
                                <ul class="footer-links">
                                    <li><a href="politica-de-privacidad" class="footer-link">Política de privacidad</a></li>
                                    <li><a href="politica-de-cookies" class="footer-link">Política sobre cookies</a></li>
                                    <li><a href="reglas-publicacion" class="footer-link">Reglas de publicación</a></li>
                                    <li><a href="aviso-legal" class="footer-link">Aviso legal y condiciones</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="footer-column">
                                <h4 class="column-title">Descarga nuestra app</h4>
                                <div class="app-downloads">
                                    <div class="app-store-badge">
                                        <div class="store-icon">
                                            <i class="fab fa-apple"></i>
                                        </div>
                                        <div class="store-text">
                                            <div class="store-name">Consíguelo en el App Store</div>
                                            <div class="store-rating">4.8 ★★★★☆</div>
                                            <div class="store-reviews">1.200+ Valoraciones</div>
                                        </div>
                                    </div>
                                    <div class="app-store-badge">
                                        <div class="store-icon">
                                            <i class="fab fa-google-play"></i>
                                        </div>
                                        <div class="store-text">
                                            <div class="store-name">DESCARGAR EN Google Play</div>
                                            <div class="store-rating">4.7 ★★★★☆</div>
                                            <div class="store-reviews">850+ Valoraciones</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="browser-extension">
                                    <p class="extension-text">El asistente de compras de Código Amigo busca ofertas y códigos de descuento por ti.</p>
                                    <a href="#" class="extension-button">
                                        Instalar ahora
                                        <i class="fab fa-chrome"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección inferior -->
            <div class="footer-bottom">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="language-selector">
                                <div class="language-dropdown">
                                    <i class="fas fa-flag"></i>
                                    <span>Código Amigo</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="security-info">
                                <h5 class="security-title">Tus datos están seguros con nosotros.</h5>
                                <div class="security-badges">
                                    <div class="security-badge">
                                        <i class="fas fa-lock"></i>
                                        <span>Cifrado SSL de 256 bits</span>
                                    </div>
                                    <div class="security-badge">
                                        <i class="fas fa-check"></i>
                                        <span>De conformidad con el RGPD</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="copyright-section">
                                <div class="copyright-left">
                                    <span class="company-info">Parte de Código Amigo</span>
                                    <div class="copyright-text">Copyright © 2017-2025 Código Amigo. Todos los derechos reservados.</div>
                                </div>
                                <div class="copyright-right">
                                    <p class="impartiality-text">
                                        Código Amigo es imparcial - Si visitas o realizas un pedido a través de nuestra web, 
                                        podríamos recibir una compensación del comerciante. Pero solo la comunidad vota y decide 
                                        la temperatura de las ofertas, independientemente de cualquier compensación.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        if ($force_css == 1) {
            ?>
            <script type="text/javascript" src="/js/funciones.js?v=<?php echo time(); ?>"></script>

            <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js?dd=<?php echo strtotime('now'); ?>"></script>

        <?php } else { ?>
            <script type="text/javascript" src="/js/funciones.js?v=<?php echo time(); ?>"></script>

            <script type="text/javascript" src="/js/jquery.easy-autocomplete.min.js"></script>
        <?php } ?>

        <!-- JavaScript para filtros móviles -->
        <script src="/js/mobile-filters.js"></script>

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

        <link href=https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css rel="stylesheet">

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
    
    $request_uri = $_SERVER["REQUEST_URI"];
    if (strpos($request_uri, "registro") || strpos($request_uri, "nuevo_codigo")) {
        // No mostrar publicidad en registro ni en nuevo código
    } else if ($show_adsense == 1 && !$panel) {
        google_adsense();
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


    </body>

    </html>

<?php }



//print_r($_SESSION);

?>