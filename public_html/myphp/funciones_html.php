<?php

date_default_timezone_set( "Europe/Madrid");

$GLOBALS['month_arr'][1]='ene';    // siempre tres caracteres
$GLOBALS['month_arr'][2]='feb';
$GLOBALS['month_arr'][3]='mar';
$GLOBALS['month_arr'][4]='abr';
$GLOBALS['month_arr'][5]='may';
$GLOBALS['month_arr'][6]='jun';
$GLOBALS['month_arr'][7]='jul';
$GLOBALS['month_arr'][8]='ago';
$GLOBALS['month_arr'][9]='sep';
$GLOBALS['month_arr'][10]='oct';
$GLOBALS['month_arr'][11]='nov';
$GLOBALS['month_arr'][12]='dic';

$GLOBALS['month_arr_long'][1]='enero';
$GLOBALS['month_arr_long'][2]='febrero';
$GLOBALS['month_arr_long'][3]='marzo';
$GLOBALS['month_arr_long'][4]='abril';
$GLOBALS['month_arr_long'][5]='mayo';
$GLOBALS['month_arr_long'][6]='junio';
$GLOBALS['month_arr_long'][7]='julio';
$GLOBALS['month_arr_long'][8]='agosto';
$GLOBALS['month_arr_long'][9]='septiembre';
$GLOBALS['month_arr_long'][10]='octubre';
$GLOBALS['month_arr_long'][11]='noviembre';
$GLOBALS['month_arr_long'][12]='diciembre';

$GLOBALS['weekdays_arr'][1]="Domingo";       // Siempre 2 caracteres
$GLOBALS['weekdays_arr'][2]="Lunes";
$GLOBALS['weekdays_arr'][3]="Martes";
$GLOBALS['weekdays_arr'][4]="Miercoles";
$GLOBALS['weekdays_arr'][5]="Jueves";
$GLOBALS['weekdays_arr'][6]="Viernes";
$GLOBALS['weekdays_arr'][7]="Sabado";

$actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


global $keywords;

/* KEYWORDS TRADE REPUBLIC */
$keywords["traderepublic"] = array(
    "codigo invitacion trade republic",
    "trade republic codigo invitacion",
    "codigo de invitacion trade republic",
    "codigo trade republic",
    "codigo invitacion trade republic 2024",
    "código invitación trade republic",
    "código invitación trade republic 2024",
    "trade republic codigo",
    "codigo amigo trade republic",
    "codigo promocional trade republic",
    "trade republic codigo amigo",
    "codigo invitación trade republic",
    "codigo puk trade republic",
    "codigo referido trade republic",
    "codigo de referencia trade republic",
    "codigo recompensa trade republic",
    "codigo referencia trade republic",
    "codigo trade republic 2024",
    "trade republic codigo de invitacion",
    "codigo bic trade republic",
    "codigo swift trade republic",
    "codigos trade republic",
    "código de invitación trade republic",
    "código trade republic",
    "trade republic código amigo",
    "codigo de invitación trade republic",
    "codigo.invitacion trade republic",
    "trade republic codigo promocional",
    "trade republic codigo puk",
    "trade republic código invitación",
    "trade republic codigo de referencia",
    "trade republic codigo invitacion forocoches"
);



function make_links_clickable($text){
    return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank">$1</a>', $text);
}

