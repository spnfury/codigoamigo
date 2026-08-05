<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';

$id = '6968dddadc02720ebb08c944';
$collection = getCollectionChollos();
$doc = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

if ($doc) {
    echo "ID: " . $doc['_id'] . "\n";
    echo "Titulo: " . ($doc['titulo'] ?? '') . "\n";
    echo "Enlace: " . ($doc['enlace'] ?? '') . "\n";
    echo "Enlace Original: " . ($doc['enlace_original'] ?? '') . "\n";
    echo "Enlace Expandido: " . ($doc['enlace_expandido'] ?? '') . "\n";
    echo "ASIN: " . ($doc['asin'] ?? '') . "\n";
    echo "Fuente: " . ($doc['fuente'] ?? '') . "\n";
} else {
    echo "Chollo no encontrado.\n";
}
