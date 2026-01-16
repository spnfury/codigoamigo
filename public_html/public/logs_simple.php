<?php
// Endpoint simplificado para logs en tiempo real
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Configurar para evitar warnings
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Intentar eliminar restricción open_basedir
ini_set('open_basedir', '');

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    http_response_code(403);
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(['success' => false, 'error' => 'No hay sesión activa. Debe iniciar sesión como administrador.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no autorizado. ID: ' . $_SESSION["user_id"]]);
    }
    exit;
}

// Configuración de logs del sistema para codigoamigo.com
$logSources = [
    'apache_error' => [
        'path' => '/var/log/apache2/domains/codigoamigo.com.error.log',
        'name' => 'Apache Error (codigoamigo.com)',
        'type' => 'apache_error'
    ],
    'apache_access' => [
        'path' => '/var/log/apache2/domains/codigoamigo.com.log',
        'name' => 'Apache Access (codigoamigo.com)',
        'type' => 'apache_access'
    ],
    'nginx_error' => [
        'path' => '/var/log/nginx/domains/codigoamigo.com.error.log',
        'name' => 'Nginx Error (codigoamigo.com)',
        'type' => 'nginx_error'
    ],
    'nginx_access' => [
        'path' => '/var/log/nginx/domains/codigoamigo.com.log',
        'name' => 'Nginx Access (codigoamigo.com)',
        'type' => 'apache_access'
    ],
    'php_error' => [
        'path' => '/home/admin/web/codigoamigo.com/public_html/php_errors.log',
        'name' => 'PHP Error (codigoamigo.com)',
        'type' => 'php_error'
    ],
    'php_fpm' => [
        'path' => '/var/log/php8.4-fpm.log',
        'name' => 'PHP-FPM',
        'type' => 'php_fpm'
    ],
    'mysql_error' => [
        'path' => '/var/log/mysql/error.log',
        'name' => 'MySQL Error',
        'type' => 'mysql_error'
    ],
    'system' => [
        'path' => '/var/log/hestia/error.log',
        'name' => 'System (Hestia)',
        'type' => 'system'
    ],
    'hestia_access' => [
        'path' => '/var/log/hestia/LE-admin-mda.codigoamigo.com.log',
        'name' => 'Hestia Access (mda.codigoamigo.com)',
        'type' => 'apache_access'
    ]
];

// Función para leer logs directamente del sistema
function readLogRealtime($logPath, $lines = 100) {
    if (!file_exists($logPath)) {
        return [];
    }
    
    if (!is_readable($logPath)) {
        return [];
    }
    
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $command = "tail -n $lines " . escapeshellarg($logPath) . " 2>/dev/null";
        $output = shell_exec($command);
        
        if ($output) {
            $lines_array = array_filter(explode("\n", $output));
            // Agregar información del archivo a cada línea para debugging
            foreach ($lines_array as &$line) {
                $line = trim($line);
            }
            return $lines_array;
        }
    }
    
    return [];
}

