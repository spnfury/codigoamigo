<?php
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/_super_landing_functions.php';

$db = createConnection();
$super_landings = $db->selectCollection('super_landings');
$codigos = $db->selectCollection('codigos');

$slug = 'mejores-neobancos-2026';
$landing = $super_landings->findOne(['slug' => $slug]);

if (!$landing) {
    die("Landing not found\n");
}

echo "Landing found: " . $landing['title'] . "\n";
echo "Type: " . ($landing['type'] ?? 'unknown') . "\n";

if (isset($landing['linked_brand_ids'])) {
    echo "Linked Brand IDs found: " . count($landing['linked_brand_ids']) . "\n";
    $ids = [];
    foreach ($landing['linked_brand_ids'] as $id) {
        $strId = (string)$id;
        $ids[] = $strId;
        echo "- ID: $strId (Type: " . get_class($id) . ")\n";
        
        // Count codes for this specific ID
        $count = $codigos->countDocuments(['marca_id' => $strId]);
        echo "  Codes in DB with marca_id='$strId': $count\n";
    }
    
    // Test the logic from the function
    $brand_ids = array_map(function($id) { return (string)$id; }, (array)$landing['linked_brand_ids']);
    $filter = ['marca_id' => ['$in' => $brand_ids], 'estado' => 1];
    $total = $codigos->countDocuments($filter);
    echo "Total codes matching filter: $total\n";
    
} else {
    echo "No linked_brand_ids found!\n";
    var_dump($landing);
}
?>
