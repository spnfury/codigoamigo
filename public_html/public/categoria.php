<?php

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
global $detect_device,$codigo_existente;



$categoria["imagen"] = str_replace("http://", "https://", $categoria["imagen"]);

?>

<section id="header_usuario" class="bloque_parallax_home" style="margin-top:50px;" >
	<div class="container">
		<div class="row">
            <div class="col-md-12  text-center">
            	<h1><?php echo $categoria["nombre"]; ?></h1>
            	<div class="breadcrumb"><?php echo $categoria["descripcion_larga"]; ?></p></div>
            </div>
		</div>
	</div>
</section>


<?php

                //echo "<pre>";print_r($categoria);die;

                $listacategorias = getCategoriasSub($categoria["_id"]);


                        foreach($listacategorias as $categoria) { ?>
                            <li>
                            	<a title="<?php echo $categoria["descripcion"]; ?>" href="<?php echo link_categoria($categoria["nombre_clave"]); ?>">
                            		<i class="<?php echo $categoria["icon"]; ?>" aria-hidden="true"></i>
                            		<span style="margin-left: 10px;"><?php echo $categoria["nombre"] ?></span>
                            	</a>
                        	</li>
                        <?php } ?>


<section class="page-heading" style="border-top:3px solid white;background: rgba(0, 0, 0, 0.1) url(<?php echo $categoria["imagen"]; ?>);">

<div class="container">
    <div class="row empieza_home">
    	<div class="col-md-12 columns small-12 slider">
           		<div class="title">
           			<h2>Códigos destacados<span class="hidden-xs"> de <?php echo $categoria["nombre"];?></span></h2>
           		</div>

            	<div class="destacado_div" >
                	<?php block_listado_codigos($lista_codigos_patrocinados, "destacados"); ?>
               	</div>
            </div>
    	</div>
    </div>
</section>


    <div class="container">
    	<div class="row" style="padding: 10px 0px;">




        	<?php  if($lista_marcas != 0) { ?>



                <div class="ccp">




                   <div class="cd-home-title titulo_zona_home ultimos_codigos_h2">Marcas de la categoría <?php echo $categoria["nombre"]; ?></div>

                    <div class="col-md-12 col-sm-12" style="padding-top: 30px;">

                    	<?php
                    	printa_bloque_marcas($listado_marcas_tab);

                    	?>
                    </div>


                    </div>
                </div>
            <?php } else { //No hay marcas en esta categoria
                echo '<div class="cd-home-title">';
                echo '<h2><span><strong>Aún no hay ninguna marca para esta categoría.</strong></span></h2>';
                echo publica_tu_codigo();
                echo '</div>';
            } ?>
        </div>
	</div>
</section>
<section class="main-contain bg-gray" >
    <div class="counter-block" style="background: rgba(0, 0, 0, 0.8) url(../img/header_categorias/<?php echo $categoria["nombre_clave"]; ?>.jpg) no-repeat fixed center 100% / 95%">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="counter-item text-center">
                        <i class="fa fa-users fa-3x" aria-hidden="true"></i>
                        <h1 class="secondary-color count"><?php echo $numero_codigos; ?></h1>
                        <h4>Códigos amigo sobre <?php echo $categoria["nombre"]; ?> </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>