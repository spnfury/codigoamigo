<?php
/**
 * Script para solucionar problemas de compatibilidad con PHP 8.3
 */

echo "=== SOLUCIONANDO PROBLEMAS DE COMPATIBILIDAD PHP 8.3 ===\n\n";

// Actualizar composer.json con versiones compatibles
echo "1. Actualizando dependencias para PHP 8.3...\n";
$composerFile = '/home/admin/web/codigoamigo.com/public_html/composer.json';
$composer = json_decode(file_get_contents($composerFile), true);

// Actualizar dependencias problemáticas
$composer['require']['spatie/image-optimizer'] = '^1.6';
$composer['require']['slim/slim'] = '^4.0';
$composer['require']['mongodb/mongodb'] = '^1.15';

// Eliminar dependencias incompatibles
unset($composer['require']['herloct/slim-newrelic']);

if (file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT))) {
    echo "   ✓ composer.json actualizado\n";
} else {
    echo "   ✗ Error al actualizar composer.json\n";
}

// Crear script de instalación limpia
echo "\n2. Creando script de instalación limpia...\n";
$installScript = '/home/admin/web/codigoamigo.com/tests/clean_install.sh';
$scriptContent = '#!/bin/bash
echo "Realizando instalación limpia de dependencias..."
cd /home/admin/web/codigoamigo.com/public_html

# Eliminar vendor y composer.lock
rm -rf vendor composer.lock

# Instalar dependencias
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "Instalación completada"
';

if (file_put_contents($installScript, $scriptContent)) {
    chmod($installScript, 0755);
    echo "   ✓ Script de instalación creado\n";
} else {
    echo "   ✗ Error al crear script de instalación\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Para completar la solución:\n";
echo "1. Ejecutar: bash /home/admin/web/codigoamigo.com/tests/clean_install.sh\n";
echo "2. Reiniciar el servidor web\n";
echo "3. Verificar que los errores se han solucionado\n";
?>
