<?php

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
global $detect_device,$codigo_existente,$u;

$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');




function show_short_desc($marca){
    
    
    ?>
        
		<div class="texto_long_marca" >
		<p style="display:flex;" ><?php

		     if($marca["descripción"]){
		         echo $marca["descripción"];
		     }else{

			    if($marca["nombre_clave"] == 'airbnb') { texto_airbnb(); }
			    elseif($marca["nombre_clave"] == 'eltenedor') { texto_eltenedor(); }
			    elseif($marca["nombre_clave"] == 'bookingcom') { texto_booking(); }
			    elseif($marca["nombre_clave"] == 'cabify') { texto_cabify(); }
			    elseif($marca["nombre_clave"] == 'mytaxi') { texto_mytaxi(); }
			    elseif($marca["nombre_clave"] == 'yugo') { texto_yugo(); }
			    elseif($marca["nombre_clave"] == 'muving') { texto_muving(); }
			    elseif($marca["nombre_clave"] == 'repsol-waylet') { texto_repsol(); }
			    elseif($marca["nombre_clave"] == 'initiativeq') { texto_initiativeq(); }
			    elseif(muestra_texto($marca["nombre_clave"])){
			        echo muestra_texto($marca["nombre_clave"]);
			    }else {

			        texto_marca_generico();

			    }

		    }
        ?></p>
        </div>
        <?
        
        
    }
    
    function show_long_desc(){
        
        global $marca;
        
        ?>
        
		<div class="texto_long_marca" >
		<p style="display:flex;" ><?php

		     if($marca["descripción_larga"]){
		         echo html_entity_decode($marca["descripción_larga"]);
		     }else{

			    if($marca["nombre_clave"] == 'airbnb') { texto_airbnb(); }
			    elseif($marca["nombre_clave"] == 'eltenedor') { texto_eltenedor(); }
			    elseif($marca["nombre_clave"] == 'bookingcom') { texto_booking(); }
			    elseif($marca["nombre_clave"] == 'cabify') { texto_cabify(); }
			    elseif($marca["nombre_clave"] == 'mytaxi') { texto_mytaxi(); }
			    elseif($marca["nombre_clave"] == 'yugo') { texto_yugo(); }
			    elseif($marca["nombre_clave"] == 'muving') { texto_muving(); }
			    elseif($marca["nombre_clave"] == 'repsol-waylet') { texto_repsol(); }
			    elseif($marca["nombre_clave"] == 'initiativeq') { texto_initiativeq(); }
			    elseif(muestra_texto($marca["nombre_clave"])){
			        echo muestra_texto($marca["nombre_clave"]);
			    }else {

			        texto_marca_generico();

			    }

		    }
        ?></p>
        </div>
        <?
        
        
    }

    if($_GET["codigo"]) {

        $id_codigo = new \MongoDB\BSON\ObjectId($_GET["codigo"]);
        $collection_codigos = getCollectionCodigos();

        $codigo_to_show = $collection_codigos->findOne(['_id' => $id_codigo]);

//         echo "<pre>";
//         print_r($codigo_to_show);
//         print_r($_SESSION);
//         echo $codigo_to_show["id_usuario"]."-----yyy----".$_SESSION["user_id"];


        if($codigo_to_show["id_usuario"] != $_SESSION["user_id"]){
            
            $m = getObjectMarca("nombre_clave", $codigo_to_show["marca"]);
            $u = getObjectUser('_id', $codigo_to_show["id_usuario"]);

            

                if($u["notis"] == 1){
                    
                    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    enviar_mail_apertura_codigo($codigo_to_show, $u["mail"], $u["username"], $actual_link, $m["nombre"], $m["imagen"]);

                }else{

                }

            }

    }

    $link_usuario = str_replace("&nuevo_codigo=1","",$GLOBALS["actual_url"]);
    $link_usuario_de_session = "https://www.codigoamigo.com/usuario_".strtolower($_SESSION["username"])."_".$_SESSION["user_id"];

 if($_GET["nuevo_codigo"] == 1) { ?>
    <style>
        .modal-backdrop {
            z-index: 1050 !important;
        }
        #modal_publicar_codigo {
            z-index: 1051 !important;
        }
        #modal_publicar_codigo .modal-dialog {
            margin-top: 10vh;
        }
        #modal_publicar_codigo .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        #modal_publicar_codigo .modal-body {
            padding: 25px;
        }
        #modal_publicar_codigo h3 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
        }
        #modal_publicar_codigo .social-buttons {
            margin: 25px 0;
        }
        #modal_publicar_codigo .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 10px;
        }
        #modal_publicar_codigo .btn-facebook {
            background: #3b5998;
            color: white;
        }
        #modal_publicar_codigo .btn-twitter {
            background: #1da1f2;
            color: white;
        }
        #modal_publicar_codigo .btn-whatsapp {
            background: #25d366;
            color: white;
        }
        #modal_publicar_codigo .btn-destacar {
            background: #f1c40f;
            color: white;
            margin: 20px 0;
        }
        #modal_publicar_codigo .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        #modal_publicar_codigo .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }
        #modal_publicar_codigo .divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #eee;
            z-index: -1;
        }
        #modal_publicar_codigo .divider span {
            background: white;
            padding: 0 15px;
            color: #95a5a6;
        }
        #modal_publicar_codigo .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #95a5a6;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        #modal_publicar_codigo .close-btn:hover {
            color: #2c3e50;
        }
        #modal_publicar_codigo .benefits {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        #modal_publicar_codigo .benefits ul {
            list-style-type: none;
            padding-left: 0;
        }
        #modal_publicar_codigo .benefits li {
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }
        #modal_publicar_codigo .benefits li:before {
            content: '✓';
            color: #27ae60;
            position: absolute;
            left: 0;
        }
        #modal_publicar_codigo .btn-x {
            background: #000000;
            color: white;
        }
        #modal_publicar_codigo .btn-linkedin {
            background: #0077b5;
            color: white;
        }
        #modal_publicar_codigo .btn-instagram {
            background: #e1306c;
            color: white;
        }
    </style>

    <div id="modal_publicar_codigo" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <i class="fas fa-times close-btn" data-dismiss="modal"></i>
                    <h3>¡ Gracias por publicar tu código !</h3>
                    
                    <div class="benefits">
                        <h4>🚀 ¡Maximiza el alcance de tu código!</h4>
                        <ul>
                            <li>Aumenta tus posibilidades de recibir beneficios compartiendo en redes</li>
                            <li>Consigue más visibilidad y alcanza a usuarios interesados</li>
                            <li>Genera un efecto viral y multiplica tus recompensas</li>
                            <li>Construye una red de referidos activa y rentable</li>
                        </ul>
                    </div>
                    
                    <div class="social-buttons">
                        <div class="row">
                            <div class="col-md-4 col-xs-12">
                                <a class="btn btn-facebook" onclick="window.open(this.href); return false;" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($link_usuario); ?>">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                            </div>
                            <div class="col-md-4 col-xs-12">
                                <a class="btn btn-x" onclick="window.open(this.href); return false;" 
                                   href="https://x.com/intent/tweet?url=<?php echo urlencode($link_usuario); ?>&text=<?php echo urlencode('Aquí está mi código de ' . $marca["nombre"] . ' para ganar ' . $marca["beneficio"] . ' 🎁'); ?>">
                                    <i class="fab fa-x-twitter"></i> X
                                </a>
                            </div>
                            <div class="col-md-4 col-xs-12">
                                <a class="btn btn-whatsapp" href="whatsapp://send?text=<?php echo urlencode($link_usuario); ?>" data-action="share/whatsapp/share">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6 col-xs-12">
                                <a class="btn btn-linkedin" onclick="window.open(this.href); return false;" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($link_usuario); ?>">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </a>
                            </div>
                            <div class="col-md-6 col-xs-12">
                                <a class="btn btn-instagram" href="https://www.instagram.com/create/story" target="_blank">
                                    <i class="fab fa-instagram"></i> Instagram Stories
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="destacar-section">
                        <div class="alert alert-warning">
                            <i class="fas fa-star"></i>
                            <strong>¡Destaca entre la multitud!</strong>
                            <p>Posiciona tu código en las primeras posiciones y multiplica por 10 tu visibilidad</p>
                        </div>
                        <a class="btn btn-destacar" title="Destacar el código" href="<?php echo link_codigo($_REQUEST["codigo"], $marca["nombre_clave"],'1'); ?>">
                            <i class="fas fa-star"></i> Destacar código
                        </a>
                    </div>

                    <div class="divider">
                        <span>o</span>
                    </div>

                    <a class="btn btn-primary" href="<?php echo $link_usuario_de_session; ?>">
                        <i class="fas fa-user"></i> Ver tu perfil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function(){
        $('#modal_publicar_codigo').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
    });
    </script>
