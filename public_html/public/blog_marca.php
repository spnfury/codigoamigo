<?php get_header_new($title, $description); ?>
       
<section class="bg-image-block parallaxBg" style="height: 200px; background-image: url('<?php echo $marca["imagen"]; ?>'); background-size: 20%; background-position: right 50px; background-color: white;background-repeat: repeat;text-shadow:1px 1px 1px black;padding:25px !important;">
	<div class="container">
		<div class="row">
            <div class="col-md-8 col-md-offset-2 text-center">
            	<h1 style="font-size: 40px;">Códigos Amigo y Códigos Promocionales de <?php echo $marca["nombre"]; ?></h1>
            </div>
		</div>
	</div>
</section>
     
<div class="container">
    <div class="row bloque_publica_nuevo_codigo">
    	<div class="col-md-6 col-sm-6 col-xs-12">
			<a title="Códigos amigo de <?php echo $marca["nombre"];?>" href="<?php echo link_marca($marca["nombre_clave"]); ?>"><img class="" src="<?php echo $marca["imagen"]; ?>" alt="Código de <?php echo $marca["nombre"]?>"></a>
		</div>
		<div class="col-md-6 col-sm-6 col-xs-12" style="margin-top: 40px;">
        	<h2>Datos de la marca</h2><hr>
            <ul class="icons-list">
                <li class="">
                	Categoria: <a target="_blank" href="<?php echo link_categoria($marca["categoria_clave"]); ?>"><?php echo $marca["categoria"]; ?></a>
        		</li>
                <li>Fecha de publicación: <?php echo $marca["fecha_publicacion"] ?></li>
                <?php $numcodes = getNumCodes('marca', $marca["nombre_clave"]); ?>
                <li>Nº de códigos activos: <?php echo $numcodes; ?></li>
                <li></li>
            </ul>
    	</div>
    	<div class="col-md-8 col-md-offset-2">
    		<?php publica_tu_codigo(); ?>
    	</div>
    </div>
</div>

<?php get_footer(); ?>