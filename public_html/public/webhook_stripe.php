<?php
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

$endpoint_secret = get_stripe_webhook_secret();

// Función para logging detallado
// Antes usaba error_log() en crudo: con este pool FPM (sin error_log ni
// catch_workers_output configurados) el mensaje no llegaba a NINGÚN fichero
// - los fallos de cobro de suscripciones eran indiagnosticables.
function logWebhook($message, $data = null, $level = 'INFO') {
    $logMessage = "[WEBHOOK] $message";

    if ($data !== null) {
        if (is_object($data) || is_array($data)) {
            $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $logMessage .= " | Data: " . $data;
        }
    }

    if ($level === 'ERROR' && function_exists('log_error')) {
        log_error($logMessage);
    } elseif (function_exists('log_info')) {
        log_info($logMessage);
    } else {
        error_log($logMessage);
    }
}

// Obtener el payload y la firma
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Log inicial del webhook recibido
logWebhook("Webhook recibido de Stripe", [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
    'has_signature' => !empty($sig_header),
    'payload_size' => strlen($payload)
]);

// Validar que tenemos el payload
if (empty($payload)) {
    logWebhook("ERROR: Payload vacío", null, 'ERROR');
    http_response_code(400);
    echo "Empty payload";
    exit();
}

// Validar que tenemos la firma
if (empty($sig_header)) {
    logWebhook("ERROR: Firma de webhook no proporcionada", null, 'ERROR');
    http_response_code(400);
    echo "Missing signature";
    exit();
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    logWebhook("Evento de Stripe verificado correctamente", [
        'event_id' => $event->id ?? 'UNKNOWN',
        'event_type' => $event->type ?? 'UNKNOWN',
        'livemode' => $event->livemode ?? false
    ]);
} catch(\UnexpectedValueException $e) {
    logWebhook("ERROR: Payload inválido", [
        'error' => $e->getMessage(),
        'payload_preview' => substr($payload, 0, 200)
    ], 'ERROR');
    http_response_code(400);
    echo "Invalid payload";
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    logWebhook("ERROR: Firma inválida", [
        'error' => $e->getMessage(),
        'endpoint_secret_configured' => !empty($endpoint_secret) && $endpoint_secret !== 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD'
    ], 'ERROR');
    http_response_code(400);
    echo "Invalid signature";
    exit();
} catch(Exception $e) {
    logWebhook("ERROR: Excepción inesperada al verificar webhook", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 'ERROR');
    http_response_code(500);
    echo "Internal error";
    exit();
}

// Manejar el evento
logWebhook("Procesando evento de Stripe", [
    'event_id' => $event->id ?? 'UNKNOWN',
    'event_type' => $event->type ?? 'UNKNOWN',
    'created' => $event->created ?? 'UNKNOWN'
]);

