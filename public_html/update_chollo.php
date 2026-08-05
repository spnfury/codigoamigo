<?php
require_once __DIR__ . '/myphp/funciones.php';
$db = createConnection();
$col = $db->selectCollection('chollos');
$id = '69709ca29012cfdc9f07dc75';
$updateData = [
    'enlace' => 'https://www.amazon.es/dp/B0C6XT2CMW?tag=spnfuryy-21',
    'enlace_expandido' => 'https://www.amazon.es/dp/B0C6XT2CMW?tag=spnfuryy-21',
    'asin' => 'B0C6XT2CMW',
    'fecha_expansion' => new MongoDB\BSON\UTCDateTime()
];

$result = $col->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($id)],
    ['$set' => $updateData]
);

if ($result->getModifiedCount() > 0) {
    echo "Update successful\n";
} else {
    echo "Update failed or no changes made\n";
    var_dump($result->getUpsertedId());
}
