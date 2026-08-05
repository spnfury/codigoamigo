<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();

$id_str = '6995ae51612a9486c10f0314';
try {
    $id = new MongoDB\BSON\ObjectId($id_str);
} catch (Exception $e) {
    $id = $id_str;
}

$codigo_tribbu = $collection_codigos->findOne(['_id' => $id]);
if (!$codigo_tribbu) {
    // try exact string just in case
    $codigo_tribbu = $collection_codigos->findOne(['_id' => $id_str]);
}

if ($codigo_tribbu) {
    echo "Found tribbu code: " . $codigo_tribbu['_id'] . "\n";
    echo "Destacado: " . $codigo_tribbu['destacado'] . "\n";
    
    // Check if what was highlighted was destacado_social
    $val = max(
        isset($codigo_tribbu['destacado']) ? (int)$codigo_tribbu['destacado'] : 0,
        isset($codigo_tribbu['destacado_social']) ? (int)$codigo_tribbu['destacado_social'] : 0
    );
    
    // Give it a boost if needed
    if ($val == 0) $val = time(); 
    
    $updateResult = $collection_codigos->updateOne(
        ['_id' => $codigo_tribbu['_id']],
        ['$set' => ['prioridad_pago' => $val]]
    );
    echo "Updated prioridad_pago to: " . $val . ". Modified docs: " . $updateResult->getModifiedCount() . "\n";
} else {
    echo "Tribbu code not found!\n";
}