// Función para parsear errores completos de PHP
function parseCompletePHPError($logLines, $source) {
    $errors = [];
    $currentError = '';
    $errorStart = false;
    $url = '';

    foreach ($logLines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Detectar inicio de error PHP (timestamp con formato más flexible)
        if (preg_match('/^\[(\d{2}-\w{3}-\d{4}\s+\d{2}:\d{2}:\d{2}[^\]]+)\]/', $line, $matches)) {
            // Si ya teníamos un error, guardarlo
            if ($errorStart && !empty($currentError)) {
                $errors[] = [
                    'timestamp' => time(),
                    'level' => 'error',
                    'source' => $source,
                    'message' => $currentError,
                    'raw' => $currentError,
                    'type' => 'php_error',
                    'file_path' => getLogFilePath($source),
                    'url' => $url,
                    'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
                ];
            }

            // Iniciar nuevo error
            $currentError = $line;
            $errorStart = true;
            $url = '';

            // Intentar extraer URL del error si está en el stack trace
            if (preg_match('/\/public_html\/([^\s\(]+)/', $line, $urlMatches)) {
                $url = '/' . $urlMatches[1];
                // Si es un archivo en /public/, esa es la URL real
                if (strpos($url, '/public/') !== false) {
                    // Ya tenemos la URL correcta
                }
            }

        } elseif (strpos($lineLower, 'acortador chollo') !== false) {
             // Es un log de actividad no un error
             $level = 'info';
             // ... logic ...
             
             $errors[] = [
                'timestamp' => time(),
                'level' => 'info',
                'source' => 'activity',
                'message' => $currentError ?: $line,
                'raw' => $currentError ?: $line,
                'type' => 'activity',
                'file_path' => getLogFilePath($source),
                'url' => '',
                'external_url' => ''
            ];
            
            $currentError = '';
            $errorStart = false; 
            continue;
            
        } elseif ($errorStart) {
            // Continuar construyendo el error
            $currentError .= "\n" . $line;

            // Buscar URL en líneas del stack trace
            if (preg_match('/\/public_html\/([^\s\(]+)/', $line, $urlMatches)) {
                $foundUrl = '/' . $urlMatches[1];
                // Priorizar archivos en /public/ sobre index.php
                if (strpos($foundUrl, '/public/') !== false) {
                    $url = $foundUrl;
                } elseif (empty($url)) {
                    $url = $foundUrl;
                }
            }

            // Si encontramos el final del stack trace, terminar el error
            if (strpos($line, '{main}') !== false || strpos($line, 'View in rendered output') !== false) {
                // Agregar este error
                $errors[] = [
                    'timestamp' => time(),
                    'level' => 'error',
                    'source' => $source,
                    'message' => $currentError,
                    'raw' => $currentError,
                    'type' => 'php_error',
                    'file_path' => getLogFilePath($source),
                    'url' => $url,
                    'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
                ];

                // Resetear para el siguiente error
                $currentError = '';
                $errorStart = false;
                $url = '';
            }
        }
    }

    // Agregar el último error si existe y no se procesó
    if ($errorStart && !empty($currentError)) {
        $errors[] = [
            'timestamp' => time(),
            'level' => 'error',
            'source' => $source,
            'message' => $currentError,
            'raw' => $currentError,
            'type' => 'php_error',
            'file_path' => getLogFilePath($source),
            'url' => $url,
            'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
        ];
    }

    return $errors;
}

// Función para parsear errores de Slim Framework
function parseSlimFrameworkError($logLines, $source) {
    $errors = [];
    $currentError = '';
    $errorStart = false;
    $url = '';

    foreach ($logLines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Detectar inicio de error de Slim Framework
        if (strpos($line, 'Slim Application Error:') === 0) {
            // Si ya teníamos un error, guardarlo
            if ($errorStart && !empty($currentError)) {
                $errors[] = [
                    'timestamp' => time(),
                    'level' => 'error',
                    'source' => $source,
                    'message' => $currentError,
                    'raw' => $currentError,
                    'type' => 'php_error',
                    'file_path' => getLogFilePath($source),
                    'url' => $url,
                    'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
                ];
            }

            // Iniciar nuevo error
            $currentError = $line;
            $errorStart = true;
            $url = '';

        } elseif ($errorStart) {
            // Continuar construyendo el error
            $currentError .= "\n" . $line;

            // Buscar URL en el stack trace de Slim
            if (preg_match('/\/public_html\/([^\s\(]+)/', $line, $urlMatches)) {
                $foundUrl = '/' . $urlMatches[1];
                // Priorizar archivos en /public/ sobre index.php y app_with_mongo.php
                if (strpos($foundUrl, '/public/') !== false) {
                    $url = $foundUrl;
                } elseif (empty($url) && !strpos($foundUrl, '/vendor/')) {
                    // Evitar archivos de vendor, pero tomar index.php o app_with_mongo.php si no hay mejor opción
                    $url = $foundUrl;
                }
            }

            // Si encontramos el final del stack trace (línea con {main})
            if (strpos($line, '{main}') !== false) {
                // Agregar este error
                $errors[] = [
                    'timestamp' => time(),
                    'level' => 'error',
                    'source' => $source,
                    'message' => $currentError,
                    'raw' => $currentError,
                    'type' => 'php_error',
                    'file_path' => getLogFilePath($source),
                    'url' => $url,
                    'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
                ];

                // Resetear para el siguiente error
                $currentError = '';
                $errorStart = false;
                $url = '';
            }
        }
    }

    // Agregar el último error si existe y no se procesó
    if ($errorStart && !empty($currentError)) {
        $errors[] = [
            'timestamp' => time(),
            'level' => 'error',
            'source' => $source,
            'message' => $currentError,
            'raw' => $currentError,
            'type' => 'php_error',
            'file_path' => getLogFilePath($source),
            'url' => $url,
            'external_url' => !empty($url) ? 'https://www.codigoamigo.com' . $url : ''
        ];
    }

    return $errors;
}