<?php }

	
	if($marca["nombre_clave"] == 'traderepublic'){
	    
	    $h1 = "Código invitación Trade Republic";
	    $meta_desc = "";
	    
	    
	}

?>



<section id="header_marca" class="text-center">
	<div class="container">
		<div class="">
            <div class="col-md-12">
            	<?php if($h1!=''){ ?>
            		<h1><?php echo $h1; ?></h1>
            	<?php }else{?>
            		<h1>Código descuento <?php echo $marca["nombre"]; ?></h1>
            	<?php } ?>
            </div>
		</div>
	</div>
</section>

<? if(!$_GET["codigo"]){ ?>
<div class="container" style="background:white !important;position:relative;border-radius: 8px;">
    <div  style="display:flow-root;margin-top:6px;background:white !important;">


<?php 
show_short_desc($marca);
?>

    	<div class="col-md-3 col-xs-12" style="padding:0px !important;">



        	<div class="col-xs-3 col-lg-12" style="padding:0px;">
    			<a title="Códigos amigo de <?php echo $marca["nombre"];?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>">
        			<img class="imagen_principal" src="<?php echo $marca["imagen"]; ?>" alt="Código promocional <?php echo $marca["nombre"]?>">

    			</a>
    		</div>
    		
    		<div class="col-lg-12 col-xs-9" style="padding:.5rem 1rem .5rem 0rem !important"><?php if($marca["nombre"] == 'Bitfinex'){ ?>Códigos descuento y códigos Referral code para <?php echo $marca["nombre"]; ?>
    			<?php }else{?>Descuentos y promociones <?php echo $marca["nombre"]; ?>
    			<?php } ?></div>
    		
    		
        	<?php if($marca["categoria_clave"]){ ?>
                <div class="nav-link col-lg-12 col-xs-9">
            		<a href="<?php echo link_categoria($marca["categoria_clave"]); ?>"><?php echo ucfirst(str_replace("-"," ",$marca["categoria"])); ?></a> > <?php echo $marca["nombre"];?>
    			</div>
    		<?php }?>

    	</div>


    	<div class="col-md-9 columns slider" style="min-height:100px;">

    			


    			<?php    			
    			
    			
    			

    			

    			
    		
    			
    			

    				    ?>
<div>
    <?php if($marca["nombre_clave"] == 'n26'){ ?>
        <div class="col-md-12" style="position:relative;background-image: url('<?php echo $marca["imagen"]; ?>');background-size: 53px;
            background-repeat: repeat;padding:20px;">
            <iframe style="width:100%;" height="200px;" src="https://www.youtube.com/embed/uDmSoH-t4No" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    <?php }elseif($marca["video"]){ ?>
        <div class="row">
            <div class="col-md-6">
                <?php echo ($marca["video"]); ?>
            </div>
            <div class="col-md-6">
                <div id="wide_ad_unit" style="height:600px;">
                    <!-- Codigoamigo_top_marcas -->
                    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                    <ins class="adsbygoogle"
                         style="display:block"
                         data-ad-slot="2215822301"
                         data-ad-format="auto"
                         data-full-width-responsive="true"></ins>
                    <script>
                         (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                </div>
            </div>
        </div>
    <?php }else{ ?>
        
            <!-- Codigoamigo_top_marcas -->
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-slot="2215822301"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
    <?php } ?>
</div>

		</div>


    </div>

	<div class="row">
		<div class="col-lg-12">
	 <nav class="nav">

    		<?php if($marca["nombre_clave"] != 'repsol-waylet'){?>
            <a class="nav-link" href="#Que_es_<?php echo $marca["nombre_clave"]; ?>">📲 ¿Que és <?php echo $marca["nombre_clave"]; ?>? </a>
            <?php }elseif($marca["nombre_clave"] == 'repsol-waylet'){?>
            <a class="nav-link" href="#Que_es_<?php echo $marca["nombre_clave"]; ?>">📲 ¿waylet repsol como funciona? </a>


            <?php } ?>
            
            <?php if($marca["nombre_clave"] == 'royalq'){ ?>
            	<a class="nav-link" href="https://opinionesde.org/royalqbot_review" title="Review y Opiniones de Royal Q"><i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?></a>
            <?php } ?>
            
            <?php if($marca["nombre_clave"] == 'goin'){ ?>
            	<a class="nav-link" href="https://opinionesde.org/goin_review" title="Review y Opiniones de goin"><i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?></a>
            <?php } ?>
            
            <?php if($marca["nombre_clave"] == 'holaluz'){ ?>
            	<a class="nav-link" href="https://opinionesde.org/holaluz_review" title="Review y Opiniones de HolaLuz"><i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?></a>
            <?php } ?>

            <?php if($marca["nombre_clave"] == 'goin'){ ?>
            	<a class="nav-link" href="https://opinionesde.org/goin_review" title="Review y Opiniones de la App Goin"> Opiniones de <?php echo $marca["nombre_clave"]; ?></a>
            <?php } ?>


            <?php if($marca["nombre_clave"] == 'openbank'){ ?>
            	<a class="nav-link btn btn_codigo_amigo btn_interno btn_a_compartir" style="color:white;" href="https://track.adtraction.com/t/t?a=1312208159&as=1434209805&t=2&tk=1" target="_blank">Registrarse en OpenBank</a>
            <?php } ?>

		<?php if($marca["nombre_clave"] == 'getitclub'){ ?>
            	<a class="nav-link btn btn_codigo_amigo btn_interno btn_a_compartir" href="https://getitclub.org/" target="_blank">Get It Club</a>
            <?php } ?>
            
            <?php if($marca["nombre_clave"] == 'royalq'){ ?>
            	<a class="nav-link btn btn_codigo_amigo btn_interno btn_a_compartir" href="http://royalq.wiki/" target="_blank">RoyalQ</a>
            <?php } ?>
            
           <a class="nav-link" href="#codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">💰 Encuentra tu código promocional <?php echo $marca["nombre"]; ?></a>
           <a class="nav-link" href="#alternativas_a_<?php echo $marca["nombre_clave"]; ?>">♻ Alternativas a <?php echo $marca["nombre_clave"]; ?> </a>

            <? if($marca["nombre_clave"] == 'repsol-waylet'){ ?>
           <p>En <b>app para pagar con movil</b>, <b>app repsol waylet</b>, <b>waylet repsol</b></p>
           <?php } ?>

            <?php /*?>
            <li class=""><a href="#codigos_por_localizacion_<?php echo $marca["nombre_clave"]; ?>">📍 Ver Códigos <?php  echo $marca["nombre_clave"]; ?> por localización</a></li>
            <li class=""><a href="#codigos_por_fecha_<?php echo $marca["nombre_clave"]; ?>">🕒 Ver <?php echo "Códigos de ".$marca["nombre_clave"]; ?> por fecha</a></li>

            <?php */ //$numcodes = getNumCodes('marca', $marca["nombre_clave"]); ?>
            <?php /*?><li>Nº de códigos activos: <?php echo $numcodes; ?></li>

            <li></li>*/ ?>

        </nav>
    </div>
    </div>

</div>
<?php } ?>


<?php if(!$_GET["codigo"] && $marca["nombre_clave"] != 'bookingcom') { ?>
<section class="page-heading" style="background: rgba(0, 0, 0, 0.1) url(<?php echo $marca["imagen"]; ?>);">

<div class="container">
    <div class="row empieza_home">
    	<div class="col-md-12 columns small-12 slider">
           		<div class="title">
           			<h2>Códigos Promocionales para <?php echo $marca["nombre"]?></h2>
           		</div>
           		<?php 
           		if($lista_codigos_patrocinados){
           		?>
            	<div class="destacado_div" >
                    <div class="listado_codigos">
                	<?php block_listado_codigos($lista_codigos_patrocinados, "destacados"); ?>
               	    </div>
                </div>
               	
               	<?php } ?>
            </div>
    	</div>
    </div>
</section>
<?php }?>

<div class="container-fluid marca_inside">

    	<?php if(!$_REQUEST["codigo"]){  ?>
    	<div class="container empieza_listado" >
    		<div class="">
            	<div class="col-md-12 card_real">

                    
                                    <!-- Codigoamigo - top -->
                            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                            <ins class="adsbygoogle"
                                 style="display:block"
                                 data-ad-slot="9558662809"
                                 data-ad-format="auto"
                                 data-full-width-responsive="true"></ins>
                            <script>
                                 (adsbygoogle = window.adsbygoogle || []).push({});
                            </script>
                </div>
        	</div>

    </div>
	<?php } ?>



<div class="container empieza_listado">
<div class=" <? if(!$_GET["codigo"]){ echo "pagina_marcas"; }elseif($_GET["codigo"]){ echo "pagina_individual"; } ?>" >

		<div class="div_entro_codigos">



    			<?php 
    			
    			
    			if($numero_codigos == 0) { ?>
    				<div class="text-center" >
    					<h3>Aún no hay códigos de esta marca</h3>
    				</div>
    			<?php } else {

    			if($_GET["codigo"] != "") {
    			    $a_printar = "detalle";
    			}else{
    			    $a_printar = "marca";
    			}

    			if($marca["nombre_clave"] != 'bookingcom'){

    			if($_GET["codigo"] == "") {
    			?>
    					<?php if($marca["nombre_clave"] == 'airbnb') { ?>
    						<h2 class="text-center ultimos_codigos_h2 cd-home-title titulo_zona_home"  id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>"><?php echo $numero_codigos_format; ?> Créditos de viaje y Códigos amigo para AirBnb</h2>
    					<?php } else { ?>
    						<h2 class="text-center ultimos_codigos_h2 cd-home-title titulo_zona_home"  id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>"><?php echo $numero_codigos_format; ?> Cupones y Códigos amigo para <?php echo $marca["nombre"]; ?></h2>
    					<?php } ?>



    					<?php if($_GET["page"] != "") { ?>
    						<h3 class="cd-home-title ">
        							<p style="font-size: 15px;">Mostrando del <?php echo $num_inicio; ?> al <?php echo $num_fin; ?> de un total de <?php echo $numero_codigos_format; ?> códigos</p>
    						</h3>
    					<?php } ?>

    				<?php } ?>

        					<?php
        					
        					if(!$_GET["codigo"]){
        					
            					// Calculamos el tamaño de cada sublista
            					$tamanio_sublista = intdiv(count($lista_codigos), 2);
            					
            					// Calculamos los índices para cortar la lista en 3 partes
            					$indice1 = $tamanio_sublista;
            					$indice2 = $tamanio_sublista * 2;
            					
            					// Dividimos la lista en 3 partes
            					$sublista1 = array_slice($lista_codigos, 0, $indice1);
            					$sublista2 = array_slice($lista_codigos, $indice1, $indice2 - $indice1);
            					$sublista3 = array_slice($lista_codigos, $indice2);
            					
        					}else{
        					    $sublista1 = $lista_codigos;
        					}
        					
        					
        					?>
        					<div class="listado_codigos">
        					<?php block_listado_codigos($sublista1, $a_printar); ?>
        					</div> 
                            <?
        					
                                if($_GET["codigo"] && !$detect_device->isMobile()){ ?>

                                <div class="col-lg-3">
                                	
                                    <!-- Codigoamigo Detalle Lateral -->
                                    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                    <ins class="adsbygoogle"
                                         style="display:block"
                                         data-ad-slot="2861865272"
                                         data-ad-format="auto"
                                         data-full-width-responsive="true"></ins>
                                    <script>
                                         (adsbygoogle = window.adsbygoogle || []).push({});
                                    </script>
                                </div>
                                <?php }



        					}else{ //ELSE BOOKING

        					    ?>

        					    <h3>Booking ya no ofrece Códigos de amigo, pero tenemos grandes ofertas para tí de booking!</h3>


                                <?php if($marca["nombre_clave"] == 'bookingcom'){ ?>
                                <ins class="bookingaff" data-aid="1917518" data-target_aid="1917518" data-prod="dfl2" data-width="100%" data-height="auto" data-lang="es" data-df_num_properties="9">
                                    <!-- Anything inside will go away once widget is loaded. -->
                                        <a href="//www.booking.com?aid=1917518">Booking.com</a>
                                </ins>
                                <script type="text/javascript">
                                    (function(d, sc, u) {
                                      var s = d.createElement(sc), p = d.getElementsByTagName(sc)[0];
                                      s.type = 'text/javascript';
                                      s.async = true;
                                      s.src = u + '?v=' + (+new Date());
                                      p.parentNode.insertBefore(s,p);
                                      })(document, 'script', '//aff.bstatic.com/static/affiliate_base/js/flexiproduct.js');
                                </script>
                                        <?php }
                                        
                          if($_GET["codigo"] && !$detect_device->isMobile()){ ?>
        					 <div class="col-lg-3">
                                	
                                    <!-- Codigoamigo Detalle Lateral -->
                                    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                    <ins class="adsbygoogle"
                                         style="display:block"
                                         data-ad-slot="2861865272"
                                         data-ad-format="auto"
                                         data-full-width-responsive="true"></ins>
                                    <script>
                                         (adsbygoogle = window.adsbygoogle || []).push({});
                                    </script>
                                </div>
                            <?php }
                            



                            }

                ?></div><?php
                
                
                
                if($_GET["codigo"]){
                    
                    ?></div><?
                    
                }

                //show_buttons_paginate($numero_codigos, $codigos_restantes,$marca);

    			}


    			if(!$_GET["codigo"]){
    			if($marca["nombre_clave"] == 'traderepublic'){
    			
    			?>
    			
    			
    			<style>

  .accordion {
            background-color: #eee;
            color: #444;
            cursor: pointer;
            padding: 12px;
            width: 100%;
            border: none;
            text-align: left;
            outline: none;
            font-size: 15px;
            transition: 0.4s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .active, .accordion:hover {
            background-color: #ccc;
        }

        .panel_faq {
            padding: 18px 18px;
            display: none;
            background-color: white;
            overflow: hidden;
        }
        
        .panel_faq p{
	font-size:14px !important;
        }

        .arrow {
            font-size: 12px;
            transition: transform 0.4s;
        }

        .active .arrow {
            transform: rotate(90deg);
        }

        .faq-item {
            margin-bottom: 5px;
        }

        .accordion h2 {
            margin: 0;
        	font-size:18px !important;
        }
    </style>

<h2 class="text-center ultimos_codigos_h2 cd-home-title titulo_zona_home">Preguntas Frecuentes - Código Invitación Trade Republic</h2>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Qué es un código de invitación de Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Un código de invitación de Trade Republic es un código promocional que puedes usar al registrarte para obtener beneficios adicionales, como descuentos en comisiones o bonos de bienvenida.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Cómo puedo obtener un código de invitación para Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Puedes obtener un código de invitación para Trade Republic a través de un amigo que ya esté registrado en la plataforma, en foros de discusión como Forocoches, o en sitios web que ofrecen promociones.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Cómo utilizo un código de invitación en Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Para usar un código de invitación en Trade Republic, debes ingresarlo durante el proceso de registro en la aplicación. Busca el campo específico para "Código de Invitación" y escribe el código proporcionado.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Cuál es el beneficio de usar un código de invitación en Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Usar un código de invitación en Trade Republic puede ofrecerte varios beneficios, como comisiones reducidas, bonos de bienvenida, o recompensas adicionales por tus inversiones.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Puedo usar varios códigos de invitación en Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>No, solo puedes usar un código de invitación por cuenta al registrarte en Trade Republic. Asegúrate de utilizar el código que te ofrezca los mejores beneficios.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Dónde puedo encontrar un código promocional de Trade Republic 2024?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Los códigos promocionales de Trade Republic para 2024 se pueden encontrar en sitios web especializados en promociones, foros como Forocoches, o a través de campañas promocionales de Trade Republic.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Qué es un código amigo de Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Un código amigo de Trade Republic es un código de referencia que puedes compartir con tus amigos para que se registren en la plataforma. Ambos pueden obtener beneficios adicionales cuando se utiliza el código durante el registro.</p>
</div>
</div>

<div class="faq-item">
    <button class="accordion">
        <h2>¿Qué hacer si olvidé mi código PUK de Trade Republic?</h2>
        <span class="arrow">▶</span>
    </button>
    <div class="panel_faq">
        <p>Si olvidaste tu código PUK de Trade Republic, debes contactar con el soporte técnico de Trade Republic para obtener asistencia. Te ayudarán a recuperar el acceso a tu cuenta.</p>
</div>
</div>

<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    });
}
</script>

    			<?php } //END TRADEREPUBLIC ?>
    			
    			
    			
    		
            
            <div><h2 class="titulo_zona_home cd-home-title ultimos_codigos_h2">Cupones descuento para <?php echo $marca["nombre"];?></h2></div>
            
            
           <?php 
            
            block_listado_codigos($sublista2, $a_printar);
            
            ?>
            
            
            <div><h2 class="titulo_zona_home cd-home-title ultimos_codigos_h2">Promociones para <?php echo $marca["nombre"];?></h2></div>
            
            <?php 
            
            block_listado_codigos($sublista3, $a_printar);
            
    		
    			?>
    			
             <div class="" id="alternativas_a_<?php echo $marca["nombre_clave"]; ?>"><h2 class="titulo_zona_home cd-home-title ultimos_codigos_h2">Alternativas a <?php echo $marca["nombre"]; ?></h2></div>
                <div class="listado_codigos">
                    <?php 
                    
                    
                    bloque_marcas_home($marca["categoria_clave"],$marca->_id); ?>
