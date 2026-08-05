<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';
require_once __DIR__ . '/myphp/funciones_chollos_amazon.php';

$chollo_id = '6968dddadc02720ebb08c944';
$collection = getCollectionChollos();
$doc = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);

if (!$doc) {
    die("Chollo no encontrado");
}

$enlace = $doc['enlace'];
echo "Enlace DB: $enlace\n";

$es_amazon = esEnlaceAmazon($enlace);
echo "Es Amazon: " . ($es_amazon ? 'SI' : 'NO') . "\n";

if ($es_amazon) {
    if (!empty($enlace) && (strpos($enlace, 'ganga.ad') !== false || strpos($enlace, 'chollo.biz') !== false)) {
        if (empty($doc['enlace_expandido']) || empty($doc['asin'])) {
            echo "REDIRIGIENDO A SELF-HEALING RESOLVER\n";
        }
    }
    
    $enlace_final = convertirEnlaceAmazonGarantizado($enlace, $chollo_id);
    echo "Enlace Final (Garantizado): $enlace_final\n";
}
