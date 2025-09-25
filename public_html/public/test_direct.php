<?php
session_start();
$_SESSION['user_id'] = "58bd851da54e295b8b52f702";

echo "<h1>Test Directo del Endpoint</h1>";

// Simular la llamada al endpoint
$_GET['action'] = 'get_logs';
$_GET['lines'] = 5;
$_GET['level'] = '';
$_GET['search'] = '';
$_GET['source'] = '';

echo "<h2>Parámetros simulados:</h2>";
echo "<p>action: " . $_GET['action'] . "</p>";
echo "<p>lines: " . $_GET['lines'] . "</p>";
echo "<p>level: " . $_GET['level'] . "</p>";
echo "<p>search: " . $_GET['search'] . "</p>";
echo "<p>source: " . $_GET['source'] . "</p>";

echo "<h2>Resultado del endpoint:</h2>";

// Capturar el output del endpoint
ob_start();
include 'logs_simple.php';
$output = ob_get_clean();

echo "<h3>Output crudo:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

echo "<h3>JSON parseado:</h3>";
$json = json_decode($output, true);
if ($json) {
    echo "<p>✓ JSON válido</p>";
    echo "<p>Success: " . ($json['success'] ? 'true' : 'false') . "</p>";
    echo "<p>Count: " . $json['count'] . "</p>";
    echo "<p>Logs: " . count($json['logs']) . "</p>";
    
    if (count($json['logs']) > 0) {
        echo "<h4>Primer log:</h4>";
        echo "<pre>" . json_encode($json['logs'][0], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>No hay logs</p>";
    }
} else {
    echo "<p style='color: red;'>✗ JSON inválido</p>";
    echo "<p>Error: " . json_last_error_msg() . "</p>";
}

echo "<h2>Logs de error del servidor:</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $recentErrors = shell_exec("tail -n 20 " . escapeshellarg($errorLog));
    echo "<pre>" . htmlspecialchars($recentErrors) . "</pre>";
} else {
    echo "<p>No se encontró log de errores</p>";
}
?>
