<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection_viewers = getCollectionCodeViewers();
$total = $collection_viewers->countDocuments();
$with_user_id = $collection_viewers->countDocuments(["viewer_user_id" => ['$exists' => true, '$ne' => null]]);
echo "Total viewers in DB: " . $total . "\n";
echo "Total with viewer_user_id: " . $with_user_id . "\n";

$sample = $collection_viewers->findOne(["viewer_user_id" => ['$exists' => true, '$ne' => null]]);
if ($sample) {
    echo "Sample with viewer_user_id:\n";
    print_r($sample);
} else {
    echo "NO SAMPLES FOUND!\n";
}
