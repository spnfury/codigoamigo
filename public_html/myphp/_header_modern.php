<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function get_header_modern($title = "", $description = "", $title_social = "", $description_social = "", $imagen_social = "", $links_meta = '') {
    
    global $author_web, $img_compartir_pagina, $ubicacion_actual, $force_css, $name_page;
    global $provincia, $data_usuario, $detect, $author_web, $datos_usuario, $que_es;
    global $noindex, $nombre_pag, $anula_adsense;
    
    ?><!DOCTYPE html>
    <html lang="es">
    <head>
        <title><?php echo $title; ?></title>
        <link rel="shortcut icon" href="/img/favicon_moneda_real.png">
        
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
            <meta name="canonical" content="<?php echo isset($GLOBALS["actual_url_limpia"]) ? $GLOBALS["actual_url_limpia"] : ''; ?>"/>
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

        <!-- CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="/css/design-fixed.css">
        
        <?php if($force_css==1){ ?>
            <!-- CSS adicional solo si es necesario -->
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css?a=<?php echo strtotime("now"); ?>">
        <?php }else{ ?>
            <!-- CSS adicional solo si es necesario -->
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css">
        <?php } ?>
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
        </style>
        
        <?php if($force_css){ ?>
            <link rel="stylesheet" href="<?php echo $force_css; ?>">
        <?php } ?>

        <!-- JavaScript -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="/js/jquery.easy-autocomplete.min.js"></script>
        <link rel="stylesheet" href="/css/easy-autocomplete.min.css?v=<?php echo time(); ?>">
        
        <?php 
        // Incluir funciones de AdSense
        include_once __DIR__ . '/funciones_adsense.php';
        
        // Mostrar código de AdSense en el header si corresponde
        if (should_show_adsense()) {
            echo get_adsense_header_code();
        }

        ?>
        
        <?php if(isset($links_meta["schema"])){ ?>
            <?php echo $links_meta["schema"]; ?>
        <?php } ?>
        
        <!-- Plausible Analytics -->
        <script defer data-domain="codigoamigo.com" src="https://plausible.miprimermvp.com/js/script.js"></script>
    </head>
    <body>
        <!-- HEADER MODERNO -->
        <header class="header-modern desktop-only">
            <div class="header-container">
                <a href="/" class="logo-section">
                    <div class="logo-text">
                        <span class="logo-codigo">codigo</span>
                        <span class="logo-amigo">amigo</span>
                    </div>
                    <div class="logo-tagline">códigos verificados, gente real</div>
                </a>
                
            <nav class="nav-modern">
                <!-- Menú desplegable de Marcas -->
                <div class="dropdown-modern">
                    <button class="nav-link dropdown-toggle" type="button">
                        Marcas <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu-modern">
                        <a href="<?php echo link_listado_marcas(); ?>" class="dropdown-item-modern">
                            <i class="fas fa-tags"></i> Todas las marcas
                        </a>
                        <?php
                        $lista_marcas = getListMarcaSpecial();
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
                    </div>
                </div>
                
                <!-- Menú desplegable de Categorías -->
                <div class="dropdown-modern">
                    <button class="nav-link dropdown-toggle" type="button">
                        Categorías <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu-modern">
                        <a href="<?php echo link_listado_categorias(); ?>" class="dropdown-item-modern">
                            <i class="fas fa-list"></i> Todas las categorías
                        </a>
                        <?php
                        $listacategorias = getCategorias();
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
            </nav>
                
                <div class="search-header">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="busqueda_marca" class="search-input-header" placeholder="Buscar códigos de descuento...">
                            <button class="search-btn" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Botón de Publicar Código -->
                    <button class="btn-publicar" id="btn-publicar">
                        <i class="fas fa-plus-circle"></i>
                        Publicar Código
                    </button>
                    
                    <!-- Botón de login (oculto cuando está logueado) -->
                    <button class="btn-acceder open_modal_login" id="btn-login">
                        <i class="fas fa-user"></i>
                        Acceder
                    </button>
                    
                    <!-- Menú de usuario (visible cuando está logueado) -->
                    <div class="user-menu" id="user-menu" style="display: none;">
                        <div class="user-profile" id="user-profile">
                            <img src="" alt="Avatar" class="user-avatar-small" id="user-avatar">
                            <span class="user-name-small" id="user-name">Usuario</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="/usuario" class="dropdown-item">
                                <i class="fas fa-user-edit"></i>
                                Editar perfil
                            </a>
                            <a href="/mis-anuncios" class="dropdown-item">
                                <i class="fas fa-list"></i>
                                Mis anuncios
                            </a>
                            <a href="/nuevo_codigo" class="dropdown-item">
                                <i class="fas fa-plus-circle"></i>
                                Publicar código
                            </a>
                            <?php if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) { ?>
                            <a href="<?php echo link_usuario($_SESSION["username"], $_SESSION["user_id"]); ?>" class="dropdown-item">
                                <i class="fas fa-user-circle"></i>
                                Mi perfil público
                            </a>
                            <?php } ?>
                            <hr class="dropdown-divider">
                            <a href="#" class="dropdown-item" id="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <?php
        // Verificar si las funciones móviles ya están incluidas
        if (!function_exists('add_mobile_header_compact')) {
            include_once __DIR__ . '/funciones_modern.php';
        }
        
        // Agregar header móvil compacto
        add_mobile_header_compact();
        ?>
        
       
            
            <!-- MENÚ DESPLEGABLE MÓVIL -->
            <div class="mobile-menu" id="mobile-menu" style="display: none;">
                <div class="mobile-menu-content">
                    <nav class="mobile-nav-links">
                        <a href="/destacados" class="mobile-nav-link">Destacados</a>
                        <a href="/nuevos" class="mobile-nav-link">Nuevos</a>
                        <a href="/populares" class="mobile-nav-link">Populares</a>
                        <a href="/categorias" class="mobile-nav-link">Categorías</a>
                        <a href="/marcas" class="mobile-nav-link">Marcas</a>
                        <a href="/nuevo_codigo" class="mobile-nav-link">Publicar Código</a>
                        <a href="#" class="mobile-nav-link open_modal_login">Iniciar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- HERO SECTION - Solo en la home -->
        <?php 
        $is_home = ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/index.php' || strpos($_SERVER['REQUEST_URI'], '/?') === 0);
        if ($is_home) { 
        ?>
        <section class="hero-section">
            <h1 class="hero-title">¡Encuentra los Mejores Descuentos!</h1>
            <p class="hero-description">Códigos de descuento verificados y actualizados diariamente para que ahorres en tus compras favoritas</p>
            
            <div class="search-hero">
                <input type="text" id="busqueda_marca2" class="search-input-hero" placeholder="Buscar códigos de descuento...">
                <button class="btn-buscar">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </div>
        </section>
        <?php } ?>

   
        <!-- Pasar datos de sesión del servidor al JavaScript -->
        <script>
        // Datos de sesión del servidor
        window.serverUserData = <?php 
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
        </script>
        
        <script>
        // JavaScript para funcionalidad moderna con autocompletado original
        $(document).ready(function() {
            // Manejar clic en botón Publicar Código
            $(document).on('click', '#btn-publicar', function(event) {
                event.preventDefault();
                console.log('Botón Publicar Código clickeado');
                
                // Verificar si el usuario está logueado
                if (window.serverUserData && window.serverUserData !== null) {
                    // Usuario logueado - redirigir directamente al formulario
                    window.location.href = '/nuevo_codigo';
                } else {
                    // Usuario no logueado - guardar URL de redirección y mostrar modal de login
                    localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                    console.log('URL de redirección guardada: /nuevo_codigo');
                    
                    // Cerrar cualquier modal abierto primero
                    $('.modal').modal('hide');
                    
                    // Abrir el modal de login después de un pequeño delay
                    setTimeout(function() {
                        $("#modal_login").modal('show');
                    }, 300);
                }
            });
            
            // Configurar autocompletado para ambos campos de búsqueda
            var url_d = "/datos.json?t=" + Date.now();
            
            var options_busqueda = {
                url: url_d,
                getValue: "nombre",
                template: {
                    type: "custom",
                    method: function(value, item) {
                        var ruta = "/de-" + item.clave;
                        
                        var codes = "";
                        if (item.codes == 1) {
                            codes = "1 código";
                        } else if (item.codes > 1) {
                            codes = item.codes + " códigos";
                        } else {
                            codes = "";
                        }
                        
                        var element = "<a style='color: black;' href='" + item.url + "'>";
                        element = element + "<div style='width: 80px;height: 40px;overflow: hidden;border-radius: 10px;display: inline-block;padding: 0px !important;'><img style='' src='" + item.imagen + "' /></div> ";
                        element = element + " <div style='margin-left: 3px; font-size: 18px; margin-bottom: 10px;display:inline-block;'>" + value + " (" + codes + ")</div>";
                        element = element + "</a>";
                        return element;
                    }
                },
                list: {
                    match: {
                        enabled: true
                    },
                    maxNumberOfElements: 20
                }
            };
            
            // Aplicar autocompletado a todos los campos de búsqueda
            $("#busqueda_marca, #busqueda_marca2, #busqueda_marca_mobile").easyAutocomplete(options_busqueda);
            
            // Botón de búsqueda del hero
            $('.btn-buscar').on('click', function() {
                var query = $('#busqueda_marca2').val();
                if(query) {
                    window.location.href = '/buscar/' + encodeURIComponent(query);
                }
            });
            
            // Botón de búsqueda del header
            $('.search-btn').on('click', function() {
                var query = $('#busqueda_marca').val();
                if(query) {
                    window.location.href = '/buscar/' + encodeURIComponent(query);
                }
            });
            
            // Enter en todos los campos de búsqueda
            $('#busqueda_marca, #busqueda_marca2, #busqueda_marca_mobile').on('keypress', function(e) {
                if(e.which == 13) {
                    var query = $(this).val();
                    if(query) {
                        window.location.href = '/buscar/' + encodeURIComponent(query);
                    }
                }
            });
            
            // Funcionalidad del menú móvil
            $('#mobile-menu-toggle').on('click', function() {
                $('#mobile-menu').slideToggle(300);
            });
            
            // Cerrar menú móvil al hacer clic en un enlace
            $('.mobile-nav-link').on('click', function() {
                $('#mobile-menu').slideUp(300);
            });
            
            // Cerrar menú móvil al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.header-modern.mobile-only').length) {
                    $('#mobile-menu').slideUp(300);
                }
            });
        });
        </script>
        
        <!-- Modal de Login -->
        <div class="modal fade" id="modal_login" role="dialog" style="">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <span class="modal-title" style="font-size:20px;">Inicia sesión para <b>ver y publicar códigos</b></span>
                    </div>
                    <div class="modal-body">
                        <form role="form" name="login" id="login" method="post">
                            <div class="form-group">
                                <input type="text" name="mail_login" id="mail_login" placeholder="Correo" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <input type="password" name="pass_login" id="pass_login" placeholder="Contraseña" class="form-control" required>
                            </div>
                            <p class="text">
                                <a title="Recuperar contraseña" class="enlace" href="cambiar_password">Recuperar contraseña</a>
                            </p>
                            <div class="form-group text-center">
                                <button class="btn btn_codigo_amigo" type="submit">Iniciar sesión</button>
                            </div>
                            <hr class="codigo">
                            <div class="text-center">
                                <div style="width:245px;text-align:center; margin: auto;">
                                    <div class="g_id_signin"
                                         data-type="standard"
                                         data-size="large"
                                         data-theme="filled_blue"
                                         data-text="sign_in_with"
                                         data-shape="rectangular"
                                         data-logo_alignment="left">
                                    </div>
                                </div>  
                            </div> 
                            <div class="text text-center" style="font-size: 14px;">
                                <br>¿Aún no te has registrado?
                                <a class="text-primary open_modal_registro" href="#" style="cursor: pointer;"><strong>Crea tu usuario</strong></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de Registro -->
        <div class="modal fade" id="modal_registro" role="dialog" style="">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header" style="background: #FF6B35; color: white; padding: 20px; font-size: 20px;">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <span class="modal-title" style="font-size:20px;">Crea tu cuenta para <b>publicar códigos</b></span>
                    </div>
                    <div class="modal-body">
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
                            <p class="help-block hide" id="text_ayuda_modal">La contraseña debe tener un mínimo de 8 caracteres</p>
                            <div class="form-group text-center">
                                <input style="width: 20px;" type="checkbox" id="acepto_modal" required /> 
                                <span style="font-size: 14px;">
                                    <span>Acepto</span> 
                                    <a href='politica-de-privacidad' target="_blank">la política de privacidad y protección de datos de <b>Código Amigo</b></a>
                                </span>
                            </div>
                            <div class="form-group text-center">
                                <button class="btn btn_codigo_amigo" type="submit" style="background: #FF6B35; border-color: #FF6B35;">Registrar usuario</button>
                            </div>
                            <hr class="codigo">
                            <div class="text-center">
                                <div style="width:245px;text-align:center; margin: auto;">
                                    <div class="g_id_signin"
                                         data-type="standard"
                                         data-size="large"
                                         data-theme="filled_blue"
                                         data-text="sign_up_with"
                                         data-shape="rectangular"
                                         data-logo_alignment="left">
                                    </div>
                                </div>  
                            </div>
                            <div class="text text-center" style="font-size: 14px;">
                                <br>¿Ya tienes cuenta?
                                <a class="text-primary open_modal_login" href="#" style="cursor: pointer;"><strong>Inicia sesión</strong></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de Activación de Usuario -->
        <div class="modal fade" id="modal_activacion" role="dialog" style="">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header" style="background: #FF6B35; color: white; padding: 20px; font-size: 20px;">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <span class="modal-title" style="font-size:20px;">
                            <i class="fas fa-envelope"></i> 
                            <strong>Activa tu cuenta</strong>
                        </span>
                    </div>
                    <div class="modal-body" style="padding: 30px; text-align: center;">
                        <div style="margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #FF6B35; margin-bottom: 15px;"></i>
                        </div>
                        <h4 style="color: #FF6B35; margin-bottom: 15px; font-weight: 600;">
    ¡Cuenta pendiente de activación!
