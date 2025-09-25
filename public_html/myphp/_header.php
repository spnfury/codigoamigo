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

                <?php /*if($links_meta["prev"] && !$_GET["codigo"]){ ?>
                    <link rel="prev" href="<?php echo $links_meta["prev"]; ?>"/>
                <?php }

                if($links_meta["next"] && !$_GET["codigo"]){ ?>
                    <link rel="next" href="<?php echo $links_meta["next"]; ?>"/>
                <?php }*/  ?>

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
                <?php }



                if(strpos($_SERVER['SERVER_NAME'],"dev.") !==false){

                    $force_css = "1";

                }


?>

                <script>
					var url = '<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>';
                </script>


                <!-- Analytics -->
                <script>
              		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
                    ga('create', 'UA-87257539-2', 'auto');
                    ga('send', 'pageview');
            	</script>
            	
            	<!-- Global site tag (gtag.js) - Google Analytics -->
                <script async src="https://www.googletagmanager.com/gtag/js?id=G-X6M39QD3HJ"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag('js', new Date());
                
                  gtag('config', 'G-X6M39QD3HJ');
                </script>
                
            <script defer data-domain="codigoamigo.com" src="https://plausible.miprimermvp.com/js/script.js"></script>
                
                
          



        	<?php if(isset($_SESSION["user_id"]) && $nombre_pag != 'usuario_sin_marcas'){ ?>

                <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
                <script src="https://checkout.stripe.com/checkout.js"></script>

            <?php } ?>

            ?>

<link rel="stylesheet" type="text/css" href="/css/libs/bootstrap.min.css">


<?php if($force_css==1){ ?>

            <link rel="stylesheet" type="text/css" href="/css/main.css?a=<?php echo strtotime('now');?>">
            <link rel="stylesheet" type="text/css" href="/css/template/style.css?c=<?php echo strtotime('now');?>">
        	<link rel="stylesheet" type="text/css" href="/css/template/header-1.css?a=<?php echo strtotime("now"); ?>">
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css?a=<?php echo strtotime("now"); ?>">

<?php }else{ ?>

            <link rel="stylesheet" type="text/css" href="/css/main.css">
            <link rel="stylesheet" type="text/css" href="/css/template/style.css">
        	<link rel="stylesheet" type="text/css" href="/css/template/header-1.css">
            <link rel="stylesheet" type="text/css" href="/css/template/footer-1.css">

<?php } ?>



            <link rel="stylesheet" type="text/css" href="/css/easy-autocomplete.themes.min.css">
            <link rel="stylesheet" type="text/css" href="/css/easy-autocomplete.min.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" type="text/css" href="/css/mobile-optimization.css">

            <link rel="stylesheet" type="text/css" href="/css/libs/slick.min.css">
			<link rel="stylesheet" type="text/css" href="/css/slick-theme.css">            

          

<? if($title && strpos($title,"Descubre ") === false && !$anula_adsense){   ?>
    <script async data-ad-client="ca-pub-2091026230098067" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js" crossorigin="anonymous"></script>
    
    <script>
if (window.location.pathname !== "/destaca") {
    
    (adsbygoogle = window.adsbygoogle || []).push({});
}
</script>
    
<?php } ?>
</script>

</head>    
            <body id="<? echo $name_page; ?>">


            	<?php


            	    // Usar variable global $detect
            	    $detect = isset($GLOBALS['detect']) ? $GLOBALS['detect'] : new Mobile_Detect();
            	    if ($detect->isMobile()) { menu_mobile(); } else { menu_laptop(); }

            	    modal_for_login();
         ?>



<?php }

