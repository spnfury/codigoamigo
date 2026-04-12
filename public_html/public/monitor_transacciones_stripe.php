<?php
/**
 * Monitor de Transacciones Stripe
 * 
 * Este script verifica periódicamente si hay transacciones faltantes
 * y envía alertas si encuentra discrepancias.
 * 
 * Ejecutar vía cron cada hora:
 * 0 * * * * /usr/bin/php /home/admin/web/codigoamigo.com/public_html/public/monitor_transacciones_stripe.php
 */

// Solo permitir ejecución desde CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Configuración
$dias_revisar = 7; // Revisar últimos 7 días
$umbral_alerta = 1; // Alertar si hay al menos 1 transacción faltante

// Claves de Stripe (via helper)
require_once __DIR__ . '/../config/stripe.php';
$stripe_live_key = get_stripe_live_secret_key();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando monitor de transacciones Stripe...\n";

// Función para obtener sesiones faltantes
function obtenerSesionesFaltantes($stripe_key, $dias) {
    $sesiones = [];
    
    try {
        $stripe = new \Stripe\StripeClient($stripe_key);
        $collection_transacciones = getCollectionTransacciones();
        
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
        
        // Calcular fecha límite
        $fecha_limite = time() - ($dias * 24 * 60 * 60);
        
        // Obtener sesiones de checkout
        $has_more = true;
        $starting_after = null;
        $total_obtenidas = 0;
        
        while ($has_more && $total_obtenidas < 500) {
            $params = ['limit' => 100];
            if ($starting_after) {
                $params['starting_after'] = $starting_after;
            }
            
            $sessions = $stripe->checkout->sessions->all($params);
            
            foreach ($sessions->data as $session) {
                if ($session->created < $fecha_limite) {
                    $has_more = false;
                    break;
                }
                
                if ($session->payment_status === 'paid' && 
                    isset($session->metadata) && 
                    isset($session->metadata->tipo) && 
                    $session->metadata->tipo === 'destacar_codigo' &&
                    !isset($session_ids_existentes[$session->id])) {
                    
                    // Extraer metadata
                    $metadata_array = (array)$session->metadata;
                    if (is_object($session->metadata)) {
                        $temp_meta = [];
                        foreach (['tipo', 'codigo_id', 'marca', 'usuario_id', 'tipo_destacado'] as $key) {
                            if (isset($session->metadata->$key)) {
                                $temp_meta[$key] = $session->metadata->$key;
                            }
                        }
                        if (!empty($temp_meta)) {
                            $metadata_array = $temp_meta;
                        }
                    }
                    
                    $sesiones[] = [
                        'session_id' => $session->id,
                        'amount_total' => $session->amount_total / 100,
                        'created' => $session->created,
                        'metadata' => $metadata_array
                    ];
                }
            }
            
            $has_more = $sessions->has_more;
            if ($has_more && count($sessions->data) > 0) {
                $starting_after = end($sessions->data)->id;
            }
            
            $total_obtenidas += count($sessions->data);
        }
        
    } catch (Exception $e) {
        error_log("Monitor Stripe: Error obteniendo sesiones: " . $e->getMessage());
        return [];
    }
    
    return $sesiones;
}

// Obtener sesiones faltantes
$sesiones_faltantes = obtenerSesionesFaltantes($stripe_live_key, $dias_revisar);

if (count($sesiones_faltantes) >= $umbral_alerta) {
    $mensaje = "ALERTA: Se encontraron " . count($sesiones_faltantes) . " transacciones faltantes en los últimos $dias_revisar días.\n\n";
    $mensaje .= "Detalles:\n";
    
    foreach ($sesiones_faltantes as $index => $sesion) {
        $num = $index + 1;
        $mensaje .= "$num. Session: " . substr($sesion['session_id'], 0, 30) . "...\n";
        $mensaje .= "   Fecha: " . date('d/m/Y H:i', $sesion['created']) . "\n";
        $mensaje .= "   Monto: " . number_format($sesion['amount_total'], 2) . " EUR\n";
        $mensaje .= "   Marca: " . ($sesion['metadata']['marca'] ?? 'N/A') . "\n";
        $mensaje .= "\n";
    }
    
    $mensaje .= "Para sincronizar, ejecuta:\n";
    $mensaje .= "php " . __DIR__ . "/sync_stripe_transactions_cli.php --dias=$dias_revisar --modo=live --confirmar\n";
    
    // Log del problema
    error_log("MONITOR STRIPE: " . $mensaje);
    
    // Intentar enviar email de alerta (si hay función de email configurada)
    if (function_exists('enviar_email_admin')) {
        try {
            enviar_email_admin(
                'Alerta: Transacciones Stripe Faltantes',
                $mensaje
            );
        } catch (Exception $e) {
            error_log("Monitor Stripe: Error enviando email: " . $e->getMessage());
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] ALERTA: " . count($sesiones_faltantes) . " transacciones faltantes detectadas\n";
    exit(1); // Exit code 1 para indicar problema
} else {
    echo "[" . date('Y-m-d H:i:s') . "] OK: No se encontraron transacciones faltantes\n";
    exit(0);
}