</div>

                <div class="text-center bloque_publica_nuevo_codigo">
                    <a class="btn btn_codigo_amigo" href="<?php echo link_listado_marcas(); ?>">
                        Ver todas las marcas
                    </a>





    			    		<?php
    			    		
    			    		
    			    		
    			    		
    			}
    			
    			

    		if(!$_GET["codigo"]){ ?>

		<div class="col-md-12">
			<div class="panel panel-info menu-derecho">
				<div class="panel-body">

					<div class="col-md-12">

						<div class="col-md-7">
							<img class="img-thumbnail" src="<?php echo $marca["imagen"]; ?>" alt="Código de <?php echo $marca["nombre"]?>">
</div>

						<div class="col-md-5 pre_publicar hidden-xs">
							<?php echo publica_tu_codigo($marca,$codigo_existente); ?>
</div>

</div>

					<div class="col-md-12">
					
					<?php show_long_desc(); ?>

    					<h2 id="Que_es_<?php echo $marca["nombre_clave"]; ?>">📲  ¿ Qué es <?php echo $marca["nombre_clave"]; ?>?</h2>

                        <div class="col-md-12">
						<h2>Relacionado con <?php echo $marca["nombre_clave"]; ?></h2>
						<div id="wide_ad_unit2"></div>
</div>
</div>

					<?php /*?>
					<p class="textos_xs text-justify">
						<?php echo $marca["descripción"]; ?>
					</p>
					<? */ ?>

					<?php /*?>
					<a href="<?php echo link_blog_marca($marca["nombre_clave"

					]); ?>">
						<button class="btn btn-product" value="Más información" type="button">Más información</button>
					</a>*/?>
</div>
</div>
</div>


		<?php }

		if($_GET["codigo"]){ // SI EXISTE CODIGO SUMO VISITA

    		$obj_id_codigo = new \MongoDB\BSON\ObjectId($_GET["codigo"]);
    		$codigo_to_show = getCodeByID($obj_id_codigo);
    		añadir_vista_codigo($codigo_to_show);


    		/* AÑADO HISTORIAL VISITA*/
    		añadir_historial_codigo($codigo_to_show);
    		//echo "a";die;

    		addVista($codigo_to_show);


		}

		?>

