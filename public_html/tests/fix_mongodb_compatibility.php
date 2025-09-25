<?php
/**
 * Script para solucionar problemas de compatibilidad de MongoDB
 */

echo "=== SOLUCIONANDO PROBLEMAS DE MONGODB ===\n\n";

// Verificar versión actual de PHP
echo "1. Verificando versión de PHP...\n";
echo "   PHP Version: " . PHP_VERSION . "\n";

// Verificar versión de MongoDB en composer.json
echo "\n2. Verificando versión de MongoDB...\n";
$composerFile = '/home/admin/web/codigoamigo.com/public_html/composer.json';
if (file_exists($composerFile)) {
    $composer = json_decode(file_get_contents($composerFile), true);
    if (isset($composer['require']['mongodb/mongodb'])) {
        echo "   Versión MongoDB actual: " . $composer['require']['mongodb/mongodb'] . "\n";
        
        // Actualizar a una versión más reciente compatible
        $composer['require']['mongodb/mongodb'] = '^1.15';
        
        if (file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT))) {
            echo "   ✓ Versión MongoDB actualizada a ^1.15\n";
        } else {
            echo "   ✗ Error al actualizar composer.json\n";
        }
    }
}

// Crear script de actualización de dependencias
echo "\n3. Creando script de actualización...\n";
$updateScript = '/home/admin/web/codigoamigo.com/tests/update_dependencies.sh';
$scriptContent = '#!/bin/bash
echo "Actualizando dependencias de MongoDB..."
cd /home/admin/web/codigoamigo.com/public_html
composer update mongodb/mongodb --no-dev --optimize-autoloader
echo "Dependencias actualizadas correctamente"
';

if (file_put_contents($updateScript, $scriptContent)) {
    chmod($updateScript, 0755);
    echo "   ✓ Script de actualización creado\n";
} else {
    echo "   ✗ Error al crear script de actualización\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Para completar la solución:\n";
echo "1. Ejecutar: bash /home/admin/web/codigoamigo.com/tests/update_dependencies.sh\n";
echo "2. Reiniciar el servidor web\n";
echo "3. Verificar que los errores se han solucionado\n";
?>
