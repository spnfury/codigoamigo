<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_marcas = getCollectionMarcas();

$marca_tribbu = $collection_marcas->findOne(['nombre_clave' => 'tribbu']);
if ($marca_tribbu) {
    echo "Tribbu exists in brands!\n";
} else {
    echo "Tribbu DOES NOT EXIST in brands!\n";
}

$marca_nueva = $collection_marcas->findOne(['nombre_clave' => 'nuevamarcatribbu']);
if ($marca_nueva) {
    echo "nuevamarcatribbu exists in brands! Deleting it...\n";
    $collection_marcas->deleteOne(['_id' => $marca_nueva['_id']]);
    echo "Deleted nuevamarcatribbu.\n";
} else {
    echo "nuevamarcatribbu DOES NOT EXIST in brands.\n";
}
