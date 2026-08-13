<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection = getCollectionCodeViewers();
// To make it fast, skip sorting if it hangs, just find one that has a recent date.
$cursor = $collection->find([], ["sort" => ["_id" => -1], "limit" => 1]);
$docs = $cursor->toArray();
if (count($docs) > 0) {
    echo "Keys:\n";
    print_r(array_keys((array)$docs[0]));
    echo "Doc:\n";
    print_r($docs[0]);
} else {
    echo "No docs found.\n";
}
