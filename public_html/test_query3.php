<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_codigos = getCollectionCodigos();

// Find recent sponsored codes
$recent_sponsored = $collection_codigos->find(
    ['$or' => [
        ['destacado' => ['$ne' => 0]],
        ['destacado_social' => ['$ne' => 0]]
    ]],
    ['sort' => ['fecha_publicacion' => -1], 'limit' => 10]
);

echo "Recent Sponsored Codes:\n";
foreach ($recent_sponsored as $c) {
    echo "ID: " . $c['_id'] . " | Marca: " . $c['marca'] . " | User ID: " . (isset($c['id_usuario']) ? $c['id_usuario'] : 'N/A') . " | Destacado: " . (isset($c['destacado']) ? $c['destacado'] : 0) . " | Social: " . (isset($c['destacado_social']) ? $c['destacado_social'] : 0) . "\n";
}

$collection_usuarios = getCollectionUsuarios();
$juanlu = $collection_usuarios->findOne(['username' => 'Juanlu']);
echo "\nJuanlu User ID: " . $juanlu['_id'] . "\n";

