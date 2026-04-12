<?php
/**
 * Script CLI de Diagnóstico: Comparar Transacciones Stripe vs MongoDB
 * 
 * Uso: php diagnostico_transacciones_stripe_cli.php [--dias=90] [--modo=both]
 * 
 * Modos: test, live, both
 */

// Solo permitir ejecución desde CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Parsear argumentos de línea de comandos
$dias = 90;
$modo = 'both';

foreach ($argv as $arg) {
    if (strpos($arg, '--dias=') === 0) {
        $dias = (int)substr($arg, 7);
    } elseif (strpos($arg, '--modo=') === 0) {
        $modo = substr($arg, 7);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php diagnostico_transacciones_stripe_cli.php [--dias=90] [--modo=both]\n";
        echo "\nOpciones:\n";
        echo "  --dias=N    Número de días a revisar (default: 90)\n";
        echo "  --modo=X    Modo Stripe: test, live, both (default: both)\n";
        echo "  --help      Mostrar esta ayuda\n";
        exit(0);
    }
}

// Claves de Stripe (via helper)
require_once __DIR__ . '/../config/stripe.php';
$stripe_test_key = get_stripe_test_secret_key();
$stripe_live_key = get_stripe_live_secret_key();

echo "========================================\n";
echo "Diagnóstico de Transacciones Stripe\n";
echo "========================================\n";
echo "Días a revisar: $dias\n";
echo "Modo: $modo\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

$errores = [];
$sesiones_stripe = [];

