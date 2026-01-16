<?php
// Enable errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting debug...\n";

require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_codigo.php';
require_once __DIR__ . '/myphp/_super_landing_functions.php';

echo "Functions loaded.\n";

$codigo_id = '5c491a352f55c844162d7ae2';
echo "Checking code: $codigo_id\n";

$db = createConnection();
$codigos_coll = $db->selectCollection('codigos');
$codigo = $codigos_coll->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

if (!$codigo) {
    echo "Code not found!\n";
    exit;
}

echo "Code Brand (marca): " . $codigo['marca'] . "\n";

echo "Calling get_active_super_landings...\n";
$all_sl = get_active_super_landings(50);
echo "Count: " . count($all_sl) . "\n";

foreach ($all_sl as $sl) {
    echo "  Checking SL: " . $sl['title'] . "\n";
    if (isset($sl['linked_brand_slugs'])) {
        $slugs = is_object($sl['linked_brand_slugs']) ? iterator_to_array($sl['linked_brand_slugs']) : $sl['linked_brand_slugs'];
        echo "    Slugs: " . json_encode($slugs) . "\n";
        
        if (in_array($codigo['marca'], $slugs)) {
            echo "    MATCH FOUND!\n";
        } else {
             // Debug why it didn't match
             echo "    No match for '" . $codigo['marca'] . "'\n";
        }
    } else {
        echo "    No linked_brand_slugs field found.\n";
    }
}
?>
