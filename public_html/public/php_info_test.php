<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";
echo "Shell_exec disponible: " . (function_exists('shell_exec') ? 'SÍ' : 'NO') . "<br>";
echo "Open_basedir: " . (ini_get('open_basedir') ?: 'Sin restricciones') . "<br>";
echo "Disable_functions: " . ini_get('disable_functions') . "<br>";

// Test directo de shell_exec
echo "<br>Test shell_exec:<br>";
$result = shell_exec('echo "test_shell_exec"');
echo "Resultado: " . ($result ? $result : 'NO FUNCIONA') . "<br>";

// Test de acceso a logs
echo "<br>Test acceso a logs:<br>";
$logFile = '/var/log/apache2/domains/codigoamigo.com.log';
echo "Archivo existe: " . (file_exists($logFile) ? 'SÍ' : 'NO') . "<br>";
if (file_exists($logFile)) {
    echo "Tamaño: " . filesize($logFile) . " bytes<br>";
    $lines = shell_exec("tail -n 2 " . escapeshellarg($logFile));
    echo "Últimas 2 líneas:<br><pre>" . htmlspecialchars($lines) . "</pre>";
}
?>