</div>


<?php


if($marca["nombre"] == "cabify" || $marca["nombre"] == "socialcar" || $marca["nombre"] == "ubeeqo" || $marca["nombre"] == "uber"){

    $quiza = 1;
    $quiza_coche = 1;

}

if($marca["nombre"] == "yego"  || $marca["nombre"] == "acciona"  || $marca["nombre"] == "ecooltra"){
    $quiza = 1;
    $quiza_moto = 1;
}

if($marca["nombre"] == "reby"){
    $quiza = 1;
    $quiza_patin = 1;
}

if($quiza){


?>



	<div class="<?php if($_REQUEST["codigo"]){ echo "col-md-12"; }else{ echo "col-md-12"; } ?>">

	<?php
	$marca["nombre"] = strtolower($marca["nombre"]);
	?>

	<div class="cd-home-title titulo_zona_home ultimos_codigos_h2">Quizá te interese <?php echo $marca["nombre"]; ?></div>

    <div class="col-md-12 panel panel-info menu-derecho">
        	<?php if($marca["nombre"] == "cabify" || $marca["nombre"] == "socialcar" || $marca["nombre"] == "ubeeqo" || $marca["nombre"] == "uber"){?>
        	<div class="titulo_zona_home">Coches Casinuevos</div>
        	<p>Si estas pensando en comparte tu propio coche encuentra gangas cerca de tí en tiempo record encuentra tu
        	<a class="underline" href="https://www.casinuevo.com/coches-de-segunda-mano/" title="coches de segunda mano" target="_blank">coches de segunda mano</a>.
        	</p>
        	<?php } ?>

        	<?php if($marca["nombre"] == "yego"  || $marca["nombre"] == "acciona"  || $marca["nombre"] == "ecooltra"){?>
        	<div class="titulo_zona_home">Motos Casinuevas</div>
        	<p>Si estas pensando en comparte tu propia moto no lo dudes y busca y encuentra gangas cerca de tí, encuentra
        	<a class="underline" href="https://www.casinuevo.com/categoria/motor/motos-de-segunda-mano/" title="motos de segunda mano" target="_blank">motos de segunda mano</a>.
        	</p>
        	<?php } ?>

        	<?php if($marca["nombre"] == "reby"){?>
            	<div class="titulo_zona_home">Patines electricos Casinuevos</div>
            	<p>Si estas pensando en comprar tu propio patín eleéctrico de segunda mano moto no lo dudes y busca y encuentra
            	<a class="underline" href="https://www.casinuevo.com/patinete-electrico.html" title="patin electrico de segunda mano" target="_blank">patin eléctrico de segunda mano</a> cerca de tí.
            	</p>
        	<?php } ?>

            

</div>

</div>

</div>

<?php

}

 if($_GET["codigo"]) { 

    // Check if code belongs to premium user
    
    if($u["pro_user"]==1) {  ?>
    <style>
        #premiumUserModal {
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        #premiumUserModal.fade {
            display: none;
        }
        
        #premiumUserModal.fade.in {
            display: flex;
        }
        
        .modal-backdrop.fade.in {
            z-index: 1040;
        }
        
        #premiumUserModal .modal-dialog {
            position: relative !important;
            width: 90% !important;
            max-width: 400px !important;
            margin: 0 auto !important;
            transform: none !important;
        }

        @media (max-width: 480px) {
            #premiumUserModal .modal-dialog {
                width: 95% !important;
                margin: 10px auto !important;
            }
            
            #premiumUserModal .modal-content {
                max-height: 90vh;
                overflow-y: auto;
            }
        }
        
        #premiumUserModal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        #premiumUserModal .modal-header {
            background: linear-gradient(135deg, #2980b9, #2c3e50);
            border: none;
            padding: 25px 25px 60px 25px !important;
            position: relative;
            text-align: center;
        }

        #premiumUserModal .modal-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            padding-top: 10px;
        }

        #premiumUserModal .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: white;
            opacity: 0.8;
            font-size: 28px;
            font-weight: 300;
            text-shadow: none;
        }

        #premiumUserModal .user-profile {
            text-align: center;
            margin: -50px auto 20px;
            position: relative;
            width: 100%;
            padding: 0 20px;
        }

        #premiumUserModal .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            object-fit: cover;
        }

        #premiumUserModal .premium-badge {
            white-space: nowrap;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: #f1c40f;
            color: #2c3e50;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(241,196,15,0.3);
        }

        #premiumUserModal .modal-body {
            padding: 20px 30px;
        }

        #premiumUserModal .username {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 15px 0;
            text-align: center;
        }

        #premiumUserModal .intro-text {
            text-align: center;
            color: #34495e;
            margin-bottom: 20px;
        }

        #premiumUserModal .benefits-list {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 0;
        }

        #premiumUserModal .benefits-list li {
            color: #2c3e50;
            padding: 10px 0 10px 35px;
            position: relative;
            list-style: none;
            margin: 5px 0;
            font-size: 0.95rem;
        }

        #premiumUserModal .benefits-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
            background: #e8f6ef;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        #premiumUserModal .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #premiumUserModal .btn-telegram {
            background: #0088cc;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
            border: none;
            transition: all 0.3s ease;
        }

        #premiumUserModal .btn-telegram:hover {
            background: #006699;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,136,204,0.2);
        }

        #premiumUserModal .btn-default {
            background: transparent;
            color: #95a5a6;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 0.9rem;
            border: none;
            transition: all 0.3s ease;
        }

        #premiumUserModal .btn-default:hover {
            color: #7f8c8d;
            background: #f8f9fa;
        }
    </style>
    <div id="premiumUserModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">¡Maximiza tus beneficios!</h4>
