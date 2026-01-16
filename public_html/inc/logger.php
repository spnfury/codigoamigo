<?php

/**
 * Sistema de Logging Organizado para CodigoAmigo
 * Separa logs informativos de errores reales
 */

class Logger {
    
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    private static $logDir = null;
    private static $enabled = true;
    
    /**
     * Inicializar el sistema de logging
     */
    public static function init($logDir = null) {
        if ($logDir === null) {
            self::$logDir = __DIR__ . '/../logs/';
        } else {
            self::$logDir = rtrim($logDir, '/') . '/';
        }
        
        // Crear directorio de logs si no existe
        if (!is_dir(self::$logDir)) {
            if (!@mkdir(self::$logDir, 0777, true)) {
                // Si no se puede crear el directorio, deshabilitar logging
                self::$enabled = false;
                error_log("Logger: No se pudo crear el directorio de logs: " . self::$logDir);
                return;
            }
        }
        
        // Intentar corregir permisos si es necesario
        if (!is_writable(self::$logDir)) {
            // Intentar cambiar permisos
            @chmod(self::$logDir, 0777);
            
            // Verificar nuevamente
            if (!is_writable(self::$logDir)) {
                self::$enabled = false;
                error_log("Logger: El directorio de logs no tiene permisos de escritura: " . self::$logDir);
                return;
            }
        }
        
        // Crear subdirectorios
        $subdirs = ['debug', 'info', 'warning', 'error', 'critical', 'application'];
        foreach ($subdirs as $subdir) {
            $path = self::$logDir . $subdir;
            if (!is_dir($path)) {
                if (!@mkdir($path, 0777, true)) {
                    // Log el error pero continúa con otros directorios
                    error_log("Logger: No se pudo crear el subdirectorio: " . $path);
                }
            } else {
                // Asegurar permisos de escritura en subdirectorios existentes
                @chmod($path, 0777);
            }
        }
    }
    