// Función para parsear logs
function parseLogLine($line, $logType, $source) {
    if (empty(trim($line))) {
        return null;
    }
    
    // Clasificar por tipo de log primero
    $level = 'info';
    $lineLower = strtolower($line);
    $statusCode = null;
    
    // Apache/Nginx Access logs
    if ($logType === 'apache_access' || $logType === 'nginx_access') {
        // Intentar extraer el código de estado
        // Formato típico: "GET /path HTTP/1.1" 200 ...
        if (preg_match('/"\s+(\d{3})\s+/', $line, $matches)) {
            $statusCode = (int)$matches[1];
            
            if ($statusCode >= 500) {
                $level = 'error';
            } elseif ($statusCode >= 400) {
                $level = 'warning';
            } elseif ($statusCode >= 300 && $statusCode < 400) {
                $level = 'info'; // Redirects are info, but we will filter them specifically
            } else {
                $level = 'info';
            }
        }
    }
    // Apache/Nginx Error logs son siempre ERROR
    elseif ($logType === 'apache_error' || $logType === 'nginx_error' || $logType === 'mysql_error') {
        $level = 'error';
    }
    // PHP logs - detectar nivel por contenido
    elseif ($logType === 'php_error' || $logType === 'php_fpm') {
        if (strpos($lineLower, 'fatal error') !== false || strpos($lineLower, 'critical') !== false) {
            $level = 'error';
        } elseif (strpos($lineLower, 'call to undefined function') !== false || 
                  strpos($lineLower, 'undefined variable') !== false ||
                  strpos($lineLower, 'undefined array key') !== false) {
            $level = 'error';
        } elseif (strpos($lineLower, 'warning') !== false || strpos($lineLower, 'warn') !== false) {
            $level = 'warning';
        } elseif (strpos($lineLower, 'notice') !== false || strpos($lineLower, 'deprecated') !== false) {
            $level = 'info';
        } elseif (strpos($lineLower, 'acortador chollo') !== false) {
            // Mensajes informativos de la aplicación
            $level = 'info';
        } else {
            $level = 'error'; // Por defecto, errores de PHP son error
        }
    }
    // System logs - detectar por contenido
    else {
        if (strpos($lineLower, 'error') !== false || strpos($lineLower, 'fatal') !== false || strpos($lineLower, 'critical') !== false) {
            $level = 'error';
        } elseif (strpos($lineLower, 'warning') !== false || strpos($lineLower, 'warn') !== false) {
            $level = 'warning';
        } else {
            $level = 'info';
        }
    }
    
    // Extraer URL de la línea si es un log de Apache/Nginx
    $url = '';
    $external_url = '';
    if ($logType === 'apache_access' || $logType === 'nginx_access') {
        // Extraer URL de logs de acceso: "GET /ruta HTTP/2.0"
        if (preg_match('/"(GET|POST|PUT|DELETE|PATCH)\s+([^\s]+)\s+HTTP/', $line, $matches)) {
            $url = $matches[2];
            // Generar URL externa completa
            $external_url = 'https://www.codigoamigo.com' . $url;
        }
    }
    
    $logEntry = [
        'timestamp' => time(),
        'level' => $level,
        'source' => $source,
        'message' => $line,
        'raw' => $line,
        'type' => $logType,
        'status_code' => $statusCode,
        'file_path' => getLogFilePath($source),
        'url' => $url,
        'external_url' => $external_url
    ];
    
    return $logEntry;
}

