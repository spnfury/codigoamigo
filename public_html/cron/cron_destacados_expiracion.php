<?php
/**
 * Cron: Gestión de expiración y auto-renovación de destacados
 * 
 * ⚠️ Ejecutar via JENKINS (no crontab). Frecuencia: diaria.
 * 
 * Funcionalidades:
 * 1. Aviso pre-expiración (2 días antes) → email al usuario
 * 2. Notificación de expiración → email al usuario
 * 3. Auto-renovación desde saldo (si activada)
 * 
 * Uso: php cron_destacados_expiracion.php [--dry-run]
 */

date_default_timezone_set('Europe/Madrid');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_destacados_email.php';

$dry_run = in_array('--dry-run', $argv ?? []);
$log_file = __DIR__ . '/cron_destacados.log';

function logMsg($msg, $log_file) {
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] $msg\n";
    file_put_contents($log_file, $entry, FILE_APPEND);
    echo $entry;
}

logMsg("=== Inicio cron destacados " . ($dry_run ? "(DRY RUN)" : "(PRODUCCIÓN)") . " ===", $log_file);

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();
$collection_transacciones = getCollectionTransacciones();

$now = new MongoDB\BSON\UTCDateTime();
$en_2_dias = new MongoDB\BSON\UTCDateTime((time() + 2 * 86400) * 1000);
$hace_24h = new MongoDB\BSON\UTCDateTime((time() - 86400) * 1000);

$stats = ['avisos_enviados' => 0, 'expirados_notificados' => 0, 'auto_renovados' => 0, 'sin_saldo' => 0, 'errores' => 0];

// ============================================================
// 1. AVISO PRE-EXPIRACIÓN (2 días antes)
// ============================================================
logMsg("--- Fase 1: Avisos pre-expiración ---", $log_file);

$filtro_aviso = [
    'destacado' => ['$ne' => 0],
    'estado' => 0,
    'fecha_fin_destacado' => [
        '$gt' => $now,
        '$lte' => $en_2_dias
    ],
    'aviso_expiracion_enviado' => ['$ne' => true]
];

$codigos_por_expirar = $collection_codigos->find($filtro_aviso);

foreach ($codigos_por_expirar as $codigo) {
    $codigo_id = (string)$codigo['_id'];
    $marca = $codigo['marca'] ?? 'N/A';
    
    try {
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo['id_usuario'])]);
        if (!$usuario) {
            logMsg("  SKIP $codigo_id: usuario no encontrado", $log_file);
            continue;
        }

        // No avisar si la renovación está garantizada: auto-renovación ON y saldo suficiente.
        // En ese caso la Fase 2 renovará solo; el aviso "perderás visibilidad" sería falso y molesto.
        $auto_renovar = isset($codigo['auto_renovar_destacado']) && $codigo['auto_renovar_destacado'] === true;
        $es_super = (($codigo['tipo_destacado'] ?? 'normal') === 'super');
        $precio_renovacion = round(($es_super ? DESTACADO_PRECIO_SUPER : DESTACADO_PRECIO_NORMAL) * 0.50, 2);
        $saldo_usuario = floatval($usuario['saldo'] ?? 0);
        if ($auto_renovar && $saldo_usuario >= $precio_renovacion) {
            logMsg("  SKIP $codigo_id: auto-renovación ON y saldo suficiente ({$saldo_usuario}€ >= {$precio_renovacion}€), no se avisa", $log_file);
            continue;
        }

        $ts_fin = $codigo['fecha_fin_destacado']->toDateTime()->getTimestamp();
        $dias_restantes = max(1, ceil(($ts_fin - time()) / 86400));
        
        logMsg("  AVISO $codigo_id | marca: $marca | usuario: " . ($usuario['username'] ?? 'N/A') . " | expira en {$dias_restantes}d", $log_file);
        
        if (!$dry_run) {
            $resultado = enviarEmailDestacadoExpiraPronto($usuario, (array)$codigo, $marca, $dias_restantes);
            
            if ($resultado && $resultado['success']) {
                $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => ['aviso_expiracion_enviado' => true]]
                );
                $stats['avisos_enviados']++;
            } else {
                logMsg("  ERROR email para $codigo_id: " . ($resultado['error'] ?? 'desconocido'), $log_file);
                $stats['errores']++;
            }
        } else {
            $stats['avisos_enviados']++;
        }
    } catch (Throwable $e) {
        logMsg("  ERROR $codigo_id: " . $e->getMessage(), $log_file);
        $stats['errores']++;
    }
}

// ============================================================
// 2. EXPIRADOS + AUTO-RENOVACIÓN
// ============================================================
logMsg("--- Fase 2: Expirados + Auto-renovación ---", $log_file);

$filtro_expirado = [
    'destacado' => ['$ne' => 0],
    'estado' => 0,
    'fecha_fin_destacado' => [
        '$lte' => $now
    ]
];

$codigos_expirados = $collection_codigos->find($filtro_expirado);

