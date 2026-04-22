<?php 


function bloque_info_home() { ?>
    
    <div class="row">
    <div class="container text-center bloque_primero_home ">          		       			 
        <div class="row">
    		
        	<div class="collapse" id="video_codigo_amigo">
    			<div class="card card-block">
    			<video id="codigoamigo" width="640" height="360" controls preload="none">
    			<source src="/img/codigoamigo.mp4" />
</video></div>
    		</div> 
        </div>    
        <div class="row bloque_1_home panel panel-default" style="padding:10px;">
        	
        	
        
        	<div class="col-md-4 col-xs-6 text-center">
        		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img1.png" /><br>
        		<p class="text-justify">
        			<span class="number">1</span> 
        			Sube tu código amigo. ¡No te llevará más de 10 segundos y es completamente <b>gratuito</b>!
    			</p>
        	</div>
        	<div class="col-md-4 col-xs-6 text-center">
        		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img2.png" /><br>
        		<p class="text-justify">
        			<span class="number">2</span> 
        			¡Comprueba como tu publicación llega a muchísima gente! <b>Los usuarios podrán acceder a tu código</b> y obtendréis muchísimas ventajas !!
    			</p>
        	</div>
        	<div class="col-md-4 col-xs-12 text-center">
        		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img3.png" /><br>
        		<p class="text-justify">
        			<span class="number">3</span> 
        			Olvídate de ir buscando amigos para ganar descuentos y deja que nosotros te hagamos el trabajo <b>¡A disfrutar!</b>
    			</p>
        	</div>
        	
        	<div class="txt-center col-md-12">
            	<a class="btn" data-toggle="collapse" href="#video_codigo_amigo" aria-expanded="false" aria-controls="video_codigo_amigo">
    				<i class="fa fa-play" aria-hidden="true"></i> 
    				<span>Ver video - ¿Qué es Código Amigo?</span>
    			</a>
			</div>
        	
        </div>    
        <?php publica_tu_codigo(); ?>               
    </div>
    </div>
    
    <?php }
    

    
    
    function bloque_info_home_mobile() { ?>
        
        <div class="container text-center bloque_primero_home">
            <div class="row">            
            
    		<a class="btn btn_codigo_amigo" data-toggle="collapse" href="#video_codigo_amigo" aria-expanded="false" aria-controls="video_codigo_amigo">
				<i class="fa fa-play" aria-hidden="true"></i> 
				<span>¿Qué es Código Amigo?</span>
			</a>
			
			<br><br>
    			
            <div class="collapse" id="video_codigo_amigo">
            
        			<div class="card card-block">
        				<video id="codigoamigo" width="100%" preload="none" controls preload="none">
        				<source src="/img/codigoamigo.mp4" /></video>
            		</div>    
            		
                    <div class="row bloque_1_home">
                    	<div class="col-md-4 col-xs-6 text-center">
                    		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img1.png" /><br>
                    		<p class="text-justify">
                    			<span class="number">1</span> 
                    			Sube tu código amigo. ¡No te llevará más de 10 segundos y es completamente <b>gratuito</b>!
                			</p>
                    	</div>
                    	<div class="col-md-4 col-xs-6 text-center">
                    		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img2.png" /><br>
                    		<p class="text-justify">
                    			<span class="number">2</span> 
                    			¡Comprueba como tu publicación llega a muchísima gente! <b>Los usuarios podrán acceder a tu código</b> y obtendréis muchísimas ventajas !!
                			</p>
                    	</div>
                    	<div class="col-md-4 col-xs-12 text-center">
                    		<img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img3.png" /><br>
                    		<p class="text-justify">
                    			<span class="number">3</span> 
                    			Olvídate de ir buscando amigos para ganar descuentos y deja que nosotros te hagamos el trabajo <b>¡A disfrutar!</b>
                			</p>
                    	</div>                    	
                	</div> 
              
            		<?php publica_tu_codigo(); ?>  
            
            
            	</div> 
            
            
            </div>    
                      
        </div>
        
        <?php }
    
    
    
    function printa_bloque_marcas($array_marcas) {
        
        
        ?>
        
        <div id="listado_marcas" class="" style="margin-right: 0px; margin-left: 0px;">
        <?php
        
        foreach ($array_marcas as $marca) {
            

            if($marca["imagen"]!='' && $marca["imagen"]!='Sin imagen'){
                
                if(!$marca["descripción"]){
                    
                    /* PATROCINADOS */
                    $array_filtro = array("marca"=>$marca["nombre_clave"]);
                    $array_filtro = array_merge($array_filtro, array("estado"=>0));
                    
                    $array_skip = array("limit"=>1);
                    $array_skip = array_merge($array_skip, array("skip"=>isset($GLOBALS['skip_patrocinados']) ? $GLOBALS['skip_patrocinados'] : 0));
                    
                    
                    //TOMO LOS CODIGOS
                    $lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
                    
                    
                }
                
                //$marca["descripción"] = recorta_texto_pos($marca["descripción"],50,"...");

            ?><div class="panel panel-default" style="min-height:230px;">
                		<div class="panel-body text-left">
                			<a title="Códigos descuento <?php echo htmlspecialchars($marca["nombre"]); ?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>">
                				<div class="pre_div_img"><img class="lazyload" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="<?php echo $marca["imagen"]; ?>" alt="Código amigo de <?php echo $marca["nombre"]; ?>"></div>
                				<div class="pre_p">
                				<h2><?php echo $marca["nombre"]; ?></h2>
                				<?php if($marca["descripción"]){?>
                					<span class='desc'><?php echo $marca["descripción"]; ?></span>
                				<?php }else{ ?>
                					<span class='desc'></span>
                				<?php }?>
                				</div>
                			</a>
                		</div>
            		</div>
            	<?php }
            	
        }
            	
            	?>
            	
            	
            	</div>
            	
            	<div class="text-center bloque_publica_nuevo_codigo">
            <a class="btn btn_codigo_amigo" href="<?php echo link_listado_marcas(); ?>">Ver todas las marcas</a>
        </div>
            	
            	
        <?php
            	    	
    }
    
    function bloque_marcas_home($categoria='', $excluye=null, $num_marcas=12) {
        
        global $detect_device,$str_marcas;

        if(!$num_marcas){
            $num_marcas = 12;
            if ($detect_device->isMobile()) { $num_marcas = 4;}    
        }
        
        
        $lista_marcas = getMarcas($num_marcas,$categoria,$excluye);
        
        $array_marcas = array();
        
        foreach ($lista_marcas as $marca) {
    
            $marca["imagen"] = str_replace("http://","https://",$marca["imagen"]);
             
            // Usar el número de códigos que ya viene calculado en getMarcas
            $num_codes = $marca["numero_codigos"] ?? 0;
            
            
            /* PATROCINADOS */
            $array_filtro = array("marca"=>$marca["nombre_clave"]);
            $array_filtro = array_merge($array_filtro, array("estado"=>0));
            
            $array_skip = array("limit"=>1);
            $skip_patrocinados = isset($GLOBALS['skip_patrocinados']) ? $GLOBALS['skip_patrocinados'] : 0;
            $array_skip = array_merge($array_skip, array("skip"=>$skip_patrocinados));
            
             
            //TOMO LOS CODIGOS
            $lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
            
            
            if(!$marca["descripción"]){
                if(is_array($lista_codigos_patrocinados_pre) && isset($lista_codigos_patrocinados_pre["results"]) && is_array($lista_codigos_patrocinados_pre["results"]) && count($lista_codigos_patrocinados_pre["results"]) > 0){
                    $marca["descripción"] = recorta_texto_pos($lista_codigos_patrocinados_pre["results"][0]["descripcion"],120,"...");
                } else {
                    $marca["descripción"] = "Descripción no disponible";
                }
            }
            
            

            $elemento = array('nombre' => $marca["nombre"],
                'codes' => $num_codes,
                'nombre_clave' => $marca["nombre_clave"],
                'descripción' => $marca["descripción"],
                'imagen' => $marca["imagen"]
            );
            
            
            array_push($array_marcas, $elemento);
            
            
            $str_marcas.= " ".$marca["nombre"];
            
            
        }
    
        // Las marcas ya vienen ordenadas por número de códigos desde getMarcas
        $array_chunck= array_chunk($array_marcas, $num_marcas); // Truncamos array a partir del elemento especificado
        $arrayfinal = isset($array_chunck[0]) ? $array_chunck[0] : array();
    ?>
    

      
            <?php if(is_array($arrayfinal) && !empty($arrayfinal)): foreach ($arrayfinal as $marca): ?>
                <div class="<?php echo $categoria ? 'col-md-12' : 'col-md-3'; ?> col-xs-6 pre_cardo">
                    <div class="panel panel-default">
                        <div class="panel-body text-center">
                            <a href="<?php echo link_marca($marca["nombre_clave"]); ?>" 
                               title="Códigos descuento <?php echo htmlspecialchars($marca["nombre"]); ?>">
                                <img class="lazyload" 
                                     src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" 
                                     data-src="<?php echo htmlspecialchars($marca["imagen"]); ?>" 
                                     alt="Código amigo de <?php echo htmlspecialchars($marca["nombre"]); ?>" 
                                     style="max-height:80px;">
                            </a>
                            <?php if (!empty($marca["descripción"])): ?>
                                <p><?php echo htmlspecialchars($marca["descripción"]); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
			
    

    <?php }
    
    
   
    
    
    
    
      

?>