<?php

require_once '../vendor/autoload.php';

require_once 'secrets.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

header('Content-Type: application/json');


$YOUR_DOMAIN = 'https://www.codigoamigo.com';


try {
  $checkout_session = \Stripe\Checkout\Session::retrieve($_POST['session_id']);
  $return_url = $YOUR_DOMAIN;
  
  print_r($checkout_session);die;

  // Authenticate your user.
  $session = \Stripe\BillingPortal\Session::create([
    'customer' => $checkout_session->customer,
    'return_url' => $return_url,
  ]);
  
  
  
  header("HTTP/1.1 303 See Other");
  header("Location: " . $session->url);
} catch (Error $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}