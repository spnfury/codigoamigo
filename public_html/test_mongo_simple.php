<?php
require_once "/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php";
$db = createConnection();
// Find one with an Amazon link that likely needs redirection
$cursor = $db->chollos->find(
    ["enlace" => new MongoDB\BSON\Regex("amazon", "i")], 
    ["limit" => 20, "sort" => ["_id" => -1]]
);

foreach ($cursor as $doc) {
    $link = $doc["enlace"];
    // Skip ganga/chollo.biz as they might trigger the resolution page which we want to avoid for this simple test if possible,
    // although our fix is in app_with_mongo which handles the initial request anyway.
    if (strpos($link, "ganga.ad") === false && strpos($link, "chollo.biz") === false) {
        echo "ID: " . $doc["_id"] . "\n";
        echo "Link: " . $link . "\n";
        break;
    }
}
?>