</div>
                <div class="modal-body">
                    <div class="user-profile">
                        <img src="<?php echo $u['img'] ? $u['img'] : '/assets/img/default-avatar.png'; ?>" 
                             alt="<?php echo $u['username']; ?>" 
                             class="user-avatar">
                        <span class="premium-badge">
                            <i class="fas fa-crown"></i> Verificado
                        </span>
</div>
                    <p class="username"><?php echo $u["username"]; ?></p>
                    <p class="brand-intro">
                        <img src="<?php echo $marca['imagen']; ?>" alt="<?php echo $marca['nombre']; ?>" class="brand-icon">
                        Experto en <?php echo $marca['nombre']; ?>
                    </p>
                    <ul class="benefits-list">
                        <li>Máximo beneficio garantizado</li>
                        <li>Evita errores en el registro</li>
                        <li>Trucos exclusivos</li>
                        <li>Soporte personalizado</li>
                    </ul>
</div>
                <div class="modal-footer">
                    <a href="https://t.me/spnfury?text=<?php echo urlencode('Hola ' . $u['username'] . ', necesito ayuda con el código de ' . $marca['nombre']); ?>" 
                       target="_blank" 
                       class="btn btn-telegram">
                        <i class="fab fa-telegram"></i> Chatear por Telegram
                    </a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Configurar solo</button>
