<?php
session_start();
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['msg_error'] = "Debes iniciar sesión para destacar un código";
    header('Location: /');
    exit;
}

$user_id = $_SESSION['user_id'];
$codigo_id = $_POST['codigo_id'] ?? null;

if (!$codigo_id) {
    $_SESSION['msg_error'] = "Código no especificado";
    header('Location: /mis-anuncios');
    exit;
}

// Get code details
$db = createConnection();
$codigos_coll = $db->selectCollection('codigos');

try {
    $codigo_obj_id = new MongoDB\BSON\ObjectId($codigo_id);
} catch (Exception $e) {
    $codigo_obj_id = $codigo_id;
}

$codigo = $codigos_coll->findOne(['_id' => $codigo_obj_id]);

if (!$codigo) {
    $_SESSION['msg_error'] = "Código no encontrado";
    header('Location: /mis-anuncios');
    exit;
}

// Check ownership
if ((string)$codigo['id_usuario'] !== $user_id && $codigo['usuario_creador'] !== $user_id) {
    $_SESSION['msg_error'] = "No tienes permiso para modificar este código";
    header('Location: /mis-anuncios');
    exit;
}

// Check if already super
if (isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super') {
    $_SESSION['msg_info'] = "Este código ya está destacado como Super";
    header('Location: /mis-anuncios');
    exit;
}

// Initialize Stripe
require_once __DIR__ . '/vendor/autoload.php';

// Get user email for Stripe key logic
$collection_usuarios = $db->selectCollection('usuarios');
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
$email_usuario = $usuario['email'] ?? '';

// Configurar Stripe según el usuario (logic copied from crear_sesion_destacar.php)
if ($email_usuario === 'thevega82@gmail.com' || in_array($user_id, ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'])) {
    // Usar Stripe de prueba para usuarios admin
    $stripe_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
} else {
    // Usar Stripe de producción
    $stripe_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
}

\Stripe\Stripe::setApiKey($stripe_key);

try {
    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Destacado Super - 30 días',
                    'description' => 'Destaca tu código en la posición premium de la Guía Oficial',
                    'images' => ['https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'],
                ],
                'unit_amount' => 999, // 9.99€ in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://' . $_SERVER['SERVER_NAME'] . '/success_super_destacado.php?session_id={CHECKOUT_SESSION_ID}&codigo_id=' . $codigo_id,
        'cancel_url' => 'https://' . $_SERVER['SERVER_NAME'] . '/destacar_super.php?codigo_id=' . $codigo_id,
        'customer_email' => $_SESSION['mail'] ?? null,
        'metadata' => [
            'codigo_id' => (string)$codigo_id,
            'user_id' => $user_id,
            'tipo' => 'super_destacado'
        ]
    ]);

    // Redirect to Stripe Checkout
    header('Location: ' . $session->url);
    exit;

} catch (Exception $e) {
    error_log("Error creating Stripe session for Super destacado: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error al procesar el pago: " . $e->getMessage();
    header('Location: /destacar_super.php?codigo_id=' . $codigo_id);
    exit;
}
?>
