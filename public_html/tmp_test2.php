<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection = getCollectionCodeViewers();
$cursor = $collection->find([]);
$count = 0;
foreach ($cursor as $doc) {
    // encode to json
    $json = MongoDB\BSON\toJSON(MongoDB\BSON\fromPHP($doc));
    if (strpos($json, "viewer_user_id") !== false) {
        $count++;
    }
}
echo "Found viewer_user_id in $count docs.\n";
