<?php
// Iniciar buffer de salida para evitar output accidental
ob_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_utilidades.php';

// Verificar que la función esté disponible
if (!function_exists('getObjectUserWithSession')) {
    log_error("Error: getObjectUserWithSession function not found");
    die("Error: Required function not available");
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    ob_clean();
    header('Location: /mis-anuncios?error=no_auth');
    exit;
}

// Paquetes válidos: precio pagado => saldo abonado. Única fuente de verdad.
// Antes precio y saldo llegaban del GET sin validar: se podía pagar 1€ y
// pedir 100€ de saldo en metadata (el webhook abona metadata['saldo']).
$PAQUETES_RECARGA = [
    '20'  => 25,
    '40'  => 50,
    '100' => 150,
];

$paquete = $_GET['paquete'] ?? '';

if (!isset($PAQUETES_RECARGA[$paquete])) {
    log_warning("Paquete de recarga inválido: $paquete", [
        'evento' => 'checkout_recarga_paquete_invalido',
        'user_id' => $_SESSION["user_id"] ?? null,
        'paquete' => $paquete,
        'precio_get' => $_GET['precio'] ?? null,
        'saldo_get' => $_GET['saldo'] ?? null,
    ]);
    ob_clean();
    header('Location: /mis-anuncios?error=incomplete_data');
    exit;
}

// Derivados del paquete, nunca del cliente
$precio = $paquete;
$saldo = $PAQUETES_RECARGA[$paquete];
// El usuario a abonar es siempre el de la sesión (antes venía del GET)
$usuario_id = $_SESSION["user_id"];

// Incluir Stripe directamente
require_once '../vendor/stripe/stripe-php/init.php';

// Configurar Stripe según el usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
$email_usuario = $usuario['mail'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_secret_key = get_stripe_secret_key($email_usuario, $_SESSION['user_id'] ?? null);

try {
    // Crear sesión de Stripe
    $stripe = new \Stripe\StripeClient($stripe_secret_key);
    
    // FUNNEL: inicio de checkout "recarga" (contar intentos en logs/info/)
    log_info("Creando sesión Stripe para paquete: $paquete, precio: $precio, saldo: $saldo", [
        'evento' => 'checkout_recarga_inicio',
        'user_id' => $_SESSION["user_id"],
        'paquete' => $paquete,
        'precio' => $precio,
        'saldo' => $saldo,
    ]);

    // Mensaje de beneficios: el bono extra (saldo recibido - precio pagado) es el
    // argumento de conversión más directo aquí, así que se calcula y se muestra explícito.
    $bono_extra = round(floatval($saldo) - floatval($precio), 2);
    $bono_extra_fmt = rtrim(rtrim(number_format($bono_extra, 2, ',', ''), '0'), ',');
    $mensaje_submit = $bono_extra > 0
        ? "Recibirás {$saldo}€ de saldo al instante — {$bono_extra_fmt}€ de regalo sobre lo que pagas. Úsalo para destacar tus códigos cuando quieras."
        : "Recibirás {$saldo}€ de saldo al instante para destacar tus códigos cuando quieras.";

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
        'customer_email' => $email_usuario ?: null,
        'customer_creation' => 'always', // asocia Cliente en Stripe (si no, "Sin cliente asignado")
        'custom_text' => [
            'submit' => [
                'message' => $mensaje_submit
            ]
        ],
        'metadata' => [
            'tipo' => 'recarga_saldo',
            'paquete' => $paquete,
            'saldo' => $saldo,
            'usuario_id' => $usuario_id
        ]
    ]);

    // FUNNEL: sesión creada OK
    log_info("Sesión Stripe creada exitosamente: " . $session->id, [
        'evento' => 'checkout_recarga_sesion_creada',
        'session_id' => $session->id,
        'user_id' => $_SESSION["user_id"],
        'paquete' => $paquete,
    ]);

    // Guardar intento de checkout para recuperación de carritos abandonados
    try {
        $db_intents = createConnection();
        $db_intents->selectCollection('destacar_checkout_intents')->insertOne([
            'session_id'  => $session->id,
            'usuario_id'  => new MongoDB\BSON\ObjectId($usuario_id),
            'email'       => $email_usuario,
            'paquete'     => $paquete,
            'saldo'       => $saldo,
            'tipo'        => 'recarga_saldo',
            'created_at'  => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'status'      => 'pending',
            'recovery_email_sent' => false,
        ]);
    } catch (Exception $e) {
        if (function_exists('log_error')) { log_error("No se pudo guardar intento de checkout recarga: " . $e->getMessage()); }
    }

    // Redirigir directamente a Stripe Checkout
    ob_clean();
    header('Location: ' . $session->url);
    exit;
    
} catch (Exception $e) {
    log_error("Error creando sesión Stripe: " . $e->getMessage(), [
        'evento' => 'checkout_recarga_error',
        'user_id' => $_SESSION["user_id"] ?? null,
        'paquete' => $paquete,
        'trace' => $e->getTraceAsString(),
    ]);
    
    // Limpiar buffer y redirigir con error
    ob_clean();
    header('Location: /mis-anuncios?error=stripe_error');
    exit;
}
?>