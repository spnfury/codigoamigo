<?php
require_once "/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php";
$db = createConnection();
$doc = $db->chollos->findOne(["enlace" => ['$ne' => null], "imagen" => ['$ne' => null]]);
print_r($doc);
?>
