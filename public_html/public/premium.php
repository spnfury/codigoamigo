<?php

get_header_new($title, $description, $title_social, $description_social, $imagen_social);



if($_SESSION["user_id"]=='639899bc6321ee0d0e4010d2' || $_SESSION["user_id"]=='58bd851da54e295b8b52f702' || $_SESSION["user_id"]=='5db1af3a2f55c82b47342172'){ //SI ES USUARIO ADMIN PATROCINO GRATIS
    
    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $stripe_live_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
    
    $sku_patrocinado_1 = 'sku_GJUimQssXxo3yB';
    $sku_patrocinado_2 = 'sku_GJWab8ArF8ZM6t';
    
}else{ //PRODUCCION
    
    //     $stripe_live_publishable_key = "pk_live_3jwIQ5ovWAY4xZE6yqewmDK9";
    //     $stripe_live_secret_key = "sk_live_Nt9VsX53qsFzPQkDaAA15yR6";
    
    //     $sku_patrocinado_1 = 'sku_GJUZ7SpPIS3m0p'; //NORMAL 0,99
    //     $sku_patrocinado_2 = 'sku_GJWbui7dHxseml'; //HOME 3,99
    
    //     $stripe_live_publishable_key = "pk_live_51H3LqbABtDSN8fl3gOEDtfA4bxb1zh32mmM7aMpaOaLV0AsvasTDu0NsZ1b9qk9Xae0dctDtawUOoq8cUEPYbZLC00rseTNYkO";
    //     $stripe_live_secret_key = "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";
    
    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $stripe_live_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
    
    
    $sku_patrocinado_1 = 'sku_H6K4Pf8TXO6w58'; //NORMAL 0,99
    $sku_patrocinado_2 = 'sku_H6K69kuQGeL0pV'; //HOME 3,99
    
}



/* Primero de todo, comprobamos si tenemos que realizar algún cargo */


if(count($_SESSION["compra_lead_sin_validar"]) > 0) {
    
    
    
    $cantidad_stripe = $_SESSION["compra_lead_sin_validar"]["cantidad"];
    $cantidad_original = $cantidad_stripe/100;
    $token_id = $_SESSION["compra_lead_sin_validar"]["token_id"];
    $codigo_operacion = $_SESSION["compra_lead_sin_validar"]["codigo"];
    $lead_id = $_SESSION["compra_lead_sin_validar"]["lead_id"];
    
    
    //BCV == destacado normal
    //BCS == destacado social
    
    try
    
    {
        
        \Stripe\Stripe::setApiKey($stripe_live_secret_key);
        $charge = \Stripe\Charge::create(array(
            'amount' => $cantidad_stripe,
            'currency' => 'eur',
            'description' => 'CODIGOAMIGO - '.$lead_id,
            'source' => $token_id));
        
    }catch(Exception $e)
    
    {
        
        /*echo "<pre>";
         print_r($e);
         print_r($charge);*/
        
    }
    
    
    
    if($charge->status != "succeeded") {
        $pago = "error";
    }else {
        $pago = "ok";
    }
    
    if($pago == "ok") {
        
        /*if($codigo_operacion == "BCI") { $operacion = "Búsqueda candidados sin validar"; }*/
        
        /*
         * OK PATROCINO
         */
        
        $obj_id_codigo = new \MongoDB\BSON\ObjectId($lead_id);
        $codigo_to_show = getCodeByID($obj_id_codigo);
        
        
        añadir_destacado_codigo($codigo_to_show,$codigo_operacion);
        
        //echo $codigo_to_show;
        
        
        
        /*$wpdb->insert('wp_user_historial_operaciones', array(
         'user_id' => $current_user->ID,
         'codigo_operacion' => "RS",
         'operacion' => "Recargar saldo",
         'cantidad' => $cantidad_original,
         ));
        
         $wpdb->insert('wp_user_historial_operaciones', array(
         'user_id' => $current_user->ID,
         'id_intento' => $id_intento,
         'codigo_operacion' => $codigo_operacion,
         'operacion' => $operacion,
         'cantidad' => $cantidad_original,
         ));
         
         $_SESSION["compra_lead_sin_validar"] = array();*/
        
        ?>
        <script type="text/javascript">
        ga('send', 'event', 'patrocinado', '<?php echo $obj_id_codigo; ?>', '<?php echo $cantidad_stripe; ?>', '', '<?php echo $obj_id_codigo; ?>');
        </script>
        <?


        header("location:".$GLOBALS["website"]);

    }



}

$_SESSION["compra_lead_sin_validar"] = array();
unset($_SESSION["compra_lead_sin_validar"]);
?>