    /**
     * Log de debug - solo para desarrollo
     */
    public static function debug($message, $context = []) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::writeLog(self::LEVEL_DEBUG, $message, $context);
        }
    }
    
    /**
     * Log informativo - flujo normal de la aplicación
     */
    public static function info($message, $context = []) {
        self::writeLog(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log de advertencia - situaciones que requieren atención pero no son errores
     */
    public static function warning($message, $context = []) {
        self::writeLog(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Enviar notificación a Telegram
     */
    private static function sendTelegramNotification($message, $level = 'ERROR') {
        if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')) {
            return;
        }

        // Evitar bucles infinitos si hay error al enviar a Telegram
        static $recursion_guard = false;
        if ($recursion_guard) return;
        $recursion_guard = true;

        $icon = $level === 'CRITICAL' ? '🚨' : '⚠️';
        $telegramMessage = "{$icon} *{$level} en CodigoAmigo*\n\n";
        $telegramMessage .= strip_tags($message);
        $telegramMessage .= "\n\n⏰ " . date('Y-m-d H:i:s');

        try {
            $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
            $data = [
                'chat_id' => TELEGRAM_ADMIN_CHAT_ID,
                'text' => $telegramMessage,
                'parse_mode' => 'Markdown'
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 5
                ]
            ];
            
            $context  = stream_context_create($options);
            @file_get_contents($url, false, $context);
        } catch (Exception $e) {
            // Silenciosamente fallar si no se puede enviar
            error_log("No se pudo enviar notificación a Telegram: " . $e->getMessage());
        }

        $recursion_guard = false;
    }
    
    /**
     * Log de error - errores que no detienen la aplicación
     */
    public static function error($message, $context = []) {
        self::writeLog(self::LEVEL_ERROR, $message, $context);
        
        // Enviar a Telegram si es un error 404 importante o 500
        $is404 = strpos($message, '404') !== false;
        $is500 = strpos($message, '500') !== false;
        
        if ($is500 || ($is404 && !empty($context))) {
             self::sendTelegramNotification($message, 'ERROR');
        }
    }
    
    /**
     * Log crítico - errores que pueden detener la aplicación
     */
    public static function critical($message, $context = []) {
        self::writeLog(self::LEVEL_CRITICAL, $message, $context);
        // Los errores críticos también van al log de errores del sistema
        error_log("CRITICAL: " . $message . " | Context: " . json_encode($context));
        
        // Siempre notificar errores críticos
        self::sendTelegramNotification($message, 'CRITICAL');
    }
    
    /**
     * Log de aplicación - eventos importantes del negocio
     */
    public static function application($message, $context = []) {
        self::writeLog('application', $message, $context);
    }
    
    /**
     * Escribir log al archivo correspondiente
     */
    private static function writeLog($level, $message, $context = []) {
        if (!self::$enabled || self::$logDir === null) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Archivo principal del nivel
        $logFile = self::$logDir . $level . '/' . date('Y-m-d') . '.log';
        if (!@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX)) {
            // Si no se puede escribir al archivo específico, intentar escribir al log de errores de PHP
            error_log("Logger: No se pudo escribir al archivo de log: " . $logFile . " | Mensaje: " . $message);
        }
        
        // También escribir en archivo combinado
        $combinedFile = self::$logDir . 'application/combined-' . date('Y-m-d') . '.log';
        if (!@file_put_contents($combinedFile, $logEntry, FILE_APPEND | LOCK_EX)) {
            // Si no se puede escribir al archivo combinado, solo log el error
            error_log("Logger: No se pudo escribir al archivo combinado: " . $combinedFile);
        }
        
        // Para errores críticos, también en el log de errores de PHP
        if ($level === self::LEVEL_ERROR || $level === self::LEVEL_CRITICAL) {
            error_log($logEntry);
        }
    }
    
    /**
     * Habilitar/deshabilitar logging
     */
    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }
    
    /**
     * Obtener logs de un nivel específico
     */
    public static function getLogs($level = null, $date = null, $limit = 100) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $logs = [];
        
        if ($level === null) {
            // Obtener de todos los niveles
            $levels = [self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL, 'application'];
        } else {
            $levels = [$level];
        }
        
        foreach ($levels as $logLevel) {
            $logFile = self::$logDir . $logLevel . '/' . $date . '.log';
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $lines = explode(PHP_EOL, trim($content));
                $lines = array_reverse($lines); // Más recientes primero
                
                foreach ($lines as $line) {
                    if (!empty($line)) {
                        $logs[] = [
                            'level' => $logLevel,
                            'message' => $line,
                            'timestamp' => self::extractTimestamp($line)
                        ];
                    }
                }
            }
        }
        
        // Ordenar por timestamp y limitar
        usort($logs, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Extraer timestamp de una línea de log
     */
    private static function extractTimestamp($line) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return strtotime($matches[1]);
        }
        return time();
    }
    
    /**
     * Limpiar logs antiguos (más de X días)
     */
    public static function cleanOldLogs($days = 30) {
        if (self::$logDir === null) {
            return;
        }
        
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $levels = ['debug', 'info', 'warning', 'error', 'critical', 'application'];
        
        foreach ($levels as $level) {
            $levelDir = self::$logDir . $level . '/';
            if (is_dir($levelDir)) {
                $files = glob($levelDir . '*.log');
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoffTime) {
                        unlink($file);
                    }
                }
            }
        }
    }
}

// Inicializar el logger automáticamente
Logger::init();

// Funciones de conveniencia globales
function log_debug($message, $context = []) {
    Logger::debug($message, $context);
}

function log_info($message, $context = []) {
    Logger::info($message, $context);
}

function log_warning($message, $context = []) {
    Logger::warning($message, $context);
}

function log_error($message, $context = []) {
    Logger::error($message, $context);
}

function log_critical($message, $context = []) {
    Logger::critical($message, $context);
}

function log_application($message, $context = []) {
    Logger::application($message, $context);
}

?>
