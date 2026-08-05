<?php
require_once __DIR__ . '/myphp/funciones.php';
$db = createConnection();
$col = $db->selectCollection('chollos');
$query = [
    'titulo' => ['$regex' => 'The Valiant', '$options' => 'i'],
    'asin' => ['$exists' => true, '$ne' => null]
];
$cursor = $col->find($query);
foreach($cursor as $doc) {
    echo "ID: " . (string)$doc['_id'] . " | ASIN: " . ($doc['asin'] ?? 'N/A') . " | Titulo: " . $doc['titulo'] . "\n";
}