<style>
#header_usuario:before{
	top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    content: " ";
    position: absolute;

	 -webkit-filter: blur(25px);
  -moz-filter: blur(25px);
  -o-filter: blur(25px);
  -ms-filter: blur(25px);
  filter: blur(25px);
	background-size:cover !important;
	background: rgba(17, 33, 82, 0.7);
	background-image: url('<?php echo $datos_usuario["img"]; ?>');    background-position: 0px 50%;

}

@media (max-width:768px)  {
    #header_usuario{
    height:450px !important;
	}
}
	
	
.premium-color {
    color: rgb(245, 210, 88) !important;
}
  
  .css-1yxmbwk {
    -webkit-tap-highlight-color: transparent;
    background-color: transparent;
    outline-color: initial;
    border-color: initial;
    text-decoration-color: initial;
    color: rgba(229, 224, 216, 0.54);
}

.css-1yxmbwk {
    display: inline-flex;
    -webkit-box-align: center;
    align-items: center;
    -webkit-box-pack: center;
    justify-content: center;
    position: relative;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
    background-color: transparent;
    outline: 0px;
    border: 0px;
    margin: 0px;
    cursor: pointer;
    user-select: none;
    vertical-align: middle;
    appearance: none;
    text-decoration: none;
    text-align: center;
    flex: 0 0 auto;
    font-size: 1.5rem;
    padding: 8px;
    border-radius: 50%;
    overflow: visible;
    color: rgba(0, 0, 0, 0.54);
    transition: background-color 150ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
    color:white !important;
}
    
