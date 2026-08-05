<?php
require_once "/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php";
$db = createConnection();
$cursor = $db->chollos->find(
    ["enlace" => ['' => "amazon"]], 
    ["sort" => ["_id" => -1], "limit" => 10]
);
foreach ($cursor as $doc) {
    if (strpos($doc["enlace"], "ganga.ad") === false && strpos($doc["enlace"], "chollo.biz") === false) {
        echo "ID: " . $doc["_id"] . "\n";
        break;
    }
}
?>
