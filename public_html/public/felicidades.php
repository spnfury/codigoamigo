<?php 

get_header_new($title, $description, $title_social, $description_social, $imagen_social);

ini_set("display_errors", "on");

if($_SESSION["user_id"]=='639899bc6321ee0d0e4010d2' || $_SESSION["user_id"]=='58bd851da54e295b8b52f702' || $_SESSION["user_id"]=='5db1af3a2f55c82b47342172'){ //SI ES USUARIO ADMIN PATROCINO GRATIS

    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $stripe_live_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";


}else{ //PRODUCCION

//     $stripe_live_publishable_key = "pk_live_3jwIQ5ovWAY4xZE6yqewmDK9";
//     $stripe_live_secret_key = "sk_live_Nt9VsX53qsFzPQkDaAA15yR6";

    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $stripe_live_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
    
//     $sku_patrocinado_1 = 'sku_H6K4Pf8TXO6w58'; //NORMAL 0,99
//     $sku_patrocinado_2 = 'sku_H6K69kuQGeL0pV'; //HOME 3,99

}

try {

    
    \Stripe\Stripe::setApiKey($stripe_live_secret_key);
    $sesion_validada = \Stripe\Checkout\Session::retrieve($_REQUEST['session_id']);
    
    $payment_details = \Stripe\PaymentIntent::retrieve($sesion_validada->payment_intent);
    
    $obj_id_codigo = new \MongoDB\BSON\ObjectId($sesion_validada->client_reference_id);
    $codigo_to_show = getCodeByID($obj_id_codigo);
    
 }catch(Exception $e){
   
    
}



if($sesion_validada->client_reference_id == $_SESSION["a_patrocinar"] && $payment_details){

 
    //PATROCINO
    
    if($_SESSION["a_patrocinar"] && $sesion_validada->amount_total == '99'){ //PATROCINADO NORMAL
        

       

//         echo "<pre>";
//         print_r($sesion_validada->display_items[0]->sku->price == '399');
//         print_r($payment_details);die;
        
        añadir_destacado_codigo($codigo_to_show,"BCV");
        
        $destacado = 1;
        
    }elseif($_SESSION["a_patrocinar"] && $sesion_validada->amount_total == '399'){ //PATROCINADO NORMAL
        
        añadir_destacado_codigo($codigo_to_show,"BCS");
        
        $super_destacado = 1;
        
    }
    
    unset($_SESSION["a_patrocinar"]);
    
    
    //UNSET
    
}else{
    $error = 1;
}

?>
<div class="container" >
    <div class="row text-center" style="margin-top:30px;">
    
        <h1>
        	<?php if(!$error){ ?>
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


