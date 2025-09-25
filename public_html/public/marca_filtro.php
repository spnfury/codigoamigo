<?php 
    get_header_new($title, $description, $title_social, $description_social, $imagen_social); 
    
    global $detect, $url_usuario_sin_imagen,$provincia;
?>



<section id="header_marca" class="bg-image-block parallaxBg" >
	<div class="container">
		<div class="row">
            <div class="col-md-12  text-center">
            	<h1><?php echo $titulo_pagina; ?></h1>
            </div>
		</div>
	</div>
</section>

<div class="container">
    <div class="row" style="margin-top:6px;">
    
    	 <div class="col-md-6 col-sm-6 col-xs-4 text-right">
			<a title="Códigos amigo de <?php echo $marca["nombre"];?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>">
			<img style="max-height:200px;border-radius:10px;" src="<?php echo $marca["imagen"]; ?>" alt="Código de <?php echo $marca["nombre"]?>"></a>
		</div>
		
		<div class="col-md-6 col-sm-6 col-xs-8">
            <ul class="icons-list">
                <li class="">
                	<a target="_blank" href="<?php echo link_categoria($marca["categoria_clave"]); ?>"><?php echo $marca["categoria"]; ?></a>
        		</li>
                <li class=""><a href="<?php echo link_marca($marca["nombre_clave"]); ?>#Que_es_<?php echo $marca["nombre_clave"]; ?>">📲 ¿Que és <?php echo $marca["nombre_clave"]; ?>? </a></li>
                <li class=""><a href="#codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">💰 Ver <?php echo $numero_codigos." Códigos de ".$marca["nombre_clave"].$extra_texto; ?> </a></li>
                
                <li class=""><a href="#codigos_por_localizacion_<?php echo $marca["nombre_clave"]; ?>">📍 Ver Códigos <?php  echo $marca["nombre_clave"]; ?> por localización</a></li>
                <li class=""><a href="#codigos_por_fecha_<?php echo $marca["nombre_clave"]; ?>">🕒 Ver <?php echo "Códigos de ".$marca["nombre_clave"]; ?> por fecha</a></li>
                
            </ul>
    	</div>
    </div>
</div>

<section class="page-heading" style="background: rgba(0, 0, 0, 0.1) url(<?php echo $marca["imagen"]; ?>);">
<div class="container"> 
    <div class="row empieza_home">
    	<div class="col-md-12 columns small-12 slider">
           		<div class="title">
           			<h2>Códigos destacados y con más ventajas para <?php echo $marca["nombre"]?></h2>
           		</div>
           		
            	<div class="destacado_div" >        	 
                	<?php 
                	block_listado_codigos($lista_codigos_patrocinados, "destacados"); ?>         
               	</div>  
            </div>
    	</div>
    </div>
</section>

<div class="container">	
	<div class="row pagina_marcas">
	
	<?php if($marca["nombre_clave"] == 'airbnb') { ?>
    						<h2 class="text-center ultimos_codigos_h2 cd-home-title titulo_zona_home"  id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>"><?php echo $numero_codigos; ?> Créditos de viaje y Códigos amigo para AirBnb <?php echo $extra_texto;?></h2>
    					<?php } else { ?>
    						<h2 class="text-center ultimos_codigos_h2 cd-home-title titulo_zona_home"  id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>"><?php echo $numero_codigos; ?> Cupones y Códigos amigo para <?php echo $marca["nombre"].$extra_texto; ?></h2>
    					<?php } ?> 
    					<?php if($_GET["page"] != "") { ?>
    						<p style="font-size: 15px;">Mostrando del <?php echo $num_inicio; ?> al <?php echo $num_fin; ?></p>    						
    					<?php } ?>
                		                	
    		<div class="listado_codigos">
    			<?php echo block_listado_codigos($lista_muestra, "marca_filtro"); ?>
    		</div>
    		
	</div>
	
	
			<div class="panel panel-info" style="padding: 10px;" id="codigos_por_localizacion_<?php echo $marca["nombre_clave"]; ?>">                     				
				<?php panel_listado_codigos_por_localizacion($marca); ?>				
			</div>	  			
			<div class="panel panel-info" style="padding: 10px;" id="codigos_por_fecha_<?php echo $marca["nombre_clave"]; ?>">                   				
				<?php panel_listado_codigos_por_fecha($marca); ?>				
			</div>		
	
</div>       


     
<?php get_footer(); ?>