<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones_chollos.php';

$collection = getCollectionChollos();
if ($collection) {
    $doc = $collection->findOne([]);
    if ($doc) {
       foreach ($doc as $k => $v) {
           echo "$k: ";
           if (is_scalar($v)) echo $v;
           else echo gettype($v);
           echo "\n";
       }
    } else {
        echo "No documents found";
    }
}
