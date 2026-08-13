<?php
/**
 * Diagnóstico y corrección forzada de códigos Octopus Energy
 */
$doc_root = '/home/admin/web/codigoamigo.com/public_html';
require_once $doc_root . '/inc/includes.php';
require_once $doc_root . '/inc/conexion.php';

$db = createConnection();
$col = $db->selectCollection('codigos');

// 1. Buscar TODOS los códigos octopus activos - sin filtro de beneficio
echo "=== TODOS los códigos Octopus activos ===\n";
$codigos = $col->find([
    'marca' => new MongoDB\BSON\Regex('octopus', 'i'),
    'estado' => ['$gte' => 0]
], ['sort' => ['num_beneficio' => -1]]);

$to_fix = [];
foreach ($codigos as $c) {
    $id = (string)$c['_id'];
    $beneficio = $c['num_beneficio'] ?? null;
    $tipo = gettype($beneficio);
    $valor = is_numeric($beneficio) ? floatval($beneficio) : $beneficio;
    
    $needs_fix = false;
    if ($valor > 50) {
        $needs_fix = true;
        $to_fix[] = $c['_id'];
    }
    
    if ($needs_fix || $valor >= 50) {
        echo $id . " | beneficio=" . $beneficio . " (type:" . $tipo . ")" . ($needs_fix ? " *** NEEDS FIX ***" : "") . "\n";
    }
}

echo "\nTotal a corregir: " . count($to_fix) . "\n";

// 2. Corregir todos los que superen 50
if (count($to_fix) > 0) {
    foreach ($to_fix as $fix_id) {
        $col->updateOne(
            ['_id' => $fix_id],
            ['$set' => ['num_beneficio' => 50]]
        );
        echo "Corregido: " . $fix_id . "\n";
    }
}

// 3. Búsqueda ampliada: también por nombre de la marca en la URL de la página
echo "\n=== Códigos con marca tipo 'https://octopus...' (nombre_clave raro) ===\n";
$marcas_col = $db->selectCollection('marcas');
$marcas_octopus = $marcas_col->find(['nombre' => new MongoDB\BSON\Regex('octopus', 'i')]);
foreach ($marcas_octopus as $m) {
    $nc = $m['nombre_clave'] ?? 'N/A';
    echo "Marca: " . ($m['nombre'] ?? $nc) . " (nombre_clave=" . $nc . ")\n";
    
    // Buscar códigos por nombre_clave de la marca
    $codigos_by_nc = $col->find([
        '$or' => [
            ['marca' => $nc],
            ['nombre_clave_marca' => $nc],
            ['marca' => new MongoDB\BSON\Regex(preg_quote($nc), 'i')]
        ],
        'estado' => ['$gte' => 0],
        'num_beneficio' => ['$gt' => 50]
    ]);
    $count = 0;
    foreach ($codigos_by_nc as $c) {
        $count++;
        echo "  " . $c['_id'] . " | beneficio=" . $c['num_beneficio'] . " | marca_field=" . ($c['marca'] ?? '?') . "\n";
        $col->updateOne(
            ['_id' => $c['_id']],
            ['$set' => ['num_beneficio' => 50]]
        );
        echo "  -> Corregido a 50\n";
    }
    if ($count === 0) echo "  Sin códigos excesivos\n";
}

// 4. Verificación final
echo "\n=== VERIFICACIÓN FINAL ===\n";
$remaining = $col->countDocuments([
    '$or' => [
        ['marca' => new MongoDB\BSON\Regex('octopus', 'i')],
        ['descripcion' => new MongoDB\BSON\Regex('octopus', 'i')]
    ],
    'estado' => ['$gte' => 0],
    'num_beneficio' => ['$gt' => 50]
]);
echo "Códigos Octopus activos con beneficio > 50: " . $remaining . "\n";
