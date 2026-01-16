<?php
// Limpiar cualquier output previo y configurar
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Configurar para evitar warnings y errores
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    // Limpiar output buffer
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Capturar errores y warnings para evitar que se muestren en la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

// Función para leer logs directamente del sistema usando comandos
function readLogRealtime($logPath, $lines = 100) {
    // Método 1: Usar shell_exec si está disponible
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $command = "tail -n $lines " . escapeshellarg($logPath) . " 2>/dev/null";
        $output = shell_exec($command);
        if ($output) {
            return array_filter(explode("\n", $output));
        }
    }
    
    // Método 2: Usar popen si está disponible
    if (function_exists('popen')) {
        $handle = popen("tail -n $lines " . escapeshellarg($logPath) . " 2>/dev/null", 'r');
        if ($handle) {
            $lines_array = [];
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) {
                    $lines_array[] = trim($line);
                }
            }
            pclose($handle);
            $result = array_filter($lines_array);
            if (!empty($result)) {
                return $result;
            }
        }
    }
    
    // Método 3: Usar exec si está disponible
    if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
        $output = [];
        $return_code = 0;
        exec("tail -n $lines " . escapeshellarg($logPath) . " 2>/dev/null", $output, $return_code);
        if ($return_code === 0 && !empty($output)) {
            return array_filter($output);
        }
    }
    
    // Método 4: Intentar con file_get_contents si el archivo es accesible y no muy grande
    if (file_exists($logPath) && is_readable($logPath)) {
        $fileSize = filesize($logPath);
        // Solo leer archivos menores a 10MB para evitar problemas de memoria
        if ($fileSize > 0 && $fileSize < 10 * 1024 * 1024) {
            $content = file_get_contents($logPath);
            if ($content !== false) {
                $allLines = explode("\n", $content);
                $allLines = array_filter($allLines);
                return array_slice($allLines, -$lines);
            }
        }
    }
    
    return [];
}