.modal-body {
    background-color: #242525;
    border-color: rgba(143, 132, 117, 0.2);
    outline-color: initia;
	color:rgba(229, 224, 216, 0.85) !important;
</style>


<div class="container container-bottom" style="padding:20px !important;background: white !important;">

	<br>	<br>	<br>	<br>
	<div class="row">


	
    
    
    
    <div class="modal-body" style="padding: 16px;"><div style="display: flex; justify-content: space-between;">
    <img src="/img/logo_codigoamigo_real4.png" style="width: 300px; margin: 10px 0px;"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="18" width="18" xmlns="http://www.w3.org/2000/svg" style="cursor: pointer; position: absolute; top: 10px; right: 10px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path fill="none" d="M0 0h24v24H0z"></path><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>
    
    
    </div><div style="font-size: 20px; margin-bottom: 8px;">Conviértete en <span class="premium-color">Premium</span>!</div>
    <ul style="list-style: none; padding-left: 0px;"><li style="display: flex; align-items: center; font-size: 14px;">
    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="20" width="20" xmlns="http://www.w3.org/2000/svg" style="margin-left: 0px; margin-right: 8px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path d="M256 48C141.6 48 48 141.6 48 256s93.6 208 208 208 208-93.6 208-208S370.4 48 256 48zm-42.7 318.9L106.7 260.3l29.9-29.9 76.8 76.8 162.1-162.1 29.9 29.9-192.1 191.9z"></path></svg> 
    <div><span class="premium-color">3 Códigos AUTO DESTACADOS </span><small> - siempre número uno</small></div><div aria-label="c.ai+ subscribers always bypass the waiting room and enjoy instant access to our services." sx="[object Object]" class="" style="display: flex;"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium css-1yxmbwk" tabindex="0" type="button"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg" data-darkreader-inline-fill="" data-darkreader-inline-stroke="" style="--darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg><span class="MuiTouchRipple-root css-w0pj6f"></span></button></div></li>
    <li style="display: flex; align-items: center; font-size: 14px;"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="20" width="20" xmlns="http://www.w3.org/2000/svg" style="margin-left: 0px; margin-right: 8px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path d="M256 48C141.6 48 48 141.6 48 256s93.6 208 208 208 208-93.6 208-208S370.4 48 256 48zm-42.7 318.9L106.7 260.3l29.9-29.9 76.8 76.8 162.1-162.1 29.9 29.9-192.1 191.9z"></path></svg> <span class="premium-color">Datos de contactos de los usuarios</span><div aria-label="Dedicated c.ai+ servers mean you spend less time waiting and more time chatting." sx="[object Object]" class="" style="display: flex;"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium css-1yxmbwk" tabindex="0" type="button"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg" data-darkreader-inline-fill="" data-darkreader-inline-stroke="" style="--darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg><span class="MuiTouchRipple-root css-w0pj6f"></span></button></div></li><li style="display: flex; align-items: center; font-size: 14px;"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="20" width="20" xmlns="http://www.w3.org/2000/svg" style="margin-left: 0px; margin-right: 8px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path d="M256 48C141.6 48 48 141.6 48 256s93.6 208 208 208 208-93.6 208-208S370.4 48 256 48zm-42.7 318.9L106.7 260.3l29.9-29.9 76.8 76.8 162.1-162.1 29.9 29.9-192.1 191.9z"></path></svg> 
    <div>Tus códigos en nuestro<span class="premium-color"> canal de telegram</span></div><div aria-label="Be the first to try out new features and improvements on character.ai." sx="[object Object]" class="" style="display: flex;"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium css-1yxmbwk" tabindex="0" type="button"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg" data-darkreader-inline-fill="" data-darkreader-inline-stroke="" style="--darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg><span class="MuiTouchRipple-root css-w0pj6f"></span></button></div></li><li style="display: flex; align-items: center; font-size: 14px;"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="20" width="20" xmlns="http://www.w3.org/2000/svg" style="margin-left: 0px; margin-right: 8px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path d="M256 48C141.6 48 48 141.6 48 256s93.6 208 208 208 208-93.6 208-208S370.4 48 256 48zm-42.7 318.9L106.7 260.3l29.9-29.9 76.8 76.8 162.1-162.1 29.9 29.9-192.1 191.9z"></path></svg> 
    <div>Contacto con usuarios: <span class="premium-color">email, whatsapp o telegram</span></div><div aria-label="Access to an exclusive community section for c.ai+ subscribers." sx="[object Object]" class="" style="display: flex;"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium css-1yxmbwk" tabindex="0" type="button"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg" data-darkreader-inline-fill="" data-darkreader-inline-stroke="" style="--darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg><span class="MuiTouchRipple-root css-w0pj6f"></span></button></div></li><li style="display: flex; align-items: center; font-size: 14px;"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="20" width="20" xmlns="http://www.w3.org/2000/svg" style="margin-left: 0px; margin-right: 8px; --darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;" data-darkreader-inline-fill="" data-darkreader-inline-stroke=""><path d="M256 48C141.6 48 48 141.6 48 256s93.6 208 208 208 208-93.6 208-208S370.4 48 256 48zm-42.7 318.9L106.7 260.3l29.9-29.9 76.8 76.8 162.1-162.1 29.9 29.9-192.1 191.9z"></path></svg> <div><div class="darkreader plus-subscriber-badge" aria-label="c.ai+ Subscriber">
    <div>Insignia en tu usuario <span class="premium-color">PREMIUM</span></div></div></div><div aria-label="Stand out in the Community with a special supporter badge that appears beside your username." sx="[object Object]" class="" style="display: flex;"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium css-1yxmbwk" tabindex="0" type="button"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg" data-darkreader-inline-fill="" data-darkreader-inline-stroke="" style="--darkreader-inline-fill: currentColor; --darkreader-inline-stroke: currentColor;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg><span class="MuiTouchRipple-root css-w0pj6f"></span></button></div></li></ul>
    
    
    

<div class="modal-footer" style="padding: 16px;"><div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
<div style="display: flex;"><div class="price" style="font-size: 28px;">24<span style="font-size: 16px;">.99€</span></div><div class="price-month" style="align-self: flex-end;">/mes</div></div>

 <form action="/create-checkout-session" method="POST">
  <input type="hidden" name="lookup_key" value="price_1OkYAMKZJkTJqkCwmOUPDIAe"  />
 <button id="checkout-and-portal-button" type="submit" class="btn btn-primary" style="height: 42px;">Suscribirse</button>
 </form></div><div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 40px;">
</div></div>



</div>

    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    <script src="https://checkout.stripe.com/checkout.js"></script>


    <div id="payment-request-button">
  <!-- A Stripe Element will be inserted here. -->
</div>
    <script type="text/javascript" >

	var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');

	<?php


	$_SESSION["a_patrocinar"] = $_REQUEST["codigo"];

// 	try

// 	{

//     \Stripe\Stripe::setApiKey($stripe_live_secret_key);
// 	$session = \Stripe\Checkout\Session::create([
// 	    'payment_method_types' => ['card'],
// 	    'mode' => 'setup',
// 	    'success_url' => $GLOBALS["actual_url"]."/felicidades?session_id={CHECKOUT_SESSION_ID}",
// 	    'cancel_url' => $GLOBALS["actual_url"]."/felicidades?session_id={CHECKOUT_SESSION_ID}",
// 	]);

// 	}catch(Exception $e)

// 	{

// 	    /*echo "<pre>";
// 	     print_r($e);
// 	     print_r($charge);*/

// 	}



	?>

	//var stripe = Stripe('pk_live_3jwIQ5ovWAY4xZE6yqewmDK9');



	var checkoutButton = document.getElementById('checkout-button-normal');
	var checkoutButtonSuper = document.getElementById('checkout-button-super');

    $(document).ready(function() {

    	checkoutButton.addEventListener('click', function () {

    	    // When the customer clicks on the button, redirect
    	    // them to Checkout.
    	    stripe.redirectToCheckout({
    	    	mode: 'payment',
    	      items: [{sku: '<?php echo $sku_patrocinado_1; ?>', quantity: 1}],
    	      clientReferenceId: '<?php echo $_REQUEST["codigo"]; ?>',
    	      billingAddressCollection: 'auto',
    	      successUrl: '<?php echo $GLOBALS["website"];?>felicidades?session_id={CHECKOUT_SESSION_ID}',
    	      cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>',
    	    })
     .then(function (result) {
          if (result.error) {
            // If `redirectToCheckout` fails due to a browser or network
            // error, display the localized error message to your customer.
            var displayError = document.getElementById('error-message');
            displayError.textContent = result.error.message;
          }
        });
    	});


	checkoutButtonSuper.addEventListener('click', function () {

    	    // When the customer clicks on the button, redirect
    	    // them to Checkout.
    	    stripe.redirectToCheckout({
    	      items: [{sku: '<?php echo $sku_patrocinado_2; ?>', quantity: 1}],
    	      clientReferenceId: '<?php echo $_REQUEST["codigo"]; ?>',
    	      successUrl: '<?php echo $GLOBALS["website"];?>felicidades?session_id={CHECKOUT_SESSION_ID}',
    	      cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>',
    	    })
     .then(function (result) {
          if (result.error) {
            // If `redirectToCheckout` fails due to a browser or network
            // error, display the localized error message to your customer.
            var displayError = document.getElementById('error-message');
            displayError.textContent = result.error.message;
          }
        });
    	});




        <?php

        $precio = "099";

        ?>



        $(document).on('click', ".loading", function (event) {

        	$(".loading").fadeOut("1000");

        });

    	/*********** DESTACAR CODIGO *****************/

    	$(document).on('click', ".destacar", function (event) {

        	if(!txt){
        		var txt = "<small> Cargando Formulario de pago...</small>";
        	}

        	$(".loading").fadeIn("1000");
        	$(".loading .dentro_loading .busca_div_abs").html(txt);

    	    var lead_id = $(this).attr("data-codigo-id");


    	    StripeCheckout.open({
    	        amount: <?php echo $precio; ?>, //Multiplicada por 100 para stripe
    	        name: "CódigoAmigo",
    	        currency: "eur",
    	        image: "https://www.codigoamigo.com/img/favicon3.png",
    	        description: "Destacar Listados",
    	        locale: "auto",
    	        key: "<?php echo $stripe_live_publishable_key; ?>",
    	        token: function(token) {

    		        /*
    		        	No enviar la cantidad. Enviar el token id por ajax y guardarlo en bd junto con la cantidad, la fecha y la hora. Luego recoger
    		        	la cantidad a pagar por bd y ya lo tenemos.
    		        */
    	        	guardar_nuevo_token(token["id"], lead_id, <?php echo $precio; ?>,"BCV");


    	        }
    	    });

    	    setTimeout(function(e){
    	    	$(".loading").fadeOut("1000");
   	    	 },2400);



        });


    	$(document).on('click', ".destacar.social", function (event) {


        	if(!txt){
        		var txt = "<small> Cargando Formulario de pago...</small>";
        	}

        	$(".loading").fadeIn("1000");
        	$(".loading .dentro_loading .busca_div_abs").html(txt);

    	    var lead_id = $(this).attr("data-codigo-id");


    	    StripeCheckout.open({
    	        amount: 399, //Multiplicada por 100 para stripe
    	        name: "CódigoAmigo",
    	        currency: "eur",
    	        image: "https://www.codigoamigo.com/img/favicon3.png",
    	        description: "Destacar Código Home + Listados + Social",
    	        locale: "auto",
    	        key: "<?php echo $stripe_live_publishable_key; ?>",
    	        token: function(token) {

    		        /*
    		        	No enviar la cantidad. Enviar el token id por ajax y guardarlo en bd junto con la cantidad, la fecha y la hora. Luego recoger
    		        	la cantidad a pagar por bd y ya lo tenemos.
    		        */
    	        	guardar_nuevo_token(token["id"], lead_id,399,'BCS');


    	        }
    	    });

    	    setTimeout(function(e){
    	    	$(".loading").fadeOut("1000");
   	    	 },2400);



        });

    	function guardar_nuevo_token(token_id, lead_id, precio, codigo) {

	 		$.ajax({
	            type: "POST",
	            url: "/myphp/ajax_actions.php",
	            data: {
	           		metodo: "guardar_token_compra_lead_sin_validar",
	           		token_id: token_id,
	           		cantidad: precio, //Multiplicada por 100 para stripe
	        		codigo: codigo,
	        		lead_id: lead_id,
	            },
	            cache: false,
	            success: function(data) {
	            	alert("Código destacado correctamente");
	            	window.location.reload(false);
	            }
	    	});

	 	}

    });
    </script>



<?php get_footer(); ?>
}