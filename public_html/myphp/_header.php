<?php
require_once __DIR__ . '/../inc/sentry_bootstrap.php';

function get_header_slim($title = "", $description = "", $title_social = "", $description_social = "", $imagen_social = "", $links_meta = '' ) {
    
    global $author_web, $img_compartir_pagina, $ubicacion_actual,$force_css,$name_page;
    
    global $provincia;
    
    global $data_usuario, $detect,$author_web,$datos_usuario,$que_es;
    
    global $noindex,$nombre_pag;

    global $anula_adsense;
    
    ?><!DOCTYPE html>
        <html lang="es">
            <head>
            	<title><?php echo $title; ?></title>
                <link rel="shortcut icon" href="/img/favicon_moneda_real.png">

                <?php
                // Logic for noindex: block dev, explicit noindex, users, parameters, etc.
                // We removed the restrictive check for "/public/" as many friendly URLs load files from there.
                if(strpos($_SERVER['SERVER_NAME'],"dev.") !==false || $que_es==1 || $noindex == 1 || (isset($datos_usuario) && isset($datos_usuario["username"])) || isset($_GET["codigo"]) || isset($_GET["page"]) || isset($provincia) || (isset($args) && isset($args["mes"]))){ ?>
                	<meta name="robots" content="noindex" />
                <?php }else{ ?>
                	<meta name="robots" content="index,follow" />
                <?php } ?>

                <meta name="author" content="<?php echo $author_web; ?>">
                <meta name="description" content="<?php echo $description; ?>">
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">

                <?php if(!isset($_GET["page"])){ 
                    // Force canonical to always use www. for consistency
                    $canonical_url = isset($GLOBALS["actual_url_limpia"]) ? $GLOBALS["actual_url_limpia"] : '';
                    $canonical_url = str_replace("https://codigoamigo.com", "https://www.codigoamigo.com", $canonical_url);
                ?>
                    <meta name="canonical" content="<?php echo $canonical_url; ?>"/>
                <?php } ?>

                <meta name="google-site-verification" content="nKq2RB4r_Up2pP8rZfsCEuesPABSQrCI2mzIPY4TlCk"/>
                <meta name="google-signin-client_id" content="673081636054-kiqrarvsg1rbt9bpn9h07kafo2dja90s.apps.googleusercontent.com">
                <?php if(isset($data_usuario) && isset($data_usuario["username"])){ ?>
                <link href="<?php echo $data_usuario["username"]; ?>" rel="publisher" />
                <?php } ?>
				<link rel="image_src" href="<?php echo $imagen_social; ?>"/>

                <!-- Facebook & Twitter -->
                <meta property="fb:app_id" content="389215518631590">
                <?php if(isset($title_social) && $title_social){ ?>
                    <meta property="og:title" content="<?php echo htmlspecialchars($title_social) ?>">
                    <meta property="og:image" content="<?php echo htmlspecialchars($imagen_social ?? '') ?>">
                    <meta property="og:image:width" content="1200">
                    <meta property="og:image:height" content="630">
                    <meta property="og:description" content="<?php echo htmlspecialchars($description_social ?? '') ?>">
                    <meta property="og:site_name" content="www.codigoamigo.com">
                    <meta property="og:url" content="<?php echo isset($GLOBALS["actual_url"]) ? $GLOBALS["actual_url"] : ''; ?>">
                    <meta property="og:type" content="product">
                    <meta name="twitter:card" content="summary_large_image">
                    <meta name="twitter:site" content="<?php echo isset($GLOBALS["site_twitter"]) ? $GLOBALS["site_twitter"] : ''; ?>">
                    <meta name="twitter:creator" content="<?php echo $author_web; ?>">
                    <meta name="twitter:title" content="<?php echo htmlspecialchars($title_social) ?>">
                    <meta name="twitter:description" content="<?php echo htmlspecialchars($description_social ?? '') ?>">
                    <meta name="twitter:image" content="<?php echo htmlspecialchars($imagen_social ?? '') ?>">
                <?php } ?>

                <script>
					var url = '<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>';
                </script>

<link rel="stylesheet" type="text/css" href="/css/libs/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" href="/css/main.css">
            <link rel="stylesheet" type="text/css" href="/css/template/style.css">
        	<link rel="stylesheet" type="text/css" href="/css/template/header-1.css">
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css">
            <link rel="stylesheet" type="text/css" href="/css/easy-autocomplete.themes.min.css">
            <link rel="stylesheet" type="text/css" href="/css/easy-autocomplete.min.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" type="text/css" href="/css/mobile-optimization.css">
            <link rel="stylesheet" type="text/css" href="/css/libs/slick.min.css">
			<link rel="stylesheet" type="text/css" href="/css/slick-theme.css">            



        <?php
        if (function_exists('codigoamigo_get_sentry_browser_snippet')) {
            echo codigoamigo_get_sentry_browser_snippet();
        }
        ?>
</head>    
            <body id="<? echo $name_page; ?>">

            	<?php
            	    // Usar variable global $detect
            	    $detect = isset($GLOBALS['detect']) ? $GLOBALS['detect'] : (class_exists('Mobile_Detect') ? new Mobile_Detect() : null);
            	    if ($detect->isMobile()) { menu_mobile(); } else { menu_laptop(); }

            	    modal_for_login();
         ?>

<?php }

