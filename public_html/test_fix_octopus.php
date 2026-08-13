<?php
require "inc/includes.php"; 
$c = getCollectionCodigos(); 

$res = $c->updateOne(
    ["_id" => new MongoDB\BSON\ObjectId("67d625d445324616b606f282")], 
    ['$set' => ["prioridad_pago" => 1771660577]]
); 

echo "Matched: " . $res->getMatchedCount() . " Modified: " . $res->getModifiedCount() . "\n";
