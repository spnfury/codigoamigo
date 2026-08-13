<?php
require_once __DIR__ . '/public_html/inc/includes.php';
require_once __DIR__ . '/public_html/myphp/funciones_faq.php';

$brand = 'airbnb'; // Test with airbnb or any other
echo "Checking FAQs for brand: $brand\n";

$faqs = getFAQsByMarca($brand, false); // Include inactive too for debugging
echo "Found " . count($faqs) . " FAQs.\n";

foreach ($faqs as $faq) {
    echo "ID: " . $faq['_id'] . "\n";
    echo "Title: " . $faq['titulo'] . "\n";
    echo "Active: " . ($faq['activa'] ? 'Yes' : 'No') . "\n";
    echo "Marca Clave: " . $faq['marca_clave'] . "\n";
    echo "-------------------\n";
}

// Also check the marcas collection for this brand
$coll_marcas = getCollectionMarcas();
$m = $coll_marcas->findOne(['nombre_clave' => $brand]);
if ($m) {
    echo "Brand doc found: " . $m['nombre'] . " (nombre_clave: " . $m['nombre_clave'] . ")\n";
} else {
    echo "Brand doc NOT found for slug: $brand\n";
}
