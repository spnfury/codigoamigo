<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();

// 1. Update the codes with brand 'nuevamarcatribbu' to 'tribbu'
$updateResult = $collection_codigos->updateMany(
    ['marca' => 'nuevamarcatribbu'],
    ['$set' => ['marca' => 'tribbu']]
);

echo "Modified " . $updateResult->getModifiedCount() . " codes from nuevamarcatribbu to tribbu.\n";

// 2. Fix the `prioridad_pago` for Juanlu's recently sponsored codes so they show up.
// Specifically the new 'tribbu' limit we just updated which had destacado = 1771660555
$codigo_tribbu = $collection_codigos->findOne([
    'marca' => 'tribbu',
    'id_usuario' => '67d62487c99054efb103f8e2',
    'destacado' => 1771660555
]);

if ($codigo_tribbu) {
    echo "Found tribbu code, setting prioridad_pago to " . $codigo_tribbu['destacado'] . "\n";
    $collection_codigos->updateOne(
        ['_id' => $codigo_tribbu['_id']],
        ['$set' => ['prioridad_pago' => $codigo_tribbu['destacado']]]
    );
}

// 3. Also check the octopus energy code, maybe we should sync su prioridad de pago?
$codigo_octopus = $collection_codigos->findOne([
    '_id' => new MongoDB\BSON\ObjectId('67d625d445324616b606f282') 
]);
if ($codigo_octopus && empty($codigo_octopus['prioridad_pago'])) {
     $collection_codigos->updateOne(
        ['_id' => $codigo_octopus['_id']],
        ['$set' => ['prioridad_pago' => $codigo_octopus['destacado_social']]]
    );
     echo "Updated octopusenergy prioridad_pago.\n";
} else {
    echo "Octopus energy already has prioridad_pago: " . (isset($codigo_octopus['prioridad_pago']) ? $codigo_octopus['prioridad_pago'] : 'N/A') . "\n";
}