// Función para obtener la ruta del archivo de log
function getLogFilePath($source) {
    global $logSources;
    
    $logFilePaths = [
        'apache_error' => '/var/log/apache2/domains/codigoamigo.com.error.log',
        'apache_access' => '/var/log/apache2/domains/codigoamigo.com.log',
        'nginx_error' => '/var/log/nginx/domains/codigoamigo.com.error.log',
        'nginx_access' => '/var/log/nginx/domains/codigoamigo.com.log',
        'php_error' => '/home/admin/web/codigoamigo.com/public_html/php_errors.log',
        'php_fpm' => '/var/log/php8.4-fpm.log',
        'mysql_error' => '/var/log/mysql/error.log',
        'system' => '/var/log/hestia/error.log',
        'hestia_access' => '/var/log/hestia/LE-admin-mda.codigoamigo.com.log'
    ];
    
    return $logFilePaths[$source] ?? 'unknown';
}

// Función para calcular estadísticas
function calculateLogStats($logs) {
    $stats = [
        'total_logs' => count($logs),
        'error_logs' => 0,
        'warning_logs' => 0,
        'info_logs' => 0,
        'recent_errors' => 0
    ];
    
    foreach ($logs as $log) {
        switch ($log['level']) {
            case 'error':
                $stats['error_logs']++;
                if ($log['timestamp'] > (time() - 3600)) {
                    $stats['recent_errors']++;
                }
                break;
            case 'warning':
                $stats['warning_logs']++;
                break;
            case 'info':
                $stats['info_logs']++;
                break;
        }
    }
    
    return $stats;
}


