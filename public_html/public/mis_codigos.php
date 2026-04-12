<?php 
    get_header_modern($title, $description, $title_social, $description_social, $imagen_social); 
    
    $link_usuario = enlace_usuario($_SESSION["username"], $_SESSION["user_id"]);


    require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
    $stripe_live_secret_key = get_stripe_secret_key(null, $_SESSION['user_id'] ?? null);
    $is_sandbox_admin = in_array($_SESSION['user_id'] ?? null, ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'], true);
    if ($is_sandbox_admin) {
        $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
        $sku_patrocinado_splash = 'sku_GjZv74bm3tOhSU';
    } else {
        $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
        $sku_patrocinado_splash = 'sku_H6ViM4K361ELMH';
    }




?>
<style>



	
@media (max-width:768px)  {
    #header_usuario{ min-height:270px !important; }
    
    #header_usuario .foto_usuario > img{
		width:60px !important;
	}
}
	
</style>


<section id="header_usuario" class="bg-image-block bg-image-block parallaxBg bloque_parallax_home" style="height: 200px; overflow:hidden;padding:25px !important;">
	<div class="row">
		<div class="col-md-2 col-md-offset-1 col-xs-12 text-center foto_usuario">
		<img src="<?php echo $datos_usuario["img"]; ?>">
		<br>           	
        	<?php             	
            	$now = time(); // or your date as well
            	$your_date = strtotime($datos_usuario["fecha_registro"]);
            	$datediff = $now - $your_date;
                $num_dias = round($datediff / (60 * 60 * 24));
            	
            	if($num_dias <= 0){ $num_dias = "desde hoy"; }            	
        	?>
        	
        	<small style="color:white;"><b><?php echo $num_dias; ?></b> días registrado en la web</small>
	
		
		<?php if ($_SESSION['user_id'] == $datos_usuario["id_string"] && !$_REQUEST["borrados"]) { ?>
			
				<br><small><a style="color:white;text-decoration:underline;" href="?borrados=1">Ver Códigos borrados</a></small>
			
			<?php }elseif ($_SESSION['user_id'] == $datos_usuario["id_string"] && $_REQUEST["borrados"]) { ?>
			
				<br><small><a style="color:white;text-decoration:underline;" href="<?php echo ($link_usuario); ?>">Códigos Online</a></small>
			
		<? } ?>
		
			</div>
		
		<div class="col-md-8 col-xs-12">
			<div class="row">
				<div class="col-md-12 col-xs-12 " >
    			<?php if ($_SESSION['user_id'] == $datos_usuario["id_string"]) { ?>
                	<h1 >Tu página personal</h1>    
                	<p> Comparte esta página y consigue más difusión en tus códigos amigo </p>    
                <?php } else { ?>
                	<h1 >Gana dinero gracias a <?php echo $datos_usuario["username"] ?> y sus códigos amigo!</h1>
            	<?php } ?>
            	</div> 
        	</div> 
        	
        	<a class="btn btn-primary" href="<?php echo $link_usuario; ?>?ext=1">Ver mi página</a>
        	<a class="btn btn-primary" href="<?php echo $link_usuario_marcas; ?>?ext=1">Descubrir nuevas marcas</a>
        	
        	
		</div>				
	</div><br>
</section>


<div class="container container-bottom" style="background: white !important;">
			
	<div class="row">	
		
		<div class="pre_card col-md-12 col-xs-12">
		
		<div class="cd-home-title titulo_zona_home"><?php echo $num_codigos; ?></b> Códigos amigo del usuario <?php echo $datos_usuario["username"]; ?></div>
				
			<h1>Visibilidad de tus códigos</h1>
		
			<div class="listado_codigos">			
				<?php 
				
				global $num_codigos_global; 
				$num_codigos_global = $num_codigos;


				block_listado_codigos($listado_codigos, $tipo_block_codigos); ?>
			</div>
			
	
			</div>
		</div>
		</div>
</div>  

 <script type="text/javascript" >

     var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');
    
     var checkoutButtonSplash = document.getElementById('checkout-button-normal-splash');
    
     checkoutButtonSplash.addEventListener('click', function () {
         stripe.redirectToCheckout({
             lineItems: [{price: '<?php echo $sku_patrocinado_splash; ?>', quantity: 1}],
             mode: 'payment',
             clientReferenceId: '<?php echo $_SESSION["user_id"]; ?>',
             billingAddressCollection: 'auto',
             successUrl: '<?php echo $GLOBALS["website"];?>felicidades_splash?session_id={CHECKOUT_SESSION_ID}',
             cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>',
         }).then(function (result) {
             if (result.error) {
                 var displayError = document.getElementById('error-message');
                 displayError.textContent = result.error.message;
             }
         });
     });

</script>