function menu_laptop() {

    /*echo ini_get("session.cookie_lifetime");
    echo "<br>";
    echo ini_get("session.gc_maxlifetime");
    echo "<br>";
    session_start();
    print_r($_SESSION);*/


    ?>

<div class="container-fluid  <?php if(strpos($_SERVER['SERVER_NAME'],"dev.") !==false){ echo "dev"; } ?>" id="cabecera_top">


    <div id="cabecera_real">
    <div class="container" >
    	<div class="row">
    		<div class="col-md-2 text-left logo" >
    			<a href="/" title="Códigos de amigo"><img src="/img/logo_codigoamigo_real4.png" alt="Logo código amigo" /></a>
    		</div>
    		<div class="col-md-3"  style="margin-top: 1%;">

                	<input type='text' class='form-control' required placeholder='Busca tu código de descuento' name='busqueda_marca2' id='busqueda_marca2' value='<?php echo isset($_REQUEST["busqueda_marca"]) ? $_REQUEST["busqueda_marca"] : ''; ?>'>

    		</div>
    		<div class="col-md-7 text-right" style="margin-top: 0%;">


    			<div class="dropdown" style="display: inline-block;">
      				<button class="dropdown-toggle" type="button" data-toggle="dropdown" style="background: none !important;">
      					<span>Nuestras marcas</span>
      					<span class="caret"></span>
    				</button>
      				<ul class="dropdown-menu" style="font-size: 15px; min-width: 200px;">
        				<li><a title="marcas con códigos amigo" href="<?php echo link_listado_marcas(); ?>"><b>Todas las marcas</b></a></li>
                        <?php


                        $lista_marcas = getListMarcaSpecial();
                        //$lista_marcas = agregacionesMarcasByCategorias();
                        if($lista_marcas){
                            foreach($lista_marcas as $marca) {
                                // Add null coalescing operators to safely access array keys
                                $codes = $marca["count"] ?? 0;
                                $nombre = $marca["nombre"] ?? '';
                                $nombre_clave = $marca["nombre_clave"] ?? '';
                                $imagen = $marca["imagen"] ?? '';

                                if($nombre){
                                ?>
                                <li>
                                	<a title="Cupones descuento para <?php echo $nombre; ?>" href="<?php echo link_marca($nombre_clave); ?>">
                                		<img alt="cupones <?php echo $nombre; ?>" loading="lazy" style="width: 20px;" src="<?php echo $imagen;?>" />
                                		<span style="margin-left: 10px;"><?php echo $nombre ?> (<?php echo $codes; ?>)</span>
                                	</a>
                            	</li>
                            <?php
                                }

                                }
                        }
                        ?>
      				</ul>
    			</div>
    			<div class="dropdown" style="display: inline-block;">
      				<button class="dropdown-toggle" type="button" data-toggle="dropdown" style="background: none !important;">
      					<span>Nuestras categorías</span>
      					<span class="caret"></span>
    				</button>
      				<ul class="dropdown-menu" style="font-size: 15px; min-width: 200px;">
        				<li><a href="<?php echo link_listado_categorias(); ?>"><b>Todas las categorías</b></a></li>
                        <?php

                        $listacategorias = getCategorias();

                        foreach($listacategorias as $categoria) { ?>
                            <li>
                            	<a title="<?php echo $categoria["descripcion"]; ?>" href="<?php echo link_categoria($categoria["nombre_clave"]); ?>">
                            		<i class="<?php echo $categoria["icon"]; ?>" aria-hidden="true"></i>
                            		<span style="margin-left: 10px;"><?php echo $categoria["nombre"] ?></span>
                            	</a>
                        	</li>
                        <?php } ?>
      				</ul>
    			</div>
    			<?php if(!empty($_SESSION["user_id"])) { ?>
    				<?php
    				    global $data_usuario;
    				?>
    				<a class="btn btn-custom" title="Publicar nuevo código" href="nuevo_codigo">+ Publicar Código</a>
    				<div class="dropdown dropdown-usuario" style="display: inline-block;">
      				<button class="btn dropdown-toggle" type="button" data-toggle="dropdown" style="background: none !important;">
      					<a href="<?php echo link_usuario($data_usuario["username"] ?? '', (string)($data_usuario["id_string"] ?? ''));?>"><img style="width: 40px;" src="<?php echo $data_usuario["img"] ?? ''; ?>" />
      					<span class="user_span"><?php echo $data_usuario["username"] ?? 'Usuario'; ?></span>
      					<span class="caret" style="position: relative; bottom: 8px;"></span></a>
    				</button>
      				<ul class="dropdown-menu">
        				<li><a href="usuario">Editar datos</a>
        				<li><a href="<?php echo link_usuario_nuevas_marcas($data_usuario["username"] ?? '', (string)($data_usuario["id_string"] ?? ''));?>">Descubrir nuevas marcas</a>
                        <li><a href="<?php echo link_usuario($data_usuario["username"] ?? '', (string)($data_usuario["id_string"] ?? ''));?>">Mis códigos</a>
                        <li><a href="/logout" onclick="signOut();">Cerrar sesión</a>
      				</ul>
    			</div>
    			<?php } else { ?>
    			
    			<div class="dropdown" style="display: inline-block;">
      				<button class="dropdown-toggle" type="button" data-toggle="dropdown" style="background: none !important;">
      					<span>Login</span>
      					<span class="caret"></span>
    				</button>
      				<ul class="dropdown-menu" style="font-size: 15px; min-width: 200px;color:black !important;">
        				<li><a class="btn-mini login open_modal_login"><i class="fa fa-user"></i> Iniciar sesión</a></li>
        				<li>
                        <hr style="border: 1px grey black;"><a href="https://t.me/codigoamigocom" target="_blank" class="telegram-link" style="
                                display: flex;
                                align-items: center;
                                background: #0088cc;
                                color: white !important;
                                padding: 10px 15px;
                                border-radius: 5px;
                                margin: 5px 10px;
                                text-decoration: none;
                                transition: background 0.3s ease;">
                                <i class="fab fa-telegram" style="margin-right: 10px; font-size: 20px;"></i>
                                Únete a nuestro Canal de Telegram
                                <i class="fas fa-arrow-right" style="margin-left: auto;"></i>
                            </a>
                        </li>
      				</ul>
    			</div>
    				<a class="btn btn-custom open_modal_login_mobile" title="Publicar nuevo código">+ Publicar Código</a>

    			<?php } ?>
    		</div>
    	</div>
    </div>

    </div>
    </div>

</div>

<?php

modal_comun();
}

