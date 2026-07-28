<?php
// La sesión ya está iniciada en app_with_mongo.php

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
    exit;
}

// Obtener parámetros
$codigo_id = $_GET['codigo'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$metodo = $_GET['metodo'] ?? 'tarjeta';
$session_id = $_GET['session_id'] ?? '';

if (empty($codigo_id) || empty($tipo)) {
    header("Location: /mis-anuncios?error=parametros_faltantes");
    exit;
}

// Obtener información del código
$obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
$codigo = getCodeByID($obj_id_codigo);

if (!$codigo || $codigo["id_usuario"] != $_SESSION["user_id"]) {
    header("Location: /mis-anuncios?error=codigo_no_encontrado");
    exit;
}

$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = htmlspecialchars($marca['nombre'] ?? $codigo['marca']);

// Si es pago con tarjeta, verificar con Stripe
$pago_exitoso = false;
if ($metodo === 'tarjeta' && !empty($session_id)) {
    try {
        require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
        
        // Determinar qué clave usar según el session_id
        // Los session IDs de test empiezan con cs_test_, los de live con cs_live_
        require_once __DIR__ . '/../config/stripe.php';
        $is_test = strpos($session_id, 'cs_test_') === 0;
        $stripe_secret_key = $is_test ? get_stripe_test_secret_key() : get_stripe_live_secret_key();
        
        error_log("Verificando sesión Stripe: $session_id (modo: " . ($is_test ? 'TEST' : 'LIVE') . ")");
        
        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        
        $session = $stripe->checkout->sessions->retrieve($session_id);
        
        // Verificar que el pago fue exitoso
        if ($session->payment_status !== 'paid') {
            error_log("ERROR: Sesión Stripe no pagada - Session: $session_id, Status: " . $session->payment_status);
            throw new Exception("El pago no fue completado. Estado: " . $session->payment_status);
        }
        
        // Verificar que el código coincide (usando client_reference_id o metadata)
        $codigo_coincide = false;
        if (isset($session->client_reference_id) && $session->client_reference_id == $codigo_id) {
            $codigo_coincide = true;
        } elseif (isset($session->metadata) && isset($session->metadata->codigo_id) && $session->metadata->codigo_id == $codigo_id) {
            $codigo_coincide = true;
        }
        
        if (!$codigo_coincide) {
            error_log("ERROR: Código no coincide - Session: $session_id, Código esperado: $codigo_id, Client ref: " . ($session->client_reference_id ?? 'N/A'));
            throw new Exception("El código de la sesión no coincide");
        }
        
        $pago_exitoso = true;
        
        // Actualizar el código para destacarlo
        $collection_codigos = getCollectionCodigos();
        $duracion_dias = $tipo === 'normal' ? DESTACADO_DURACION_NORMAL : DESTACADO_DURACION_SUPER;
        $fecha_fin = new DateTime();
        $fecha_fin->add(new DateInterval('P' . $duracion_dias . 'D'));
        
        // Preparar datos de actualización.
        //
        // La auto-renovación es OPT-IN: solo se activa si la sesión de Stripe
        // trae auto_renovar === '1'. Antes el valor por defecto era true y solo
        // se desactivaba con un '0' explícito; como crear_sesion_destacar.php
        // ni siquiera enviaba ese metadato, todos los pagos quedaban con la
        // renovación activada aunque la casilla fuese sin marcar. Es un cobro
        // recurrente sin consentimiento, así que ante la duda no se activa.
        $auto_renovar = isset($session->metadata->auto_renovar)
            && $session->metadata->auto_renovar === '1';
        $update_data = [
            'estado' => 0, // Reactivar código si estaba desactivado/caducado (-2/-3)
            'destacado' => time(), // Usar timestamp en lugar de true para consistencia
            'tipo_destacado' => $tipo,
            'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
            'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000),
            'prioridad_pago' => time(),
            'auto_renovar_destacado' => $auto_renovar,
            'aviso_expiracion_enviado' => false,
            'aviso_expirado_enviado' => false
        ];
        
        // Para destacado "super", establecer también destacado_social (aparece en home y tiene prioridad)
        if ($tipo === 'super') {
            $update_data['destacado_social'] = time();
            error_log("Destacado SUPER: Estableciendo destacado_social para código $codigo_id");
        }
        
        $resultado_destacado = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => $update_data]
        );
        
        if ($resultado_destacado->getModifiedCount() > 0) {
            error_log("SUCCESS: Código destacado correctamente - Código ID: $codigo_id, Tipo: $tipo, Destacado: " . $update_data['destacado']);
            
            // Si es destacado super, notificar a todos los usuarios con códigos en el home
            if ($tipo === 'super') {
                if (function_exists('notificar_competencia_home_destacado_super')) {
                    $codigo_actualizado = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
                    $emails_enviados = notificar_competencia_home_destacado_super(
                        $codigo_id,
                        $_SESSION["user_id"],
                        $codigo_actualizado
                    );
                    error_log("Notificaciones de competencia home enviadas: $emails_enviados");
                }
            }
            
            // Notificar a usuarios con códigos destacados activos en la misma marca
            if (!function_exists('notificarCompetenciaDestacado')) {
                require_once __DIR__ . '/../myphp/funciones_destacados_email.php';
            }
            $marca_clave = $codigo['marca'] ?? '';
            if ($marca_clave) {
                $notifs = notificarCompetenciaDestacado($marca_clave, $_SESSION["user_id"], $tipo);
                error_log("Notificaciones competencia marca ($marca_clave): $notifs enviadas");
            }
            
            // Verificar que se actualizó correctamente
            $codigo_verificado = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
            if ($codigo_verificado) {
                $destacado_verificado = isset($codigo_verificado['destacado']) ? $codigo_verificado['destacado'] : 'NO';
                error_log("VERIFICACIÓN: Código $codigo_id tiene destacado = $destacado_verificado");
            }
        } else {
            if ($resultado_destacado->getMatchedCount() == 0) {
                error_log("ERROR: Código no encontrado para destacar - Código ID: $codigo_id");
            } else {
                error_log("WARNING: Código encontrado pero no se modificó - Código ID: $codigo_id (puede que ya esté destacado con los mismos valores)");
            }
        }
        
        // Registrar transacción (si no existe ya por webhook)
        $collection_transacciones = getCollectionTransacciones();
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $session_id]);
        
        if (!$existe) {
            // Obtener cantidad real de la sesión o usar precio por defecto
            $precio = isset($session->amount_total) ? ($session->amount_total / 100) : ($tipo === 'normal' ? 0.99 : 3.99);
            $marca_nombre = $codigo['marca'] ?? '';
            
            // Extraer metadata si está disponible
            $metadata = [];
            if (isset($session->metadata)) {
                if (is_object($session->metadata)) {
                    foreach (['usuario_id', 'codigo_id', 'marca', 'tipo_destacado'] as $key) {
                        if (isset($session->metadata->$key)) {
                            $metadata[$key] = $session->metadata->$key;
                        }
                    }
                } else {
                    $metadata = (array)$session->metadata;
                }
            }
            
            $transaccion = [
                'usuario_id' => $metadata['usuario_id'] ?? $_SESSION["user_id"],
                'tipo' => 'destacado',
                'subtipo' => $tipo,
                'cantidad' => $precio,
                'descripcion' => "Destacado de código - Marca: {$marca_nombre} - Tipo: {$tipo}",
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completada',
                'codigo_id' => $codigo_id,
                'marca' => $marca_nombre,
                'tipo_destacado' => $tipo,
                'stripe_session_id' => $session_id,
                'stripe_payment_intent' => $session->payment_intent ?? null,
                'stripe_customer_email' => isset($session->customer_details) && isset($session->customer_details->email) 
                    ? $session->customer_details->email 
                    : null,
                'stripe_payment_status' => $session->payment_status ?? 'paid',
                'metodo_pago' => 'tarjeta',
                'registrado_desde' => 'felicidades_destacar' // Marca que fue registrado desde backup
            ];
            
            try {
                $resultado_insert = $collection_transacciones->insertOne($transaccion);
                if ($resultado_insert->getInsertedCount() > 0) {
                    error_log("✓ Transacción registrada desde felicidades_destacar.php (backup): " . $session_id);
                } else {
                    error_log("WARNING: InsertOne no devolvió insertedCount > 0 para sesión: " . $session_id);
                }
            } catch (Exception $e) {
                error_log("ERROR al insertar transacción desde felicidades_destacar.php: " . $e->getMessage());
                // No lanzar excepción aquí para no bloquear el flujo
            }
        } else {
            error_log("INFO: Transacción ya existe en MongoDB (probablemente registrada por webhook): " . $session_id);
        }
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        error_log("ERROR Stripe: Sesión no encontrada o inválida - Session: $session_id, Error: " . $e->getMessage());
        // Si la sesión no existe, podría ser un problema de timing o de clave incorrecta
    } catch (Exception $e) {
        error_log("ERROR verificando pago Stripe: " . $e->getMessage() . " | Session: $session_id");
        // No establecer $pago_exitoso = false aquí, dejar que el flujo continúe
    }
} elseif ($metodo === 'saldo') {
    // Si es pago con saldo, verificar que el código esté realmente destacado
    if (isset($codigo['destacado']) && $codigo['destacado'] && 
        isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === $tipo) {
        $pago_exitoso = true;
    } else {
        // Si no está destacado, intentar destacarlo usando la función moderna
        if (destacar_codigo_moderno($codigo_id, $tipo)) {
            $pago_exitoso = true;
            // Recargar los datos del código
            $codigo = getCodeByID($obj_id_codigo);
        } else {
            $pago_exitoso = false;
        }
    }
}