function transformafechaV2($date){
    
    
    global $amZona,$debugAdmin;
    $morelong = false; // Inicializar variable $morelong
    $zona = ''; // Inicializar variable $zona
    
    
    //no puede mostrar fechas en el futuro
    
    $opening_date = date('Y-m-d G:i:s',strtotime('now'));
    $current_date = date('Y-m-d G:i:s',($date));
    
    if ($opening_date < $current_date || $date==1)
    {
        $date = $opening_date;
        $date = date('Y-m-d',strtotime('now'));
    }
    
    
    
    
    // Asegurar que $date sea un timestamp válido
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    if($timestamp === false) {
        $timestamp = time();
    }
    
    if(date("d-m-y")==date("d-m-y",$timestamp)){
        if(!$morelong){
            $str_week = " ";
        }else{
            $str_week = isset($GLOBALS['weekdays_arr'][date("w",$timestamp)+1]) ? $GLOBALS['weekdays_arr'][date("w",$timestamp)+1] : "";
        }
    }else{
        $str_week = isset($GLOBALS['weekdays_arr'][date("w",$timestamp)+1]) ? $GLOBALS['weekdays_arr'][date("w",$timestamp)+1] : "";
    }
    
    $day = date("d",$timestamp);
    
    $month = isset($GLOBALS['month_arr'][date("n",$timestamp)]) ? $GLOBALS['month_arr'][date("n",$timestamp)] : "";
    $month_l = isset($GLOBALS['month_arr_long'][date("n",$timestamp)]) ? $GLOBALS['month_arr_long'][date("n",$timestamp)] : "";
    
    $year = date("y",$timestamp);
    $yearF = date("Y",$timestamp);
    $hora = date("H:i",$timestamp);
    
    $nuevahora = strtotime('now')-$timestamp;
    $nuevahora_horas = (int)($nuevahora/(60*60));
    $nuevahora_minutos = date("i",$nuevahora);
    
    $f1 = date('Ymd');
    $f2 = date('Ymd', $timestamp);
    
    
    
    if($f1 == $f2){
        
        $form_date = "Hoy, hace ".$hora." ".$zona;
        
    }else{
        
        $form_date = $str_week.", ".$day." de ".$month_l;
    }
    
    
    
    
    return $form_date;
}

    function bloque_titulo_pagina($titulo) { ?>

        <div class="row bloque_titulo_pagina"><h1><?php echo $titulo; ?></h1></div>

    <?php }

    function busqueda() { ?>

        <div class='row'>
            <div class='col-md-6 col-md-offset-3 col-xs-12 col-sm-12'>
                <form role='form' name='busqueda_marca' action='busqueda' method='POST'>
                	<input type='text' class='form-control' required placeholder='Busca tu marca' name='busqueda_marca' id='busqueda_marca' value='<?php echo $_REQUEST["busqueda_marca"]; ?>'>
                </form>
            </div>
        </div>

    <?php }

    function publica_tu_codigo($marca = '',$already='') { ?>

        <div class="bloque_publica_nuevo_codigo"><?php


        if($marca){
        	if(!$already){

        	if($marca["nombre"] != "") { ?>
        	   <span>Tienes un código de <?php echo $marca["nombre_clave"]; ?>?</span>
        	<?php } ?>
        	

        	<?php if(!empty($_SESSION["user_id"])) { ?>
        		<a href="<?php echo link_nuevo_codigo(); ?>?marca=<?php echo $marca["nombre"]; ?>" title="publicar un codigo de amigo"><button class="btn btn_codigo_amigo"><i class="fa fa-plus"></i> Publica tu código ahora !</button></a>
        	<?php }else{ ?>
        		<button class="btn btn_codigo_amigo open_modal_login"><i class="fa fa-plus"></i> Publica tu código ahora !</button>
        	<?php } ?>

        	<?php }else{ ?>Ya has publicado tu código <?php echo $marca["nombre_clave"]; ?><?php } ?>

           <script type="text/javascript">
          var addthis_config =
          {
           services_exclude: 'print, printfriendly'
          }
          </script>
      </div>
      </div>
      <br>

      <?php
      $share_url = "https://www.codigoamigo.com/de-".$marca["nombre_clave"];

      // Get current page URL
      $crunchifyURL = urlencode($share_url);

      // Get current page title
      $crunchifyTitle = htmlspecialchars(urlencode(html_entity_decode($title)));
      // $crunchifyTitle = str_replace( ' ', '%20', get_the_title());

      // Get Post Thumbnail for pinterest
      $crunchifyThumbnail = $imagen_social;

      // Construct sharing URL without using any script
      $twitterURL = 'https://twitter.com/intent/tweet?text='.$crunchifyTitle.'&amp;url='.$crunchifyURL.'&amp;via=Crunchify';
      $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u='.$crunchifyURL;
      $googleURL = 'https://plus.google.com/share?url='.$crunchifyURL;
      $bufferURL = 'https://bufferapp.com/add?url='.$crunchifyURL.'&amp;text='.$crunchifyTitle;
      $whatsappURL = 'whatsapp://send?text='.$crunchifyTitle.' '.$crunchifyURL;
      $telegramURL = 'https://telegram.me/share/url?url='.$crunchifyURL.'&text='.$crunchifyTitle;
      $linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url='.$crunchifyURL.'&amp;title='.$crunchifyTitle;

      // Based on popular demand added Pinterest too
      $pinterestURL = 'https://pinterest.com/pin/create/button/?url='.$crunchifyURL.'&amp;media='.$crunchifyThumbnail[0].'&amp;description='.$crunchifyTitle;

      // Add sharing button at the end of page/page content
      //$content .= '<!-- Implement your own superfast social sharing buttons without any JavaScript loading. No plugin required. Detailed steps here: http://crunchify.me/1VIxAsz -->';



      $content2 .= '<div class="crunchify-social pre_publicar">';
      $content2 .= '<span>Comparte el código amigo</span>';
      $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-whatsapp" href="'.$whatsappURL.'" target="_blank">Whatsapp</a>';
      $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-telegram" href="'.$telegramURL.'" target="_blank">Telegram</a>';

      $content2 .=' <a rel="nofollow" class="crunchify-link crunchify-twitter" href="'. $twitterURL .'" target="_blank">Twitter</a>';
      $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-facebook" href="'.$facebookURL.'" target="_blank">Facebook</a>';
      $content2 .= '</div>';

      echo $content2;
      ?><div>

    <?php
        }else{
            
        ?><button class="btn btn_codigo_amigo open_modal_login"><i class="fa fa-plus"></i> Publica tu código ahora !</button><?php
        }


     }










	/**********************************************************
     * CÓDIGOS
     * *******************************************************/

    /*function block_destaca_codigo($item) {

        global $detect_device, $url_usuario_sin_imagen;

        $datos_usuario = array();
        $usuario = getObjectUser('_id', $item["id_usuario"]);
        if($usuario && $usuario != "") { $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($item["id_usuario"])); }
        if($usuario && $usuario != "") { $datos_usuario = get_array_de_usuario($usuario); }
        $marca = getObjectMarca('nombre_clave', $item["marca"]);

        ?>
        <div class="row card_real <? echo $destacado; ?>">
        	<div class="col-md-3 col-xs-3 card text-center">

            <?php

            print_r($item);

            ?>

        	</div>
        </div>
        <?
    }*/



    function block_listado_codigos($lista_codigos='', $tipo='') {



 
        global $detect_device, $url_usuario_sin_imagen,$tipo_block_codigos, $data_usuario,$provincia,$marca,$num_codigos_global,$keywords,$actual_link;


        $html = "";



        if($lista_codigos == "" || empty($lista_codigos)) {

           /* $html .= '<div class="pre_card"><p>No esperes más y publica ya tu código</p></div>';
            $html .= publica_tu_codigo();   */

        } else {

            $array_posiciones = array();


            ob_start();
            
         
            $i=0;

            if(isset($lista_codigos['results']) && $lista_codigos['results']){

                $resultados = $lista_codigos['results'];

            }else{
                $resultados = $lista_codigos;
            }

            
           
            if(isset($lista_codigos['total_number']) && $lista_codigos['total_number']){
                $num_destacados =$lista_codigos['total_number'];
            }else{
                $num_destacados = count($lista_codigos);
            }

            // Definir variable $tam antes del bucle
            if($tipo == "mis_codigos"){
                $tam = "col-xs-12";
            }else{
                $tam = "col-xs-12";
            }

            // Inicializar variable $suma
            $suma = 0;
            
            foreach ($resultados as $item) {
                if($tipo_block_codigos!='mis_codigos'){
                    $corte = 2;
                    $corte2 = 8;
                    $corte3 = 12;

                    if($tipo != 'destacados' && ($corte == $suma || $corte2 == $suma || $corte3 == $suma)){
                        ?>

                        <div class="pre_card">

                        	<div class="card_real" style="margin-bottom:20px;margin-left:10px;">
                            	<div>
                            	<?php /*if($marca["categoria_clave"] == 'apuestas'){ ?>
                            		<span class="promo" onclick="window.open('https://ads.williamhill.es/redirect.aspx?pid=191770076&bid=1487417674&lpid=1487416096');"><img src="https://s0.2mdn.net/8563112/_ESP300_5th_March_NC_300x250.gif"></span>
                            	<?php }*/ ?>

                                	<!-- Codigoamigo - entremedio -->
                                    <ins class="adsbygoogle"
                                         style="display:block"
                                         data-ad-slot="6883957062"
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
                                        } catch (e) {
                                            if (!e || !e.message || e.message.indexOf("adsbygoogle.push() error") === -1) {
                                                console.warn("Error al inicializar el bloque AdSense intermedio:", e);
                                            }
                                        }
                                    })();
                                    </script>
                                </div>
                            </div>
                        </div>
                        <?
                    }

                
                }

                if($item["destacado"]){
                    $destacado_fecha = transformafechaV2($item["destacado"]);
                }else{
                    $destacado_fecha = "";
                }

                $datos_usuario = array();
                $usuario = getObjectUser('_id', $item["id_usuario"]);
                if($usuario && $usuario != "") { $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($item["id_usuario"])); }
                if($usuario && $usuario != "") {$datos_usuario = get_array_de_usuario($usuario); }
                $marca = getObjectMarca('nombre_clave', $item["marca"]);
                if($marca && isset($marca["nombre"])) {
                    $marca["nombre"] = ucfirst(strtolower($marca["nombre"]));
                } else {
                    $marca = array("nombre" => ucfirst($item["marca"]));
                }
                
            
                if($marca["nombre"]){
                if($item["destacado"]){ $destacado = "destacado"; } else { $destacado = ""; } ?>
            <div class="pre_card  <?php echo $tam; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?> <? echo $destacado; ?> <?php if(isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $item["id_usuario"] || $tipo == "mis_codigos" || $tipo == "normal"){ echo "mis_codigos"; }?>">

            <div class="card_real <? echo $destacado; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?><?php if($tipo == "mis_codigos" || $tipo == "normal"){ echo "mis_codigos"; }?>">
            
            <?php 
            // Mostrar badge de destacado si el código está destacado
            if($item["destacado"] && $item["destacado"] > 0): ?>
                <div class="featured-badge">
                    <i class="fas fa-star"></i> Destacado
                </div>
            <?php endif; ?>
            
            <?php 
            // Mostrar badge de borrado si el código está borrado
            if($item["estado"] == -2): ?>
                <div class="deleted-badge">
                    <i class="fas fa-trash"></i> Borrado
                </div>
            <?php endif; ?>
            
            <?php 
            
            
            
            if($tipo=='destacados' || $tipo=='mis_codigos'){ ?>
            <div style="position: relative;height: 160px;">
            
            <?php 
            
            if(link_marca($marca["nombre_clave"]) != $actual_link ){?>
            <a title="Código invitacion <?php echo $marca["nombre"]; ?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>"> 
            <?php } ?>
            
            
                <img loading="lazy" style="object-fit:scale-down;left:0px;position:absolute;" alt="<?php echo isset($keywords[$marca["nombre_clave"]][$i]) ? $keywords[$marca["nombre_clave"]][$i] : $marca["nombre"]; ?>" class="post-card imagen <?php if($tipo == "mis_codigos"){ echo "mis_codigos"; } ?> lazyload" src="<?php echo $marca["imagen"]; ?>">
   
   
                          <?php
                          
                          if(isset($datos_usuario["pro_user"]) && $datos_usuario["pro_user"] == 1){?><div class="premium">✔️ Usuario Premium</div><?php }
                          
    			if(isset($_SESSION["user_id"]) && $item["id_usuario"] == $_SESSION["user_id"] && $item["estado"] != '-2') {
    			    
    
    			    $posicion = get_code_position($marca,$item["_id"]);
    
    			    if(!isset($array_posiciones[$posicion])){
    			        $array_posiciones[$posicion] = 0;
    			    }
    			    $array_posiciones[$posicion]++;
    
    			    if($posicion == 1){
    			        $posicion = "🥇".$posicion;
    			        $class = "alta_vis_top";
    			    }
    
    			    if($posicion == 2){
    			        $class = "media_vis_top";
    			    }
    
    			    if($posicion > 2){
    			        $class = "baja_vis_top";
    			    }
    
    			    ?>
                	 <div class='<?php echo $class; ?>' style="text-align:center;padding:0px 0px 0px 0px;margin-top:0px;color:white;position:relative;">
                	 <?php echo "Código en ".$posicion." posición <b></b><br>";
                	 ?></div><?
                	 }
    			?>
    
                </img>
                
             
            
            </a>
            
               </div>
               
               <?php } ?>

			<div class="middle">
            <div class="avatar" item-start="">

           <div class="pre_avatar lazyload" data-src="<?php echo isset($datos_usuario["img"]) ? $datos_usuario["img"] : ''; ?>">
            
            
            <div rel="nofollow" class="link_usuario a_link_us" title="Publicado por <?php echo isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario'; ?>" data-href="<?php echo link_usuario(isset($datos_usuario["username"]) ? $datos_usuario["username"] : '', isset($datos_usuario["_id"]) ? $datos_usuario["_id"] : ''); ?>">            </div>
           
                    </div>

                </div>
                <div class="item-inner">
                    <div class="input-wrapper">
                        <div class="label">
                            <div class="a_link_us" title="Publicado por <?php echo isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario'; ?>" data-href="<?php echo link_usuario(isset($datos_usuario["username"]) ? $datos_usuario["username"] : '', isset($datos_usuario["_id"]) ? $datos_usuario["_id"] : ''); ?>"><?php if($tipo != "mis_codigos"){?><p><?php echo isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario';  ?></p><?php } ?>

                            <small class="ico hidden-xs"><span showwhen="core"><i class="fas fa-eye"></i></span> <?php echo $item["totalclicks"]; ?></small>
                            <div class="fecha-publicacion-large" name="<?php echo $destacado_fecha; ?>">
                                <i class="far fa-clock"></i> 
                                <span class="fecha-texto"><?php echo formatDateAgoLarge(($item["fecha_publicacion"])); ?></span>
                            </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-xs-12 container">

        				<div class="row">
            				<div class="col-md-12">
            					<div class="beneficio-destacado">
            						<div class="beneficio-icono">💰</div>
            						<div class="beneficio-contenido">
            							<div class="beneficio-cantidad"><?php echo $item["num_beneficio"]; ?>€</div>
            							<div class="beneficio-tipo"><?php echo $item["tipo_descuento"]; ?></div>
            						</div>
            					</div>
            				</div>
                    	</div>
                    	<?php

                    	/*
                    	 * TEXTEAREA SEO EXPERIMIENTO
                    	 */

                    	$item["provincia"] = ucfirst(strtolower(($item["provincia"])));
                    	$item["localidad"] = ucfirst(strtolower(($item["localidad"])));

                    	$desc = $item["descripcion"];

                    	//$desc.= "\n\nPertenece a ".$marca["categoria"];
                    	
                    	if($item["provincia"]){

                    	$desc.= "\n\nLocalizado en ".$item["provincia"].", ".$item["localidad"];
                    	
                    	}
                    	
                    	
                    	if(isset($datos_usuario["pro_user"]) && $datos_usuario["pro_user"]){
                    	    
                    	    $desc = make_links_clickable($desc);
                    	}
                    	
                    	echo "<div class='div_inside'>";

                    	   $css = ""; // Inicializar variable CSS
                    	   if($marca["nombre_clave"] == 'reby' || $marca["nombre_clave"] == 'acciona'){
                    	       $css = " marca_r";
                    	   }
                    	   
                    	   if(!isset($_GET['codigo']) || !$_GET['codigo']){
                        	   echo "<div class='text_inside ".$css."' id='desc_".$tipo.$item["_id"]."' style='    display: -webkit-box !important;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 10px;height: inherit !important;'>".$desc."</div>";
                    	   }else{
                    	       echo "<div class='text_inside dentro_codigo ".$css."' id='desc_".$tipo.$item["_id"]."' style='overflow:hidden' readonly>".nl2br($desc)."</div>";
                    	   }

                        	/*echo "aaaa";
                        	print_r($datos_usuario);*/
                        	
                        	//print_r($_GET['codigo']);
                        	
                    	   /*if(!$_GET['codigo']){
                            	if(strlen($desc)>120){
                            	   echo "<div class='show_more' data-show='desc_".$tipo.$item["_id"]."'>...ver más</div>";
                            	}
                            }*/

                        echo "</div>";


                    if($tipo == "mis_codigos") {
                        echo "<div class='show_code_last' style='font-size:12px;    word-wrap: break-word;'>".$item["codigo"]."</div>"; ?>
                     <?php }

                    if($tipo == 'detalle'){
                        
                        


                        if($a_destacar){?>

                                                <hr>
                                            		<div class="text-center show_code_last" style="font-size: 16px; padding: 10px;">


                                            		<?php printa_boton_stripe($c); ?>

                                            		</div>


                           <?php }else{ ?>
                           <br><br>
                           <?php

	/*if((!$_SESSION["user_id"] || $_SESSION["user_id"] == "") && $marca["nombre_clave"] != 'bookingcom') { ?>

		<div class="text-center block_codigo_no_sesion"><p>Por favor, inicia sesión para poder ver este código</p>

		<button class="btn-custom btn-mini login open_modal_login"><i class="fa fa-user"></i> Iniciar sesión</button>
		</div>

	 <?php }else{*/

	     if(strpos($item["codigo"], "http") !==false){
	         $url_codigo = 1;
	     }
	     ?>

			<div class="text-center">

			<div class="tab-content clearfix">

			  <div id="1a">

			  <?php

			  if($marca["nombre_clave"] == 'openbank'){

  			      echo "<h3 style='text-align:left;'>Primer paso</h3>";
  			      ?>
  			                      	<div class="show_code_last link_d">
  			                      		<h3>
  			                      			<a class="btn btn_codigo_amigo btn_interno btn_a_compartir" style="color:white;" href="https://track.adtraction.com/t/t?a=1312208159&as=1434209805&t=2&tk=1" target="_blank">Registrarse en Openbnk</a>
  			                      		</h3>
  			                      	</div><br>

  			  <?php }

			  if($marca["nombre_clave"] == 'n26' || $marca["nombre_clave"] == 'openbank'){
			     echo "<h3 style='text-align:left;'>Segundo paso</h3>";
			  }
			  
			  

			  if($url_codigo){
			      echo "<h3 style='text-align:left;'>Enlace</h3>";
			  }else{

			  }
			  ?>
    			  <div class="show_code_last <?php if($url_codigo){ ?>link_d<?php } ?>">
    	 			<h3>
            	 <?php

                	if($url_codigo){
                	    ?><a target="_blank" href="<? echo $item["codigo"]; ?>"><? echo $item["codigo"]; ?></a><?
                	}else{
                	    echo $item["codigo"];
                	}

            	?></h3>

            	</div>

            	<?php echo "<br><i class='fa fa-clipboard' aria-hidden='true'></i> <a onclick='executeCopy(\"".$item["codigo"]."\",$(this));'>Copiar <u>Enlace</u> al portapapeles</a>"; ?>



        	</div>


		<?php 
		
		
		if($url_codigo){ //EXTRAIGO EL CODIGO DE LA URL

		    if($item["codigo_simple"]){
		        
		        $item_solo_codigo = $item["codigo_simple"];
		        
		    }else{
		        
		        if(strpos($item["codigo"],"=") !==false){
        		    $item_solo_codigo = substr($item["codigo"], strrpos($item["codigo"], '=') + 1);
        		}else{
                    $item_solo_codigo = substr($item["codigo"], strrpos($item["codigo"], '/') + 1);
        		}
        		
	        }

		echo "<hr><h3 style='text-align:left;'>Código</h3>";
        	?>

					<div class="show_code_last">
         		 		<h3><?php echo $item_solo_codigo; ?></h3>
         		 	</div>
         		 	<?php echo "<br><i class='fa fa-clipboard' aria-hidden='true'></i> <a onclick='executeCopy(\"".$item_solo_codigo."\",$(this));'>Copiar <u>Código</u> al portapapeles</a>"; ?>



			<?php }
			
			?>
                       </div>

                                               		<hr>

                                            		<div class="text-center">
                                            			<?php

                        $share_url = "https://www.codigoamigo.com/de-".$marca["nombre_clave"]."?codigo=".$item["_id"];

                		 // Get current page URL
                		 $crunchifyURL = urlencode($share_url);

                		 // Get current page title
                		 $crunchifyTitle = htmlspecialchars(urlencode(html_entity_decode($title)));
                		 // $crunchifyTitle = str_replace( ' ', '%20', get_the_title());

                		 // Get Post Thumbnail for pinterest
                		 $crunchifyThumbnail = $imagen_social;

                		 // Construct sharing URL without using any script
                		 $twitterURL = 'https://twitter.com/intent/tweet?text='.$crunchifyTitle.'&amp;url='.$crunchifyURL.'&amp;via=Crunchify';
                		 $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u='.$crunchifyURL;
                		 $googleURL = 'https://plus.google.com/share?url='.$crunchifyURL;
                		 $bufferURL = 'https://bufferapp.com/add?url='.$crunchifyURL.'&amp;text='.$crunchifyTitle;
                		 $whatsappURL = 'whatsapp://send?text='.$crunchifyTitle.' '.$crunchifyURL;
                		 $telegramURL = 'https://telegram.me/share/url?url='.$crunchifyURL.'&text='.$crunchifyTitle;
                		 $linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url='.$crunchifyURL.'&amp;title='.$crunchifyTitle;

                		 // Based on popular demand added Pinterest too
                		 $pinterestURL = 'https://pinterest.com/pin/create/button/?url='.$crunchifyURL.'&amp;media='.$crunchifyThumbnail[0].'&amp;description='.$crunchifyTitle;

                		 // Add sharing button at the end of page/page content
                		 //$content .= '<!-- Implement your own superfast social sharing buttons without any JavaScript loading. No plugin required. Detailed steps here: http://crunchify.me/1VIxAsz -->';



                		 $content2 .= '<div class="crunchify-social">';
                		 $content2 .= '<span>Comparte el código amigo</span>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-whatsapp" href="'.$whatsappURL.'" target="_blank">Whatsapp</a>';$content2 .= '<a class="crunchify-link crunchify-linkedin" href="'.$linkedInURL.'" target="_blank">LinkedIn</a>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-telegram" href="'.$telegramURL.'" target="_blank">Telegram</a>';

                    		 $content2 .=' <a rel="nofollow" class="crunchify-link crunchify-twitter" href="'. $twitterURL .'" target="_blank">Twitter</a>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-facebook" href="'.$facebookURL.'" target="_blank">Facebook</a>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-pinterest" href="'.$pinterestURL.'" data-pin-custom="true" target="_blank">Pin It</a>';
                		 $content2 .= '</div>';

                		 echo $content2;

                		 ?></div>
                                                    <?php //} ?>
                                                </div>
                                                <?php
                                                	    	}
                                                	    	
                                                	    	
                                                	    	if(isset($datos_usuario["pro_user"]) && $datos_usuario["pro_user"] == 1){
                                                	    	    
                                                	    	    echo '<br><h3>Hablar con '.(isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario').'</h4><div class="" style="text-align:center;border:none;">';
                                                	    	    ?>
                                                	    	    
                                
            	<?php echo '
<a class="btn btn_codigo_amigo ir_codigo" title="Ir al código amigo" href="https://t.me/spnfury" target="_blank" style="color:black;text-decoration:none;">💬 Contactar con '.(isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario').' vía telegram</a></div><br>'; ?>
                                                	    	    
                                                	    	    
                                                	    	    
                           
                            
			 	<?php }
                                                	    	
                                                	    	
                    


                    }else{
                        
                        ?>
                        <a class="btn btn_codigo_amigo ir_codigo" title="Ir al código amigo" href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>">Ir al código <i class="fas fa-angle-right"></i></a>
                    	
                        <?php

                        /*if($item["id_usuario"] == $_SESSION["user_id"]){?>
                        	<a class="btn btn_codigo_amigo ir_codigo" title="Ir al código amigo" href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>">Ir al código <i class="fas fa-angle-right"></i></a>
                    	<?php } elseif($_SESSION["user_id"] != "" || $marca["nombre_clave"]=='bookingcom') { ?>
                        	<a class="btn btn_codigo_amigo ir_codigo" title="Ir al código amigo" href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" rel="nofollow">Ir al código <i class="fas fa-angle-right"></i></a>
                        <?php }else { ?>
                    		<span class="btn btn_codigo_amigo ir_codigo open_modal_login" data-codigo="de-<? echo $marca["nombre_clave"]; ?>?codigo=<?php echo $item["_id"]; ?>" title="Ir al código amigo">Ir al código <i class="fas fa-angle-right"></i></span>
                    	<?php }*/

                    }
                    
                    
                	  if(isset($_SESSION["user_id"]) && $item["id_usuario"] == $_SESSION["user_id"]) { ?>

                    	  <!-- Menú de acciones para códigos propios -->
                    	  <div class="code-actions-menu">
                    	      <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    	          <i class="fas fa-cog"></i> Acciones
                    	      </button>
                    	      <ul class="dropdown-menu">
                    	          <?php if($item["estado"] != '-2') { ?>
                    	          <li>
                    	              <a class="dropdown-item" href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"],'1'); ?>" title="Destacar el código">
                    	                  <i class="fas fa-star"></i> Destacar
                    	              </a>
                    	          </li>
                    	          <li>
                    	              <a class="dropdown-item" href="/modificar_codigo/<?php echo (string)($item["_id"]);?>" title="Editar el código">
                    	                  <i class="fas fa-edit"></i> Editar
                    	              </a>
                    	          </li>
                    	          <?php if($item["estado"] != '-2') { ?>
                    	          <li>
                    	              <a class="dropdown-item" href="/crear-promocion?codigo_id=<?php echo (string)($item["_id"]); ?>" title="Crear promoción temporal">
                    	                  <i class="fas fa-tag"></i> Crear Promoción
                    	              </a>
                    	          </li>
                    	          <?php } ?>
                    	          <li><hr class="dropdown-divider"></li>
                    	          <li>
                    	              <a class="dropdown-item open_modal_compartir" data-codigo-url="https://www.codigoamigo.com/de-<? echo $marca["nombre_clave"]; ?>?codigo=<?php echo $item["_id"]; ?>" title="Compartir en redes">
                    	                  <i class="fas fa-share"></i> Compartir
                    	              </a>
                    	          </li>
                    	          <li>
                    	              <a class="dropdown-item open_modal_estadisticas" data-codigo-id="<?php echo $item["_id"]; ?>" data-codigo-url="https://www.codigoamigo.com/estadisticas?codigo=<?php echo $item["_id"]; ?>" title="Estadísticas de tu código">
                    	                  <i class="fas fa-chart-bar"></i> Estadísticas
                    	              </a>
                    	          </li>
                    	          <li><hr class="dropdown-divider"></li>
                    	          <li>
                    	              <a class="dropdown-item text-danger desactivar_codigo_usuario" data-id-codigo="<?php echo $item["_id"]; ?>" title="Eliminar código">
                    	                  <i class="fas fa-trash"></i> Eliminar
                    	              </a>
                    	          </li>
                    	          <?php } else { ?>
                    	          <li>
                    	              <a class="dropdown-item text-success restaurar_codigo_usuario" data-id-codigo="<?php echo $item["_id"]; ?>" title="Restaurar código">
                    	                  <i class="fas fa-undo"></i> Restaurar
                    	              </a>
                    	          </li>
                    	          <?php } ?>
                    	      </ul>
                    	  </div>


                    <?php }elseif(!$destacado){

                        if(!isset($item["votos_positivos"])){
                            $item["votos_positivos"] = 0;
                        }

                        if(!isset($item["votos_negativos"])){
                            $item["votos_negativos"] = 0;
                        }

                        $item["media"] = $item["votos_positivos"]-$item["votos_negativos"];

                	?>

                	<div class="caja_votos" data-codigo-id="<?php echo $item["_id"]; ?>">
                    	<a class="votar menos" data-codigo-id="<?php echo $item["_id"]; ?>">- <?php /*?><small>(<?php echo $item["votos_negativos"]; ?>)</small><?*/?></a>
                    	<span class="votar votar_sin" <?php if($item["media"]<0){?>style='color:red;'<?php }elseif($item["media"]>0){ ?>style='color:green;' <?php }?>><?php echo $item["media"]; ?>°</span> <a class="votar mas" data-codigo-id="<?php echo $item["_id"]; ?>" > + <?php /*<small>(<?php echo $item["votos_positivos"]; ?>)</small>*/?></a>
                	</div>

                	<?php } ?>
                	
                </div>

            </div>
            </div>
            <?php
            

            $suma++;
            }

            if($tipo=='categoria' && $suma==3){
                break;
            }
            
            $i++;

            }
            
            $contenido = ob_get_contents();
            ob_end_clean();

            
 
             //saco las posiciones

            $num_1_codes = 0;
            $num_2_codes = 0;
            $num_3_codes = 0;
            

            if(isset($array_posiciones[1])){
                $num_1_codes = $array_posiciones[1];
            }

            if(isset($array_posiciones[2])){
                $num_2_codes = $array_posiciones[2];
            }

            $num_3_codes = $num_destacados - ($num_1_codes + $num_2_codes);
            

            // echo "<pre>";
            // print_r($array_posiciones);die;
            // foreach($array_posiciones as $num => $pos){

            //     if($num>0){
            //         $num_3_codes++;
            //     }

            // }
            

            if(isset($_SESSION["user_id"]) && $item["id_usuario"] == $_SESSION["user_id"] && $tipo == "mis_codigos"){
                
               
                
            ?>


                <div class="pre_card  <?php echo $tam; ?>">
                	<p class="alta_vis_tab">Alta Visibilidad</p>
                	<div class="card_real  text-center" style="padding:10px;font-size:22px;">
                		<span class="numeraco"><?php echo $num_1_codes; ?></span><small style="font-size:16px;">Códigos</small>
                		<span class="numeraco_2">🟢  En posición Núm. 1</span>
                	
                   
        				<br><span class="numeraco_2" style="margin:0px !important;" >🥇 Destaca todos tus <b  style="color:red !important;"><?php echo $num_codigos_global; ?></b> Códigos</span>
        			
        				<div style="font-size:26px !important;padding:20px 20px 0px 20px;"><?php echo "<del>".($num_codigos_global*(0.99))."€</del>"; ?> <b style="color:red !important;"> 9,99€</b></div>
        				
        				<?php printa_boton_splash_stripe($c); ?>
    				
                	</div>
                	
                	
                </div>
                
                

                <div class="pre_card  <?php echo $tam; ?>">
                	<p class="media_vis_tab">Media Visibilidad</p>
                	<div class="card_real text-center" style="padding:10px;font-size:22px;">
                		<span class="numeraco"><?php echo $num_2_codes; ?></span><small style="font-size:16px;">Códigos</small>
                		<span class="numeraco_2">🟡 En posición Núm. 2</span>
                	</div>
                </div>

                <div class="pre_card  <?php echo $tam; ?>">
                	<p class="baja_vis_tab">Baja Visibilidad</p>
                	<div class="card_real text-center" style="padding:10px;font-size:22px;">
                		<span class="numeraco"><?php echo $num_3_codes; ?></span><small style="font-size:16px;">Códigos</small>
                		<span class="numeraco_2">🔴 Por Debajo de Núm. 3</span>
                	</div>
                
                

           </div>
           </div>
           
           

          
           
           
           
           <?php /*?>
         <h1>PREMIUM - AUTO DESTACAR</h1>
            
            <div class="pre_card col-lg-12">
<div class="card_real destacado" style="padding:20px 10px;font-size:22px;">
<h2>Destaca tus códigos de manera automática cada 72H!</h2>


<div class="row" style="margin-top: 1%;">
<div class="col-md-2 col-md-offset-2" style="padding-top: 10px;"><label>SLOT 1:</label></div>
<div class="col-md-6">
	<?php 
	
	
// 	echo "<pre>";
// 	print_r($lista_codigos);
// 	die;
	
	?><select id="slot_1" name="slot_1" style="height:50px;border:1px solid grey;">
	<?php foreach($lista_codigos as $id=>$field){?>
		<option value="<?php echo $lista_codigos["_id"];?>"><?php echo $field["marca"]?>
	<?php } ?>
	</option>
	</select>
</div>
</div><br>


<div class="row" style="margin-top: 1%;">
<div class="col-md-2 col-md-offset-2" style="padding-top: 10px;"><label>SLOT 2:</label></div>
<div class="col-md-6">
	<?php 
	
	
// 	echo "<pre>";
// 	print_r($lista_codigos);
// 	die;
	
	?><select id="slot_2" name="slot_2" style="height:50px;border:1px solid grey;">
	<?php foreach($lista_codigos as $id=>$field){?>
		<option value="<?php echo $lista_codigos["_id"];?>"><?php echo $field["marca"]?>
	<?php } ?>
	</option>
	</select><br>
</div>
</div><br>
<div class="row" style="margin-top: 1%;">
<div class="col-md-2 col-md-offset-2" style="padding-top: 10px;"><label>SLOT 3:</label></div>
<div class="col-md-6">
	<?php 
	
	
// 	echo "<pre>";
// 	print_r($lista_codigos);
// 	die;
	
	?><select id=""slot_3"" name="slot_3" style="height:50px;border:1px solid grey;">
	<?php foreach($lista_codigos as $id=>$field){?>
		<option value="<?php echo $lista_codigos["_id"];?>"><?php echo $field["marca"]?>
	<?php } ?>
	</option>
	</select>
</div>
</div><br>
</div>
</div>
       
       </div> 
       */ ?>
       
       </div></div>
       
      
       
           <h1>Tus códigos (<?php echo $num_destacados; ?>)</h1>
           
           
           <br>



			<div class="listado_codigos">

            <?  }

            echo $contenido;


        }


        

        // Inicializar variable si no está definida
        if (!isset($num_destacados)) {
            $num_destacados = 0;
        }
        $resta = 3-($num_destacados);

        if($tipo == 'destacados'){

            ?>

            <div class="pre_card  <?php echo isset($tam) ? $tam : ''; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?> <? echo isset($destacado) ? $destacado : ''; ?> <?php if(isset($_SESSION["user_id"]) && isset($item["id_usuario"]) && $_SESSION["user_id"] == $item["id_usuario"] || $tipo == "mis_codigos" || $tipo == "normal"){ echo "mis_codigos"; }?>">
                        <div class="vamos_destaca card_real <? echo isset($destacado) ? $destacado : ''; ?>">


                        <div class="text-center" class="col-md-12" style="padding:40px 20px;font-size:50px;">
                        	<i class="fas fa-star"></i>
                        </div>

                        <?php if(isset($data_usuario["username"]) && $data_usuario["username"]){?>
                        <a style="color:white !important;" href="<?php echo link_usuario($data_usuario["username"], (string)($data_usuario["id_string"]));?>#<?php echo isset($marca["nombre_clave"]) ? $marca["nombre_clave"] : ''; ?>">

                        <?php }else{?>
                        <a class="open_modal_login" style="color:white !important;" data-codigo="mis_codigos">

                        <?php } ?>
                            <br>
                            <b>!Destácalo ahora!</b>
                         </a>

                         </div>
            <?php

        }


