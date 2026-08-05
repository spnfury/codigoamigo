<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';

$id = $argv[1] ?? null;
$new_url = $argv[2] ?? null;
$asin = $argv[3] ?? null;

if (!$id || !$new_url) {
    die("Usage: php manual_fix_chollo.php [id] [new_url] [asin]\n");
}

$collection = getCollectionChollos();
$result = $collection->updateOne(
    ["_id" => new MongoDB\BSON\ObjectId($id)],
    ['$set' => [
        "enlace" => $new_url,
        "enlace_expandido" => $new_url,
        "asin" => $asin,
        "fecha_expansion" => new MongoDB\BSON\UTCDateTime()
    ]]
);

echo "Update Result for $id:\n";
echo "Matched: " . $result->getMatchedCount() . "\n";
echo "Modified: " . $result->getModifiedCount() . "\n";