if (!$pago_exitoso) {
    header("Location: /mis-anuncios?error=pago_no_verificado");
    exit;
}

// Configurar información para la página
$titulo_destacado = $tipo === 'normal' ? 'Destacado Normal' : 'Destacado Super';
$precio_destacado = $tipo === 'normal' ? '0,99€' : '3,99€';
$duracion_dias = $tipo === 'normal' ? DESTACADO_DURACION_NORMAL : DESTACADO_DURACION_SUPER;
$fecha_expiracion = date('d/m/Y', time() + ($duracion_dias * 86400));
$duracion_destacado = $duracion_dias . ' días (hasta el ' . $fecha_expiracion . ')';
$descripcion_destacado = $tipo === 'normal' 
    ? 'Badge "Destacado" en la página de la marca durante ' . $duracion_dias . ' días'
    : 'Badge dorado "Super Destacado" en la marca + carrusel de la página principal durante ' . $duracion_dias . ' días';

$title = "¡Código destacado exitosamente! - Código Amigo";
$description = "Tu código de " . $marca_nombre . " ha sido destacado correctamente";

// No incluir header para evitar puntos de fuga
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<style>
/* Estilos para la página de felicidades destacar */
body {
    background: linear-gradient(135deg, #E30613 0%, #f7931e 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden;
}

.success-container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    animation: slideInUp 0.8s ease-out;
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin-top: 80px;
    margin-bottom: 40px;
}