</h4>
                        <p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 25px;">
                            Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.
                        </p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                <i class="fas fa-info-circle"></i> 
                                <strong>¿No recibiste el email?</strong> Revisa tu carpeta de spam o solicita un nuevo enlace de activación.
                            </p>
                        </div>
                        <div class="form-group text-center">
                            <button class="btn btn-primary" id="btn-reenviar-email" style="background: #FF6B35; border-color: #FF6B35; padding: 12px 30px; font-size: 16px; margin-right: 10px;">
                                <i class="fas fa-paper-plane"></i>
                                <span id="btn-text">Reenviar email de activación</span>
                                <span id="btn-countdown" style="display: none;">(60s)</span>
                            </button>
                            <button class="btn btn-secondary" data-dismiss="modal" style="padding: 12px 20px; font-size: 16px;">
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
        </div>
        
        <!-- Google OAuth -->
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <script>
        // Función callback para Google OAuth
        function googleLoginEndpoint(response) {
            // Google OAuth response recibida
            
            // Enviar el token a tu servidor
            $.ajax({
                type: "POST",
                url: "/api/login.php",
                data: {
                    metodo: "google_login",
                    credential: response.credential
                },
                cache: false,
                success: function(data) {
                    // Google login response recibida
                    
                    var responseText = data.trim();
                    
                    if (responseText === "success") {
                        // Login exitoso - redirigir
                        $("#modal_login").modal('hide');
                        var redirectUrl = localStorage.getItem('redirectAfterLogin');
                        if (redirectUrl && redirectUrl !== window.location.href) {
                            console.log('Redirigiendo a:', redirectUrl);
                            localStorage.removeItem('redirectAfterLogin');
                            window.location.href = redirectUrl;
                        } else {
                            location.reload();
                        }
                    } else if (responseText.includes('error')) {
                        alert("Error en el login con Google: " + responseText);
                    } else {
                        // Intentar parsear como JSON si contiene datos del usuario
                        try {
                            var userData = JSON.parse(responseText);
                            alert("Error en el login con Google: " + JSON.stringify(userData));
                        } catch (e) {
                            alert("Error en el login con Google: " + responseText);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Error en Google login
                    alert('Error al procesar el login con Google. Inténtalo de nuevo.');
                }
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
            // Manejar clic en botón de login
            $(document).on('click', '.open_modal_login', function(event) {
                event.preventDefault();
                console.log('Botón de login clickeado');
                
                // Verificar si hay una URL de redirección específica en el botón
                var redirectUrl = $(this).data('redirect-url');
                if (redirectUrl) {
                    // Si hay una URL específica (ej: /nuevo_codigo?marca=traderepublic), usarla
                    localStorage.setItem('redirectAfterLogin', redirectUrl);
                    console.log('URL específica guardada para redirección:', redirectUrl);
                } else {
                    // Si no hay URL específica, usar la URL actual
                    var currentUrl = window.location.href;
                    localStorage.setItem('redirectAfterLogin', currentUrl);
                    console.log('URL actual guardada para redirección:', currentUrl);
                }
                
                // Cerrar cualquier modal abierto primero
                $('.modal').modal('hide');
                
                // Abrir el modal de login después de un pequeño delay
                setTimeout(function() {
                    $("#modal_login").modal('show');
                }, 300);
            });
            
            // Variable para controlar si ya hay una petición en curso
            var loginInProgress = false;
            
            // Manejar envío del formulario de login
            $(document).on('submit', '#login', function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                // Prevenir múltiples envíos simultáneos
                if (loginInProgress) {
                    console.log('Login ya en progreso, ignorando envío');
                    return false;
                }
                
                console.log('Formulario de login enviado');
                loginInProgress = true;

                // Deshabilitar el botón para evitar múltiples envíos
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Iniciando sesión...');

                $.ajax({
                    type: "POST",
                    url: "/api/login.php",
                    data: {
                        metodo: "login_user",
                        mail: $("#mail_login").val(),
                        pass: $("#pass_login").val(),
                    },
                    cache: false,
                    timeout: 10000, // 10 segundos de timeout
                    success: function(data){
                        try {
                            var response = data.trim();
                            console.log('Respuesta del servidor:', response);

                            if(response == "no_trobat") {
                                alert("El usuario y contraseña introducidos no aparecen en nuestra base de datos");
                            } else if(response == "no_verificado") {
                                // Mostrar modal de activación en lugar de alert
                                if (typeof showActivationModal === 'function') {
                                    showActivationModal();
                                } else {
                                    alert("Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.");
                                }
                            } else {
                                // Login exitoso - guardar datos del usuario
                                try {
                                    var userData = JSON.parse(response);
                                    localStorage.setItem('userData', JSON.stringify(userData));
                                    if (typeof showUserMenu === 'function') {
                                        showUserMenu(userData);
                                    }
                                    $("#modal_login").modal('hide');
                                    
                                    // Redirigir a la URL guardada o recargar la página
                                    var redirectUrl = localStorage.getItem('redirectAfterLogin');
                                    if (redirectUrl && redirectUrl !== window.location.href) {
                                        console.log('Redirigiendo a:', redirectUrl);
                                        localStorage.removeItem('redirectAfterLogin');
                                        window.location.href = redirectUrl;
                                    } else {
                                        location.reload();
                                    }
                                } catch (e) {
                                    console.log('Error parseando JSON:', e);
                                    // Si no es JSON, asumir que es un mensaje de éxito
                                    var redirectUrl = localStorage.getItem('redirectAfterLogin');
                                    if (redirectUrl && redirectUrl !== window.location.href) {
                                        console.log('Redirigiendo a:', redirectUrl);
                                        localStorage.removeItem('redirectAfterLogin');
                                        window.location.href = redirectUrl;
                                    } else {
                                        location.reload();
                                    }
                                }
                            }
                        } catch (e) {
                            console.error('Error procesando respuesta:', e);
                            alert('Error al procesar la respuesta del servidor. Inténtalo de nuevo.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error en AJAX:', error);
                        alert('Error al procesar el login. Inténtalo de nuevo.');
                    },
                    complete: function() {
                        // Rehabilitar el botón en cualquier caso
                        $submitBtn.prop('disabled', false).text(originalText);
                        loginInProgress = false;
                    }
                });
            });
            
            // Funcionalidad del menú de usuario
            initializeUserMenu();
            
            // Manejar clic en botón de registro
            $(document).on('click', '.open_modal_registro', function(event) {
                event.preventDefault();
                console.log('Botón de registro clickeado');
                
                // Verificar si hay una URL de redirección específica en el botón
                var redirectUrl = $(this).data('redirect-url');
                if (redirectUrl) {
                    // Si hay una URL específica (ej: /nuevo_codigo?marca=traderepublic), usarla
                    localStorage.setItem('redirectAfterLogin', redirectUrl);
                    console.log('URL específica guardada para redirección:', redirectUrl);
                } else {
                    // Si no hay URL específica, usar la URL actual
                    var currentUrl = window.location.href;
                    localStorage.setItem('redirectAfterLogin', currentUrl);
                    console.log('URL actual guardada para redirección:', currentUrl);
                }
                
                $("#modal_registro").modal('show');
            });
            
            // Manejar envío del formulario de registro
            $(document).on('submit', '#registrar_usuario_modal', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Formulario de registro enviado');

                var nombre = $("#nombre_modal").val();
                var correo = $("#correo_modal").val();
                var password = $("#password_modal").val();
                var confirm_password = $("#confirm_password_modal").val();
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
                            origin: "web",
                        }, 
                        cache: false,
                        success: function(data){
                            if(data == "trobat") { 
                                alert("Este correo ya existe. Prueba con otro !!!");
                                $("#correo_modal").focus(); 
                            } else {
                                // Registro exitoso - cerrar modal y mostrar mensaje
                                $("#modal_registro").modal('hide');
                                alert("¡Registro exitoso! Ahora puedes iniciar sesión.");
                                
                                // Abrir modal de login
                                $("#modal_login").modal('show');
                                
                                // Limpiar formulario de registro
                                $("#registrar_usuario_modal")[0].reset();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Error en AJAX:', error);
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
        
        // Función para verificar el estado de login
        function checkLoginStatus() {
            // Primero verificar si hay datos del servidor
            if (window.serverUserData && window.serverUserData !== null) {
                // Usuario logueado desde servidor
                // Guardar en localStorage para consistencia
                localStorage.setItem('userData', JSON.stringify(window.serverUserData));
                showUserMenu(window.serverUserData);
                return;
            }
            
            // Si no hay datos del servidor, verificar localStorage
            var userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
            
            if (userData) {
                try {
                    var user = JSON.parse(userData);
                    showUserMenu(user);
                } catch (e) {
                    // Error parsing user data
                    showLoginButton();
                }
            } else {
                showLoginButton();
            }
        }
        
        // Función para mostrar el menú de usuario
        function showUserMenu(user) {
            $('#btn-login').hide();
            $('#btn-publicar').hide();
            $('#user-menu').show();
            
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
        
        // Función para cerrar sesión
        function logout() {
            // Limpiar datos de usuario
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            
            // Hacer petición al servidor para cerrar sesión
            $.ajax({
                type: "POST",
                url: "/logout",
                success: function() {
                    showLoginButton();
                    location.reload();
                },
                error: function() {
                    // Aún así mostrar el botón de login
                    showLoginButton();
                    location.reload();
                }
            });
        }
        
        // Función para obtener iniciales
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().substring(0, 2);
        }
        
        // Función para crear SVG de avatar con iniciales
        function createAvatarSVG(initials) {
            return `<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" fill="#FF6B35" rx="16"/>
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
                console.error('Error al copiar: ', err);
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
        $("#modal_activacion").modal('show');
        
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
                var response = data.trim();
                
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
    // Agregar JavaScript móvil
    add_mobile_javascript();
    ?>
</body>
</html>
    <?php
    }
    
    // Alias para compatibilidad con código existente
    function get_header_new($title = "", $description = "", $title_social = "", $description_social = "", $imagen_social = "", $links_meta = '') {
        return get_header_modern($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
    }
    ?>