if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;
    
    logWebhook("Checkout completado", [
        'session_id' => $session->id ?? 'UNKNOWN',
        'payment_status' => $session->payment_status ?? 'UNKNOWN',
        'amount_total' => $session->amount_total ?? 'UNKNOWN',
        'currency' => $session->currency ?? 'UNKNOWN',
        'has_metadata' => isset($session->metadata),
        'metadata_keys' => isset($session->metadata)
            ? array_keys(($session->metadata instanceof \Stripe\StripeObject) ? $session->metadata->toArray() : (array)$session->metadata)
            : []
    ]);
    
    // Validar que el pago fue exitoso
    if (!isset($session->payment_status) || $session->payment_status !== 'paid') {
        logWebhook("WARNING: Sesión no pagada, ignorando", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'payment_status' => $session->payment_status ?? 'UNKNOWN'
        ], 'WARNING');
        http_response_code(200);
        echo "Payment not completed";
        exit();
    }
    
    // Validar que existe metadata
    if (!isset($session->metadata) || empty($session->metadata)) {
        logWebhook("WARNING: Sesión sin metadata, ignorando", [
            'session_id' => $session->id ?? 'UNKNOWN'
        ], 'WARNING');
        http_response_code(200);
        echo "No metadata";
        exit();
    }
    
    // Convertir metadata a array para facilitar el acceso
    // OJO: $session->metadata es un \Stripe\StripeObject; (array) expone
    // props internas (_values, _opts...) con bytes nulos en vez de las claves.
    // Hay que usar ->toArray() para obtener las claves reales (tipo, codigo_id...).
    $metadata = ($session->metadata instanceof \Stripe\StripeObject)
        ? $session->metadata->toArray()
        : (array)$session->metadata;
    $tipo_metadata = $metadata['tipo'] ?? null;
    
    logWebhook("Metadata de sesión", [
        'session_id' => $session->id ?? 'UNKNOWN',
        'tipo' => $tipo_metadata,
        'metadata_completo' => $metadata
    ]);
    
    // Verificar que es un pago de destacar código
    if ($tipo_metadata === 'destacar_codigo') {
        
        logWebhook("Procesando pago de destacar código", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'metadata' => $metadata
        ]);
        
        // Validar campos requeridos en metadata
        $campos_requeridos = ['usuario_id', 'codigo_id', 'tipo_destacado'];
        $campos_faltantes = [];
        
        foreach ($campos_requeridos as $campo) {
            if (!isset($metadata[$campo]) || empty($metadata[$campo])) {
                $campos_faltantes[] = $campo;
            }
        }
        
        if (!empty($campos_faltantes)) {
            logWebhook("ERROR: Metadata incompleto, faltan campos requeridos", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'campos_faltantes' => $campos_faltantes,
                'metadata_recibido' => $metadata
            ], 'ERROR');
            http_response_code(200);
            echo "Missing required metadata";
            exit();
        }
        
        $collection_transacciones = getCollectionTransacciones();
        
        // Verificar si ya existe esta transacción (evitar duplicados)
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $session->id]);
        if ($existe) {
            logWebhook("Transacción ya existe en MongoDB, ignorando duplicado", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'transaccion_id' => (string)($existe['_id'] ?? 'UNKNOWN')
            ]);
            http_response_code(200);
            echo "Transaction already exists";
            exit();
        }
        
        // Preparar datos de la transacción con validación
        $marca = $metadata['marca'] ?? '';
        $tipo_destacado = $metadata['tipo_destacado'] ?? 'normal';
        $cantidad = isset($session->amount_total) ? ($session->amount_total / 100) : 0;
        
        // Validar que la cantidad es válida
        if ($cantidad <= 0) {
            logWebhook("ERROR: Cantidad inválida", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'amount_total' => $session->amount_total ?? 'UNKNOWN',
                'cantidad_calculada' => $cantidad
            ], 'ERROR');
            http_response_code(200);
            echo "Invalid amount";
            exit();
        }
        
        // Obtener saldo anterior del usuario (para pagos con tarjeta no cambia el saldo pero guardamos histórico)
        $collection_usuarios = getCollectionUsuarios();
        $usuario_actual = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($metadata['usuario_id'])]);
        $saldo_anterior = $usuario_actual['saldo'] ?? 0;
        
        // Registrar transacción en MongoDB
        $transaccion = [
            'usuario_id' => $metadata['usuario_id'],
            'tipo' => 'destacado',
            'subtipo' => $tipo_destacado,
            'cantidad' => $cantidad,
            'descripcion' => "Destacado de código - Marca: {$marca} - Tipo: {$tipo_destacado}",
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completada',
            'codigo_id' => $metadata['codigo_id'],
            'marca' => $marca,
            'tipo_destacado' => $tipo_destacado,
            // Datos de Stripe
            'stripe_session_id' => $session->id,
            'stripe_payment_intent' => $session->payment_intent ?? null,
            'stripe_customer_email' => isset($session->customer_details) && isset($session->customer_details->email) 
                ? $session->customer_details->email 
                : null,
            'stripe_payment_status' => $session->payment_status ?? 'paid',
            'metodo_pago' => 'tarjeta',
            'saldo_anterior' => $saldo_anterior,
            'saldo_nuevo' => $saldo_anterior // En pagos con tarjeta el saldo no cambia
        ];
        
        try {
            $resultado = $collection_transacciones->insertOne($transaccion);
            
            if ($resultado->getInsertedCount() > 0) {
                logWebhook("Transacción registrada exitosamente desde webhook", [
                    'session_id' => $session->id ?? 'UNKNOWN',
                    'transaccion_id' => (string)$resultado->getInsertedId(),
                    'usuario_id' => $metadata['usuario_id'],
                    'codigo_id' => $metadata['codigo_id'],
                    'cantidad' => $cantidad,
                    'tipo_destacado' => $tipo_destacado
                ]);
            } else {
                logWebhook("WARNING: InsertOne no devolvió insertedCount > 0", [
                    'session_id' => $session->id ?? 'UNKNOWN'
                ], 'WARNING');
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            logWebhook("ERROR: Excepción de MongoDB al insertar transacción", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
        } catch (Exception $e) {
            logWebhook("ERROR: Excepción general al insertar transacción", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
        }

        // Activar destacado en el código
        try {
            $collection_codigos = getCollectionCodigos();
            $duracion_dias = ($tipo_destacado === 'super') ? DESTACADO_DURACION_SUPER : DESTACADO_DURACION_NORMAL;
            $update_destacado = [
                'destacado' => time(),
                'tipo_destacado' => $tipo_destacado,
                'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
                'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime((time() + ($duracion_dias * 86400)) * 1000),
                'aviso_expiracion_enviado' => false,
                'aviso_expirado_enviado' => false,
            ];
            if ($tipo_destacado === 'super') {
                $update_destacado['destacado_social'] = time();
            }
            $resultado_destaca = $collection_codigos->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($metadata['codigo_id'])],
                ['$set' => $update_destacado]
            );
            logWebhook("Código activado como destacado", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'codigo_id' => $metadata['codigo_id'],
                'tipo_destacado' => $tipo_destacado,
                'duracion_dias' => $duracion_dias,
                'modified' => $resultado_destaca->getModifiedCount()
            ]);
        } catch (Exception $e) {
            logWebhook("ERROR: No se pudo activar destacado en código", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'codigo_id' => $metadata['codigo_id'] ?? 'UNKNOWN',
                'error' => $e->getMessage()
            ], 'ERROR');
        }

        // Cerrar la intención de checkout (evita email de recuperación de un pago ya completado)
        try {
            $db_intents = createConnection();
            if ($db_intents && !empty($session->id)) {
                $db_intents->selectCollection('destacar_checkout_intents')->updateOne(
                    ['session_id' => $session->id],
                    ['$set' => ['status' => 'completed', 'completed_at' => new MongoDB\BSON\UTCDateTime()]]
                );
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) { log_error("No se pudo cerrar intent destacar: " . $e->getMessage()); }
        }

        // Cross-sell VIP: si el comprador no es VIP, ofrecerle la suscripción
        // (10€/mes de saldo cubre destacados como el que acaba de pagar aparte).
        // Envuelto en try/catch propio para no afectar el pago ya confirmado.
        try {
            $usuario_id_str = $metadata['usuario_id'];
            if (!es_usuario_vip($usuario_id_str)) {
                $usuario_comprador = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id_str)]);
                $cooldown_ok = true;
                if ($usuario_comprador && isset($usuario_comprador['email_post_compra_destacado_fecha'])) {
                    $last = $usuario_comprador['email_post_compra_destacado_fecha'];
                    if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > strtotime('-30 days')) {
                        $cooldown_ok = false;
                    }
                }

                if ($usuario_comprador && $cooldown_ok
                    && !empty($usuario_comprador['mail'])
                    && filter_var($usuario_comprador['mail'], FILTER_VALIDATE_EMAIL)
                    && (!function_exists('usuarioAceptaEmail') || usuarioAceptaEmail($usuario_id_str, 'cross_sell_destacar_vip'))
                ) {
                    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                        include_once __DIR__ . '/../myphp/funciones_destacados_email.php';
                    }

                    $username_comprador = trim($usuario_comprador['username'] ?? 'Usuario');
                    $cantidad_pagada = number_format($cantidad, 2, ',', '.');

                    $contenido = '
                        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username_comprador) . '</strong>,</p>
                        <p>Acabas de pagar <strong>' . $cantidad_pagada . '€</strong> por destacar tu código. Con VIP, ese gasto te habría salido gratis.</p>

                        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
                            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Con VIP (9,99€/mes) consigues:</p>
                            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos, sin pagar de tu bolsillo</p>
                            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics</p>
                            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> y <strong>auto-renovación</strong> de tus destacados</p>
                        </div>

                        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:14px 16px;">
                            <p style="margin:0;color:#c7254e;font-weight:600;font-size:14px;">Primer mes a mitad de precio: 4,99€.</p>
                        </div>';

                    $html = _templateBaseDestacadoEmail('Esto te habría salido gratis con VIP', $contenido, 'Hazte VIP ahora', 'https://www.codigoamigo.com/public/suscripcion_vip.php');
                    $text = "Hola $username_comprador,\n\nAcabas de pagar $cantidad_pagada€ por destacar tu código. Con VIP (9,99€/mes, primer mes 4,99€) consigues 10€ de saldo gratis cada mes, badge verificado, chat ilimitado y auto-renovación de destacados.\n\nHazte VIP: https://www.codigoamigo.com/public/suscripcion_vip.php";

                    $resultado_email = enviarEmailConBrevoYRegistrar(
                        $usuario_comprador['mail'], $username_comprador,
                        "$username_comprador, esto te habría salido gratis con VIP",
                        $html, 'cross_sell_destacar_vip', $usuario_id_str,
                        ['cantidad_pagada' => $cantidad, 'codigo_id' => $metadata['codigo_id']],
                        $text
                    );

                    if (!empty($resultado_email['success'])) {
                        $collection_usuarios->updateOne(
                            ['_id' => new MongoDB\BSON\ObjectId($usuario_id_str)],
                            ['$set' => ['email_post_compra_destacado_fecha' => new MongoDB\BSON\UTCDateTime()]]
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            logWebhook("ERROR: No se pudo enviar cross-sell VIP post-compra destacado", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'error' => $e->getMessage()
            ], 'ERROR');
        }
    }
    // También manejar recargas de saldo
    elseif ($tipo_metadata === 'recarga_saldo') {
        
        logWebhook("Procesando recarga de saldo", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'metadata' => $metadata
        ]);
        
        // Validar campos requeridos
        if (!isset($metadata['usuario_id']) || empty($metadata['usuario_id'])) {
            logWebhook("ERROR: Metadata de recarga incompleto, falta usuario_id", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'metadata_recibido' => $metadata
            ], 'ERROR');
            http_response_code(200);
            echo "Missing required metadata";
            exit();
        }
        
        $collection_transacciones = getCollectionTransacciones();
        
        // Verificar si ya existe esta transacción
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $session->id]);
        if ($existe) {
            logWebhook("Transacción de recarga ya existe en MongoDB, ignorando duplicado", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'transaccion_id' => (string)($existe['_id'] ?? 'UNKNOWN')
            ]);
            http_response_code(200);
            echo "Transaction already exists";
            exit();
        }
        
        // Calcular cantidad de recarga
        $cantidad_recarga = isset($metadata['saldo']) ? (float)$metadata['saldo'] : (isset($session->amount_total) ? ($session->amount_total / 100) : 0);
        $paquete = $metadata['paquete'] ?? 'N/A';
        
        // Obtener saldo anterior antes de actualizar
        $collection_usuarios = getCollectionUsuarios();
        $usuario_actual = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($metadata['usuario_id'])]);
        $saldo_anterior = $usuario_actual['saldo'] ?? 0;
        
        // Registrar transacción de recarga
        $transaccion = [
            'usuario_id' => $metadata['usuario_id'],
            'tipo' => 'recarga',
            'cantidad' => $cantidad_recarga,
            'descripcion' => "Recarga de saldo - Paquete {$paquete}€",
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completada',
            'stripe_session_id' => $session->id,
            'stripe_payment_intent' => $session->payment_intent ?? null,
            'stripe_customer_email' => isset($session->customer_details) && isset($session->customer_details->email) 
                ? $session->customer_details->email 
                : null,
            'stripe_payment_status' => $session->payment_status ?? 'paid',
            'metodo_pago' => 'tarjeta',
            'paquete' => $paquete,
            'saldo_anterior' => $saldo_anterior,
            'saldo_nuevo' => $saldo_anterior + $cantidad_recarga
        ];
        
        try {
            $resultado = $collection_transacciones->insertOne($transaccion);
            
            if ($resultado->getInsertedCount() > 0) {
                logWebhook("Transacción de recarga registrada exitosamente desde webhook", [
                    'session_id' => $session->id ?? 'UNKNOWN',
                    'transaccion_id' => (string)$resultado->getInsertedId(),
                    'usuario_id' => $metadata['usuario_id'],
                    'cantidad' => $cantidad_recarga,
                    'paquete' => $paquete
                ]);
            } else {
                logWebhook("WARNING: InsertOne de recarga no devolvió insertedCount > 0", [
                    'session_id' => $session->id ?? 'UNKNOWN'
                ], 'WARNING');
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            logWebhook("ERROR: Excepción de MongoDB al insertar recarga", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
        } catch (Exception $e) {
            logWebhook("ERROR: Excepción general al insertar recarga", [
                'session_id' => $session->id ?? 'UNKNOWN',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
        }

        // Cerrar la intención de checkout (evita email de recuperación de un pago ya completado)
        try {
            $db_intents = createConnection();
            if ($db_intents && !empty($session->id)) {
                $db_intents->selectCollection('destacar_checkout_intents')->updateOne(
                    ['session_id' => $session->id],
                    ['$set' => ['status' => 'completed', 'completed_at' => new MongoDB\BSON\UTCDateTime()]]
                );
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) { log_error("No se pudo cerrar intent recarga: " . $e->getMessage()); }
        }
    }
    // Manejar suscripción premium
    elseif ($tipo_metadata === 'suscripcion_premium') {
        
        logWebhook("Procesando suscripción premium", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'metadata' => $metadata
        ]);
        
        // En este punto, la suscripción ya se creó en Stripe
        // Necesitamos esperar al evento subscription.created para activarla
        // Por ahora solo registramos que el checkout se completó
        logWebhook("Checkout de suscripción premium completado - esperando evento subscription.created", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'usuario_id' => $metadata['usuario_id'] ?? 'UNKNOWN'
        ]);
    }
    // Manejar suscripción VIP (9,99€/mes)
    elseif ($tipo_metadata === 'suscripcion_vip') {
        
        logWebhook("Procesando suscripción VIP", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'metadata' => $metadata
        ]);
        
        // Cerrar la intención de checkout (embudo de conversión)
        try {
            $db_intents = createConnection();
            if ($db_intents && !empty($session->id)) {
                $db_intents->selectCollection('vip_checkout_intents')->updateOne(
                    ['session_id' => $session->id],
                    ['$set' => ['status' => 'completed', 'completed_at' => new MongoDB\BSON\UTCDateTime()]]
                );
            }
        } catch (Throwable $e) {
            log_error("No se pudo cerrar intent VIP: " . $e->getMessage());
        }

        // Esperar al evento subscription.created para activar completamente
        logWebhook("Checkout de suscripción VIP completado - esperando evento subscription.created", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'usuario_id' => $metadata['usuario_id'] ?? 'UNKNOWN'
        ]);
    } else {
        // Tipo de metadata no reconocido
        logWebhook("INFO: Tipo de metadata no reconocido, ignorando", [
            'session_id' => $session->id ?? 'UNKNOWN',
            'tipo_metadata' => $tipo_metadata,
            'metadata_completo' => $metadata
        ]);
    }
} 
// Manejar eventos de suscripción
elseif ($event->type === 'customer.subscription.created') {
    $subscription = $event->data->object;
    
    logWebhook("Suscripción creada", [
        'subscription_id' => $subscription->id ?? 'UNKNOWN',
        'customer_id' => $subscription->customer ?? 'UNKNOWN',
        'status' => $subscription->status ?? 'UNKNOWN',
        'has_metadata' => isset($subscription->metadata)
    ]);
    
    // Verificar si es una suscripción premium
    if (isset($subscription->metadata) && isset($subscription->metadata->tipo) && $subscription->metadata->tipo === 'suscripcion_premium') {
        $usuario_id = $subscription->metadata->usuario_id ?? null;
        
        if ($usuario_id) {
            // Incluir funciones premium
            if (!function_exists('activarSuscripcionPremium')) {
                include_once __DIR__ . '/../myphp/funciones_premium.php';
            }
            
            // Activar suscripción premium
            $resultado = activarSuscripcionPremium($usuario_id, $subscription->id, 1);
            
            if ($resultado) {
                logWebhook("Suscripción premium activada exitosamente", [
                    'subscription_id' => $subscription->id ?? 'UNKNOWN',
                    'usuario_id' => $usuario_id
                ]);
            } else {
                logWebhook("ERROR: No se pudo activar suscripción premium", [
                    'subscription_id' => $subscription->id ?? 'UNKNOWN',
                    'usuario_id' => $usuario_id
                ], 'ERROR');
            }
        }
    }
    // Verificar si es una suscripción VIP (9,99€/mes)
    // NOTA: La activación real del VIP se hace en invoice.paid para evitar doble crédito de saldo
    // (subscription.created e invoice.paid llegan casi simultáneamente en la primera suscripción)
    elseif (isset($subscription->metadata) && isset($subscription->metadata->tipo) && $subscription->metadata->tipo === 'suscripcion_vip') {
        $usuario_id = $subscription->metadata->usuario_id ?? null;
        
        logWebhook("Suscripción VIP creada - la activación se realizará via invoice.paid", [
            'subscription_id' => $subscription->id ?? 'UNKNOWN',
            'usuario_id' => $usuario_id,
            'status' => $subscription->status ?? 'UNKNOWN'
        ]);
    }
}
elseif ($event->type === 'customer.subscription.deleted' || $event->type === 'customer.subscription.updated') {
    $subscription = $event->data->object;
    
    logWebhook("Suscripción " . ($event->type === 'deleted' ? 'eliminada' : 'actualizada'), [
        'subscription_id' => $subscription->id ?? 'UNKNOWN',
        'status' => $subscription->status ?? 'UNKNOWN',
        'cancel_at_period_end' => $subscription->cancel_at_period_end ?? false
    ]);
    
    // Determinar tipo de suscripción
    $tipo_suscripcion = $subscription->metadata->tipo ?? null;
    $usuario_id = $subscription->metadata->usuario_id ?? null;
    
    // Verificar si es una suscripción premium
    if ($tipo_suscripcion === 'suscripcion_premium') {
        // Si la suscripción fue cancelada, eliminada, 'unpaid' (dunning agotado) o programada
        if ($event->type === 'deleted' || $subscription->status === 'canceled' || $subscription->status === 'unpaid' || $subscription->cancel_at_period_end === true) {
            if ($usuario_id) {
                if (!function_exists('desactivarSuscripcionPremium')) {
                    include_once __DIR__ . '/../myphp/funciones_premium.php';
                }
                
                if ($subscription->cancel_at_period_end === true) {
                    logWebhook("Suscripción premium programada para cancelación al final del período", [
                        'subscription_id' => $subscription->id ?? 'UNKNOWN',
                        'usuario_id' => $usuario_id,
                        'current_period_end' => $subscription->current_period_end ?? 'UNKNOWN'
                    ]);
                } else {
                    $resultado = desactivarSuscripcionPremium($usuario_id);
                    logWebhook($resultado ? "Suscripción premium desactivada exitosamente" : "ERROR: No se pudo desactivar suscripción premium", [
                        'subscription_id' => $subscription->id ?? 'UNKNOWN',
                        'usuario_id' => $usuario_id
                    ], $resultado ? 'INFO' : 'ERROR');
                }
            }
        }
        // Si la suscripción fue reactivada
        elseif ($subscription->status === 'active' && $subscription->cancel_at_period_end === false) {
            if ($usuario_id) {
                if (!function_exists('activarSuscripcionPremium')) {
                    include_once __DIR__ . '/../myphp/funciones_premium.php';
                }
                $current_period_end = $subscription->current_period_end ?? time() + (30 * 24 * 60 * 60);
                $meses_restantes = max(1, ceil(($current_period_end - time()) / (30 * 24 * 60 * 60)));
                $resultado = activarSuscripcionPremium($usuario_id, $subscription->id, $meses_restantes);
                if ($resultado) {
                    logWebhook("Suscripción premium reactivada exitosamente", [
                        'subscription_id' => $subscription->id ?? 'UNKNOWN',
                        'usuario_id' => $usuario_id
                    ]);
                }
            }
        }
    }
    // Verificar si es una suscripción VIP
    elseif ($tipo_suscripcion === 'suscripcion_vip') {
        if (!function_exists('activar_vip')) {
            include_once __DIR__ . '/../myphp/funciones_usuario.php';
        }
        
        // Si la suscripción fue cancelada, eliminada o marcada 'unpaid' (dunning agotado).
        // 'unpaid' importa: si Stripe deja de reintentar y NO cancela, sin esto el
        // usuario mantendría VIP gratis hasta vip_expires_at.
        if ($event->type === 'deleted' || $subscription->status === 'canceled' || $subscription->status === 'unpaid') {
            if ($usuario_id) {
                $resultado = desactivar_vip($usuario_id);
                logWebhook($resultado ? "Suscripción VIP desactivada exitosamente" : "ERROR: No se pudo desactivar suscripción VIP", [
                    'subscription_id' => $subscription->id ?? 'UNKNOWN',
                    'usuario_id' => $usuario_id
                ], $resultado ? 'INFO' : 'ERROR');
            }
        }
        // Programada para cancelar al final del período
        elseif ($subscription->cancel_at_period_end === true) {
            logWebhook("Suscripción VIP programada para cancelación al final del período", [
                'subscription_id' => $subscription->id ?? 'UNKNOWN',
                'usuario_id' => $usuario_id,
                'current_period_end' => $subscription->current_period_end ?? 'UNKNOWN'
            ]);
            // Sync cancel pending flag in MongoDB
            if ($usuario_id) {
                try {
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                        ['$set' => ['vip_cancel_pending' => true]]
                    );
                } catch (Throwable $e) {
                    logWebhook("ERROR: No se pudo marcar vip_cancel_pending", ['error' => $e->getMessage()], 'ERROR');
                }
            }
        }
        // Si la suscripción fue reactivada
        elseif ($subscription->status === 'active' && $subscription->cancel_at_period_end === false) {
            if ($usuario_id) {
                $current_period_end = $subscription->current_period_end ?? time() + (30 * 24 * 60 * 60);
                $expires_at = new DateTime();
                $expires_at->setTimestamp($current_period_end);
                $resultado = activar_vip($usuario_id, $subscription->id, $expires_at);
                if ($resultado) {
                    logWebhook("Suscripción VIP reactivada exitosamente", [
                        'subscription_id' => $subscription->id ?? 'UNKNOWN',
                        'usuario_id' => $usuario_id
                    ]);
                }
                // Clear cancel pending flag
                try {
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                        ['$set' => ['vip_cancel_pending' => false]]
                    );
                } catch (Throwable $e) {
                    logWebhook("ERROR: No se pudo limpiar vip_cancel_pending", ['error' => $e->getMessage()], 'ERROR');
                }
            }
        }
    }
}
elseif ($event->type === 'invoice.paid') {
    $invoice = $event->data->object;
    
    logWebhook("Factura pagada", [
        'invoice_id' => $invoice->id ?? 'UNKNOWN',
        'subscription_id' => $invoice->subscription ?? 'UNKNOWN',
        'amount_paid' => $invoice->amount_paid ?? 'UNKNOWN'
    ]);
    
    // Si hay una suscripción asociada, verificar si es premium y renovar si es necesario
    if (isset($invoice->subscription) && !empty($invoice->subscription)) {
        try {
            require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
            
            // Obtener clave de Stripe
            $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD';
            require_once __DIR__ . '/../config/stripe.php';
            $stripe_secret_key = get_stripe_live_secret_key();
            
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            
            $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
            
            $tipo_suscripcion = $subscription->metadata->tipo ?? null;
            $usuario_id = $subscription->metadata->usuario_id ?? null;
            
            // Manejar suscripción premium
            if ($tipo_suscripcion === 'suscripcion_premium') {
                if ($usuario_id) {
                    if (!function_exists('activarSuscripcionPremium')) {
                        include_once __DIR__ . '/../myphp/funciones_premium.php';
                    }
                    $current_period_end = $subscription->current_period_end ?? time() + (30 * 24 * 60 * 60);
                    $meses_restantes = max(1, ceil(($current_period_end - time()) / (30 * 24 * 60 * 60)));
                    $resultado = activarSuscripcionPremium($usuario_id, $subscription->id, $meses_restantes);
                    if ($resultado) {
                        logWebhook("Suscripción premium renovada exitosamente", [
                            'subscription_id' => $subscription->id ?? 'UNKNOWN',
                            'invoice_id' => $invoice->id ?? 'UNKNOWN',
                            'usuario_id' => $usuario_id
                        ]);
                    }
                }
            }
            // Manejar suscripción VIP
            elseif ($tipo_suscripcion === 'suscripcion_vip') {
                if ($usuario_id) {
                    if (!function_exists('activar_vip')) {
                        include_once __DIR__ . '/../myphp/funciones_usuario.php';
                    }
                    
                    // Renovar VIP: actualizar fecha de expiración y añadir saldo mensual
                    $current_period_end = $subscription->current_period_end ?? time() + (30 * 24 * 60 * 60);
                    $expires_at = new DateTime();
                    $expires_at->setTimestamp($current_period_end);
                    
                    $resultado = activar_vip($usuario_id, $subscription->id, $expires_at);
                    
                    if ($resultado) {
                        logWebhook("Suscripción VIP renovada exitosamente (incluye +10€ saldo)", [
                            'subscription_id' => $subscription->id ?? 'UNKNOWN',
                            'invoice_id' => $invoice->id ?? 'UNKNOWN',
                            'usuario_id' => $usuario_id,
                            'expires_at' => $expires_at->format('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            logWebhook("ERROR: Excepción al procesar invoice.paid", [
                'invoice_id' => $invoice->id ?? 'UNKNOWN',
                'error' => $e->getMessage()
            ], 'ERROR');
        }
    }
}
elseif ($event->type === 'invoice.payment_failed') {
    $invoice = $event->data->object;

    logWebhook("Pago de factura fallido", [
        'invoice_id' => $invoice->id ?? 'UNKNOWN',
        'subscription_id' => $invoice->subscription ?? 'UNKNOWN',
        'attempt_count' => $invoice->attempt_count ?? 0,
        'next_payment_attempt' => $invoice->next_payment_attempt ?? null,
    ]);

    if (isset($invoice->subscription) && !empty($invoice->subscription)) {
        try {
            require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
            require_once __DIR__ . '/../config/stripe.php';
            \Stripe\Stripe::setApiKey(get_stripe_live_secret_key());

            $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
            $tipo_suscripcion = $subscription->metadata->tipo ?? null;
            $usuario_id = $subscription->metadata->usuario_id ?? null;

            if ($tipo_suscripcion === 'suscripcion_vip' && $usuario_id) {
                $collection_usuarios = getCollectionUsuarios();
                $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

                if ($usuario) {
                    // Marcar fallo de pago en BD para tracking
                    $collection_usuarios->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                        [
                            '$set' => [
                                'vip_pago_fallido' => true,
                                'vip_pago_fallido_invoice' => $invoice->id,
                                'vip_pago_fallido_intentos' => $invoice->attempt_count ?? 0,
                                'vip_pago_fallido_proximo_intento' => isset($invoice->next_payment_attempt)
                                    ? new MongoDB\BSON\UTCDateTime($invoice->next_payment_attempt * 1000)
                                    : null,
                                'vip_pago_fallido_fecha' => new MongoDB\BSON\UTCDateTime(),
                            ],
                        ]
                    );

                    // Enviar email solo en primer fallo (intento 1) para evitar spam
                    if (($invoice->attempt_count ?? 1) <= 1) {
                        if (!function_exists('enviarEmailVIPPagoFallidoReintento')) {
                            include_once __DIR__ . '/../myphp/funciones_email.php';
                        }
                        if (function_exists('enviarEmailVIPPagoFallidoReintento')) {
                            $proximo_intento = isset($invoice->next_payment_attempt)
                                ? date('d/m/Y', $invoice->next_payment_attempt)
                                : null;
                            enviarEmailVIPPagoFallidoReintento($usuario, $proximo_intento);
                            logWebhook("Email aviso pago fallido enviado", ['usuario_id' => $usuario_id]);
                        } else {
                            logWebhook("ERROR: enviarEmailVIPPagoFallidoReintento no existe", ['usuario_id' => $usuario_id], 'ERROR');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            logWebhook("ERROR: Excepción al procesar invoice.payment_failed", [
                'invoice_id' => $invoice->id ?? 'UNKNOWN',
                'error' => $e->getMessage()
            ], 'ERROR');
        }
    }
}
else {
    // Tipo de evento no manejado
    logWebhook("INFO: Tipo de evento no manejado", [
        'event_type' => $event->type ?? 'UNKNOWN',
        'event_id' => $event->id ?? 'UNKNOWN'
    ]);
}

// Responder siempre con 200 para evitar reintentos de Stripe
logWebhook("Webhook procesado exitosamente", [
    'event_type' => $event->type ?? 'UNKNOWN',
    'event_id' => $event->id ?? 'UNKNOWN'
]);

http_response_code(200);
echo "Webhook processed successfully";
?>
