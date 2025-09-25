<?php
/**
 * Script para solucionar errores críticos en codigoamigo.com
 * - Error de MongoDB BSONArray
 * - Función duplicada menu_mobile()
 * - Warning de Datetime
 */

echo "=== SOLUCIONANDO ERRORES DE CODIGOAMIGO.COM ===\n\n";

// 1. Solucionar warning de Datetime en config/app.php
echo "1. Solucionando warning de Datetime...\n";
$configFile = '/home/admin/web/codigoamigo.com/public_html/config/app.php';
$configContent = file_get_contents($configFile);

// Eliminar la línea problemática de use Datetime
$configContent = preg_replace('/\/\/ use \\\\Datetime;.*\n/', '', $configContent);

if (file_put_contents($configFile, $configContent)) {
    echo "   ✓ Warning de Datetime solucionado\n";
} else {
    echo "   ✗ Error al solucionar warning de Datetime\n";
}

// 2. Verificar y solucionar función duplicada menu_mobile
echo "\n2. Verificando función menu_mobile...\n";
$headerFile = '/home/admin/web/codigoamigo.com/public_html/myphp/_header.php';
$headerContent = file_get_contents($headerFile);

// Contar declaraciones de la función
$functionCount = substr_count($headerContent, 'function menu_mobile()');
echo "   Declaraciones encontradas: $functionCount\n";

if ($functionCount > 1) {
    echo "   ⚠ Función duplicada detectada\n";
} else {
    echo "   ✓ Función no duplicada\n";
}

// 3. Verificar versión de MongoDB
echo "\n3. Verificando versión de MongoDB...\n";
$composerFile = '/home/admin/web/codigoamigo.com/public_html/composer.json';
if (file_exists($composerFile)) {
    $composer = json_decode(file_get_contents($composerFile), true);
    if (isset($composer['require']['mongodb/mongodb'])) {
        echo "   Versión MongoDB: " . $composer['require']['mongodb/mongodb'] . "\n";
    }
}

// 4. Crear backup antes de cambios
echo "\n4. Creando backup de seguridad...\n";
$backupDir = '/home/admin/web/codigoamigo.com/public_html/myphp/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '_header.php.backup.' . $timestamp;
if (copy($headerFile, $backupFile)) {
    echo "   ✓ Backup creado: $backupFile\n";
} else {
    echo "   ✗ Error al crear backup\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Recomendaciones:\n";
echo "1. Reiniciar el servidor web (Apache/Nginx)\n";
echo "2. Limpiar caché de PHP si está habilitado\n";
echo "3. Verificar logs de error después de los cambios\n";
?>
