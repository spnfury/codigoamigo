<?php
require_once __DIR__ . '/inc/conexion.php';

$db = createConnection();
$codigos = $db->selectCollection('codigos');

$brand_slug = 'n26';
echo "Searching for code with marca='$brand_slug'...\n";

$code = $codigos->findOne(['marca' => $brand_slug]);

if ($code) {
    echo "Found Code ID: " . $code['_id'] . "\n";
    echo "marca: " . ($code['marca'] ?? 'MISSING') . "\n";
    echo "marca_id: " . ($code['marca_id'] ?? 'MISSING') . "\n";
    var_dump($code['marca_id']);
} else {
    echo "No code found for slug '$brand_slug'.\n";
    
    // Try regex
    $regex = new MongoDB\BSON\Regex($brand_slug, 'i');
    $code = $codigos->findOne(['marca' => $regex]);
    if ($code) {
        echo "Found via Regex ($brand_slug): " . $code['marca'] . "\n";
    }
}
?>
