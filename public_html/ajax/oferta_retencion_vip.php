<?php
/**
 * Oferta de retención VIP — 50% descuento para el próximo mes (4,99€)
 * Aplica un cupón de Stripe al próximo invoice y revierte la cancelación
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debes estar logueado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verificar que es VIP
if (!es_usuario_vip($user_id)) {
    echo json_encode(['success' => false, 'error' => 'No tienes una suscripción VIP activa']);
    exit;
}

$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

if (!$usuario || empty($usuario['vip_subscription_id'])) {
    echo json_encode(['success' => false, 'error' => 'No se encontró la suscripción de Stripe']);
    exit;
}

// Verificar que no haya aceptado ya una oferta de retención
if (!empty($usuario['vip_retention_applied']) && $usuario['vip_retention_applied'] === true) {
    echo json_encode(['success' => false, 'error' => 'Ya has usado la oferta de retención anteriormente']);
    exit;
}

$subscription_id = $usuario['vip_subscription_id'];

try {
    require_once __DIR__ . '/../config/stripe.php';
    $stripe_secret_key = get_stripe_live_secret_key();
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    \Stripe\Stripe::setApiVersion('2023-10-16');

    // Buscar o crear un cupón de retención VIP (50% descuento, una sola vez)
    $coupon_id = 'vip_retention_50';
    try {
        $coupon = \Stripe\Coupon::retrieve($coupon_id);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // El cupón no existe, crearlo
        $coupon = \Stripe\Coupon::create([
            'id' => $coupon_id,
            'percent_off' => 50,
            'duration' => 'once',
            'name' => 'VIP Retención - 50% descuento',
            'metadata' => [
                'tipo' => 'vip_retention'
            ]
        ]);
    }

    // Aplicar cupón a la suscripción y revertir cancelación
    $subscription = \Stripe\Subscription::update($subscription_id, [
        'cancel_at_period_end' => false,
        'coupon' => $coupon_id
    ]);

    // Actualizar MongoDB
    $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => [
            'vip_cancel_pending' => false,
            'vip_retention_applied' => true,
            'vip_retention_applied_at' => new MongoDB\BSON\UTCDateTime()
        ],
        '$unset' => [
            'vip_cancel_requested_at' => ''
        ]]
    );

    // Registrar la transacción
    $collection_transacciones = getCollectionTransacciones();
    $collection_transacciones->insertOne([
        'usuario_id' => $user_id,
        'tipo' => 'retencion_vip',
        'cantidad' => 0,
        'descripcion' => 'Oferta de retención VIP aceptada: 50% descuento en próxima renovación (4,99€)',
        'fecha' => new MongoDB\BSON\UTCDateTime(),
        'estado' => 'completado',
        'stripe_subscription_id' => $subscription_id,
        'stripe_coupon_id' => $coupon_id
    ]);

    log_info("[VIP RETENTION] Usuario $user_id aceptó oferta de retención (4,99€ próximo mes)");

    echo json_encode([
        'success' => true,
        'next_amount' => '4,99€',
        'message' => '¡Genial! Tu próxima renovación será de solo 4,99€'
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    log_error("[VIP RETENTION ERROR] Stripe: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al aplicar la oferta: ' . $e->getMessage()]);
} catch (Exception $e) {
    log_error("[VIP RETENTION ERROR] General: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
