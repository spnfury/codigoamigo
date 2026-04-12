<?php
/**
 * Crear sesión de Stripe Checkout para suscripción VIP
 * Suscripción recurrente de 9,99€/mes
 */

require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';
require_once __DIR__ . '/myphp/funciones_usuario.php';

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Debes estar logueado para suscribirte a VIP'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verificar si ya es VIP
if (es_usuario_vip($user_id)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Ya tienes una suscripción VIP activa'
    ]);
    exit;
}

// Obtener datos del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

if (!$usuario) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no encontrado'
    ]);
    exit;
}

// Configurar Stripe
require_once __DIR__ . '/config/stripe.php';
$stripe_secret_key = get_stripe_live_secret_key();
\Stripe\Stripe::setApiKey($stripe_secret_key);

try {
    // Buscar o crear el producto de suscripción VIP en Stripe
    $products = \Stripe\Product::search([
        'query' => 'metadata["product_type"]:"vip_subscription"',
        'limit' => 1
    ]);
    
    if (count($products->data) > 0) {
        $product = $products->data[0];
    } else {
        // Crear el producto si no existe
        $product = \Stripe\Product::create([
            'name' => 'Suscripción VIP CodigoAmigo',
            'description' => 'Suscripción mensual VIP con badge verificado, chat ilimitado con viewers, y 10€ de saldo mensual',
            'metadata' => [
                'product_type' => 'vip_subscription'
            ]
        ]);
    }
    
    // Buscar o crear el precio de 9,99€/mes
    $prices = \Stripe\Price::search([
        'query' => 'product:"' . $product->id . '" AND active:"true" AND recurring.interval:"month"',
        'limit' => 1
    ]);
    
    if (count($prices->data) > 0) {
        $price = $prices->data[0];
    } else {
        // Crear el precio si no existe
        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => 999, // 9,99€ en céntimos
            'currency' => 'eur',
            'recurring' => [
                'interval' => 'month'
            ],
            'metadata' => [
                'price_type' => 'vip_monthly'
            ]
        ]);
    }
    
    // URLs de retorno
    $success_url = $GLOBALS['website'] . 'public/success_vip.php?session_id={CHECKOUT_SESSION_ID}';
    $cancel_url = $GLOBALS['website'] . 'public/suscripcion_vip.php?cancelled=1';
    
    // Crear la sesión de checkout
    $checkout_session = \Stripe\Checkout\Session::create([
        'mode' => 'subscription',
        'customer_email' => $usuario['mail'] ?? null,
        'line_items' => [
            [
                'price' => $price->id,
                'quantity' => 1,
            ],
        ],
        'subscription_data' => [
            'metadata' => [
                'tipo' => 'suscripcion_vip',
                'usuario_id' => $user_id
            ]
        ],
        'metadata' => [
            'tipo' => 'suscripcion_vip',
            'usuario_id' => $user_id
        ],
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'locale' => 'es',
        'allow_promotion_codes' => true
    ]);
    
    // Retornar la URL de checkout
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_session->url,
        'session_id' => $checkout_session->id
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Error de Stripe al crear sesión VIP: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error al crear la sesión de pago: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general al crear sesión VIP: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
