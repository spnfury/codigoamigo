<?php

require_once 'vendor/autoload.php';
require_once 'secrets.php';


\Stripe\Stripe::setApiKey($stripeSecretKey);
header('Content-Type: application/json');

$YOUR_DOMAIN = 'https://www.codigoamigo.com';

 //$_POST['lookup_key'] = "price_1OkbRWKZJkTJqkCw8VQ4cW9x";

try {
    
//   $prices = \Stripe\Price::all([
//     // retrieve lookup_key from form data POST body
//     'lookup_keys' => [$_POST['lookup_key']],
//     'expand' => ['data.product']
//   ]);
  
  
//   print_r($prices);die;

  $checkout_session = \Stripe\Checkout\Session::create([
    'line_items' => [[
        'price' => $_POST['lookup_key'],
      'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'success_url' => $YOUR_DOMAIN . '/public/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $YOUR_DOMAIN . '/public/cancel.html',
  ]);

  header("HTTP/1.1 303 See Other");
  header("Location: " . $checkout_session->url);
} catch (Error $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}