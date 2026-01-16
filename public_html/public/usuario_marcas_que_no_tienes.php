<?php 
get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta); 

function bloque_marcas_perso($lista_marcas){
    
    global $detect_device,$str_marcas,$arrMisMarcas;
    
    $array_marcas = array();
    
    foreach ($lista_marcas as $marca) {
        
        
        $marca["imagen"] = str_replace("http://","https://",$marca["imagen"]);
        
            $elemento = array('nombre' => $marca["nombre"],
                'codes' => $num_codes,
                'nombre_clave' => $marca["nombre_clave"],
                'descripción' => $marca["descripción"],
                'imagen' => $marca["imagen"]
            );
            
            
            array_push($array_marcas, $elemento);
        
        
        
        
    }
    
    $arrayfinal = $array_marcas;
    ?>
                    
                    <div>
                    	<div class="row marcas_home" style="margin-right: 0px; margin-left: 0px;">
                    	
                        <?php foreach ($arrayfinal as $marca) { ?>
                    		<div class="<?php if($categoria){ ?>col-md-12<?php }else{ ?>col-md-3<?php } ?> col-xs-6 pre_cardo">
                        		<div class="panel panel-default" >
                            		<div class="panel-body text-center pre_cardo">
                            			<a title="Códigos amigo de <?php echo $marca["nombre"]; ?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>" target="_blank">
                            				<img class="lazyload" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="<?php echo $marca["imagen"]; ?>" alt="Código amigo de <?php echo $marca["nombre"]; ?>">
                            			
                            			<?php if($marca["descripción"]){?>
                            				<p><?php echo $marca["descripción"]; ?></p>
                            			<?php } ?>
                            			</a>
                            		</div>
                        		</div>
                    		</div>
                    	<?php } ?>
                    	</div></div>
    
    <?php 
    }



?>

<div class="container-fluid main_entremedio">

	<div class="container text-center bloque_titulo_home">
		<div class="container">
    		<div class="row ">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                	<h1>😱 Descubre nuevas marcas 😱</h1>
                	<p>Descubiertas <?php echo $suma_mis_marcas; ?> de un total de <?php echo $suma_marcas; ?></p><br>
               
               <?php 
               $porcentaje_final = ($suma_mis_marcas/$suma_marcas)*100;
               ?>
               <div class="col-md-12"><div class="progress" style="height: 20px;margin-bottom:50px;">
                   <div class="progress-bar" role="progressbar" style="width: <?php echo $porcentaje_final; ?>%" aria-valuenow="<?php echo $porcentaje_final; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $suma_mis_marcas; ?> / <?php echo $suma_marcas; ?></div>
                   </div>
               </div>
            
               	 </div>
    		</div>
    	</div>	     
	</div>

<div class="row">

	<div class="container"> 

    <?php 
    
    $lista_categorias = getCategorias();
    
    
    foreach($lista_categorias as $cat) {
        
        
        
        
        $lista_marcas_pre = getMarcas(150,$cat["nombre_clave"]);
        
        /* LIMPIO MARCAS */
        $lista_marcas = array();
        $lista_tengui_marcas = array();
        $lista_falta_marcas = array();
        $lista_marcas_total = array();
        
        foreach ($lista_marcas_pre as $marca) {
            
            $noentra = "";
            if(!$marca["descripción"]){
                
                
                /* PATROCINADOS */
                $array_filtro = array("marca"=>$marca["nombre_clave"]);
                $array_filtro = array_merge($array_filtro, array("estado"=>0));
                
                $array_skip = array("limit"=>0);
                $array_skip = array_merge($array_skip, array("skip"=>$skip_patrocinados));
                
                //TOMO LOS CODIGOS
                $lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
                if(!$lista_codigos_patrocinados_pre["results"][0]){
                    $noentra = 1;
                }
                
                $marca["descripción"] = recorta_texto_pos($lista_codigos_patrocinados_pre["results"][0]["descripcion"],120,"...");
            }
            
            if(!$noentra){
                if($arrMisMarcas[$marca["nombre_clave"]]){
                    $lista_tengui_marcas[] = $marca;
                }else{
                    $lista_marcas[] = $marca;
                }
                
                $lista_marcas_total[] = $marca;
            }
            
        }

        $total_marcas_categoria = count($lista_marcas_total);
        $total_mis_marcas_categoria = count($lista_tengui_marcas);
        

        
        ?>
    

    
    <div class="inside_listado_categorias">
    
        <div style="display:inline-block;width:100%;">
        <h3 style="float:left">
        <a href="<?php echo link_categoria($cat["nombre_clave"]); ?>" id="<?php echo $cat['nombre_clave'] ?>">
            <?php echo $cat["nombre"]; 
            
            $porcentaje = ($total_mis_marcas_categoria/$total_marcas_categoria)*100;
            
            $porcentaje = round($porcentaje);
            
            ?>
            </a>
           </h3> 
           <h3 style="float:right;color:white;"><?php echo ($total_mis_marcas_categoria)."/".$total_marcas_categoria; ?></h3>
            </div>
            <div style="display:inline-block;width:100%;">
               <div class="progress" style="height: 20px;">
                  <div class="progress-bar progress-bar-success" role="progressbar" style="width: <?php echo $porcentaje; ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"><?php echo $porcentaje."%"; ?></div>
                </div>
           </div>
    	    <?php 
    	            bloque_marcas_perso($lista_marcas);
            
            ?>
       
    	
    </div>
    
    <?php

    }


    ?>
	</div> 

</div>

</div>
                <?php 

get_footer(); ?>