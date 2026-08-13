<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection = getCollectionCodeViewers();

// find any document that has viewer_user_id
$doc = $collection->findOne(['viewer_user_id' => ['$exists' => true]]);
if ($doc) {
    echo "Found! ID: " . $doc['_id'] . "\n";
    print_r((array)$doc);
} else {
    echo "Definitely not found!\n";
}
