<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();
$code = $collection_codigos->findOne(['marca' => 'nuevamarcatribbu']);
print_r($code);

$collection_usuarios = getCollectionUsuarios();
$juanlu = $collection_usuarios->findOne(['username' => 'Juanlu']);
if ($juanlu) {
    echo "Buscando codigos de Juanlu...\n";
    $codes = $collection_codigos->find(
        ['id_usuario' => $juanlu['_id']], // maybe id_usuario and ObjectId?
        ['sort' => ['fecha_publicacion' => -1], 'limit' => 5]
    );
    foreach ($codes as $c) {
        echo $c['_id'] . " - " . $c['marca'] . "\n";
    }
    
    $codes2 = $collection_codigos->find(
        ['id_usuario' => (string)$juanlu['_id']],
        ['sort' => ['fecha_publicacion' => -1], 'limit' => 5]
    );
    foreach ($codes2 as $c) {
        echo $c['_id'] . " - " . (is_string($c['marca']) ? $c['marca'] : 'N/A') . "\n";
    }
}