try {
    $action = $_GET['action'] ?? 'get_logs';
    $source = $_GET['source'] ?? null;
    $lines = (int)($_GET['lines'] ?? 100);
    $level = $_GET['level'] ?? null;
    $search = $_GET['search'] ?? null;
    
    switch ($action) {
        case 'get_logs':
            $allLogs = [];
            
            if ($source && isset($logSources[$source])) {
                // Leer un log específico
                $logLines = readLogRealtime($logSources[$source]['path'], $lines);
                
                // Si es un log de PHP, usar parseo completo
                if ($logSources[$source]['type'] === 'php_error') {
                    $completeErrors = parseCompletePHPError($logLines, $source);
                    $slimErrors = parseSlimFrameworkError($logLines, $source);
                    $allLogs = array_merge($allLogs, $completeErrors, $slimErrors);
                } else {
                    foreach ($logLines as $line) {
                        $parsed = parseLogLine($line, $logSources[$source]['type'], $source);
                        if ($parsed) {
                            $allLogs[] = $parsed;
                        }
                    }
                }
            } else {
                // Leer todos los logs
                foreach ($logSources as $key => $sourceConfig) {
                    $logLines = readLogRealtime($sourceConfig['path'], $lines);
                    
                    // Si es un log de PHP, usar parseo completo
                    if ($sourceConfig['type'] === 'php_error') {
                        $completeErrors = parseCompletePHPError($logLines, $key);
                        $slimErrors = parseSlimFrameworkError($logLines, $key);
                        $allLogs = array_merge($allLogs, $completeErrors, $slimErrors);
                    } else {
                        foreach ($logLines as $line) {
                            $parsed = parseLogLine($line, $sourceConfig['type'], $key);
                            if ($parsed) {
                                // FILTERING LOGIC: Keep only relevant logs
                                $keepLog = false;
                                
                                // 1. Always keep PHP, Apache/Nginx Error, MySQL errors
                                if (in_array($parsed['type'], ['apache_error', 'nginx_error', 'mysql_error', 'system'])) {
                                    $keepLog = true;
                                }
                                // 2. For Access Logs (Apache/Nginx), ONLY keep 404, 500, 301
                                elseif (in_array($parsed['type'], ['apache_access', 'nginx_access', 'hestia_access'])) {
                                    if (isset($parsed['status_code'])) {
                                        if ($parsed['status_code'] == 404 || 
                                            $parsed['status_code'] >= 500 || 
                                            $parsed['status_code'] == 301) {
                                            $keepLog = true;
                                        }
                                    }
                                }
                                // 3. Keep anything already marked as 'error' or 'warning' by parseLogLine
                                elseif ($parsed['level'] === 'error' || $parsed['level'] === 'warning') {
                                    $keepLog = true;
                                }
                                
                                if ($keepLog) {
                                    $allLogs[] = $parsed;
                                }
                            }
                        }
                    }
                }
            }
            
            // Aplicar filtros si se especifican
            if ($level) {
                $beforeCount = count($allLogs);
                $allLogs = array_filter($allLogs, function($log) use ($level) {
                    return $log['level'] === $level;
                });
                $afterCount = count($allLogs);
            }
            
            if ($search) {
                $allLogs = array_filter($allLogs, function($log) use ($search) {
                    return stripos($log['message'], $search) !== false;
                });
            }
            
            // Ordenar por timestamp (más recientes primero)
            usort($allLogs, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            
            echo json_encode([
                'success' => true,
                'logs' => $allLogs,
                'count' => count($allLogs),
                'timestamp' => time()
            ]);
            break;
            
        case 'get_stats':
            $allLogs = [];
            foreach ($logSources as $key => $sourceConfig) {
                $logLines = readLogRealtime($sourceConfig['path'], 100);

                // Si es un log de PHP, usar parseo completo
                if ($sourceConfig['type'] === 'php_error') {
                    $completeErrors = parseCompletePHPError($logLines, $key);
                    $slimErrors = parseSlimFrameworkError($logLines, $key);
                    $allLogs = array_merge($allLogs, $completeErrors, $slimErrors);
                } else {
                    foreach ($logLines as $line) {
                        $parsed = parseLogLine($line, $sourceConfig['type'], $key);
                        if ($parsed) {
                            $allLogs[] = $parsed;
                        }
                    }
                }
            }
            
            $stats = calculateLogStats($allLogs);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => time()
            ]);
            break;
            
        case 'clear_logs':
            $clearedFiles = [];
            $errors = [];
            
            // Función para limpiar un archivo de log
            function clearLogFile($filePath, $fileName) {
                if (!file_exists($filePath)) {
                    return ['success' => false, 'message' => "Archivo no existe: $fileName"];
                }
                
                if (!is_writable($filePath)) {
                    return ['success' => false, 'message' => "Sin permisos de escritura: $fileName"];
                }
                
                // Intentar limpiar el archivo usando diferentes métodos
                $methods = [
                    // Método 1: Truncar archivo
                    function($path) {
                        return file_put_contents($path, '') !== false;
                    },
                    // Método 2: Usar shell_exec si está disponible
                    function($path) {
                        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
                            $command = '> ' . escapeshellarg($path) . ' 2>/dev/null';
                            shell_exec($command);
                            return filesize($path) == 0;
                        }
                        return false;
                    },
                    // Método 3: Usar fopen/fwrite
                    function($path) {
                        $handle = fopen($path, 'w');
                        if ($handle) {
                            fclose($handle);
                            return true;
                        }
                        return false;
                    }
                ];
                
                foreach ($methods as $method) {
                    if ($method($filePath)) {
                        return ['success' => true, 'message' => "Archivo limpiado: $fileName"];
                    }
                }
                
                return ['success' => false, 'message' => "No se pudo limpiar: $fileName"];
            }
            
            // Limpiar cada archivo de log
            foreach ($logSources as $key => $sourceConfig) {
                $result = clearLogFile($sourceConfig['path'], $sourceConfig['name']);
                
                if ($result['success']) {
                    $clearedFiles[] = $sourceConfig['name'];
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            // También limpiar logs de MongoDB si están disponibles
            try {
                include_once __DIR__ . '/../inc/includes.php';
                $collection_logs = getCollectionLogs();
                
                if ($collection_logs) {
                    $deleteResult = $collection_logs->deleteMany([]);
                    $clearedFiles[] = "Logs de MongoDB (" . $deleteResult->getDeletedCount() . " registros)";
                }
            } catch (Exception $e) {
                // MongoDB no disponible, continuar
            }
            
            $response = [
                'success' => count($errors) === 0,
                'cleared_files' => $clearedFiles,
                'errors' => $errors,
                'timestamp' => time()
            ];
            
            if (count($errors) > 0) {
                $response['message'] = 'Algunos archivos no pudieron ser limpiados. Ver detalles en "errors".';
            } else {
                $response['message'] = 'Todos los logs han sido limpiados exitosamente.';
            }
            
            echo json_encode($response);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
