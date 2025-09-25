<?php get_header_new($title, $description, $title_social, $description_social, $imagen_social); ?>
        	 	
<div class="container">
	
	<div class="title">
           			<h2>Categorías</h2>
           		</div>
	
	<div class="ccp listado_categorias">
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
  (adsbygoogle = window.adsbygoogle || []).push({
    google_ad_client: "ca-pub-8991940088210256",
    enable_page_level_ads: true
  });
</script>

<style>




.inside_listado_categorias{
	position:relative;
	margin:0px;
	justify-content: center;
	border-radius: 3.84px;
	    overflow: hidden;
	margin: 3.84px;
	display:inline-block;
    /*height: 110px;*/
	width:47%;
	padding: 0px;
}

.inside_listado_categorias img{
    width: 100%;
    height: 100%;
}

.inside_listado_categorias .cubre{
	background:black;
	height: 100%;
	width:100%;
    opacity: .7;
    position: absolute;
	top:0;
}

.inside_listado_categorias .titlo_listado{
	position:absolute;
	text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    max-width: 100%;
	color:white !important;
	
	    font-size: 15px;
    line-height: 18.75px;
	    z-index: 999;
    top: 39%;
    width: 100%;
    text-align: center;
}
</style>
<div class="container container-top container-bottom text-center">
	<div class="ccp listado_categorias">
		<?php 
		    $lista_categorias = getCategorias(); 
		    foreach($lista_categorias as $cat) { 		
		        
		        $cat["imagen"] = str_replace("http://","https://",$cat["imagen"]);	
		        
                if($cat["imagen"]){
                    

            	    ?>
                    <div class="sub_pre_cardo col-xs-6" style="min-height:150px;">
            			
                        <a href="<?php echo link_categoria($cat["nombre_clave"]); ?>" id="<?php echo $cat['nombre_clave'] ?>">
                            
                        <img src="<?php echo $cat["imagen"] ?>" alt="Código amigo de <?php echo $cat["imagen"] ?>">
                        
            			
            			<div class="cubre"></div>
            			
            			<div class="titlo_listado"><?php echo $cat["nombre"]; ?></div>
            			
            			</a>
                       
            			
            		</div>
            	<?php } ?>	
            		
        	<?php } ?>
        	</div>
        	</div>
	</div>
</div>
                
<?php get_footer(); ?>