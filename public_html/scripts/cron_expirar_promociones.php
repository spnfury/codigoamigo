<?php
/**
 * Script CRON para marcar promociones expiradas como inactivas
 * 
 * Ejecutar diariamente mediante crontab:
 * 0 0 * * * /usr/bin/php /home/admin/web/codigoamigo.com/public_html/scripts/cron_expirar_promociones.php
 */

// Configurar ruta base
$base_path = dirname(__DIR__);

// Incluir funciones necesarias
require_once $base_path . '/inc/includes.php';
require_once $base_path . '/myphp/funciones_premium.php';

// Log de inicio
$log_file = $base_path . '/logs/cron_promociones.log';
$timestamp = date('Y-m-d H:i:s');

function log_cron($message) {
    global $log_file, $timestamp;
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    echo $log_message;
}

log_cron("Iniciando validación de promociones expiradas...");

try {
    // Ejecutar validación de promociones expiradas
    $promociones_expiradas = validarPromocionesExpiradas();
    
    log_cron("Promociones marcadas como expiradas: $promociones_expiradas");
    log_cron("Proceso completado exitosamente");
    
    exit(0);
} catch (Throwable $e) {
    log_cron("ERROR: " . $e->getMessage());
    log_cron("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>

