<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();

$juanlu_id = "5ec7078ba11b5d4fa90de974";
$juanlu_codes = $collection_codigos->find(
    ['id_usuario' => $juanlu_id]
);

echo "Códigos de Juanlu:\n";
foreach ($juanlu_codes as $c) {
    echo "ID: " . $c['_id'] . " | Marca: " . $c['marca'] . " | Destacado: " . (isset($c['destacado']) ? $c['destacado'] : 0) . "\n";
}

// Any sponsored code from nuevamarcatribbu?
$tribbu_codes = $collection_codigos->find(['marca' => 'nuevamarcatribbu']);
echo "\nCódigos nuevamarcatribbu:\n";
foreach ($tribbu_codes as $c) {
    echo "ID: " . $c['_id'] . " | Marca: " . $c['marca'] . " | User ID: " . (isset($c['id_usuario']) ? $c['id_usuario'] : 'N/A') . " | Destacado: " . (isset($c['destacado']) ? $c['destacado'] : 0) . " | Estado: " . $c['estado'] . "\n";
}
