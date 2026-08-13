<?php
/**
 * Script para restaurar los códigos desactivados masivamente por el cron de antigüedad
 * 
 * Uso:
 *   php scripts/restore_codes.php --dry-run    (ver qué se restauraría)
 *   php scripts/restore_codes.php --execute    (ejecutar la restauración)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';

$dry_run = in_array('--dry-run', $argv ?? []);
$execute = in_array('--execute', $argv ?? []);

if (!$dry_run && !$execute) {
    echo "Uso:" . PHP_EOL;
    echo "  php scripts/restore_codes.php --dry-run    (ver qué se restauraría)" . PHP_EOL;
    echo "  php scripts/restore_codes.php --execute    (ejecutar la restauración)" . PHP_EOL;
    exit(1);
}

echo "=== Restauración de códigos desactivados por antigüedad ===" . PHP_EOL;
echo "Modo: " . ($dry_run ? "DRY-RUN (sin cambios)" : "EJECUTAR") . PHP_EOL;
echo "Fecha: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

$collection = getCollectionCodigos();

// Filtro: códigos con estado=-3 que tenían estado_anterior=0 (estaban activos)
$filtro = [
    'estado' => -3,
    'estado_anterior_antiguedad' => 0,
    'fecha_desactivacion_antiguedad' => ['$exists' => true]
];

$count = $collection->countDocuments($filtro);
echo "Códigos a restaurar: $count" . PHP_EOL . PHP_EOL;

if ($dry_run) {
    // Mostrar resumen por marca
    $pipeline = [
        ['$match' => $filtro],
        ['$group' => [
            '_id' => '$marca',
            'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 30]
    ];
    
    $por_marca = $collection->aggregate($pipeline)->toArray();
    echo "Top 30 marcas afectadas:" . PHP_EOL;
    foreach ($por_marca as $m) {
        echo "  " . str_pad($m['_id'], 25) . " " . $m['count'] . " códigos" . PHP_EOL;
    }
    
    echo PHP_EOL . "Para ejecutar la restauración:" . PHP_EOL;
    echo "  php scripts/restore_codes.php --execute" . PHP_EOL;
    
} else {
    // EJECUTAR: restaurar todos
    echo "Restaurando $count códigos..." . PHP_EOL;
    
    $result = $collection->updateMany(
        $filtro,
        [
            '$set' => ['estado' => 0],
            '$unset' => [
                'fecha_desactivacion_antiguedad' => '',
                'aviso_antiguedad_enviado' => '',
                'estado_anterior_antiguedad' => ''
            ]
        ]
    );
    
    $modified = $result->getModifiedCount();
    echo "✅ Códigos restaurados: $modified" . PHP_EOL . PHP_EOL;
    
    // Verificar resultado
    echo "=== Verificación ===" . PHP_EOL;
    echo "  estado=0 (activo): " . $collection->countDocuments(['estado' => 0]) . PHP_EOL;
    echo "  estado=-1 (admin): " . $collection->countDocuments(['estado' => -1]) . PHP_EOL;
    echo "  estado=-2 (user): " . $collection->countDocuments(['estado' => -2]) . PHP_EOL;
    echo "  estado=-3 (antigüedad): " . $collection->countDocuments(['estado' => -3]) . PHP_EOL;
    echo "  total: " . $collection->countDocuments([]) . PHP_EOL;
}
