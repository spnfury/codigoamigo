<?php

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

        	<? if($title && strpos($title,"Descubre ") === false && !$anula_adsense){   ?>
            <script>
            // Prevenir carga duplicada del script de AdSense
            if (!document.querySelector('script[src*="adsbygoogle.js"]')) {
                var adsenseScript = document.createElement('script');
                adsenseScript.async = true;
                adsenseScript.setAttribute('data-ad-client', 'ca-pub-2091026230098067');
                adsenseScript.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
                adsenseScript.crossOrigin = 'anonymous';
                adsenseScript.onerror = function() {
                    console.warn('Error al cargar el script de AdSense');
                };
                document.head.appendChild(adsenseScript);
            }
            
            window.adsenseScriptLoaded = true;
            </script>
    <?php } ?>
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
    // Código del modal común...
}

function modal_for_login() {
    // Código del modal de login...
}

function modal_mobile() {
    // Código del modal móvil...
}
