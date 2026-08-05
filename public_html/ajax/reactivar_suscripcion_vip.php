<?php
/**
 * Reactivar suscripción VIP (undo pending cancellation)
 * Revierte cancel_at_period_end para que la suscripción continúe
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

// Verificar que es VIP con cancelación pendiente
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

if (empty($usuario['vip_cancel_pending']) || !$usuario['vip_cancel_pending']) {
    echo json_encode(['success' => false, 'error' => 'No hay cancelación pendiente']);
    exit;
}

$subscription_id = $usuario['vip_subscription_id'];

try {
    require_once __DIR__ . '/../config/stripe.php';
    $stripe_secret_key = get_stripe_live_secret_key();
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    \Stripe\Stripe::setApiVersion('2023-10-16');

    // Revertir cancelación
    $subscription = \Stripe\Subscription::update($subscription_id, [
        'cancel_at_period_end' => false
    ]);

    // Limpiar en MongoDB
    $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => [
            'vip_cancel_pending' => false
        ],
        '$unset' => [
            'vip_cancel_requested_at' => ''
        ]]
    );

    error_log("[VIP REACTIVATE] Usuario $user_id reactivó suscripción VIP");

    echo json_encode([
        'success' => true,
        'message' => '¡Tu suscripción VIP se ha reactivado!'
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("[VIP REACTIVATE ERROR] Stripe: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al reactivar: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("[VIP REACTIVATE ERROR] General: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