function menu_mobile() { ?>
    <?php global $data_usuario,$nombre_pag; ?>

    <div class="container <?php if(strpos($_SERVER['SERVER_NAME'],"dev") !==false){ echo "dev"; } ?>"  id="cabecera_top" >
        <div class="cabecera_mov" style="background:#8061d0 !important;height:60px;">
            
            <!-- Columna del menú hamburguesa -->
            <div class="col-xs-2 text-left">
                <style>
                    .menu {
                        display: none;
                        flex-direction: column;
                        width: 100%;
                        background-color: #fff;
                        position: fixed;
                        top: 60px;
                        left: 0;
                        z-index: 9999;
                        min-width: 200px;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                        height: calc(100vh - 60px);
                        overflow-y: auto;
                    }
                    .menu a {
                        padding: 15px;
                        text-decoration: none;
                        color: #333;
                        text-align: left;
                        border-bottom: 1px solid #eee;
                        font-size: 16px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .menu a i {
                        margin-right: 10px;
                        width: 20px;
                        text-align: center;
                    }
                    
                    .menu span {
                        padding: 15px;
                        text-align: center;
                        font-weight: bold;
                        background: #8061d0;
                        color: white;
                    }
                    
                    .menu a:hover {
                        background-color: #f5f5f5;
                    }
                    
                    /* Ajustes del botón hamburguesa */
                    .hamburger {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        cursor: pointer;
                        padding: 10px 0px !important;
                        background: none;
                        border: none;
                        height: 60px;
                        width: 40px;
                        position: relative;
                    }
                    
                    .hamburger div {
                        width: 25px;
                        height: 3px;
                        background-color: white;
                        margin: 3px 0;
                        transition: all 0.3s ease;
                        border-radius: 2px;
                    }
                    
                    .hamburger.open div:nth-child(1) {
                        transform: rotate(45deg) translate(5px, 5px);
                    }
                    
                    .hamburger.open div:nth-child(2) {
                        opacity: 0;
                    }
                    
                    .hamburger.open div:nth-child(3) {
                        transform: rotate(-45deg) translate(7px, -6px);
                    }
                </style>

                <div class="hamburger" onclick="toggleMenu()">
                    <div></div>
                    <div></div>
                    <div></div>
                </div>

                <div class="menu" id="menu">
                    <span>Códigoamigo.com</span>
                    <a href="https://www.codigoamigo.com" title="Códigos descuento">
                        <i class="fa fa-home"></i> Inicio
                    </a>
                    <a href="https://www.codigoamigo.com/listado-categorias">
                        <i class="fa fa-list"></i> Categorías
                    </a>
                    <a href="https://www.codigoamigo.com/listado-marcas">
                        <i class="fa fa-tags"></i> Todas Las Marcas
                    </a>
                    <a href="https://www.codigoamigo.com/contacto">
                        <i class="fa fa-envelope"></i> Contacto
                    </a>
                </div>

                <script src="/js/mobile-filters.js"></script>
            </div>

            <!-- ... resto del código existente ... -->

            <style>
            .div_busqueda{
	           margin:0px;padding:0px;
	           margin-bottom: 2%;
    			box-shadow: 0 0 5px 0 rgba(0,0,0,0.1);
    		}

            .div_busqueda .interior{
	           position:absolute;left:10px;top:10px;z-index:9999;
            	font-size:18px;
            	color:#575557;
    		}

    		.div_busqueda input{
    			padding-left:40px;
    			border:none !important;
    		}
    		</style>
    		

		<div class="col-xs-6 div_busqueda">
    			<form role='form' name='busqueda_marca2' action='busqueda' method='POST'>
                <div class="interior "><i class="fa fa-search"></i></div> <input type='text' class='form-control' required placeholder='Busca tu código de descuento' name='busqueda_marca2' id='busqueda_marca2' value='<?php echo isset($_REQUEST["busqueda_marca"]) ? $_REQUEST["busqueda_marca"] : ''; ?>'>
                </form>
		</div>
    		
		<div class="col-xs-2 text-left">
			<?php if(!empty($_SESSION["user_id"])){ ?>
				<a href="<?php echo link_nuevo_codigo(); ?>" title="publicar un codigo de amigo"><button class="btn btn_codigo_amigo open_modal_login_mobile" style="padding:5px 15px !important;"><i class="fa fa-plus"></i></button></a>
			<?    }else{ ?>
				<span title="publicar un codigo de amigo"><button class="btn btn_codigo_amigo open_modal_login_mobile" style="padding:5px !important;min-width:50px;height:35px;"><i class="fa fa-plus"></i></button></span>
			<?php } ?>

		</div>

-
    	<?php if(isset($_SESSION["user_id"]) && $_SESSION["user_id"] != "") { ?>
        <style>
            .profile-menu {
                display: none;
                position: fixed;
                top: 60px;
                right: 0;
                background: white;
                width: 200px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
            }
            
            .profile-menu a {
                display: block;
                padding: 15px;
                text-decoration: none;
                color: #333;
                border-bottom: 1px solid #eee;
            }
            
            .profile-menu a:hover {
                background-color: #f5f5f5;
            }
            
            .profile-image {
                cursor: pointer;
                width: 36px;
                height: 36px;
                border-radius: 50%;
            }
        </style>

        <img onclick="toggleProfileMenu()" class="profile-image" src="<?php echo $data_usuario["img"]; ?>" />
        
        <div id="profileMenu" class="profile-menu">
            <a href="usuario">Editar datos</a>
            <a href="<?php echo link_usuario_nuevas_marcas($data_usuario["username"], (string)($data_usuario["id_string"]));?>">Descubrir nuevas marcas</a>
            <a href="<?php echo link_usuario($data_usuario["username"], (string)($data_usuario["id_string"]));?>">Mis códigos</a>
            <a href="logout" onclick="signOut();">Cerrar sesión</a>
        </div>

        <script>
            function toggleProfileMenu() {
                var menu = document.getElementById("profileMenu");
                menu.style.display = menu.style.display === "block" ? "none" : "block";
            }

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(event) {
                var menu = document.getElementById("profileMenu");
                var profileImage = document.querySelector('.profile-image');
                
                if (!menu.contains(event.target) && !profileImage.contains(event.target)) {
                    menu.style.display = "none";
                }
            });
        </script>
    <?php } else { ?>
        <div class="col-xs-2 text-right">
            <button class="btn open_modal_login_mobile" style="padding: 8px; background: #3466FF; color: white; border: none; border-radius: 5px; width: 36px; height: 36px;">
                <i class="fa fa-user"></i>
            </button>
        </div>
    <?php } ?>
    </div>


	<div class="block_menu_mobile hide">
	  	<a href="https://www.codigoamigo.com">
  			<img alt="códigos de amigo" src="/img/logo_codigoamigo_real4.png">
		</a><hr class="codigo">
	  	<ul id="menu-mobile-menu" class="">
	  		<li class="hidden-xs"><a href="<?php echo link_listado_marcas(); ?>">Todas las marcas</a></li>
	  		<li class="hidden-xs"><a href="<?php echo link_listado_categorias(); ?>">Todas las categorías</a></li>
			<?php if(!empty($_SESSION["user_id"])) { ?>
                <li>
                	<a class="btn-custom btn-mini open_modal_login_mobile" href="nuevo_codigo">Publicar código</a>
                </li>
                <hr class="codigo">
                <li data-toggle="collapse" data-target="#collapse_zona1">
                	<a href="javascript:void(0);" class="dropdown-toggle logueado" data-toggle="dropdown">
                        <?php
                            if(!empty($_SESSION["username"])) {
                                $usuario = getObjectUser('username', $_SESSION["username"]);
                                $imagen = !empty($usuario["img"]) ? $usuario["img"] : "/img/po.png";
                                ?>
                                <a href="<?php echo link_usuario($_SESSION["username"], (string)($_SESSION["user_id"]));?>">
                                    <img src='<?php echo $imagen;?>' class='img_profile' ><?php echo $_SESSION["username"];?>
                                </a>
                            <?php
                            } else {
                                echo "Mi Cuenta";
                            }
                        ?>
                    </a>
            	</li>
	  			<li><a href="usuario">Editar datos</a>
	  			<li><a href="<?php echo link_usuario_nuevas_marcas($data_usuario["username"], (string)($data_usuario["id_string"]));?>"><i class="fas fa-exclamation"></i> Descubrir nuevas marcas</a>
                <li><a href="<?php echo link_usuario($_SESSION["username"], (string)($_SESSION["user_id"]));?>">Mis códigos</a>
                <li><a href="logout" onclick="signOut();">Cerrar sesión</a>
                <?php } else {?>
                
                <li>
                    <a class="btn-custom btn-mini hide" href="registro"><i class="fa fa-user"></i> Iniciar sesión o registrase</a>
                    <button class="btn-custom btn-mini open_modal_login_mobile"><i class="fa fa-user"></i> Iniciar sesión</button>
                </li>
            <?php } ?>
        </ul>
    </div>
    </div>
<?php 

modal_comun();
}

function modal_comun(){
    
    
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


function modal_compartir(){


    ?>
    <div class="modal fade" id="modal_compartir" role="dialog"></div>


    <?

}


function modal_statistics(){


    ?>
    <div class="modal fade" id="modal_statistics" role="dialog"></div>


    <?

}




function modal_for_destacar() { ?>




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

function modal_for_login() { ?>
<?php /*?><div class="modal fade" id="modal_continuar" role="dialog" style="">
    <div class="modal-dialog modal-md" style="width:100% !important;">
    
         <div class="modal-body">
            <div class="text-center">
            <h2 style="color:white !important;    white-space: break-spaces;">Para continuar viendo el contenido por favor</h2>
        
            <a class="btn btn-contact btn-87" href="<? echo $actual_url;?>?continua_viendo=1" rel="gallery" style="width: 100%;
    margin: 20px 0px 0px 0px;float:left;">Continua</a>
        </div>
    </div>
    
    </div>
</div>*/?>

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
                	
                	<div style="width:245px;text-align:center;    margin: auto;">
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
                        <a class="text-primary" href="registro"><strong>Crea tu usuario</strong></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php }

function modal_mobile() { ?>

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

<?php } ?>