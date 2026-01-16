<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/public_html/vendor/autoload.php';
require_once __DIR__ . '/public_html/myphp/funciones_chollos_amazon.php';
require_once __DIR__ . '/public_html/myphp/funciones.php'; 

$id = "69681ff7832c0f9d360352c3";

echo "Testing Chollo ID: $id\n";

try {
    $db = createConnection();
    $collection = $db->selectCollection('chollos');
    $chollo = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    
    if ($chollo) {
        echo "Chollo found: " . $chollo['titulo'] . "\n";
        echo "Link: " . $chollo['enlace'] . "\n";
        
        $isAmazon = esEnlaceAmazon($chollo['enlace']);
        echo "Es Amazon? " . ($isAmazon ? 'SI' : 'NO') . "\n";
        
        $asin = extraerASIN($chollo['enlace']);
        echo "ASIN extracted: " . ($asin ? $asin : 'NULL') . "\n";

        $expanded = expandirAcortadorAmazon($chollo['enlace']);
        echo "Expanded Link: " . $expanded . "\n";
        
        $asin_expanded = extraerASIN($expanded);
        echo "ASIN from expanded: " . ($asin_expanded ? $asin_expanded : 'NULL') . "\n";
        
        if (empty($asin) && !empty($chollo['asin'])) {
             echo "ASIN from DB field: " . $chollo['asin'] . "\n";
        }
    } else {
        echo "Chollo NOT found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
