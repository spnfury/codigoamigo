<?php
// Test específico del endpoint de logs con tu sesión
session_start();

echo "<h1>Test del Endpoint de Logs</h1>";
echo "<p><strong>Usuario:</strong> " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "</p>";

// Simular exactamente lo que hace el JavaScript
$_GET['action'] = 'get_logs';
$_GET['lines'] = 100;
$_GET['last_timestamp'] = 0;
$_GET['level'] = '';
$_GET['search'] = '';
$_GET['source'] = '';

echo "<h2>1. Test de logs (GET logs):</h2>";

ob_start();
include 'logs_simple.php';
$output = ob_get_clean();

echo "<h3>Respuesta completa:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

$json = json_decode($output, true);
if ($json && $json['success']) {
    echo "<h3 style='color: green;'>✓ Éxito - Logs: " . $json['count'] . "</h3>";
    if ($json['count'] > 0) {
        echo "<h4>Primeros 3 logs:</h4>";
        for ($i = 0; $i < min(3, count($json['logs'])); $i++) {
            $log = $json['logs'][$i];
            echo "<p><strong>" . $log['source'] . "</strong>: " . substr($log['message'], 0, 100) . "...</p>";
        }
    } else {
        echo "<h3 style='color: red;'>✗ No hay logs en la respuesta</h3>";
    }
} else {
    echo "<h3 style='color: red;'>✗ Error: " . ($json['error'] ?? 'JSON inválido') . "</h3>";
}

echo "<h2>2. Test de estadísticas (GET stats):</h2>";

// Limpiar GET para stats
unset($_GET['lines'], $_GET['last_timestamp'], $_GET['level'], $_GET['search'], $_GET['source']);
$_GET['action'] = 'get_stats';

ob_start();
include 'logs_simple.php';
$stats_output = ob_get_clean();

echo "<h3>Respuesta de estadísticas:</h3>";
echo "<pre>" . htmlspecialchars($stats_output) . "</pre>";

$stats_json = json_decode($stats_output, true);
if ($stats_json && $stats_json['success']) {
    echo "<h3 style='color: green;'>✓ Éxito - Total: " . $stats_json['stats']['total_logs'] . "</h3>";
    echo "<p>Errores: " . $stats_json['stats']['error_logs'] . "</p>";
    echo "<p>Advertencias: " . $stats_json['stats']['warning_logs'] . "</p>";
    echo "<p>Info: " . $stats_json['stats']['info_logs'] . "</p>";
} else {
    echo "<h3 style='color: red;'>✗ Error: " . ($stats_json['error'] ?? 'JSON inválido') . "</h3>";
}

echo "<h2>3. Test de archivos de log directo:</h2>";

$logFiles = [
    '/var/log/apache2/domains/codigoamigo.com.log',
    '/home/admin/web/codigoamigo.com/public_html/php_errors.log'
];

foreach ($logFiles as $file) {
    echo "<h4>" . basename($file) . ":</h4>";
    if (file_exists($file)) {
        echo "<p>✓ Existe - Tamaño: " . number_format(filesize($file)) . " bytes</p>";
        
        if (function_exists('shell_exec')) {
            $output = shell_exec("tail -n 3 " . escapeshellarg($file) . " 2>&1");
            if ($output) {
                echo "<p>✓ Últimas 3 líneas:</p>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                echo "<p>✗ No se pudo leer</p>";
            }
        } else {
            echo "<p>✗ shell_exec no disponible</p>";
        }
    } else {
        echo "<p>✗ No existe</p>";
    }
}

echo "<h2>4. Test de función readLogRealtime:</h2>";

// Simular la función del endpoint (sin redeclarar)
if (!function_exists('readLogRealtime')) {
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
}

$testFile = '/var/log/apache2/domains/codigoamigo.com.log';
echo "<p>Probando lectura de: $testFile</p>";
$lines = readLogRealtime($testFile, 5);
echo "<p>Líneas obtenidas: " . count($lines) . "</p>";
if (count($lines) > 0) {
    echo "<p>Primera línea: " . htmlspecialchars($lines[0]) . "</p>";
}

echo "<h2>5. Test JavaScript (simulado):</h2>";
echo "<div id='js-test-results'></div>";
echo "<button onclick='testWithJavaScript()'>Probar con JavaScript</button>";

echo "<script>
async function testWithJavaScript() {
    const results = document.getElementById('js-test-results');
    results.innerHTML = 'Probando...';
    
    try {
        // Test logs
        const logsResponse = await fetch('logs_simple.php?action=get_logs&lines=5');
        const logsData = await logsResponse.json();
        
        results.innerHTML = '<h4>Respuesta de logs:</h4>';
        results.innerHTML += '<pre>' + JSON.stringify(logsData, null, 2) + '</pre>';
        
        if (logsData.success) {
            results.innerHTML += '<p style=\"color: green;\">✓ Logs: ' + logsData.count + '</p>';
        } else {
            results.innerHTML += '<p style=\"color: red;\">✗ Error: ' + logsData.error + '</p>';
        }
        
        // Test stats
        const statsResponse = await fetch('logs_simple.php?action=get_stats');
        const statsData = await statsResponse.json();
        
        results.innerHTML += '<h4>Respuesta de estadísticas:</h4>';
        results.innerHTML += '<pre>' + JSON.stringify(statsData, null, 2) + '</pre>';
        
    } catch (error) {
        results.innerHTML += '<p style=\"color: red;\">✗ Error JavaScript: ' + error.message + '</p>';
    }
}
</script>";
?>