function menu_laptop() {
    // Código del menú laptop existente...
    echo "<!-- Menú laptop -->";
}

function menu_mobile() {
    // Incluir el nuevo header móvil completamente rediseñado
    include_once __DIR__ . '/_header_mobile_new.php';
}

function modal_comun() {
    // paste google client ID and client secret keys
    $google_oauth_client_id = "298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com";
    $google_oauth_client_secret = "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";
    ?>
     
    <!-- check if the user is not logged in -->
    <?php if (empty($_SESSION["user_id"])): ?>
    
        <!-- display the login prompt -->
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <div id="g_id_onload"
            data-client_id="<?php echo $google_oauth_client_id; ?>"
            data-context="signin"
            data-callback="googleLoginEndpoint"
            data-close_on_tap_outside="false"
            data-auto_prompt="false">
        </div>
         
    <?php endif; 
}

function modal_for_login() {
    ?><div class="modal fade" id="modal_login" role="dialog" style="">
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
                        <input  type="password" name="pass_login" id="pass_login" placeholder="Contraseña" class="form-control" required>
                    </div>
                    <p class="text">
                        <a title="Recuperar contraseña" class="enlace" href="cambiar_password">Recuperar contraseña</a>
                    </p>
                    <div class="form-group text-center">
                        <button class="btn btn_codigo_amigo" type="submit">Iniciar sesión</button>
                    </div>
                    <hr class="codigo">
                	<div class="text-center">
                		<p style="font-size: 20px;"> O inicia sesión con Facebook </p>
                	</div>
                    <div class="text text-center" style="font-size: 14px;">
                        <br>¿Aún no te has registrado?
                        <a class="text-primary" href="registro"><strong>Crea tu usuario</strong></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php }

function modal_mobile() {
    ?>
<div class="modal fade text-center" id="modal_login_mobile" role="dialog">
    <div class="modal-dialog modal-md" style="left: 5%; right: 5%; top: 5%;">
        <div class="modal-content">
            <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <span class="modal-title">Inicia sesión para <b>ver y publicar códigos</b></span>
            </div>
            <div class="modal-body">
                <form role="form" name="login" id="login" method="post">
                    <div class="form-group">
                        <input type="text" name="mail_login" id="mail_login" placeholder="Correo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <input  type="password" name="pass_login" id="pass_login" placeholder="Contraseña" class="form-control" required>
                    </div>
                    <p class="text">
                        <a title="Recuperar contraseña" class="enlace" href="cambiar_password">Recuperar contraseña</a>
                    </p>
                    <div class="form-group text-center">
                        <button class="btn btn_codigo_amigo" type="submit">Iniciar sesión</button>
                    </div>
                    <hr class="codigo">
                	<div class="text-center">
                		<p style="font-size: 20px;"> O inicia sesión con Facebook </p>
                	</div>
                    <div class="text text-center" style="font-size: 14px;">
                        <br>¿Aún no te has registrado?
                        <a class="text-primary" href="registro"><strong>Crea tu usuario</strong></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php }

