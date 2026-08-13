<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();

// Insert a fake code for testing
$fake_id = new MongoDB\BSON\ObjectId();
$collection_codigos->insertOne([
    '_id' => $fake_id,
    'codigo' => 'TEST' . time(),
    'marca' => 'test_brand',
    'id_usuario' => '67d62487c99054efb103f8e2',
    'estado' => 0
]);

echo "Created fake code: " . $fake_id . "\n";

// Use the modern highglighting function
$success = destacar_codigo_moderno((string)$fake_id, 'super');

if ($success) {
    echo "Highlighting worked.\n";
    $updated_code = $collection_codigos->findOne(['_id' => $fake_id]);
    echo "Destacado: " . (isset($updated_code['destacado']) ? $updated_code['destacado'] : 'N/A') . "\n";
    echo "Destacado Social: " . (isset($updated_code['destacado_social']) ? $updated_code['destacado_social'] : 'N/A') . "\n";
    echo "Prioridad Pago: " . (isset($updated_code['prioridad_pago']) ? $updated_code['prioridad_pago'] : 'N/A') . "\n";
} else {
    echo "Highlighting failed.\n";
}

// Cleanup
$collection_codigos->deleteOne(['_id' => $fake_id]);
echo "Fake code deleted.\n";
