<?php
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';

$chollo_id = '697218789bb5a33865065483';

try {
    $db = createConnection();
    $collection = $db->selectCollection('chollos');
    $doc = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);

    if ($doc) {
        echo "ID: " . (string)$doc['_id'] . "\n";
        echo "Titulo: " . ($doc['titulo'] ?? 'N/A') . "\n";
        echo "Enlace DB: " . ($doc['enlace'] ?? 'N/A') . "\n";
        echo "Enlace Expandido: " . ($doc['enlace_expandido'] ?? 'N/A') . "\n";
        echo "ASIN: " . ($doc['asin'] ?? 'N/A') . "\n";
        // Check if there are any other fields that might interfere
    } else {
        echo "Chollo no encontrado.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
