<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * Procesador automático de newsletters
 * 
 * Este script se ejecuta cada hora vía cron para procesar la cola de newsletters
 * Respeta el límite de 300 emails/día de Brevo
 * 
 * Configurar en crontab:
 * 0 * * * * /usr/bin/php /home/admin/web/codigoamigo.com/public_html/cron/newsletter_processor.php
 */

// Configurar zona horaria
date_default_timezone_set('Europe/Madrid');

// Incluir archivos necesarios
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_newsletter.php';
require_once __DIR__ . '/../config/email_config.php';

// Log de inicio
$log_file = __DIR__ . '/newsletter_processor.log';
$timestamp = date('Y-m-d H:i:s');

function escribirLog($mensaje, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $mensaje\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry;
}

escribirLog("=== Inicio procesamiento de newsletters ===", $log_file);

try {
    // Verificar límites de Brevo
    $emails_enviados_hoy = obtenerEmailsEnviadosHoy();
    $limite_diario = 300;
    $disponibles = max(0, $limite_diario - $emails_enviados_hoy);
    
    escribirLog("Emails enviados hoy: $emails_enviados_hoy / $limite_diario", $log_file);
    escribirLog("Emails disponibles: $disponibles", $log_file);
    
    if ($disponibles <= 0) {
        escribirLog("Límite diario alcanzado. No se procesarán más emails hoy.", $log_file);
        escribirLog("=== Fin procesamiento (límite alcanzado) ===", $log_file);
        exit(0);
    }
    
    // Procesar cola de newsletters
    $resultado = procesarColaNewsletter($limite_diario);
    
    escribirLog("Procesados: " . $resultado['procesados'], $log_file);
    escribirLog("Enviados exitosamente: " . $resultado['enviados'], $log_file);
    escribirLog("Errores: " . $resultado['errores'], $log_file);
    
    if (!empty($resultado['newsletters_completadas'])) {
        escribirLog("Newsletters completadas: " . implode(', ', $resultado['newsletters_completadas']), $log_file);
    }
    
    if (isset($resultado['error'])) {
        escribirLog("Error en procesamiento: " . $resultado['error'], $log_file);
    }
    
    escribirLog("=== Fin procesamiento exitoso ===", $log_file);
    
} catch (Throwable $e) {
    $error_msg = "Error crítico en procesamiento: " . $e->getMessage() . " en línea " . $e->getLine();
    escribirLog($error_msg, $log_file);
    log_error($error_msg);
    exit(1);
}

exit(0);
?>



