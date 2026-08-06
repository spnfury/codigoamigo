<?php
include_once __DIR__ . '/inc/logger.php';
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
log_info("=== DEBUG crear_sesion_destacar.php ===");
log_info("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
log_info("POST data: " . json_encode($_POST));
log_info("GET data: " . json_encode($_GET));
log_info("codigo_id: " . $codigo_id);
log_info("tipo: " . $tipo);
log_info("sku: " . $sku);

if (empty($codigo_id) || empty($sku)) {
    log_error("ERROR: Datos incompletos");
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

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_secret_key = get_stripe_secret_key($email_usuario, $_SESSION['user_id'] ?? null);

// Get auto_renovar flag from POST
$auto_renovar = $_POST['auto_renovar'] ?? '0';

try {
    // Crear sesión de Stripe
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    if (function_exists('log_info')) { log_info("Creando sesión Stripe para destacar código: $codigo_id, tipo: $tipo, auto_renovar: $auto_renovar"); }
    
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
            'auto_renovar' => $auto_renovar,
            'descripcion' => substr($codigo['descripcion'] ?? '', 0, 200)
        ]
    ]);
    
    if (function_exists('log_info')) { log_info("Sesión Stripe creada exitosamente: " . $session->id); }
    
    // Devolver JSON con la URL de la sesión
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['session_id' => $session->id, 'url' => $session->url]);
    exit;
    
} catch (Exception $e) {
    log_error("Error creando sesión Stripe: " . $e->getMessage());
    log_error("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al crear la sesión de pago: ' . $e->getMessage()]);
    exit;
}
?>
