<?php
// Iniciar buffer de salida para evitar output accidental
ob_start();

session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$codigo_id = $_POST['codigo_id'] ?? '';
$tipo = $_POST['tipo'] ?? 'normal';
$sku = $_POST['sku'] ?? '';

// Debug logs
error_log("=== DEBUG crear_sesion_destacar.php ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . json_encode($_POST));
error_log("GET data: " . json_encode($_GET));
error_log("codigo_id: " . $codigo_id);
error_log("tipo: " . $tipo);
error_log("sku: " . $sku);

if (empty($codigo_id) || empty($sku)) {
    error_log("ERROR: Datos incompletos");
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Obtener información del código
$collection_codigos = getCollectionCodigos();
$codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

if (!$codigo) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Código no encontrado']);
    exit;
}

// Verificar que el código pertenece al usuario
if ($codigo["id_usuario"] != $_SESSION["user_id"]) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado para destacar este código']);
    exit;
}

// Configurar Stripe según el usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
$email_usuario = $usuario['email'] ?? '';

if ($email_usuario === 'thevega82@gmail.com' || in_array($_SESSION["user_id"], ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'])) {
    // Usar Stripe de prueba para usuarios admin
    $stripe_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
} else {
    // Usar Stripe de producción
    $stripe_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
}

try {
    // Crear sesión de Stripe
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    error_log("Creando sesión Stripe para destacar código: $codigo_id, tipo: $tipo");
    
    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $sku,
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://www.codigoamigo.com/felicidades_destacar?session_id={CHECKOUT_SESSION_ID}&codigo=' . $codigo_id . '&tipo=' . $tipo,
        'cancel_url' => 'https://www.codigoamigo.com/destacar_codigo?codigo=' . $codigo_id,
        'client_reference_id' => $codigo_id,
        'metadata' => [
            'tipo' => 'destacar_codigo',
            'codigo_id' => $codigo_id,
            'marca' => $codigo['marca'] ?? '',
            'usuario_id' => $_SESSION["user_id"],
            'username' => $_SESSION["username"] ?? 'Usuario',
            'tipo_destacado' => $tipo,
            'descripcion' => substr($codigo['descripcion'] ?? '', 0, 200)
        ]
    ]);
    
    error_log("Sesión Stripe creada exitosamente: " . $session->id);
    
    // Devolver JSON con la URL de la sesión
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['session_id' => $session->id, 'url' => $session->url]);
    exit;
    
} catch (Exception $e) {
    error_log("Error creando sesión Stripe: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al crear la sesión de pago: ' . $e->getMessage()]);
    exit;
}
?>
