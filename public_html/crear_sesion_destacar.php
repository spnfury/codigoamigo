<?php
// Iniciar buffer de salida para evitar output accidental
ob_start();

session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

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
// El formulario envía auto_renovar ('1'/'0') pero hasta ahora no se leía ni se
// pasaba a Stripe. Como felicidades_destacar.php daba por buena la renovación
// cuando no encontraba el dato, TODOS los pagos acababan con auto-renovación
// activada aunque el usuario dejara la casilla sin marcar (reportado por un
// usuario el 2026-07-28). Solo cuenta como activada si llega un '1' explícito.
$auto_renovar = (($_POST['auto_renovar'] ?? '0') === '1') ? '1' : '0';

if (empty($codigo_id) || empty($sku)) {
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
$email_usuario = $usuario['mail'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_secret_key = get_stripe_secret_key($email_usuario, $_SESSION['user_id'] ?? null);

try {
    // Crear sesión de Stripe
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    // FUNNEL: inicio de checkout "destacar" (contar intentos en logs/info/)
    log_info("Creando sesión Stripe para destacar código: $codigo_id, tipo: $tipo", [
        'evento' => 'checkout_destacar_inicio',
        'user_id' => $_SESSION["user_id"],
        'codigo_id' => $codigo_id,
        'tipo_destacado' => $tipo,
        'sku' => $sku,
        'auto_renovar' => $auto_renovar,
    ]);
    
    $line_items = [];
    if ($sku === 'super_landing_999') {
        // Dynamic price for Super Landing
        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Super Destacado en Guías - 30 días',
                    'description' => 'Destaca tu código en la posición #1 de la Guía Oficial',
                    'images' => ['https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'],
                ],
                'unit_amount' => 999, // 9.99€
            ],
            'quantity' => 1,
        ];
    } else {
        // Pre-defined price ID (SKU)
        $line_items[] = [
            'price' => $sku,
            'quantity' => 1,
        ];
    }

    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'https://www.codigoamigo.com/felicidades_destacar?session_id={CHECKOUT_SESSION_ID}&codigo=' . $codigo_id . '&tipo=' . $tipo,
        'cancel_url' => 'https://www.codigoamigo.com/destacar_codigo?codigo=' . $codigo_id,
        'client_reference_id' => $codigo_id,
        'customer_email' => $email_usuario ?: null,
        'customer_creation' => 'always', // asocia Cliente en Stripe (si no, "Sin cliente asignado")
        // Mensaje de beneficios justo antes del botón de pago, para mejorar conversión
        'custom_text' => [
            'submit' => [
                'message' => 'Tu código pasará a la posición destacada, con más visibilidad y clics durante todo el periodo activo.'
            ]
        ],
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

    // FUNNEL: sesión creada OK (completar intentos -> éxitos en logs/info/)
    log_info("Sesión Stripe creada exitosamente: " . $session->id, [
        'evento' => 'checkout_destacar_sesion_creada',
        'session_id' => $session->id,
        'user_id' => $_SESSION["user_id"],
        'codigo_id' => $codigo_id,
    ]);

    // Guardar intento de checkout para recuperación de carritos abandonados
    // (mismo patrón que vip_checkout_intents, ver cron/recuperar_carritos_destacar.php).
    // Antes esta versión (la que realmente ejecuta el JS de destacar_codigo.php) no
    // guardaba nada aquí -> el cron de recuperación nunca encontraba carritos de
    // "destacar código" que recuperar, aunque el cron sí corría cada 15 min.
    try {
        $db_intents = createConnection();
        $coll_intents = $db_intents->selectCollection('destacar_checkout_intents');
        $coll_intents->insertOne([
            'session_id'     => $session->id,
            'usuario_id'     => new MongoDB\BSON\ObjectId($_SESSION["user_id"]),
            'codigo_id'      => $codigo_id,
            'email'          => $email_usuario,
            'username'       => $_SESSION["username"] ?? 'Usuario',
            'marca'          => $codigo['marca'] ?? '',
            'tipo'           => 'destacar_codigo',
            'tipo_destacado' => $tipo,
            'created_at'     => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'status'         => 'pending',
            'recovery_email_sent' => false,
        ]);
    } catch (Exception $e) {
        log_error("No se pudo guardar intento de checkout destacar: " . $e->getMessage());
    }

    // Devolver JSON con la URL de la sesión
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['session_id' => $session->id, 'url' => $session->url]);
    exit;
    
} catch (Exception $e) {
    log_error("Error creando sesión Stripe: " . $e->getMessage(), [
        'evento' => 'checkout_destacar_error',
        'user_id' => $_SESSION["user_id"] ?? null,
        'codigo_id' => $codigo_id,
        'trace' => $e->getTraceAsString(),
    ]);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al crear la sesión de pago: ' . $e->getMessage()]);
    exit;
}
?>
