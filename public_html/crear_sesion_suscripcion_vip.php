<?php
/**
 * Crear sesión de Stripe Checkout para suscripción VIP
 * Suscripción recurrente de 9,99€/mes
 */

require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';
require_once __DIR__ . '/myphp/funciones_usuario.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir URL base si no existe
if (!isset($GLOBALS['website'])) {
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
}

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
\Stripe\Stripe::setApiVersion('2023-10-16');

// Intentar capturar el origen de la suscripción (ej. página pincipal vs modal)
$input = json_decode(file_get_contents('php://input'), true);
$source = $input['source'] ?? 'suscripcion_vip_page';

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
        'query' => 'product:"' . $product->id . '" AND active:"true" AND metadata["price_type"]:"vip_monthly"',
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
    
    // Cupón de bienvenida: 50% descuento primer mes (4,99€ en vez de 9,99€)
    $coupon_primer_mes = 'vip_primer_mes_50';
    try {
        \Stripe\Coupon::retrieve($coupon_primer_mes);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        \Stripe\Coupon::create([
            'id' => $coupon_primer_mes,
            'percent_off' => 50,
            'duration' => 'once',
            'name' => 'VIP Bienvenida - 50% primer mes',
            'metadata' => ['tipo' => 'vip_primer_mes']
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
                'usuario_id' => $user_id,
                'source' => $source
            ]
        ],
        'metadata' => [
            'tipo' => 'suscripcion_vip',
            'usuario_id' => $user_id,
            'source' => $source
        ],
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'locale' => 'es',
        // Descuento 50% primer mes auto-aplicado (excluyente con allow_promotion_codes)
        'discounts' => [
            ['coupon' => $coupon_primer_mes]
        ],
        // Mensaje de beneficios justo antes del botón de pago, para mejorar conversión
        'custom_text' => [
            'submit' => [
                'message' => 'Al confirmar activas: badge verificado ✓, chat ilimitado con quien te contacte, y 10€ de saldo cada mes para destacar tus códigos. Primer mes a mitad de precio.'
            ]
        ]
    ]);
    
    // Guardar el carrito abandonado (intención de checkout) en la BBDD
    // para tracking de embudo + emails de recuperación (cron/recuperar_carritos_vip.php)
    try {
        $db = createConnection();
        if ($db) {
            $coll_checkouts = $db->selectCollection('vip_checkout_intents');
            $coll_checkouts->insertOne([
                'usuario_id' => new MongoDB\BSON\ObjectId($user_id),
                'session_id' => $checkout_session->id,
                'source' => $source,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'status' => 'pending',
                'recovery_email_sent' => false
            ]);
        }
    } catch (Throwable $db_error) {
        // No detener el proceso de pago si falla el guardado estadístico
        log_error("No se pudo guardar la intención de checkout VIP: " . $db_error->getMessage());
    }
    
    // Retornar la URL de checkout
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_session->url,
        'session_id' => $checkout_session->id
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    log_error("Error de Stripe al crear sesión VIP: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error al crear la sesión de pago: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    log_error("Error general al crear sesión VIP: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
