<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_usuarios = getCollectionUsuarios();
$juanlu = $collection_usuarios->findOne(['username' => 'Juanlu']);

if ($juanlu) {
    echo "Juanlu ID: " . $juanlu['_id'] . "\n";
    $collection_codigos = getCollectionCodigos();
    $codes = $collection_codigos->find(
        ['user_id' => (string) $juanlu['_id']],
        ['sort' => ['fecha_publicacion' => -1], 'limit' => 5]
    );

    foreach ($codes as $c) {
        // Handle MongoDB\BSON\UTCDateTime
        $dateStr = 'Unknown';
        if (isset($c['fecha_publicacion'])) {
            if ($c['fecha_publicacion'] instanceof MongoDB\BSON\UTCDateTime) {
                $dateStr = $c['fecha_publicacion']->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $dateStr = (string)$c['fecha_publicacion'];
            }
        }

        echo "=> ID: " . $c['_id'] . "\n";
        echo "   Titulo: " . $c['titulo'] . "\n";
        echo "   Marca: " . $c['marca'] . "\n";
        echo "   Estado: " . $c['estado'] . "\n";
        echo "   Destacado: " . (isset($c['destacado']) ? $c['destacado'] : 'N/A') . "\n";
        echo "   Destacado_social: " . (isset($c['destacado_social']) ? $c['destacado_social'] : 'N/A') . "\n";
        echo "   Prioridad_pago: " . (isset($c['prioridad_pago']) ? $c['prioridad_pago'] : 'N/A') . "\n";
        echo "   Fecha: " . $dateStr . "\n";
        echo "--------------------------\n";
    }
} else {
    echo "Usuario Juanlu no encontrado.\n";
}

echo "\n--- NUEVAMARCATRIBBU ---\n";
$codes_tribbu = $collection_codigos->find(
    ['marca' => 'nuevamarcatribbu']
);

foreach ($codes_tribbu as $c) {
    echo "=> ID: " . $c['_id'] . "\n";
    echo "   Titulo: " . $c['titulo'] . "\n";
    echo "   Marca: " . $c['marca'] . "\n";
    echo "   Estado: " . $c['estado'] . "\n";
    echo "   Destacado: " . (isset($c['destacado']) ? $c['destacado'] : 'N/A') . "\n";
    echo "   Destacado_social: " . (isset($c['destacado_social']) ? $c['destacado_social'] : 'N/A') . "\n";
    echo "--------------------------\n";
}
