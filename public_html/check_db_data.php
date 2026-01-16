<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php'; 

$db = createConnection();
echo "Connected.\n";

// Check code
$codigo_id = '5c491a352f55c844162d7ae2';
$codigos = $db->selectCollection('codigos');
$code = $codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
echo "Code Brand: " . ($code['marca'] ?? 'N/A') . "\n";

// Check Super Landing
$sl = $db->selectCollection('super_landings');
// Try to find the ING landing (assuming slug 'mejores-cuentas-ing' or similar, or just list all)
$cursor = $sl->find([]);
foreach ($cursor as $landing) {
    echo "Landing: " . $landing['title'] . " (Slug: " . $landing['slug'] . ")\n";
    echo "  Status: " . ($landing['status'] ?? 'N/A') . "\n";
    if (isset($landing['linked_brand_slugs'])) {
        $slugs = $landing['linked_brand_slugs'];
        if (is_object($slugs)) {
             $slugs = iterator_to_array($slugs);
        }
        echo "  Linked Slugs: " . json_encode($slugs) . "\n";
    } else {
        echo "  No linked_brand_slugs\n";
    }
    echo "----------------\n";
}
?>
