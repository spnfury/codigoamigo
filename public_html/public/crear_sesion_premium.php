<?php
// Iniciar buffer de salida para evitar output accidental
ob_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_premium.php';

// Verificar que la función esté disponible
if (!function_exists('getObjectUserWithSession')) {
    include_once __DIR__ . '/../myphp/funciones_usuario.php';
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    ob_clean();
    $_SESSION['msg_error'] = "Debes iniciar sesión para suscribirte a premium";
    header('Location: /login');
    exit;
}

// Verificar si el usuario ya es premium
if (esUsuarioPremium($_SESSION["user_id"])) {
    ob_clean();
    $_SESSION['msg'] = "Ya tienes una suscripción premium activa";
    header('Location: /premium-dashboard');
    exit;
}

// Incluir Stripe directamente
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Configurar Stripe según el usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
$email_usuario = $usuario['email'] ?? '';

if ($email_usuario === 'thevega82@gmail.com') {
    // Usar Stripe de prueba para el usuario específico
    $stripe_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
    $price_id_premium = "price_test_premium_mensual"; // Configurar en Stripe Dashboard
} else {
    // Usar Stripe de producción para el resto
    $stripe_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
    // IMPORTANTE: Crear este price en Stripe Dashboard:
    // Producto: "Premium Suscripción CodigoAmigo"
    // Precio: 40€/mes (recurrente)
    // Price ID: price_premium_mensual (copiar aquí)
    $price_id_premium = "price_premium_mensual"; // Configurar en Stripe Dashboard
}

try {
    // Crear sesión de Stripe para suscripción
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    error_log("Creando sesión Stripe Premium para usuario: " . $_SESSION["user_id"]);
    
    // Crear o recuperar customer de Stripe
    $collection_usuarios = getCollectionUsuarios();
    $usuario_doc = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
    
    $stripe_customer_id = $usuario_doc['stripe_customer_id'] ?? null;
    
    // Si no tiene customer_id, crear uno nuevo
    if (!$stripe_customer_id) {
        $customer = $stripe->customers->create([
            'email' => $email_usuario,
            'metadata' => [
                'usuario_id' => (string)$_SESSION["user_id"]
            ]
        ]);
        $stripe_customer_id = $customer->id;
        
        // Guardar customer_id en el usuario
        $collection_usuarios->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])],
            ['$set' => ['stripe_customer_id' => $stripe_customer_id]]
        );
    }
    
    $session = $stripe->checkout->sessions->create([
        'customer' => $stripe_customer_id,
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id_premium,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'https://www.codigoamigo.com/premium-exito?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://www.codigoamigo.com/premium',
        'client_reference_id' => (string)$_SESSION["user_id"],
        'metadata' => [
            'tipo' => 'suscripcion_premium',
            'usuario_id' => (string)$_SESSION["user_id"]
        ],
        'subscription_data' => [
            'metadata' => [
                'usuario_id' => (string)$_SESSION["user_id"],
                'tipo' => 'suscripcion_premium'
            ]
        ]
    ]);
    
    error_log("Sesión Stripe Premium creada exitosamente: " . $session->id);
    
    // Redirigir directamente a Stripe Checkout
    ob_clean();
    header('Location: ' . $session->url);
    exit;
    
} catch (Exception $e) {
    error_log("Error creando sesión Stripe Premium: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Limpiar buffer y redirigir con error
    ob_clean();
    $_SESSION['msg_error'] = "Error al procesar el pago. Por favor, inténtalo de nuevo.";
    header('Location: /premium');
    exit;
}
?>

