<?php

require_once '../vendor/autoload.php';

require_once 'secrets.php';

require_once '../inc/conexion.php';

require_once '../inc/includes.php';




\Stripe\Stripe::setApiKey($stripeSecretKey);


$YOUR_DOMAIN = 'https://www.codigoamigo.com';


try {
  $checkout_session = \Stripe\Checkout\Session::retrieve($_REQUEST['session_id']);
  $return_url = $YOUR_DOMAIN;

  
  
  
  
//   header("HTTP/1.1 303 See Other");
//   header("Location: " . $session->url);
} catch (Error $e) {
//   http_response_code(500);
//   echo json_encode(['error' => $e->getMessage()]);
}


if($checkout_session->customer!=''){ //EXISTE EL USUARIO Y EL PAGO
    session_start();

    try {
        
        $collection_usuarios = getCollectionUsuarios();
        
        
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $_SESSION["mail"]],
            ['$set' => ['pro_user' => 1]]
            );
    } catch(MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }
    
    
    //LE METO EL PREMIUM
    
    
    
    ?>
    <!DOCTYPE html>
<html>
<head>
  <title>Thanks for your order!</title>
  <link rel="stylesheet" href="style.css">
  <script src="client.js" defer></script>
</head>
<body>
  <section>
    <div class="product Box-root">
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="14px" height="16px" viewBox="0 0 14 16" version="1.1">
          <defs/>
          <g id="Flow" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
              <g id="0-Default" transform="translate(-121.000000, -40.000000)" fill="#E184DF">
                  <path d="M127,50 L126,50 C123.238576,50 121,47.7614237 121,45 C121,42.2385763 123.238576,40 126,40 L135,40 L135,56 L133,56 L133,42 L129,42 L129,56 L127,56 L127,50 Z M127,48 L127,42 L126,42 C124.343146,42 123,43.3431458 123,45 C123,46.6568542 124.343146,48 126,48 L127,48 Z" id="Pilcrow"/>
              </g>
          </g>
      </svg>
      <div class="description Box-root">
        <h3>Subscription to Starter plan successful!</h3>
      </div>
    </div>
    <form action="/public/create-portal-session.php" method="POST">
      <input type="hidden" id="session-id" name="session_id" value="<?php echo $_REQUEST["session_id"]; ?>" />
      <button id="checkout-and-portal-button" type="submit">Manage your billing information</button>
    </form>
  </section>
</body>
</html>
    
    <?php 
}else{
    echo "no existe";die;
}

?>