// Función para obtener sesiones de Stripe
function obtenerSesionesStripe($stripe_key, $dias, $modo_nombre) {
    global $errores;
    $sesiones = [];
    
    try {
        $stripe = new \Stripe\StripeClient($stripe_key);
        
        // Calcular fecha límite
        $fecha_limite = time() - ($dias * 24 * 60 * 60);
        
        echo "Buscando sesiones en modo $modo_nombre...\n";
        
        // Obtener todas las sesiones de checkout
        $has_more = true;
        $starting_after = null;
        $total_obtenidas = 0;
        $sesiones_encontradas = 0;
        
        while ($has_more && $total_obtenidas < 1000) { // Límite de seguridad
            $params = [
                'limit' => 100,
            ];
            
            if ($starting_after) {
                $params['starting_after'] = $starting_after;
            }
            
            $sessions = $stripe->checkout->sessions->all($params);
            
            foreach ($sessions->data as $session) {
                // Filtrar por fecha
                if ($session->created < $fecha_limite) {
                    $has_more = false;
                    break;
                }
                
                // Solo sesiones completadas con metadata tipo destacar_codigo
                if ($session->payment_status === 'paid' && 
                    isset($session->metadata) && 
                    isset($session->metadata->tipo) && 
                    $session->metadata->tipo === 'destacar_codigo') {
                    
                    // Extraer metadata correctamente - igual que en webhook_stripe.php
                    // Convertir metadata a array para facilitar el acceso
                    $metadata_array = (array)$session->metadata;
                    
                    // Si es un objeto Stripe, los valores reales pueden estar en _values
                    // Intentar acceder directamente a las propiedades públicas primero
                    if (is_object($session->metadata)) {
                        // Intentar usar toArray() si existe
                        if (method_exists($session->metadata, 'toArray')) {
                            $metadata_array = $session->metadata->toArray();
                        } else {
                            // Acceder directamente a las propiedades como en el webhook
                            $temp_meta = [];
                            foreach (['tipo', 'codigo_id', 'marca', 'usuario_id', 'tipo_destacado', 'username', 'descripcion'] as $key) {
                                if (isset($session->metadata->$key)) {
                                    $temp_meta[$key] = $session->metadata->$key;
                                }
                            }
                            if (!empty($temp_meta)) {
                                $metadata_array = $temp_meta;
                            } else {
                                // Último recurso: usar json_encode/decode
                                $metadata_json = json_encode($session->metadata);
                                $metadata_decoded = json_decode($metadata_json, true);
                                if (isset($metadata_decoded['_values']) && is_array($metadata_decoded['_values'])) {
                                    $metadata_array = $metadata_decoded['_values'];
                                } elseif (isset($metadata_decoded['_originalValues']) && is_array($metadata_decoded['_originalValues'])) {
                                    $metadata_array = $metadata_decoded['_originalValues'];
                                }
                            }
                        }
                    }
                    
                    $sesiones[] = [
                        'session_id' => $session->id,
                        'payment_intent' => $session->payment_intent,
                        'amount_total' => $session->amount_total / 100,
                        'currency' => $session->currency,
                        'customer_email' => $session->customer_details->email ?? null,
                        'created' => $session->created,
                        'metadata' => $metadata_array,
                        'modo' => $modo_nombre
                    ];
                    $sesiones_encontradas++;
                }
            }
            
            $has_more = $sessions->has_more;
            if ($has_more && count($sessions->data) > 0) {
                $starting_after = end($sessions->data)->id;
            }
            
            $total_obtenidas += count($sessions->data);
            echo "  Revisadas: $total_obtenidas, Encontradas: $sesiones_encontradas\r";
        }
        
        echo "\n  ✓ Modo $modo_nombre: $sesiones_encontradas sesiones encontradas\n";
        
    } catch (Exception $e) {
        $error_msg = "Error obteniendo sesiones de Stripe ($modo_nombre): " . $e->getMessage();
        $errores[] = $error_msg;
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    return $sesiones;
}

// Obtener sesiones de Stripe
if ($modo === 'test' || $modo === 'both') {
    $sesiones_test = obtenerSesionesStripe($stripe_test_key, $dias, 'Test');
    $sesiones_stripe = array_merge($sesiones_stripe, $sesiones_test);
}

if ($modo === 'live' || $modo === 'both') {
    $sesiones_live = obtenerSesionesStripe($stripe_live_key, $dias, 'Live');
    $sesiones_stripe = array_merge($sesiones_stripe, $sesiones_live);
}

echo "\n";

// Obtener transacciones de MongoDB
echo "Consultando MongoDB...\n";
$collection_transacciones = getCollectionTransacciones();
$transacciones_mongo = $collection_transacciones->find([
    'stripe_session_id' => ['$exists' => true, '$ne' => null],
    'tipo' => 'destacado'
])->toArray();

// Crear mapa de session_ids en MongoDB
$mongo_session_ids = [];
foreach ($transacciones_mongo as $trans) {
    if (isset($trans['stripe_session_id'])) {
        $mongo_session_ids[$trans['stripe_session_id']] = $trans;
    }
}

echo "  ✓ Transacciones en MongoDB: " . count($transacciones_mongo) . "\n\n";

// Comparar y encontrar faltantes
$faltantes = [];
$existentes = [];

foreach ($sesiones_stripe as $sesion) {
    $session_id = $sesion['session_id'];
    if (isset($mongo_session_ids[$session_id])) {
        $existentes[] = $sesion;
    } else {
        $faltantes[] = $sesion;
    }
}

// Mostrar estadísticas
echo "========================================\n";
echo "RESUMEN\n";
echo "========================================\n";
echo "Sesiones en Stripe:        " . count($sesiones_stripe) . "\n";
echo "Transacciones en MongoDB: " . count($transacciones_mongo) . "\n";
echo "Coincidencias:             " . count($existentes) . "\n";
echo "FALTANTES:                 " . count($faltantes) . "\n";
echo "========================================\n\n";

if (count($faltantes) > 0) {
    echo "⚠️  TRANSACCIONES FALTANTES EN MONGODB:\n";
    echo "========================================\n\n";
    
    foreach ($faltantes as $index => $faltante) {
        $num = $index + 1;
        echo "$num. Session ID: " . $faltante['session_id'] . "\n";
        echo "   Fecha: " . date('d/m/Y H:i', $faltante['created']) . "\n";
        echo "   Monto: " . number_format($faltante['amount_total'], 2) . " " . strtoupper($faltante['currency']) . "\n";
        echo "   Email: " . ($faltante['customer_email'] ?? 'N/A') . "\n";
        // Mostrar metadata de forma más legible
        if (!empty($faltante['metadata'])) {
            echo "   Metadata:\n";
            foreach ($faltante['metadata'] as $key => $value) {
                if (strpos($key, "\0") === false) { // Saltar propiedades privadas
                    echo "     - $key: " . (is_string($value) ? substr($value, 0, 50) : json_encode($value)) . "\n";
                }
            }
        }
        echo "   Usuario ID: " . ($faltante['metadata']['usuario_id'] ?? 'N/A') . "\n";
        echo "   Código ID: " . ($faltante['metadata']['codigo_id'] ?? 'N/A') . "\n";
        echo "   Marca: " . ($faltante['metadata']['marca'] ?? 'N/A') . "\n";
        echo "   Tipo: " . ($faltante['metadata']['tipo_destacado'] ?? 'N/A') . "\n";
        echo "   Modo: " . $faltante['modo'] . "\n";
        echo "\n";
    }
    
    echo "\n💡 Para sincronizar estas transacciones, ejecuta:\n";
    echo "   php sync_stripe_transactions_cli.php --dias=$dias --modo=$modo --confirmar\n\n";
} else {
    echo "✅ Perfecto: Todas las transacciones de Stripe están registradas en MongoDB.\n\n";
}

if (!empty($errores)) {
    echo "========================================\n";
    echo "ERRORES\n";
    echo "========================================\n";
    foreach ($errores as $error) {
        echo "✗ $error\n";
    }
    echo "\n";
}

echo "Diagnóstico completado.\n";

