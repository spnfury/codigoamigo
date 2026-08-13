<?php
/**
 * Cron: Desactivación automática de códigos MUY antiguos (>5 años)
 * 
 * ⚠️ DESACTIVADO POR DEFECTO tras incidente del 18-20 marzo 2026 donde se
 *    desactivaron masivamente 18,510 códigos con umbral de 1 año.
 * 
 * Para ejecutar se requiere --force además de --dry-run o sin flag:
 *   php cron_codigos_antiguos.php --dry-run --force
 *   php cron_codigos_antiguos.php --force
 * 
 * Protecciones añadidas:
 * - Umbral aumentado a 5 años (antes: 1 año)
 * - Safety cap: máximo 100 códigos por ejecución
 * - Requiere --force para ejecutar en producción
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_codigos_antiguos_email.php';

$dry_run = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);
$log_file = __DIR__ . '/cron_codigos_antiguos.log';

// SAFETY: requiere --force para ejecutar
if (!$force) {
    echo "[" . date('Y-m-d H:i:s') . "] ABORTADO: Este cron está desactivado. Usa --force para ejecutar." . PHP_EOL;
    echo "  Motivo: Incidente 18-20 marzo 2026 - desactivación masiva de 18,510 códigos." . PHP_EOL;
    exit(0);
}

// SAFETY: Máximo de códigos a desactivar por ejecución
$SAFETY_CAP = 100;

function logMsg($msg, $log_file) {
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] $msg\n";
    file_put_contents($log_file, $entry, FILE_APPEND);
    echo $entry;
}

logMsg("=== Inicio cron códigos antiguos " . ($dry_run ? "(DRY RUN)" : "(PRODUCCIÓN)") . " ===", $log_file);

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();

// Fecha límite: hace 5 AÑOS (antes: 1 año - demasiado agresivo para códigos de referido)
$umbral_anos = 5;
$fecha_limite = new DateTime("-{$umbral_anos} years");
$fecha_limite_str = $fecha_limite->format('Y-m-d H:i:s');
$hace_1_ano_ts = $fecha_limite->getTimestamp();

logMsg("Fecha límite: $fecha_limite_str (códigos anteriores a esta fecha serán desactivados)", $log_file);
logMsg("Safety cap: máximo $SAFETY_CAP códigos por ejecución", $log_file);

$stats = ['desactivados' => 0, 'emails_enviados' => 0, 'emails_fallidos' => 0, 'sin_usuario' => 0, 'errores' => 0];

// Buscar todos los códigos activos
$codigos_activos = $collection_codigos->find([
    'estado' => 0,
    'aviso_antiguedad_enviado' => ['$ne' => true] // No reprocesar los ya notificados
]);

$procesados = 0;

foreach ($codigos_activos as $codigo) {
    $codigo_id = (string)$codigo['_id'];
    $marca = $codigo['marca'] ?? 'N/A';
    $fecha_pub = $codigo['fecha_publicacion'] ?? null;
    
    if (empty($fecha_pub)) {
        // Sin fecha de publicación, usar el timestamp del ObjectId como fallback
        try {
            $oid_timestamp = $codigo['_id']->getTimestamp();
            if ($oid_timestamp >= $hace_1_ano_ts) {
                continue; // No es antiguo
            }
            $fecha_display = date('Y-m-d H:i:s', $oid_timestamp) . ' (desde ObjectId)';
        } catch (Exception $e) {
            continue; // No se puede determinar la fecha
        }
    } else {
        // Parseamos la fecha (soportamos ambos formatos)
        $timestamp = null;
        
        if (is_string($fecha_pub)) {
            $timestamp = strtotime($fecha_pub);
        } elseif ($fecha_pub instanceof MongoDB\BSON\UTCDateTime) {
            $timestamp = $fecha_pub->toDateTime()->getTimestamp();
        }
        
        if ($timestamp === null || $timestamp === false) {
            continue; // No se puede parsear la fecha
        }
        
        // Verificar si es mayor a 1 año
        if ($timestamp >= $hace_1_ano_ts) {
            continue; // No es antiguo, saltar
        }
        
        $fecha_display = date('Y-m-d H:i:s', $timestamp);
    }
    
    $procesados++;
    
    // SAFETY CAP: abortar si se detectan demasiados códigos para desactivar
    if ($procesados > $SAFETY_CAP) {
        logMsg("⚠️ SAFETY CAP alcanzado ($SAFETY_CAP códigos). Abortando para evitar desactivación masiva.", $log_file);
        logMsg("   Si realmente quieres desactivar más, aumenta \$SAFETY_CAP en el script.", $log_file);
        break;
    }
    
    logMsg("  ANTIGUO $codigo_id | marca: $marca | fecha: $fecha_display", $log_file);
    
    try {
        // Buscar usuario dueño del código
        $usuario_id = $codigo['id_usuario'] ?? null;
        if (!$usuario_id) {
            logMsg("    SKIP: sin id_usuario", $log_file);
            $stats['sin_usuario']++;
            continue;
        }
        
        // Convertir a ObjectId si es string
        if (is_string($usuario_id)) {
            $usuario_id = new MongoDB\BSON\ObjectId($usuario_id);
        }
        
        $usuario = $collection_usuarios->findOne(['_id' => $usuario_id]);
        if (!$usuario) {
            logMsg("    SKIP: usuario no encontrado ($usuario_id)", $log_file);
            $stats['sin_usuario']++;
            
            // Aún así desactivamos el código sin usuario
            if (!$dry_run) {
                $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => [
                        'estado' => -3,
                        'fecha_desactivacion_antiguedad' => new MongoDB\BSON\UTCDateTime(),
                        'aviso_antiguedad_enviado' => true
                    ]]
                );
                $stats['desactivados']++;
            }
            continue;
        }
        
        // Desactivar el código
        if (!$dry_run) {
            $collection_codigos->updateOne(
                ['_id' => $codigo['_id']],
                ['$set' => [
                    'estado' => -3,
                    'estado_anterior_antiguedad' => (int)($codigo['estado'] ?? 0),
                    'fecha_desactivacion_antiguedad' => new MongoDB\BSON\UTCDateTime(),
                    'aviso_antiguedad_enviado' => true
                ]]
            );
            $stats['desactivados']++;
            
            // Enviar email al usuario
            $resultado = enviarEmailCodigoDesactivadoPorAntiguedad(
                (array)$usuario,
                (array)$codigo,
                $marca
            );
            
            if ($resultado && $resultado['success']) {
                $stats['emails_enviados']++;
                logMsg("    ✅ Email enviado a " . ($usuario['mail'] ?? 'N/A'), $log_file);
            } else {
                $stats['emails_fallidos']++;
                logMsg("    ❌ Error email: " . ($resultado['error'] ?? 'desconocido'), $log_file);
            }
        } else {
            $stats['desactivados']++;
            logMsg("    [DRY-RUN] Se desactivaría y enviaría email a " . ($usuario['mail'] ?? 'N/A'), $log_file);
        }
        
    } catch (Throwable $e) {
        logMsg("    ERROR $codigo_id: " . $e->getMessage(), $log_file);
        $stats['errores']++;
    }
}

// ============================================================
// RESUMEN
// ============================================================
logMsg("=== Resumen ===", $log_file);
logMsg("  Códigos revisados (antiguos detectados): $procesados", $log_file);
logMsg("  Desactivados: " . $stats['desactivados'], $log_file);
logMsg("  Emails enviados: " . $stats['emails_enviados'], $log_file);
logMsg("  Emails fallidos: " . $stats['emails_fallidos'], $log_file);
logMsg("  Sin usuario: " . $stats['sin_usuario'], $log_file);
logMsg("  Errores: " . $stats['errores'], $log_file);
logMsg("=== Fin cron códigos antiguos ===", $log_file);
