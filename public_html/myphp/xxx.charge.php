<?php 

require_once('../vendor/autoload.php');
\Stripe\Stripe::setApiKey('sk_test_ML0vGPIQHfl4iQYVHeflQTZt');
$token = $_POST['stripeToken'];

// This is a $20.00 charge in US Dollar.
$charge = \Stripe\Charge::create(
    array(
        'amount' => 0100,
        'currency' => 'usd',
        'source' => $token
    )
    );


if($charge->status != "succeeded") { 
    header("location:https://www.codigoamigo.com?msg=codigo_patrocinado_correctamente"); 
}else{
    header("location:https://www.codigoamigo.com?msg=error_al_patrocinar");
}




?>