.success-icon {
    font-size: 80px;
    color: #fff;
    margin-bottom: 20px;
    animation: bounceIn 1s ease-out;
}

h1 {
    font-size: 3em;
    margin-bottom: 15px;
    font-weight: 700;
    text-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

p {
    font-size: 1.2em;
    margin-bottom: 25px;
    line-height: 1.6;
}

.details-box {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
    text-align: left;
}

.details-box p {
    margin-bottom: 10px;
    font-size: 1em;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.details-box p strong {
    color: #f0f0f0;
}

.btn-group {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-custom {
    background: #fff;
    color: #E30613;
    border: none;
    padding: 12px 25px;
    border-radius: 50px;
    font-size: 1.1em;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.btn-custom:hover {
    background: #e0e0e0;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    color: #E30613;
    text-decoration: none;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes bounceIn {
    0%, 20%, 40%, 60%, 80%, 100% {
        -webkit-transform: translateY(0);
        transform: translateY(0);
    }
    50% {
        -webkit-transform: translateY(-20px);
        transform: translateY(-20px);
    }
}

@media (max-width: 768px) {
    h1 {
        font-size: 2.2em;
    }
    p {
        font-size: 1em;
    }
    .success-container {
        padding: 25px;
    }
    .btn-group {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<div class="success-container">
    <i class="fas fa-star success-icon"></i>
    <h1>¡Código Destacado!</h1>
    <p>Tu código de descuento ha sido destacado exitosamente</p>
    
    <div class="details-box">
        <p>Marca: <strong><?php echo htmlspecialchars($marca_nombre); ?></strong></p>
        <p>Código: <strong><?php echo htmlspecialchars($codigo['codigo'] ?? 'N/A'); ?></strong></p>
        <p>Tipo de destacado: <strong><?php echo $titulo_destacado; ?></strong></p>
        <p>Precio pagado: <strong><?php echo $precio_destacado; ?></strong></p>
        <p>Método de pago: <strong><?php echo $metodo === 'tarjeta' ? 'Tarjeta de crédito' : 'Saldo de la cuenta'; ?></strong></p>
        <p>Duración: <strong><?php echo $duracion_destacado; ?></strong></p>
        <p>Beneficios: <strong><?php echo $descripcion_destacado; ?></strong></p>
        <p>Fecha: <strong><?php echo date('d/m/Y H:i'); ?></strong></p>
    </div>
    
    <div style="background: rgba(255, 255, 255, 0.15); border-radius: 10px; padding: 20px; margin-top: 20px; text-align: left; border-left: 4px solid #fff;">
        <h3 style="margin: 0 0 10px 0; font-size: 1.1em; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-sync-alt"></i> Renovación Automática Activada
        </h3>
        <p style="margin: 0; font-size: 0.95em; line-height: 1.5; color: rgba(255,255,255,0.9);">
            Tu código se ha configurado para auto-renovarse garantizando que no pierdas su posición. 
            <strong>💡 El truco definitivo:</strong> Si te haces <strong><a href="/vip" style="color: #fff; font-weight: bold; text-decoration: underline;">Usuario VIP</a></strong>, recibirás saldo gratis automáticamente cada mes para que tus destacados se paguen solos. ¡Nunca más tendrás que recargar a mano!
        </p>
    </div>
    
    <div class="btn-group">
        <a href="/mis-anuncios" class="btn-custom">
            <i class="fas fa-home"></i> Ir a Mis Anuncios
        </a>
        <a href="/marca/<?php echo $codigo['marca']; ?>" class="btn-custom">
            <i class="fas fa-eye"></i> Ver Código Destacado
        </a>
    </div>
</div>

</body>
</html>