foreach ($codigos_expirados as $codigo) {
    $codigo_id = (string)$codigo['_id'];
    $marca = $codigo['marca'] ?? 'N/A';
    $auto_renovar = isset($codigo['auto_renovar_destacado']) && $codigo['auto_renovar_destacado'] === true;
    
    try {
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo['id_usuario'])]);
        if (!$usuario) {
            logMsg("  SKIP $codigo_id: usuario no encontrado", $log_file);
            continue;
        }
        
        $tipo = $codigo['tipo_destacado'] ?? 'normal';
        $es_super = ($tipo === 'super');
        $precio_original = $es_super ? DESTACADO_PRECIO_SUPER : DESTACADO_PRECIO_NORMAL;
        $precio = round($precio_original * 0.50, 2); // 50% de descuento por fidelidad en auto-renovación
        $duracion = $es_super ? DESTACADO_DURACION_SUPER : DESTACADO_DURACION_NORMAL;
        $saldo = floatval($usuario['saldo'] ?? 0);
        
        // ¿Auto-renovar?
        if ($auto_renovar && $saldo >= $precio) {
            logMsg("  AUTO-RENOVAR $codigo_id | marca: $marca | tipo: $tipo | saldo: {$saldo}€ → cobrar {$precio}€", $log_file);
            
            if (!$dry_run) {
                $nuevo_saldo = round($saldo - $precio, 2);
                
                // Renovar el código
                $nueva_fecha_fin = new MongoDB\BSON\UTCDateTime((time() + ($duracion * 86400)) * 1000);
                $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => [
                        'destacado' => time(),
                        'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
                        'fecha_fin_destacado' => $nueva_fecha_fin,
                        'prioridad_pago' => time(),
                        'aviso_expiracion_enviado' => false,
                        'aviso_expirado_enviado' => true // marcar para no re-procesar
                    ]]
                );
                
                // Cobrar saldo
                $collection_usuarios->updateOne(
                    ['_id' => $usuario['_id']],
                    ['$set' => ['saldo' => $nuevo_saldo]]
                );
                
                // Registrar transacción
                $collection_transacciones->insertOne([
                    'usuario_id' => (string)$usuario['_id'],
                    'tipo' => 'auto_renovacion_destacado',
                    'cantidad' => -$precio,
                    'descripcion' => 'Auto-renovación ' . ($es_super ? 'Super' : 'Normal') . ' en ' . $marca . ' (50% dto)',
                    'fecha' => new MongoDB\BSON\UTCDateTime(),
                    'estado' => 'completada',
                    'saldo_anterior' => $saldo,
                    'saldo_nuevo' => $nuevo_saldo,
                    'codigo_id' => $codigo_id,
                    'marca' => $marca,
                    'descuento_aplicado' => '50%',
                    'precio_original' => $precio_original,
                    'precio_cobrado' => $precio
                ]);
                
                // Email de confirmación
                enviarEmailDestacadoAutoRenovado($usuario, (array)$codigo, $marca, $nuevo_saldo, $duracion);
            }
            $stats['auto_renovados']++;
            
        } elseif ($auto_renovar && $saldo < $precio) {
            // Auto-renovar activado pero sin saldo
            logMsg("  SIN SALDO $codigo_id | marca: $marca | saldo: {$saldo}€ < {$precio}€", $log_file);
            
            if (!$dry_run) {
                // Desactivar auto-renovar y limpiar destacado
                $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => [
                        'auto_renovar_destacado' => false,
                        'aviso_expirado_enviado' => true,
                        'destacado' => 0
                    ]]
                );
                
                // Email de saldo insuficiente
                enviarEmailDestacadoSaldoInsuficiente($usuario, (array)$codigo, $marca, $saldo);
            }
            $stats['sin_saldo']++;
            
        } else {
            // No tiene auto-renovar → solo notificar expiración
            logMsg("  EXPIRADO $codigo_id | marca: $marca | usuario: " . ($usuario['username'] ?? 'N/A'), $log_file);
            
            if (!$dry_run) {
                $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => [
                        'aviso_expirado_enviado' => true,
                        'destacado' => 0
                    ]]
                );
                
                enviarEmailDestacadoExpirado($usuario, (array)$codigo, $marca);
            }
            $stats['expirados_notificados']++;
        }
    } catch (Throwable $e) {
        logMsg("  ERROR $codigo_id: " . $e->getMessage(), $log_file);
        $stats['errores']++;
    }
}

// ============================================================
// RESUMEN
// ============================================================
logMsg("=== Resumen ===", $log_file);
logMsg("  Avisos pre-expiración enviados: " . $stats['avisos_enviados'], $log_file);
logMsg("  Expirados notificados: " . $stats['expirados_notificados'], $log_file);
logMsg("  Auto-renovados: " . $stats['auto_renovados'], $log_file);
logMsg("  Sin saldo (auto-renew desactivado): " . $stats['sin_saldo'], $log_file);
logMsg("  Errores: " . $stats['errores'], $log_file);
logMsg("=== Fin cron destacados ===", $log_file);