</div>
</div>
</div>
</div>

    <style>
        @media (max-width: 480px) {
            #premiumUserModal .modal-dialog {
                margin: 10px;
            }
            
            #premiumUserModal .modal-body {
                padding: 15px;
            }
            
            #premiumUserModal .user-avatar {
                width: 80px;
                height: 80px;
            }
            
            #premiumUserModal .premium-badge {
                font-size: 0.75rem;
                padding: 3px 10px;
            }
            
            #premiumUserModal .brand-intro {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.9rem;
            }
            
            #premiumUserModal .brand-icon {
                width: 20px;
                height: 20px;
                object-fit: contain;
            }
            
            #premiumUserModal .benefits-list {
                padding: 15px;
            }
            
            #premiumUserModal .benefits-list li {
                font-size: 0.85rem;
                padding: 8px 0 8px 30px;
            }
            
            #premiumUserModal .modal-footer {
                padding: 15px;
            }
            
            #premiumUserModal .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>

    <?php }
} ?>

<div class="text-center" style="margin: 20px 0;">
    <a href="<?php echo link_marca($marca["nombre_clave"]); ?>" class="btn btn-primary">
        <i class="fas fa-list"></i> Ver todos los códigos de <?php echo $marca["nombre"]; ?>
    </a>
</div>

<?

// Incluir el footer si no se ha incluido ya
if (!function_exists('get_footer')) {
    include_once __DIR__ . '/../myphp/_footer.php';
}
get_footer();
?>