/*         if($tipo == 'destacados' && $num_destacados<4){

                    for($i=1;$i<$resta;$i++){

                        ?>
                        <div class="pre_card  <?php echo $tam; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?> destacado <? echo $destacado; ?>">
                            <div class="card_real por_insertar">

                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                          		<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                          		<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:26px;"></div>

                            	<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                            <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                            <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                            <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                            <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                            <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>

                            </div>
                        </div>
                        <?

                    }

        } */


?> </div><?

    }



    function block_listado_codigos_lite($lista_codigos, $tipo) {


        global $detect_device, $url_usuario_sin_imagen,$tipo_block_codigos, $data_usuario,$provincia,$marca;



        /*
         * LOS AGRUPO POR CATEGORIA
         */


         if($lista_codigos['results']){

            $resultados = $lista_codigos['results'];

        }else{
            $resultados = $lista_codigos;
        }

        
       
        if($lista_codigos['total_number']){
            $num_destacados =$lista_codigos['total_number'];
        }else{
            $num_destacados = count($lista_codigos);
        }



        $suma = 0;
        foreach ($resultados as $item) {



//             echo "<pre>";
//             print_r($marca);die;

            if($item["codigo"]){

                $marca = getObjectMarca('nombre_clave', $item["marca"]);

                $lista_codigos_categoria[$marca["categoria_clave"]][$suma]["item"] = $item;
                $lista_codigos_categoria[$marca["categoria_clave"]][$suma]["marca"] = $marca;

                $categorias_arr[$marca["categoria_clave"]] = $marca["categoria"];
                $suma++;

            }
        }



        $html = "";


        if($lista_codigos == "" || empty($lista_codigos)) {

            /* $html .= '<div class="pre_card"><p>No esperes más y publica ya tu código</p></div>';
             $html .= publica_tu_codigo();   */

        } else {


            $i=0;
            foreach($categorias_arr as $name=>$name_normalizado_pre){

                $name_normalizado = ucfirst(str_replace("-", " ",$name_normalizado_pre));

                echo "<h2>".$name_normalizado."</h2><br>";

                echo '<div class="list-group">';
                    foreach($lista_codigos_categoria[$name] as $num=>$item_pre) {

                        $item = $item_pre["item"];
                        $marca = $item_pre["marca"];




                        ?>
                        
                        <a target="_blank" href="<? echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                        
                        

							<div class="d-flex w-100 justify-content-between">


							 		<div class="row">
                                    	<div class="col-xs-4 col-md-2">
                                    		<img src="<?php echo $marca["imagen"];?>" style="max-height:100px;">
                                		</div>
                                		<div class="col-xs-8  col-md-10 text-left">
                                			<ul>
											<li style="color:grey;font-size:18px;"><b><?php echo $item["num_beneficio"]." ".$item["tipo_descuento"]; ?></b></li>
                                			<li>

                                    			<div class=''><?php echo substr($item["descripcion"], 0, 200) . (strlen($item["descripcion"]) > 200 ? '...' : '');
                                    			if(strlen($item["descripcion"])>200){
                                    			    echo "<div class='show_more' data-show='desc_".$tipo.$item["_id"]."'>...ver más</div>";
                                    			}
                                    			?>
                                    			</div>

                                			</li>

                                		</div>



                                	</div>
                                		<hr>
                        		</div>
                    		</a>
                        <?

                    }
                echo "</div>";
                
                $i++;

            }





            }


            $num_destacados = count($lista_codigos);

            $resta = 3-($num_destacados);

            if($tipo == 'destacados'){

                ?>

                <div class="pre_card  <?php echo $tam; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?> <? echo $destacado; ?> <?php if(isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $item["id_usuario"] || $tipo == "mis_codigos" || $tipo == "normal"){ echo "mis_codigos"; }?>">
                            <div" class="vamos_destaca card_real <? echo $destacado; ?>">


                            <div class="text-center" class="col-md-12" style="padding:40px 20px;font-size:50px;">
                            	<i class="fas fa-star"></i>
                            </div>

                            <?php if($data_usuario["username"]){?>
                            <a style="color:white !important;" href="<?php echo link_usuario($data_usuario["username"], (string)($data_usuario["id_string"]));?>#<?php echo $marca["nombre_clave"]; ?>">

                            <?php }else{?>
                            <a class="open_modal_login" style="color:white !important;" data-codigo="mis_codigos">

                            <?php } ?>¿Quieres ver tu código aquí?
                                <br>
                                <b>!Destácalo ahora!</b>
                             </a>

                             </div>
                <?php

            }


            if($tipo == 'destacados' && $num_destacados<4){

                        for($i=1;$i<$resta;$i++){

                            ?>
                            <div class="pre_card  <?php echo $tam; ?> <? if($tipo == 'detalle'){ echo "detalle"; } ?> destacado <? echo $destacado; ?>">
                                <div class="card_real por_insertar">

                                    <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                              		<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                              		<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:26px;"></div>

                                	<div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>
                                <div class="col-md-12" style="background:#ECEFF1;border-radius:20px;height:5px;margin-bottom:6px;"></div>

                                </div>
                            </div>
                            <?

                        }

            }




        }




    /**********************************************************
     * CÓDIGOS
     * *******************************************************/

    function block_listado_codigos_sergi($lista_codigos, $tipo) {

        global $detect_device, $url_usuario_sin_imagen;
        $html = "";
        if($lista_codigos == "") {

            $html .= '<p>No esperes más y publica ya tu código</p>';
            $html .= publica_tu_codigo();

        } else {
            foreach ($lista_codigos as $item) {

                $datos_usuario = array();
                $usuario = getObjectUser('_id', $item["id_usuario"]);
                if($usuario && $usuario != "") { $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($item["id_usuario"])); }
                if($usuario && $usuario != "") { $datos_usuario = get_array_de_usuario($usuario); }
                $marca = getObjectMarca('nombre_clave', $item["marca"]);
                ?>
                <div class="row card_real">
                	<div class="col-md-3 col-xs-12 card text-center">
                		<a title="Publicado por <?php echo isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario'; ?>" rel="nofollow" href="<?php echo link_usuario(isset($datos_usuario["username"]) ? $datos_usuario["username"] : '', isset($datos_usuario["_id"]) ? $datos_usuario["_id"] : ''); ?>">
                			<div class="col-xs-2 img_usuario" style="width: 40px;height: 40px;border-radius: 50%;background:#d8dce6 url(<?php echo isset($datos_usuario["img"]) ? $datos_usuario["img"] : ''; ?>) no-repeat center;    background-size: 100%;">&nbsp;</div>
                			<div class="col-xs-8" style="text-align:left;font-weight: bold;display:inline-block;">

                					<?php echo strtoupper(isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario'); ?>
                					<br><span style="font-size:10px;color:grey;"><?php echo $item["fecha_publicacion"]; ?></span>

                			</div>
                			<div class="col-xs-2">
                			<span class="hidden-xs" style="font-size:10px;color:grey;"><?php echo $item["provincia"]; ?></span>
                			<span style="font-size:10px;color:grey;"><?php echo $item["localidad"]; ?></span><br>
                			<span style="font-size:10px;color:grey;"><i class="fas fa-eye"></i> <?php echo $item["totalclicks"]; ?></span>
                			</div>
            			</a>
                	</div>

                	<div class="col-md-9 col-xs-12 card" style="position:relative;left:-5px;top:-5px;margin-top:30px;min-height:200px;width:105%;color:white;text-shadow:1px 1px 1px black;background-position: 108% 115%;background: url(<?php echo $marca["imagen"]; ?>)">
                        <p style="position:absolute;bottom:0px;font-size:18px;background: rgba(38,38,38,1);
background: -moz-linear-gradient(top, rgba(38,38,38,1) 0%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(43,43,43,1) 12%, rgba(51,51,51,1) 25%, rgba(8,8,8,1) 100%);
background: -webkit-gradient(left top, left bottom, color-stop(0%, rgba(38,38,38,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(3%, rgba(39,39,39,1)), color-stop(12%, rgba(43,43,43,1)), color-stop(25%, rgba(51,51,51,1)), color-stop(100%, rgba(8,8,8,1)));
background: -webkit-linear-gradient(top, rgba(38,38,38,1) 0%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(43,43,43,1) 12%, rgba(51,51,51,1) 25%, rgba(8,8,8,1) 100%);
background: -o-linear-gradient(top, rgba(38,38,38,1) 0%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(43,43,43,1) 12%, rgba(51,51,51,1) 25%, rgba(8,8,8,1) 100%);
background: -ms-linear-gradient(top, rgba(38,38,38,1) 0%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(43,43,43,1) 12%, rgba(51,51,51,1) 25%, rgba(8,8,8,1) 100%);
background: linear-gradient(to bottom, rgba(38,38,38,1) 0%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(39,39,39,1) 3%, rgba(43,43,43,1) 12%, rgba(51,51,51,1) 25%, rgba(8,8,8,1) 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#262626', endColorstr='#080808', GradientType=0 ); );">

                        	<div class="col-xs-10"><?php echo $item["num_beneficio"]." ".$item["tipo_descuento"]; ?>
                        	 con <a style="color:white;"  title="Códigos de <?php echo $marca["nombre"]; ?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>"><b><?php echo $marca["nombre"]; ?></b></a></span>
                        	<div class="col-xs-2"><a style="color:white;" href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>">Ir al código ></a></div>
                        </p>

                        <div class="hidden-xs">
                        <hr>
                        <p><?php echo $item["descripcion"]; ?></p>
                        <br></div>


                        <?php if($tipo == "mis_codigos") { ?>
                        	<button data-id-codigo="<?php echo $item["_id"]; ?>" style="margin-left: 5px;" class="btn btn-danger desactivar_codigo_usuario">Borrar código</button>


                        <?php } ?>
                        <a title="Códigos de <?php echo $marca["nombre"]; ?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>"></a>
                	</div>
                </div>
                <?php }
            }

        }


        function printa_boton_splash_stripe($c){
            ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <div class="row">
        <div class="col-lg-12" style="margin:10px 0px;">
            <button
                class="btn btn_codigo_amigo btn_interno btn_a_destacar btn_alta_visiblidad"
                style="background-color:#48c500;color:#FFF;padding:8px 12px;border:0;border-radius:4px;font-size:1em"
                id="checkout-button-normal-splash"
                role="link" onclick="ga('send', 'event', 'compra', 'patrocinado 9,99', '9,99');" >
                🥇 Destacar todos mis códigos
            </button>
        </div>
    </div>
    <?php
}


    function printa_boton_stripe($c){


        ?>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

        <div class="row">

            <div class="col-lg-12" style="margin:10px 0px;">
            	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado normal" data-content="Te permite estar primero en los listados de marca" ><i class="fa fa-info " ></i> Que és?</button>*/?>
            	<?php /*?><button style="max-width:inherit !important" class="btn btn_codigo_amigo destacar col-lg-12" data-codigo-id="<?php echo $c; ?>"><i class="fa fa-star"></i> Destacado Normal (0,99€)</button></a>*/?>

            	<!-- Create a button that your customers click to complete their purchase. Customize the styling to suit your branding. -->

 (0,99€)<br><br>
<button
  style="background-color:#48c500;color:#FFF;padding:8px 12px;border:0;border-radius:4px;font-size:1em;border-bottom:3px solid green;"
  id="checkout-button-normal"
  role="link" onclick="ga('send', 'event', 'compra', 'patrocinado 0,99', '0,99');" >
  Destacado Listados de Marca
</button>

            </div>


                <div class="col-lg-12" style="margin:10px 0px;">
                	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado Home" data-content="Multiplica x10 las ganancias de tu código amigo. Te permite estar primero en los listados de marca, de la home y tu código será promocionado en redes sociales" ><i class="fa fa-info" ></i> Que és?</button><?php */?>
                	<?php /*?><button style="max-width:inherit !important" class="btn btn_codigo_amigo destacar social col-lg-12" data-codigo-id="<?php echo $c; ?>"><i class="fa fa-star"></i> Destacar Home + Social (3,99€)</button></a> */ ?>

                	<!-- Create a button that your customers click to complete their purchase. Customize the styling to suit your branding. -->

 (3,99€)<br><br>
<button
  style="background-color:#48c500;color:#FFF;padding:8px 12px;border:0;border-radius:4px;font-size:1em;border-bottom:3px solid green;"
  id="checkout-button-super"
  role="link" onclick="ga('send', 'event', 'compra', 'patrocinado 3,99', '3,99');"
>
  Destacado Página Princial (Home) <br>+ Listados de Marca <br>+ Nuestras Redes Sociales
</button>
                <div style="width:100%;padding-top:10px;text-align:center;">
                <span style="font-size:11px;line-height:12px;font-style=italic;">Pago Seguro y Garantizado!</span><br>
                <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxESEBEQEhIVFRUXGRUXFxgTGhIdFhUXGhgeGhgeHyggGBolHBYXITMhJSkrLjouFx8zOjMvNygtLisBCgoKDg0OGxAQGy8mHyUvMjcyLS03MC8vMDItLS8tLS81LS0tLS0tLS0tLS0tLS0tLi0tLS0tLy0tLTUtLy0tLf/AABEIAH0BkgMBEQACEQEDEQH/xAAcAAEAAgIDAQAAAAAAAAAAAAAABQYEBwECAwj/xABMEAACAQMBBQMGCQcKBQUAAAABAgMABBESBQYTITEiQVEHFFJhcZEWIzJCVIGhsdIVc5KTssHRFzM0U2JjcoKUozVDs+HwJCWi0/H/xAAbAQEAAgMBAQAAAAAAAAAAAAAABAUCAwYBB//EADcRAAIBAgMECAYBBAIDAAAAAAABAgMRBCExBRJBUTJhcYGRobHwExQiwdHhQhUjM/E0UgZicv/aAAwDAQACEQMRAD8A3jQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoDoZlyRqGQMkZ5geOK1urBScW1dZ26jLcla9iIm2+vDyow+QNLfx7+X31Q1dv03RvRX130fr7zJ0cDJTtLTmcttdyyFY20/O5Zz7D6qxntqu6kHGlLd/lln3dnn1HiwkFFpyV+B6Q7YBZtQwPmjHP6/XXtL/yCHxJqtFxX8cs+/rfh1mMsI91bub4mdb3Ssgfpnlgn14q1w2PpVqMardk3bPne1u0jTpOMnE96nGsUAoBQCgMW72jDF/OSonqZgD7uteOSWpqqV6dPpyS7WQ13vtZJ0kLnwRSftOB9ta3WiiHPauFj/K/YQl75SkGeFbs3rdgv2AH760yxVtERZbah/CL78vyRtl5V26S2oPrjfH/xI5++tUca+MTZHaq/lEsFl5R7F8auJGf7SZHvUmt0cXTet0b47SoPV2J2z3htJcaLiIk/N1BT+icHvrdGrB6MkwxNGfRkiTrYbxQCgFAKAUBh7V2pBbRGa4lWNB85jjJ7gB1Zj3Ac6A11tbyz26Ni2tpJhnm7twAR4qNLMfYQKAw4PLb2hrsCF7ys+th7FMag+8UBeN2N+rG+ISKTTL9Hl7D/AFc8P/lJoCy0AoBQCgFAcE0BxqoBqoBqoBqoBqoBqoBrHf7aAiG3ijzyVj6+XOpSwkuZDeMhyOPhGnoN7xXvykuY+djyHwjT0G94p8pLmPnY8h8Ik9BveKfKS5j52PIfCJPQb3inykuY+djyHwjT0G94p8pLmPnY8jL2dtRJiVAIIGcHvFaqtGVNXZtpV41HZGfWk3igFAKAjdqbRCIdDjWCBp69/PlVRtHaMaVNqlJb6ay468iXh8O5STmvpKNvXvZBbyKs3xlzIVVLSMgMSThdbE4jBPd19lVkcDVxtX4uIWfJZJdr59SJTrRox3Yac/x+WVeLb20pby5tJj5gIoTOEh4IMqhlBIuJG0adLFtRJHYYZBFXdHZ9OmreSyXln5kKeIcv3n+vIw7HZe0HbaQe9uS8Mk0Nr2kiMzxRSS5ZHGWBVYhhOfxwPQZrd8nQ4xMfmKnMydk7S2rFYQXrXSygxyzTQzqMRIoZocMvfIFGMg/LWtFXZtGorZrzXg7oyjiZLWz980WjYG9yT8FZozbSyqskQYhknBAOY5BlWxnmOo6ECudxmx5UH8SHDitO9cO7LqJlOtGeXkXew2pln4rKuMYHT2/uqw2fteU5yWKlGOluHPtuaa2GSivhpsllbIBHQ10MWpK6ITVnY5r08FAKA1Tv7/TpfYn7C1GqdJnHbY/5b7F6FZetMiuRjyVpkbYkSKjE5mRFWaNUjMjrNaGiR9Bw/JX2D7quEd0tDvQ9FAKAUBG7w7Zis7aW6lzpQfJHVyThVX1kkD66A+a95N4bi+nM9w2Tz0Rj5EIPzUH1DJ6nHOgI+1tZJWCRRvI56JGrOx/yqCaAzNpbCu7cap7aaNfTeNgv6WMA+rNAR6kgggkEEEEHBBHQgjmCPGgN6+SnfprxTaXJzcIuVk/r0GASe7WMjPjnPjQGxaAUAoBQHWQ8jQELtbbiW7RKySu0pcIkScRjoXU3LPhQEed+LQ6RGZJWfg6EjTnIZhMVADEYYCCXIbGNOOvKgMv4RxmbgpHO7DhiRkjJW3MgBUSkkaWwwJAyQDk4FAd7reGKON5CJCqqzZVc6woJOkZyTgE45ZxUeGKpzlurjpyZJnhakI7ztlquKPC/3st4mC9uQlYGHCXXnzlykIzkc2KkgeAzUgjHEe9kBnW3ZZkctHHl0wqvJFxUQkE4YqD6uWM0BNO/Zf8Awt91ZR6SMZ9FmvoZSxCqCSegHfV5JJK7OcjJyaSMk2s3L4tueMdDnJAGPEZIGa179Pmbfh1eQ82m5dg8+nNef2034cx8Opy9PycrazHmEJ9hB/fT4lPmPhVXovQeazYzoOOucjp49elPiU+Y+FV5eh5XKSR41qVzy5+wH7iPfWUXGXRZjNTh0lYldz5s3DD+7b9pKj42Nqa7fySdnyvVfZ90XOqsuRQCgPC8lZUZlAJAzg/b9lRcZWnRoSqQV2le3r5GylFSmovia7323gkgQcBA95OHMMYxiNUUl5Dk8gApOT4E+FUGAoSxVZ4mas5cuC0v2vh4k6rNU4bi0Xm/wuPgaytbWCS3keSUEyIJmM5WO9tbpY9URiGA1xBMSoCgEYfPZK5PTxioKy0K5ycndltbZl5eOz3L+axkylLOGOJpolnRlmR5dIVBIWLsh1NlskZGaqsZtijQe6s2SaWEnUz0RIxbhQELIfO2ZMaZmu5C6nSFypCADsoo5DooFVv9cxEo78YZdn7NzwcE7OWZE7a3GuBbzw211I0cvAElvcFTxFtyOGqXAX4vAAA1LjkM9K34bb8Ju1RW7Px+Gap4SS0IchJEubm/MkcNu4ig2QkkkQtDEh4ZdwDokKa9DYw8nUgHFX8ZRqR3o5pkXNMtm5u2Zpl82uY5UmVTJA0ycN7qDUQrFe5+XQcskc8NXMbV2cofVDR+T/BY4fEc+/8AJsrYl+0yliqgDABHfyz+8e+rbZeMqYmDc4pJZZe+wj4qjGk0k73JKrQiigFAap39/p0vsT9hajVOkzjtsf8ALfYvQrL1pkVyMeStMjbEiRUYnM2N5Pty+Lpurlfi+scR/wCZ4Mw9HwHf7Os3D4fe+qWhaYHAXtUqLLgh5Qtgw28iyxMq8TOYPDxZR3L6vHp6sq9OMXdceBH2thadNqpF2b4fc2jD8lfYPuqejolod6HooBQCgNLeXbbJaeCyU9mNeK48WfKr9YUN+soDWdnavLJHDGNTuyoq+JY4GT3DJ60B9M7o7sQbPt1hiALEAyTEdqVu8k+HgO4UBNSxqylWAZSCCpGQQeoI7xQHz35U901sLpWhGIJgzIvURspGtPZzBHtI+bQFX2LtRrW4hukzqiYPgfOA+Uv1rkfXQH1XBKHVXXmGAYHxBGRQHegFAKA6TfJb2GgKttzZ8sslvLDMkTwmQgvEZlbiR6D2Q6cwDnrQEDPuesUcoE8JSUQI4u4FnWR+JOSxGtcO8t1kacYIAGc0B7bNsxaNpivkMbGLjrOnFd3jgRXKyh10M8UQYhg2MFhR5hZExG0asiNOjKhOmPlnIYp2jnoGUg8hzGPVUOGFcWryuloiZUxaknaNm9X+iuwbrW7WjWcVzG5eaOcGRROOHCwEMTR6wWiVIwnUZ7R76mEM9ju7HbTpfNLGroY8RpDpQqluIWSOMudDHAZWByvMc1Jzqr4iFGG/N5G2jQnWnuwRL7v7Te4kuWbkBGdKdyjJ958TVfszFyxNeUnplZd5O2jhY0MPGK1zu+eRV9lXka6zIXBK6VKKGI1HD9SMHRlQefys91dtVhJ23ffLzOEoSjG7lfTh5+WXeSlnfxyHCmbXg4CohKKxYyjUGUEdtsHAx2TjlUedOUdbW7+7g+XN8SVTqRnkr37sued1zdslbLLIy0OGOmObSVZccMYUF5COYl08uKowfCtbzWbV+3s6r8DdFbryTtbl1v8A9rceIcDB+LnwQBzjRslVZevEAI7S5X2jvon1rxf48w45aPwXWv8At5HD6y7Fo5efywIkC6SRldGrUoGiPmWIzq5DPMrKKSa8X43t1vhyPGpOTbT68l4WvlouL45GFtdHKdlJcBmfLqo0oI1UDIY68BOvqrbRlFSza5Zc79mWpoxEJOOSet87aWS5u9rHtuDJm7Yf3T/tx1jj1/aXb9mZbM/zvsfqjYVU5fCgFARO18MyIHOc4ZQTjHie7NcxtucKtWFGE3vXs4pu1ub4XJuGvCLk12HznvNvr/7xczNGJoNMtqYNRTMRXQwVhnQ3rAPSrzBU1Gknz9OHkaKz+q3L2/Mum6MhvzDessgEQkituNJx5FGrVNM0mkZYlljQAALhsDIzVbtrHuhD4cNWbsJRU3vS0L5ZIiow0gjHI+j/AOZNcjCsoqSlG7fHkWNR3aadrHus2lSueXf9n8KxhWqKm6a0Zg4qUt485bldGnHPPyvV4Vsi4fC3d36r6+/fXwM403vXvkVHeWFY2TaCRh5LYFiuBmSIAh1BIOHQEujYOCCOhq72PjZU6ipyeUvXg/s+40YvDJx3lqamt9/Ls3ttdSvkRuGIA66kjjlOeup1jBIGF1cwBmupr0lVpuD4lXCW7K59DWEqR3facojASLgkA6+4+rVq9wrlsG40cUnOW6vBN6Z+paS3qmHtFXay7i4A11xUigFAap38/p0vsT9hajVNWcdtj/lvsXoQ+1NkTwBGlQqHGVP7j4N6q1Ti1qR62Dq0EnNa+7dpEyVoka4lr8n24/F03dyvxfWOI/8AM8GYeh6u/wBnXPDYfe+qWnI6XBYK9qlTuRfN6t5IrKLJw0jD4uLx9Z8FH/aplasqa6yfisVGhC714I0/e38k8jSytqdup8PAAdwHhVe5OV29TkcRWnVnvTeZvqH5K+wfdVsjtlod6HpgX8IkkjibnGVkZk9MgoAG8VwzZXoeXhQGO+zbBXWMw2oduaxlIgzexcZPQ+6gPf8AIVp9Fg/VJ/CgPCXdXZ7HU1jasfEwRE+8rQHVd0tnA5FhaA+It4vw0AbYOzg6xm1tA7BiqGKIMwXGogYyQNS59o8aAQbB2c41Ja2jDLLqWKJhlGKsMgdQwII7iCKAw9s7M2PboJLqCxiTOkNLFCoJI6DI5nAPTwoDvbbu7Ik5R2li/ZR8LDA3ZkzobkvyW0tg9Dg+FAZUWy7AyNCLaDUiqSvBUABshcHTg/JPIeFAZH5CtPosH6pP4UB5y2EcJSSFFiOtFZUAVXDsFOpRyJGrIPXl4EggSlAeVz8hvYfuoCE4lAedzGsi6HGpcqSp6HSwYZHeMqOVAYltsuCM5Ve8NzJbJERiGSebDQxGDnx60B0h2TbxppVdKgEHtNhgVRWDEnmCI1znvyfnNk3bNnqTbsiKe9tbY5t11OF0atR0hdTNgn53adm9p61VYna1KnlT+p+X77vEtMNsmrUzqfSvP9d/gQt5ePK2t2yfsHqA7hXOV69SvLem7l/RoQox3YKyLBuOed1+a/jVxsH/ACS7ip22v7Ue/wBCmLLyFfRj5yoGbsu7RGfWSAyOmQNWCw5csjI+utdWLaVuZtpWi3fimSlxta3bPaYEqy8ouz2gwywL5ZxrOk92CPnco8aVRePP9aZZ8+4kTqU5eFtP3m88uXedvyxasWzxF5uoIVGLhljVWPIKpAVuQHhzp8KorWtw+578Wm2734/Y7rti1w4JJDaSPi2GCrIw6SdAU+6vPhVLp/ft6usy+LSs0/Ts6+o8p9sw6ThmLFGVviwvEyjqATrIQDUOYGez4k17GjO+frpnfkYyqwtk/LXJrnl65GR5OHzeN+Zf/qRV5tD/ABLt+zGz42rdz9UbLqmLoUAoCE2pJ22OkghH5n53YOK5PaM97Hq8N2yef/bL7E+iv7evFep8v7K3cW7ivrlrmONoSW4R5vLnWdKg4yxI5DPPnXSqe4oxSIcs5Nm1t09mrLsiOISSRfEx/GQtocZ1SthsHGWcg1ye0q7hj95xUrcJK68Cxpw/spLl9yh7vqs0eyLaQaoXkvGeHJCsQBpJAPPGOX1+NXuLbpTxFWGUkoWfEiU4725F6O5xsZzcR7JtpiXizd5iJODpUlOh7u7wr3EpUZYirTyl9Gfbr48TKkt/cjLTMydhpK1nY3LAskGsmQsvxIjuYpDjJzzjR1GnPUDpWnFSpxxFWksnO2XO8GvVp59ptpRk6cZ8F5WafoXDdu7RxOrfzza5J1IOATlNAbGGCBBHy9D11T4unKLi49BK0e5b17cN7pd5OoyjJNPW+fflbu0NVLuuh2fNe+cxhkfQLdjh25sNQAycHTgZAyQ3hz65VfqUbaoo2rG99izEwbKkZOIzW0BKddXZB+9ia5fEtQxae7vWk8uZaYb6qc1e2mZsZDkA4I9R7q6yLurlU1ZnNengoDXW3b2ODaxllj1qAnLw7C4YDvIrRJ2lc5rFV4Udo79RXVl3dZd5Egu4MHTJE4/89YI99bWlJF+1TxFPnFlU2Z5PkS5Z5W1xLgoh6t/j7sD1dfsqPHDrez0K2hsiFOrvSd0tF+Sa3t3misYsnDSMPi4fH1nwUf8Aatlasqa6ywxOIjRjd68EaWvtoS3ErTStqdup8PAAdwHhVW5OTuzl69WVWW9I7RVmtCJI+g4vkr7B91W6O6Wh3oemLJ/Px/m5f2oqAhdp7secX63MjkRIkGI109t4Z3lXXlCwUEoRpZc4IORyoCHs92NpLGwa5IfMQ1ecTSCQ6Zo7iU6l+KLLMrLCuUVoVwe8Acy7tbS0rpuie3ICOPIpVQkMcDq5Rsuqws5RgQXmcknvA977dy+MTcOdjI09y7KbmaMGOR5uAEcK3DKLIh0hcZXv0igMnZuwbpdoecyuHQcYBzNIzESCHSBCVEcWnhkEqefImgIOTYV/arcSozcmmkihhknm84kkvjPEGj0hYE0FonI7JEzMxGkGgJXeLdm5lsrWGJhJcQ9oXbzyQPHJoIMisqNqyWbKEaSDjligIfau4l27XrgwPJcW1vGLos8TrJFo4mFCEKjlA2QeWleVAee926kkQuHgQGORtmJHGpkZgYrtnkLkAsF+MyWyT1NAeNz5OrxobVDIgWM3J83jm0Lb8abXGYZHgk5ovZBCKw7mHMEDZO0BiNR17cPM9/xqUBl0B0lTUpXxBFAQ52bJ/Z99AeUljP8ANRT7XC/uNYSlNdFedvybIxg+lK3df8GFPYX5+SsC+suWP3AfZUSo8Y+gorxf2JlNYGPTcn3Jfe/mRdzuvfyfLdG9Rc4H1YwKra2z8bW6c0+/7WLKltDBUl/bi13fe9zw+Bd3/d/pn+FaP6NX5r33G7+sYbr8P2PgXd/3f6R/hT+jV+a99w/rGG6/D9ls3a3fFsj6iGd8aiOgAzgD3nnVxgMF8tF3d2ymx+N+ZkrK0UU7aW4Nysh4JR0+bqbSwHgeWD7RXTQx8Gvq1OcngZJ/ToYnwGvvRj/TFZ/PUusx+SqD4DX3ox/pinz1LrHyVTqHwGvvRj/TFPnqXWPkqnUPgNfejH+mKfPUusfJVOofAa+9GP8ATFPnqXWPkqnUWPcjdie2neabSOwUCqdWdTKST4Y0/bUTF4mFSKjHmSMNhpU5b0uRdagE4UAoCG2ujCRWYjScoB0xqBH1+2uZ2vTqxxEKspLd0S4q6fj2+RPwzi4OKWevgfJu8mzHivri2CknikKgBJbUcpgd5ww99X+GqKdKMlyIlaO7No2x5PppYYmsLlGimjGko/XB1MhHcQQxH+Sub23h2qqqL3z+xaYNqdK3L2jpDuQI4bdY7llltzIyTiNSMS4BBQkj1Zz31jLa7qVJuVNOMkrq74aZo9jgrRSUs1xOBuUiR2ywzvHJBxNMulX1cX5WVPLpyH7+tePbEpTm6kE1K2Wa00z9TNYJJR3ZWa49pI2O7SQ20NuHYxJIHbUP50glwp8Bq0nHPkmO8mo1XH1KtWVdxzasurK3e7X8bm2GHjGCpp6Z9vtmBtFEsYry7MmdYdUQgDBklaVuee12n09B8pevWpeHlLEqFPdtay8El3ZLrzZqqRVFynfr8W7efoaju9i3UVwtrJE6SuVCowxr1thSD0ZSehGRXVtpK5SLNn05Z2bC4ighYDzeKKME9BoXny7+yy8q5SEalbFr4TSau7vlp3lpTcYUJSmrpl0FdYiqOaAUBRfKPsgnTdIMgDS+O4Z7J+0j3Vpqx4nPbbwjlatHhk/syr7u7xSWkmR2oz8uLx9Y8GrSpuLK3AY+eGlzi9V911l92tvlbxWwnjYOzg6I+hyOuofNA762zrxUbo6WttClCkqid76L35mldp7RluJWmmbU7dT3DwAHcB4VVSk5O7KKtVlVlvSOkVeojSLRuVsZrm5QY+LQh5D3YB5D2kjHv8KkUYb0jfgcM61ZclmzdVWZ1ooDFvLQsVdG0SLqCsRqBDYyrLyypIU8iDlRz65A8eDd/wBdB+of/wC6gNTbQ8r17FNNFwLc8OSSPViQatDlc41cs4zigPD+We9+j2/+5+KgH8s979Ht/wDc/FQEnu/5SNrXsvBtrO3dgMsSXVUHizFuX3+qgJjeLeTbtlEZ5bSzeMfKeJpX0etgdJx6wDQFU/lnvfo9v/ufioB/LPe/R7f/AHPxUA/lnvfo9v8A7n4qA2FuTt272haLdaoIiWdSnCd8aWxybijORjuoCfSzlZlM0qsFOoIkZjBPcWy7FsdQOXPnz5YAz6AxtpuVgmYHBCOQfDCmsoZyRjPos13HL66sbFadp7xUUs7BVHVicAfXWMnGKvJ2Rsp051ZKEE23wWZEfCO1ZtImXPrDKPeQBWiOMoN23iwnsXHwjvOk7dVm/BNvyPZ8t051liMbh8Mr1ppevgsyDRwtas7U4t++bMK6jcDJBA8axwu0sLiXu0ppvlo/B2Z7XwWIoK9SDS56ryuRkzeurBEI2H5LtrSypPDIxYR6CrMckB9QxnwGj7ag4umotNcSdhKjknF8C5mSoZMHEoBxKAcSgHEoBxKA7Rtk0B6UAoBQEXt+BOGZGJBUYXB6k9OXtqn2xhqU6LqzveKytzfV2kzB1JKaitHqah33gkhuF2nAsbEKEuI5EMisgdXD6QQSUdFfkQeyOfKo2x8amvhy4+v7N2Mw7tvLh6ET+Tl12ktpcyXF3OJZVE5MMbQIZXkHD0nzdWkDaCW08mYle+6xGHjXhuSIVGrKlLeRbt295ll4kCNpm5pLbSdmVCuQw057WD3rnoOnSuVxGDr4W6Wj48P13lrCvSqtN6osEcQELKYZDJ3MF6Z/cNPf6XKo1OnD4TUl9XPL3/vI2SqP4iakre/fcQ+1tqC2gIu5lhjzq4eoGRz4KBn9/jit+HoVJx+HHNdX50E6tKMt9a8/fvrKkJGvJoriWOWOKOaCOG283FwBxk1RS3EbMCYW1csZJJY5yBnpcHg1QV3r6dRVYjEOo7LT1Jbdl5LibzudBHaWTuI7ZBEYnuQWQmArGrGMDBGontMOZwa1bSxKpw3FqzGjTcnkXjc+FJWeV2PFVtRAOB2/tI6j2AVXbIoUqs3Vd95PLstl97k7GylSgqa0aLjXSFUKAUB0ljVlKsAVIIKnmCD1BoeSipKz0NZ717nPCWlgBeLqVHNo/wAS+v8A/aiVKbWa0OYx2ypUm50s48uK/KKVJUVlVEiVqMTmWLdfdqe8fEY0oD2piOyvs9I+ofZW+lSlU08TdQwdSu8slzNz7C2PFaRCKIetmPVz3k1aU6agrI6OhQhRhuxJGszcKAUAoD5T2/8A0y7/AD8//WegMCgFAbM8h+24orma0cHVcaOGwGcmJZGKnw7JJB6cj4igNh+Uzb8NpYTLJkvOkkUaAZ1FkIJJ6AAHJz99AfOFAKAUBv8A8in/AApfzsv7VAXygFAYe2T/AOmn/NyfsGs6fSXaYVOg+w1Es1WtiqI673jurQhIxGY86l1KWydbuc8+uZGH+HFUeMnUoztZbr079TttkYXDY+gpOTU42TtbKysnpo0l33RjLv3c5yVj5lCTh2YaOHpKlnOGHCT247Wqobxk7ZJef5LV7Co2ylLy6+S6/toWrZ148p42eyVdQMmQOJDGW5t2uRhjxz5Y+qqTFbXq/45wi1xTT7V/IhPAwp5Ju99cr6W5cbkk15IQASCB3Fcg9h0OQT0IduQwOnsrB7crztBQjbPKzzvw18LGhbPpx+refblw7imbfv+NO8mQc8sjkD7PVknHqxXd7MoVKWHXxelJuT6m+HcrI5HH1oVazcOikkuxce8t3kbPbvfZB981Z43SPf9hgtZd33LtM/ab2n76ryeaRutw7tr+SARsLR59ZkBULoyxHLOdQV3Qcupq/jj6SoqV/rStbr9pM1bruWXykbCuLq4sIooS0CcnYYCoHdAeRPciHp41DwFeFKE5Sf1PTz+5lJNtHtvJtjaDNPbNskXEJYiNiQVYdxYc+ff1U+w1jQpUEoz+Nuy4+/9ht8ioJuntOCxeBYWZrl4zIisuI1hBK6jnGpmYE4zyjGeuKnvF4edZTbyinbrb/C9THdaRmbwbg7QW1jRbjzhYz8XbpGI9GrOWDE8+fj45rXQx9B1G3Hdvq73Di7G5N25neONpFKuY1LoeqsQNQ+o5FUs0lJpaG1E1WIFAKAUB0liVhhgGHXBGRyrGUIzVpK57GTi7pkHtTdlJ5CxIC4ACAd4H2d3SqrE7KVas6m9bLRE2jjXTgo2uVG+3XkEGpwDHnHClVZgM9+lgQB66p9zGYel8aSsu3PttyLB1KFWe5q/fEin3SiV0Vtn2xZ+ajh6dX1AYFbnisanGDveWmX7NfwMO05K1lrn+iU2RsqdWkjtbeGBl+Xw40iPPp2gBnOO/NYqni8RKUW3dcHl6fkNYemlLKz7/fgSuz90nlSKeSRhJqyyOM9kP0/skgZ8OfSpmG2VeEZSdnfThr71ua6mNjCTjFXVte4ucVrGrFlRVJ6sAAT7cdepq9UIp3SzKtzk1Zs9qyMRQCgFAR+2bx41URLqcnkuNXJRqblkdcBc9xcHnWE5NLI1VZuK+nX3/rvI5ttsWuNLxgJwymoouUZIWJ5uDn4xgMgLkqM1h8TN++Rp+O25Wayt4WXX19hMWtzqSJsMdahs6dOOyDzGez16VsTukSYyuk+ZD3G2WHFKSwsqOAeYDdJMqqluZyqAZxnD+qtbqa2aI7rPOzVl+/fiJttSAyDsrpdRhuQReKqEyHmV1K2oZA5Z8M0dRh1nn2/e2fqe0G13ZouyoV2QHU3TVEzdk4GckDGeuR416pvIyVVtrr/BN1tJAoBQCgPlfeeB4768R1KsJ5jpPg0jMp9hUgj1GgMfZIjM8IlAMetdasxjBXPMFxzXl3/d1oC1y2uygEw8LHizgnVOgI4b8AMOKzLFxNAZgCcZOoCgMO3ttnlY2lMAbWqSRxvORk30RZlySTF5rxBqz1/tYNAem2oNmeb3D24jEmYTGNchYLoQSYRpOR16+R18uhwQQB2eLZirG2IWJFrqi1znTqk03BZw41HR2sALpxjHiBhbbWyNqHgWJZePMCqvIxEYdhEQHkPIqFOcHr1HSgK5QH0N5HrR49kw61K62kkUH0WY6T7CACPURQF1oBQGDt4E2tyAMkxS4A7+wazp9NdphU6D7DSSz+urexUXEkisMMAR4HmKwnTjNbsldG2jXqUZqdOTT5owxZQA5CD68ke4nFRo7Pw6d931LOe39oTjuur4JJ+KV/AzItounyWwPDkR7jXuK2dhsUkq0E7dz8VZ92hAo42vRbdObV9ePrfx1PG82rI40s/LwGBn2461hhdk4TCy3qVNJ882/O9u4yr7QxFdbtSeXLJLy17yLklHjVjYhGxfIqCWvWxy+IGe7I4pIz48x7xULHfx7/sTsGuk+z7lvuJO2/8Aib7zVeTjy4lAOJQDXQDiUBzxKAz9jnLMfV++gJagFAKAUAoBQHBGeteNXBw0YJBIGRnB8M9aOKbue3drHIUZzjn4+yljy5zXoFAKAUAoBQCgFAKAUAoBQCgFAKAUBH7R2HaXDB57aCZgMBpYkkIHgCwOBQGL8ENm/QLT/TxfhoB8ENm/QLT/AE8X4aAfBDZv0C0/08X4aAfBDZv0C0/08X4aAfBDZv0C0/08X4aAfBDZv0C0/wBPF+GgOV3S2cCCLC0BHMEW8XIjp82gJmgFAKAUBiNsu3JJMMRJ6nQvP7Ky35czHcjyOPyVb/1EX6tf4U35cxuR5D8lW/8AURfq1/hTflzG5HkPyVb/ANRF+rX+FN+XMbkeRx+Sbb+oi/Vp/Cm/LmNyPIfki2+jxfq0/hTflzG5HkZMFuiDSiqo9FQFHuFeNt6nqVtDhrZCclFJ8cCvD0481j9BfcKAeax+gvuFAPNY/QX3CgHmsfoL7hQDzWP0F9woD0RABgAAeA5UB2oBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKAUAoBQCgFAKA//2Q=="
                style="max-width:250px;margin-top: 5px;"></div>
                </div>

        </div>
        <?

    }


    function printa_boton_paypal($c){

        echo "En breve...";
        return 1;


        ?>

            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

            <div class="row">

                <div class="col-lg-12" style="margin:10px 0px;">
                	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado normal" data-content="Te permite estar primero en los listados de marca" ><i class="fa fa-info " ></i> Que és?</button>*/?>
                	Destacado normal (0,99€)

                	<script src="https://www.paypal.com/sdk/js?client-id=AbqEsl58r0yQMRtwnyYymSsU1Fw0FZT3MPzEVsIBi3JQSBj-C-j2YStVsFnjkRQbD5MVgKeAXWXZMQ28"></script>
					<script>paypal.Buttons().render('body');</script>


</div>


                    <div class="col-lg-12" style="margin:10px 0px;">
                    	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado Home" data-content="Multiplica x10 las ganancias de tu código amigo. Te permite estar primero en los listados de marca, de la home y tu código será promocionado en redes sociales" ><i class="fa fa-info" ></i> Que és?</button><?php */?>
                    	<button style="max-width:inherit !important" class="btn btn_codigo_amigo destacar social col-lg-12" data-codigo-id="<?php echo $c; ?>"><i class="fa fa-star"></i> Destacar Home + Social (3,99€)</button></a>
                    </div>

                    <div style="width:100%;display:block;padding-top:10px;text-align:center;"><span style="font-size:11px;line-height:12px;font-style=italic;float:right;width:100%;text-align:right">Pago Seguro y Garantizado!</span><img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" style="max-width:250px;float: right;margin-top: 5px;"></div>


            </div>
            <?

        }




    function printa_boton_gratis($c,$marca,$id_codigo,$codigo_to_show=''){

        //print_r($marca);


        ?>

            <div class="row">

                <div class="col-lg-12" style="margin:10px 0px;">
                	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado normal" data-content="Te permite estar primero en los listados de marca" ><i class="fa fa-info " ></i> Que és?</button>*/?>
                	<button style="max-width:inherit !important" class="btn btn_codigo_amigo destacar_gratis col-lg-12" data-codigo-id="<?php echo $c; ?>"><i class="fa fa-star"></i> Destacado Normal (mención en twitter)</button></a>
                </div>


                    <div class="col-lg-12" style="margin:10px 0px;">
                    	<?php /*?><button class="btn btn_codigo_amigo col-lg-2 info_pop" data-toggle="popover" data-placement="left" title="Destacado Home" data-content="Multiplica x10 las ganancias de tu código amigo. Te permite estar primero en los listados de marca, de la home y tu código será promocionado en redes sociales" ><i class="fa fa-info" ></i> Que és?</button><?php */?>
                    	<button style="max-width:inherit !important" class="btn btn_codigo_amigo destacar_gratis_plus social col-lg-12" data-codigo-id="<?php echo $c; ?>"><i class="fa fa-star"></i> Destacar Home + Social (mención en twitter + post en blog)</button></a>
                    </div>

            </div>


            <div class="modal fade" id="destacar_gratis" role="dialog" style="">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <span class="modal-title" style="font-size:20px;">Destaca tu código de manera gratuita</b></span>
                        </div>
                        <div class="modal-body">
                            Puedes patrocinar y destacar tu código de manera gratuita

                            <br>
                            En tal sólo dos pasos
                            <br><br>
                            <h1 class="text-left">Primer paso</h1>
                            	<hr>
                            	<div class="text-left">
                            	👉 Publica tú código en tu cuenta de twitter (mínimo 100 followers)<br>
                            	👉 <a class="btn btn_codigo_amigo btn_interno btn_a_compartir" data-marca="<? echo $marca["nombre"]; ?>" data-descuento="<?php echo $codigo_to_show["num_beneficio"]." ".$codigo_to_show["tipo_descuento"]; ?>"  data-codigo-url="https://www.codigoamigo.com/de-<? echo $marca["nombre_clave"]; ?>" title="Compartir en redes">Compartir <i class="fas fa-user"></i></a><br><br>
                           		👉 Sigue en twitter, pinchando aquí a <a target="_blank" href="https://twitter.com/codigoamigoweb">Código amigo en Twitter</a><br>
                            	</div>

                            <br><br><br>
                            <h1  class="text-left">Segundo paso</h1>
                            <hr>
                            <div class="text-left">
                            	<a href="https://www.codigoamigo.com/contacto" target="_blank">Contáctanos</a> y envíanos el enlace, para que lo validemos
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="destacar_gratis_plus" role="dialog" style="">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <span class="modal-title" style="font-size:20px;">Inicia sesión para <b>ver y publicar códigos</b></span>
                        </div>
                        <div class="modal-body">
                             <div class="modal-body">
                                Puedes patrocinar, destacar tu código, ponerlo en la portada y que le demos viralidad en nuestras redes

                                <br>
                                En tal sólo dos pasos
                                <br><br>
                                <h1>Primer paso</h1>
                                	Haz un artículo genial sobre la marca y explica que beneficios trae este código para los usuarios
                                <br><br>

                                <h1>Segundo paso</h1>
                                	Publica en el post dos enlaces<br><br>

                                	A la marca <br>
                                	<textarea cols=90 style="font-size:10px;"><a href="https://www.codigoamigo.com/de-<? echo $marca["nombre_clave"]; ?>" title="Código <? echo $marca["nombre"]; ?>">Código <? echo $marca["nombre"]; ?></a>
                                	</textarea>
                                	 <br>
                                	A tú código <br>
                                	<textarea cols=90 style="font-size:10px;"><a href="https://www.codigoamigo.com/de-<? echo $marca["nombre_clave"]; ?>?codigo=<?php echo $id_codigo; ?>" title="Código <? echo $marca["nombre"]; ?>">Código <? echo $marca["nombre"]; ?></a>
                                	Código Amigo de <?php echo $marca["nombre_clave"]; ?></a></textarea>
                                	 <br>


                                <br><br>

                                <h1>Tercer paso</h1>
                                <a href="https://www.codigoamigo.com/contacto" target="_blank">Contáctanos</a> y envíanos el enlace, para que lo validemos


                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?

        }

//     function check_buzz(){




//     }


    function muestra_visitas($c) {



        $obj_id_codigo = new \MongoDB\BSON\ObjectId($c);

        $visitas = getVistasCodeById($obj_id_codigo);

        $arrVisitasR = array();

        $i = 0;

        foreach($visitas as $usuario){

            $id_usuario_busca = new \MongoDB\BSON\ObjectId($usuario["id_usuario"]);
            $info_user = get_object_user("_id", $id_usuario_busca);

            $arrVisitasR[$i]["visita"] = iterator_to_array($usuario);

            $arrVisitasR[$i]["usuario"] = $info_user;

            $i++;

        }

        /* RECORRO PARA MONTAR MESES*/
        foreach($visitas as $usuario){

           $usuario_a = iterator_to_array($usuario);

//            echo DateTime::date_create_from_format('d/m/y', $usuario_a["fecha_vista"])->format('Y-m-d')."<br>";


           $claves = preg_split("/[\s,]+/", $usuario_a["fecha_vista"]);

           $usuario_a["fecha_vista"] = $claves[0];
//           echo $usuario_a["fecha_vista"]."<br>";
//             echo $newDate."<br>";

           $sumaVisitas[$usuario_a["fecha_vista"]] ++;
           $sumaVisitasMes[$usuario_a["fecha_vista"]] ++;

        }



        foreach ($sumaVisitas as $strMOnta){

            $data.= $strMOnta.",";
        }

        foreach ($sumaVisitasMes as $field=>$strMOnta){

            $dataMes.= "'".$field."',";
        }

//         echo $data;
//         print_r($sumaVisitasMes);

        /* MONTA TABLA */



        ?>


        <canvas id="myChart" height="50"></canvas>
        <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var chart = new Chart(ctx, {
            // The type of chart we want to create
            type: 'line',

            // The data for our dataset
            data: {
                labels: [<?php echo $dataMes; ?>],
                datasets: [{
                    label: 'Aperturas del Código',
                    backgroundColor: 'rgb(255, 99, 132)',
                    borderColor: 'rgb(255, 99, 132)',
                    data: [<?php echo $data; ?>]
                }]
            },

            // Configuration options go here
            options: {}
        });
        </script>

        <div class="row">
        <table class="table table-hover table_datatable" style="background: white;" id="table_marca">
        <thead style="background: #3466ff !important; color: white; font-size: 14px;">
				<tr>
    				<td>Fecha Código Abierto</td>
    				<td>Usuario</td>
    				<td>Zumbido (disponibles <?php echo $_SESSION["zumbido_saldo"]; ?>)</td>
				</tr>
			</thead>
        <tbody>

        <?

        foreach($arrVisitasR as $datos){
//             echo "<pre>";
//             print_r($datos["usuario"]["_id"]);

            ?>
            <tr>
            	<td><?php echo $datos["visita"]["fecha_vista"]; ?></td>
            	<td><?php if($datos["usuario"]["username"]){?><img width="50px" src="<?php echo $datos["usuario"]["img"]; ?>"><?php echo $datos["usuario"]["username"]; ?><?php }else{ echo "usuario anónimo"; } ?></td>
            	<td><?php

            	/* CHECK BUZZ */
            	//check_buzz($datos["visita"]["fecha_vista"],$c,$datos["usuario"]["_id"]);

            	if($datos["usuario"]["username"]){?>
                    <a class="btn btn-custom envia_buzz" data-codigo-id-user="<?php echo $datos["usuario"]["_id"]; ?>" data-codigo-id="<?php echo $obj_id_codigo; ?>"><i class="far fa-bell"></i> Enviar Zumbido</a>
                    <a class="btn btn-primary envia_chat" onclick="if(window.parent && typeof window.parent.openChatModal === 'function') { window.parent.openChatModal('<?php echo (string)$datos["usuario"]["_id"]; ?>', '<?php echo $datos["usuario"]["username"]; ?>', '<?php echo $datos["usuario"]["img"]; ?>'); } else { window.parent.location.href='/chat?usuario=<?php echo (string)$datos["usuario"]["_id"]; ?>'; }" style="background: #28a745; border-color: #28a745; margin-left: 5px; color: white;"><i class="fas fa-comments"></i> Chatear</a>
                <?php } ?></td>
            </tr>

            <?

        }

        ?></tbody></div><?




    }



    function block_codigo_escogido($c,$a_destacar='') {

        ?>

    	<?php

    		    if($c != "") {
    		        $obj_id_codigo = new \MongoDB\BSON\ObjectId($c);
    		        $codigo_to_show = getCodeByID($obj_id_codigo);
    		        añadir_vista_codigo($codigo_to_show);






    		        $info_user = array();
    		        $u = getObjectUser('_id', $codigo_to_show["id_usuario"]);
    		        if($u && $u != "") { $info_user = get_array_de_usuario($u); }
    		        $marca_codigo = getObjectMarca('nombre_clave', $codigo_to_show["marca"]);

    		        if(isset($codigo_to_show["destacado"])){ $destacado = "destacado"; } else { $destacado = ""; }

    	        ?>

    	        <div class="text-center">
    	        <?php if($a_destacar){?>
    	           <h2>Destacando tu código de  <?php echo $marca_codigo["nombre"]; ?></h2>
    	           <p>Multiplica x10 o x100 el alcance de tú código y empieza a ganar más</p>
    	        <?php }else{?>
            	   <h2>Mostrando el código de  <?php echo $marca_codigo["nombre"]; ?></h2>
            	<?php } ?>
            	</div>
            	<hr><br>
		        <div class="row card_real selected" style="background: #3466ff !important; color: white; margin-bottom: 45px;">
                	<div class="col-md-3 col-xs-3 card text-center">
                		<a class="" title="Publicado por <?php echo $info_user["username"]; ?>" rel="nofollow" href="<?php echo link_usuario($info_user["username"], $info_user["_id"]); ?>">
                			<img style="border-radius: 50%; width: 60px; height: 60px;" class="" src="<?php echo $info_user["img"]; ?>" />
            			</a>
                		<p style="font-size: 70%; font-weight: bold;">
                			<span><?php echo $codigo_to_show["provincia"]; ?></span><br>
                			<span><?php echo $codigo_to_show["localidad"]; ?></span><br>
                			<span><?php echo $codigo_to_show["fecha_publicacion"]; ?></span><br>
                			<span><i class="fas fa-eye"></i> <?php echo $codigo_to_show["totalclicks"]; ?></span><br>
                        </p>
                	</div>
                	<div class="col-md-9 col-xs-9 card">
                		<p><b><?php echo $info_user["username"]; ?></b></p>
                        <p>
                        	<span class="span_descuento" style="color: black;"><?php echo $codigo_to_show["num_beneficio"]." ".$codigo_to_show["tipo_descuento"]; ?></span>
                        	<span> con <b><?php echo $marca_codigo["nombre"]; ?></b>
                    		</span>
                        </p>
                        <hr>
                        <p><?php echo $codigo_to_show["descripcion"]; ?></p>



                        <?php if($a_destacar){?>

                        <hr>
                    		<div class="text-center show_code_last" style="font-size: 16px; padding: 10px;">

                    		 <h2>Tarjeta de crédito</h2>

                       		 <?php printa_boton_stripe($c); ?>

                    		</div>


                    		<br>
                    		<div class="text-center show_code_last" style="font-size: 16px; padding: 10px;">

                    		 <h2>PayPal</h2>

                       		 <?php printa_boton_paypal($c); ?>

                    		</div>

                    		<br>
                    		<div class="text-center show_code_last" style="font-size: 16px; padding: 10px;">

                    		 Gratuitamente

                    		<?php printa_boton_gratis($c,$marca_codigo,$obj_id_codigo,$codigo_to_show); ?>

                    		</div>


                        <?php }else{ ?>

                        <br><br>
                        <div class="text-center show_code_last" style="font-size: 16px; padding: 10px;">

                        	<p>

                        	<?php

                        	/*if(!$_SESSION["user_id"] || $_SESSION["user_id"] == "") { ?>

                        	                        	    		<div class="text-center block_codigo_no_sesion"><p>Por favor, inicia sesión para poder ver este código</p>

                        	                        	    		<button class="btn-custom btn-mini login open_modal_login"><i class="fa fa-user"></i> Iniciar sesión</button>
                        	                        	    		</div>
                        	 <?php }else{*/

                        	if(strpos($codigo_to_show["codigo"], "http") !==false){
                        	    ?><a target="_blank" href="<? echo $codigo_to_show["codigo"]; ?>"><? echo $codigo_to_show["codigo"]; ?></a><?
                        	}else{
                        	    echo $codigo_to_show["codigo"];
                        	}
                        	
                        	?>
                        	</p>
                        	<?php echo "<br><i class='fa fa-clipboard' aria-hidden='true'></i> <a onclick='executeCopy(\"".$codigo_to_show["codigo"]."\",$(this));'>Copiar al portapapeles</a>"; ?>
                       		<hr>
                    		<div class="text-center">
                    			<?php

                    	$share_url = "https://www.codigoamigo.com/de-".$marca["nombre_clave"]."?codigo=".$codigo_to_show["_id"];


                		 // Get current page URL
                		 $crunchifyURL = urlencode($share_url);

                		 // Get current page title
                		 $crunchifyTitle = htmlspecialchars(urlencode(html_entity_decode($title)));
                		 // $crunchifyTitle = str_replace( ' ', '%20', get_the_title());

                		 // Get Post Thumbnail for pinterest
                		 $crunchifyThumbnail = $imagen_social;

                		 // Construct sharing URL without using any script
                		 $twitterURL = 'https://twitter.com/intent/tweet?text='.$crunchifyTitle.'&amp;url='.$crunchifyURL.'&amp;via=Crunchify';
                		 $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u='.$crunchifyURL;
                		 $googleURL = 'https://plus.google.com/share?url='.$crunchifyURL;
                		 $bufferURL = 'https://bufferapp.com/add?url='.$crunchifyURL.'&amp;text='.$crunchifyTitle;
                		 $whatsappURL = 'whatsapp://send?text='.$crunchifyTitle.' '.$crunchifyURL;

                		 $linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url='.$crunchifyURL.'&amp;title='.$crunchifyTitle;

                		 // Based on popular demand added Pinterest too
                		 $pinterestURL = 'https://pinterest.com/pin/create/button/?url='.$crunchifyURL.'&amp;media='.$crunchifyThumbnail[0].'&amp;description='.$crunchifyTitle;

                		 // Add sharing button at the end of page/page content
                		 //$content .= '<!-- Implement your own superfast social sharing buttons without any JavaScript loading. No plugin required. Detailed steps here: http://crunchify.me/1VIxAsz -->';



                		 $content2 .= '<div class="crunchify-social">';
                    		 $content2 .= '<h5>Comparte</h5>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-whatsapp" href="'.$whatsappURL.'" target="_blank">Whatsapp</a>';$content2 .= '<a class="crunchify-link crunchify-linkedin" href="'.$linkedInURL.'" target="_blank">LinkedIn</a>';

                    		 $content2 .=' <a rel="nofollow" class="crunchify-link crunchify-twitter" href="'. $twitterURL .'" target="_blank">Twitter</a>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-facebook" href="'.$facebookURL.'" target="_blank">Facebook</a>';
                    		 $content2 .= '<a rel="nofollow" class="crunchify-link crunchify-pinterest" href="'.$pinterestURL.'" data-pin-custom="true" target="_blank">Pin It</a>';
                		 $content2 .= '</div>';

                		 echo $content2;

                		 ?>
                            </div>

                       		<?php // }?>
                        </div>
                        <?php
                        	    	} ?>
                	</div>
                </div>
    	    <?php } ?>

    <?php }

    function panel_listado_codigos_por_localizacion($marca) {

        global $array_mes;
        $url_marca = link_marca($marca["nombre_clave"]);
        $array_ordenada = order_listado_codigos_localizacion($marca);
        ?>
                <h2>Promociones por Localización <?php echo $marca["nombre"]; ?></h2><hr>
                <ul class="listado_por_fecha text-center row">
                	<?php foreach ($array_ordenada as $index=>$item) { ?>
                		<?php foreach ($item as $index2=>$subitem) { ?>
                			<?php

                			    $nombre = count($subitem)." Codigos descuento de ".$marca["nombre"]." en ".$index;
                			    $title = "Codigos descuento de ".$marca["nombre"]." de ".$index2." del ".$index;
                			    if(count($subitem) > 0) {
                			?>
                            <li class="col-md-3" style="text-align: center !important; font-size: 12px;"><a title='<?php echo $title; ?>' href='<?php echo link_codigos_filtro_localizacion($marca["nombre_clave"], optimizeUrlPath($index2)); ?>'><?php echo $nombre; ?></a></li>
                        	<?php } ?>
                		<?php } ?>
                	<?php } ?>
                </ul>

            <?php }

    function panel_listado_codigos_por_fecha($marca) {

        global $array_mes;
        $url_marca = link_marca($marca["nombre_clave"]);
        $array_ordenada = order_listado_codigos($marca);
    ?>
        <h2>Promociones por fecha <?php echo $marca["nombre"]; ?></h2><hr>
        <ul class="listado_por_fecha text-center row">
        	<?php
        	$i = 0;
        	foreach ($array_ordenada as $index=>$item) { ?>
        		<?php foreach ($item as $index2=>$subitem) {


        		    if($i <= 3){

        			    $nombre = count($subitem)." Codigos descuento de ".$marca["nombre"]." de ".$index2." del ".$index;
        			    $title = "Codigos descuento de ".$marca["nombre"]." de ".$index2." del ".$index;
        			    if(count($subitem) > 0) {
        			?>
                    <li class="col-md-3" style="text-align: center !important; font-size: 12px;"><a title='<?php echo $title; ?>' href='<?php echo link_codigos_filtro($marca["nombre_clave"], $index2, $index); ?>'><?php echo $nombre; ?></a></li>
                	<?php }
        		    }

        		    $i++;

                    } ?>
        	<?php } ?>
        </ul>

    <?php }

    // Función para generar tarjetas de códigos modernas para página de marca
    function block_listado_codigos_marca($lista_codigos, $marca_info) {
        global $detect_device, $url_usuario_sin_imagen, $data_usuario, $provincia, $marca, $num_codigos_global, $keywords, $actual_link;
        
        $html = "";
        
        if(empty($lista_codigos)) {
            $html .= '<div class="no-codes-message text-center py-5">';
            $html .= '<i class="fas fa-search fa-3x text-muted mb-3"></i>';
            $html .= '<h4>No hay códigos disponibles</h4>';
            $html .= '<p class="text-muted">No se encontraron códigos para esta marca.</p>';
            $html .= '</div>';
            return $html;
        }
        
        $html .= '<div class="row codes-grid-marca">';
        
        foreach($lista_codigos as $item) {
            // Obtener datos del usuario
            $datos_usuario = array();
            $usuario = getObjectUser('_id', $item["id_usuario"]);
            if($usuario && $usuario != "") { 
                $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($item["id_usuario"])); 
            }
            if($usuario && $usuario != "") {
                $datos_usuario = get_array_de_usuario($usuario); 
            }
            
            // Obtener información de la marca
            $marca_data = getObjectMarca('nombre_clave', $item["marca"]);
            if($marca_data && isset($marca_data["nombre"])) {
                $marca_data["nombre"] = ucfirst(strtolower($marca_data["nombre"]));
            } else {
                $marca_data = array("nombre" => ucfirst($item["marca"]));
            }
            
            // Badge de destacado
            $destacado_badge = '';
            if($item["destacado"]) {
                $destacado_fecha = transformafechaV2($item["destacado"]);
                $destacado_badge = '<div class="featured-badge-marca">
                    <i class="fas fa-star"></i> Destacado
                </div>';
            }
            
            // Información del usuario
            $user_avatar = isset($datos_usuario["img"]) && $datos_usuario["img"] ? $datos_usuario["img"] : $url_usuario_sin_imagen;
            $username = isset($datos_usuario["username"]) ? $datos_usuario["username"] : 'Usuario';
            // Convertir user_id a string si es un ObjectId
            $user_id_raw = isset($datos_usuario["_id"]) ? $datos_usuario["_id"] : '';
            if ($user_id_raw instanceof MongoDB\BSON\ObjectId) {
                $user_id = (string)$user_id_raw;
            } elseif (isset($datos_usuario["id_string"])) {
                $user_id = $datos_usuario["id_string"];
            } else {
                $user_id = (string)$user_id_raw;
            }
            // Generar enlace al perfil público del usuario
            $user_link = !empty($user_id) && !empty($username) ? link_usuario($username, $user_id) : '#';
            
            // Información de la marca
            $marca_imagen = isset($marca_data["imagen"]) ? $marca_data["imagen"] : '';
            $marca_nombre = $marca_data["nombre"];
            $marca_clave = $item["marca"];
            
            // Beneficio
            $beneficio = isset($item["num_beneficio"]) ? $item["num_beneficio"] : 0;
            $tipo_descuento = isset($item["tipo_descuento"]) ? $item["tipo_descuento"] : 'Descuento';
            
            // Fecha
            $fecha_publicacion = isset($item["fecha_publicacion"]) ? formatDateAgoLarge($item["fecha_publicacion"]) : 'Fecha no disponible';
            
            // Descripción
            $descripcion = isset($item["descripcion"]) ? $item["descripcion"] : '';
            $descripcion = strip_tags($descripcion);
            $descripcion = mb_substr($descripcion, 0, 100) . (mb_strlen($descripcion) > 100 ? '...' : '');
            
            // Clicks
            $total_clicks = isset($item["totalclicks"]) ? $item["totalclicks"] : 0;
            
            // Menú de acciones para códigos propios
            $actions_menu = '';
            if(isset($_SESSION["user_id"]) && $item["id_usuario"] == $_SESSION["user_id"]) {
                if($item["estado"] != '-2') {
                    $actions_menu = '
                    <div class="code-actions-menu">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i> Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="' . link_codigo($item["_id"], $marca_clave, '1') . '" title="Destacar el código">
                                    <i class="fas fa-star"></i> Destacar
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/modificar_codigo/' . (string)($item["_id"]) . '" title="Editar el código">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </li>';
            if($item["estado"] != '-2') {
                $actions_menu .= '
                            <li>
                                <a class="dropdown-item" href="/crear-promocion?codigo_id=' . (string)($item["_id"]) . '" title="Crear promoción temporal">
                                    <i class="fas fa-tag"></i> Crear Promoción
                                </a>
                            </li>';
            }
            $actions_menu .= '
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item open_modal_compartir" data-codigo-url="https://www.codigoamigo.com/de-' . $marca_clave . '?codigo=' . $item["_id"] . '" title="Compartir en redes">
                                    <i class="fas fa-share"></i> Compartir
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item open_modal_estadisticas" data-codigo-id="' . $item["_id"] . '" data-codigo-url="https://www.codigoamigo.com/estadisticas?codigo=' . $item["_id"] . '" title="Estadísticas de tu código">
                                    <i class="fas fa-chart-bar"></i> Estadísticas
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger desactivar_codigo_usuario" data-id-codigo="' . $item["_id"] . '" title="Eliminar código">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </li>
                        </ul>
                    </div>';
                } else {
                    $actions_menu = '
                    <div class="code-actions-menu">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i> Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item text-success restaurar_codigo_usuario" data-id-codigo="' . $item["_id"] . '" title="Restaurar código">
                                    <i class="fas fa-undo"></i> Restaurar
                                </a>
                            </li>
                        </ul>
                    </div>';
                }
            }
            
            $html .= '
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="code-card-marca">
                    ' . $destacado_badge . '
                    
                    <!-- Header con logo de marca y usuario -->
                    <div class="card-header-marca">
                        <div class="brand-logo-marca">
                            <img src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="brand-image-marca">
                        </div>
                        <a href="' . htmlspecialchars($user_link) . '" class="user-info-marca" title="Ver perfil de ' . htmlspecialchars($username) . '">
                            <img src="' . htmlspecialchars($user_avatar) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="user-avatar-marca">
                            <span class="username-marca">' . htmlspecialchars($username) . '</span>
                        </a>
                    </div>
                    
                    <!-- Contenido principal -->
                    <div class="card-content-marca">
                        <h5 class="brand-name-marca">' . htmlspecialchars($marca_nombre) . '</h5>
                        
                        <div class="benefit-section-marca">
                            <div class="benefit-amount-marca">' . $beneficio . '€</div>
                            <div class="benefit-type-marca">' . htmlspecialchars($tipo_descuento) . '</div>
                        </div>
                        
                        <div class="description-marca">' . htmlspecialchars($descripcion) . '</div>
                        
                        <div class="card-meta-marca">
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span>' . $total_clicks . '</span>
                            </div>
                            <div class="meta-item">
                                <i class="far fa-clock"></i>
                                <span>' . $fecha_publicacion . '</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer con botón y acciones -->
                    <div class="card-footer-marca">
                        <a href="' . link_codigo($item["_id"], $marca_clave) . '" class="btn btn-primary btn-block-marca">
                            <i class="fas fa-eye"></i> Ver Código
                        </a>
                        ' . $actions_menu . '
                    </div>
                </div>
            </div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    // Estilos CSS para el menú de acciones de códigos
    function get_code_actions_css() {
        return '
        <style>
        .code-actions-menu {
            margin: 10px 0;
            text-align: center;
        }
        
        .code-actions-menu .dropdown-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .code-actions-menu .dropdown-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .code-actions-menu .dropdown-toggle:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
        }
        
        .code-actions-menu .dropdown-menu {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px 0;
            min-width: 180px;
        }
        
        .code-actions-menu .dropdown-item {
            padding: 10px 16px;
            font-size: 14px;
            color: #333;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .code-actions-menu .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #667eea;
        }
        
        .code-actions-menu .dropdown-item i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        .code-actions-menu .dropdown-item.text-danger:hover {
            background-color: #f8d7da;
            color: #dc3545;
        }
        
        .code-actions-menu .dropdown-divider {
            margin: 8px 0;
            border-top: 1px solid #e9ecef;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .code-actions-menu .dropdown-toggle {
                font-size: 12px;
                padding: 6px 12px;
            }
            
            .code-actions-menu .dropdown-menu {
                min-width: 160px;
            }
            
            .code-actions-menu .dropdown-item {
                padding: 8px 12px;
                font-size: 13px;
            }
        }
        
        /* Estilos para tarjetas de marca */
        .codes-grid-marca {
            margin: 0 -15px;
        }
        
        .code-card-marca {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .code-card-marca:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .featured-badge-marca {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #E30613, #f7931e);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }
        
        .card-header-marca {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 15px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .brand-logo-marca {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .brand-image-marca {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .user-info-marca {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: inherit;
            transition: opacity 0.2s ease;
        }
        
        .user-info-marca:hover {
            opacity: 0.8;
        }
        
        .user-info-marca:hover .username-marca {
            color: #667eea;
        }
        
        .user-avatar-marca {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .username-marca {
            font-size: 12px;
            color: #666;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .card-content-marca {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .brand-name-marca {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .benefit-section-marca {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 12px;
        }
        
        .benefit-amount-marca {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .benefit-type-marca {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .description-marca {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
            margin-bottom: 12px;
            flex-grow: 1;
        }
        
        .card-meta-marca {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .card-footer-marca {
            padding: 15px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }
        
        .btn-block-marca {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-block-marca:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .code-actions-menu {
            margin-top: 8px;
        }
        
        /* Responsive para tarjetas de marca */
        @media (max-width: 768px) {
            .codes-grid-marca {
                margin: 0 -10px;
            }
            
            .code-card-marca {
                margin-bottom: 15px;
            }
            
            .card-header-marca {
                padding: 12px;
            }
            
            .card-content-marca {
                padding: 12px;
            }
            
            .card-footer-marca {
                padding: 12px;
            }
            
            .benefit-amount-marca {
                font-size: 18px;
            }
            
            .brand-name-marca {
                font-size: 14px;
            }
        }
        </style>';
    }

?>