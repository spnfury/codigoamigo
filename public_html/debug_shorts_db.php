<?php
require_once "/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php";
$db = createConnection();

echo "Testing Chollos Aggregate...\n";
$match = ["enlace" => ['$ne' => null], "imagen" => ['$ne' => null]];
$cursor = $db->chollos->aggregate([
    ['$match' => $match],
    ['$sort' => ['fecha_inicio' => -1]],
    ['$limit' => 5]
]);
foreach ($cursor as $doc) {
    echo "ID: " . $doc['_id'] . " | Titulo: " . $doc['titulo'] . "\n";
}

echo "\nTesting Curated Shorts...\n";
$total_curated = $db->curated_shorts->countDocuments(['active' => true]);
echo "Total active curated: $total_curated\n";
if ($total_curated > 0) {
    $c = $db->curated_shorts->findOne(['active' => true]);
    echo "Sample Curated Video ID: " . $c['video_id'] . "\n";
}
?>
