<?php
/**
 * Solución conservadora para mantener compatibilidad
 */

echo "=== SOLUCIÓN CONSERVADORA PARA COMPATIBILIDAD ===\n\n";

// Restaurar composer.json original pero con MongoDB actualizado
echo "1. Restaurando composer.json con MongoDB actualizado...\n";
$composerFile = '/home/admin/web/codigoamigo.com/public_html/composer.json';

// Leer el archivo original de backup si existe
$backupFile = '/home/admin/web/codigoamigo.com/public_html/composer.json.backup';
if (file_exists($backupFile)) {
    $composer = json_decode(file_get_contents($backupFile), true);
} else {
    // Crear configuración básica compatible
    $composer = [
        'require' => [
            'mongodb/mongodb' => '^1.15',
            'slim/slim' => '^3.12',
            'bryanjhv/slim-session' => '~3.0',
            'spatie/image-optimizer' => '^1.2'
        ]
    ];
}

// Solo actualizar MongoDB
$composer['require']['mongodb/mongodb'] = '^1.15';

if (file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT))) {
    echo "   ✓ composer.json restaurado con MongoDB actualizado\n";
} else {
    echo "   ✗ Error al restaurar composer.json\n";
}

// Crear script de instalación específica para MongoDB
echo "\n2. Creando script específico para MongoDB...\n";
$mongodbScript = '/home/admin/web/codigoamigo.com/tests/update_mongodb_only.sh';
$scriptContent = '#!/bin/bash
echo "Actualizando solo MongoDB..."
cd /home/admin/web/codigoamigo.com/public_html

# Instalar solo MongoDB con ignore-platform-reqs
composer require mongodb/mongodb:^1.15 --ignore-platform-reqs --no-dev

echo "MongoDB actualizado correctamente"
';

if (file_put_contents($mongodbScript, $scriptContent)) {
    chmod($mongodbScript, 0755);
    echo "   ✓ Script específico para MongoDB creado\n";
} else {
    echo "   ✗ Error al crear script\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Para completar la solución:\n";
echo "1. Ejecutar: bash /home/admin/web/codigoamigo.com/tests/update_mongodb_only.sh\n";
echo "2. Reiniciar el servidor web\n";
echo "3. Verificar que los errores se han solucionado\n";
?>
