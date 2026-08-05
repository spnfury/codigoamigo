<?php
/**
 * Migración: Añadir fecha_fin_destacado a códigos ya destacados
 * 
 * Este script añade fecha_fin_destacado a los códigos que ya tienen
 * destacado > 0 pero no tienen fecha_fin_destacado.
 * 
 * - Super (destacado_social > 0) → +14 días desde ahora
 * - Normal (solo destacado > 0)  → +7 días desde ahora
 * 
 * Uso: php migrate_destacados_duracion.php [--dry-run]
 */

require_once '/home/admin/web/codigoamigo.com/public_html/vendor/autoload.php';
require_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
require_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

$dry_run = in_array('--dry-run', $argv ?? []);

echo "=== Migración: fecha_fin_destacado ===\n";
echo $dry_run ? "MODO: DRY RUN (no se harán cambios)\n" : "MODO: EJECUCIÓN REAL\n";
echo "---\n";

$collection = getCollectionCodigos();

// Buscar códigos con destacado > 0 que NO tengan fecha_fin_destacado o la tengan a null
$filter = [
    'destacado' => ['$ne' => 0],
    '$or' => [
        ['fecha_fin_destacado' => ['$exists' => false]],
        ['fecha_fin_destacado' => null],
        ['fecha_fin_destacado' => '']
    ]
];

$codigos = $collection->find($filter);
$count_super = 0;
$count_normal = 0;
$count_skip = 0;

foreach ($codigos as $codigo) {
    $id = (string)$codigo['_id'];
    $marca = $codigo['marca'] ?? 'N/A';
    $tipo = $codigo['tipo_destacado'] ?? 'unknown';
    $tiene_social = isset($codigo['destacado_social']) && $codigo['destacado_social'] > 0;
    
    if ($tiene_social || $tipo === 'super') {
        $duracion = DESTACADO_DURACION_SUPER;
        $label = 'Super';
        $count_super++;
    } else {
        $duracion = DESTACADO_DURACION_NORMAL;
        $label = 'Normal';
        $count_normal++;
    }
    
    $fecha_fin = new MongoDB\BSON\UTCDateTime((time() + ($duracion * 86400)) * 1000);
    $fecha_fin_str = date('Y-m-d H:i:s', time() + ($duracion * 86400));
    
    echo "[$label] $id | marca: $marca | tipo: $tipo | expira: $fecha_fin_str\n";
    
    if (!$dry_run) {
        $collection->updateOne(
            ['_id' => $codigo['_id']],
            ['$set' => ['fecha_fin_destacado' => $fecha_fin]]
        );
    }
}

echo "\n=== Resumen ===\n";
echo "Super: $count_super códigos\n";
echo "Normal: $count_normal códigos\n";
echo "Total: " . ($count_super + $count_normal) . " códigos\n";

if ($dry_run) {
    echo "\n⚠️  Ejecuta sin --dry-run para aplicar los cambios.\n";
} else {
    echo "\n✅ Migración completada.\n";
}
