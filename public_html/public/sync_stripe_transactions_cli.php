<?php
/**
 * Script CLI de Sincronización: Importar Transacciones Faltantes desde Stripe
 * 
 * Uso: php sync_stripe_transactions_cli.php [--dias=90] [--modo=both] [--confirmar]
 * 
 * IMPORTANTE: Usa --confirmar para realmente insertar las transacciones
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
$confirmar = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--dias=') === 0) {
        $dias = (int)substr($arg, 7);
    } elseif (strpos($arg, '--modo=') === 0) {
        $modo = substr($arg, 7);
    } elseif ($arg === '--confirmar') {
        $confirmar = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php sync_stripe_transactions_cli.php [--dias=90] [--modo=both] [--confirmar]\n";
        echo "\nOpciones:\n";
        echo "  --dias=N      Número de días a revisar (default: 90)\n";
        echo "  --modo=X      Modo Stripe: test, live, both (default: both)\n";
        echo "  --confirmar   Realmente insertar las transacciones (sin esto solo muestra)\n";
        echo "  --help        Mostrar esta ayuda\n";
        exit(0);
    }
}

// Claves de Stripe (via helper)
require_once __DIR__ . '/../config/stripe.php';
$stripe_test_key = get_stripe_test_secret_key();
$stripe_live_key = get_stripe_live_secret_key();

echo "========================================\n";
echo "Sincronización de Transacciones Stripe\n";
echo "========================================\n";
echo "Días a revisar: $dias\n";
echo "Modo: $modo\n";
echo "Confirmar: " . ($confirmar ? "SÍ (se insertarán)" : "NO (solo simulación)") . "\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

$errores = [];
$insertadas = 0;
$errores_insertar = 0;
$sesiones_faltantes = [];

// Función para obtener sesiones faltantes de Stripe
function obtenerSesionesFaltantes($stripe_key, $dias, $modo_nombre) {
    global $errores;
    $sesiones = [];
    
    try {
        $stripe = new \Stripe\StripeClient($stripe_key);
        $collection_transacciones = getCollectionTransacciones();
        
        echo "Buscando sesiones faltantes en modo $modo_nombre...\n";
        
        // Obtener todas las session_ids existentes en MongoDB
        $transacciones_existentes = $collection_transacciones->find([
            'stripe_session_id' => ['$exists' => true, '$ne' => null]
        ], [
            'projection' => ['stripe_session_id' => 1]
        ])->toArray();
        
        $session_ids_existentes = [];
        foreach ($transacciones_existentes as $trans) {
            if (isset($trans['stripe_session_id'])) {
                $session_ids_existentes[$trans['stripe_session_id']] = true;
            }
        }
        
        echo "  Sesiones existentes en MongoDB: " . count($session_ids_existentes) . "\n";
        
        // Calcular fecha límite
        $fecha_limite = time() - ($dias * 24 * 60 * 60);
        
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
                    
                    // Solo agregar si no existe en MongoDB
                    if (!isset($session_ids_existentes[$session->id])) {
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
                            'payment_status' => $session->payment_status,
                            'modo' => $modo_nombre
                        ];
                        $sesiones_encontradas++;
                    }
                }
            }
            
            $has_more = $sessions->has_more;
            if ($has_more && count($sessions->data) > 0) {
                $starting_after = end($sessions->data)->id;
            }
            
            $total_obtenidas += count($sessions->data);
            echo "  Revisadas: $total_obtenidas, Faltantes encontradas: $sesiones_encontradas\r";
        }
        
        echo "\n  ✓ Modo $modo_nombre: $sesiones_encontradas sesiones faltantes encontradas\n";
        
    } catch (Exception $e) {
        $error_msg = "Error obteniendo sesiones de Stripe ($modo_nombre): " . $e->getMessage();
        $errores[] = $error_msg;
        echo "\n  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    return $sesiones;
}

// Función para insertar transacción en MongoDB
function insertarTransaccion($sesion) {
    global $errores, $insertadas, $errores_insertar;
    
    try {
        $collection_transacciones = getCollectionTransacciones();
        
        // Verificar nuevamente que no existe (doble verificación)
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $sesion['session_id']]);
        if ($existe) {
            return ['success' => false, 'message' => 'Ya existe en MongoDB'];
        }
        
        // Validar que tenemos los metadata necesarios
        if (!isset($sesion['metadata']['usuario_id']) || 
            !isset($sesion['metadata']['codigo_id']) || 
            !isset($sesion['metadata']['tipo_destacado'])) {
            return ['success' => false, 'message' => 'Metadata incompleto'];
        }
        
        // Crear la transacción con el mismo formato que el webhook
        $transaccion = [
            'usuario_id' => $sesion['metadata']['usuario_id'],
            'tipo' => 'destacado',
            'subtipo' => $sesion['metadata']['tipo_destacado'],
            'cantidad' => $sesion['amount_total'],
            'descripcion' => "Destacado de código - Marca: " . ($sesion['metadata']['marca'] ?? 'N/A') . " - Tipo: " . $sesion['metadata']['tipo_destacado'],
            'fecha' => new MongoDB\BSON\UTCDateTime($sesion['created'] * 1000),
            'estado' => 'completada',
            'codigo_id' => $sesion['metadata']['codigo_id'],
            'marca' => $sesion['metadata']['marca'] ?? '',
            'tipo_destacado' => $sesion['metadata']['tipo_destacado'],
            'stripe_session_id' => $sesion['session_id'],
            'stripe_payment_intent' => $sesion['payment_intent'],
            'stripe_customer_email' => $sesion['customer_email'],
            'stripe_payment_status' => $sesion['payment_status'],
            'metodo_pago' => 'tarjeta',
            'sincronizado_manual' => true,
            'fecha_sincronizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $resultado = $collection_transacciones->insertOne($transaccion);
        
        if ($resultado->getInsertedCount() > 0) {
            $insertadas++;
            return ['success' => true, 'message' => 'Insertada correctamente', 'id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'message' => 'Error al insertar'];
        }
        
    } catch (Exception $e) {
        $errores_insertar++;
        $errores[] = "Error insertando transacción {$sesion['session_id']}: " . $e->getMessage();
        return ['success' => false, 'message' => 'Excepción: ' . $e->getMessage()];
    }
}

// Obtener sesiones faltantes
if ($modo === 'test' || $modo === 'both') {
    $sesiones_test = obtenerSesionesFaltantes($stripe_test_key, $dias, 'Test');
    $sesiones_faltantes = array_merge($sesiones_faltantes, $sesiones_test);
}

if ($modo === 'live' || $modo === 'both') {
    $sesiones_live = obtenerSesionesFaltantes($stripe_live_key, $dias, 'Live');
    $sesiones_faltantes = array_merge($sesiones_faltantes, $sesiones_live);
}

echo "\n";

if (count($sesiones_faltantes) > 0) {
    echo "========================================\n";
    echo "TRANSACCIONES FALTANTES ENCONTRADAS: " . count($sesiones_faltantes) . "\n";
    echo "========================================\n\n";
    
    if (!$confirmar) {
        echo "⚠️  MODO SIMULACIÓN - No se insertarán transacciones\n";
        echo "   Usa --confirmar para realmente insertarlas\n\n";
    } else {
        echo "🔄 Insertando transacciones...\n\n";
        
        foreach ($sesiones_faltantes as $index => $sesion) {
            $num = $index + 1;
            echo "[$num/" . count($sesiones_faltantes) . "] ";
            echo "Session: " . substr($sesion['session_id'], 0, 30) . "... ";
            
            $resultado = insertarTransaccion($sesion);
            
            if ($resultado['success']) {
                echo "✓ " . $resultado['message'] . "\n";
            } else {
                echo "✗ " . $resultado['message'] . "\n";
            }
        }
        
        echo "\n========================================\n";
        echo "RESUMEN DE SINCRONIZACIÓN\n";
        echo "========================================\n";
        echo "Total faltantes:  " . count($sesiones_faltantes) . "\n";
        echo "Insertadas:      " . $insertadas . "\n";
        echo "Errores:         " . $errores_insertar . "\n";
        echo "========================================\n\n";
    }
    
    // Mostrar detalles de las transacciones
    echo "\nDetalles de transacciones faltantes:\n";
    echo "----------------------------------------\n";
    foreach ($sesiones_faltantes as $index => $faltante) {
        $num = $index + 1;
        echo "$num. " . substr($faltante['session_id'], 0, 30) . "...\n";
        echo "   Fecha: " . date('d/m/Y H:i', $faltante['created']) . "\n";
        echo "   Monto: " . number_format($faltante['amount_total'], 2) . " " . strtoupper($faltante['currency']) . "\n";
        echo "   Marca: " . ($faltante['metadata']['marca'] ?? 'N/A') . "\n";
        echo "   Tipo: " . ($faltante['metadata']['tipo_destacado'] ?? 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "✅ Perfecto: No se encontraron transacciones faltantes.\n";
    echo "   Todo está sincronizado.\n\n";
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

echo "Sincronización completada.\n";