// Función para parsear logs según el tipo
function parseLogLine($line, $logType, $source) {
    if (empty(trim($line))) {
        return null;
    }
    
    $timestamp = time();
    $level = 'info';
    $message = $line;
    
    switch ($logType) {
        case 'apache_error':
            // Formato: [Wed Oct 09 08:22:11.123456 2024] [error] [client 192.168.1.1:12345] message
            if (preg_match('/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                $level = strtolower($matches[2]);
                $message = $matches[4];
            }
            break;
            
        case 'apache_access':
            // Formato: 192.168.1.1 - - [09/Oct/2024:08:22:11 +0000] "GET /page HTTP/1.1" 200 1234
            if (preg_match('/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"([^"]+)"\s+(\d+)\s+(\S+)/', $line, $matches)) {
                $timestamp = strtotime(str_replace(['/', ':'], ['-', ' '], $matches[2]));
                $level = ($matches[4] >= 400) ? 'error' : (($matches[4] >= 300) ? 'warning' : 'info');
                $message = $matches[3];
                $ip = $matches[1];
                $status_code = $matches[4];
            }
            break;
            
        case 'php_error':
            // Formato: [09-Oct-2024 08:22:11 UTC] PHP Warning: message in file on line 123
            if (preg_match('/^\[([^\]]+)\]\s+PHP\s+(\w+):\s+(.+?)\s+in\s+(.+?)\s+on\s+line\s+(\d+)$/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                $level = strtolower($matches[2]);
                $message = $matches[3];
                $file = $matches[4];
                $line_num = $matches[5];
            }
            break;
            
        case 'nginx_error':
            // Formato: 2024/10/09 08:22:11 [error] 123#0: *1234 message
            if (preg_match('/^(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[(\w+)\]\s+(.+)$/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                $level = strtolower($matches[2]);
                $message = $matches[3];
            }
            break;
            
        case 'mysql_error':
            // Formato: 2024-10-09T08:22:11.123456Z 0 [Note] [MY-010116] message
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z)\s+\d+\s+\[(\w+)\]\s+\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                $level = strtolower($matches[2]);
                $message = $matches[4];
                $code = $matches[3];
            }
            break;
    }
    
    $logEntry = [
        'timestamp' => $timestamp,
        'level' => $level,
        'source' => $source,
        'message' => $message,
        'raw' => $line,
        'type' => $logType
    ];
    
    // Agregar campos específicos si existen
    if (isset($ip)) $logEntry['ip'] = $ip;
    if (isset($status_code)) $logEntry['status_code'] = $status_code;
    if (isset($file)) $logEntry['file'] = $file;
    if (isset($line_num)) $logEntry['line'] = $line_num;
    if (isset($code)) $logEntry['code'] = $code;
    
    return $logEntry;
}

// Configuración de logs del sistema - acceso directo a archivos originales
$logSources = [
    'apache_error' => [
        'path' => '/var/log/apache2/domains/codigoamigo.com.error.log',
        'name' => 'Apache Error',
        'type' => 'apache_error'
    ],
    'apache_access' => [
        'path' => '/var/log/apache2/domains/codigoamigo.com.log',
        'name' => 'Apache Access',
        'type' => 'apache_access'
    ],
    'php_error' => [
        'path' => '/home/admin/web/codigoamigo.com/public_html/php_errors.log',
        'name' => 'PHP Error',
        'type' => 'php_error'
    ],
    'php_fpm' => [
        'path' => '/var/log/php8.4-fpm.log',
        'name' => 'PHP-FPM',
        'type' => 'php_fpm'
    ],
    'nginx_error' => [
        'path' => '/var/log/hestia/nginx-error.log',
        'name' => 'Nginx Error',
        'type' => 'nginx_error'
    ],
    'nginx_access' => [
        'path' => '/var/log/hestia/nginx-access.log',
        'name' => 'Nginx Access',
        'type' => 'apache_access'
    ],
    'mysql_error' => [
        'path' => '/var/log/mysql/error.log',
        'name' => 'MySQL Error',
        'type' => 'mysql_error'
    ],
    'system' => [
        'path' => '/var/log/hestia/error.log',
        'name' => 'System',
        'type' => 'system'
    ]
];

// Obtener parámetros de la petición
$action = $_GET['action'] ?? 'get_logs';
$source = $_GET['source'] ?? null;
$lines = (int)($_GET['lines'] ?? 100);
$level = $_GET['level'] ?? null;
$search = $_GET['search'] ?? null;
$lastTimestamp = (int)($_GET['last_timestamp'] ?? 0);

try {
    switch ($action) {
        case 'get_logs':
            $allLogs = [];
            
            if ($source && isset($logSources[$source])) {
                // Leer un log específico
                $logData = $logSources[$source];
                $rawLines = readLogRealtime($logData['path'], $lines);
                
                foreach ($rawLines as $line) {
                    $parsedLog = parseLogLine($line, $logData['type'], $source);
                    if ($parsedLog && $parsedLog['timestamp'] > $lastTimestamp) {
                        $allLogs[] = $parsedLog;
                    }
                }
            } else {
                // Leer todos los logs
                foreach ($logSources as $key => $logData) {
                    $rawLines = readLogRealtime($logData['path'], $lines);
                    
                    foreach ($rawLines as $line) {
                        $parsedLog = parseLogLine($line, $logData['type'], $key);
                        if ($parsedLog && isset($parsedLog['timestamp']) && $parsedLog['timestamp'] > $lastTimestamp) {
                            $allLogs[] = $parsedLog;
                        }
                    }
                }
            }
            
            // Aplicar filtros
            if ($level) {
                $allLogs = array_filter($allLogs, function($log) use ($level) {
                    return $log['level'] === $level;
                });
            }
            
            if ($search) {
                $allLogs = array_filter($allLogs, function($log) use ($search) {
                    return stripos($log['message'], $search) !== false;
                });
            }
            
            // Ordenar por timestamp descendente
            usort($allLogs, function($a, $b) {
                $timestampA = isset($a['timestamp']) ? $a['timestamp'] : 0;
                $timestampB = isset($b['timestamp']) ? $b['timestamp'] : 0;
                return $timestampB <=> $timestampA;
            });
            
            echo json_encode([
                'success' => true,
                'logs' => $allLogs,
                'count' => count($allLogs),
                'timestamp' => time()
            ]);
            break;
            
        case 'get_stats':
            $stats = [
                'total_logs' => 0,
                'error_logs' => 0,
                'warning_logs' => 0,
                'info_logs' => 0,
                'by_source' => [],
                'recent_errors' => 0
            ];
            
            foreach ($logSources as $key => $logData) {
                $rawLines = readLogRealtime($logData['path'], 1000);
                $sourceStats = [
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                    'info' => 0
                ];
                
                foreach ($rawLines as $line) {
                    $parsedLog = parseLogLine($line, $logData['type'], $key);
                    if ($parsedLog) {
                        $stats['total_logs']++;
                        $sourceStats['total']++;
                        $sourceStats[$parsedLog['level']]++;
                        
                        if ($parsedLog['level'] === 'error') {
                            $stats['error_logs']++;
                            if ($parsedLog['timestamp'] > (time() - 3600)) {
                                $stats['recent_errors']++;
                            }
                        } elseif ($parsedLog['level'] === 'warning') {
                            $stats['warning_logs']++;
                        } else {
                            $stats['info_logs']++;
                        }
                    }
                }
                
                $stats['by_source'][$key] = $sourceStats;
            }
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => time()
            ]);
            break;
            
        case 'get_sources_status':
            $status = [];
            
            foreach ($logSources as $key => $logData) {
                $info = [
                    'name' => $logData['name'],
                    'exists' => file_exists($logData['path']),
                    'readable' => is_readable($logData['path']),
                    'size' => file_exists($logData['path']) ? filesize($logData['path']) : 0,
                    'modified' => file_exists($logData['path']) ? date('Y-m-d H:i:s', filemtime($logData['path'])) : null,
                    'error' => null
                ];
                
                $status[$key] = $info;
            }
            
            echo json_encode([
                'success' => true,
                'sources' => $status,
                'timestamp' => time()
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    // Limpiar cualquier output previo
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// Limpiar output buffer y devolver solo JSON
ob_clean();
exit;
?>
