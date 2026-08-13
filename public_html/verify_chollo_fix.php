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

echo "ID: " . $doc['_id'] . "\n";
echo "Enlace: " . ($doc['enlace'] ?? '') . "\n";
echo "Enlace Expandido: " . ($doc['enlace_expandido'] ?? '') . "\n";
echo "ASIN: " . ($doc['asin'] ?? '') . "\n";

$enlace = $doc['enlace'];
$es_amazon = esEnlaceAmazon($enlace);

if ($es_amazon) {
    // Verificar si seguiría redirigiendo a Self-Healing
    if (!empty($enlace) && (strpos($enlace, 'ganga.ad') !== false || strpos($enlace, 'chollo.biz') !== false)) {
        if (empty($doc['enlace_expandido']) || empty($doc['asin'])) {
            echo "RESULTADO: Todavía redirigiría a Self-Healing (ERROR)\n";
        } else {
            echo "RESULTADO: Pasaría por Self-Healing pero como ya está expandido redirigiría rápido (OK)\n";
        }
    } else {
        echo "RESULTADO: Redirección directa y limpia (EXCELENTE)\n";
    }
    
    $enlace_final = convertirEnlaceAmazonGarantizado($enlace, $chollo_id);
    echo "Enlace Final generado: $enlace_final\n";
    
    if (strpos($enlace_final, 'B0CLYS1XG7') !== false && strpos($enlace_final, 'tag=spnfuryy-21') !== false) {
        echo "VERIFICACIÓN: ENLACE CORRECTO Y TAGUEADO\n";
    } else {
        echo "VERIFICACIÓN: FALLO EN EL ENLACE O TAG\n";
    }
}
