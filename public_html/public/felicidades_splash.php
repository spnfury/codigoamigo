<?php 

get_header_new($title, $description, $title_social, $description_social, $imagen_social);

ini_set("display_errors", "on");


require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_live_secret_key = get_stripe_secret_key(null, $_SESSION['user_id'] ?? null);
// Publishable keys (públicas por diseño; dejar hardcodeadas mientras no se añadan al helper)
$is_sandbox_admin = in_array($_SESSION['user_id'] ?? null, ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'], true);
$stripe_live_publishable_key = $is_sandbox_admin ? "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V" : "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";

try {

    
    \Stripe\Stripe::setApiKey($stripe_live_secret_key);
    $sesion_validada = \Stripe\Checkout\Session::retrieve($_REQUEST['session_id']);
    
    $payment_details = \Stripe\PaymentIntent::retrieve($sesion_validada->payment_intent);
    
    $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($sesion_validada->client_reference_id));
    $datos_usuario = get_array_de_usuario($usuario);
    
    
    
//     $obj_id_codigo = new \MongoDB\BSON\ObjectId($sesion_validada->client_reference_id);
//     $codigo_to_show = getCodeByID($obj_id_codigo);
    
 }catch(Exception $e){
   
    
}

session_start();

// echo "<br><br><br><br><br><br><br><br><br><br>****";

// echo "<pre>";
// print_r($_SESSION["user_id"]);

// print_r($sesion_validada->client_reference_id)."--".$_SESSION["user_id"]["oid"];
// die;

if($sesion_validada->client_reference_id  && $payment_details){
    
//     echo "<br><br><br><br><br><br>";
//     print_r($sesion_validada);
   
    //PATROCINO
    
    
    
    if($sesion_validada->amount_total == '999'){ //PATROCINADO NORMAL
        
        
        

//         print_r($sesion_validada->display_items[0]->sku->price == '399');
//         print_r($payment_details);die;
        
        añadir_destacado_codigo_usuario($datos_usuario,"BCV");
        
        $destacado = 1;
        
    }
    
    unset($_SESSION["a_patrocinar"]);
    
    
    //UNSET
    
}else{
    $error = 1;
}

?>
<div class="container" >
    <div class="row text-center" style="margin-top:30px;">
    
    <br>
    <br>
    <br>
    <br>
        <h1>
        	<?php 
        	
        	
        	if(!$error){ ?>
        	FELICIDADES HAS DESTACADO CON ÉXITO!
        	<?php }else{ ?>
        	UPS, ALGO HA FALLADO
        	
        	<?php } ?>
        </h1>
        
        <p><a href="https://www.codigoamigo.com" class="btn btn-custom">Ir a la página principal</a></p>
        <?php if($codigo_to_show["marca"]){ ?>
        <br>
        <p><a href="https://www.codigoamigo.com/de-<?php echo $codigo_to_show["marca"]; ?>" class="btn btn-custom">Ir a la página de <?php echo $codigo_to_show["marca"]; ?></a></p>
    	<?php }?>
    
    </div>

</div>

<?php get_footer(); ?>


