<?php
// La sesión ya está iniciada en app_with_mongo.php

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

log_info("felicidades_recarga iniciado", ['get' => $_GET, 'user_id' => $_SESSION['user_id'] ?? null]);

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    log_warning("felicidades_recarga: usuario no logueado, redirigiendo a login");
    header("Location: /login");
    exit;
}

// Obtener parámetros de la URL
$session_id = $_GET['session_id'] ?? '';
$paquete = $_GET['paquete'] ?? '';
$saldo = $_GET['saldo'] ?? '';

if (empty($session_id) || empty($paquete) || empty($saldo)) {
    log_warning("felicidades_recarga: parámetros faltantes", ['session_id' => $session_id, 'paquete' => $paquete, 'saldo' => $saldo]);
    header("Location: /mis-anuncios?error=parametros_faltantes");
    exit;
}

// Verificar el pago con Stripe
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Configurar Stripe según el usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
$email_usuario = $usuario['email'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_secret_key = get_stripe_secret_key($email_usuario, $_SESSION['user_id'] ?? null);
$stripe = new \Stripe\StripeClient($stripe_secret_key);

try {
    log_info("felicidades_recarga: verificando sesión Stripe", ['session_id' => $session_id]);

    // Obtener la sesión de Stripe
    $session = $stripe->checkout->sessions->retrieve($session_id);

    log_info("felicidades_recarga: sesión Stripe recibida", [
        'payment_status' => $session->payment_status,
        'client_reference_id' => $session->client_reference_id,
    ]);

    if ($session->payment_status !== 'paid') {
        log_warning("felicidades_recarga: pago no completado", ['payment_status' => $session->payment_status, 'session_id' => $session_id]);
        header("Location: /mis-anuncios?error=pago_no_completado");
        exit;
    }
    
    // Verificar que el usuario coincida (opcional - por seguridad)
    // Comentado temporalmente para permitir acceso a la página de éxito
    // if ($session->client_reference_id !== $_SESSION["user_id"]) {
    //     header("Location: /mis-anuncios?error=usuario_no_coincide");
    //     exit;
    // }
    
    // Obtener saldo anterior antes de actualizar
    $collection_usuarios = getCollectionUsuarios();
    $usuario_antes = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
    $saldo_anterior = $usuario_antes['saldo'] ?? 0;
    
    // Actualizar el saldo del usuario en la base de datos
    $resultado = $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])],
        ['$inc' => ['saldo' => (int)$saldo]]
    );

    log_info("felicidades_recarga: updateOne saldo", [
        'user_id' => $_SESSION["user_id"],
        'cantidad' => (int)$saldo,
        'modified_count' => $resultado->getModifiedCount(),
    ]);

    if ($resultado->getModifiedCount() > 0) {
        
        // Registrar la transacción en la base de datos (si no existe ya por webhook)
        $collection_transacciones = getCollectionTransacciones();
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $session_id]);
        
        if (!$existe) {
            $transaccion = [
                'usuario_id' => $_SESSION["user_id"],
                'tipo' => 'recarga',
                'cantidad' => (int)$saldo,
                'descripcion' => "Recarga de saldo - Paquete {$paquete}€",
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completada',
                'stripe_session_id' => $session_id,
                'stripe_payment_intent' => $session->payment_intent,
                'stripe_customer_email' => $session->customer_details->email ?? null,
                'metodo_pago' => 'tarjeta',
                'paquete' => $paquete,
                'saldo_anterior' => $saldo_anterior,
                'saldo_nuevo' => $saldo_anterior + (int)$saldo
            ];
            $collection_transacciones->insertOne($transaccion);
            log_info("felicidades_recarga: transacción registrada", ['session_id' => $session_id, 'user_id' => $_SESSION["user_id"]]);
        }
        
        // Obtener el saldo actualizado
        $usuario_actualizado = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
        $saldo_actual = $usuario_actualizado['saldo'] ?? 0;
        
        // Mostrar página de éxito
        $title = "¡Recarga exitosa! - Código Amigo";
        $description = "Tu saldo ha sido recargado correctamente - " . $author_web;
        
        // El header ya está incluido en inc/includes.php
        get_header_modern($title, $description);
        ?>
        
        <div class="main-content" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
            <div class="success-container" style="text-align: center; background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(76, 175, 80, 0.3);">
                <div class="success-icon" style="font-size: 5rem; margin-bottom: 2rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; font-weight: 700;">
                    ¡Recarga Exitosa! 🎉
                </h1>
                
                <p style="font-size: 1.3rem; margin-bottom: 2rem; opacity: 0.9;">
                    Tu saldo ha sido recargado correctamente
                </p>
                
                <div class="saldo-info" style="background: rgba(255, 255, 255, 0.2); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
                    <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">
                        Saldo añadido: <span style="color: #FFD700; font-weight: bold;">+<?php echo $saldo; ?>€</span>
                    </h2>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0;">
                        Saldo total: <span style="color: #FFD700; font-weight: bold;"><?php echo $saldo_actual; ?>€</span>
                    </h3>
                </div>
                
                <div class="action-buttons" style="margin-top: 2rem;">
                    <a href="/mis-anuncios" class="btn_codigo_amigo" style="display: inline-block; margin: 0.5rem; padding: 1rem 2rem; background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; border-radius: 10px; border: 2px solid white; transition: all 0.3s ease;">
                        <i class="fas fa-home" style="margin-right: 0.5rem;"></i>
                        Ir a Mis Anuncios
                    </a>
                    
                    <a href="/nuevo_codigo" class="btn_codigo_amigo" style="display: inline-block; margin: 0.5rem; padding: 1rem 2rem; background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; border-radius: 10px; border: 2px solid white; transition: all 0.3s ease;">
                        <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
                        Publicar Código
                    </a>
                </div>
            </div>
            
            <div class="info-section" style="margin-top: 3rem; text-align: center; color: #666;">
                <h3 style="color: #333; margin-bottom: 1rem;">
                    <i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #4CAF50;"></i>
                    Información del pago
                </h3>
                <p style="margin-bottom: 0.5rem;">
                    <strong>Paquete:</strong> <?php echo $paquete; ?>€
                </p>
                <p style="margin-bottom: 0.5rem;">
                    <strong>Saldo recibido:</strong> <?php echo $saldo; ?>€
                </p>
                <p style="margin-bottom: 0.5rem;">
                    <strong>ID de sesión:</strong> <?php echo substr($session_id, 0, 20); ?>...
                </p>
                <p style="margin-bottom: 0;">
                    <strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?>
                </p>
            </div>
        </div>
        
        <style>
        .success-container {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .btn_codigo_amigo:hover {
            background: rgba(255, 255, 255, 0.3) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        </style>
        
        <?php get_footer(); ?>
        
    <?php
    } else {
        // Pago OK en Stripe pero update saldo falló — caso crítico (dinero cobrado sin acreditar)
        log_critical("felicidades_recarga: pago OK pero no se pudo actualizar saldo", [
            'session_id' => $session_id,
            'user_id' => $_SESSION["user_id"],
            'paquete' => $paquete,
            'saldo' => $saldo,
            'modified_count' => $resultado->getModifiedCount(),
        ]);
        header("Location: /mis-anuncios?error=error_actualizando_saldo");
        exit;
    }

} catch (Exception $e) {
    log_error("felicidades_recarga: excepción verificando pago Stripe", [
        'session_id' => $session_id,
        'user_id' => $_SESSION["user_id"] ?? null,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    header("Location: /mis-anuncios?error=error_verificando_pago");
    exit;
}
?>