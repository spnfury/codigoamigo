<?php
/**
 * Cancelar suscripción VIP (schedule cancellation at period end)
 * No cancela inmediatamente — el usuario mantiene VIP hasta fin de período
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

// Obtener subscription_id del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

if (!$usuario || empty($usuario['vip_subscription_id'])) {
    echo json_encode(['success' => false, 'error' => 'No se encontró la suscripción de Stripe']);
    exit;
}

$subscription_id = $usuario['vip_subscription_id'];

// VIP regalado (sin suscripción real de Stripe): nada que cancelar en Stripe.
// El VIP expira solo en vip_expires_at — marcamos el pending y respondemos OK.
if (strpos($subscription_id, 'sub_') !== 0) {
    $expires_date = new DateTime();
    if (isset($usuario['vip_expires_at']) && $usuario['vip_expires_at'] instanceof MongoDB\BSON\UTCDateTime) {
        $expires_date = $usuario['vip_expires_at']->toDateTime();
    }
    $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => [
            'vip_cancel_pending' => true,
            'vip_cancel_requested_at' => new MongoDB\BSON\UTCDateTime()
        ]]
    );
    log_info("[VIP CANCEL] VIP no-Stripe (regalado), cancelación marcada sin Stripe", ['user_id' => $user_id]);
    echo json_encode([
        'success' => true,
        'expires_at' => $expires_date->format('d/m/Y'),
        'message' => 'Tu VIP finalizará el ' . $expires_date->format('d/m/Y')
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../config/stripe.php';
    // Helper con whitelist sandbox (la suscripción pudo crearse en modo test)
    $stripe_secret_key = get_stripe_secret_key($usuario['mail'] ?? null, $user_id);
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    \Stripe\Stripe::setApiVersion('2023-10-16');

    // Programar cancelación al final del período
    $subscription = \Stripe\Subscription::update($subscription_id, [
        'cancel_at_period_end' => true
    ]);

    // Calcular fecha de expiración
    $expires_timestamp = $subscription->current_period_end;
    $expires_date = new DateTime();
    $expires_date->setTimestamp($expires_timestamp);

    // Marcar en MongoDB
    $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
        ['$set' => [
            'vip_cancel_pending' => true,
            'vip_cancel_requested_at' => new MongoDB\BSON\UTCDateTime()
        ]]
    );

    log_info("[VIP CANCEL] Usuario programó cancelación VIP", [
        'user_id' => $user_id,
        'expires' => $expires_date->format('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'success' => true,
        'expires_at' => $expires_date->format('d/m/Y'),
        'message' => 'Tu suscripción se cancelará el ' . $expires_date->format('d/m/Y')
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    log_error("[VIP CANCEL ERROR] Stripe: " . $e->getMessage(), ['user_id' => $user_id]);
    echo json_encode(['success' => false, 'error' => 'Error al cancelar: ' . $e->getMessage()]);
} catch (Exception $e) {
    log_error("[VIP CANCEL ERROR] General: " . $e->getMessage(), ['user_id' => $user_id]);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
