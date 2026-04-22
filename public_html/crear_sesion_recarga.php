<?php
// Iniciar buffer de salida para evitar output accidental
ob_start();

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_utilidades.php';

// Verificar que la función esté disponible
if (!function_exists('getObjectUserWithSession')) {
    error_log("Error: getObjectUserWithSession function not found");
    die("Error: Required function not available");
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    ob_clean();
    header('Location: /mis-anuncios?error=no_auth');
    exit;
}

// Obtener datos del GET
$paquete = $_GET['paquete'] ?? '';
$precio = $_GET['precio'] ?? '';
$saldo = $_GET['saldo'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';

// Log para debugging

if (empty($paquete) || empty($precio) || empty($saldo)) {
    error_log("Datos incompletos - paquete: $paquete, precio: $precio, saldo: $saldo");
    ob_clean();
    header('Location: /mis-anuncios?error=incomplete_data');
    exit;
}

// Incluir Stripe directamente
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

// Configurar Stripe según el usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
$email_usuario = $usuario['email'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_secret_key = get_stripe_secret_key($email_usuario, $_SESSION['user_id'] ?? null);

try {
    // Crear sesión de Stripe
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    error_log("Creando sesión Stripe para paquete: $paquete, precio: $precio, saldo: $saldo");
    
    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => "Recarga de Saldo - Paquete {$paquete}€",
                    'description' => "Recibe {$saldo}€ de saldo por {$precio}€",
                ],
                'unit_amount' => intval($precio) * 100, // Convertir a céntimos
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://www.codigoamigo.com/felicidades_recarga?session_id={CHECKOUT_SESSION_ID}&paquete=' . $paquete . '&saldo=' . $saldo,
        'cancel_url' => 'https://www.codigoamigo.com/mis-anuncios',
        'client_reference_id' => $usuario_id,
        'metadata' => [
            'tipo' => 'recarga_saldo',
            'paquete' => $paquete,
            'saldo' => $saldo,
            'usuario_id' => $usuario_id
        ]
    ]);
    
    error_log("Sesión Stripe creada exitosamente: " . $session->id);
    
    // Redirigir directamente a Stripe Checkout
    ob_clean();
    header('Location: ' . $session->url);
    exit;
    
} catch (Exception $e) {
    error_log("Error creando sesión Stripe: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Limpiar buffer y redirigir con error
    ob_clean();
    header('Location: /mis-anuncios?error=stripe_error');
    exit;
}
?>