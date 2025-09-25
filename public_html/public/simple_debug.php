<?php
session_start();
$_SESSION['user_id'] = "58bd851da54e295b8b52f702";

echo "<h1>Debug Simple de Logs</h1>";

echo "<h2>1. Verificar archivos de log:</h2>";

$logFiles = [
    '/var/log/apache2/domains/codigoamigo.com.log',
    '/var/log/apache2/domains/codigoamigo.com.error.log',
    '/var/log/nginx/domains/codigoamigo.com.log',
    '/var/log/nginx/domains/codigoamigo.com.error.log',
    '/home/admin/web/codigoamigo.com/public_html/php_errors.log',
    '/var/log/php8.4-fpm.log',
    '/var/log/mysql/error.log',
    '/var/log/hestia/error.log'
];

foreach ($logFiles as $file) {
    echo "<h3>" . basename($file) . ":</h3>";
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p>✓ Existe - Tamaño: " . number_format($size) . " bytes</p>";
        echo "<p>Permisos: " . substr(sprintf('%o', fileperms($file)), -4) . "</p>";
        echo "<p>Propietario: " . posix_getpwuid(fileowner($file))['name'] . "</p>";
        echo "<p>Grupo: " . posix_getgrgid(filegroup($file))['name'] . "</p>";
        
        if ($size > 0) {
            echo "<p>Últimas 3 líneas:</p>";
            if (function_exists('shell_exec')) {
                $output = shell_exec("tail -n 3 " . escapeshellarg($file) . " 2>&1");
                if ($output) {
                    echo "<pre>" . htmlspecialchars($output) . "</pre>";
                } else {
                    echo "<p>No se pudo leer con shell_exec</p>";
                }
            } else {
                echo "<p>shell_exec no disponible</p>";
            }
        } else {
            echo "<p>Archivo vacío</p>";
        }
    } else {
        echo "<p>✗ No existe</p>";
    }
}

echo "<h2>2. Test de shell_exec:</h2>";
echo "<p>shell_exec disponible: " . (function_exists('shell_exec') ? 'SÍ' : 'NO') . "</p>";
echo "<p>disable_functions: " . ini_get('disable_functions') . "</p>";

if (function_exists('shell_exec')) {
    $testCommand = "tail -n 3 /var/log/apache2/domains/codigoamigo.com.log";
    echo "<p>Comando de prueba: $testCommand</p>";
    $result = shell_exec($testCommand . " 2>&1");
    if ($result) {
        echo "<p>✓ shell_exec funciona:</p>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
    } else {
        echo "<p>✗ shell_exec no devuelve nada</p>";
    }
}

echo "<h2>3. Test de función readLogRealtime:</h2>";

function readLogRealtime($logPath, $lines = 100) {
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $command = "tail -n $lines " . escapeshellarg($logPath) . " 2>/dev/null";
        $output = shell_exec($command);
        if ($output) {
            return array_filter(explode("\n", $output));
        }
    }
    return [];
}

$testFile = '/var/log/apache2/domains/codigoamigo.com.log';
echo "<p>Probando: $testFile</p>";
$lines = readLogRealtime($testFile, 5);
echo "<p>Líneas obtenidas: " . count($lines) . "</p>";
if (count($lines) > 0) {
    echo "<p>Primera línea: " . htmlspecialchars($lines[0]) . "</p>";
} else {
    echo "<p style='color: red;'>No se obtuvieron líneas</p>";
}

echo "<h2>4. Test de parseLogLine:</h2>";

function parseLogLine($line, $logType, $source) {
    if (empty(trim($line))) {
        return null;
    }
    
    $level = 'info';
    $lineLower = strtolower($line);
    
    if (strpos($lineLower, 'error') !== false || strpos($lineLower, 'fatal') !== false || strpos($lineLower, 'critical') !== false) {
        $level = 'error';
    } elseif (strpos($lineLower, 'warning') !== false || strpos($lineLower, 'warn') !== false) {
        $level = 'warning';
    }
    
    if ($logType === 'apache_error' || $logType === 'nginx_error' || $logType === 'mysql_error') {
        $level = 'error';
    } elseif ($logType === 'php_error' && strpos($lineLower, 'warning') !== false) {
        $level = 'warning';
    }
    
    return [
        'timestamp' => time(),
        'level' => $level,
        'source' => $source,
        'message' => $line,
        'raw' => $line,
        'type' => $logType
    ];
}

$testLine = "213.177.193.0 - - [25/Sep/2025:08:39:22 +0200] \"GET /public/logs_simple.php HTTP/2.0\" 200 77";
$parsed = parseLogLine($testLine, 'apache_access', 'apache_access');
echo "<p>Línea de prueba: " . htmlspecialchars($testLine) . "</p>";
echo "<p>Parseado: " . json_encode($parsed, JSON_PRETTY_PRINT) . "</p>";
?>
