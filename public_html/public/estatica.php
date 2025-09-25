<?php



get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

?>
<div class="container-fluid main_entremedio">
	<div class="container text-center bloque_titulo_home">
		<div class="container">
    		<div class="row ">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                	<h1>Códigos Amigos y Códigos Descuento</h1>
                	<p>Comparte todos tus códigos y gana dinero</p><br>
                </div>
    		</div>
    	</div>
	</div>
<?php

if($_GET["page"] == ""){
    if ($detect->isMobile()) {
        bloque_info_home_mobile();
    }else{
        bloque_info_home();
    }
}

 ?>

</div>

<?php

get_footer(); ?>