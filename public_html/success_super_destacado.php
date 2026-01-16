<?php
session_start();
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/vendor/autoload.php';

$session_id = $_GET['session_id'] ?? null;
$codigo_id = $_GET['codigo_id'] ?? null;

if (!$session_id || !$codigo_id) {
    $_SESSION['msg_error'] = "Sesión de pago inválida";
    header('Location: /mis-anuncios');
    exit;
}

// Get Stripe key
$stripe_key = (strpos($_SERVER['SERVER_NAME'], 'dev.') !== false || $_SERVER['SERVER_NAME'] === 'localhost')
    ? 'sk_test_51IREyUEbj8l3ljyFx06D1jG5gqy0y8lCpO9i4lqt6ZInXBrOD5bqAHLWbU9HcS6xE6J6dcrJjBhIZxHaSzjdQP8D00Q9jqC9JB'
    : getenv('STRIPE_SECRET_KEY');

\Stripe\Stripe::setApiKey($stripe_key);

try {
    // Retrieve the session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    if ($session->payment_status === 'paid') {
        // Update the code to Super Featured
        $db = createConnection();
        $codigos_coll = $db->selectCollection('codigos');
        
        try {
            $codigo_obj_id = new MongoDB\BSON\ObjectId($codigo_id);
        } catch (Exception $e) {
            $codigo_obj_id = $codigo_id;
        }
        
        $result = $codigos_coll->updateOne(
            ['_id' => $codigo_obj_id],
            [
                '$set' => [
                    'tipo_destacado' => 'super',
                    'destacado' => time(),
                    'destacado_social' => time(),
                    'super_destacado_fecha' => new MongoDB\BSON\UTCDateTime(),
                    'super_destacado_expira' => new MongoDB\BSON\UTCDateTime(strtotime('+30 days') * 1000),
                    'prioridad_pago' => time()
                ]
            ]
        );
        
        if ($result->getModifiedCount() > 0) {
            // Log the payment
            $pagos_coll = $db->selectCollection('pagos');
            $pagos_coll->insertOne([
                'user_id' => $_SESSION['user_id'] ?? '',
                'codigo_id' => (string)$codigo_id,
                'tipo' => 'super_destacado',
                'amount' => 9.99,
                'currency' => 'EUR',
                'stripe_session_id' => $session_id,
                'stripe_payment_intent' => $session->payment_intent ?? '',
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completado'
            ]);
            
            $_SESSION['msg_success'] = "¡Felicidades! Tu código ahora está destacado como SUPER en la Guía Oficial. Comenzarás a recibir más visitas inmediatamente.";
        } else {
            $_SESSION['msg_error'] = "Error al actualizar el código. Por favor contacta con soporte.";
        }
    } else {
        $_SESSION['msg_error'] = "El pago no se ha completado correctamente";
    }
    
} catch (Exception $e) {
    error_log("Error verifying Super Destacado payment: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error al verificar el pago: " . $e->getMessage();
}

header('Location: /mis-anuncios');
exit;
?>
